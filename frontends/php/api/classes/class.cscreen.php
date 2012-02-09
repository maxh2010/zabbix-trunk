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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/
?>
<?php
/**
 * File containing CScreen class for API.
 * @package API
 */
/**
 * Class containing methods for operations with Screens
 */
class CScreen extends CZBXAPI {

	protected $tableName = 'screens';

	protected $tableAlias = 's';

/**
 * Get Screen data
 *
 * @param array $options
 * @param array $options['nodeids'] Node IDs
 * @param boolean $options['with_items'] only with items
 * @param boolean $options['editable'] only with read-write permission. Ignored for SuperAdmins
 * @param int $options['count'] count Hosts, returned column name is rowscount
 * @param string $options['pattern'] search hosts by pattern in host names
 * @param int $options['limit'] limit selection
 * @param string $options['order'] deprecated parameter (for now)
 * @return array|boolean Host data as array or false if error
 */
	public function get($options = array()) {
		$result = array();
		$userType = self::$userData['type'];

		// allowed columns for sorting
		$sortColumns = array('screenid', 'name');

		// allowed output options for [ select_* ] params
		$subselectsAllowedOutputs = array(API_OUTPUT_REFER, API_OUTPUT_EXTEND);

		$sqlParts = array(
			'select'	=> array('screens' => 's.screenid'),
			'from'		=> array('screens' => 'screens s'),
			'where'		=> array('template' => 's.templateid IS NULL'),
			'order'		=> array(),
			'group'		=> array(),
			'limit'		=> null
		);

		$defOptions = array(
			'nodeids'					=> null,
			'screenids'					=> null,
			'screenitemids'				=> null,
			'editable'					=> null,
			'nopermissions'				=> null,
			// filter
			'filter'					=> null,
			'search'					=> null,
			'searchByAny'				=> null,
			'startSearch'				=> null,
			'excludeSearch'				=> null,
			'searchWildcardsEnabled'	=> null,
			// output
			'output'					=> API_OUTPUT_REFER,
			'selectScreenItems'			=> null,
			'countOutput'				=> null,
			'groupCount'				=> null,
			'preservekeys'				=> null,
			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null
		);
		$options = zbx_array_merge($defOptions, $options);

		if (is_array($options['output'])) {
			unset($sqlParts['select']['screens']);

			$dbTable = DB::getSchema('screens');
			foreach ($options['output'] as $field) {
				if (isset($dbTable['fields'][$field])) {
					$sqlParts['select'][$field] = 's.'.$field;
				}
			}
			$options['output'] = API_OUTPUT_CUSTOM;
		}

// nodeids
		$nodeids = !is_null($options['nodeids']) ? $options['nodeids'] : get_current_nodeid();

// screenids
		if (!is_null($options['screenids'])) {
			zbx_value2array($options['screenids']);
			$sqlParts['where'][] = DBcondition('s.screenid', $options['screenids']);
		}

// screenitemids
		if (!is_null($options['screenitemids'])) {
			zbx_value2array($options['screenitemids']);
			if ($options['output'] != API_OUTPUT_EXTEND) {
				$sqlParts['select']['screenitemid'] = 'si.screenitemid';
			}
			$sqlParts['from']['screens_items'] = 'screens_items si';
			$sqlParts['where']['ssi'] = 'si.screenid=s.screenid';
			$sqlParts['where'][] = DBcondition('si.screenitemid', $options['screenitemids']);
		}

// filter
		if (is_array($options['filter'])) {
			zbx_db_filter('screens s', $options, $sqlParts);
		}

// search
		if (is_array($options['search'])) {
			zbx_db_search('screens s', $options, $sqlParts);
		}

// output
		if ($options['output'] == API_OUTPUT_EXTEND) {
			$sqlParts['select']['screens'] = 's.*';
		}

// countOutput
		if (!is_null($options['countOutput'])) {
			$options['sortfield'] = '';
			$sqlParts['select'] = array('count(DISTINCT s.screenid) as rowscount');

// groupCount
			if (!is_null($options['groupCount'])) {
				foreach ($sqlParts['group'] as $key => $fields) {
					$sqlParts['select'][$key] = $fields;
				}
			}
		}

		// sorting
		zbx_db_sorting($sqlParts, $options, $sortColumns, 's');

// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sqlParts['limit'] = $options['limit'];
		}
//-------

		$screenids = array();

		$sqlParts['select'] = array_unique($sqlParts['select']);
		$sqlParts['from'] = array_unique($sqlParts['from']);
		$sqlParts['where'] = array_unique($sqlParts['where']);
		$sqlParts['group'] = array_unique($sqlParts['group']);
		$sqlParts['order'] = array_unique($sqlParts['order']);

		$sqlSelect = '';
		$sqlFrom = '';
		$sqlWhere = '';
		$sqlGroup = '';
		$sqlOrder = '';
		if (!empty($sqlParts['select']))	$sqlSelect.= implode(',', $sqlParts['select']);
		if (!empty($sqlParts['from']))		$sqlFrom.= implode(',', $sqlParts['from']);
		if (!empty($sqlParts['where']))		$sqlWhere.= ' AND '.implode(' AND ', $sqlParts['where']);
		if (!empty($sqlParts['group']))		$sqlGroup.= ' GROUP BY '.implode(',', $sqlParts['group']);
		if (!empty($sqlParts['order']))		$sqlOrder.= ' ORDER BY '.implode(',', $sqlParts['order']);
		$sqlLimit = $sqlParts['limit'];

		$sql = 'SELECT '.zbx_db_distinct($sqlParts).' '.$sqlSelect.'
				FROM '.$sqlFrom.'
				WHERE '.DBin_node('s.screenid', $nodeids).
					$sqlWhere.
				$sqlGroup.
				$sqlOrder;
		$res = DBselect($sql, $sqlLimit);
		while ($screen = DBfetch($res)) {
			if (!is_null($options['countOutput'])) {
				if (!is_null($options['groupCount']))
					$result[] = $screen;
				else
					$result = $screen['rowscount'];
			}
			else{
				$screenids[$screen['screenid']] = $screen['screenid'];

				if ($options['output'] == API_OUTPUT_SHORTEN) {
					$result[$screen['screenid']] = array('screenid' => $screen['screenid']);
				}
				else{
					if (!isset($result[$screen['screenid']])) $result[$screen['screenid']]= array();

					if (!is_null($options['selectScreenItems']) && !isset($result[$screen['screenid']]['screenitems'])) {
						$result[$screen['screenid']]['screenitems'] = array();
					}

					if (isset($screen['screenitemid']) && is_null($options['selectScreenItems'])) {
						if (!isset($result[$screen['screenid']]['screenitems']))
							$result[$screen['screenid']]['screenitems'] = array();

						$result[$screen['screenid']]['screenitems'][] = array('screenitemid' => $screen['screenitemid']);
						unset($screen['screenitemid']);
					}

					$result[$screen['screenid']] += $screen;
				}
			}
		}

// editable + PERMISSION CHECK
		if ((USER_TYPE_SUPER_ADMIN == $userType) || $options['nopermissions']) {}
		elseif (!empty($result)) {
			$groupsToCheck = array();
			$hostsToCheck = array();
			$graphsToCheck = array();
			$itemsToCheck = array();
			$mapsToCheck = array();
			$screensToCheck = array();
			$screensItems = array();

			$dbSitems = DBselect('SELECT * FROM screens_items WHERE '.DBcondition('screenid', $screenids));
			while ($sitem = DBfetch($dbSitems)) {
				$screensItems[$sitem['screenitemid']] = $sitem;

				if ($sitem['resourceid']) {
					switch ($sitem['resourcetype']) {
						case SCREEN_RESOURCE_HOSTS_INFO:
						case SCREEN_RESOURCE_TRIGGERS_INFO:
						case SCREEN_RESOURCE_TRIGGERS_OVERVIEW:
						case SCREEN_RESOURCE_DATA_OVERVIEW:
						case SCREEN_RESOURCE_HOSTGROUP_TRIGGERS:
							$groupsToCheck[] = $sitem['resourceid'];
						break;
						case SCREEN_RESOURCE_HOST_TRIGGERS:
							$hostsToCheck[] = $sitem['resourceid'];
						break;
						case SCREEN_RESOURCE_GRAPH:
							$graphsToCheck[] = $sitem['resourceid'];
						break;
						case SCREEN_RESOURCE_SIMPLE_GRAPH:
						case SCREEN_RESOURCE_PLAIN_TEXT:
							$itemsToCheck[] = $sitem['resourceid'];
						break;
						case SCREEN_RESOURCE_MAP:
							$mapsToCheck[] = $sitem['resourceid'];
						break;
						case SCREEN_RESOURCE_SCREEN:
							$screensToCheck[] = $sitem['resourceid'];
						break;
					}
				}
			}

			$groupsToCheck = array_unique($groupsToCheck);
			$hostsToCheck = array_unique($hostsToCheck);
			$graphsToCheck = array_unique($graphsToCheck);
			$itemsToCheck = array_unique($itemsToCheck);
			$mapsToCheck = array_unique($mapsToCheck);
			$screensToCheck = array_unique($screensToCheck);

// group
			$groupOptions = array(
								'nodeids' => $nodeids,
								'groupids' => $groupsToCheck,
								'editable' => $options['editable']);
			$allowedGroups = API::HostGroup()->get($groupOptions);
			$allowedGroups = zbx_objectValues($allowedGroups, 'groupid');

// host
			$hostOptions = array(
								'nodeids' => $nodeids,
								'hostids' => $hostsToCheck,
								'editable' => $options['editable']);
			$allowedHosts = API::Host()->get($hostOptions);
			$allowedHosts = zbx_objectValues($allowedHosts, 'hostid');

// graph
			$graphOptions = array(
								'nodeids' => $nodeids,
								'graphids' => $graphsToCheck,
								'editable' => $options['editable']);
			$allowedGraphs = API::Graph()->get($graphOptions);
			$allowedGraphs = zbx_objectValues($allowedGraphs, 'graphid');

// item
			$itemOptions = array(
				'nodeids' => $nodeids,
				'itemids' => $itemsToCheck,
				'webitems' => 1,
				'editable' => $options['editable']
			);
			$allowedItems = API::Item()->get($itemOptions);
			$allowedItems = zbx_objectValues($allowedItems, 'itemid');
// map
			$mapOptions = array(
				'nodeids' => $nodeids,
				'sysmapids' => $mapsToCheck,
				'editable' => $options['editable']
			);
			$allowedMaps = API::Map()->get($mapOptions);
			$allowedMaps = zbx_objectValues($allowedMaps, 'sysmapid');
// screen
			$screensOptions = array(
								'nodeids' => $nodeids,
								'screenids' => $screensToCheck,
								'editable' => $options['editable']);
			$allowedScreens = API::Screen()->get($screensOptions);
			$allowedScreens = zbx_objectValues($allowedScreens, 'screenid');


			$restrGroups = array_diff($groupsToCheck, $allowedGroups);
			$restrHosts = array_diff($hostsToCheck, $allowedHosts);
			$restrGraphs = array_diff($graphsToCheck, $allowedGraphs);
			$restrItems = array_diff($itemsToCheck, $allowedItems);
			$restrMaps = array_diff($mapsToCheck, $allowedMaps);
			$restrScreens = array_diff($screensToCheck, $allowedScreens);

// group
			foreach ($restrGroups as $resourceid) {
				foreach ($screensItems as $screenItemid => $screenItem) {
					if ((bccomp($screenItem['resourceid'], $resourceid) == 0) &&
						uint_in_array($screenItem['resourcetype'], array(SCREEN_RESOURCE_HOSTS_INFO,SCREEN_RESOURCE_TRIGGERS_INFO,SCREEN_RESOURCE_TRIGGERS_OVERVIEW,SCREEN_RESOURCE_DATA_OVERVIEW,SCREEN_RESOURCE_HOSTGROUP_TRIGGERS))
					) {
						unset($result[$screenItem['screenid']]);
						unset($screensItems[$screenItemid]);
					}
				}
			}
// host
			foreach ($restrHosts as $resourceid) {
				foreach ($screensItems as $screenItemid => $screenItem) {
					if ((bccomp($screenItem['resourceid'], $resourceid) == 0) &&
						uint_in_array($screenItem['resourcetype'], array(SCREEN_RESOURCE_HOST_TRIGGERS))
					) {
						unset($result[$screenItem['screenid']]);
						unset($screensItems[$screenItemid]);
					}
				}
			}
// graph
			foreach ($restrGraphs as $resourceid) {
				foreach ($screensItems as $screenItemid => $screenItem) {
					if ((bccomp($screenItem['resourceid'], $resourceid) == 0) && ($screenItem['resourcetype'] == SCREEN_RESOURCE_GRAPH)) {
						unset($result[$screenItem['screenid']]);
						unset($screensItems[$screenItemid]);
					}
				}
			}
// item
			foreach ($restrItems as $resourceid) {
				foreach ($screensItems as $screenItemid => $screenItem) {
					if ((bccomp($screenItem['resourceid'], $resourceid) == 0) &&
						uint_in_array($screenItem['resourcetype'], array(SCREEN_RESOURCE_SIMPLE_GRAPH, SCREEN_RESOURCE_PLAIN_TEXT))
					) {
						unset($result[$screenItem['screenid']]);
						unset($screensItems[$screenItemid]);
					}
				}
			}
// map
			foreach ($restrMaps as $resourceid) {
				foreach ($screensItems as $screenItemid => $screenItem) {
					if ((bccomp($screenItem['resourceid'], $resourceid) == 0) && ($screenItem['resourcetype'] == SCREEN_RESOURCE_MAP)) {
						unset($result[$screenItem['screenid']]);
						unset($screensItems[$screenItemid]);
					}
				}
			}
// screen
			foreach ($restrScreens as $resourceid) {
				foreach ($screensItems as $screenItemid => $screenItem) {
					if ((bccomp($screenItem['resourceid'], $resourceid) == 0) && ($screenItem['resourcetype'] == SCREEN_RESOURCE_SCREEN)) {
						unset($result[$screenItem['screenid']]);
						unset($screensItems[$screenItemid]);
					}
				}
			}
		}

		if (!is_null($options['countOutput'])) {
			return $result;
		}

// Adding ScreenItems
		if (!is_null($options['selectScreenItems']) && str_in_array($options['selectScreenItems'], $subselectsAllowedOutputs)) {
			if (!isset($screensItems)) {
				$screensItems = array();
				$dbSitems = DBselect('SELECT * FROM screens_items WHERE '.DBcondition('screenid', $screenids));
				while ($sitem = DBfetch($dbSitems)) {
					$screensItems[$sitem['screenitemid']] = $sitem;
				}
			}

			foreach ($screensItems as $snum => $sitem) {
				if (!isset($result[$sitem['screenid']]['screenitems'])) {
					$result[$sitem['screenid']]['screenitems'] = array();
				}

				$result[$sitem['screenid']]['screenitems'][] = $sitem;
			}
		}

// removing keys (hash -> array)
		if (is_null($options['preservekeys'])) {
			$result = zbx_cleanHashes($result);
		}

	return $result;
	}

	public function exists($data) {
		$keyFields = array(array('screenid', 'name'));

		$options = array(
			'filter' => zbx_array_mintersect($keyFields, $data),
			'preservekeys' => 1,
			'output' => API_OUTPUT_SHORTEN,
			'nopermissions' => 1,
			'limit' => 1
		);

		if (isset($data['node']))
			$options['nodeids'] = getNodeIdByNodeName($data['node']);
		elseif (isset($data['nodeids']))
			$options['nodeids'] = $data['nodeids'];

		$screens = $this->get($options);

		return !empty($screens);
	}

/**
 * Create Screen
 *
 * @param _array $screens
 * @param string $screens['name']
 * @param array $screens['hsize']
 * @param int $screens['vsize']
 * @return array
 */
	public function create($screens) {
		$screens = zbx_toArray($screens);
		$insertScreenItems = array();

			$newScreenNames = zbx_objectValues($screens, 'name');
// Exists
			$options = array(
				'filter' => array('name' => $newScreenNames),
				'output' => API_OUTPUT_EXTEND,
				'nopermissions' => 1
			);
			$dbScreens = $this->get($options);
			foreach ($dbScreens as $dbScreen) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Screen "%1$s" already exists.', $dbScreen['name']));
			}
//---

			foreach ($screens as $screen) {

				$screenDbFields = array('name' => null);
				if (!check_db_fields($screenDbFields, $screen)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Wrong fields for screen "%1$s".', $screen['name']));
				}
			}
			$screenids = DB::insert('screens', $screens);

			foreach ($screens as $snum => $screen) {
				if (isset($screen['screenitems'])) {
					foreach ($screen['screenitems'] as $screenitem) {
						$screenitem['screenid'] = $screenids[$snum];
						$insertScreenItems[] = $screenitem;
					}
				}
			}

			// save screen items
			API::ScreenItem()->create($insertScreenItems);

			return array('screenids' => $screenids);
	}

/**
 * Update Screen
 *
 * @param _array $screens multidimensional array with Hosts data
 * @param string $screens['screenid']
 * @param int $screens['name']
 * @param int $screens['hsize']
 * @param int $screens['vsize']
 * @return boolean
 */
	public function update($screens) {
		$screens = zbx_toArray($screens);
		$update = array();

		$options = array(
			'screenids' => zbx_objectValues($screens, 'screenid'),
			'editable' => 1,
			'output' => API_OUTPUT_SHORTEN,
			'preservekeys' => 1,
		);
		$updScreens = $this->get($options);
		foreach ($screens as $gnum => $screen) {
				if (!isset($screen['screenid'], $updScreens[$screen['screenid']])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
			}
		}

		foreach ($screens as $snum => $screen) {
			if (isset($screen['name'])) {
				$options = array(
					'filter' => array(
						'name' => $screen['name'],
					),
					'preservekeys' => 1,
					'nopermissions' => 1,
					'output' => API_OUTPUT_SHORTEN,
				);
				$existScreen = $this->get($options);
				$existScreen = reset($existScreen);

				if ($existScreen && (bccomp($existScreen['screenid'], $screen['screenid']) != 0))
					self::exception(ZBX_API_ERROR_PERMISSIONS, _s('Screen "%1$s" already exists.', $screen['name']));
			}

			$screenid = $screen['screenid'];
			unset($screen['screenid']);
			if (!empty($screen)) {
				$update[] = array(
					'values' => $screen,
					'where' => array('screenid' => $screenid),
				);
			}

			// udpate screen items
			if (isset($screen['screenitems'])) {
				$this->replaceItems($screenid, $screen['screenitems']);
			}
		}
		DB::update('screens', $update);

		return array('screenids' => zbx_objectValues($screens, 'screenid'));
	}

/**
 * Delete Screen
 *
 * @param array $screenids
 * @return boolean
 */
	public function delete($screenids) {
		$screenids = zbx_toArray($screenids);

		$options = array(
				'screenids' => $screenids,
				'editable' => 1,
				'preservekeys' => 1,
		);
		$delScreens = $this->get($options);

		foreach ($screenids as $screenid) {
			if (!isset($delScreens[$screenid]))
				self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}

		DB::delete('screens_items', array('screenid'=>$screenids));
		DB::delete('screens_items', array('resourceid'=>$screenids, 'resourcetype'=>SCREEN_RESOURCE_SCREEN));
		DB::delete('slides', array('screenid'=>$screenids));
		DB::delete('screens', array('screenid'=>$screenids));

		return array('screenids' => $screenids);
	}


	/**
	 * Replaces all of the screen items of the given screen with the new ones.
	 *
	 * @param int $screenid        The ID of the target screen
	 * @param array $screenItems   An array of screen items
	 */
	protected function replaceItems($screenid, $screenItems) {
		// fetch the current screen items
		$dbScreenItems = API::ScreenItem()->get(array(
			'screenids' => $screenid,
			'preservekeys' => true
		));

		// update the new ones
		foreach ($screenItems as &$screenItem) {
			$screenItem['screenid'] = $screenid;
		}
		$result = API::ScreenItem()->updateByPosition($screenItems);

		// deleted the old items
		$deleteItemids = array_diff(array_keys($dbScreenItems), $result['screenitemids']);
		API::ScreenItem()->delete($deleteItemids);
	}
}
?>
