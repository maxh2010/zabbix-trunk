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
require_once('include/media.inc.php');
require_once('include/forms.inc.php');

$page['title'] = "S_MEDIA_TYPES";
$page['file'] = "media_types.php";
$page['hist_arg'] = array('form','mediatypeid');

include_once('include/page_header.php');

?>
<?php
	$fields=array(
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION

// media form
		'media_types'=>		array(T_ZBX_INT, O_OPT,	P_SYS,	DB_ID, NULL),
		'mediatypeid'=>		array(T_ZBX_INT, O_NO,	P_SYS,	DB_ID,'(isset({form})&&({form}=="update"))'),
		'type'=>		array(T_ZBX_INT, O_OPT,	NULL,	IN(implode(',',array(MEDIA_TYPE_EMAIL,MEDIA_TYPE_EXEC,MEDIA_TYPE_SMS,MEDIA_TYPE_JABBER))),'(isset({save}))'),
		'description'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'(isset({save}))'),
		'smtp_server'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'isset({type})&&({type}=='.MEDIA_TYPE_EMAIL.')&&isset({save})'),
		'smtp_helo'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'isset({type})&&({type}=='.MEDIA_TYPE_EMAIL.')&&isset({save})'),
		'smtp_email'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'isset({type})&&({type}=='.MEDIA_TYPE_EMAIL.')&&isset({save})'),
		'exec_path'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'isset({type})&&({type}=='.MEDIA_TYPE_EXEC.')&&isset({save})'),
		'gsm_modem'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'isset({type})&&({type}=='.MEDIA_TYPE_SMS.')&&isset({save})'),
		'username'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'(isset({type})&&{type}=='.MEDIA_TYPE_JABBER.')&&isset({save})'),
		'password'=>		array(T_ZBX_STR, O_OPT,	NULL,	NOT_EMPTY,'isset({type})&&({type}=='.MEDIA_TYPE_JABBER.')&&isset({save})'),
/* actions */
		'save'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'delete'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'cancel'=>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	NULL,	NULL),
		'go'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, NULL, NULL),
/* other */
		'form'=>		array(T_ZBX_STR, O_OPT, P_SYS,	NULL,	NULL),
		'form_refresh'=>	array(T_ZBX_INT, O_OPT,	NULL,	NULL,	NULL)
	);

	check_fields($fields);
	validate_sort_and_sortorder('mt.description',ZBX_SORT_UP);
?>
<?php

/* MEDIATYPE ACTIONS */
	$_REQUEST['go'] = get_request('go', 'none');

	$result = 0;
	if(isset($_REQUEST['save'])){
		if(isset($_REQUEST['mediatypeid'])){
/* UPDATE */
/*			$action = AUDIT_ACTION_UPDATE;*/
			$result=update_mediatype($_REQUEST['mediatypeid'],
				$_REQUEST['type'],$_REQUEST['description'],get_request('smtp_server'),
				get_request('smtp_helo'),get_request('smtp_email'),get_request('exec_path'),
				get_request('gsm_modem'),get_request('username'),get_request('password'));

			show_messages($result, S_MEDIA_TYPE_UPDATED, S_MEDIA_TYPE_WAS_NOT_UPDATED);
		}
		else{
/* ADD */
/*			$action = AUDIT_ACTION_ADD;*/
			$result=add_mediatype(
				$_REQUEST['type'],$_REQUEST['description'],get_request('smtp_server'),
				get_request('smtp_helo'),get_request('smtp_email'),get_request('exec_path'),
				get_request('gsm_modem'),get_request('username'),get_request('password'));

			show_messages($result, S_ADDED_NEW_MEDIA_TYPE, S_NEW_MEDIA_TYPE_WAS_NOT_ADDED);
		}
		if($result){
/*			add_audit($action,AUDIT_RESOURCE_MEDIA_TYPE,
				'Media type ['.$_REQUEST['description'].']');
*/
			unset($_REQUEST['form']);
		}
	}
	elseif(isset($_REQUEST['delete'])&&isset($_REQUEST['mediatypeid'])){
		$result=delete_mediatype($_REQUEST['mediatypeid']);
		show_messages($result, S_MEDIA_TYPE_DELETED, S_MEDIA_TYPE_WAS_NOT_DELETED);
		if($result)
		{
/*			add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_MEDIA_TYPE,
				'Media type ['.$mediatype['description'].']');
*/
			unset($_REQUEST['form']);
		}
	}

	else if($_REQUEST['go'] == 'delete'){
		$result = true;
		$media_types = get_request('media_types', array());

		DBstart();
		foreach($media_types as $media_typeid){
			$result &= delete_mediatype($media_typeid);
			if(!$result) break;
		}
		$result = DBend($result);

		if($result){
			unset($_REQUEST['form']);
		}

		show_messages($result, S_MEDIA_TYPE_DELETED, S_MEDIA_TYPE_WAS_NOT_DELETED);
	}
?>
<?php
	$medias_wdgt = new CWidget();

	$form = new CForm();
	$form->setMethod('get');

	$form->addItem(new CButton('form',S_CREATE_MEDIA_TYPE));

	$medias_wdgt->addPageHeader(S_CONFIGURATION_OF_MEDIA_TYPES_BIG, $form);

?>
<?php
	if(isset($_REQUEST['form'])){

		$medias_wdgt->addItem(insert_media_type_form());
	}
	else{
		$numrows = new CDiv();
		$numrows->setAttribute('name','numrows');

		$medias_wdgt->addHeader(S_MEDIA_TYPES_BIG);
//		$medias_wdgt->addHeader($numrows);

		$form = new CForm();
		$form->setName('frm_media_types');

		$table=new CTableInfo(S_NO_MEDIA_TYPES_DEFINED);
		$table->setHeader(array(
			new CCheckBox('all_media_types',NULL,"checkAll('".$form->getName()."','all_media_types','media_types');"),
			make_sorting_link(S_TYPE,'mt.type'),
			make_sorting_link(S_DESCRIPTION,'mt.description'),
			S_DETAILS
		));

// sorting
//		order_page_result($proxies, 'description');

// PAGING UPPER
		$paging = BR();
//		$paging = getPagingLine($proxies);
		$medias_wdgt->addItem($paging);
//---------

		$sql = 'SELECT mt.* '.
				' FROM media_type mt'.
				' WHERE '.DBin_node('mt.mediatypeid').
				order_by('mt.type,mt.description');
		$result=DBselect($sql);
		while($row=DBfetch($result)){
			switch($row['type']){
				case MEDIA_TYPE_EMAIL:
					$details =
						S_SMTP_SERVER.': "'.$row['smtp_server'].'", '.
						S_SMTP_HELO.': "'.$row['smtp_helo'].'", '.
						S_SMTP_EMAIL.': "'.$row['smtp_email'].'"';
					break;
				case MEDIA_TYPE_EXEC:
					$details = S_SCRIPT_NAME.': "'.$row['exec_path'].'"';
					break;
				case MEDIA_TYPE_SMS:
					$details = S_GSM_MODEM.': "'.$row['gsm_modem'].'"';
					break;
				case MEDIA_TYPE_JABBER:
					$details = S_JABBER_IDENTIFIER.': "'.$row['username'].'"';
					break;
				default:
					$details = '';
			}

			$table->addRow(array(
				new CCheckBox('media_types['.$row['mediatypeid'].']',NULL,NULL,$row['mediatypeid']),
				media_type2str($row['type']),
				new CLink($row['description'],'?&form=update&mediatypeid='.$row['mediatypeid']),
				$details));
		}

// PAGING FOOTER
		$table->addRow(new CCol($paging));
//		$items_wdgt->addItem($paging);
//---------

//----- GO ------
		$goBox = new CComboBox('go');
		$goBox->addItem('delete', S_DELETE_SELECTED);

// goButton name is necessary!!!
		$goButton = new CButton('goButton',S_GO.' (0)');
		$goButton->setAttribute('id','goButton');
		zbx_add_post_js('chkbxRange.pageGoName = "media_types";');

		$table->setFooter(new CCol(array($goBox, $goButton)));

		$form->addItem($table);

		$medias_wdgt->addItem($form);
	}

	$medias_wdgt->show();
?>

<?php

include_once('include/page_footer.php');

?>
