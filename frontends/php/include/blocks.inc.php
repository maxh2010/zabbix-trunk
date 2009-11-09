<?php
/*
** ZABBIX
** Copyright (C) 2001-2009 SIA Zabbix
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
require_once('include/graphs.inc.php');
require_once('include/screens.inc.php');
require_once('include/maps.inc.php');
require_once('include/users.inc.php');


// Author: Aly
function make_favorite_graphs(){
	$table = new CTableInfo();

	$fav_graphs = get_favorites('web.favorite.graphids');
	foreach($fav_graphs as $key => $favorite){

		$source = $favorite['source'];
		$sourceid = $favorite['value'];

		if('itemid' == $source){
			if(!$item = get_item_by_itemid($sourceid)) continue;

			$host = get_host_by_itemid($sourceid);
			$item["description"] = item_description($item);

			$link = new CLink(get_node_name_by_elid($sourceid, null, ': ').$host['host'].':'.$item['description'],'history.php?action=showgraph&itemid='.$sourceid);
			$link->setTarget('blank');

			$capt = new CSpan($link);
			$capt->setAttribute('style','line-height: 14px; vertical-align: middle;');

			$icon = new CLink(new CImg('images/general/chart.png','chart',18,18,'borderless'),'history.php?action=showgraph&itemid='.$sourceid.'&fullscreen=1');
			$icon->setTarget('blank');
		}
		else{
			if(!$graph = get_graph_by_graphid($sourceid)) continue;
			if(!graph_accessible($sourceid)) continue;

			$result = get_hosts_by_graphid($sourceid);
			$ghost = DBFetch($result);

			$link = new CLink(get_node_name_by_elid($sourceid, null, ': ').$ghost['host'].':'.$graph['name'],'charts.php?graphid='.$sourceid);
			$link->setTarget('blank');

			$capt = new CSpan($link);
			$capt->setAttribute('style','line-height: 14px; vertical-align: middle;');

			$icon = new CLink(new CImg('images/general/chart.png','chart',18,18,'borderless'),'charts.php?graphid='.$sourceid.'&fullscreen=1');
			$icon->setTarget('blank');
		}

		$table->addRow(new CCol(array(
			$icon,
			SPACE,
			$capt)
		));
	}
	$td = new CCol(array(new CLink(S_GRAPHS.' &raquo;','charts.php','highlight')));
	$td->setAttribute('style','text-align: right;');

	$table->setFooter($td);

return $table;
}

// Author: Aly
function make_favorite_screens(){
	$table = new CTableInfo();

	$fav_screens = get_favorites('web.favorite.screenids');

	foreach($fav_screens as $key => $favorite){
		$source = $favorite['source'];
		$sourceid = $favorite['value'];

		if('slideshowid' == $source){
			if(!$slide = get_slideshow_by_slideshowid($sourceid)) continue;
			if(!slideshow_accessible($sourceid, PERM_READ_ONLY)) continue;

			$link = new CLink(get_node_name_by_elid($sourceid, null, ': ').$slide['name'],'screens.php?config=1&elementid='.$sourceid);
			$link->setTarget('blank');

			$capt = new CSpan($link);
			$capt->setAttribute('style','line-height: 14px; vertical-align: middle;');

			$icon = new CLink(new CImg('images/general/chart.png','screen',18,18,'borderless'),'screens.php?config=1&elementid='.$sourceid.'&fullscreen=1');
			$icon->setTarget('blank');
		}
		else{
			if(!$screen = get_screen_by_screenid($sourceid)) continue;
			if(!screen_accessible($sourceid, PERM_READ_ONLY)) continue;

			$link = new CLink(get_node_name_by_elid($sourceid, null, ': ').$screen['name'],'screens.php?config=0&elementid='.$sourceid);
			$link->setTarget('blank');

			$capt = new CSpan($link);
			$capt->setAttribute('style','line-height: 14px; vertical-align: middle;');

			$icon = new CLink(new CImg('images/general/chart.png','screen',18,18,'borderless'),'screens.php?config=0&elementid='.$sourceid.'&fullscreen=1');
			$icon->setTarget('blank');
		}

		$table->addRow(new CCol(array(
			$icon,
			SPACE,
			$capt)
		));
	}

	$td = new CCol(array(new CLink(S_SCREENS.' &raquo;','screens.php','highlight')));
	$td->setAttribute('style','text-align: right;');

	$table->setFooter($td);

return $table;
}

// Author: Aly
function make_favorite_maps(){
	$table = new CTableInfo();

	$fav_sysmaps = get_favorites('web.favorite.sysmapids');

	foreach($fav_sysmaps as $key => $favorite){

		$source = $favorite['source'];
		$sourceid = $favorite['value'];

		if(!$sysmap = get_sysmap_by_sysmapid($sourceid)) continue;
		if(!sysmap_accessible($sourceid,PERM_READ_ONLY)) continue;

		$link = new CLink(get_node_name_by_elid($sourceid, null, ': ').$sysmap['name'],'maps.php?sysmapid='.$sourceid);
		$link->setTarget('blank');

		$capt = new CSpan($link);
		$capt->setAttribute('style','line-height: 14px; vertical-align: middle;');

		$icon = new CLink(new CImg('images/general/chart.png','map',18,18,'borderless'),'maps.php?sysmapid='.$sourceid.'&fullscreen=1');
		$icon->setTarget('blank');

		$table->addRow(new CCol(array(
			$icon,
			SPACE,
			$capt)
		));
	}

	$td = new CCol(array(new CLink(S_MAPS.' &raquo;','maps.php','highlight')));
	$td->setAttribute('style','text-align: right;');

	$table->setFooter($td);

return $table;
}

// Author: Aly
function make_system_summary(){
	global $USER_DETAILS;
	
	$config = select_config();

	$table = new CTableInfo();
	$table->setHeader(array(
		is_show_all_nodes() ? S_NODE : null,
		S_HOST_GROUP,
		S_DISASTER,
		S_HIGH,
		S_AVERAGE,
		S_WARNING,
		S_INFORMATION,
		S_NOT_CLASSIFIED
	));

// SELECT HOST GROUPS {{{
	$options = array(
		'monitored_hosts' => 1,
		'with_monitored_triggers' => 1,
		'extendoutput' => 1	
	);
	$groups = CHostGroup::get($options);
	order_result($groups, 'name');
	$groupids = array();
	foreach($groups as $num => $group){
		$groupids[] = $group['groupid'];
		$groups[$num]['tab_priority'] = array();
		$groups[$num]['tab_priority'][TRIGGER_SEVERITY_DISASTER] = array('count' => 0, 'triggers' => array());
		$groups[$num]['tab_priority'][TRIGGER_SEVERITY_HIGH] = array('count' => 0, 'triggers' => array());
		$groups[$num]['tab_priority'][TRIGGER_SEVERITY_AVERAGE] = array('count' => 0, 'triggers' => array());
		$groups[$num]['tab_priority'][TRIGGER_SEVERITY_WARNING] = array('count' => 0, 'triggers' => array());
		$groups[$num]['tab_priority'][TRIGGER_SEVERITY_INFORMATION] = array('count' => 0, 'triggers' => array());
		$groups[$num]['tab_priority'][TRIGGER_SEVERITY_NOT_CLASSIFIED] = array('count' => 0, 'triggers' => array());
	}
// }}} SELECT HOST GROUPS
	
// SELECT TRIGGERS {{{
	$options = array(
		'groupids' => $groupids,
		'monitored' => 1,
		'select_hosts' => 1,
		'extendoutput' => 1,
		'filter' => 1,
		'only_true' => 1
	);
	$triggers = CTrigger::get($options);
	order_result($triggers, 'lastchange', ZBX_SORT_DOWN);
	foreach($triggers as $num => $trigger){
		if(!trigger_dependent($trigger['triggerid'])){
			if($groups[$trigger['groupid']]['tab_priority'][$trigger['priority']]['count'] < 30){
				$groups[$trigger['groupid']]['tab_priority'][$trigger['priority']]['triggers'][] = $trigger;
			}
			$groups[$trigger['groupid']]['tab_priority'][$trigger['priority']]['count']++;
		}
	}
// }}} SELECT TRIGGERS
	
	foreach($groups as $num => $group){
		$group_row = new CRow();
		if(is_show_all_nodes())
			$group_row->addItem(get_node_name_by_elid($group['groupid']));

		$name = new CLink($group['name'], 'tr_status.php?groupid='.$group['groupid'].'&show_triggers='.TRIGGERS_OPTION_ONLYTRUE);
		$name->setTarget('blank');
		$group_row->addItem($name);

		foreach($group['tab_priority'] as $severity => $data){
			$trigger_count = $data['count'];
			
			if($trigger_count){
				$table_inf = new CTableInfo();
				$table_inf->setAttribute('style', 'width: 400px;');
				$table_inf->setHeader(array(
					is_show_all_nodes() ? S_NODE : null,
					S_HOST,
					S_ISSUE,
					S_AGE,
					($config['event_ack_enable']) ? S_ACK : NULL,
					S_ACTIONS
				));

				foreach($data['triggers'] as $num => $trigger){
					$trigger_hosts = array();
					foreach($trigger['hosts'] as $host){
						$trigger_hosts[] = $host['host'];
					}
					$trigger_hosts = implode(', ', $trigger_hosts);


					$options = array(
						'triggerids' => $trigger['triggerid'],
						'object' => EVENT_SOURCE_TRIGGERS,
						'value' => TRIGGER_VALUE_TRUE,
						'extendoutput' => 1,
						'nopermissions' => 1,
						'limit' => 1,
						'sortfield' => 'eventid',
						'sortorder' => ZBX_SORT_DOWN
					);
					$event = CEvent::get($options);
					zbx_valueTo($event, array('object' => 1));
	
					if(!empty($event)){
						if($config['event_ack_enable']){
							$ack = ($event['acknowledged'] == 1) ? new CLink(S_YES, 'acknow.php?eventid='.$event['eventid'], 'off')
								: new CLink(S_NO, 'acknow.php?eventid='.$event['eventid'], 'on');
						}

						// $description = expand_trigger_description_by_data(zbx_array_merge($trigger, array('clock' => $event['clock'])), ZBX_FLAG_EVENT);
						$description = expand_trigger_description_by_data($trigger, ZBX_FLAG_EVENT);
						$actions = get_event_actions_status($event['eventid']);
					}
					else{
						$description = expand_trigger_description_by_data($trigger, ZBX_FLAG_EVENT);
						$ack = '-';
						$actions = S_NO_DATA_SMALL;
						$event['clock'] = $trigger['lastchange'];
					}

					$table_inf->addRow(array(
						get_node_name_by_elid($trigger['triggerid']),
						$trigger_hosts,
						new CCol($description, get_severity_style($trigger['priority'])),
						zbx_date2age($event['clock']),
						($config['event_ack_enable']) ? (new CCol($ack, 'center')) : NULL,
						$actions
					));
				}

				$trigger_count = new CSpan($trigger_count, 'pointer');
				$trigger_count->setHint($table_inf);

			}
			$group_row->addItem(new CCol($trigger_count, get_severity_style($severity, $trigger_count)));
			unset($table_inf);
		}
		$table->addRow($group_row);
	}
	$table->setFooter(new CCol(S_UPDATED.': '.date("H:i:s", time())));
return $table;
}

// Author: Aly
function make_status_of_zbx(){
	$table = new CTableInfo();
	$table->setHeader(array(
		S_PARAMETER,
		S_VALUE,
		S_DETAILS
	));

	show_messages(); //because in function get_status(); function clear_messages() is called when fsockopen() fails.
	$status=get_status();

	$table->addRow(array(S_ZABBIX_SERVER_IS_RUNNING,
	new CSpan($status['zabbix_server'], ($status['zabbix_server'] == S_YES ? 'off' : 'on')),' - '));
	//	$table->addRow(array(S_VALUES_STORED,$status['history_count']));$table->addRow(array(S_TRENDS_STORED,$status['trends_count']));
	$title = new CSpan(S_NUMBER_OF_HOSTS);
	$title->setAttribute('title', 'asdad');
	$table->addRow(array(S_NUMBER_OF_HOSTS ,$status['hosts_count'],
		array(
			new CSpan($status['hosts_count_monitored'],'off'),' / ',
			new CSpan($status['hosts_count_not_monitored'],'on'),' / ',
			new CSpan($status['hosts_count_template'],'unknown')
		)
	));
	$title = new CSpan(S_NUMBER_OF_ITEMS);
	$title->setAttribute('title', S_NUMBER_OF_ITEMS_TOOLTIP);
	$table->addRow(array($title, $status['items_count'],
		array(
			new CSpan($status['items_count_monitored'],'off'),' / ',
			new CSpan($status['items_count_disabled'],'on'),' / ',
			new CSpan($status['items_count_not_supported'],'unknown')
		)
	));
	$title = new CSpan(S_NUMBER_OF_TRIGGERS);
	$title->setAttribute('title', S_NUMBER_OF_TRIGGERS_TOOLTIP);
	$table->addRow(array($title,$status['triggers_count'],
		array(
			$status['triggers_count_enabled'],' / ',
			$status['triggers_count_disabled'].SPACE.SPACE.'[',
			new CSpan($status['triggers_count_on'],'on'),' / ',
			new CSpan($status['triggers_count_unknown'],'unknown'),' / ',
			new CSpan($status['triggers_count_off'],'off'),']'
		)
	));
/*
	$table->addRow(array(S_NUMBER_OF_EVENTS,$status['events_count'],' - '));
	$table->addRow(array(S_NUMBER_OF_ALERTS,$status['alerts_count'],' - '));
*/

//Log Out 10min
	$sql = 'SELECT COUNT(*) as usr_cnt FROM users u WHERE '.DBin_node('u.userid');
	$usr_cnt = DBfetch(DBselect($sql));

	$online_cnt = 0;
	$sql = 'SELECT DISTINCT s.userid, MAX(s.lastaccess) as lastaccess, MAX(u.autologout) as autologout, s.status '.
			' FROM sessions s, users u '.
			' WHERE '.DBin_node('s.userid').
				' AND u.userid=s.userid '.
				' AND s.status='.ZBX_SESSION_ACTIVE.
			' GROUP BY s.userid,s.status';
	$db_users = DBselect($sql);
	while($user=DBfetch($db_users)){
		$online_time = (($user['autologout'] == 0) || (ZBX_USER_ONLINE_TIME<$user['autologout']))?ZBX_USER_ONLINE_TIME:$user['autologout'];
		if(!is_null($user['lastaccess']) && (($user['lastaccess']+$online_time)>=time()) && (ZBX_SESSION_ACTIVE == $user['status'])) $online_cnt++;
	}

	$table->addRow(array(S_NUMBER_OF_USERS,$usr_cnt,new CSpan($online_cnt,'green')));
	$table->addRow(array(S_REQUIRED_SERVER_PERFORMANCE_NVPS,$status['qps_total'],' - '));
	$table->setFooter(new CCol(S_UPDATED.': '.date("H:i:s",time())));

return $table;
}


// author Aly
function make_latest_issues($params = array()){
	global $USER_DETAILS;

	$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY);
	$available_triggers = get_accessible_triggers(PERM_READ_ONLY, array());

	$scripts_by_hosts = CScript::getScriptsByHosts($available_hosts);


	$config=select_config();

	$sql_select = '';
	$sql_from = '';
	$sql_where= '';
	$limit = 20;
	if(!empty($params)){
		if(isset($params['limit']))
			$limit = $params['limit'];

		if(isset($params['groupid']) && ($params['groupid']>0)){
			$sql_select.=',g.name ';
			$sql_from.= ',groups g ';
			$sql_where.= ' AND g.groupid=hg.groupid '.
							' AND hg.groupid='.$params['groupid'];
		}

		if(isset($params['hostid']) && ($params['hostid']>0))
			$sql_where.= ' AND h.hostid='.$params['hostid'];
	}

	$table  = new CTableInfo();
	$table->setHeader(array(
		is_show_all_nodes()?S_NODE:null,
		(isset($params['groupid']) && ($params['groupid']>0))?S_GROUP:null,
		S_HOST,
		S_ISSUE,
		S_LAST_CHANGE,
		S_AGE,
		($config['event_ack_enable'])? S_ACK : NULL,
		S_ACTIONS
		));

	$sql = 'SELECT DISTINCT t.triggerid,t.type,t.status,t.description,t.expression,t.priority,t.lastchange,t.value,h.host,h.hostid '.$sql_select.
				' FROM triggers t,hosts h,items i,functions f,hosts_groups hg '.$sql_from.
				' WHERE f.itemid=i.itemid '.
					' AND h.hostid=i.hostid '.
					' AND hg.hostid=h.hostid '.
					' AND t.triggerid=f.triggerid '.
					' AND t.status='.TRIGGER_STATUS_ENABLED.
					' AND i.status='.ITEM_STATUS_ACTIVE.
					' AND '.DBcondition('t.triggerid',$available_triggers).
					' AND h.status='.HOST_STATUS_MONITORED.
					' AND t.value='.TRIGGER_VALUE_TRUE.
					$sql_where.
				' ORDER BY t.lastchange DESC';
	$result = DBselect($sql,$limit);
	while($row=DBfetch($result)){
// Check for dependencies
		if(trigger_dependent($row["triggerid"]))	continue;

		$host = null;
		$menus = '';

		$host_nodeid = id2nodeid($row['hostid']);

		foreach($scripts_by_hosts[$row['hostid']] as $id => $script){
			$script_nodeid = id2nodeid($script['scriptid']);
			if( (bccomp($host_nodeid ,$script_nodeid ) == 0))
				$menus.= "['".$script['name']."',\"javascript: openWinCentered('scripts_exec.php?execute=1&hostid=".$row['hostid']."&scriptid=".$script['scriptid']."','".S_TOOLS."',760,540,'titlebar=no, resizable=yes, scrollbars=yes, dialog=no');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
		}
		if(!empty($scripts_by_hosts)){
			$menus = "[".zbx_jsvalue(S_TOOLS).",null,null,{'outer' : ['pum_oheader'],'inner' : ['pum_iheader']}],".$menus;
		}

		$menus.= "[".zbx_jsvalue(S_LINKS).",null,null,{'outer' : ['pum_oheader'],'inner' : ['pum_iheader']}],";
		$menus.= "['".S_LATEST_DATA."',\"javascript: redirect('latest.php?groupid=0&hostid=".$row['hostid']."')\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";

		$menus = rtrim($menus,',');
		$menus = 'show_popup_menu(event,['.$menus.'],180);';

		$host = new CSpan($row['host'],'link_menu');
		$host->setAttribute('onclick','javascript: '.$menus);
		$host->setAttribute('onmouseover',"javascript: this.style.cursor = 'pointer';");

		$event_sql = 'SELECT DISTINCT e.eventid, e.value, e.clock, e.objectid as triggerid, e.acknowledged, t.type, t.url '.
					' FROM events e, triggers t '.
					' WHERE e.object='.EVENT_SOURCE_TRIGGERS.
						' AND e.objectid='.$row['triggerid'].
						' AND t.triggerid=e.objectid '.
						' AND e.value='.TRIGGER_VALUE_TRUE.
					' ORDER by e.object DESC, e.objectid DESC, e.eventid DESC';
		$res_events = DBSelect($event_sql,1);

		while($row_event=DBfetch($res_events)){
			$ack = NULL;
			if($config['event_ack_enable']){
				if($row_event['acknowledged'] == 1){
					$ack_info = make_acktab_by_eventid($row_event['eventid']);
					$ack_info->setAttribute('style','width: auto;');

					$ack=new CLink(S_YES,'acknow.php?eventid='.$row_event['eventid'],'off');
					$ack->setHint($ack_info, '', '', false);
				}
				else{
					$ack= new CLink(S_NO,'acknow.php?eventid='.$row_event['eventid'],'on');
				}
			}

//			$description = expand_trigger_description($row['triggerid']);
			$description = expand_trigger_description_by_data(zbx_array_merge($row, array('clock'=>$row_event['clock'])),ZBX_FLAG_EVENT);

//actions
			$actions = get_event_actions_stat_hints($row_event['eventid']);

			$clock = new CLink(
					zbx_date2str(S_DATE_FORMAT_YMDHMS,$row_event['clock']),
					'events.php?triggerid='.$row['triggerid'].'&source=0&show_unknown=1&nav_time='.$row_event['clock']
					);

			if($row_event['url'])
				$description = new CLink($description, $row_event['url'], null, null, true);
			else
				$description = new CSpan($description,'pointer');

			$description = new CCol($description,get_severity_style($row['priority']));
			$description->setHint(make_popup_eventlist($row_event['eventid'], $row['type']), '', '', false);

			$table->addRow(array(
				get_node_name_by_elid($row['triggerid']),
				$host,
				$description,
				$clock,
				zbx_date2age($row_event['clock']),
				$ack,
				$actions
			));
		}
		unset($row,$description,$actions,$alerts,$hint);
	}
	$table->setFooter(new CCol(S_UPDATED.': '.date("H:i:s",time())));
return $table;
}

// author Aly
function make_webmon_overview(){
	global $USER_DETAILS;

	$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY,PERM_RES_IDS_ARRAY);

	$table  = new CTableInfo();
	$table->setHeader(array(
		is_show_all_nodes() ? S_NODE : null,
		S_HOST_GROUP,
		S_OK,
		S_FAILED,
		S_IN_PROGRESS,
		S_UNKNOWN
		));

	$sql = 'SELECT DISTINCT g.groupid, g.name '.
			' FROM httptest ht, applications a, groups g, hosts_groups hg '.
			' WHERE '.DBcondition('hg.hostid',$available_hosts).
				' AND hg.hostid=a.hostid '.
				' AND g.groupid=hg.groupid '.
				' AND a.applicationid=ht.applicationid '.
				' AND ht.status='.HTTPTEST_STATUS_ACTIVE.
			' ORDER BY g.name';
	$host_groups = DBSelect($sql);

	while($group = DBFetch($host_groups)){

		$apps['ok'] = 0;
		$apps['failed'] = 0;
		$apps[HTTPTEST_STATE_BUSY] = 0;
		$apps[HTTPTEST_STATE_UNKNOWN] = 0;

		$sql = 'SELECT DISTINCT ht.httptestid, ht.curstate, ht.lastfailedstep '.
				' FROM httptest ht, applications a, hosts_groups hg, groups g '.
				' WHERE g.groupid='.$group['groupid'].
					' AND hg.groupid=g.groupid '.
					' AND a.hostid=hg.hostid '.
					' AND ht.applicationid=a.applicationid '.
					' AND ht.status='.HTTPTEST_STATUS_ACTIVE;

		$db_httptests = DBselect($sql);

		while($httptest_data = DBfetch($db_httptests)){

			if( HTTPTEST_STATE_BUSY == $httptest_data['curstate'] ){
				$apps[HTTPTEST_STATE_BUSY]++;
			}
			else if( HTTPTEST_STATE_IDLE == $httptest_data['curstate'] ){
				if($httptest_data['lastfailedstep'] > 0){
					$apps['failed']++;
				}
				else{
					$apps['ok']++;
				}
			}
			else{
				$apps[HTTPTEST_STATE_UNKNOWN]++;
			}
		}

		$table->addRow(array(
			is_show_all_nodes() ? get_node_name_by_elid($group['groupid']) : null,
			$group['name'],
			new CSpan($apps['ok'],'off'),
			new CSpan($apps['failed'],$apps['failed']?'on':'off'),
			new CSpan($apps[HTTPTEST_STATE_BUSY],$apps[HTTPTEST_STATE_BUSY]?'orange':'off'),
			new CSpan($apps[HTTPTEST_STATE_UNKNOWN],'unknown')
		));
	}
	$table->setFooter(new CCol(S_UPDATED.': '.date("H:i:s",time())));
return $table;
}

// Author: Aly
function make_discovery_status(){
	$drules = array();

	$db_drules = DBselect('select distinct * from drules where '.DBin_node('druleid').' order by name');
	while($drule_data = DBfetch($db_drules)){
		$drules[$drule_data['druleid']] = $drule_data;
		$drules[$drule_data['druleid']]['up'] = 0;
		$drules[$drule_data['druleid']]['down'] = 0;
	}

	$db_dhosts = DBselect('SELECT d.* '.
					' FROM dhosts d '.
					' ORDER BY d.dhostid,d.status,d.ip');

	$services = array();
	$discovery_info = array();

	while($drule_data = DBfetch($db_dhosts)){
		if(DHOST_STATUS_DISABLED == $drule_data['status']){
			$drules[$drule_data['druleid']]['down']++;		}
		else{
			$drules[$drule_data['druleid']]['up']++;
		}
	}

	$header = array(
		is_show_all_nodes() ? new CCol(S_NODE, 'center') : null,
		new CCol(S_DISCOVERY_RULE, 'center'),
		new CCol(S_UP),
		new CCol(S_DOWN)
		);

	$table  = new CTableInfo();
	$table->setHeader($header,'vertical_header');

	foreach($drules as $druleid => $drule){
		$table->addRow(array(
			get_node_name_by_elid($druleid),
			new CLink(get_node_name_by_elid($drule['druleid'], null, ': ').$drule['name'],'discovery.php?druleid='.$druleid),
			new CSpan($drule['up'],'green'),
			new CSpan($drule['down'],($drule['down'] > 0)?'red':'green')
		));
	}
	$table->setFooter(new CCol(S_UPDATED.': '.date("H:i:s",time())));

return 	$table;
}

function make_latest_data(){
	global $USER_DETAILS;

	$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY,PERM_RES_IDS_ARRAY);

	while($db_app = DBfetch($db_applications)){
		$db_items = DBselect('SELECT DISTINCT i.* '.
					' FROM items i,items_applications ia'.
					' WHERE ia.applicationid='.$db_app['applicationid'].
						' AND i.itemid=ia.itemid'.
						' AND i.status='.ITEM_STATUS_ACTIVE.
					order_by('i.description,i.itemid,i.lastclock'));

		$app_rows = array();
		$item_cnt = 0;
		while($db_item = DBfetch($db_items)){
			$description = item_description($db_item);

			if( !zbx_empty($_REQUEST['select']) && !zbx_stristr($description, $_REQUEST['select']) ) continue;

			++$item_cnt;
			if(!uint_in_array($db_app['applicationid'],$_REQUEST['applications']) && !isset($show_all_apps)) continue;

			if(isset($db_item['lastclock']))
				$lastclock=date(S_DATE_FORMAT_YMDHMS,$db_item['lastclock']);
			else
				$lastclock = new CCol('-', 'center');

			$lastvalue=format_lastvalue($db_item);

			if( isset($db_item['lastvalue']) && isset($db_item['prevvalue']) &&
				($db_item['value_type'] == 0) && ($db_item['lastvalue']-$db_item['prevvalue'] != 0) )
			{
				if($db_item['lastvalue']-$db_item['prevvalue']<0){
					$change=convert_units($db_item['lastvalue']-$db_item['prevvalue'],$db_item['units']);
				}
				else{
					$change='+'.convert_units($db_item['lastvalue']-$db_item['prevvalue'],$db_item['units']);
				}
				$change=nbsp($change);
			}
			else{
				$change=new CCol('-','center');
			}
			if(($db_item['value_type']==ITEM_VALUE_TYPE_FLOAT) ||($db_item['value_type']==ITEM_VALUE_TYPE_UINT64)){
				$actions=new CLink(S_GRAPH,'history.php?action=showgraph&itemid='.$db_item['itemid']);
			}
			else{
				$actions=new CLink(S_HISTORY,'history.php?action=showvalues&period=3600&itemid='.$db_item['itemid']);
			}
			array_push($app_rows, new CRow(array(
				is_show_all_nodes() ? SPACE : null,
				$_REQUEST['hostid'] > 0 ? NULL : SPACE,
				str_repeat(SPACE,6).$description,
				$lastclock,
				new CCol($lastvalue, $lastvalue=='-' ? 'center' : null),
				$change,
				$actions
				)));
		}

		if($item_cnt > 0){
			if(uint_in_array($db_app['applicationid'],$_REQUEST['applications']) || isset($show_all_apps)){
				$link = new CLink(new CImg('images/general/opened.gif'),
					'?close=1&applicationid='.$db_app['applicationid'].
					url_param('groupid').url_param('hostid').url_param('applications').
					url_param('select'));
			}
			else{
				$link = new CLink(new CImg('images/general/closed.gif'),
					'?open=1&applicationid='.$db_app['applicationid'].
					url_param('groupid').url_param('hostid').url_param('applications').
					url_param('select'));
			}

			$col = new CCol(array($link,SPACE,bold($db_app['name']),
				SPACE.'('.$item_cnt.SPACE.S_ITEMS.')'));
			$col->setColSpan(5);

			$table->ShowRow(array(
					get_node_name_by_elid($db_app['hostid']),
					$_REQUEST['hostid'] > 0 ? NULL : $db_app['host'],
					$col
					));

			$any_app_exist = true;

			foreach($app_rows as $row)	$table->ShowRow($row);
		}
	}
}

function make_graph_menu(&$menu,&$submenu){

	$menu['menu_graphs'][] = array(
				S_FAVOURITE.SPACE.S_GRAPHS,
				null,
				null,
				array('outer'=> array('pum_oheader'), 'inner'=>array('pum_iheader'))
		);
	$menu['menu_graphs'][] = array(
				S_ADD.SPACE.S_GRAPH,
				'javascript: '.
				"PopUp('popup.php?srctbl=graphs&".
					'reference=dashboard&'.
					'dstfrm=fav_form&'.
					'dstfld1=favobj&'.
					'dstfld2=favid&'.
					'srcfld1=name&'.
					"srcfld2=graphid',800,450);".
				'void(0);',
				null,
				array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu'))
		);
	$menu['menu_graphs'][] = array(
				S_ADD.SPACE.S_SIMPLE_GRAPH,
				'javascript: '.
				"PopUp('popup.php?srctbl=simple_graph&".
					'reference=dashboard&'.
					'dstfrm=fav_form&'.
					'dstfld1=favobj&'.
					'dstfld2=favid&'.
					'srcfld1=description&'.
					"srcfld2=itemid',800,450);".
				"void(0);",
				null,
				array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu'))
		);
	$menu['menu_graphs'][] = array(
				S_REMOVE,
				null,
				null,
				array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu'))
		);
	$submenu['menu_graphs'] = make_graph_submenu();
}

function make_graph_submenu(){
	$graphids = array();

	$fav_graphs = get_favorites('web.favorite.graphids');

	foreach($fav_graphs as $key => $favorite){

		$source = $favorite['source'];
		$sourceid = $favorite['value'];

		if('itemid' == $source){
			if(!$item = get_item_by_itemid($sourceid)) continue;

			$item_added = true;

			$host = get_host_by_itemid($sourceid);
			$item["description"] = item_description($item);

			$graphids[] = array(
							'name'	=>	$host['host'].':'.$item['description'],
							'favobj'=>	'itemid',
							'favid'	=>	$sourceid,
							'action'=>	'remove'
						);
		}
		else{
			if(!$graph = get_graph_by_graphid($sourceid)) continue;

			$graph_added = true;

			$result = get_hosts_by_graphid($sourceid);
			$ghost = DBFetch($result);

			$graphids[] = array(
							'name'	=>	$ghost['host'].':'.$graph['name'],
							'favobj'=>	'graphid',
							'favid'	=>	$sourceid,
							'action'=>	'remove'
						);
		}
	}

	if(isset($graph_added)){
			$graphids[] = array(
			'name'	=>	S_REMOVE.SPACE.S_ALL_S.SPACE.S_GRAPHS,
			'favobj'=>	'graphid',
			'favid'	=>	0,
			'action'=>	'remove'
		);
	}

	if(isset($item_added)){
		$graphids[] = array(
			'name'	=>	S_REMOVE.SPACE.S_ALL_S.SPACE.S_SIMPLE_GRAPHS,
			'favobj'=>	'itemid',
			'favid'	=>	0,
			'action'=>	'remove'
		);
	}

return $graphids;
}

function make_sysmap_menu(&$menu,&$submenu){

	$menu['menu_sysmaps'][] = array(S_FAVOURITE.SPACE.S_MAPS, null, null, array('outer'=> array('pum_oheader'), 'inner'=>array('pum_iheader')));
	$menu['menu_sysmaps'][] = array(
				S_ADD.SPACE.S_MAP,
				'javascript: '.
				"PopUp('popup.php?srctbl=sysmaps&".
					'reference=dashboard&'.
					'dstfrm=fav_form&'.
					'dstfld1=favobj&'.
					'dstfld2=favid&'.
					'srcfld1=name&'.
					"srcfld2=sysmapid',800,450);".
				"void(0);",
				null,
				array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu')
		));
	$menu['menu_sysmaps'][] = array(S_REMOVE, null, null, array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu')));
	$submenu['menu_sysmaps'] = make_sysmap_submenu();
}

function make_sysmap_submenu(){
	$sysmapids = array();
	$fav_sysmaps = get_favorites('web.favorite.sysmapids');

	foreach($fav_sysmaps as $key => $favorite){

		$source = $favorite['source'];
		$sourceid = $favorite['value'];

		if(!$sysmap = get_sysmap_by_sysmapid($sourceid)) continue;

		$sysmapids[] = array(
							'name'	=>	$sysmap['name'],
							'favobj'=>	'sysmapid',
							'favid'	=>	$sourceid,
							'action'=>	'remove'
						);
	}

	if(!empty($sysmapids)){
		$sysmapids[] = array(
							'name'	=>	S_REMOVE.SPACE.S_ALL_S.SPACE.S_MAPS,
							'favobj'=>	'sysmapid',
							'favid'	=>	0,
							'action'=>	'remove'
						);
	}

return $sysmapids;
}

function make_screen_menu(&$menu,&$submenu){

	$menu['menu_screens'][] = array(S_FAVOURITE.SPACE.S_SCREENS, null, null, array('outer'=> array('pum_oheader'), 'inner'=>array('pum_iheader')));
	$menu['menu_screens'][] = array(
				S_ADD.SPACE.S_SCREEN,
				'javascript: '.
				"PopUp('popup.php?srctbl=screens&".
					'reference=dashboard&'.
					'dstfrm=fav_form&'.
					'dstfld1=favobj&'.
					'dstfld2=favid&'.
					'srcfld1=name&'.
					"srcfld2=screenid',800,450);".
				"void(0);",
				null,
				array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu')
		));
	$menu['menu_screens'][] = array(
				S_ADD.SPACE.S_SLIDESHOW,
				'javascript: '.
				"PopUp('popup.php?srctbl=slides&".
					'reference=dashboard&'.
					'dstfrm=fav_form&'.
					'dstfld1=favobj&'.
					'dstfld2=favid&'.
					'srcfld1=name&'.
					"srcfld2=slideshowid',800,450);".
				"void(0);",
				null,
				array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu')
		));
	$menu['menu_screens'][] = array(S_REMOVE, null, null, array('outer' => 'pum_o_submenu', 'inner'=>array('pum_i_submenu')));
	$submenu['menu_screens'] = make_screen_submenu();
}

function make_screen_submenu(){
	$screenids = array();

	$fav_screens = get_favorites('web.favorite.screenids');

	foreach($fav_screens as $key => $favorite){
		$source = $favorite['source'];
		$sourceid = $favorite['value'];

		if('slideshowid' == $source){
			if(!$slide = get_slideshow_by_slideshowid($sourceid)) continue;
			$slide_added = true;

			$screenids[] = array(
								'name'	=>	$slide['name'],
								'favobj'=>	'slideshowid',
								'favid'	=>	$sourceid,
								'action'=>	'remove'
							);

		}
		else{
			if(!$screen = get_screen_by_screenid($sourceid)) continue;
			$screen_added = true;

			$screenids[] = array(
								'name'	=>	$screen['name'],
								'favobj'=>	'screenid',
								'favid'	=>	$sourceid,
								'action'=>	'remove'
							);
		}
	}


	if(isset($screen_added)){
		$screenids[] = array(
			'name'	=>	S_REMOVE.SPACE.S_ALL_S.SPACE.S_SCREENS,
			'favobj'=>	'screenid',
			'favid'	=>	0,
			'action'=>	'remove'
		);
	}

	if(isset($slide_added)){
		$screenids[] = array(
			'name'	=>	S_REMOVE.SPACE.S_ALL_S.SPACE.S_SLIDES,
			'favobj'=>	'slideshowid',
			'favid'	=>	0,
			'action'=>	'remove'
		);
	}

return $screenids;
}

?>
