<?php
/*
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
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
	require_once 'include/config.inc.php';
	require_once 'include/hosts.inc.php';
	require_once 'include/items.inc.php';

	$page['title'] = S_LATEST_DATA;
	$page['file'] = 'latest.php';
	$page['hist_arg'] = array('groupid','hostid','show','select','open','applicationid');
	$page['scripts'] = array('scriptaculous.js?load=effects');

	$page['type'] = detect_page_type(PAGE_TYPE_HTML);

	define('ZBX_PAGE_MAIN_HAT','hat_latest');

	if(PAGE_TYPE_HTML == $page['type']){
		define('ZBX_PAGE_DO_REFRESH', 1);
	}
//	define('ZBX_PAGE_DO_JS_REFRESH', 1);

include_once 'include/page_header.php';
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'applications'=>	array(T_ZBX_INT, O_OPT,	NULL,	DB_ID,		NULL),
		'applicationid'=>	array(T_ZBX_INT, O_OPT,	NULL,	DB_ID,		NULL),
		'close'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN('1'),	NULL),
		'open'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN('1'),	NULL),
		'groupbyapp'=>		array(T_ZBX_INT, O_OPT,	NULL,	IN('1'),	NULL),

		'groupid'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,		NULL),
		'hostid'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,		NULL),


		'fullscreen'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	IN('0,1'),		NULL),

// filter
		'select'=>			array(T_ZBX_STR, O_OPT, NULL,	NULL,		NULL),

		'filter_rst'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	IN(array(0,1)),	NULL),
		'filter_set'=>		array(T_ZBX_STR, O_OPT,	P_SYS,	null,	NULL),

//ajax
		'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			'isset({favid})'),
		'favid'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		NULL),
		'state'=>		array(T_ZBX_INT, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj})'),
	);

	check_fields($fields);

// HEADER REQUEST
	$_REQUEST['select'] = get_request('select',CProfile::get('web.latest.filter.select', ''));
	CProfile::update('web.latest.filter.select', $_REQUEST['select'], PROFILE_TYPE_STR);

	$options = array('allow_all_hosts','monitored_hosts','with_historical_items');
	//if(!$ZBX_WITH_ALL_NODES)	array_push($options,'only_current_node');

//SDI($_REQUEST['groupid'].' : '.$_REQUEST['hostid']);
	$params = array();
	foreach($options as  $option) $params[$option] = 1;
	$PAGE_GROUPS = get_viewed_groups(PERM_READ_ONLY, $params);
	$PAGE_HOSTS = get_viewed_hosts(PERM_READ_ONLY, $PAGE_GROUPS['selected'], $params);
//SDI($_REQUEST['groupid'].' : '.$_REQUEST['hostid']);
	validate_group_with_host($PAGE_GROUPS,$PAGE_HOSTS);
//SDI($_REQUEST['groupid'].' : '.$_REQUEST['hostid']);

//----------------
?>
<?php
/* AJAX */
	if(isset($_REQUEST['favobj'])){
		if('filter' == $_REQUEST['favobj']){
			CProfile::update('web.latest.filter.state',$_REQUEST['state'], PROFILE_TYPE_INT);
		}
/*
		else if('refresh' == $_REQUEST['favobj']){
			switch($_REQUEST['favid']){
				case ZBX_PAGE_MAIN_HAT:
					include_once('blocks/latest.page.php');
					break;
			}
		}
//*/
	}

	if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
		include_once('include/page_footer.php');
		exit();
	}
//--------

/* FILTER */
	if(isset($_REQUEST['filter_rst'])){
		$_REQUEST['select'] = '';
	}

	$_REQUEST['select'] = get_request('select',CProfile::get('web.latest.filter.select',''));

	if(isset($_REQUEST['filter_set']) || isset($_REQUEST['filter_rst'])){
		CProfile::update('web.latest.filter.select',$_REQUEST['select'], PROFILE_TYPE_STR);
	}
// --------------

	$latest_wdgt = new CWidget();
// Header

	$url = '?fullscreen='.($_REQUEST['fullscreen']?'0':'1');

	$fs_icon = new CDiv(SPACE,'fullscreen');
	$fs_icon->setAttribute('title',$_REQUEST['fullscreen']?S_NORMAL.' '.S_VIEW:S_FULLSCREEN);
	$fs_icon->addAction('onclick',new CJSscript("javascript: document.location = '".$url."';"));

	$latest_wdgt->addPageHeader(S_LATEST_DATA_BIG,$fs_icon);

// 2nd header
	$r_form = new CForm();
	$r_form->setMethod('get');

//	$cmbGroup = new CComboBox('groupid',$_REQUEST['groupid'],"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."',this.form);");
//	$cmbHosts = new CComboBox('hostid',$_REQUEST['hostid'],"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."',this.form);");

	$available_groups = $PAGE_GROUPS['groupids'];
	$available_hosts = $PAGE_HOSTS['hostids'];

	$cmbGroup = new CComboBox('groupid',$PAGE_GROUPS['selected'],'javascript: submit();');
	$cmbHosts = new CComboBox('hostid',$PAGE_HOSTS['selected'],'javascript: submit();');

	foreach($PAGE_GROUPS['groups'] as $groupid => $name){
		$cmbGroup->addItem($groupid, get_node_name_by_elid($groupid, null, ': ').$name);
	}
	foreach($PAGE_HOSTS['hosts'] as $hostid => $name){
		$cmbHosts->addItem($hostid, get_node_name_by_elid($hostid, null, ': ').$name);
	}

	$r_form->addItem(array(S_GROUP.SPACE,$cmbGroup));
	$r_form->addItem(array(SPACE.S_HOST.SPACE,$cmbHosts));

	$latest_wdgt->addHeader(S_ITEMS_BIG,$r_form);
//	show_table_header(S_LATEST_DATA_BIG,$r_form);
//-------------

/************************* FILTER **************************/
/***********************************************************/
	$filterForm = new CFormTable();
	$filterForm->setAttribute('name','zbx_filter');
	$filterForm->setAttribute('id','zbx_filter');

	$filterForm->addRow(S_SHOW_ITEMS_WITH_DESCRIPTION_LIKE, new CTextBox('select',$_REQUEST['select'],20));

	$reset = new CButton("filter_rst",S_RESET);
	$reset->setType('button');
	$reset->setAction('javascript: var uri = new Curl(location.href); uri.setArgument("filter_rst",1); location.href = uri.getUrl();');

	$filterForm->addItemToBottomRow(new CButton("filter_set",S_FILTER));
	$filterForm->addItemToBottomRow($reset);

	$latest_wdgt->addFlicker($filterForm, CProfile::get('web.latest.filter.state',1));
//-------

	validate_sort_and_sortorder('i.description',ZBX_SORT_UP);

	$_REQUEST['groupbyapp'] = get_request('groupbyapp',CProfile::get('web.latest.groupbyapp',1));
	CProfile::update('web.latest.groupbyapp',$_REQUEST['groupbyapp'],PROFILE_TYPE_INT);

	$_REQUEST['applications'] = get_request('applications', get_favorites('web.latest.applications'));
	$_REQUEST['applications'] = zbx_objectValues($_REQUEST['applications'], 'value');

	if(isset($_REQUEST['open'])){
		if(!isset($_REQUEST['applicationid'])){
			$_REQUEST['applications'] = array();
			$show_all_apps = 1;
		}
		else if(!uint_in_array($_REQUEST['applicationid'],$_REQUEST['applications'])){
			array_push($_REQUEST['applications'],$_REQUEST['applicationid']);
		}

	}
	else if(isset($_REQUEST['close'])){
		if(!isset($_REQUEST['applicationid'])){
			$_REQUEST['applications'] = array();
		}
		else if(($i=array_search($_REQUEST['applicationid'], $_REQUEST['applications'])) !== FALSE){
			unset($_REQUEST['applications'][$i]);
		}
	}

	if(count($_REQUEST['applications']) > 25){
		$_REQUEST['applications'] = array_slice($_REQUEST['applications'], -25);
	}
	
	rm4favorites('web.latest.applications');
	foreach($_REQUEST['applications'] as $application){
		add2favorites('web.latest.applications', $application);
	}
	
	/* limit opened application count */
	// while(count($_REQUEST['applications']) > 25){
		// array_shift($_REQUEST['applications']);
	// }

	// CProfile::update('web.latest.applications',$_REQUEST['applications'],PROFILE_TYPE_ARRAY_ID);
?>
<?php
	if(isset($show_all_apps)){
		$url = '?close=1'.
			url_param('groupid').
			url_param('hostid').
			url_param('applications').
			url_param('select');
		$link = new CLink(new CImg('images/general/opened.gif'),$url);
//		$link = new CLink(new CImg('images/general/opened.gif'),$url,null,"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."','".$url."');");
	}
	else{
		$url = '?open=1'.
			url_param('groupid').
			url_param('hostid').
			url_param('applications').
			url_param('select');
		$link = new CLink(new CImg('images/general/closed.gif'),$url);
//		$link = new CLink(new CImg('images/general/closed.gif'),$url,null,"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."','".$url."');");
	}

	$table=new CTableInfo();
	$table->setHeader(array(
		is_show_all_nodes()?make_sorting_link(S_NODE,'h.hostid') : null,
		($_REQUEST['hostid'] ==0)?make_sorting_link(S_HOST,'h.host') : NULL,
		array($link,SPACE,make_sorting_link(S_DESCRIPTION,'i.description')),
		make_sorting_link(S_LAST_CHECK,'i.lastclock'),
		S_LAST_VALUE,
		S_CHANGE,
		S_HISTORY));

//	$table->ShowStart();

	$db_apps = array();
	$db_appids = array();

	$sql_from = '';
	$sql_where = '';
	if($_REQUEST['groupid'] > 0){
		$sql_from .= ',hosts_groups hg ';
		$sql_where.= ' AND hg.hostid=h.hostid AND hg.groupid='.$_REQUEST['groupid'];
	}

	if($_REQUEST['hostid']>0){
		$sql_where.= ' AND h.hostid='.$_REQUEST['hostid'];
	}

	$sql = 'SELECT DISTINCT h.host,h.hostid, a.* '.
			' FROM applications a, hosts h '.$sql_from.
			' WHERE a.hostid=h.hostid'.
				$sql_where.
				' AND '.DBcondition('h.hostid',$available_hosts).
				' AND h.status='.HOST_STATUS_MONITORED.
			order_by('h.host,h.hostid','a.name,a.applicationid');
//SDI($sql);
	$db_app_res = DBselect($sql);
	while($db_app = DBfetch($db_app_res)){
		$db_app['item_cnt'] = 0;

		$db_apps[$db_app['applicationid']] = $db_app;
		$db_appids[$db_app['applicationid']] = $db_app['applicationid'];
	}

	$tab_rows = array();

	$sql = 'SELECT DISTINCT i.*, ia.applicationid '.
			' FROM items i,items_applications ia'.
			' WHERE '.DBcondition('ia.applicationid',$db_appids).
				' AND i.itemid=ia.itemid AND i.lastvalue IS NOT NULL'.
				' AND (i.status='.ITEM_STATUS_ACTIVE. ' OR i.status='.ITEM_STATUS_NOTSUPPORTED.')'.
			order_by('i.description,i.itemid,i.lastclock');
//SDI($sql);
	$db_items = DBselect($sql);
	while($db_item = DBfetch($db_items)){
		$description = item_description($db_item);

		if(!empty($_REQUEST['select']) && !zbx_stristr($description, $_REQUEST['select']) ) continue;
		
		if(strpos($db_item['units'], ',') !== false)
			list($db_item['units'], $db_item['unitsLong']) = explode(',', $db_item['units']);
		else
			$db_item['unitsLong'] = '';

		$db_app = &$db_apps[$db_item['applicationid']];

		if(!isset($tab_rows[$db_app['applicationid']])) $tab_rows[$db_app['applicationid']] = array();
		$app_rows = &$tab_rows[$db_app['applicationid']];

		$db_app['item_cnt']++;

		if(!uint_in_array($db_app['applicationid'],$_REQUEST['applications']) && !isset($show_all_apps)) continue;

		if(isset($db_item['lastclock']))
			$lastclock=date(S_DATE_FORMAT_YMDHMS,$db_item['lastclock']);
		else
			$lastclock = ' - ';

		$lastvalue = format_lastvalue($db_item);

		if(isset($db_item['lastvalue']) && isset($db_item['prevvalue']) && ($db_item['value_type'] == 0) && ($db_item['lastvalue']-$db_item['prevvalue'] != 0)){
			if($db_item['lastvalue']-$db_item['prevvalue']<0){
				$change=convert_units($db_item['lastvalue']-$db_item['prevvalue'],$db_item['units']);
			}
			else{
				$change='+'.convert_units($db_item['lastvalue']-$db_item['prevvalue'],$db_item['units']);
			}
			$change=nbsp($change);
		}
		else{
			$change = ' - ';
		}

		if(($db_item['value_type']==ITEM_VALUE_TYPE_FLOAT) || ($db_item['value_type']==ITEM_VALUE_TYPE_UINT64)){
			$actions = new CLink(S_GRAPH,'history.php?action=showgraph&itemid='.$db_item['itemid']);
		}
		else{
			$actions = new CLink(S_HISTORY,'history.php?action=showvalues&period=3600&itemid='.$db_item['itemid']);
		}

		$item_status = $db_item['status']==3?'unknown': null;

		array_push($app_rows, new CRow(array(
			is_show_all_nodes()?SPACE:null,
			($_REQUEST['hostid']>0)?NULL:SPACE,
			new CCol(str_repeat(SPACE,6).$description, $item_status),
			new CCol($lastclock, $item_status),
			new CCol($lastvalue, $item_status),
			new CCol($change, $item_status),
			$actions
			)));
	}
	unset($app_rows);
	unset($db_app);

	foreach($db_apps as $appid => $db_app){
		if(!isset($tab_rows[$appid])) continue;

		$app_rows = $tab_rows[$appid];

		if(uint_in_array($db_app['applicationid'],$_REQUEST['applications']) || isset($show_all_apps)){
			$url = '?close=1&applicationid='.$db_app['applicationid'].
				url_param('groupid').url_param('hostid').url_param('applications').
				url_param('fullscreen').url_param('select');

			$link = new CLink(new CImg('images/general/opened.gif'),$url);
//			$link = new CLink(new CImg('images/general/opened.gif'),$url,null,"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."','".$url."');");
		}
		else{
			$url = '?open=1&applicationid='.$db_app['applicationid'].
					url_param('groupid').url_param('hostid').url_param('applications').
					url_param('fullscreen').url_param('select');
			$link = new CLink(new CImg('images/general/closed.gif'),$url);
//			$link = new CLink(new CImg('images/general/closed.gif'),$url,null,"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."','".$url."');");
		}

		$col = new CCol(array($link,SPACE,bold($db_app['name']),SPACE.'('.$db_app['item_cnt'].SPACE.S_ITEMS.')'));
		$col->setColSpan(5);

		$table->addRow(array(
				get_node_name_by_elid($db_app['applicationid']),
				($_REQUEST['hostid'] > 0)?NULL:$db_app['host'],
				$col
			));

		foreach($app_rows as $row)
			$table->addRow($row);
	}

// OTHER ITEMS (which doesn't linked to application)
	$db_hosts = array();
	$db_hostids = array();

	$sql = 'SELECT DISTINCT h.host,h.hostid '.
			' FROM hosts h'.$sql_from.', items i '.
				' LEFT JOIN items_applications ia ON ia.itemid=i.itemid'.
			' WHERE ia.itemid is NULL '.
				$sql_where.
				' AND h.hostid=i.hostid '.
				' AND h.status='.HOST_STATUS_MONITORED.
				' AND i.status='.ITEM_STATUS_ACTIVE.
				' AND '.DBcondition('h.hostid',$available_hosts).
			' ORDER BY h.host';

	$db_host_res = DBselect($sql);
	while($db_host = DBfetch($db_host_res)){
		$db_host['item_cnt'] = 0;

		$db_hosts[$db_host['hostid']] = $db_host;
		$db_hostids[$db_host['hostid']] = $db_host['hostid'];
	}

	$tab_rows = array();

	$sql = 'SELECT DISTINCT h.host,h.hostid,i.* '.
			' FROM hosts h'.$sql_from.', items i '.
				' LEFT JOIN items_applications ia ON ia.itemid=i.itemid'.
			' WHERE ia.itemid is NULL '.
				$sql_where.
				' AND h.hostid=i.hostid '.
				' AND h.status='.HOST_STATUS_MONITORED.
				' AND i.status='.ITEM_STATUS_ACTIVE.
				' AND '.DBcondition('h.hostid',$db_hostids).
			' ORDER BY i.description,i.itemid';
	$db_items = DBselect($sql);
	while($db_item = DBfetch($db_items)){

		$description = item_description($db_item);

		if(!empty($_REQUEST['select']) && !zbx_stristr($description, $_REQUEST['select']) ) continue;
		
		if(strpos($db_item['units'], ',') !== false)
			list($db_item['units'], $db_item['unitsLong']) = explode(',', $db_item['units']);
		else
			$db_item['unitsLong'] = '';

		$db_host = &$db_hosts[$db_item['hostid']];

		if(!isset($tab_rows[$db_host['hostid']])) $tab_rows[$db_host['hostid']] = array();
		$app_rows = &$tab_rows[$db_host['hostid']];

		$db_host['item_cnt']++;

		if(!uint_in_array(0,$_REQUEST['applications']) && !isset($show_all_apps)) continue;


		if(isset($db_item['lastclock']))
			$lastclock=zbx_date2str(S_DATE_FORMAT_YMDHMS,$db_item['lastclock']);
		else
			$lastclock = new CCol(' - ');

		$lastvalue=format_lastvalue($db_item);

		if( isset($db_item['lastvalue']) && isset($db_item['prevvalue']) &&
			($db_item['value_type'] == ITEM_VALUE_TYPE_FLOAT || $db_item['value_type'] == ITEM_VALUE_TYPE_UINT64) &&
			($db_item['lastvalue']-$db_item['prevvalue'] != 0) )
		{
			$change = '';
			if($db_item['lastvalue']-$db_item['prevvalue']>0){
				$change = '+';
			}

			$digits = ($db_item['value_type'] == ITEM_VALUE_TYPE_FLOAT) ? 2 : 0;
			$change = $change . convert_units(bcsub($db_item['lastvalue'], $db_item['prevvalue'], $digits), $db_item['units'], 0);
			$change = nbsp($change);
		}
		else{
			$change = new CCol(' - ');
		}

		if(($db_item['value_type']==ITEM_VALUE_TYPE_FLOAT) || ($db_item['value_type']==ITEM_VALUE_TYPE_UINT64)){
			$actions=new CLink(S_GRAPH,'history.php?action=showgraph&itemid='.$db_item['itemid']);
		}
		else{
			$actions=new CLink(S_HISTORY,'history.php?action=showvalues&period=3600&itemid='.$db_item['itemid']);
		}

		array_push($app_rows, new CRow(array(
			is_show_all_nodes()?($db_host['item_cnt']?SPACE:get_node_name_by_elid($db_item['itemid'])):null,
			$_REQUEST['hostid']?NULL:($db_host['item_cnt']?SPACE:$db_item['host']),
			str_repeat(SPACE, 6).$description,
			$lastclock,
			new CCol($lastvalue),
			$change,
			$actions
			)));
	}
	unset($app_rows);
	unset($db_host);

	foreach($db_hosts as $hostid => $db_host){

		if(!isset($tab_rows[$hostid])) continue;
		$app_rows = $tab_rows[$hostid];

		if(uint_in_array(0,$_REQUEST['applications']) || isset($show_all_apps)){
			$url = '?close=1&applicationid=0'.
				url_param('groupid').url_param('hostid').
				url_param('applications').url_param('select');
			$link = new CLink(new CImg('images/general/opened.gif'),$url);
//			$link = new CLink(new CImg('images/general/opened.gif'),$url,null,"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."','".$url."');");
		}
		else{
			$url = '?open=1&applicationid=0'.
				url_param('groupid').url_param('hostid').
				url_param('applications').url_param('select');
			$link = new CLink(new CImg('images/general/closed.gif'),$url);
//			$link = new CLink(new CImg('images/general/closed.gif'),$url,null,"javascript: return updater.onetime_update('".ZBX_PAGE_MAIN_HAT."','".$url."');");
		}

		$col = new CCol(array($link,SPACE,bold(S_MINUS_OTHER_MINUS),SPACE.'('.$db_host['item_cnt'].SPACE.S_ITEMS.')'));
		$col->setColSpan(5);

		$table->addRow(array(
				get_node_name_by_elid($db_host['hostid']),
				($_REQUEST['hostid'] > 0)?NULL:$db_host['host'],
				$col
				));

		foreach($app_rows as $row)
			$table->addRow($row);
	}

/*
// Refresh tab
	$refresh_tab = array(
		array('id'	=> ZBX_PAGE_MAIN_HAT,
				'interval' 	=> $USER_DETAILS['refresh'],
				'url'	=>	zbx_empty($_SERVER['QUERY_STRING'])?'':'?'.$_SERVER['QUERY_STRING'],
			)
	);
//*/

	$latest_wdgt->addItem($table);
	$latest_wdgt->show();

//	add_refresh_objects($refresh_tab);
?>
<?php
include_once 'include/page_footer.php';
?>
