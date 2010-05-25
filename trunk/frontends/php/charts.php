<?php
/*
** ZABBIX
** Copyright (C) 2000-2010 SIA Zabbix
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
require_once('include/config.inc.php');
require_once('include/hosts.inc.php');
require_once('include/graphs.inc.php');

$page['title'] = 'S_CUSTOM_GRAPHS';
$page['file'] = 'charts.php';
$page['hist_arg'] = array('hostid','groupid','graphid');
$page['scripts'] = array('effects.js', 'dragdrop.js','class.calendar.js', 'gtlc.js');

$page['type'] = detect_page_type(PAGE_TYPE_HTML);

define('ZBX_PAGE_DO_REFRESH', 1);

include_once('include/page_header.php');
?>
<?php
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'groupid'=>		array(T_ZBX_INT, O_OPT,	 P_SYS,		DB_ID,NULL),
		'hostid'=>		array(T_ZBX_INT, O_OPT,  P_SYS,		DB_ID,NULL),
		'graphid'=>		array(T_ZBX_INT, O_OPT,  P_SYS,		DB_ID,NULL),
		'period'=>		array(T_ZBX_INT, O_OPT,  P_SYS, 	null,NULL),
		'stime'=>		array(T_ZBX_STR, O_OPT,  P_SYS, 	NULL,NULL),
		'action'=>		array(T_ZBX_STR, O_OPT,  P_SYS, 	IN("'go','add','remove'"),NULL),
		'fullscreen'=>	array(T_ZBX_INT, O_OPT,	P_SYS,		IN('0,1'),NULL),
//ajax
		'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
		'favref'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		NULL),
		'favid'=>		array(T_ZBX_INT, O_OPT, P_ACT,  NULL,			NULL),

		'state'=>		array(T_ZBX_INT, O_OPT, P_ACT,  NOT_EMPTY,		NULL),
		'action'=>		array(T_ZBX_STR, O_OPT, P_ACT, 	IN("'add','remove'"),NULL)
	);

	check_fields($fields);
?>
<?php

	if(isset($_REQUEST['favobj'])){
		if('filter' == $_REQUEST['favobj']){
			CProfile::update('web.charts.filter.state',$_REQUEST['state'], PROFILE_TYPE_INT);
		}
		if('hat' == $_REQUEST['favobj']){
			CProfile::update('web.charts.hats.'.$_REQUEST['favref'].'.state',$_REQUEST['state'], PROFILE_TYPE_INT);
		}

		if('timeline' == $_REQUEST['favobj']){
			if(isset($_REQUEST['graphid']) && isset($_REQUEST['period'])){
				navigation_bar_calc('web.graph',$_REQUEST['graphid'], true);
			}
		}

		if(str_in_array($_REQUEST['favobj'],array('itemid','graphid'))){
			$result = false;
			if('add' == $_REQUEST['action']){
				$result = add2favorites('web.favorite.graphids', $_REQUEST['favid'], $_REQUEST['favobj']);
				if($result){
					print('$("addrm_fav").title = "'.S_REMOVE_FROM.' '.S_FAVOURITES.'";'."\n");
					print('$("addrm_fav").onclick = function(){rm4favorites("graphid","'.$_REQUEST['favid'].'",0);}'."\n");
				}
			}
			else if('remove' == $_REQUEST['action']){
				$result = rm4favorites('web.favorite.graphids',$_REQUEST['favid'],$_REQUEST['favobj']);

				if($result){
					print('$("addrm_fav").title = "'.S_ADD_TO.' '.S_FAVOURITES.'";'."\n");
					print('$("addrm_fav").onclick = function(){ add2favorites("graphid","'.$_REQUEST['favid'].'");}'."\n");
				}
			}

			if((PAGE_TYPE_JS == $page['type']) && $result){
				print('switchElementsClass("addrm_fav","iconminus","iconplus");');
			}
		}
	}

	if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
		include_once('include/page_footer.php');
		exit();
	}
?>
<?php
	$options = array(
		'groups' => array('monitored_hosts' => 1, 'with_graphs' => 1),
		'hosts' => array('monitored_hosts' => 1, 'with_graphs' => 1),
		'groupid' => get_request('groupid', null),
		'hostid' => get_request('hostid', null),
		'graphs' => array('templated' => 0),
		'graphid' => get_request('graphid', null),
	);
	$pageFilter = new CPageFilter($options);

	$_REQUEST['graphid'] = $pageFilter->graphid;

	$effectiveperiod = navigation_bar_calc('web.graph',$_REQUEST['graphid']);

	$r_form = new CForm(null, 'get');
	$r_form->addVar('fullscreen', $_REQUEST['fullscreen']);

	$r_form->addItem(array(S_GROUP.SPACE, $pageFilter->getGroupsCB(true)));
	$r_form->addItem(array(SPACE.S_HOST.SPACE, $pageFilter->getHostsCB(true)));
	$r_form->addItem(array(SPACE.S_GRAPH.SPACE, $pageFilter->getGraphsCB(true)));

?>
<?php

	$icons = array();
	$charts_wdgt = new CWidget('hat_charts');
	$table = new CTableInfo('...','chart');
	$header = null;

	if($pageFilter->graphsSelected){
		$header = $pageFilter->graphs[$pageFilter->graphid];

		$scroll_div = new CDiv();
		$scroll_div->setAttribute('id','scrollbar_cntr');
		$charts_wdgt->addFlicker($scroll_div, CProfile::get('web.charts.filter.state',1));

		$graphDims = getGraphDims($_REQUEST['graphid']);

		if(($graphDims['graphtype'] == GRAPH_TYPE_PIE) || ($graphDims['graphtype'] == GRAPH_TYPE_EXPLODED)){
			$loadSBox = 0;
			$scrollWidthByImage = 0;
			$containerid = 'graph_cont1';
			$src = 'chart6.php?graphid='.$_REQUEST['graphid'];
		}
		else{
			$loadSBox = 1;
			$scrollWidthByImage = 1;
			$containerid = 'graph_cont1';
			$src = 'chart2.php?graphid='.$_REQUEST['graphid'];
		}

		$graph_cont = new CCol();
		$graph_cont->setAttribute('id', $containerid);
		$table->addRow($graph_cont);

		if(infavorites('web.favorite.graphids',$_REQUEST['graphid'],'graphid')){
			$icon = new CDiv(SPACE,'iconminus');
			$icon->setAttribute('title',S_REMOVE_FROM.' '.S_FAVOURITES);
			$icon->addAction('onclick',new CJSscript("javascript: rm4favorites('graphid','".$_REQUEST['graphid']."',0);"));
		}
		else{
			$icon = new CDiv(SPACE,'iconplus');
			$icon->setAttribute('title',S_ADD_TO.' '.S_FAVOURITES);
			$icon->addAction('onclick',new CJSscript("javascript: add2favorites('graphid','".$_REQUEST['graphid']."');"));
		}
		$icon->setAttribute('id','addrm_fav');

		$url = '?graphid='.$_REQUEST['graphid'].($_REQUEST['fullscreen']?'':'&fullscreen=1');
		$fs_icon = new CDiv(SPACE,'fullscreen');
		$fs_icon->setAttribute('title',$_REQUEST['fullscreen']?S_NORMAL.' '.S_VIEW:S_FULLSCREEN);
		$fs_icon->addAction('onclick',new CJSscript("javascript: document.location = '".$url."';"));

		$rst_icon = new CDiv(SPACE,'iconreset');
		$rst_icon->setAttribute('title',S_RESET);
		$rst_icon->addAction('onclick',new CJSscript("javascript: timeControl.objectReset('".$_REQUEST['graphid']."');"));

		array_push($icons, $icon, $rst_icon, $fs_icon);

// NAV BAR
		$timeline = array(
			'period' => $effectiveperiod,
			'starttime' => date('YmdHi', get_min_itemclock_by_graphid($_REQUEST['graphid']))
		);

		if(isset($_REQUEST['stime'])){
			$timeline['usertime'] = date('YmdHi', zbxDateToTime($_REQUEST['stime']) + $timeline['period']);
		}

		$dom_graph_id = 'graph';
		$objData = array(
			'id' => $_REQUEST['graphid'],
			'domid' => $dom_graph_id,
			'containerid' => $containerid,
			'src' => $src,
			'objDims' => $graphDims,
			'loadSBox' => $loadSBox,
			'loadImage' => 1,
			'loadScroll' => 1,
			'scrollWidthByImage' => $scrollWidthByImage,
			'dynamic' => 1
		);

		zbx_add_post_js('timeControl.addObject("'.$dom_graph_id.'",'.zbx_jsvalue($timeline).','.zbx_jsvalue($objData).');');
		zbx_add_post_js('timeControl.processObjects();');
	}

	$charts_wdgt->addPageHeader(S_GRAPHS_BIG, $icons);
	$charts_wdgt->addHeader($header, $r_form);
	$charts_wdgt->addItem(BR());
	$charts_wdgt->addItem($table);
	$charts_wdgt->show();


include_once('include/page_footer.php');
?>
