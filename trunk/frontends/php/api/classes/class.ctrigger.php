<?php
/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
/**
 * File containing CTrigger class for API.
 *
 * @package API
 */


class CTrigger extends CZBXAPI {
	/**
	 * Get Triggers data
	 *
	 * @param array $options
	 * @param array $options['itemids']
	 * @param array $options['hostids']
	 * @param array $options['groupids']
	 * @param array $options['triggerids']
	 * @param array $options['applicationids']
	 * @param array $options['status']
	 * @param array $options['editable']
	 * @param array $options['count']
	 * @param array $options['pattern']
	 * @param array $options['limit']
	 * @param array $options['order']
	 *
	 * @return array|int item data as array or false if error
	 */
	public function get(array $options = array()) {

		$result = array();
		$user_type = self::$userData['type'];
		$userid = self::$userData['userid'];

		// allowed columns for sorting
		$sort_columns = array(
			'triggerid',
			'description',
			'status',
			'priority',
			'lastchange',
			'hostname'
		);
		// allowed output options for [ select_* ] params
		$subselects_allowed_outputs = array(
			API_OUTPUT_REFER,
			API_OUTPUT_EXTEND
		);

		$fields_to_unset = array();

		$sql_parts = array(
			'select' => array('triggers' => 't.triggerid'),
			'from' => array('t' => 'triggers t'),
			'where' => array(),
			'group' => array(),
			'order' => array(),
			'limit' => null,
		);

		$def_options = array(
			'nodeids' => null,
			'groupids' => null,
			'templateids' => null,
			'hostids' => null,
			'triggerids' => null,
			'itemids' => null,
			'applicationids' => null,
			'discoveryids' => null,
			'functions' => null,
			'inherited' => null,
			'templated' => null,
			'monitored' => null,
			'active' => null,
			'maintenance' => null,

			'withUnacknowledgedEvents' => null,
			'withAcknowledgedEvents' => null,
			'withLastEventUnacknowledged' => null,

			'skipDependent' => null,
			'nopermissions' => null,
			'editable' => null,
// timing
			'lastChangeSince' => null,
			'lastChangeTill' => null,
// filter
			'group' => null,
			'host' => null,
			'only_true' => null,
			'min_severity' => null,

			'filter' => null,
			'search' => null,
			'searchByAny' => null,
			'startSearch' => null,
			'excludeSearch' => null,
			'searchWildcardsEnabled' => null,

// output
			'expandData' => null,
			'expandDescription' => null,
			'output' => API_OUTPUT_REFER,
			'selectGroups' => null,
			'selectHosts' => null,
			'selectItems' => null,
			'selectFunctions' => null,
			'selectDependencies' => null,
			'selectDiscoveryRule' => null,
			'countOutput' => null,
			'groupCount' => null,
			'preservekeys' => null,

			'sortfield' => '',
			'sortorder' => '',
			'limit' => null,
			'limitSelects' => null
		);

		$options = zbx_array_merge($def_options, $options);

		if (is_array($options['output'])) {
			unset($sql_parts['select']['triggers']);

			$dbTable = DB::getSchema('triggers');
			$sql_parts['select']['triggerid'] = ' t.triggerid';
			foreach ($options['output'] as $field) {
				if (isset($dbTable['fields'][$field])) {
					$sql_parts['select'][$field] = 't.'.$field;
				}
			}

			if (!is_null($options['expandDescription'])) {
				if (!str_in_array('description', $options['output'])) {
					$options['expandDescription'] = null;
				}
				else {
					if (!str_in_array('expression', $options['output'])) {
						$sql_parts['select']['expression'] = ' t.expression';
						$fields_to_unset[] = 'expression';
					}
				}
			}

			$options['output'] = API_OUTPUT_CUSTOM;
		}

		// editable + PERMISSION CHECK

		if ((USER_TYPE_SUPER_ADMIN == $user_type) || $options['nopermissions']) {
		}
		else {
			$permission = $options['editable'] ? PERM_READ_WRITE : PERM_READ_ONLY;

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['from']['rights'] = 'rights r';
			$sql_parts['from']['users_groups'] = 'users_groups ug';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where'][] = 'r.id=hg.groupid';
			$sql_parts['where'][] = 'r.groupid=ug.usrgrpid';
			$sql_parts['where'][] = 'ug.userid='.$userid;
			$sql_parts['where'][] = 'r.permission>='.$permission;
			$sql_parts['where'][] = 'NOT EXISTS ('.
										' SELECT ff.triggerid'.
										' FROM functions ff,items ii'.
										' WHERE ff.triggerid=t.triggerid'.
											' AND ff.itemid=ii.itemid'.
											' AND EXISTS ('.
												' SELECT hgg.groupid'.
												' FROM hosts_groups hgg,rights rr,users_groups gg'.
												' WHERE hgg.hostid=ii.hostid'.
													' AND rr.id=hgg.groupid'.
													' AND rr.groupid=gg.usrgrpid'.
													' AND gg.userid='.$userid.
													' AND rr.permission<'.$permission.'))';
		}

		// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

		// groupids
		if (!is_null($options['groupids'])) {
			zbx_value2array($options['groupids']);

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['groupid'] = 'hg.groupid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['groupid'] = DBcondition('hg.groupid', $options['groupids']);

			if (!is_null($options['groupCount'])) {
				$sql_parts['group']['hg'] = 'hg.groupid';
			}
		}

		// templateids
		if (!is_null($options['templateids'])) {
			zbx_value2array($options['templateids']);

			if (!is_null($options['hostids'])) {
				zbx_value2array($options['hostids']);
				$options['hostids'] = array_merge($options['hostids'], $options['templateids']);
			}
			else {
				$options['hostids'] = $options['templateids'];
			}
		}

		// hostids
		if (!is_null($options['hostids'])) {
			zbx_value2array($options['hostids']);

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['hostid'] = 'i.hostid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['where']['hostid'] = DBcondition('i.hostid', $options['hostids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';

			if (!is_null($options['groupCount'])) {
				$sql_parts['group']['i'] = 'i.hostid';
			}
		}

		// triggerids
		if (!is_null($options['triggerids'])) {
			zbx_value2array($options['triggerids']);

			$sql_parts['where']['triggerid'] = DBcondition('t.triggerid', $options['triggerids']);
		}

		// itemids
		if (!is_null($options['itemids'])) {
			zbx_value2array($options['itemids']);

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['itemid'] = 'f.itemid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['where']['itemid'] = DBcondition('f.itemid', $options['itemids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';

			if (!is_null($options['groupCount'])) {
				$sql_parts['group']['f'] = 'f.itemid';
			}
		}

		// applicationids
		if (!is_null($options['applicationids'])) {
			zbx_value2array($options['applicationids']);

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['applicationid'] = 'a.applicationid';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['applications'] = 'applications a';
			$sql_parts['where']['a'] = DBcondition('a.applicationid', $options['applicationids']);
			$sql_parts['where']['ia'] = 'i.hostid=a.hostid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
		}

		// discoveryids
		if (!is_null($options['discoveryids'])) {
			zbx_value2array($options['discoveryids']);

			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['itemid'] = 'id.parent_itemid';
			}
			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['item_discovery'] = 'item_discovery id';
			$sql_parts['where']['fid'] = 'f.itemid=id.itemid';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where'][] = DBcondition('id.parent_itemid', $options['discoveryids']);

			if (!is_null($options['groupCount'])) {
				$sql_parts['group']['id'] = 'id.parent_itemid';
			}
		}

		// functions
		if (!is_null($options['functions'])) {
			zbx_value2array($options['functions']);

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where'][] = DBcondition('f.function', $options['functions']);
		}

		// monitored
		if (!is_null($options['monitored'])) {
			$sql_parts['where']['monitored'] = ''.
				' NOT EXISTS ('.
					' SELECT ff.functionid'.
					' FROM functions ff'.
					' WHERE ff.triggerid=t.triggerid'.
						' AND EXISTS ('.
							' SELECT ii.itemid'.
							' FROM items ii,hosts hh'.
							' WHERE ff.itemid=ii.itemid'.
								' AND hh.hostid=ii.hostid'.
								' AND ('.
									' ii.status<>'.ITEM_STATUS_ACTIVE.
									' OR hh.status<>'.HOST_STATUS_MONITORED.
								' )'.
						' )'.
				' )';
			$sql_parts['where']['status'] = 't.status='.TRIGGER_STATUS_ENABLED;
		}

		// active
		if (!is_null($options['active'])) {
			$sql_parts['where']['active'] = ''.
				' NOT EXISTS ('.
					' SELECT ff.functionid'.
					' FROM functions ff'.
					' WHERE ff.triggerid=t.triggerid'.
						' AND EXISTS ('.
							' SELECT ii.itemid'.
							' FROM items ii,hosts hh'.
							' WHERE ff.itemid=ii.itemid'.
								' AND hh.hostid=ii.hostid'.
								' AND  hh.status<>'.HOST_STATUS_MONITORED.
						' )'.
				' )';
			$sql_parts['where']['status'] = 't.status='.TRIGGER_STATUS_ENABLED;
		}

		// maintenance
		if (!is_null($options['maintenance'])) {
			$sql_parts['where'][] = (($options['maintenance'] == 0) ? ' NOT ' : '').
				' EXISTS ('.
					' SELECT ff.functionid'.
					' FROM functions ff'.
					' WHERE ff.triggerid=t.triggerid'.
						' AND EXISTS ('.
							' SELECT ii.itemid'.
							' FROM items ii,hosts hh'.
							' WHERE ff.itemid=ii.itemid'.
								' AND hh.hostid=ii.hostid'.
								' AND hh.maintenance_status=1'.
						' )'.
				' )';
			$sql_parts['where'][] = 't.status='.TRIGGER_STATUS_ENABLED;
		}

		// lastChangeSince
		if (!is_null($options['lastChangeSince'])) {
			$sql_parts['where']['lastchangesince'] = 't.lastchange>'.$options['lastChangeSince'];
		}

		// lastChangeTill
		if (!is_null($options['lastChangeTill'])) {
			$sql_parts['where']['lastchangetill'] = 't.lastchange<'.$options['lastChangeTill'];
		}

		// withUnacknowledgedEvents
		if (!is_null($options['withUnacknowledgedEvents'])) {
			$sql_parts['where']['unack'] = ' EXISTS ('.
				' SELECT e.eventid'.
				' FROM events e'.
				' WHERE e.objectid=t.triggerid'.
					' AND e.object='.EVENT_OBJECT_TRIGGER.
					' AND e.value_changed='.TRIGGER_VALUE_CHANGED_YES.
					' AND e.value='.TRIGGER_VALUE_TRUE.
					' AND e.acknowledged=0)';
		}
		// withAcknowledgedEvents
		if (!is_null($options['withAcknowledgedEvents'])) {
			$sql_parts['where']['ack'] = 'NOT EXISTS ('.
				' SELECT e.eventid'.
				' FROM events e'.
				' WHERE e.objectid=t.triggerid'.
					' AND e.object='.EVENT_OBJECT_TRIGGER.
					' AND e.value_changed='.TRIGGER_VALUE_CHANGED_YES.
					' AND e.value='.TRIGGER_VALUE_TRUE.
					' AND e.acknowledged=0)';
		}

		// templated
		if (!is_null($options['templated'])) {
			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';

			if ($options['templated']) {
				$sql_parts['where'][] = 'h.status='.HOST_STATUS_TEMPLATE;
			}
			else {
				$sql_parts['where'][] = 'h.status<>'.HOST_STATUS_TEMPLATE;
			}
		}

		// inherited
		if (!is_null($options['inherited'])) {
			if ($options['inherited']) {
				$sql_parts['where'][] = 't.templateid IS NOT NULL';
			}
			else {
				$sql_parts['where'][] = 't.templateid IS NULL';
			}
		}

		// search
		if (is_array($options['search'])) {
			zbx_db_search('triggers t', $options, $sql_parts);
		}

		// --- FILTER ---
		if (is_null($options['filter'])) {
			$options['filter'] = array();
		}

		if (is_array($options['filter'])) {
			if (!array_key_exists('flags', $options['filter'])) {
				$options['filter']['flags'] = array(
					ZBX_FLAG_DISCOVERY_NORMAL,
					ZBX_FLAG_DISCOVERY_CREATED
				);
			}

			zbx_db_filter('triggers t', $options, $sql_parts);

			if (isset($options['filter']['host']) && !is_null($options['filter']['host'])) {
				zbx_value2array($options['filter']['host']);

				$sql_parts['from']['functions'] = 'functions f';
				$sql_parts['from']['items'] = 'items i';
				$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
				$sql_parts['where']['fi'] = 'f.itemid=i.itemid';

				$sql_parts['from']['hosts'] = 'hosts h';
				$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
				$sql_parts['where']['host'] = DBcondition('h.host', $options['filter']['host']);
			}

			if (isset($options['filter']['hostid']) && !is_null($options['filter']['hostid'])) {
				zbx_value2array($options['filter']['hostid']);

				$sql_parts['from']['functions'] = 'functions f';
				$sql_parts['from']['items'] = 'items i';
				$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
				$sql_parts['where']['fi'] = 'f.itemid=i.itemid';

				$sql_parts['where']['hostid'] = DBcondition('i.hostid', $options['filter']['hostid']);
			}
		}

		// group
		if (!is_null($options['group'])) {
			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['name'] = 'g.name';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts_groups'] = 'hosts_groups hg';
			$sql_parts['from']['groups'] = 'groups g';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hgi'] = 'hg.hostid=i.hostid';
			$sql_parts['where']['ghg'] = 'g.groupid = hg.groupid';
			$sql_parts['where']['group'] = ' UPPER(g.name)='.zbx_dbstr(zbx_strtoupper($options['group']));
		}

		// host
		if (!is_null($options['host'])) {
			if ($options['output'] != API_OUTPUT_SHORTEN) {
				$sql_parts['select']['host'] = 'h.host';
			}

			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['i'] = DBcondition('i.hostid', $options['hostids']);
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
			$sql_parts['where']['host'] = ' UPPER(h.host)='.zbx_dbstr(zbx_strtoupper($options['host']));
		}

		// only_true
		if (!is_null($options['only_true'])) {
			$config = select_config();
			$sql_parts['where']['ot'] = '((t.value='.TRIGGER_VALUE_TRUE.')'.
					' OR '.
					'((t.value='.TRIGGER_VALUE_FALSE.') AND (t.lastchange>'.(time() - $config['ok_period']).')))';
		}

		// min_severity
		if (!is_null($options['min_severity'])) {
			$sql_parts['where'][] = 't.priority>='.$options['min_severity'];
		}

		// output
		if ($options['output'] == API_OUTPUT_EXTEND) {
			$sql_parts['select']['triggers'] = 't.*';
		}

		// expandData
		if (!is_null($options['expandData'])) {
			$sql_parts['select']['host'] = 'h.host';
			$sql_parts['select']['hostid'] = 'h.hostid';
			$sql_parts['from']['functions'] = 'functions f';
			$sql_parts['from']['items'] = 'items i';
			$sql_parts['from']['hosts'] = 'hosts h';
			$sql_parts['where']['ft'] = 'f.triggerid=t.triggerid';
			$sql_parts['where']['fi'] = 'f.itemid=i.itemid';
			$sql_parts['where']['hi'] = 'h.hostid=i.hostid';
		}

		// countOutput
		if (!is_null($options['countOutput'])) {
			$options['sortfield'] = '';
			$sql_parts['select'] = array('COUNT(DISTINCT t.triggerid) as rowscount');

			// groupCount
			if (!is_null($options['groupCount'])) {
				foreach ($sql_parts['group'] as $key => $fields) {
					$sql_parts['select'][$key] = $fields;
				}
			}
		}

		// orderhosts
		// restrict not allowed columns for sorting
		$options['sortfield'] = str_in_array($options['sortfield'], $sort_columns) ? $options['sortfield'] : '';
		if (!zbx_empty($options['sortfield'])) {
			// DESC or ASC
			$sortorder = $options['sortorder'] == ZBX_SORT_DOWN ? ZBX_SORT_DOWN : ZBX_SORT_UP;

			// for postgreSQL column which is present in ORDER BY should also be present in SELECT
			// we will be using lastchange for ordering in any case
			if (!str_in_array('t.lastchange', $sql_parts['select']) && !str_in_array('t.*', $sql_parts['select'])) {
				$sql_parts['select']['lastchange'] = 't.lastchange';
			}

			switch ($options['sortfield']) {
				case 'hostname':
					// the only way to sort by host name is to get it like this:
					// triggers -> functions -> items -> hosts
					$sql_parts['select']['hostname'] = 'h.name';
					$sql_parts['from']['functions'] = 'functions f';
					$sql_parts['from']['items'] = 'items i';
					$sql_parts['from']['hosts'] = 'hosts h';
					$sql_parts['where'][] = 't.triggerid = f.triggerid';
					$sql_parts['where'][] = 'f.itemid = i.itemid';
					$sql_parts['where'][] = 'i.hostid = h.hostid';
					$sql_parts['order'][] = 'h.name '.$sortorder.', t.lastchange DESC';
					break;
				case 'lastchange':
					$sql_parts['order'][] = $options['sortfield'].' '.$sortorder;
					break;
				default:
					// adding sort field to SELECT part if it is not already there
					if (!str_in_array('t.'.$options['sortfield'], $sql_parts['select']) && !str_in_array('t.*', $sql_parts['select'])) {
						$sql_parts['select'][] = 't.'.$options['sortfield'];
					}
					// if lastchange is not used for ordering, it should be the second order criteria
					$sql_parts['order'][] = 't.'.$options['sortfield'].' '.$sortorder.', t.lastchange DESC';
					break;
			}
		}

		// limit
		$postLimit = false;
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			// to make limit work correctly with truncating filters (skipDependent, withLastEventUnacknowledged)
			// do select without limit, truncate result and then slice excess data
			if (!is_null($options['skipDependent']) || !is_null($options['withLastEventUnacknowledged'])) {
				$postLimit = $options['limit'];
				$sql_parts['limit'] = null;
			}
			else {
				$sql_parts['limit'] = $options['limit'];
			}
		}
		//---------------

		$triggerids = array();

		$sql_parts['select'] = array_unique($sql_parts['select']);
		$sql_parts['from'] = array_unique($sql_parts['from']);
		$sql_parts['where'] = array_unique($sql_parts['where']);
		$sql_parts['group'] = array_unique($sql_parts['group']);
		$sql_parts['order'] = array_unique($sql_parts['order']);

		$sql_select = '';
		$sql_from = '';
		$sql_where = '';
		$sql_group = '';
		$sql_order = '';
		if (!empty($sql_parts['select'])) {
			$sql_select .= implode(',', $sql_parts['select']);
		}
		if (!empty($sql_parts['from'])) {
			$sql_from .= implode(',', $sql_parts['from']);
		}
		if (!empty($sql_parts['where'])) {
			$sql_where .= ' AND '.implode(' AND ', $sql_parts['where']);
		}
		if (!empty($sql_parts['group'])) {
			$sql_where .= ' GROUP BY '.implode(',', $sql_parts['group']);
		}
		if (!empty($sql_parts['order'])) {
			$sql_order .= ' ORDER BY '.implode(',', $sql_parts['order']);
		}
		$sql_limit = $sql_parts['limit'];

		$sql = 'SELECT '.zbx_db_distinct($sql_parts).' '.$sql_select.
				' FROM '.$sql_from.
				' WHERE '.DBin_node('t.triggerid', $nodeids).
				$sql_where.
				$sql_group.
				$sql_order;

		$db_res = DBselect($sql, $sql_limit);
		while ($trigger = DBfetch($db_res)) {
			if (!is_null($options['countOutput'])) {
				if (!is_null($options['groupCount'])) {
					$result[] = $trigger;
				}
				else {
					$result = $trigger['rowscount'];
				}
			}
			else {
				$triggerids[$trigger['triggerid']] = $trigger['triggerid'];

				if ($options['output'] == API_OUTPUT_SHORTEN) {
					$result[$trigger['triggerid']] = array('triggerid' => $trigger['triggerid']);
				}
				else {
					if (!isset($result[$trigger['triggerid']])) {
						$result[$trigger['triggerid']] = array();
					}

					if (!is_null($options['selectHosts']) && !isset($result[$trigger['triggerid']]['hosts'])) {
						$result[$trigger['triggerid']]['hosts'] = array();
					}
					if (!is_null($options['selectItems']) && !isset($result[$trigger['triggerid']]['items'])) {
						$result[$trigger['triggerid']]['items'] = array();
					}
					if (!is_null($options['selectFunctions']) && !isset($result[$trigger['triggerid']]['functions'])) {
						$result[$trigger['triggerid']]['functions'] = array();
					}
					if (!is_null($options['selectDependencies']) && !isset($result[$trigger['triggerid']]['dependencies'])) {
						$result[$trigger['triggerid']]['dependencies'] = array();
					}
					if (!is_null($options['selectDiscoveryRule']) && !isset($result[$trigger['triggerid']]['discoveryRule'])) {
						$result[$trigger['triggerid']]['discoveryRule'] = array();
					}

					// groups
					if (isset($trigger['groupid']) && is_null($options['selectGroups'])) {
						if (!isset($result[$trigger['triggerid']]['groups'])) {
							$result[$trigger['triggerid']]['groups'] = array();
						}

						$result[$trigger['triggerid']]['groups'][] = array('groupid' => $trigger['groupid']);
						unset($trigger['groupid']);
					}

					// hostids
					if (isset($trigger['hostid']) && is_null($options['selectHosts'])) {
						if (!isset($result[$trigger['triggerid']]['hosts'])) {
							$result[$trigger['triggerid']]['hosts'] = array();
						}

						$result[$trigger['triggerid']]['hosts'][] = array('hostid' => $trigger['hostid']);

						if (is_null($options['expandData'])) {
							unset($trigger['hostid']);
						}
					}
					// itemids
					if (isset($trigger['itemid']) && is_null($options['selectItems'])) {
						if (!isset($result[$trigger['triggerid']]['items'])) {
							$result[$trigger['triggerid']]['items'] = array();
						}

						$result[$trigger['triggerid']]['items'][] = array('itemid' => $trigger['itemid']);
						unset($trigger['itemid']);
					}

					$result[$trigger['triggerid']] += $trigger;
				}
			}
		}

		Copt::memoryPick();
		if (!is_null($options['countOutput'])) {
			return $result;
		}

		// skipDependent
		if (!is_null($options['skipDependent'])) {
			$tids = $triggerids;
			$map = array();

			do {
				$sql = 'SELECT d.triggerid_down, d.triggerid_up, t.value '.
						' FROM trigger_depends d, triggers t '.
						' WHERE '.DBcondition('d.triggerid_down', $tids).
							' AND d.triggerid_up=t.triggerid';
				$db_result = DBselect($sql);

				$tids = array();
				while ($row = DBfetch($db_result)) {
					if (TRIGGER_VALUE_TRUE == $row['value']) {
						if (isset($map[$row['triggerid_down']])) {
							foreach ($map[$row['triggerid_down']] as $triggerid => $state) {
								unset($result[$triggerid]);
								unset($triggerids[$triggerid]);
							}
						}
						else {
							unset($result[$row['triggerid_down']]);
							unset($triggerids[$row['triggerid_down']]);
						}
					}
					else {
						if (isset($map[$row['triggerid_down']])) {
							if (!isset($map[$row['triggerid_up']])) {
								$map[$row['triggerid_up']] = array();
							}

							$map[$row['triggerid_up']] += $map[$row['triggerid_down']];
						}
						else {
							if (!isset($map[$row['triggerid_up']])) {
								$map[$row['triggerid_up']] = array();
							}

							$map[$row['triggerid_up']][$row['triggerid_down']] = 1;
						}
						$tids[] = $row['triggerid_up'];
					}
				}
			} while (!empty($tids));
		}

		// withLastEventUnacknowledged
		if (!is_null($options['withLastEventUnacknowledged'])) {
			$eventids = array();
			$sql = 'SELECT max(e.eventid) as eventid, e.objectid'.
					' FROM events e '.
					' WHERE e.object='.EVENT_OBJECT_TRIGGER.
						' AND '.DBcondition('e.objectid', $triggerids).
						' AND '.DBcondition('e.value', array(TRIGGER_VALUE_TRUE)).
						' AND e.value_changed='.TRIGGER_VALUE_CHANGED_YES.
					' GROUP BY e.objectid';
			$events_db = DBselect($sql);
			while ($event = DBfetch($events_db)) {
				$eventids[] = $event['eventid'];
			}

			$correct_triggerids = array();
			$sql = 'SELECT e.objectid'.
					' FROM events e '.
					' WHERE '.DBcondition('e.eventid', $eventids).
						' AND e.acknowledged=0';
			$triggers_db = DBselect($sql);
			while ($trigger = DBfetch($triggers_db)) {
				$correct_triggerids[$trigger['objectid']] = $trigger['objectid'];
			}
			foreach ($result as $triggerid => $trigger) {
				if (!isset($correct_triggerids[$triggerid])) {
					unset($result[$triggerid]);
					unset($triggerids[$triggerid]);
				}

			}
		}

		// limit selected triggers after result set is truncated by previous filters (skipDependent, withLastEventUnacknowledged)
		if ($postLimit) {
			$result = array_slice($result, 0, $postLimit, true);
			$triggerids = array_slice($triggerids, 0, $postLimit, true);
		}

		// Adding Objects
		// Adding trigger dependencies
		if (!is_null($options['selectDependencies']) && str_in_array($options['selectDependencies'], $subselects_allowed_outputs)) {
			$deps = array();
			$depids = array();

			$sql = 'SELECT triggerid_up, triggerid_down '.
					' FROM trigger_depends '.
					' WHERE '.DBcondition('triggerid_down', $triggerids);
			$db_deps = DBselect($sql);
			while ($db_dep = DBfetch($db_deps)) {
				if (!isset($deps[$db_dep['triggerid_down']])) {
					$deps[$db_dep['triggerid_down']] = array();
				}
				$deps[$db_dep['triggerid_down']][$db_dep['triggerid_up']] = $db_dep['triggerid_up'];
				$depids[] = $db_dep['triggerid_up'];
			}

			$obj_params = array(
				'triggerids' => $depids,
				'output' => $options['selectDependencies'],
				'expandData' => 1,
				'preservekeys' => 1
			);
			$allowed = $this->get($obj_params); //allowed triggerids

			foreach ($deps as $triggerid => $deptriggers) {
				foreach ($deptriggers as $num => $deptriggerid) {
					if (isset($allowed[$deptriggerid])) {
						$result[$triggerid]['dependencies'][] = $allowed[$deptriggerid];
					}
				}
			}
		}

		// Adding groups
		if (!is_null($options['selectGroups']) && str_in_array($options['selectGroups'], $subselects_allowed_outputs)) {
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['selectGroups'],
				'triggerids' => $triggerids,
				'preservekeys' => 1
			);
			$groups = API::HostGroup()->get($obj_params);
			foreach ($groups as $groupid => $group) {
				$gtriggers = $group['triggers'];
				unset($group['triggers']);

				foreach ($gtriggers as $num => $trigger) {
					$result[$trigger['triggerid']]['groups'][] = $group;
				}
			}
		}
		// Adding hosts
		if (!is_null($options['selectHosts'])) {

			$obj_params = array(
				'nodeids' => $nodeids,
				'triggerids' => $triggerids,
				'templated_hosts' => 1,
				'nopermissions' => 1,
				'preservekeys' => 1
			);

			if (is_array($options['selectHosts']) || str_in_array($options['selectHosts'], $subselects_allowed_outputs)) {
				$obj_params['output'] = $options['selectHosts'];
				$hosts = API::Host()->get($obj_params);

				if (!is_null($options['limitSelects'])) {
					order_result($hosts, 'host');
				}
				foreach ($hosts as $hostid => $host) {
					unset($hosts[$hostid]['triggers']);

					$count = array();
					foreach ($host['triggers'] as $tnum => $trigger) {
						if (!is_null($options['limitSelects'])) {
							if (!isset($count[$trigger['triggerid']])) {
								$count[$trigger['triggerid']] = 0;
							}
							$count[$trigger['triggerid']]++;

							if ($count[$trigger['triggerid']] > $options['limitSelects']) {
								continue;
							}
						}

						$result[$trigger['triggerid']]['hosts'][] = &$hosts[$hostid];
					}
				}
			}
			else {
				if (API_OUTPUT_COUNT == $options['selectHosts']) {
					$obj_params['countOutput'] = 1;
					$obj_params['groupCount'] = 1;

					$hosts = API::Host()->get($obj_params);
					$hosts = zbx_toHash($hosts, 'hostid');
					foreach ($result as $triggerid => $trigger) {
						if (isset($hosts[$triggerid])) {
							$result[$triggerid]['hosts'] = $hosts[$triggerid]['rowscount'];
						}
						else {
							$result[$triggerid]['hosts'] = 0;
						}
					}
				}
			}
		}

		// Adding Functions
		if (!is_null($options['selectFunctions']) && str_in_array($options['selectFunctions'], $subselects_allowed_outputs)) {

			if ($options['selectFunctions'] == API_OUTPUT_EXTEND) {
				$sql_select = 'f.*';
			}
			else {
				$sql_select = 'f.functionid, f.triggerid';
			}

			$sql = 'SELECT '.$sql_select.
					' FROM functions f '.
					' WHERE '.DBcondition('f.triggerid', $triggerids);
			$res = DBselect($sql);
			while ($function = DBfetch($res)) {
				$triggerid = $function['triggerid'];
				unset($function['triggerid']);

				$result[$triggerid]['functions'][] = $function;
			}
		}

		// Adding Items
		if (!is_null($options['selectItems']) && str_in_array($options['selectItems'], $subselects_allowed_outputs)) {
			$obj_params = array(
				'nodeids' => $nodeids,
				'output' => $options['selectItems'],
				'triggerids' => $triggerids,
				'webitems' => 1,
				'nopermissions' => 1,
				'preservekeys' => 1
			);
			$items = API::Item()->get($obj_params);
			foreach ($items as $itemid => $item) {
				$itriggers = $item['triggers'];
				unset($item['triggers']);
				foreach ($itriggers as $num => $trigger) {
					$result[$trigger['triggerid']]['items'][] = $item;
				}
			}
		}

		// Adding discoveryRule
		if (!is_null($options['selectDiscoveryRule'])) {
			$ruleids = $rule_map = array();

			$sql = 'SELECT id.parent_itemid, td.triggerid'.
					' FROM trigger_discovery td, item_discovery id, functions f'.
					' WHERE '.DBcondition('td.triggerid', $triggerids).
						' AND td.parent_triggerid=f.triggerid'.
						' AND f.itemid=id.itemid';
			$db_rules = DBselect($sql);
			while ($rule = DBfetch($db_rules)) {
				$ruleids[$rule['parent_itemid']] = $rule['parent_itemid'];
				$rule_map[$rule['triggerid']] = $rule['parent_itemid'];
			}

			$obj_params = array(
				'nodeids' => $nodeids,
				'itemids' => $ruleids,
				'nopermissions' => 1,
				'preservekeys' => 1,
			);

			if (is_array($options['selectDiscoveryRule']) || str_in_array($options['selectDiscoveryRule'], $subselects_allowed_outputs)) {
				$obj_params['output'] = $options['selectDiscoveryRule'];
				$discoveryRules = API::Item()->get($obj_params);

				foreach ($result as $triggerid => $trigger) {
					if (isset($rule_map[$triggerid]) && isset($discoveryRules[$rule_map[$triggerid]])) {
						$result[$triggerid]['discoveryRule'] = $discoveryRules[$rule_map[$triggerid]];
					}
				}
			}
		}

		// expandDescription
		if (!is_null($options['expandDescription'])) {
			// Function compare values {{{
			foreach ($result as $tnum => $trigger) {
				preg_match_all('/\$([1-9])/u', $trigger['description'], $numbers);
				preg_match_all('~{[0-9]+}[+\-\*/<>=#]?[\(]*(?P<val>[+\-0-9]+)[\)]*~u', $trigger['expression'], $matches);

				foreach ($numbers[1] as $i) {
					$rep = isset($matches['val'][$i - 1]) ? $matches['val'][$i - 1] : '';
					$result[$tnum]['description'] = str_replace('$'.($i), $rep, $result[$tnum]['description']);
				}
			}
			// }}}

			$functionids = array();
			$triggers_to_expand_hosts = array();
			$triggers_to_expand_items = array();
			$triggers_to_expand_items2 = array();
			foreach ($result as $tnum => $trigger) {

				preg_match_all('/{HOST\.NAME([1-9]?)}/u', $trigger['description'], $hnums);
				if (!empty($hnums[1])) {
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach ($hnums[1] as $fnum) {
						$fnum = $fnum ? $fnum : 1;
						if (isset($funcs[$fnum - 1])) {
							$functionid = $funcs[$fnum - 1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_hosts[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}

				preg_match_all('/{HOSTNAME([1-9]?)}/u', $trigger['description'], $hnums);
				if (!empty($hnums[1])) {
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach ($hnums[1] as $fnum) {
						$fnum = $fnum ? $fnum : 1;
						if (isset($funcs[$fnum - 1])) {
							$functionid = $funcs[$fnum - 1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_hosts[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}

				preg_match_all('/{HOST\.HOST([1-9]?)}/u', $trigger['description'], $hnums);
				if (!empty($hnums[1])) {
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach ($hnums[1] as $fnum) {
						$fnum = $fnum ? $fnum : 1;
						if (isset($funcs[$fnum - 1])) {
							$functionid = $funcs[$fnum - 1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_hosts[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}

				preg_match_all('/{ITEM\.LASTVALUE([1-9]?)}/u', $trigger['description'], $inums);
				if (!empty($inums[1])) {
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach ($inums[1] as $fnum) {
						$fnum = $fnum ? $fnum : 1;
						if (isset($funcs[$fnum - 1])) {
							$functionid = $funcs[$fnum - 1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_items[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}

				preg_match_all('/{ITEM\.VALUE([1-9]?)}/u', $trigger['description'], $inums);
				if (!empty($inums[1])) {
					preg_match_all('/{([0-9]+)}/u', $trigger['expression'], $funcs);
					$funcs = $funcs[1];

					foreach ($inums[1] as $fnum) {
						$fnum = $fnum ? $fnum : 1;
						if (isset($funcs[$fnum - 1])) {
							$functionid = $funcs[$fnum - 1];
							$functionids[$functionid] = $functionid;
							$triggers_to_expand_items2[$trigger['triggerid']][$functionid] = $fnum;
						}
					}
				}
			}

			if (!empty($functionids)) {
				$sql = 'SELECT DISTINCT f.triggerid, f.functionid, h.host, h.name, i.lastvalue'.
						' FROM functions f,items i,hosts h'.
						' WHERE f.itemid=i.itemid'.
							' AND i.hostid=h.hostid'.
							' AND h.status<>'.HOST_STATUS_TEMPLATE.
							' AND '.DBcondition('f.functionid', $functionids);
				$db_funcs = DBselect($sql);
				while ($func = DBfetch($db_funcs)) {
					if (isset($triggers_to_expand_hosts[$func['triggerid']][$func['functionid']])) {

						$fnum = $triggers_to_expand_hosts[$func['triggerid']][$func['functionid']];
						if ($fnum == 1) {
							$result[$func['triggerid']]['description'] = str_replace('{HOSTNAME}', $func['host'], $result[$func['triggerid']]['description']);
							$result[$func['triggerid']]['description'] = str_replace('{HOST.NAME}', $func['name'], $result[$func['triggerid']]['description']);
							$result[$func['triggerid']]['description'] = str_replace('{HOST.HOST}', $func['host'], $result[$func['triggerid']]['description']);
						}

						$result[$func['triggerid']]['description'] = str_replace('{HOSTNAME'.$fnum.'}', $func['host'], $result[$func['triggerid']]['description']);
						$result[$func['triggerid']]['description'] = str_replace('{HOST.NAME'.$fnum.'}', $func['name'], $result[$func['triggerid']]['description']);
						$result[$func['triggerid']]['description'] = str_replace('{HOST.HOST'.$fnum.'}', $func['host'], $result[$func['triggerid']]['description']);
					}

					if (isset($triggers_to_expand_items[$func['triggerid']][$func['functionid']])) {
						$fnum = $triggers_to_expand_items[$func['triggerid']][$func['functionid']];
						if ($fnum == 1) {
							$result[$func['triggerid']]['description'] = str_replace('{ITEM.LASTVALUE}', $func['lastvalue'], $result[$func['triggerid']]['description']);
						}

						$result[$func['triggerid']]['description'] = str_replace('{ITEM.LASTVALUE'.$fnum.'}', $func['lastvalue'], $result[$func['triggerid']]['description']);
					}

					if (isset($triggers_to_expand_items2[$func['triggerid']][$func['functionid']])) {
						$fnum = $triggers_to_expand_items2[$func['triggerid']][$func['functionid']];
						if ($fnum == 1) {
							$result[$func['triggerid']]['description'] = str_replace('{ITEM.VALUE}', $func['lastvalue'], $result[$func['triggerid']]['description']);
						}

						$result[$func['triggerid']]['description'] = str_replace('{ITEM.VALUE'.$fnum.'}', $func['lastvalue'], $result[$func['triggerid']]['description']);
					}
				}
			}

			foreach ($result as $tnum => $trigger) {
				if ($res = preg_match_all('/'.ZBX_PREG_EXPRESSION_USER_MACROS.'/', $trigger['description'], $arr)) {
					$macros = API::UserMacro()->getMacros(array(
						'macros' => $arr[1],
						'triggerid' => $trigger['triggerid']
					));

					$search = array_keys($macros);
					$values = array_values($macros);

					$result[$tnum]['description'] = str_replace($search, $values, $trigger['description']);
				}
			}
		}

		if (!empty($fields_to_unset)) {
			foreach ($result as $tnum => $trigger) {
				foreach ($fields_to_unset as $field_to_unset) {
					unset($result[$tnum][$field_to_unset]);
				}
			}
		}

		COpt::memoryPick();
		// removing keys (hash -> array)
		if (is_null($options['preservekeys'])) {
			$result = zbx_cleanHashes($result);
		}
		return $result;
	}

	/**
	 * Get triggerid by host.host and trigger.expression.
	 *
	 * @param array $triggerData multidimensional array with trigger objects
	 * @param array $triggerData[0,...]['expression']
	 * @param array $triggerData[0,...]['host']
	 * @param array $triggerData[0,...]['hostid'] OPTIONAL
	 * @param array $triggerData[0,...]['description'] OPTIONAL
	 *
	 * @return array|int
	 */
	public function getObjects(array $triggerData) {
		$options = array(
			'filter' => $triggerData,
			'output' => API_OUTPUT_EXTEND
		);

		if (isset($triggerData['node'])) {
			$options['nodeids'] = getNodeIdByNodeName($triggerData['node']);
		}
		else {
			if (isset($triggerData['nodeids'])) {
				$options['nodeids'] = $triggerData['nodeids'];
			}
		}

		// expression is checked later
		unset($options['filter']['expression']);
		$result = $this->get($options);
		if (isset($triggerData['expression'])) {
			foreach ($result as $tnum => $trigger) {
				$tmp_exp = explode_exp($trigger['expression']);

				if (strcmp(trim($tmp_exp, ' '), trim($triggerData['expression'], ' ')) != 0) {
					unset($result[$tnum]);
				}
			}
		}

		return $result;
	}

	/**
	 * @param $object
	 *
	 * @return bool
	 */
	public function exists(array $object) {
		$keyFields = array(
			array(
				'hostid',
				'host'
			),
			'description'
		);

		$result = false;

		if (!isset($object['hostid']) && !isset($object['host'])) {
			$expr = new CTriggerExpression($object);

			if (!empty($expr->errors) || empty($expr->data['hosts'])) {
				return false;
			}

			$object['host'] = reset($expr->data['hosts']);
		}

		$options = array(
			'filter' => array_merge(zbx_array_mintersect($keyFields, $object), array('flags' => null)),
			'output' => API_OUTPUT_EXTEND,
			'nopermissions' => 1,
		);

		if (isset($object['node'])) {
			$options['nodeids'] = getNodeIdByNodeName($object['node']);
		}
		elseif (isset($object['nodeids'])) {
			$options['nodeids'] = $object['nodeids'];
		}

		$triggers = $this->get($options);
		foreach ($triggers as $trigger) {
			$tmp_exp = explode_exp($trigger['expression']);
			if (strcmp($tmp_exp, $object['expression']) == 0) {
				$result = true;
				break;
			}
		}

		return $result;
	}

	/**
	 * @param $triggers
	 * @param $method
	 */
	public function checkInput(array &$triggers, $method) {
		$create = ($method == 'create');
		$update = ($method == 'update');
		$delete = ($method == 'delete');

		// permissions
		if ($update || $delete) {
			$trigger_db_fields = array('triggerid' => null);
			$dbTriggers = $this->get(array(
				'triggerids' => zbx_objectValues($triggers, 'triggerid'),
				'output' => API_OUTPUT_EXTEND,
				'editable' => true,
				'preservekeys' => true,
				'selectDependencies' => API_OUTPUT_REFER,
			));
		}
		else {
			$trigger_db_fields = array(
				'description' => null,
				'expression' => null,
				'error' => 'Trigger just added. No status update so far.',
				'value' => TRIGGER_VALUE_FALSE,
				'value_flags' => TRIGGER_VALUE_FLAG_UNKNOWN
			);
		}

		foreach ($triggers as $tnum => &$trigger) {
			$currentTrigger = $triggers[$tnum];

			if (!check_db_fields($trigger_db_fields, $trigger)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect fields for trigger'));
			}

			if (($update || $delete) && !isset($dbTriggers[$trigger['triggerid']])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, S_NO_PERMISSIONS);
			}

			if ($update) {
				$dbTrigger = $dbTriggers[$trigger['triggerid']];
				$currentTrigger['description'] = $dbTrigger['description'];
			}
			elseif ($delete) {
				if ($dbTriggers[$trigger['triggerid']]['templateid'] != 0) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Cannot delete templated trigger [%1$s:%2$s]', $dbTriggers[$trigger['triggerid']]['description'], explode_exp($dbTriggers[$trigger['triggerid']]['expression']))
					);
				}

				continue;
			}

			$expressionChanged = true;
			if ($update) {

				if (isset($trigger['expression'])) {
					$expression_full = explode_exp($dbTrigger['expression']);
					if (strcmp($trigger['expression'], $expression_full) == 0) {
						$expressionChanged = false;
					}
				}

				if (isset($trigger['description']) && strcmp($trigger['description'], $dbTrigger['description']) == 0) {
					unset($trigger['description']);
				}

				if (isset($trigger['priority']) && $trigger['priority'] == $dbTrigger['priority']) {
					unset($trigger['priority']);
				}

				if (isset($trigger['type']) && $trigger['type'] == $dbTrigger['type']) {
					unset($trigger['type']);
				}

				if (isset($trigger['comments']) && strcmp($trigger['comments'], $dbTrigger['comments']) == 0) {
					unset($trigger['comments']);
				}

				if (isset($trigger['url']) && strcmp($trigger['url'], $dbTrigger['url']) == 0) {
					unset($trigger['url']);
				}

				if (isset($trigger['status']) && $trigger['status'] == $dbTrigger['status']) {
					unset($trigger['status']);
				}

				$dbTrigger['dependencies'] = zbx_objectValues($dbTrigger['dependencies'], 'triggerid');
				if (array_equal($dbTrigger['dependencies'], $trigger['dependencies'])) {
					unset($trigger['dependencies']);
				}
			}

			// if some of the properties are unchanged, no need to update them in DB

			// validating trigger expression
			if (isset($trigger['expression']) && $expressionChanged) {
				// expression permissions
				$expressionData = new CTriggerExpression($trigger);
				if (!empty($expressionData->errors)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, implode(' ', $expressionData->errors));
				}

				$hosts = API::Host()->get(array(
					'filter' => array('host' => $expressionData->data['hosts']),
					'editable' => true,
					'output' => array(
						'hostid',
						'host',
						'status'
					),
					'templated_hosts' => true,
					'preservekeys' => true
				));
				$hosts = zbx_toHash($hosts, 'host');
				$hostsStatusFlags = 0x0;
				foreach ($expressionData->data['hosts'] as $host) {
					if (!isset($hosts[$host])) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect trigger expression. Host "%s" does not exist or you have no access to this host.', $host));
					}

					// find out if both templates and hosts are referenced in expression
					$hostsStatusFlags |= ($hosts[$host]['status'] == HOST_STATUS_TEMPLATE) ? 0x1 : 0x2;
					if ($hostsStatusFlags == 0x3) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect trigger expression. Trigger expression elements should not belong to a template and a host simultaneously.'));
					}
				}
			}

			// check existing
			if ($create) {
				$existTrigger = API::Trigger()->exists(array(
					'description' => $trigger['description'],
					'expression' => $trigger['expression']
				));

				if ($existTrigger) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger [%1$s:%2$s] already exists.', $trigger['description'], $trigger['expression']));
				}
			}
		}
		unset($trigger);
	}

	/**
	 * Add triggers
	 *
	 * Trigger params: expression, description, type, priority, status, comments, url, templateid
	 *
	 * @param array $triggers
	 *
	 * @return boolean
	 */
	public function create(array $triggers) {
		$triggers = zbx_toArray($triggers);

		$this->checkInput($triggers, __FUNCTION__);

		$this->createReal($triggers);

		foreach ($triggers as $trigger) {
			$this->inherit($trigger);
		}

		return array('triggerids' => zbx_objectValues($triggers, 'triggerid'));
	}

	/**
	 * Update triggers
	 *
	 * @param array $triggers
	 *
	 * @return boolean
	 */
	public function update(array $triggers) {
		$triggers = zbx_toArray($triggers);
		$triggerids = zbx_objectValues($triggers, 'triggerid');

		$this->checkInput($triggers, __FUNCTION__);

		$this->updateReal($triggers);

		foreach ($triggers as $trigger) {
			$this->inherit($trigger);
		}

		return array('triggerids' => $triggerids);
	}

	/**
	 * Delete triggers
	 *
	 * @param array $triggerids array with trigger ids
	 *
	 * @return array
	 */
	public function delete(array $triggerids, $nopermissions = false) {
		$triggerids = zbx_toArray($triggerids);
		$triggers = zbx_toObject($triggerids, 'triggerid');

		if (empty($triggerids)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter.'));
		}

		// TODO: remove $nopermissions hack
		if (!$nopermissions) {
			$this->checkInput($triggers, __FUNCTION__);
		}

		// get child triggers
		$parent_triggerids = $triggerids;
		do {
			$db_items = DBselect('SELECT triggerid FROM triggers WHERE '.DBcondition('templateid', $parent_triggerids));
			$parent_triggerids = array();
			while ($db_trigger = DBfetch($db_items)) {
				$parent_triggerids[] = $db_trigger['triggerid'];
				$triggerids[$db_trigger['triggerid']] = $db_trigger['triggerid'];
			}
		} while (!empty($parent_triggerids));


		// select all triggers which are deleted (including children)
		$options = array(
			'triggerids' => $triggerids,
			'output' => API_OUTPUT_EXTEND,
			'nopermissions' => true,
			'preservekeys' => true,
		);
		$del_triggers = $this->get($options);

		DB::delete('events', array(
			'objectid' => $triggerids,
			'object' => EVENT_OBJECT_TRIGGER,
		));

		DB::delete('sysmaps_elements', array(
			'elementid' => $triggerids,
			'elementtype' => SYSMAP_ELEMENT_TYPE_TRIGGER,
		));

		// disable actions
		$actionids = array();
		$sql = 'SELECT DISTINCT actionid '.
				' FROM conditions '.
				' WHERE conditiontype='.CONDITION_TYPE_TRIGGER.
					' AND '.DBcondition('value', $triggerids, false, true);
		$db_actions = DBselect($sql);
		while ($db_action = DBfetch($db_actions)) {
			$actionids[$db_action['actionid']] = $db_action['actionid'];
		}

		DBexecute('UPDATE actions '.
				' SET status='.ACTION_STATUS_DISABLED.
				' WHERE '.DBcondition('actionid', $actionids));

		// delete action conditions
		DB::delete('conditions', array(
			'conditiontype' => CONDITION_TYPE_TRIGGER,
			'value' => $triggerids
		));

		// TODO: REMOVE info
		foreach ($del_triggers as $triggerid => $trigger) {
			info(_s('Trigger [%1$s:%2$s] deleted.', $trigger['description'], explode_exp($trigger['expression'])));
			add_audit_ext(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_TRIGGER, $trigger['triggerid'], $trigger['description'].':'.$trigger['expression'], NULL, NULL, NULL);
		}

		DB::delete('triggers', array('triggerid' => $triggerids));

		update_services_status_all();

		return array('triggerids' => $triggerids);
	}

	/**
	 * Add dependency for trigger
	 *
	 * @param array $triggersData
	 * @param array $triggersData['triggerid]
	 * @param array $triggersData['dependsOnTriggerid']
	 *
	 * @return boolean
	 */
	public function addDependencies(array $triggersData) {
		$triggersData = zbx_toArray($triggersData);
		$triggerids = array();

		foreach ($triggersData as $num => $dep) {
			$triggerids[$dep['triggerid']] = $dep['triggerid'];

			$result = (bool) insert_dependency($dep['triggerid'], $dep['dependsOnTriggerid']);
			if (!$result) {
				self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot create dependency');
			}
		}

		return array('triggerids' => $triggerids);
	}

	/**
	 * Delete trigger dependencies
	 *
	 * @param array $triggersData
	 *
	 * @return boolean
	 */
	public function deleteDependencies(array $triggersData) {
		$triggersData = zbx_toArray($triggersData);

		$triggerids = array();
		foreach ($triggersData as $num => $trigger) {
			$triggerids[] = $trigger['triggerid'];
		}

		$result = delete_dependencies_by_triggerid($triggerids);
		if (!$result) {
			self::exception(ZBX_API_ERROR_PARAMETERS, 'Cannot delete dependency');
		}

		return array('triggerids' => zbx_objectValues($triggersData, 'triggerid'));
	}

	/**
	 * @param $triggers
	 */
	protected function createReal(array &$triggers) {
		$triggers = zbx_toArray($triggers);

		$triggerids = DB::insert('triggers', $triggers);

		foreach ($triggers as $tnum => $trigger) {
			$triggerid = $triggers[$tnum]['triggerid'] = $triggerids[$tnum];

			addEvent($triggerid, TRIGGER_VALUE_UNKNOWN);

			$expression = implode_exp($trigger['expression'], $triggerid);
			if (is_null($expression)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot implode expression "%s".', $trigger['expression']));
			}

			$this->validateItems($trigger);

			DB::update('triggers', array(
				'values' => array('expression' => $expression),
				'where' => array('triggerid' => $triggerid)
			));

			info(_s('Trigger [%1$s:%2$s] created.', $trigger['description'], $trigger['expression']));
		}

		$this->validateDependencies($triggers);

		foreach ($triggers as $trigger) {
			if (isset($trigger['dependencies'])) {
				foreach ($trigger['dependencies'] as $triggerid_up) {
					DB::insert('trigger_depends', array(
						array(
							'triggerid_down' => $trigger['triggerid'],
							'triggerid_up' => $triggerid_up
						)
					));
				}
			}
		}
	}

	/**
	 * @param $triggers
	 */
	protected function updateReal(array $triggers) {
		$triggers = zbx_toArray($triggers);
		$infos = array();

		$options = array(
			'triggerids' => zbx_objectValues($triggers, 'triggerid'),
			'output' => API_OUTPUT_EXTEND,
			'preservekeys' => true,
			'nopermissions' => true,
		);
		$dbTriggers = $this->get($options);

		$description_changed = $expression_changed = false;
		foreach ($triggers as &$trigger) {
			$dbTrigger = $dbTriggers[$trigger['triggerid']];

			if (isset($trigger['description']) && (strcmp($dbTrigger['description'], $trigger['description']) != 0)) {
				$description_changed = true;
			}
			else {
				$trigger['description'] = $dbTrigger['description'];
			}

			$expression_full = explode_exp($dbTrigger['expression']);
			if (isset($trigger['expression']) && strcmp($expression_full, $trigger['expression']) != 0) {
				$this->validateItems($trigger);

				$expression_changed = true;
				$expression_full = $trigger['expression'];
				$trigger['error'] = 'Trigger expression updated. No status update so far.';
			}

			if ($description_changed || $expression_changed) {
				$expressionData = new CTriggerExpression(array('expression' => $expression_full));

				if (!empty($expressionData->errors)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, reset($expressionData->errors));
				}

				$host = reset($expressionData->data['hosts']);

				$options = array(
					'filter' => array(
						'description' => $trigger['description'],
						'host' => $host
					),
					'output' => API_OUTPUT_EXTEND,
					'editable' => true,
					'nopermissions' => true,
				);
				$triggers_exist = API::Trigger()->get($options);

				$trigger_exist = false;
				foreach ($triggers_exist as $tr) {
					$tmp_exp = explode_exp($tr['expression']);
					if (strcmp($tmp_exp, $expression_full) == 0) {
						$trigger_exist = $tr;
						break;
					}
				}
				if ($trigger_exist && (bccomp($trigger_exist['triggerid'], $trigger['triggerid']) != 0)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger "%s" already exists.', $trigger['description']));
				}
			}

			if ($expression_changed) {
				delete_function_by_triggerid($trigger['triggerid']);

				$trigger['expression'] = implode_exp($expression_full, $trigger['triggerid']);
				if (is_null($trigger['expression'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot implode expression "%s".', $expression_full));
				}

				if (isset($trigger['status']) && ($trigger['status'] != TRIGGER_STATUS_ENABLED)) {
					if ($trigger['value_flags'] == TRIGGER_VALUE_FLAG_NORMAL) {
						addEvent($trigger['triggerid'], TRIGGER_VALUE_UNKNOWN);

						$trigger['value_flags'] = TRIGGER_VALUE_FLAG_UNKNOWN;
					}
				}
			}

			$trigger_update = $trigger;
			if (!$description_changed) {
				unset($trigger_update['description']);
			}
			if (!$expression_changed) {
				unset($trigger_update['expression']);
			}

			DB::update('triggers', array(
				'values' => $trigger_update,
				'where' => array('triggerid' => $trigger['triggerid'])
			));

			$expression = $expression_changed ? explode_exp($trigger['expression']) : $expression_full;
			$infos[] = _s('Trigger "%1$s:%2$s" updated.', $trigger['description'], $expression);
		}
		unset($trigger);

		foreach ($triggers as $trigger) {
			if (isset($trigger['dependencies'])) {
				DB::delete('trigger_depends', array('triggerid_down' => $trigger['triggerid']));

				foreach ($trigger['dependencies'] as $triggerid_up) {
					DB::insert('trigger_depends', array(
						array(
							'triggerid_down' => $trigger['triggerid'],
							'triggerid_up' => $triggerid_up
						)
					));
				}
			}
		}

		$this->validateDependencies($triggers);

		foreach ($infos as $info) {
			info($info);
		}
	}

	/**
	 * @param $trigger
	 * @param null $hostids
	 *
	 * @return bool
	 */
	protected function inherit(array $trigger, $hostids = null) {

		$triggerTemplates = API::Template()->get(array(
			'triggerids' => $trigger['triggerid'],
			'output' => API_OUTPUT_EXTEND,
			'nopermissions' => true,
		));
		if (empty($triggerTemplates)) {
			return true;
		}

		if (!isset($trigger['expression']) || !isset($trigger['description'])) {
			$options = array(
				'triggerids' => $trigger['triggerid'],
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => true,
				'nopermissions' => true,
			);
			$dbTrigger = $this->get($options);
			$dbTrigger = reset($dbTrigger);

			if (!isset($trigger['description'])) {
				$trigger['description'] = $dbTrigger['description'];
			}
			if (!isset($trigger['expression'])) {
				$trigger['expression'] = explode_exp($dbTrigger['expression']);
			}
		}


		$chd_hosts = API::Host()->get(array(
			'templateids' => zbx_objectValues($triggerTemplates, 'templateid'),
			'output' => array(
				'hostid',
				'host'
			),
			'preservekeys' => true,
			'hostids' => $hostids,
			'nopermissions' => true,
			'templated_hosts' => true,
		));

		foreach ($chd_hosts as $chd_host) {
			$newTrigger = $trigger;
			$newTrigger['templateid'] = $trigger['triggerid'];

			if (isset($trigger['dependencies'])) {
				$deps = zbx_objectValues($trigger['dependencies'], 'triggerid');
				$newTrigger['dependencies'] = replace_template_dependencies($deps, $chd_host['hostid']);
			}

			$expressionData = new CTriggerExpression($trigger);
			// replace template separately in each expression, only in beginning (host part)
			foreach ($expressionData->expressions as $expr) {
				$newExpr = '';
				foreach ($triggerTemplates as $triggerTemplate) {
					$pos = strpos($expr['expression'], '{'.$triggerTemplate['host'].':');
					if ($pos === 0) {
						$newExpr = substr_replace($expr['expression'], '{'.$chd_host['host'].':', 0, strlen('{'.$triggerTemplate['host'].':'));
						break;
					}
				}
				if (!empty($newExpr)) {
					$newTrigger['expression'] = str_replace($expr['expression'], $newExpr, $newTrigger['expression']);
				}
			}

			// check if templated trigger exists
			$childTriggers = $this->get(array(
				'filter' => array('templateid' => $newTrigger['triggerid']),
				'output' => API_OUTPUT_EXTEND,
				'preservekeys' => 1,
				'hostids' => $chd_host['hostid']
			));

			if ($childTrigger = reset($childTriggers)) {
				$childTrigger['expression'] = explode_exp($childTrigger['expression']);

				if ((strcmp($childTrigger['expression'], $newTrigger['expression']) != 0)
						|| (strcmp($childTrigger['description'], $newTrigger['description']) != 0)
				) {
					$exists = $this->exists(array(
						'description' => $newTrigger['description'],
						'expression' => $newTrigger['expression'],
						'hostids' => $chd_host['hostid']
					));
					if ($exists) {
						self::exception(ZBX_API_ERROR_PARAMETERS,
							_s('Trigger [%1$s] already exists on [%2$s]', $newTrigger['description'], $chd_host['host']));
					}
				}
				elseif ($childTrigger['flags'] != ZBX_FLAG_DISCOVERY_NORMAL) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger with same name but other type exists'));
				}

				$newTrigger['triggerid'] = $childTrigger['triggerid'];
				$this->updateReal($newTrigger);
			}
			else {
				$options = array(
					'filter' => array(
						'description' => $newTrigger['description'],
						'flags' => null
					),
					'output' => API_OUTPUT_EXTEND,
					'preservekeys' => 1,
					'nopermissions' => 1,
					'hostids' => $chd_host['hostid']
				);
				$childTriggers = $this->get($options);

				$childTrigger = false;
				foreach ($childTriggers as $tr) {
					$tmp_exp = explode_exp($tr['expression']);
					if (strcmp($tmp_exp, $newTrigger['expression']) == 0) {
						$childTrigger = $tr;
						break;
					}
				}

				if ($childTrigger) {
					if ($childTrigger['templateid'] != 0) {
						self::exception(ZBX_API_ERROR_PARAMETERS,
							_s('Trigger [%1$s] already exists on [%2$s]', $childTrigger['description'], $chd_host['host']));
					}
					elseif ($childTrigger['flags'] != $newTrigger['flags']) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _s('Trigger with same name but other type exists'));
					}

					$newTrigger['triggerid'] = $childTrigger['triggerid'];
					$this->updateReal($newTrigger);
				}
				else {
					$this->createReal($newTrigger);
					$newTrigger = reset($newTrigger);
				}
			}
			$this->inherit($newTrigger);
		}

		return true;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public function syncTemplates(array $data) {
		$data['templateids'] = zbx_toArray($data['templateids']);
		$data['hostids'] = zbx_toArray($data['hostids']);

		$allowedHosts = API::Host()->get(array(
			'hostids' => $data['hostids'],
			'editable' => true,
			'preservekeys' => true,
			'templated_hosts' => true,
			'output' => API_OUTPUT_SHORTEN,
		));
		foreach ($data['hostids'] as $hostid) {
			if (!isset($allowedHosts[$hostid])) {
				self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
			}
		}

		$allowedTemplates = API::Template()->get(array(
			'templateids' => $data['templateids'],
			'preservekeys' => true,
			'editable' => true,
			'output' => API_OUTPUT_SHORTEN
		));
		foreach ($data['templateids'] as $templateid) {
			if (!isset($allowedTemplates[$templateid])) {
				self::exception(ZBX_API_ERROR_PERMISSIONS, S_NO_PERMISSION);
			}
		}

		$triggers = $this->get(array(
			'hostids' => $data['templateids'],
			'preservekeys' => true,
			'output' => API_OUTPUT_EXTEND,
		));

		foreach ($triggers as $trigger) {
			$trigger['expression'] = explode_exp($trigger['expression']);
			$this->inherit($trigger, $data['hostids']);
		}


		// Update dependencies, do it after all triggers that can be dependent were created/updated on all child hosts/templates.
		// Starting from highest level template triggers select triggers from one level lower, then for each lower trigger
		// look if it's parent has dependencies, if so find this dependency trigger child on dependent trigger host and add new dependency.
		$parentTriggers = $this->get(array(
			'hostids' => $data['templateids'],
			'preservekeys' => true,
			'output' => array(
				'triggerid',
				'templateid'
			),
			'selectDependencies' => API_OUTPUT_REFER
		));

		while (!empty($parentTriggers)) {
			$childTriggers = $this->get(array(
				'filter' => array('templateid' => array_keys($parentTriggers)),
				'nopermissions' => true,
				'preservekeys' => true,
				'output' => array(
					'triggerid',
					'templateid'
				),
				'selectDependencies' => API_OUTPUT_REFER,
				'selectHosts' => array('host'),
			));

			foreach ($childTriggers as $childTrigger) {
				if (!empty($parentTriggers[$childTrigger['templateid']]['dependencies'])) {

					$deps = zbx_objectValues($parentTriggers[$childTrigger['templateid']]['dependencies'], 'triggerid');
					$host = reset($childTrigger['hosts']);
					$newDeps = replace_template_dependencies($deps, $host['hostid']);

					DB::delete('trigger_depends', array('triggerid_down' => $childTrigger['triggerid']));
					foreach ($newDeps as $triggerid_up) {
						DB::insert('trigger_depends', array(
							array(
								'triggerid_down' => $childTrigger['triggerid'],
								'triggerid_up' => $triggerid_up
							)
						));
					}
				}
			}
			$parentTriggers = $childTriggers;
		}

		return true;
	}

	/**
	 * @param $triggers
	 */
	protected function validateDependencies(array $triggers) {

		foreach ($triggers as $trigger) {
			if (!isset($trigger['dependencies']) || empty($trigger['dependencies'])) {
				continue;
			}

			// check circular dependency {{{
			$triggerid_down = $trigger['dependencies'];
			do {
				$sql = 'SELECT triggerid_up'.
						' FROM trigger_depends'.
						' WHERE'.DBcondition('triggerid_down', $triggerid_down);
				$db_up_triggers = DBselect($sql);
				$up_triggerids = array();
				while ($up_trigger = DBfetch($db_up_triggers)) {
					if (bccomp($up_trigger['triggerid_up'], $trigger['triggerid']) == 0) {
						self::exception(ZBX_API_ERROR_PARAMETERS, S_INCORRECT_DEPENDENCY);
					}
					$up_triggerids[] = $up_trigger['triggerid_up'];
				}
				$triggerid_down = $up_triggerids;

			} while (!empty($up_triggerids));
			// }}} check circular dependency


			$expr = new CTriggerExpression($trigger);

			$templates = API::Template()->get(array(
				'output' => array(
					'hostid',
					'host'
				),
				'filter' => array('host' => $expr->data['hosts']),
				'nopermissions' => true,
				'preservekeys' => true
			));
			$templateids = array_keys($templates);
			$templateids = zbx_toHash($templateids);

			$dep_templateids = array();
			$db_dephosts = get_hosts_by_triggerid($trigger['dependencies']);
			while ($dephost = DBfetch($db_dephosts)) {
				if ($dephost['status'] == HOST_STATUS_TEMPLATE) {
					$templates[$dephost['hostid']] = $dephost;
					$dep_templateids[$dephost['hostid']] = $dephost['hostid'];
				}
			}

			$tdiff = array_diff($dep_templateids, $templateids);
			if (!empty($templateids) && !empty($dep_templateids) && !empty($tdiff)) {
				$tpls = zbx_array_merge($templateids, $dep_templateids);
				$sql = 'SELECT DISTINCT ht.templateid,ht.hostid,h.host'.
						' FROM hosts_templates ht,hosts h'.
						' WHERE h.hostid=ht.hostid'.
							' AND'.DBcondition('ht.templateid', $tpls);

				$db_lowlvltpl = DBselect($sql);
				$map = array();
				while ($lowlvltpl = DBfetch($db_lowlvltpl)) {
					if (!isset($map[$lowlvltpl['hostid']])) {
						$map[$lowlvltpl['hostid']] = array();
					}
					$map[$lowlvltpl['hostid']][$lowlvltpl['templateid']] = $lowlvltpl['host'];
				}

				foreach ($map as $templates) {
					$set_with_dep = false;

					foreach ($templateids as $tplid) {
						if (isset($templates[$tplid])) {
							$set_with_dep = true;
							break;
						}
					}
					foreach ($dep_templateids as $dep_tplid) {
						if (!isset($templates[$dep_tplid]) && $set_with_dep) {
							self::exception(ZBX_API_ERROR_PARAMETERS, _s('Not all templates are linked to host "%s".', reset($templates)));
						}
					}
				}
			}
		}
	}

	/**
	 * Check if all templates trigger belongs to are linked to same hosts.
	 *
	 * @throws APIException
	 *
	 * @param $trigger
	 *
	 * @return bool
	 */
	protected function validateItems(array $trigger) {
		$trigExpr = new CTriggerExpression(array('expression' => $trigger['expression']));

		$hosts = array();
		foreach ($trigExpr->expressions as $exprPart) {
			if (!zbx_empty($exprPart['host'])) {
				$hosts[] = $exprPart['host'];
			}
		}

		$templatesData = API::Template()->get(array(
			'output' => API_OUTPUT_REFER,
			'selectHosts' => API_OUTPUT_REFER,
			'selectTemplates' => API_OUTPUT_REFER,
			'filter' => array('host' => $hosts),
			'nopermissions' => true,
			'preservekeys' => true,
		));
		$firstTemplate = array_pop($templatesData);

		if ($firstTemplate) {
			$compareLinks = array_merge(
				zbx_objectValues($firstTemplate['hosts'], 'hostid'),
				zbx_objectValues($firstTemplate['templates'], 'templateid')
			);

			foreach ($templatesData as $data) {
				$linkedTo = array_merge(
					zbx_objectValues($data['hosts'], 'hostid'),
					zbx_objectValues($data['templates'], 'templateid')
				);

				if (array_diff($compareLinks, $linkedTo) || array_diff($linkedTo, $compareLinks)) {
					self::exception(
						ZBX_API_ERROR_PARAMETERS,
						_s('Trigger "%s" belongs to templates with different linkages.', $trigger['description'])
					);
				}
			}
		}

		return true;
	}

	/**
	 * @param $ids
	 *
	 * @return bool
	 */
	public function isReadable(array $ids) {
		if (empty($ids)) {
			return true;
		}

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'triggerids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}

	/**
	 * @param $ids
	 *
	 * @return bool
	 */
	public function isWritable(array $ids) {
		if (empty($ids)) {
			return true;
		}

		$ids = array_unique($ids);

		$count = $this->get(array(
			'nodeids' => get_current_nodeid(true),
			'triggerids' => $ids,
			'output' => API_OUTPUT_SHORTEN,
			'editable' => true,
			'countOutput' => true
		));

		return (count($ids) == $count);
	}

}

?>
