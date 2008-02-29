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

require_once "include/config.inc.php";
require_once "include/hosts.inc.php";
require_once "include/triggers.inc.php";
require_once "include/items.inc.php";
require_once "include/html.inc.php";
require_once "include/blocks.inc.php";

$page["title"] = "S_DASHBOARD";
$page["file"] = "dashboard.php";
$page['hist_arg'] = array();
$page['scripts'] = array('prototype.js','url.js','dashboard.js');

$page['type'] = detect_page_type(PAGE_TYPE_HTML);

include_once "include/page_header.php";

//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		'groupid'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,			NULL),
		'view_style'=>	array(T_ZBX_INT, O_OPT,	P_SYS,	IN('0,1'),		NULL),
		'type'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	IN('0,1'),		NULL),
		
		'output'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
		'jsscriptid'=>	array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
		
//ajax
		'favobj'=>		array(T_ZBX_STR, O_OPT, P_ACT,	NULL,			NULL),
		'favid'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj})'),
		'favcnt'=>		array(T_ZBX_INT, O_OPT,	null,	null,			null),

		'action'=>		array(T_ZBX_STR, O_OPT, P_ACT, 	IN("'add','remove'"),NULL),
		'action'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		NULL),
		'state'=>		array(T_ZBX_STR, O_OPT, P_ACT,  NOT_EMPTY,		'isset({favobj}) && ("hat"=={favobj})'),
	);

	check_fields($fields);
	
	$available_hosts = get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY, null, null, get_current_nodeid());
// ACTION /////////////////////////////////////////////////////////////////////////////

	if(isset($_REQUEST['favobj'])){
		if('hat' == $_REQUEST['favobj']){
//			echo 'alert("'.$_REQUEST['favid'].' : '.$_REQUEST['state'].'");';
			update_profile('web.dashboard.hats.'.$_REQUEST['favid'].'.state',$_REQUEST['state']);
		}
		if('refresh' == $_REQUEST['favobj']){
//			echo 'alert("'.$_REQUEST['favid'].' : '.$_REQUEST['state'].'");';
			switch($_REQUEST['favid']){
				case 'hat_syssum':
					$syssum = make_system_summary($available_hosts);
					$syssum->show();
					break;
				case 'hat_stszbx':
					$stszbx = make_status_of_zbx();
					$stszbx->Show();
					break;
				case 'hat_lastiss':
					$lastiss = make_latest_issues($available_hosts);
					$lastiss->Show();
					break;
				case 'hat_webovr':
					$webovr = make_webmon_overview();
					$webovr->Show();
					break;
			}
		}
		if('set_rf_rate' == $_REQUEST['favobj']){
			if(in_array($_REQUEST['favid'],array('hat_syssum','hat_stszbx','hat_lastiss','hat_webovr'))){
				update_profile('web.dahsboard.rf_rate.'.$_REQUEST['favid'],$_REQUEST['favcnt']);
				$_REQUEST['favcnt'] = get_profile('web.dahsboard.rf_rate.'.$_REQUEST['favid'],60);

				echo get_refresh_obj_script(
						array(
								'id'=>			$_REQUEST['favid'], 
								'interval'=>	$_REQUEST['favcnt']
						));
				
				$menu = array();
				$submenu = array();
				
				make_refresh_menu($_REQUEST['favid'],$_REQUEST['favcnt'],$menu,$submenu);
				
				echo 'dashboard_menu["menu_'.$_REQUEST['favid'].'"] = '.zbx_jsvalue($menu['menu_'.$_REQUEST['favid']]).';';
			}
		}

		if(in_array($_REQUEST['favobj'],array('simple_graph','graphs'))){
			$result = false;
			if('add' == $_REQUEST['action']){
				$result = add2favorites('web.favorite.graphids',$_REQUEST['favid'],$_REQUEST['favobj']);
			}
			else if('remove' == $_REQUEST['action']){
				$result = rm4favorites('web.favorite.graphids',$_REQUEST['favid'],get_request('favcnt',0),$_REQUEST['favobj']);
			}

			if((PAGE_TYPE_JS == $page['type']) && $result){
				$innerHTML = make_favorite_graphs($available_hosts);
				$innerHTML = $innerHTML->toString();
				print('$("hat_favgrph").update('.zbx_jsvalue($innerHTML).');');
				
				$menu = array();
				$submenu = array();
				print('dashboard_submenu["menu_graphs"] = '.zbx_jsvalue(make_graph_submenu()).';');
			}
		}
		
		if('sysmaps' == $_REQUEST['favobj']){
			$result = false;
			if('add' == $_REQUEST['action']){
				$result = add2favorites('web.favorite.sysmapids',$_REQUEST['favid'],$_REQUEST['favobj']);
			}
			else if('remove' == $_REQUEST['action']){
				$result = rm4favorites('web.favorite.sysmapids',$_REQUEST['favid'],get_request('favcnt',0),$_REQUEST['favobj']);
			}			
			
			if((PAGE_TYPE_JS == $page['type']) && $result){
				$innerHTML = make_favorite_maps();
				$innerHTML = $innerHTML->toString();
				echo '$("hat_favmap").update('.zbx_jsvalue($innerHTML).');';
				
				$menu = array();
				$submenu = array();
				echo 'dashboard_submenu["menu_sysmaps"] = '.zbx_jsvalue(make_sysmap_submenu()).';';
			}
		}
		if(in_array($_REQUEST['favobj'],array('screens','slides'))){
			$result = false;
			if('add' == $_REQUEST['action']){
				$perm = ('screens' == $_REQUEST['favobj'])?
					screen_accessiable($_REQUEST['favid'], PERM_READ_ONLY):
					slideshow_accessiable($_REQUEST['favid'], PERM_READ_ONLY);
					
				if($perm){
					$result = add2favorites('web.favorite.screenids',$_REQUEST['favid'],$_REQUEST['favobj']);
				}
			}
			else if('remove' == $_REQUEST['action']){
				$result = rm4favorites('web.favorite.screenids',$_REQUEST['favid'],get_request('favcnt',0),$_REQUEST['favobj']);
			}			
			
			if(PAGE_TYPE_JS == $page['type'] && $result){
				$innerHTML = make_favorite_screens();
				$innerHTML = $innerHTML->toString();
				echo '$("hat_favscr").update('.zbx_jsvalue($innerHTML).');';
				
				$menu = array();
				$submenu = array();
				echo 'dashboard_submenu["menu_screens"] = '.zbx_jsvalue(make_screen_submenu()).';';
			}
		}
	}	
	
	if(isset($_REQUEST['output'])){
		if('json2' == $_REQUEST['output']){
			echo 'try{'.
					'json.callBack("sdt"); '.
					'json.removeScript("'.$_REQUEST['jsscriptid'].'");'.
				' }catch(e){ '.
					'alert("Warning: incorrect JSON return.");'.
					'json.removeScript("'.$_REQUEST['jsscriptid'].'");'.
				' }';
		}
	}
	


	if((PAGE_TYPE_JS == $page['type']) || (PAGE_TYPE_HTML_BLOCK == $page['type'])){
		exit();
	}

//	validate_group(PERM_READ_ONLY,array("allow_all_hosts","monitored_hosts","with_monitored_items"));
//	$time = new CSpan(date("[H:i:s]",time()));
//	$time->AddOption('id','refreshed');
	show_table_header(array(S_DASHBOARD_BIG,SPACE),SPACE);

	$left_tab = new CTable();
	$left_tab->SetCellPadding(5);
	$left_tab->SetCellSpacing(5);

	$left_tab->AddOption('border',0);
	
	$menu = array();
	$submenu = array();

// js menu arrays	
	make_graph_menu($menu,$submenu);
	make_sysmap_menu($menu,$submenu);
	make_screen_menu($menu,$submenu);
	
	make_refresh_menu('hat_syssum',get_profile('web.dahsboard.rf_rate.hat_syssum',60),$menu,$submenu);
	make_refresh_menu('hat_stszbx',get_profile('web.dahsboard.rf_rate.hat_stszbx',60),$menu,$submenu);
	make_refresh_menu('hat_lastiss',get_profile('web.dahsboard.rf_rate.hat_lastiss',60),$menu,$submenu);
	make_refresh_menu('hat_webovr',get_profile('web.dahsboard.rf_rate.hat_webovr',60),$menu,$submenu);
	
	insert_js('var dashboard_menu='.zbx_jsvalue($menu)."\n".
			 'var dashboard_submenu='.zbx_jsvalue($submenu)."\n"
		);
	
// --------------

	$graph_menu = new CDiv(SPACE,'iconmenu');
	$graph_menu->AddAction('onclick','javascript: create_menu(event,"graphs");');
	
	$left_tab->AddRow(create_hat(
			S_FAVORITE.SPACE.S_GRAPHS,
			make_favorite_graphs($available_hosts),
			array($graph_menu),
			'hat_favgrph',
			get_profile('web.dashboard.hats.hat_favgrph.state',1)
		));
		
	$sysmap_menu = new CDiv(SPACE,'iconmenu');
	$sysmap_menu->AddAction('onclick','javascript: create_menu(event,"sysmaps");');
		
	$left_tab->AddRow(create_hat(
			S_FAVORITE.SPACE.S_MAPS,
			make_favorite_maps(),
			array($sysmap_menu),
			'hat_favmap',
			get_profile('web.dashboard.hats.hat_favmap.state',1)
		));
		
	$screen_menu = new CDiv(SPACE,'iconmenu');
	$screen_menu->AddAction('onclick','javascript: create_menu(event,"screens");');

	$left_tab->AddRow(create_hat(
			S_FAVORITE.SPACE.S_SCREENS,
			make_favorite_screens(),
			array($screen_menu),
			'hat_favscr',
			get_profile('web.dashboard.hats.hat_favscr.state',1)
		));
	$left_tab->AddRow(SPACE);
	
	$right_tab = new CTable();
	$right_tab->SetCellPadding(5);
	$right_tab->SetCellSpacing(5);

	$right_tab->AddOption('border',0);

// Refresh tab

	$refresh_tab = array(
		array('id' => 'hat_syssum',
				'interval' => get_profile('web.dahsboard.rf_rate.hat_syssum',120)
			),
		array('id' => 'hat_stszbx',
				'interval' => get_profile('web.dahsboard.rf_rate.hat_stszbx',120)
			),
		array('id' => 'hat_lastiss',
				'interval'  => get_profile('web.dahsboard.rf_rate.hat_lastiss',60)
			),
		array('id' => 'hat_webovr',
				'interval'  => get_profile('web.dahsboard.rf_rate.hat_webovr',60)
			)
/*		array('id' => 'hat_custom',
				'interval'  =>	get_profile('web.dahsboard.rf_rate.hat_custom',60),
				'url'=>	'charts.php?groupid=4&hostid=10017&graphid=5&output=html&fullscreen=1'
			)*/
	);
	add_refresh_objects($refresh_tab);

	$refresh_menu = new CDiv(SPACE,'iconmenu');
	$refresh_menu->AddAction('onclick','javascript: create_menu(event,"hat_syssum");');

	$right_tab->AddRow(create_hat(
			S_SYSTEM_STATUS,
			null,//make_system_summary($available_hosts),
			array($refresh_menu),
			'hat_syssum',
			get_profile('web.dashboard.hats.hat_syssum.state',1)
		));

	$refresh_menu = new CDiv(SPACE,'iconmenu');
	$refresh_menu->AddAction('onclick','javascript: create_menu(event,"hat_stszbx");');

		
	$right_tab->AddRow(create_hat(
			S_STATUS_OF_ZABBIX,
			null,//make_status_of_zbx(),
			array($refresh_menu),
			'hat_stszbx',
			get_profile('web.dashboard.hats.hat_stszbx.state',1)
		));
		
	$refresh_menu = new CDiv(SPACE,'iconmenu');
	$refresh_menu->AddAction('onclick','javascript: create_menu(event,"hat_lastiss");');
		
	$right_tab->AddRow(create_hat(S_LATEST_ISSUES,
			null,//make_latest_issues($available_hosts),
			array($refresh_menu),
			'hat_lastiss',
			get_profile('web.dashboard.hats.hat_lastiss.state',1)
		));
		
	$refresh_menu = new CDiv(SPACE,'iconmenu');
	$refresh_menu->AddAction('onclick','javascript: create_menu(event,"hat_webovr");');

	$right_tab->AddRow(create_hat(
			S_WEB_MONITORING,
			null,//make_webmon_overview(),
			array($refresh_menu),
			'hat_webovr',
			get_profile('web.dashboard.hats.hat_webovr.state',1)
		));
/*		
	$right_tab->AddRow(create_hat(
			S_GRAPH,
			null,//make_webmon_overview(),
			null,
			'hat_custom',
			get_profile('web.dashboard.hats.hat_custom.state',1)
		));
*/
	$td_l = new CCol($left_tab);
	$td_l->AddOption('valign','top');
	
	$td_r = new CCol($right_tab);
	$td_r->AddOption('valign','top');

	$outer_table = new CTable();
	$outer_table->AddOption('border',0);
	$outer_table->SetCellPadding(1);
	$outer_table->SetCellSpacing(1);
	$outer_table->AddRow(array($td_l,$td_r));
	
	$outer_table->Show();

	$fav_form = new CForm();
	$fav_form->AddOption('name','fav_form');
	$fav_form->AddOption('id','fav_form');
	$fav_form->AddOption('style','display: inline; margin: 0px;');
	$fav_form->AddVar('favobj','');
	$fav_form->AddVar('favid','');
	$fav_form->AddVar('resource','');
	$fav_form->Show();

	$jsmenu = new CPUMenu(null,170);
	$jsmenu->InsertJavaScript();
		
//	$link = new CLink('Click Me','javascript: callJSON();','highlight');
//	$link->Show();
?>
<?php

include_once "include/page_footer.php";

?>