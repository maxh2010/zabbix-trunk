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

	show_header($page["title"],0,0);
	insert_confirm_javascript();
?>

<?php
        if(!check_anyright("User","U"))
        {
                show_table_header("<font color=\"AA0000\">".S_NO_PERMISSIONS."</font>");
                show_footer();
                exit;
        }

	$_REQUEST["config"]=@iif(isset($_REQUEST["config"]),$_REQUEST["config"],get_profile("web.users.config",0));
	update_profile("web.users.config",$_REQUEST["config"]);
?>

<?php
	update_profile("web.menu.config.last",$page["file"]);
?>

<?php
	if(isset($_REQUEST["register"]))
	{
		if($_REQUEST["register"]=="add")
		{
			if($_REQUEST["password1"]==$_REQUEST["password2"])
			{
				$result=add_user($_REQUEST["name"],$_REQUEST["surname"],$_REQUEST["alias"],$_REQUEST["password1"],$_REQUEST["url"],$_REQUEST["autologout"],$_REQUEST["lang"],$_REQUEST["refresh"]);
				show_messages($result, S_USER_ADDED, S_CANNOT_ADD_USER);
				if($result)
					add_audit(AUDIT_ACTION_ADD,AUDIT_RESOURCE_USER,"User alias [".addslashes($_REQUEST["alias"])."] name [".addslashes($_REQUEST["name"])."] surname [".addslashes($_REQUEST["surname"])."]]");
			}
			else
			{
				show_error_message(S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST);
			}
		}
		if($_REQUEST["register"]=="delete")
		{
			$user=get_user_by_userid($_REQUEST["userid"]);
			$result=delete_user($_REQUEST["userid"]);
			show_messages($result, S_USER_DELETED, S_CANNOT_DELETE_USER);
			if($result)
				add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_USER,"User alias [".$user["alias"]."] name [".$user["name"]."] surname [".$user["surname"]."]");
			unset($userid);
		}
		if($_REQUEST["register"]=="delete_permission")
		{
			$result=delete_permission($_REQUEST["rightid"]);
			show_messages($result, S_PERMISSION_DELETED, S_CANNOT_DELETE_PERMISSION);
			unset($rightid);
		}
		if($_REQUEST["register"]=="add permission")
		{
			$result=add_permission($_REQUEST["userid"],$_REQUEST["right"],$_REQUEST["permission"],$_REQUEST["id"]);
			show_messages($result, S_PERMISSION_ADDED, S_CANNOT_ADD_PERMISSION);
		}
		if($_REQUEST["register"]=="update")
		{
			if($_REQUEST["password1"]==$_REQUEST["password2"])
			{
				$result=update_user($_REQUEST["userid"],$_REQUEST["name"],$_REQUEST["surname"],$_REQUEST["alias"],$_REQUEST["password1"],$_REQUEST["url"],$_REQUEST["autologout"],$_REQUEST["lang"],$_REQUEST["refresh"]);
				show_messages($result, S_USER_UPDATED, S_CANNOT_UPDATE_USER);
				if($result)
					add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_USER,"User alias [".addslashes($_REQUEST["alias"])."] name [".addslashes($_REQUEST["name"])."] surname [".addslashes($_REQUEST["surname"])."]]");
			}
			else
			{
				show_error_message(S_CANNOT_UPDATE_USER_BOTH_PASSWORDS);
			}
		}
		if($_REQUEST["register"]=="add group")
		{
			$users=array();
			$result=DBselect("select userid from users");
			while($row=DBfetch($result))
			{
				if(isset($_REQUEST[$row["userid"]]))
				{
					$users=array_merge($users,array($row["userid"]));
				}
			}
//			$result=add_user_group($_REQUEST["name"], $_REQUEST["users"]);
			$result=add_user_group($_REQUEST["name"], $users);
			show_messages($result, S_GROUP_ADDED, S_CANNOT_ADD_GROUP);
		}
		if($_REQUEST["register"]=="update group")
		{
			$users=array();
			$result=DBselect("select userid from users");
			while($row=DBfetch($result))
			{
				if(isset($_REQUEST[$row["userid"]]))
				{
					$users=array_merge($users,array($row["userid"]));
				}
			}
//			$result=update_user_group($_REQUEST["usrgrpid"], $_REQUEST["name"], $_REQUEST["users"]);
			$result=update_user_group($_REQUEST["usrgrpid"], $_REQUEST["name"], $users);
			show_messages($result, S_GROUP_UPDATED, S_CANNOT_UPDATE_GROUP);
		}
		if($_REQUEST["register"]=="delete group")
		{
			$result=delete_user_group($_REQUEST["usrgrpid"]);
			show_messages($result, S_GROUP_DELETED, S_CANNOT_DELETE_GROUP);
			unset($_REQUEST["usrgrpid"]);
		}
	}
?>

<?php
?>

<?php
	if(!isset($_REQUEST["config"]))
	{
		$_REQUEST["config"]=0;
	}

	$h1=S_CONFIGURATION_OF_USERS_AND_USER_GROUPS;

#	$h2=S_GROUP."&nbsp;";
	$h2="";
	$h2=$h2."<select class=\"biginput\" name=\"config\" onChange=\"submit()\">";
	$h2=$h2.form_select("config",0,S_USERS);
	$h2=$h2.form_select("config",1,S_USER_GROUPS);
	$h2=$h2."</select>";

	show_header2($h1, $h2, "<form name=\"selection\" method=\"get\" action=\"users.php\">", "</form>");
?>

<?php
	if($_REQUEST["config"]==1)
	{
		echo "<br>";
		show_table_header(S_USER_GROUPS_BIG);
		table_begin();
		table_header(array(S_ID,S_NAME,S_MEMBERS,S_ACTIONS));
	
		$result=DBselect("select usrgrpid,name from usrgrp order by name");
		$col=0;
		while($row=DBfetch($result))
		{
			if(!check_right("User group","R",$row["usrgrpid"]))
			{
				continue;
			}
			$result1=DBselect("select distinct u.alias from users u,users_groups ug where u.userid=ug.userid and ug.usrgrpid=".$row["usrgrpid"]." order by alias");
			$users="&nbsp;";
			while($row1=DBfetc($result1)))
			{
				$users=$users.$row1["alias"];
				if($i<DBnum_rows($result1)-1)
				{
					$users=$users.", ";
				}
			}
			$actions="<A HREF=\"users.php?config=".$_REQUEST["config"]."&usrgrpid=".$row["usrgrpid"]."#form\">".S_CHANGE."</A>";
			table_row(array(
				$row["usrgrpid"],
				$row["name"],
				$users,
				$actions
				),$col++);
		}
		if(DBnum_rows($result)==0)
		{
				echo "<TR BGCOLOR=#EEEEEE>";
				echo "<TD COLSPAN=3 ALIGN=CENTER>".S_NO_USER_GROUPS_DEFINED."</TD>";
				echo "<TR>";
		}
		table_end();
	}
?>

<?php
	if($_REQUEST["config"]==0)
	{
		echo "<br>";
		show_table_header(S_USERS_BIG);
		table_begin();
		table_header(array(S_ID,S_ALIAS,S_NAME,S_SURNAME,S_IS_ONLINE_Q,S_ACTIONS));
	
		$result=DBselect("select u.userid,u.alias,u.name,u.surname from users u order by u.alias");
		$col=0;
		while($row=DBfetch($result))
		{
			if(!check_right("User","R",$row["userid"]))
			{
				continue;
			}
		
			$sql="select count(*) as count from sessions where userid=".$row["userid"]." and lastaccess-600<".time();
			$result2=DBselect($sql);
			$row2=DBfetch($result2);
			if($row2["count"]>0)
				$online=array("value"=>S_YES,"class"=>"on");
			else
				$online=array("value"=>S_NO,"class"=>"off");
	
	        	if(check_right("User","U",$row["userid"]))
			{
				if(get_media_count_by_userid($row["userid"])>0)
				{
					$actions="<A HREF=\"users.php?register=change&config=".$_REQUEST["config"]."&userid=".$row["userid"]."#form\">".S_CHANGE."</A> :: <A HREF=\"media.php?userid=".$row["userid"]."\"><b>M</b>edia</A>";
				}
				else
				{
					$actions="<A HREF=\"users.php?register=change&config=".$_REQUEST["config"]."&userid=".$row["userid"]."#form\">".S_CHANGE."</A> :: <A HREF=\"media.php?userid=".$row["userid"]."\">".S_MEDIA."</A>";
				}
			}
			else
			{
				$actions=S_CHANGE." - ".S_MEDIA;
			}
	
			table_row(array(
				$row["userid"],
				$row["alias"],
				$row["name"],
				$row["surname"],
				$online,
				$actions
				),$col++);
		}
		if(DBnum_rows($result)==0)
		{
				echo "<TR BGCOLOR=#EEEEEE>";
				echo "<TD COLSPAN=6 ALIGN=CENTER>".S_NO_USERS_DEFINED."</TD>";
				echo "<TR>";
		}
		table_end();
	}
?>

<?php
	if(isset($_REQUEST["userid"])&&($_REQUEST["config"]==0))
	{
	echo "<a name=\"form\"></a>";
	show_table_header("USER PERMISSIONS");

	table_begin();
	table_header(array(S_PERMISSION,S_RIGHT,S_RESOURCE_NAME,S_ACTIONS));
	$result=DBselect("select rightid,name,permission,id from rights where userid=".$_REQUEST["userid"]." order by name,permission,id");
	$col=0;
	while($row=DBfetch($result))
	{
		if($row["permission"]=="R")
		{
			$permission=S_READ_ONLY;
		}
		else if($row["permission"]=="U")
		{
			$permission=S_READ_WRITE;
		}
		else if($row["permission"]=="H")
		{
			$permission=S_HIDE;
		}
		else if($row["permission"]=="A")
		{
			$permission=S_ADD;
		}
		else
		{
			$permission=$row["permission"];
		}
		$actions="<A HREF=users.php?userid=".$_REQUEST["userid"]."&rightid=".$row["rightid"]."&register=delete_permission>".S_DELETE."</A>";
		table_row(array(
			$row["name"],
			$permission,
			get_resource_name($row["name"],$row["id"]),
			$actions
		),$col++);
	}
	table_end();

	insert_permissions_form($_REQUEST["userid"]);

	}
?>

<?php
	if($_REQUEST["config"]==1)
	{
		@insert_usergroups_form($_REQUEST["usrgrpid"]);
	}

	if($_REQUEST["config"]==0)
	{
		@insert_user_form($_REQUEST["userid"]);
	}
?>

<?php
	show_footer();
?>
