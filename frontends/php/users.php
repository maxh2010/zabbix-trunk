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
	include "include/config.inc.php";
	include "include/forms.inc.php";

	$page["title"] = "S_USERS";
	$page["file"] = "users.php";

	show_header($page["title"]);
	insert_confirm_javascript();
?>
<?php
        if(!check_anyright("User","U"))
        {
                show_table_header("<font color=\"AA0000\">".S_NO_PERMISSIONS."</font>");
                show_page_footer();
                exit;
        }

	$_REQUEST["config"]=@iif(isset($_REQUEST["config"]),$_REQUEST["config"],get_profile("web.users.config",0));
	update_profile("web.users.config",$_REQUEST["config"]);
?>
<?php
	update_profile("web.menu.config.last",$page["file"]);
?>
<?php
	if(isset($_REQUEST["save"])&&($_REQUEST["config"]==0))
	{
		if($_REQUEST["password1"]!=$_REQUEST["password2"]){
			if(isset($_REQUEST["userid"]))
				show_error_message(S_CANNOT_UPDATE_USER_BOTH_PASSWORDS);
			else
				show_error_message(S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST);
		} else {
			if(isset($_REQUEST["userid"])){
				$action = AUDIT_ACTION_UPDATE;
				$result=update_user($_REQUEST["userid"],
					$_REQUEST["name"],$_REQUEST["surname"],$_REQUEST["alias"],
					$_REQUEST["password1"],$_REQUEST["url"],$_REQUEST["autologout"],
					$_REQUEST["lang"],$_REQUEST["refresh"]);

				show_messages($result, S_USER_UPDATED, S_CANNOT_UPDATE_USER);
			} else {
				$action = AUDIT_ACTION_ADD;
				$result=add_user(
					$_REQUEST["name"],$_REQUEST["surname"],$_REQUEST["alias"],
					$_REQUEST["password1"],$_REQUEST["url"],$_REQUEST["autologout"],
					$_REQUEST["lang"],$_REQUEST["refresh"]);

				show_messages($result, S_USER_ADDED, S_CANNOT_ADD_USER);
			}
			if($result){
				add_audit($action,AUDIT_RESOURCE_USER,
					"User alias [".addslashes($_REQUEST["alias"]).
					"] name [".addslashes($_REQUEST["name"])."] surname [".
					addslashes($_REQUEST["surname"])."]]");
				unset($_REQUEST["form"]);
			}
		}
	}

	if(isset($_REQUEST["delete"])&&($_REQUEST["config"]==0))
	{
		$user=get_user_by_userid($_REQUEST["userid"]);
		$result=delete_user($_REQUEST["userid"]);
		show_messages($result, S_USER_DELETED, S_CANNOT_DELETE_USER);
		if($result){
			add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_USER,
				"User alias [".$user["alias"]."] name [".$user["name"]."] surname [".
				$user["surname"]."]");

			unset($_REQUEST["userid"]);
			unset($_REQUEST["form"]);
		}
	}

	if(isset($_REQUEST["save"])&&($_REQUEST["config"]==1))
	{
		$users=array();
		$db_users=DBselect("select userid from users");
		while($db_user=DBfetch($db_users)){
			if(!isset($_REQUEST[$db_user["userid"]])) continue;
			array_push($users,$db_user["userid"]);
		}

		if(isset($_REQUEST["usrgrpid"])){
			$result=update_user_group($_REQUEST["usrgrpid"], $_REQUEST["name"], $users);
			show_messages($result, S_GROUP_UPDATED, S_CANNOT_UPDATE_GROUP);
		}else{
			$result=add_user_group($_REQUEST["name"], $users);
			show_messages($result, S_GROUP_ADDED, S_CANNOT_ADD_GROUP);
		}

		if($result){
			unset($_REQUEST["form"]);
		}
	}

	if(isset($_REQUEST["delete"])&&($_REQUEST["config"]==1))
	{
		$result=delete_user_group($_REQUEST["usrgrpid"]);
		show_messages($result, S_GROUP_DELETED, S_CANNOT_DELETE_GROUP);
		if($result){
			unset($_REQUEST["usrgrpid"]);
			unset($_REQUEST["form"]);
		}
	}

	if(isset($_REQUEST["register"]))
	{
		if($_REQUEST["register"]=="delete_permission")
		{
			$result=delete_permission($_REQUEST["rightid"]);
			show_messages($result, S_PERMISSION_DELETED, S_CANNOT_DELETE_PERMISSION);
			unset($rightid);
		}
		if($_REQUEST["register"]=="add permission")
		{
			$result=add_permission($_REQUEST["userid"],$_REQUEST["right"],
				$_REQUEST["permission"],$_REQUEST["id"]);

			show_messages($result, S_PERMISSION_ADDED, S_CANNOT_ADD_PERMISSION);
		}
	}
	
	if(isset($_REQUEST["cancel"])){
		unset($_REQUEST["form"]);
	}
?>
<?php
	if(!isset($_REQUEST["config"]))
	{
		$_REQUEST["config"]=0;
	}

	$cmbConf = new CComboBox("config",$_REQUEST["config"],"submit()");
	$cmbConf->AddItem(0,S_USERS);
	$cmbConf->AddItem(1,S_USER_GROUPS);
	if($_REQUEST["config"] == 0){
		$btnNew = new CButton("form",S_CREATE_USER);
	}else if($_REQUEST["config"] == 1){
		$btnNew = new CButton("form",S_CREATE_GROUP);
	}
	$frmForm = new CForm("users.php");
	$frmForm->AddItem($cmbConf);
	$frmForm->AddItem(SPACE."|".SPACE);
	$frmForm->AddItem($btnNew);
	show_header2(S_CONFIGURATION_OF_USERS_AND_USER_GROUPS, $frmForm);
	echo BR; 
?>
<?php
	if($_REQUEST["config"]==0)
	{
		if(!isset($_REQUEST["form"]))
		{
			show_table_header(S_USERS_BIG);
			$table=new CTableInfo(S_NO_USERS_DEFINED);
			$table->setHeader(array(S_ID,S_ALIAS,S_NAME,S_SURNAME,S_IS_ONLINE_Q,S_ACTIONS));
		
			$db_users=DBselect("select u.userid,u.alias,u.name,u.surname ".
				"from users u order by u.alias");
			while($db_user=DBfetch($db_users))
			{
				if(!check_right("User","R",$db_user["userid"]))		continue;

				$alias = new CLink($db_user["alias"],
					"users.php?register=change&form=0&config=".$_REQUEST["config"].
					"&userid=".$db_user["userid"]."#form");
			
				$db_sessions = DBselect("select count(*) as count from sessions ".
						"where userid=".$db_user["userid"]." and lastaccess-600<".time());
				$db_ses_cnt=DBfetch($db_sessions);
				if($db_ses_cnt["count"]>0)
					$online=new CCol(S_YES,"on");
				else
					$online=new CCol(S_NO,"off");
		
		        	if(check_right("User","U",$db_user["userid"]))
				{
					$actions = S_MEDIA;
					if(get_media_count_by_userid($db_user["userid"])>0)
					{
						bfirst(&$actions);
					}
					$actions = new CLink($actions,"media.php?userid=".$db_user["userid"]);
				}
				else
				{
					$actions=S_CHANGE.SPACE."-".SPACE.S_MEDIA;
				}
		
				$table->addRow(array(
					$db_user["userid"],
					$alias,
					$db_user["name"],
					$db_user["surname"],
					$online,
					$actions
					));
			}
			$table->show();
		}
		else
		{
			insert_user_form(get_request("userid",NULL));

			if(isset($_REQUEST["userid"])))
			{
				echo BR;
				show_table_header("USER PERMISSIONS");

				$table  = new CTableInfo();
				$table->setHeader(array(S_PERMISSION,S_RIGHT,S_RESOURCE_NAME,S_ACTIONS));

				$db_rights = DBselect("select rightid,name,permission,id from rights ".
					"where userid=".$_REQUEST["userid"]." order by name,permission,id");
				while($db_right = DBfetch($db_rights))
				{
					if($db_right["permission"]=="R")	$permission=S_READ_ONLY;
					else if($db_right["permission"]=="U")	$permission=S_READ_WRITE;
					else if($db_right["permission"]=="H")	$permission=S_HIDE;
					else if($db_right["permission"]=="A")	$permission=S_ADD;
					else					$permission=$db_right["permission"];

					$actions= new CLink(
						S_DELETE,
						"users.php?userid=".$_REQUEST["userid"]."&rightid=".
						$db_right["rightid"]."&register=delete_permission>");

					$table->addRow(array(
						$db_right["name"],
						$permission,
						get_resource_name($db_right["name"],$db_right["id"]),
						$actions
					));
				}
				$table->show();

				echo BR;

				insert_permissions_form($_REQUEST["userid"]);
			}
		}
	}
	elseif($_REQUEST["config"]==1)
	{
		if(!isset($_REQUEST["form"]))
		{
			show_table_header(S_USER_GROUPS_BIG);
	
			$table = new CTableInfo(S_NO_USER_GROUPS_DEFINED);
			$table->setHeader(array(S_ID,S_NAME,S_MEMBERS));
		
			$result=DBselect("select usrgrpid,name from usrgrp order by name");
			while($row=DBfetch($result))
			{
				if(!check_right("User group","R",$row["usrgrpid"]))	continue;

				$name = new CLink(
					$row["name"],
					"users.php?config=".$_REQUEST["config"]."&form=0&usrgrpid=".
					$row["usrgrpid"]."#form");

				$users=SPACE;

				$db_users=DBselect("select distinct u.alias from users u,users_groups ug ".
					"where u.userid=ug.userid and ug.usrgrpid=".$row["usrgrpid"].
					" order by alias");

				if($db_user=DBfetch($db_users))		$users .=      $db_user["alias"];
				while($db_user=DBfetch($db_users))	$users .= ", ".$db_user["alias"];

				$table->addRow(array(
					$row["usrgrpid"], 
					$name, 
					$users));
			}
			$table->show();
		}
		else
		{
			insert_usergroups_form(isset($_REQUEST["usrgrpid"]) ? $_REQUEST["usrgrpid"] : NULL);
		}
	}
?>
<?php
	show_page_footer();
?>
