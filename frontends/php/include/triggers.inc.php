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
	require_once "maps.inc.php";

	function	get_severity_style($severity)
	{
		if($severity == 1)	return "information";
		elseif($severity == 2)	return "warning";
		elseif($severity == 3)	return "average";
		elseif($severity == 4)	return "high";
		elseif($severity == 5)	return "disaster";

		return "";
	}

	function	get_severity_description($severity)
	{
		if($severity == 0)	return S_NOT_CLASSIFIED;
		else if($severity == 1)	return S_INFORMATION;
		else if($severity == 2)	return S_WARNING;
		else if($severity == 3)	return S_AVERAGE;
		else if($severity == 4)	return S_HIGH;
		else if($severity == 5)	return S_DISASTER;

		return "Unknown";
	}

	function        get_realhosts_by_triggerid($triggerid)
	{
		$trigger = get_trigger_by_triggerid($triggerid);
		if($trigger["templateid"] > 0)
			return get_realhosts_by_triggerid($trigger["templateid"]);

		return get_hosts_by_triggerid($triggerid);
	}

	function	get_trigger_by_triggerid($triggerid)
	{
		$sql="select * from triggers where triggerid=$triggerid";
		$result=DBselect($sql);
		$row=DBfetch($result);
		if($row)
		{
			return	$row;
		}
		error("No trigger with triggerid=[$triggerid]");
		return FALSE;
	}

	function	get_hosts_by_triggerid($triggerid)
	{
		return DBselect("select distinct h.* from hosts h, functions f, items i".
			" where i.itemid=f.itemid and h.hostid=i.hostid and f.triggerid=$triggerid");
	}

	function	get_functions_by_triggerid($triggerid)
	{
		return DBselect("select * from functions where triggerid=$triggerid");
	}

	function	get_triggers_by_hostid($hostid, $show_mixed = "yes")
	{
		$db_triggers = DBselect("select distinct t.* from triggers t, functions f, items i".
			" where i.hostid=$hostid and f.itemid=i.itemid and f.triggerid=t.triggerid");

		if($show_mixed == "yes")
			return $db_triggers;

		$triggers = array();
		while($db_trigger = DBfetch($db_triggers))
		{
			$db_hosts = get_hosts_by_triggerid($db_trigger["triggerid"]);
			if(DBfetch($db_hosts))
			{
				array_push($triggers,$db_trigger["triggerid"]);
			}
		}
		$sql = "select distinct * from triggers where triggerid=0";
		foreach($triggers as $triggerid)
		{
			$sql .= " or triggerid=$triggerid";
		}
		return DBselect($sql);
	}

	function	get_triggers_by_templateid($triggerid)
	{
			return DBselect("select * from triggers where templateid=$triggerid");
	}

	function	get_hosts_by_expression($expression)
	{
		global $ZBX_CURNODEID;

		$state="";
		$host="";
		$hosts=array();
		for($i=0,$max=strlen($expression); $i<$max; $i++)
		{
			if($expression[$i] == '{' && $state=="")
			{
				$host="";
				$state='HOST';
				continue;
			}
			
			if($expression[$i] == '}' && $state=="")
			{
				$state='';
				$hosts[$host] = '\''.$host.'\'';
				continue;
			}

			if($expression[$i] == '(' && $state == "FUNCTION")
			{
				$state='PARAMETER';
				continue;
			}
			
			if($expression[$i] == ')' && $state == "PARAMETER")
			{
				$state='';
				continue;
			}
			
			if($expression[$i] == ':' && $state == "HOST")
			{
				$state="KEY";
				continue;
			}

			if($expression[$i] == '.' && ($state == "KEY" || $state == "FUNCTION"))
			{
				$state="FUNCTION";
				continue;
			}

			if($state == "HOST")
			{
				$host .= $expression[$i];
				continue;
			}
			if($state == "KEY" || $state == "FUNCTION" || $state == "PARAMETER")
				continue;
		}

		if(count($hosts) == 0) $hosts = array('');

		return DBselect('select distinct * from hosts where '.DBid2nodeid('hostid').'='.$ZBX_CURNODEID.
			' and host in ('.implode(',',$hosts).')');
	}

	function	validate_expression($expression)
	{
//		echo "Validating expression: $expression<br>";
		$exp_hosts = get_hosts_by_expression($expression);

		$ok=0;
// Replace all {server:key.function(param)} with 0
		while($ok==0)
		{
//			echo "Expression:$expression<br>";
			$arr="";
			if (eregi('^((.)*)[ ]*(\{((.)*)\})[ ]*((.)*)$', $expression, $arr)) 
			{
//				for($i=0;$i<20;$i++)
//				{
//					if($arr[$i])
//						echo "  $i: ",$arr[$i],"<br>";
//				}
				if(validate_simple_expression($arr[3])!=0)
				{
					return -1;
				}
				$expression=$arr[1]."0".$arr[6];
	                }
			else
			{
				$ok=1;
			}
		}
//		echo "Result:$expression<br><hr>";

		$ok=0;
		while($ok==0)
		{
// 	Replace all <float> <sign> <float> <K|M|G> with 0
//			echo "Expression:$expression<br>";
			$arr="";
// The minus sing '-' must be the last one in the list, otherwise it won't work!
			if (eregi('^((.)*)([0-9\.]+[A-Z]{0,1})[ ]*([\&\|\>\<\=\+\*\/\#-]{1})[ ]*([0-9\.]+[A-Z]{0,1})((.)*)$', $expression, $arr)) 
			{
//				echo "OK<br>";
//				for($i=0;$i<50;$i++)
//				{
//					if($arr[$i]!="")
//						echo "  $i: ",$arr[$i],"<br>";
//				}
				if(validate_float($arr[3])!=0)
				{
					error("[".$arr[3]."] is not a float");
					return -1;
				}
				if(validate_float($arr[5])!=0)
				{
					error("[".$arr[5]."] is not a float");
					return -1;
				}
				$expression=$arr[1]."(0)".$arr[6];
	                }
			else
			{
				$ok=1;
			}


// 	Replace all (float) with 0
//			echo "Expression2:[$expression]<br>";
			$arr="";
			if (eregi('^((.)*)(\(([ 0-9\.]+)\))((.)*)$', $expression, $arr)) 
			{
//				echo "OK<br>";
//				for($i=0;$i<30;$i++)
//				{
//					if($arr[$i]!="")
//						echo "  $i: ",$arr[$i],"<br>";
//				}
				if(validate_float($arr[4])!=0)
				{
					error("[".$arr[4]."] is not a float");
					return -1;
				}
				$expression=$arr[1]."0".$arr[5];
				$ok=0;
	                }
			else
			{
				$ok=1;
			}



		}

		$exp_host = DBfetch($exp_hosts);
		if(!$exp_host)
		{
			error("Incorrect trigger expression. Incorrect host is used.");
			return 1;
		}
		else
		{
			$rows=0;
			unset($fail);
			do
			{
				if($exp_host["status"]==HOST_STATUS_TEMPLATE)
				{
					$fail=1;
				}
				$rows++;
			} while($exp_host = DBfetch($exp_hosts));

			if(isset($fail) && ($rows>1))
			{
				error("Incorrect trigger expression. You can't use template hosts".
					" in mixed expressions.");
				return 1;
			}
		}

		if($expression=="0")
		{
			return 0;
		}

		error("Incorrect trigger expression '$expression'");
		return 1;
	}


	function	add_trigger(
		$expression, $description, $priority, $status,
		$comments, $url, $deps=array(), $templateid=0)
	{
		if(!is_null($expression)) if(validate_expression($expression))	return FALSE;

		$triggerid=get_dbid("triggers","triggerid");

		$result=DBexecute("insert into triggers".
			"  (triggerid,description,priority,status,comments,url,value,error,templateid)".
			" values ($triggerid,".zbx_dbstr($description).",$priority,$status,".zbx_dbstr($comments).",".
			"".zbx_dbstr($url).",2,'Trigger just added. No status update so far.',$templateid)");
		if(!$result)
		{
			return	$result;
		}
 
		add_event($triggerid,TRIGGER_VALUE_UNKNOWN);
 
		$expression = implode_exp($expression,$triggerid);

		DBexecute("update triggers set expression=".zbx_dbstr($expression)." where triggerid=$triggerid");

		reset_items_nextcheck($triggerid);

		foreach($deps as $val)
		{
			$result = add_trigger_dependency($triggerid, $val);
		}

		$trig_hosts = get_hosts_by_triggerid($triggerid);
		$trig_host = DBfetch($trig_hosts);
		if($result)
		{
			$msg = "Added trigger '".$description."'";
			if($trig_host)
			{
				$msg .= " to host '".$trig_host["host"]."'";
			}
			info($msg);
		}

		if($trig_host)
		{// create trigger for childs
			$child_hosts = get_hosts_by_templateid($trig_host["hostid"]);
			while($child_host = DBfetch($child_hosts))
			{
				$result = copy_trigger_to_host($triggerid, $child_host["hostid"]);
				if(!$result){
					if($templateid == 0)
					{ // delete main trigger (and recursively childs)
						delete_trigger($triggerid);
					}
					return $result;
				}
			}
		}

		return $triggerid;
	}

	function	get_trigger_dependences_by_triggerid($triggerid)
	{
		$result = array();

		$db_deps = DBselect("select * from trigger_depends where triggerid_down=".$triggerid);
		while($db_dep = DBfetch($db_deps))
			array_push($result, $db_dep["triggerid_up"]);
			
		return $result;
	}

	function	copy_trigger_to_host($triggerid, $hostid, $copy_mode = false)
	{
		$trigger = get_trigger_by_triggerid($triggerid);

		$deps = get_trigger_dependences_by_triggerid($triggerid);

		$host_triggers = get_triggers_by_hostid($hostid, "no");
		while($host_trigger = DBfetch($host_triggers))
		{
			if($host_trigger["templateid"] != 0)				continue;
			if(cmp_triggers($triggerid, $host_trigger["triggerid"]))	continue;

			// link not linked trigger with same expression
			return update_trigger(
				$host_trigger["triggerid"],
				NULL,	// expression
				$trigger["description"],
				$trigger["priority"],
				NULL,	// status
				$trigger["comments"],
				$trigger["url"],
				$deps,
				$copy_mode ? 0 : $triggerid);
		}

		$newtriggerid=get_dbid("triggers","triggerid");

		$result = DBexecute("insert into triggers".
			" (triggerid,description,priority,status,comments,url,value,expression,templateid)".
			" values ($newtriggerid,".zbx_dbstr($trigger["description"]).",".$trigger["priority"].",".
			$trigger["status"].",".zbx_dbstr($trigger["comments"]).",".
			zbx_dbstr($trigger["url"]).",2,'{???:???}',".
			($copy_mode ? 0 : $triggerid).")");

		if(!$result)
			return $result;

		$host = get_host_by_hostid($hostid);
		$newexpression = $trigger["expression"];

		// Loop: functions
		$functions = get_functions_by_triggerid($triggerid);
		while($function = DBfetch($functions))
		{
			$item = get_item_by_itemid($function["itemid"]);

			$host_items = DBselect("select * from items".
				" where key_=".zbx_dbstr($item["key_"]).
				" and hostid=".$host["hostid"]);
			$host_item = DBfetch($host_items);
			if(!$host_item)
			{
				error("Missing key '".$item["key_"]."' for host '".$host["host"]."'");
				return FALSE;
			}

			$newfunctionid=get_dbid("functions","functionid");

			$result = DBexecute("insert into functions (functionid,itemid,triggerid,function,parameter)".
				" values ($newfunctionid,".$host_item["itemid"].",$newtriggerid,".
				zbx_dbstr($function["function"]).",".zbx_dbstr($function["parameter"]).")");

			$newexpression = str_replace(
				"{".$function["functionid"]."}",
				"{".$newfunctionid."}",
				$newexpression);
		}

		DBexecute("update triggers set expression=".zbx_dbstr($newexpression).
			" where triggerid=$newtriggerid");
// copy dependences
		delete_dependencies_by_triggerid($newtriggerid);
		$db_deps = DBselect("select * from trigger_depends where".
			" triggerid_down=".$triggerid);
		while($db_dep = DBfetch($db_deps))
		{
			add_trigger_dependency($newtriggerid, $db_dep["triggerid_up"]);
		}

		info("Added trigger '".$trigger["description"]."' to host '".$host["host"]."'");

// Copy triggers to the child hosts
		$child_hosts = get_hosts_by_templateid($hostid);
		while($child_host = DBfetch($child_hosts))
		{// recursion
			$result = copy_trigger_to_host($newtriggerid, $child_host["hostid"]);
			if(!$result){
				return result;
			}
		}

		return $newtriggerid;
	}
	
	# Translate {10}>10 to something like localhost:procload.last(0)>10

	function	explode_exp ($expression, $html,$template=false)
	{
#		echo "EXPRESSION:",$expression,"<Br>";

		$functionid='';
		$exp='';
		$state='';
		for($i=0,$max=strlen($expression); $i<$max; $i++)
		{
			if($expression[$i] == '{')
			{
				$functionid='';
				$state='FUNCTIONID';
				continue;
			}
			if($expression[$i] == '}')
			{
				$state='';
				if($function_data = DBfetch(DBselect('select h.host,i.key_,f.function,f.parameter,i.itemid,i.value_type'.
					' from items i,functions f,hosts h'.
					' where functionid='.$functionid.' and i.itemid=f.itemid and h.hostid=i.hostid')))
				{
					if($template) $function_data["host"] = '{HOSTNAME}';
						
					if($html == 0)
					{
						$exp .= "{".$function_data["host"].":".$function_data["key_"].".".
							$function_data["function"]."(".$function_data["parameter"].")}";
					}
					else
					{
						$link = new CLink($function_data["host"].":".$function_data["key_"],
							'history.php?action='.( $function_data["value_type"] ==0 ? 'showvalues' : 'showgraph').
							'&itemid='.$function_data['itemid']);
					
						$exp .= $link->ToString().'.'.bold($function_data["function"].'(').$function_data["parameter"].bold(')');
					}
				}
				else
				{
					if($html == 1)	$exp .= "<FONT COLOR=\"#AA0000\">";
					$exp .= "*ERROR*";
					if($html == 1)	$exp .= "</FONT>";
				}
				continue;
			}
			if($state == "FUNCTIONID")
			{
				$functionid=$functionid.$expression[$i];
				continue;
			}
			$exp=$exp.$expression[$i];
		}
#		echo "EXP:",$exp,"<Br>";
		return $exp;
	}

	function	implode_exp ($expression, $triggerid)
	# Translate localhost:procload.last(0)>10 to {12}>10
	{
//		echo "Expression:$expression<br>";
		$exp='';
		$state="";
		for($i=0,$max=strlen($expression); $i<$max; $i++)
		{
			if($expression[$i] == '{')
			{
				if($state=="")
				{
					$host='';
					$key='';
					$function='';
					$parameter='';
					$state='HOST';
					continue;
				}
			}
			if( ($expression[$i] == '}')&&($state=="") )
			{
//				echo "HOST:$host<BR>";
//				echo "KEY:$key<BR>";
//				echo "FUNCTION:$function<BR>";
//				echo "PARAMETER:$parameter<BR>";
				$state='';
		
				$res=DBselect("select i.itemid from items i,hosts h".
					" where i.key_=".zbx_dbstr($key).
					" and h.host=".zbx_dbstr($host).
					" and h.hostid=i.hostid");
				$row=DBfetch($res);

				$itemid=$row["itemid"];
	
				$functionid = get_dbid("functions","functionid");
				$res=DBexecute("insert into functions (functionid,itemid,triggerid,function,parameter)".
					" values ($functionid,$itemid,$triggerid,".zbx_dbstr($function).",".
					zbx_dbstr($parameter).")");
				if(!$res)
				{
					return	$res;
				}

				$exp=$exp.'{'.$functionid.'}';

				continue;
			}
			if($expression[$i] == '(')
			{
				if($state == "FUNCTION")
				{
					$state='PARAMETER';
					continue;
				}
			}
			if($expression[$i] == ')')
			{
				if($state == "PARAMETER")
				{
					$state='';
					continue;
				}
			}
			if(($expression[$i] == ':') && ($state == "HOST"))
			{
				$state="KEY";
				continue;
			}
			if($expression[$i] == '.')
			{
				if($state == "KEY")
				{
					$state="FUNCTION";
					continue;
				}
				// Support for '.' in KEY
				if($state == "FUNCTION")
				{
					$state="FUNCTION";
					$key=$key.".".$function;
					$function="";
					continue;
				}
			}
			if($state == "HOST")
			{
				$host=$host.$expression[$i];
				continue;
			}
			if($state == "KEY")
			{
				$key=$key.$expression[$i];
				continue;
			}
			if($state == "FUNCTION")
			{
				$function=$function.$expression[$i];
				continue;
			}
			if($state == "PARAMETER")
			{
				$parameter=$parameter.$expression[$i];
				continue;
			}
			$exp=$exp.$expression[$i];
		}
		return $exp;
	}

	function	update_trigger_comments($triggerid,$comments)
	{
		return	DBexecute("update triggers set comments=".zbx_dbstr($comments).
			" where triggerid=$triggerid");
	}

	# Update Trigger status

	function	update_trigger_status($triggerid,$status)
	{
		// first update status for child triggers
		$db_chd_triggers = get_triggers_by_templateid($triggerid);
		while($db_chd_trigger = DBfetch($db_chd_triggers))
		{
			update_trigger_status($db_chd_trigger["triggerid"],$status);
		}

		add_event($triggerid,TRIGGER_VALUE_UNKNOWN);
		return	DBexecute("update triggers set status=$status where triggerid=$triggerid");
	}

	# "Processor load on {HOSTNAME} is 5" to "Processor load on www.sf.net is 5"
	function	expand_trigger_description_by_data($row)
	{
		if($row)
		{
			if(is_null($row["host"])) $row["host"] = "{HOSTNAME}";
			$description = str_replace("{HOSTNAME}", $row["host"],$row["description"]);
		}
		else
		{
			$description = "*ERROR*";
		}
		return $description;
	}
	
	function	expand_trigger_description_simple($triggerid)
	{
		return expand_trigger_description_by_data(
			DBfetch(
				DBselect("select distinct t.description,h.host".
					" from triggers t left join functions f on t.triggerid=f.triggerid ".
					" left join items i on f.itemid=i.itemid ".
					" left join hosts h on i.hostid=h.hostid ".
					" where t.triggerid=$triggerid")
				)
			);
	}

	function	expand_trigger_description($triggerid)
	{
		$description=expand_trigger_description_simple($triggerid);
		$description=stripslashes(htmlspecialchars($description));

		return $description;
	}

	function	update_trigger_value_to_unknown_by_hostid($hostid)
	{
		$result = DBselect("select distinct t.triggerid".
			" from hosts h,items i,triggers t,functions f".
			" where f.triggerid=t.triggerid and f.itemid=i.itemid".
			" and h.hostid=i.hostid and h.hostid=$hostid");
		$now = time();
		while($row=DBfetch($result))
		{
			if(!add_event($row["triggerid"],TRIGGER_VALUE_UNKNOWN,$now)) continue;

			DBexecute('update triggers set value='.TRIGGER_VALUE_UNKNOWN.' where triggerid='.$row["triggerid"]);
		}
	}

	function add_event($triggerid, $value, $time=NULL)
	{
		if(is_null($time)) $time = time();

		$result = DBselect('select value from events where triggerid='.$triggerid.' order by clock desc',1);
		$last_value = DBfetch($result);
		if($last_value)
		{
			if($value == $last_value['value'])
				return false;
		}
		$eventid = get_dbid("events","eventid");
		$result = DBexecute('insert into events(eventid,triggerid,clock,value) values('.$eventid.','.$triggerid.','.$time.','.$value.')');
		if($value == TRIGGER_VALUE_FALSE || $value == TRIGGER_VALUE_TRUE)
		{
			DBexesute('update alerts set retries=3,error=\'Trigger changed its status. WIll not send repeats.\''.
				' where triggerid='.$triggerid.' and repeats>0 and status='.ALERT_STATUS_NOT_SENT);
		}
		return true;
	}

	function	add_trigger_dependency($triggerid,$depid)
	{
		$result=insert_dependency($triggerid,$depid);;
		if(!$result)
		{
			return $result;
		}
		add_additional_dependencies($triggerid,$depid);
		return $result;
	}

	# Delete Trigger definition

	function	delete_trigger($triggerid)
	{
		// first delete child triggers
		$db_triggers= get_triggers_by_templateid($triggerid);
		while($db_trigger = DBfetch($db_triggers))
		{// recursion
			$result = delete_trigger($db_trigger["triggerid"]);
			if(!$result)    return  $result;
		}

		// get hosts before functions deletion !!!
		$trig_hosts = get_hosts_by_triggerid($triggerid);

		$result = delete_dependencies_by_triggerid($triggerid);
		if(!$result)	return	$result;

		DBexecute("delete from trigger_depends where triggerid_up=$triggerid");

		$result=delete_function_by_triggerid($triggerid);
		if(!$result)	return	$result;

		$result=delete_events_by_triggerid($triggerid);
		if(!$result)	return	$result;

		$result=delete_services_by_triggerid($triggerid);
		if(!$result)	return	$result;

		$result=delete_sysmaps_elements_with_triggerid($triggerid);
		if(!$result)	return	$result;

		DBexecute("delete from alerts where triggerid=$triggerid");

		DBexecute("update sysmaps_links set triggerid=NULL where triggerid=$triggerid");
		
		$db_actions = DBselect("select distinct c.actionid from conditions c, triggers t".
			" where c.conditiontype=".CONDITION_TYPE_TRIGGER.
			" and c.value=t.triggerid and t.triggerid=".$triggerid);
		while($db_action = DBfetch($db_actions))
		{
			DBexecute("update actions set status=".ACTION_STATUS_DISABLED.
				" where actionid=".$db_action["actionid"]);
		}

		$trigger = get_trigger_by_triggerid($triggerid);

		$result = DBexecute("delete from triggers where triggerid=$triggerid");

		if($result)
		{
			$msg = "Trigger '".$trigger["description"]."' deleted";
			$trig_host = DBfetch($trig_hosts);
			if($trig_host)
			{
				$msg .= " from host '".$trig_host["host"]."'";
			}
			info($msg);
		}
		return $result;
	}

	# Update Trigger definition

	function	update_trigger($triggerid,$expression=NULL,$description=NULL,$priority=NULL,$status=NULL,
		$comments=NULL,$url=NULL,$deps=array(),$templateid=0)
	{
		$trigger	= get_trigger_by_triggerid($triggerid);
		$trig_hosts	= get_hosts_by_triggerid($triggerid);
		$trig_host	= DBfetch($trig_hosts);

		if(is_null($expression))
		{
			$expression = explode_exp($trigger["expression"],0);
		}
		else
		{
			if(validate_expression($expression))
				return FALSE;
		}

		$exp_hosts 	= get_hosts_by_expression($expression);
		$chd_hosts	= get_hosts_by_templateid($trig_host["hostid"]);

		if(DBfetch($chd_hosts))
		{
			$exp_host = DBfetch($exp_hosts);
			$db_chd_triggers = get_triggers_by_templateid($triggerid);
			while($db_chd_trigger = DBfetch($db_chd_triggers))
			{
				$chd_trig_hosts = get_hosts_by_triggerid($db_chd_trigger["triggerid"]);
				$chd_trig_host = DBfetch($chd_trig_hosts);

				$newexpression = str_replace(
					"{".$exp_host["host"].":",
					"{".$chd_trig_host["host"].":",
					$expression);
			// recursion
				update_trigger(
					$db_chd_trigger["triggerid"],
					$newexpression,
					$description,
					$priority,
					NULL,		// status
					$comments,
					$url,
					$deps,
					$triggerid);
			}
		}

		$result=delete_function_by_triggerid($triggerid);
		if(!$result)
		{
			return	$result;
		}

		$expression = implode_exp($expression,$triggerid);
		add_event($triggerid,TRIGGER_VALUE_UNKNOWN);
		reset_items_nextcheck($triggerid);

		$sql="update triggers set";
		if(!is_null($expression))	$sql .= " expression=".zbx_dbstr($expression).",";
		if(!is_null($description))	$sql .= " description=".zbx_dbstr($description).",";
		if(!is_null($priority))		$sql .= " priority=$priority,";
		if(!is_null($status))		$sql .= " status=$status,";
		if(!is_null($comments))		$sql .= " comments=".zbx_dbstr($comments).",";
		if(!is_null($url))		$sql .= " url=".zbx_dbstr($url).",";
		if(!is_null($templateid))	$sql .= " templateid=$templateid,";
		$sql .= " value=2 where triggerid=$triggerid";

		$result = DBexecute($sql);

		delete_dependencies_by_triggerid($triggerid);
		foreach($deps as $val)
		{
			$result=add_trigger_dependency($triggerid, $val);
		}

		if($result)
		{
			$trig_hosts	= get_hosts_by_triggerid($triggerid);
			$msg = "Trigger '".$trigger["description"]."' updated";
			$trig_host = DBfetch($trig_hosts);
			if($trig_host)
			{
				$msg .= " from host '".$trig_host["host"]."'";
			}
			info($msg);
		}
		return $result;
	}

	function	check_right_on_trigger($permission,$triggerid)	/* TODO */
	{
		/*
                $result=DBselect("select distinct h.hostid from functions f,items i,hosts h".
			" where h.hostid=i.hostid and i.itemid=f.itemid and f.triggerid=$triggerid");
		while($row=DBfetch($result))
			if(check_right("Host",$permission,$row["hostid"]))
				return 1;
		*/
		return	0;
	}

	function	delete_dependencies_by_triggerid($triggerid)
	{
		$db_deps = DBselect("select triggerid_up, triggerid_down from trigger_depends".
			" where triggerid_down=".$triggerid);
		while($db_dep = DBfetch($db_deps))
		{
			delete_trigger_dependency($db_dep["triggerid_down"],$db_dep["triggerid_up"]);
		}
		return TRUE;
	}

	function	delete_trigger_dependency($triggerid_down, $triggerid_up)
	{
		$result = DBselect("select triggerid_up from trigger_depends".
			" where triggerid_up=$triggerid_up and triggerid_down=$triggerid_down");
		while($row=DBfetch($result))
		{
			DBexecute("update triggers set dep_level=dep_level-1".
				" where triggerid=".$row["triggerid_up"]);
		}

		DBexecute("delete from trigger_depends".
			" where triggerid_up=$triggerid_up and triggerid_down=$triggerid_down");

		return	TRUE;
	}

	function	insert_dependency($triggerid_down,$triggerid_up)
	{
		$triggerdepid = get_dbid("trigger_depends","triggerdepid");
		$result=DBexecute("insert into trigger_depends (triggerdepid,triggerid_down,triggerid_up)".
			" values ($triggerdepid,$triggerid_down,$triggerid_up)");
		if(!$result)
		{
			return	$result;
		}
		return DBexecute("update triggers set dep_level=dep_level+1 where triggerid=$triggerid_up");
	}

	// If 1 depends on 2, and 2 depends on 3, then add dependency 1->3
	function	add_additional_dependencies($triggerid_down,$triggerid_up)
	{
		$result=DBselect("select triggerid_down from trigger_depends".
			" where triggerid_up=$triggerid_down");
		while($row=DBfetch($result))
		{
			insert_dependency($row["triggerid_down"],$triggerid_up);
			add_additional_dependencies($row["triggerid_down"],$triggerid_up);
		}
		$result=DBselect("select triggerid_up from trigger_depends where triggerid_down=$triggerid_up");
		while($row=DBfetch($result))
		{
			insert_dependency($triggerid_down,$row["triggerid_up"]);
			add_additional_dependencies($triggerid_down,$row["triggerid_up"]);
		}
	}

	function	delete_function_by_triggerid($triggerid)
	{
		return	DBexecute("delete from functions where triggerid=$triggerid");
	}

	function	delete_events_by_triggerid($triggerid)
	{
		return	DBexecute("delete from events where triggerid=$triggerid");
	}

	function	delete_triggers_by_itemid($itemid)
	{
		$result=DBselect("select triggerid from functions where itemid=$itemid");
		while($row=DBfetch($result))
		{
			if(!delete_trigger($row["triggerid"]))
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	# Delete Service definitions by triggerid

	function	delete_services_by_triggerid($triggerid)
	{
		$result = DBselect("select serviceid from services where triggerid=$triggerid");
		while($row = DBfetch($result))
		{
			delete_service($row["serviceid"]);
		}
		return	TRUE;
	}

	function	cmp_triggers($triggerid1, $triggerid2)	// compare EXPRESSION !!!
	{
		$trig1 = get_trigger_by_triggerid($triggerid1);
		$trig2 = get_trigger_by_triggerid($triggerid2);

		$trig_fnc1 = get_functions_by_triggerid($triggerid1);
		
		$expr1 = $trig1["expression"];
		while($fnc1 = DBfetch($trig_fnc1))
		{
			$trig_fnc2 = get_functions_by_triggerid($triggerid2);
			while($fnc2 = DBfetch($trig_fnc2)){
				if(strcmp($fnc1["function"],$fnc2["function"]))	continue;
				if($fnc1["parameter"] != $fnc2["parameter"])	continue;

				$item1 = get_item_by_itemid($fnc1["itemid"]);
				$item2 = get_item_by_itemid($fnc2["itemid"]);

				if(strcmp($item1["key_"],$item2["key_"]))	continue;

				$expr1 = str_replace(
					"{".$fnc1["functionid"]."}",
					"{".$fnc2["functionid"]."}",
					$expr1);
				break;
			}
		}
		return strcmp($expr1,$trig2["expression"]);
	}

	function	delete_template_triggers($hostid, $templateid = null /* array format 'arr[id]=name' */, $unlink_mode = false)
	{
		$triggers = get_triggers_by_hostid($hostid);
		while($trigger = DBfetch($triggers))
		{
			if($trigger["templateid"]==0)	continue;

			if($templateid != null)
                        {
                                $db_tmp_hosts = get_hosts_by_triggerid($trigger["templateid"]);
				$tmp_host = DBfetch($db_tmp_hosts);
				if(is_array($templateid))
				{
					if(!isset($templateid[$tmp_host["hostid"]]))
						continue;
				}
                                elseif($tmp_host["hostid"] != $templateid)
                                        continue;
                        }

                        if($unlink_mode)
                        {
                                if(DBexecute("update triggers set templateid=0 where triggerid=".$trigger["triggerid"]))
                                {
                                        info("Trigger '".$trigger["description"]."' unlinked");
                                }
                        }
                        else
                        {
				delete_trigger($trigger["triggerid"]);
			}
		}

		return TRUE;
	}
	
	function	copy_template_triggers($hostid, $templateid = null /* array format 'arr[id]=name' */, $copy_mode = false)
	{
		if(null == $templateid)
		{
			$templateid = get_templates_by_hostid($hostid);;
		}

		if(is_array($templateid))
		{
			foreach($templateid as $id => $name)
				copy_template_triggers($hostid, $id, $copy_mode); // attention recursion
			return;
		}

		$triggers = get_triggers_by_hostid($templateid);
		while($trigger = DBfetch($triggers))
		{
			copy_trigger_to_host($trigger["triggerid"], $hostid, $copy_mode);
		}
	}

	function	get_triggers_overview($groupid, $nodeid)
	{
		global $USER_DETAILS;

		$table = new CTableInfo(S_NO_TRIGGERS_DEFINED);
		if($groupid > 0)
		{
			$group_where = ',hosts_groups hg where hg.groupid='.$groupid.' and hg.hostid=h.hostid and';
		} else {
			$group_where = ' where';
		}

		$result=DBselect('select distinct t.description,t.value,t.priority,t.lastchange,h.hostid,h.host'.
			' from hosts h,items i,triggers t, functions f '.$group_where.
			' h.status='.HOST_STATUS_MONITORED.' and h.hostid=i.hostid and i.itemid=f.itemid and f.triggerid=t.triggerid'.
			' and h.hostid in ('.get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY, null, null, $nodeid).') '.
			' and t.status='.TRIGGER_STATUS_ENABLED.' and i.status='.ITEM_STATUS_ACTIVE.
			' order by t.description');
		unset($triggers);
		unset($hosts);
		while($row = DBfetch($result))
		{
			$hosts[$row['host']] = $row['host'];
			$triggers[$row['description']][$row['host']] = array(
				'value'		=> $row['value'], 
				'lastchange'	=> $row['lastchange'],
				'priority'	=> $row['priority']);
		}
		if(!isset($hosts))
		{
			return $table;
		}
		sort($hosts);

		$header=array(new CCol(S_TRIGGERS,'center'));
		foreach($hosts as $hostname)
		{
			$header=array_merge($header,array(new CImg('vtext.php?text='.$hostname)));
		}
		$table->SetHeader($header,'vertical_header');

		foreach($triggers as $descr => $trhosts)
		{
			$table_row = array(nbsp($descr));
			foreach($hosts as $hostname)
			{
				$style = NULL;
				if(isset($trhosts[$hostname]))
				{
					switch($trhosts[$hostname]['value'])
					{
						case TRIGGER_VALUE_TRUE:
							$style = get_severity_style($trhosts[$hostname]['priority']);
							break;
						case TRIGGER_VALUE_FALSE:
							$style = 'normal';
						default:
							$style = 'unknown_trigger';
					}

					if((time(NULL)-$trhosts[$hostname]['lastchange'])<300)	 	$style .= '_blink1';
					elseif((time(NULL)-$trhosts[$hostname]['lastchange'])<900) 	$style .= '_blink2';
				}
				array_push($table_row,new CCol(SPACE,$style));
			}
			$table->AddRow($table_row);
		}
		return $table;
	}

	function	get_function_by_functionid($functionid)
	{
		$result=DBselect("select * from functions where functionid=$functionid");
		$row=DBfetch($result);
		if($row)
		{
			return	$row;
		}
		else
		{
			error("No function with functionid=[$functionid]");
		}
		return	$item;
	}

	function	calculate_availability($triggerid,$period_start,$period_end)
	{
		if(($period_start==0)&&($period_end==0))
		{
	        	$sql="select count(*) as cnt,min(clock) as minn,max(clock) as maxx from events where triggerid=$triggerid";
		}
		else
		{
	        	$sql="select count(*) as cnt,min(clock) as minn,max(clock) as maxx from events where triggerid=$triggerid and clock>=$period_start and clock<=$period_end";
		}

		$row=DBfetch(DBselect($sql));
		if($row["cnt"]>0)
		{
			$min=$row["minn"];
			$max=$row["maxx"];
		}
		else
		{
			if(($period_start==0)&&($period_end==0))
			{
				$max=time();
				$min=$max-24*3600;
			}
			else
			{
				$ret["true_time"]	= 0;
				$ret["false_time"]	= 0;
				$ret["unknown_time"]	= 0;
				$ret["true"]		= 0;
				$ret["false"]		= 0;
				$ret["unknown"]		= 100;
				return $ret;
			}
		}

		$result=DBselect("select clock,value from events where triggerid=$triggerid and clock>=$min and clock<=$max");

		$state		= -1;
		$true_time	= 0;
		$false_time	= 0;
		$unknown_time	= 0;
		$time		= $min;

		if(($period_start==0)&&($period_end==0))
		{
			$max=time();
		}
		$rows=0;
		while($row=DBfetch($result))
		{
			$clock=$row["clock"];
			$value=$row["value"];

			$diff=$clock-$time;

			$time=$clock;

			if($state==-1)
			{
				$state=$value;
				if($state == 0)
				{
					$false_time+=$diff;
				}
				if($state == 1)
				{
					$true_time+=$diff;
				}
				if($state == 2)
				{
					$unknown_time+=$diff;
				}
			}
			else if($state==0)
			{
				$false_time+=$diff;
				$state=$value;
			}
			else if($state==1)
			{
				$true_time+=$diff;
				$state=$value;
			}
			else if($state==2)
			{
				$unknown_time+=$diff;
				$state=$value;
			}
			$rows++;
		}

		if($rows==0)
		{
			$trigger = get_trigger_by_triggerid($triggerid);
			$state = $trigger['value'];
		}
		
		if($state==0)
		{
			$false_time=$false_time+$max-$time;
		}
		elseif($state==1)
		{
			$true_time=$true_time+$max-$time;
		}
		elseif($state==3)
		{
			$unknown_time=$unknown_time+$max-$time;
		}

		$total_time=$true_time+$false_time+$unknown_time;

		if($total_time==0)
		{
			$ret["true_time"]	= 0;
			$ret["false_time"]	= 0;
			$ret["unknown_time"]	= 0;
			$ret["true"]		= 0;
			$ret["false"]		= 0;
			$ret["unknown"]		= 100;
		}
		else
		{
			$ret["true_time"]	= $true_time;
			$ret["false_time"]	= $false_time;
			$ret["unknown_time"]	= $unknown_time;
			$ret["true"]		= (100*$true_time)/$total_time;
			$ret["false"]		= (100*$false_time)/$total_time;
			$ret["unknown"]		= (100*$unknown_time)/$total_time;
		}
		return $ret;
	}

?>
