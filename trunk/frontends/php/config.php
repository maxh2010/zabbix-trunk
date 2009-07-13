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
	require_once('include/config.inc.php');
	require_once('include/images.inc.php');
	require_once('include/regexp.inc.php');
	require_once('include/forms.inc.php');


	$page['title'] = "S_CONFIGURATION_OF_ZABBIX";
	$page['file'] = 'config.php';
	$page['hist_arg'] = array('config');

	include_once('include/page_header.php');

?>
<?php
	$fields=array(
//		VAR				TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION

		'config'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN('0,3,5,6,7,8,9,10'),	NULL),

// other form
		'alert_history'=>		array(T_ZBX_INT, O_NO,	NULL,	BETWEEN(0,65535),	'isset({config})&&({config}==0)&&isset({save})'),
		'event_history'=>		array(T_ZBX_INT, O_NO,	NULL,	BETWEEN(0,65535),	'isset({config})&&({config}==0)&&isset({save})'),
		'work_period'=>			array(T_ZBX_STR, O_NO,	NULL,	NULL,			'isset({config})&&({config}==7)&&isset({save})'),
		'refresh_unsupported'=>		array(T_ZBX_INT, O_NO,	NULL,	BETWEEN(0,65535),	'isset({config})&&({config}==5)&&isset({save})'),
		'alert_usrgrpid'=>		array(T_ZBX_INT, O_NO,	NULL,	DB_ID,			'isset({config})&&({config}==5)&&isset({save})'),
		'discovery_groupid'=>		array(T_ZBX_INT, O_NO,	NULL,	DB_ID,			'isset({config})&&({config}==5)&&isset({save})'),

// image form
		'imageid'=>			array(T_ZBX_INT, O_NO,	P_SYS,	DB_ID,			'isset({config})&&({config}==3)&&(isset({form})&&({form}=="update"))'),
		'name'=>			array(T_ZBX_STR, O_NO,	NULL,	NOT_EMPTY,		'isset({config})&&({config}==3)&&isset({save})'),
		'imagetype'=>			array(T_ZBX_INT, O_OPT,	NULL,	IN('1,2'),		'isset({config})&&({config}==3)&&(isset({save}))'),

//value mapping
		'valuemapid'=>			array(T_ZBX_INT, O_NO,	P_SYS,	DB_ID,			'isset({config})&&({config}==6)&&(isset({form})&&({form}=="update"))'),
		'mapname'=>			array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY, 		'isset({config})&&({config}==6)&&isset({save})'),
		'valuemap'=>			array(T_ZBX_STR, O_OPT,	NULL,	NULL,	NULL),
		'rem_value'=>			array(T_ZBX_INT, O_OPT,	NULL,	BETWEEN(0,65535), NULL),
		'add_value'=>			array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY, 'isset({add_map})'),
		'add_newvalue'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY, 'isset({add_map})'),

/* actions */
		'add_map'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'del_map'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'save'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
/* GUI */
		'event_ack_enable'=>		array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	IN('0,1'),		'isset({config})&&({config}==8)&&isset({save})'),
		'event_expire'=> 			array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,65535),	'isset({config})&&({config}==8)&&isset({save})'),
		'event_show_max'=> 			array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,65535),	'isset({config})&&({config}==8)&&isset({save})'),
		'dropdown_first_entry'=>	array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	IN('0,1,2'),		'isset({config})&&({config}==8)&&isset({save})'),
		'dropdown_first_remember'=>	array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	IN('0,1'),	NULL),
		'max_in_table' => 				array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,65535),	'isset({config})&&({config}==8)&&isset({save})'),

/* Themes */
		'default_theme'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,			'isset({config})&&({config}==9)&&isset({save})'),

// regexp
		'regexpids'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,		NULL),
		'regexpid'=>			array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID,			'isset({config})&&({config}==10)&&(isset({form})&&({form}=="update"))'),
		'rename'=>			array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,		'isset({config})&&({config}==10)&&isset({save})'),
		'test_string'=>			array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,		'isset({config})&&({config}==10)&&isset({save})'),
		'delete_regexp'=>		array(T_ZBX_STR, O_OPT,	NULL,	NULL,		null),

		'g_expressionid'=>		array(T_ZBX_INT, O_OPT,	NULL,	DB_ID,		null),
		'expressions'=>			array(T_ZBX_STR, O_OPT,	NULL,	NULL,		'isset({config})&&({config}==10)&&isset({save})'),
		'new_expression'=>		array(T_ZBX_STR, O_OPT,	NULL,	NULL,		null),
		'cancel_new_expression'=>	array(T_ZBX_STR, O_OPT,	NULL,	NULL,		null),

		'clone'=>			array(T_ZBX_STR, O_OPT,	NULL,	NULL,		null),
		'add_expression'=>		array(T_ZBX_STR, O_OPT,	NULL,	NULL,		null),
		'edit_expressionid'=>		array(T_ZBX_STR, O_OPT,	NULL,	NULL,		null),
		'delete_expression'=>		array(T_ZBX_STR, O_OPT,	NULL,	NULL,		null),

/* other */
		'form'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)
	);
?>
<?php
	$_REQUEST['config'] = get_request('config',get_profile('web.config.config',0));

	check_fields($fields);

	update_profile('web.config.config',$_REQUEST['config'],PROFILE_TYPE_INT);

	$orig_config = select_config();

	$result = 0;
	if($_REQUEST['config']==3){
/* IMAGES ACTIONS */
		if(isset($_REQUEST['save'])){
			$file = isset($_FILES['image']) && $_FILES['image']['name'] != '' ? $_FILES['image'] : NULL;
			if(isset($_REQUEST['imageid'])){
	/* UPDATE */
				$result=update_image($_REQUEST['imageid'],$_REQUEST['name'],
					$_REQUEST['imagetype'],$file);

				$msg_ok = S_IMAGE_UPDATED;
				$msg_fail = S_CANNOT_UPDATE_IMAGE;
				$audit_action = 'Image ['.$_REQUEST['name'].'] updated';
			}
			else {
	/* ADD */
				if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY))){
					access_deny();
				}
				$result=add_image($_REQUEST['name'],$_REQUEST['imagetype'],$file);

				$msg_ok = S_IMAGE_ADDED;
				$msg_fail = S_CANNOT_ADD_IMAGE;
				$audit_action = 'Image ['.$_REQUEST['name'].'] added';
			}

			show_messages($result, $msg_ok, $msg_fail);
			if($result){
				add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_IMAGE,$audit_action);
				unset($_REQUEST['form']);
			}
		}
		else if(isset($_REQUEST['delete'])&&isset($_REQUEST['imageid'])) {
	/* DELETE */
			$image = get_image_by_imageid($_REQUEST['imageid']);

			$result=delete_image($_REQUEST['imageid']);
			show_messages($result, S_IMAGE_DELETED, S_CANNOT_DELETE_IMAGE);

			if($result){
				add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_IMAGE,'Image ['.$image['name'].'] deleted');
				unset($_REQUEST['form']);
			}

			unset($image, $_REQUEST['imageid']);
		}
	}
	else if(isset($_REQUEST['save']) && ($_REQUEST['config']==8)){ // GUI
		if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
			access_deny();

		$configs = array(
			'default_theme' => get_request('default_theme'),
			'event_ack_enable' => get_request('event_ack_enable'),
			'event_expire' => get_request('event_expire'),
			'event_show_max' => get_request('event_show_max'),
			'dropdown_first_entry' => get_request('dropdown_first_entry'),
			'dropdown_first_remember' => (is_null(get_request('dropdown_first_remember')) ? 0 : 1),
			'max_in_table' => get_request('max_in_table'),
		);

		$result = update_config($configs);

		show_messages($result, S_CONFIGURATION_UPDATED, S_CONFIGURATION_WAS_NOT_UPDATED);

		if($result){
			$msg = array();
			if(!is_null($val = get_request('default_theme')))
				$msg[] = S_DEFAULT_THEME.' ['.$val.']';
			if(!is_null($val = get_request('event_ack_enable')))
				$msg[] = S_EVENT_ACKNOWLEDGES.' ['.($val?(S_DISABLED):(S_ENABLED)).']';
			if(!is_null($val = get_request('event_expire')))
				$msg[] = S_SHOW_EVENTS_NOT_OLDER.SPACE.'('.S_DAYS.')'.' ['.$val.']';
			if(!is_null($val = get_request('event_show_max')))
				$msg[] = S_SHOW_EVENTS_MAX.' ['.$val.']';
			if(!is_null($val = get_request('dropdown_first_entry')))
				$msg[] = S_DROPDOWN_FIRST_ENTRY.' ['.$val.']';
			if(!is_null($val = get_request('dropdown_first_remember')))
				$msg[] = S_DROPDOWN_REMEMBER_SELECTED.' ['.$val.']';
			if(!is_null($val = get_request('max_in_table')))
				$msg[] = S_MAX_IN_TABLE.' ['.$val.']';

			add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_ZABBIX_CONFIG,implode('; ',$msg));
		}
	}
	else if(isset($_REQUEST['save'])&&uint_in_array($_REQUEST['config'],array(0,5,7))){

		if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
			access_deny();

/* OTHER ACTIONS */
		$configs = array(
				'event_history' => get_request('event_history'),
				'alert_history' => get_request('alert_history'),
				'refresh_unsupported' => get_request('refresh_unsupported'),
				'work_period' => get_request('work_period'),
				'alert_usrgrpid' => get_request('alert_usrgrpid'),
				'discovery_groupid' => get_request('discovery_groupid'),
			);
		$result=update_config($configs);

		show_messages($result, S_CONFIGURATION_UPDATED, S_CONFIGURATION_WAS_NOT_UPDATED);
		if($result){
			$msg = array();
			if(!is_null($val = get_request('event_history')))
				$msg[] = S_DO_NOT_KEEP_EVENTS_OLDER_THAN.' ['.$val.']';
			if(!is_null($val = get_request('alert_history')))
				$msg[] = S_DO_NOT_KEEP_ACTIONS_OLDER_THAN.' ['.$val.']';
			if(!is_null($val = get_request('refresh_unsupported')))
				$msg[] = S_REFRESH_UNSUPPORTED_ITEMS.' ['.$val.']';
			if(!is_null($val = get_request('work_period')))
				$msg[] = S_WORKING_TIME.' ['.$val.']';
			if(!is_null($val = get_request('discovery_groupid'))){
				$val = CHostGroup::get(array('groupids' => $val, 'editable' => 1));
				if(!empty($val)){
					$val = array_pop($val);
					$msg[] = S_GROUP_FOR_DISCOVERED_HOSTS.' ['.$val['name'].']';

					setHostGroupInternal($orig_config['discovery_groupid'], ZBX_NOT_INTERNAL_GROUP);
					setHostGroupInternal($val['groupid'], ZBX_INTERNAL_GROUP);
				}
			}
			if(!is_null($val = get_request('alert_usrgrpid'))){
				if(0 == $val) {
					$val = S_NONE;
				}
				else{
					$val = DBfetch(DBselect('SELECT name FROM usrgrp WHERE usrgrpid='.$val));
					$val = $val['name'];
				}

				$msg[] = S_USER_GROUP_FOR_DATABASE_DOWN_MESSAGE.' ['.$val.']';
			}

			add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_ZABBIX_CONFIG,implode('; ',$msg));
		}
	}
// VALUE MAPS
	else if($_REQUEST['config']==6){
		$_REQUEST['valuemap'] = get_request('valuemap',array());
		if(isset($_REQUEST['add_map'])){
			$added = 0;
			$cnt = count($_REQUEST['valuemap']);
			for($i=0; $i < $cnt; $i++){
				if($_REQUEST['valuemap'][$i]['value'] != $_REQUEST['add_value'])	continue;
				$_REQUEST['valuemap'][$i]['newvalue'] = $_REQUEST['add_newvalue'];
				$added = 1;
				break;
			}

			if($added == 0){
				if(!ctype_digit($_REQUEST['add_value']) || !is_string($_REQUEST['add_newvalue'])){
					info('Value maps are used to create a mapping between numeric values and string representations');
					show_messages(false,null,S_CANNNOT_ADD_VALUE_MAP);
				}
				else{
					array_push($_REQUEST['valuemap'],array(
						'value'		=> $_REQUEST['add_value'],
						'newvalue'	=> $_REQUEST['add_newvalue']));
				}
			}
		}
		else if(isset($_REQUEST['del_map'])&&isset($_REQUEST['rem_value'])){

			$_REQUEST['valuemap'] = get_request('valuemap',array());
			foreach($_REQUEST['rem_value'] as $val)
				unset($_REQUEST['valuemap'][$val]);
		}
		else if(isset($_REQUEST['save'])){

			$mapping = get_request('valuemap',array());
			if(isset($_REQUEST['valuemapid'])){
				$result		= update_valuemap($_REQUEST['valuemapid'],$_REQUEST['mapname'], $mapping);
				$audit_action	= AUDIT_ACTION_UPDATE;
				$msg_ok		= S_VALUE_MAP_UPDATED;
				$msg_fail	= S_CANNNOT_UPDATE_VALUE_MAP;
				$valuemapid	= $_REQUEST['valuemapid'];
			}
			else{
				if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY))){
					access_deny();
				}
				$result		= add_valuemap($_REQUEST['mapname'], $mapping);
				$audit_action	= AUDIT_ACTION_ADD;
				$msg_ok		= S_VALUE_MAP_ADDED;
				$msg_fail	= S_CANNNOT_ADD_VALUE_MAP;
				$valuemapid	= $result;
			}

			if($result){
				add_audit($audit_action, AUDIT_RESOURCE_VALUE_MAP,
					S_VALUE_MAP.' ['.$_REQUEST['mapname'].'] ['.$valuemapid.']');
				unset($_REQUEST['form']);
			}
			show_messages($result,$msg_ok, $msg_fail);
		}
		else if(isset($_REQUEST['delete']) && isset($_REQUEST['valuemapid'])){
			$result = false;

			$sql = 'SELECT * FROM valuemaps WHERE '.DBin_node('valuemapid').' AND valuemapid='.$_REQUEST['valuemapid'];
			if($map_data = DBfetch(DBselect($sql))){
				$result = delete_valuemap($_REQUEST['valuemapid']);
			}

			if($result){
				add_audit(AUDIT_ACTION_DELETE, AUDIT_RESOURCE_VALUE_MAP,
					S_VALUE_MAP.' ['.$map_data['name'].'] ['.$map_data['valuemapid'].']');
				unset($_REQUEST['form']);
			}
			show_messages($result, S_VALUE_MAP_DELETED, S_CANNNOT_DELETE_VALUE_MAP);
		}
	}
	else if(isset($_REQUEST['save']) && ($_REQUEST['config']==9)){
		if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
			access_deny();

/* OTHER ACTIONS */
		$configs = array(
				'default_theme' => get_request('default_theme')
			);
		$result=update_config($configs);

		show_messages($result, S_CONFIGURATION_UPDATED, S_CONFIGURATION_WAS_NOT_UPDATED);

		if($result){
			$msg = S_DEFAULT_THEME.' ['.get_request('default_theme').']';
			add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_ZABBIX_CONFIG,$msg);
		}
	}
	else if($_REQUEST['config'] == 10){
		if(inarr_isset(array('clone','regexpid'))){
			unset($_REQUEST['regexpid']);
			$_REQUEST['form'] = 'clone';
		}
		else if(isset($_REQUEST['cancel_new_expression'])){
			unset($_REQUEST['new_expression']);
		}
		else if(isset($_REQUEST['save'])){
			if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY)))
				access_deny();

			$regexp = array('name' => $_REQUEST['rename'],
						'test_string' => $_REQUEST['test_string']
					);

			DBstart();
			if(isset($_REQUEST['regexpid'])){
				$regexpid=$_REQUEST['regexpid'];

				delete_expressions_by_regexpid($_REQUEST['regexpid']);
				$result = update_regexp($regexpid, $regexp);

				$msg1 = S_REGULAR_EXPRESSION_UPDATED;
				$msg2 = S_CANNOT_UPDATE_REGULAR_EXPRESSION;
			}
			else {
				$result = $regexpid = add_regexp($regexp);

				$msg1 = S_REGULAR_EXPRESSION_ADDED;
				$msg2 = S_CANNOT_ADD_REGULAR_EXPRESSION;
			}

			if($result){
				$expressions = get_request('expressions', array());
				foreach($expressions as $id => $expression){
					$expressionid = add_expression($regexpid,$expression);
				}
			}

			$result = Dbend($result);

			show_messages($result,$msg1,$msg2);

			if($result){ // result - OK
				add_audit(!isset($_REQUEST['regexpid'])?AUDIT_ACTION_ADD:AUDIT_ACTION_UPDATE,
					AUDIT_RESOURCE_REGEXP,
					S_NAME.': '.$_REQUEST['rename']);

				unset($_REQUEST['form']);
			}
		}
		else if(isset($_REQUEST['delete'])){
			if(!count(get_accessible_nodes_by_user($USER_DETAILS,PERM_READ_WRITE,PERM_RES_IDS_ARRAY))) access_deny();

			$regexpids = get_request('regexpid', array());
			if(isset($_REQUEST['regexpids']))
				$regexpids = $_REQUEST['regexpids'];

			zbx_value2array($regexpids);

			$regexps = array();
			foreach($regexpids as $id => $regexpid){
				$regexps[$regexpid] = get_regexp_by_regexpid($regexpid);
			}

			DBstart();
			$result = delete_regexp($regexpids);
			$result = Dbend($result);

			show_messages($result,S_REGULAR_EXPRESSION_DELETED,S_CANNOT_DELETE_REGULAR_EXPRESSION);
			if($result){
				foreach($regexps as $regexpid => $regexp){
					add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_REGEXP,'Id ['.$regexpid.'] '.S_NAME.' ['.$regexp['name'].']');
				}

				unset($_REQUEST['form']);
				unset($_REQUEST['regexpid']);
			}
		}
		else if(inarr_isset(array('add_expression','new_expression'))){
			$new_expression = $_REQUEST['new_expression'];

			if(!isset($new_expression['case_sensitive']))		$new_expression['case_sensitive'] = 0;

			$result = false;
			if(zbx_empty($new_expression['expression'])) {
				info(S_INCORRECT_EXPRESSION);
			}
			else{
				$result = true;
			}

			if($result){
				if(!isset($new_expression['id'])){
					if(!isset($_REQUEST['expressions'])) $_REQUEST['expressions'] = array();

					if(!str_in_array($new_expression,$_REQUEST['expressions']))
						array_push($_REQUEST['expressions'],$new_expression);
				}
				else{
					$id = $new_expression['id'];
					unset($new_expression['id']);
					$_REQUEST['expressions'][$id] = $new_expression;
				}

				unset($_REQUEST['new_expression']);
			}
		}
		else if(inarr_isset(array('delete_expression','g_expressionid'))){
			$_REQUEST['expressions'] = get_request('expressions',array());
			foreach($_REQUEST['g_expressionid'] as $val){
				unset($_REQUEST['expressions'][$val]);
			}
		}
		else if(inarr_isset(array('edit_expressionid'))){
			$_REQUEST['edit_expressionid'] = array_keys($_REQUEST['edit_expressionid']);
			$edit_expressionid = $_REQUEST['edit_expressionid'] = array_pop($_REQUEST['edit_expressionid']);
			$_REQUEST['expressions'] = get_request('expressions',array());

			if(isset($_REQUEST['expressions'][$edit_expressionid])){
				$_REQUEST['new_expression'] = $_REQUEST['expressions'][$edit_expressionid];
				$_REQUEST['new_expression']['id'] = $edit_expressionid;
			}
		}
	}

?>

<?php

	$form = new CForm('config.php');
	$form->SetMethod('get');
	$cmbConfig = new CCombobox('config',$_REQUEST['config'],'submit()');
//	$cmbConfig->AddItem(4,S_AUTOREGISTRATION);
//	$cmbConfig->AddItem(2,S_ESCALATION_RULES);
	$cmbConfig->AddItem(8,S_GUI);
	$cmbConfig->AddItem(0,S_HOUSEKEEPER);
	$cmbConfig->AddItem(3,S_IMAGES);
	$cmbConfig->AddItem(10,S_REGULAR_EXPRESSIONS);
//	$cmbConfig->AddItem(9,S_THEMES);
	$cmbConfig->AddItem(6,S_VALUE_MAPPING);
	$cmbConfig->AddItem(7,S_WORKING_TIME);
	$cmbConfig->AddItem(5,S_OTHER);
	$form->AddItem($cmbConfig);
	switch($_REQUEST['config']){
		case 3:
			$form->AddItem(SPACE.'|'.SPACE);
			$form->AddItem(new CButton('form',S_CREATE_IMAGE));
			break;
		case 6:
			$form->AddItem(SPACE.'|'.SPACE);
			$form->AddItem(new CButton('form',S_CREATE_VALUE_MAP));
			break;
		case 10:
			if(!isset($_REQUEST['form'])){
				$form->addItem(SPACE.'|'.SPACE);
				$form->addItem(new CButton('form',S_NEW_REGULAR_EXPRESSION));
			}
			break;
	}

	show_table_header(S_CONFIGURATION_OF_ZABBIX_BIG, $form);
?>
<?php
	if(isset($_REQUEST['config'])) {
		$config = select_config(false);
	}

/////////////////////////////////
//  config = 0 // Housekeeper  //
/////////////////////////////////
	if($_REQUEST['config']==0){ //housekeeper
		echo SBR;

		$frmHouseKeep = new CFormTable(S_HOUSEKEEPER, "config.php");
		$frmHouseKeep->SetHelp("web.config.housekeeper.php");
		$frmHouseKeep->addVar("config", get_request("config", 0));

		$frmHouseKeep->addRow(S_DO_NOT_KEEP_ACTIONS_OLDER_THAN,
			new CNumericBox("alert_history", $config["alert_history"], 5));
		$frmHouseKeep->addRow(S_DO_NOT_KEEP_EVENTS_OLDER_THAN,
			new CNumericBox("event_history", $config["event_history"], 5));

		$frmHouseKeep->addItemToBottomRow(new CButton("save", S_SAVE));
		$frmHouseKeep->Show();
	}
//////////////////////////////////////
//  config = 5 // Other Parameters  //
//////////////////////////////////////
	else if($_REQUEST['config']==5){ // Other parameters
		echo SBR;

		$frmOther = new CFormTable(S_OTHER_PARAMETERS, 'config.php');
		$frmOther->SetHelp('web.config.other.php');
		$frmOther->addVar('config',get_request('config', 5));

		$frmOther->addRow(S_REFRESH_UNSUPPORTED_ITEMS,
			new CNumericBox('refresh_unsupported', $config['refresh_unsupported'], 5));


		$cmbGrp = new CComboBox('discovery_groupid', $config['discovery_groupid']);
		$groups = CHostGroup::get(array('order'=>'name', 'editable' => 1));
		foreach($groups as $groupid => $group){
			$cmbGrp->addItem($groupid, $group['name']);
		}
		$frmOther->addRow(S_GROUP_FOR_DISCOVERED_HOSTS, $cmbGrp);


		$cmbUsrGrp = new CComboBox('alert_usrgrpid', $config['alert_usrgrpid']);
		$cmbUsrGrp->addItem(0, S_NONE);
		$result=DBselect('SELECT usrgrpid,name FROM usrgrp'.
				' WHERE '.DBin_node('usrgrpid').
				' order by name');
		while($row = DBfetch($result))
			$cmbUsrGrp->addItem(
					$row['usrgrpid'],
					get_node_name_by_elid($row['usrgrpid']).$row['name']
					);
		$frmOther->addRow(S_USER_GROUP_FOR_DATABASE_DOWN_MESSAGE, $cmbUsrGrp);

		$frmOther->addItemToBottomRow(new CButton('save', S_SAVE));
		$frmOther->show();
	}
/////////////////////////////////
//  config = 7 // Work Period  //
/////////////////////////////////
	else if($_REQUEST['config']==7){ //work period
		echo SBR;

		$frmHouseKeep = new CFormTable(S_WORKING_TIME, "config.php");
		$frmHouseKeep->SetHelp("web.config.workperiod.php");
		$frmHouseKeep->addVar("config",get_request("config", 7));

		$frmHouseKeep->addRow(S_WORKING_TIME,
			new CTextBox("work_period",$config["work_period"], 35));

		$frmHouseKeep->addItemToBottomRow(new CButton("save", S_SAVE));
		$frmHouseKeep->Show();

	}
/////////////////////////
//  config = 8 // GUI  //
/////////////////////////
	else if($_REQUEST['config']==8){ // GUI
		echo SBR;

		$frmGUI = new CFormTable(S_GUI, "config.php");
		$frmGUI->addVar("config",get_request("config",8));

		$combo_theme = new CComboBox('default_theme',$config['default_theme']);
		$combo_theme->addItem('css_ob.css',S_ORIGINAL_BLUE);
		$combo_theme->addItem('css_bb.css',S_BLACK_AND_BLUE);

		$text_max_in_table = new CTextBox('max_in_table', $config['max_in_table'], 5);
		
		$exp_select = new CComboBox('event_ack_enable');
		$exp_select->addItem(EVENT_ACK_ENABLED,S_ENABLED,$config['event_ack_enable']?'yes':'no');
		$exp_select->addItem(EVENT_ACK_DISABLED,S_DISABLED,$config['event_ack_enable']?'no':'yes');

		$combo_dd_first_entry = new CComboBox('dropdown_first_entry');
		$combo_dd_first_entry->addItem(ZBX_DROPDOWN_FIRST_NONE, S_NONE, ($config['dropdown_first_entry'] == ZBX_DROPDOWN_FIRST_NONE)?'yes':'no');
		$combo_dd_first_entry->addItem(ZBX_DROPDOWN_FIRST_ALL, S_ALL_S, ($config['dropdown_first_entry'] == ZBX_DROPDOWN_FIRST_ALL)?'yes':'no');
		$combo_dd_first_entry->addItem(ZBX_DROPDOWN_FIRST_ZBX162, S_ZBX162_MODE, ($config['dropdown_first_entry'] == ZBX_DROPDOWN_FIRST_ZBX162)?'yes':'no');

		$check_dd_first_remember = new CCheckBox('dropdown_first_remember', $config['dropdown_first_remember'], null, 1);

		$frmGUI->addRow(S_DEFAULT_THEME, $combo_theme);
		$frmGUI->addRow(S_DROPDOWN_FIRST_ENTRY, array(
			$combo_dd_first_entry,
			$check_dd_first_remember,
			S_DROPDOWN_REMEMBER_SELECTED
			));
		$frmGUI->addRow(S_MAX_IN_TABLE, $text_max_in_table);
		$frmGUI->addRow(S_EVENT_ACKNOWLEDGES,$exp_select);
		$frmGUI->addRow(S_SHOW_EVENTS_NOT_OLDER.SPACE.'('.S_DAYS.')',
			new CTextBox('event_expire',$config['event_expire'],5));
		$frmGUI->addRow(S_MAX_COUNT_OF_EVENTS,
			new CTextBox('event_show_max',$config['event_show_max'],5));
		$frmGUI->addItemToBottomRow(new CButton("save",S_SAVE));
		$frmGUI->Show();
	}
////////////////////////////
//  config = 3 // Images  //
////////////////////////////
	elseif($_REQUEST["config"]==3){ // Images
		echo SBR;
		if(isset($_REQUEST["form"])){
			$frmImages = new CFormTable(S_IMAGE, 'config.php', 'post', 'multipart/form-data');
		$frmImages->SetHelp('web.config.images.php');
		$frmImages->addVar('config', get_request('config',3));

		if(isset($_REQUEST['imageid'])){
			$result=DBselect('SELECT imageid,imagetype,name '.
						' FROM images '.
						' WHERE imageid='.$_REQUEST['imageid']);

			$row=DBfetch($result);
			$frmImages->SetTitle(S_IMAGE.' "'.$row['name'].'"');
			$frmImages->addVar('imageid', $_REQUEST['imageid']);
		}

		if(isset($_REQUEST['imageid']) && !isset($_REQUEST['form_refresh'])){
			$name		= $row['name'];
			$imagetype	= $row['imagetype'];
			$imageid	= $row['imageid'];
		}
		else{
			$name		= get_request('name','');
			$imagetype	= get_request('imagetype',1);
			$imageid	= get_request('imageid',0);
		}

		$frmImages->addRow(S_NAME,new CTextBox('name',$name,64));

		$cmbImg = new CComboBox('imagetype',$imagetype);
		$cmbImg->addItem(IMAGE_TYPE_ICON,S_ICON);
		$cmbImg->addItem(IMAGE_TYPE_BACKGROUND,S_BACKGROUND);

		$frmImages->addRow(S_TYPE,$cmbImg);

		$frmImages->addRow(S_UPLOAD,new CFile('image'));

		if($imageid > 0){
			$frmImages->addRow(S_IMAGE,new CLink(
				new CImg('image.php?width=640&height=480&imageid='.$imageid,'no image',null),'image.php?imageid='.$row['imageid']));
		}

		$frmImages->addItemToBottomRow(new CButton('save',S_SAVE));
		if(isset($_REQUEST['imageid'])){
			$frmImages->addItemToBottomRow(SPACE);
			$frmImages->addItemToBottomRow(new CButtonDelete(S_DELETE_SELECTED_IMAGE,
				url_param('form').url_param('config').url_param('imageid')));
		}

		$frmImages->addItemToBottomRow(SPACE);
		$frmImages->addItemToBottomRow(new CButtonCancel(url_param('config')));
		$frmImages->Show();
		}
		else{
			$imagetype = get_request('imagetype',IMAGE_TYPE_ICON);

			$r_form = new CForm();

			$cmbImg = new CComboBox('imagetype',$imagetype,'submit();');
			$cmbImg->AddItem(IMAGE_TYPE_ICON,S_ICON);
			$cmbImg->AddItem(IMAGE_TYPE_BACKGROUND,S_BACKGROUND);

			$r_form->AddItem(S_TYPE.SPACE);
			$r_form->AddItem($cmbImg);

			show_table_header(S_IMAGES_BIG,$r_form);

			$table = new CTableInfo(S_NO_IMAGES_DEFINED);
			$table->setHeader(array(S_NAME,S_TYPE,S_IMAGE));

			$result=DBselect('SELECT imageid,imagetype,name '.
						' FROM images'.
						' WHERE '.DBin_node('imageid').
							' AND imagetype='.$imagetype.
						' ORDER BY name');
			while($row=DBfetch($result)){
				if($row['imagetype'] == IMAGE_TYPE_ICON)	$imagetype=S_ICON;
				else if($row['imagetype'] == IMAGE_TYPE_BACKGROUND)	$imagetype=S_BACKGROUND;
				else				$imagetype=S_UNKNOWN;

				$name = new CLink($row['name'],'config.php?form=update'.url_param('config').'&imageid='.$row['imageid']);

				$table->addRow(array(
					$name,
					$imagetype,
					$actions=new CLink(
						new CImg('image.php?height=24&imageid='.$row['imageid'],'no image',NULL),'image.php?imageid='.$row['imageid'])
					));
			}
			$table->show();
		}
	}
///////////////////////////////////
//  config = 6 // Value Mapping  //
///////////////////////////////////
	elseif($_REQUEST['config']==6){ // Value Mapping
		echo SBR;
		if(isset($_REQUEST['form'])) {
			$frmValmap = new CFormTable(S_VALUE_MAP);
			$frmValmap->SetHelp("web.mapping.php");
			$frmValmap->addVar("config",get_request("config",6));

			if(isset($_REQUEST["valuemapid"])){
				$frmValmap->addVar("valuemapid",$_REQUEST["valuemapid"]);
				$db_valuemaps = DBselect("select * FROM valuemaps".
					" WHERE valuemapid=".$_REQUEST["valuemapid"]);

				$db_valuemap = DBfetch($db_valuemaps);

				$frmValmap->SetTitle(S_VALUE_MAP.' "'.$db_valuemap["name"].'"');
			}

			if(isset($_REQUEST["valuemapid"]) && !isset($_REQUEST["form_refresh"])){
				$valuemap = array();
				$mapname = $db_valuemap["name"];
				$mappings = DBselect("select * FROM mappings WHERE valuemapid=".$_REQUEST["valuemapid"]);
				while($mapping = DBfetch($mappings)) {
					$value = array(
						"value" => $mapping["value"],
						"newvalue" => $mapping["newvalue"]);
					array_push($valuemap, $value);
				}
			}
			else{
				$mapname = get_request("mapname","");
				$valuemap = get_request("valuemap",array());
			}

			$frmValmap->addRow(S_NAME, new CTextBox("mapname",$mapname,40));

			$i = 0;
			$valuemap_el = array();
			foreach($valuemap as $value){
				array_push($valuemap_el,
					array(
						new CCheckBox("rem_value[]", 'no', null, $i),
						$value["value"].SPACE.RARR.SPACE.$value["newvalue"]
					),
					BR());
				$frmValmap->addVar("valuemap[$i][value]",$value["value"]);
				$frmValmap->addVar("valuemap[$i][newvalue]",$value["newvalue"]);
				$i++;
			}

			$saveButton = new CButton('save', S_SAVE);

			if(count($valuemap_el)==0) {
				array_push($valuemap_el, S_NO_MAPPING_DEFINED);
				$saveButton->setAttribute('disabled', 'true');
			} else {
				array_push($valuemap_el, new CButton('del_map',S_DELETE_SELECTED));
			}

			$frmValmap->addRow(S_MAPPING, $valuemap_el);
			$frmValmap->addRow(S_NEW_MAPPING, array(
				new CTextBox("add_value","",10),
				new CSpan(RARR,"rarr"),
				new CTextBox("add_newvalue","",10),
				SPACE,
				new CButton("add_map",S_ADD)
				),'new');

			$frmValmap->addItemToBottomRow($saveButton);
			if(isset($_REQUEST["valuemapid"])){
				$frmValmap->addItemToBottomRow(SPACE);
				$frmValmap->addItemToBottomRow(new CButtonDelete("Delete selected value mapping?",
					url_param("form").url_param("valuemapid").url_param("config")));
			}
			else {
			}
			$frmValmap->addItemToBottomRow(SPACE);
			$frmValmap->addItemToBottomRow(new CButtonCancel(url_param("config")));

			$frmValmap->Show();
		}
		else{
			show_table_header(S_VALUE_MAPPING_BIG);

			$table = new CTableInfo();
			$table->SetHeader(array(S_NAME, S_VALUE_MAP));

			$db_valuemaps = DBselect('SELECT * FROM valuemaps WHERE '.DBin_node('valuemapid'));
			while($db_valuemap = DBfetch($db_valuemaps)){
				$mappings_row = array();
				$db_maps = DBselect('SELECT * FROM mappings'.
					' WHERE valuemapid='.$db_valuemap['valuemapid']);

				while($db_map = DBfetch($db_maps)){
					array_push($mappings_row,
						$db_map['value'],
						SPACE.RARR.SPACE,
						$db_map['newvalue'],
						BR());
				}
				$table->AddRow(array(
					new CLink($db_valuemap['name'],'config.php?form=update&'.
						'valuemapid='.$db_valuemap['valuemapid'].url_param('config'),
						'action'),
					empty($mappings_row)?SPACE:$mappings_row
				));
			}

			$table->Show();
		}
	}
//////////////////////////////////////////
//  config = 10 // Regular Expressions  //
//////////////////////////////////////////
	else if($_REQUEST['config'] == 10){
		if(isset($_REQUEST["form"])){

			$frmRegExp = new CForm('config.php','post');
			$frmRegExp->setName(S_REGULAR_EXPRESSION);

			$frmRegExp->addVar('form',get_request('form',1));

			$from_rfr = get_request('form_refresh',0);
			$frmRegExp->addVar('form_refresh',$from_rfr+1);

			$frmRegExp->addVar('config',get_request('config',10));

			if(isset($_REQUEST['regexpid']))
				$frmRegExp->addVar('regexpid',$_REQUEST['regexpid']);

			$left_tab = new CTable();
			$left_tab->setCellPadding(3);
			$left_tab->setCellSpacing(3);

			$left_tab->setAttribute('border',0);

			$left_tab->addRow(create_hat(
					S_REGULAR_EXPRESSION,
					get_regexp_form(),//null,
					null,
					'hat_regexp',
					get_profile('web.config.hats.hat_regexp.state',1)
				));

			$right_tab = new CTable();
			$right_tab->setCellPadding(3);
			$right_tab->setCellSpacing(3);

			$right_tab->setAttribute('border',0);

			$right_tab->addRow(create_hat(
					S_EXPRESSIONS,
					get_expressions_tab(),//null,
					null,
					'hat_expressions',
					get_profile('web.config.hats.hat_expressions.state',1)
				));

			if(isset($_REQUEST['new_expression'])){
				$right_tab->addRow(create_hat(
						S_NEW_EXPRESSION,
						get_expression_form(),//null
						null,
						'hat_new_expression',
						get_profile('web.config.hats.hat_new_expression.state',1)
					));
			}


			$td_l = new CCol($left_tab);
			$td_l->setAttribute('valign','top');

			$td_r = new CCol($right_tab);
			$td_r->setAttribute('valign','top');

			$outer_table = new CTable();
			$outer_table->setAttribute('border',0);
			$outer_table->SetCellPadding(1);
			$outer_table->SetCellSpacing(1);
			$outer_table->AddRow(array($td_l,$td_r));

			$frmRegExp->Additem($outer_table);

			show_messages();
			$frmRegExp->Show();
		}
		else{
			echo SBR;
			show_table_header(S_REGULAR_EXPRESSIONS);
// ----
			$regexps = array();
			$regexpids = array();

			$sql = 'SELECT re.* '.
					' FROM regexps re '.
					' WHERE '.DBin_node('re.regexpid').
					' ORDER BY re.name';

			$db_regexps = DBselect($sql);
			while($regexp = DBfetch($db_regexps)){
				$regexp['expressions'] = array();

				$regexps[$regexp['regexpid']] = $regexp;
				$regexpids[$regexp['regexpid']] = $regexp['regexpid'];
			}

			$count = array();
			$expressions = array();
			$sql = 'SELECT e.* '.
					' FROM expressions e '.
					' WHERE '.DBin_node('e.expressionid').
						' AND '.DBcondition('e.regexpid',$regexpids).
					' ORDER BY e.expression_type';

			$db_exps = DBselect($sql);
			while($exp = DBfetch($db_exps)){
				if(!isset($expressions[$exp['regexpid']])) $count[$exp['regexpid']] = 1;
				else $count[$exp['regexpid']]++;

				if(!isset($expressions[$exp['regexpid']])) $expressions[$exp['regexpid']] = new CTable();

				$expressions[$exp['regexpid']]->addRow(array($count[$exp['regexpid']], ' &raquo; ', $exp['expression'],' ['.expression_type2str($exp['expression_type']).']'));

				$regexp[$exp['regexpid']]['expressions'][$exp['expressionid']] = $exp;
			}

			$form = new CForm(null,'post');
			$form->setName('regexp');

			$table = new CTableInfo();
			$table->setHeader(array(
				new CCheckBox('all_regexps',NULL,"CheckAll('".$form->GetName()."','all_regexps','group_regexpid');"),
				S_NAME,
				S_EXPRESSIONS
				));

			foreach($regexps as $regexpid => $regexp){

				$table->addRow(array(
					new CCheckBox('regexpids['.$regexp['regexpid'].']',NULL,NULL,$regexp['regexpid']),
					new CLink($regexp['name'],'config.php?form=update'.url_param('config').'&regexpid='.$regexp['regexpid'].'#form'),
					isset($expressions[$regexpid])?$expressions[$regexpid]:'-'
					));
			}
//			$table->SetFooter(new CCol(new CButtonQMessage('delete_selected',S_DELETE_SELECTED,S_DELETE_SELECTED_USERS_Q)));

			$table->SetFooter(new CCol(array(
				new CButtonQMessage('delete',S_DELETE_SELECTED,S_DELETE_SELECTED_REGULAR_EXPRESSIONS_Q)
			)));

			$form->AddItem($table);

			$form->show();
		}
	}
?>
<?php

include_once 'include/page_footer.php';

?>
