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
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
/*
 * Function: map_link_drawtypes
 *
 * Description:
 *     Return available drawing types for links
 *
 * Author:
 *     Eugene Grigorjev
 *
 */
	function map_link_drawtypes(){
		return array(
				MAP_LINK_DRAWTYPE_LINE,
				MAP_LINK_DRAWTYPE_BOLD_LINE,
				(function_exists('imagesetstyle') ? MAP_LINK_DRAWTYPE_DOT : null),
				MAP_LINK_DRAWTYPE_DASHED_LINE
			    );
	}

	function sysmap_element_types($type=null){
		$types = array(
			SYSMAP_ELEMENT_TYPE_HOST => S_HOST,
			SYSMAP_ELEMENT_TYPE_HOST_GROUP => S_HOST_GROUP,
			SYSMAP_ELEMENT_TYPE_TRIGGER => S_TRIGGER,
			SYSMAP_ELEMENT_TYPE_MAP => S_MAP,
			SYSMAP_ELEMENT_TYPE_IMAGE => S_IMAGE,
		);

		if(is_null($type)){
			natsort($types);
			return $types;
		}
		else if(isset($types[$type]))
			return $types[$type];
		else
			return S_UNKNOWN;
	}

	function sysmapElementLabel($label=null){
		$labels = array(
			MAP_LABEL_TYPE_LABEL => _('Label'),
			MAP_LABEL_TYPE_IP => _('IP address'),
			MAP_LABEL_TYPE_NAME => _('Element name'),
			MAP_LABEL_TYPE_STATUS => _('Status only'),
			MAP_LABEL_TYPE_NOTHING => _('Nothing'),
			MAP_LABEL_TYPE_CUSTOM => _('Custom label')
		);

		if(is_null($label)){
			return $labels;
		}
		else if(isset($labels[$label]))
			return $labels[$label];
		else
			return false;
	}

/*
 * Function: map_link_drawtype2str
 *
 * Description:
 *     Represent integer value of links drawing type into the string
 *
 * Author:
 *     Eugene Grigorjev
 *
 */
	function map_link_drawtype2str($drawtype){
		switch($drawtype){
			case MAP_LINK_DRAWTYPE_LINE:		$drawtype = S_LINE;			break;
			case MAP_LINK_DRAWTYPE_BOLD_LINE:	$drawtype = S_BOLD_LINE;	break;
			case MAP_LINK_DRAWTYPE_DOT:			$drawtype = S_DOT;			break;
			case MAP_LINK_DRAWTYPE_DASHED_LINE:	$drawtype = S_DASHED_LINE;	break;
			default: $drawtype = S_UNKNOWN;		break;
		}
	return $drawtype;
	}

	function getActionMapBySysmap($sysmap){
		$action_map = new CAreaMap('links'.$sysmap['sysmapid']);

		$hostids = array();
		foreach($sysmap['selements'] as $snum => $selement){
			if($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST)
				$hostids[$selement['elementid']] = $selement['elementid'];
		}

		$scripts_by_hosts = API::Script()->getScriptsByHosts($hostids);

		$options = array(
			'nodeids' => get_current_nodeid(true),
			'hostids' => $hostids,
			'output' => API_OUTPUT_EXTEND,
			'nopermissions' => 1
		);
		$hosts = API::Host()->get($options);
		$hosts = zbx_toHash($hosts, 'hostid');

// Draws elements
		$map_info = getSelementsInfo($sysmap);
//SDII($map_info);

		foreach($sysmap['selements'] as $snum => $db_element){
			$links_menus = '';
			$menus = '';

			if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST){
				$host = $hosts[$db_element['elementid']];
				if($host['status'] == HOST_STATUS_MONITORED){
					$host_nodeid = id2nodeid($db_element['elementid']);
					$tools_menus = '';
					foreach($scripts_by_hosts[$db_element['elementid']] as $id => $script){
						$script_nodeid = id2nodeid($script['scriptid']);

						if((bccomp($host_nodeid ,$script_nodeid ) == 0)){
							$str_tmp = zbx_jsvalue('javascript: executeScript('.$db_element['elementid'].', '.
									$script['scriptid'].', '.
									zbx_jsvalue($script['confirmation']).')'
							);

							$tools_menus.= "[".zbx_jsvalue($script['name']).", ".$str_tmp.", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
						}
					}

					if(!empty($tools_menus)){
						$menus .= "['".S_TOOLS."',null,null,{'outer' : ['pum_oheader'],'inner' : ['pum_iheader']}],";
						$menus .= $tools_menus;
					}
					$links_menus .= "['".S_STATUS_OF_TRIGGERS."',\"javascript: redirect('tr_status.php?hostid=".$db_element['elementid']."');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
				}
			}
			else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_MAP){
				$links_menus .= "['".S_SUBMAP."',\"javascript: redirect('maps.php?sysmapid=".$db_element['elementid']."');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
			}
			else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_TRIGGER){
				$links_menus.= "['".S_LATEST_EVENTS."',\"javascript: redirect('events.php?source=0&triggerid=".$db_element['elementid']."&nav_time=".(time()-7*86400)."');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
			}
			else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST_GROUP){
				$links_menus.= "['".S_STATUS_OF_TRIGGERS."',\"javascript: redirect('tr_status.php?hostid=0&groupid=".$db_element['elementid']."');\", null,{'outer' : ['pum_o_item'],'inner' : ['pum_i_item']}],";
			}

			if(!empty($links_menus)){
				$menus .= "['".S_GO_TO."',null,null,{'outer' : ['pum_oheader'],'inner' : ['pum_iheader']}],";
				$menus .= $links_menus;
			}

			if(!empty($db_element['urls'])){
				order_result($db_element['urls'], 'name');
				$menus .= "['".S_LINKS."',null,null,{'outer' : ['pum_oheader'],'inner' : ['pum_iheader']}],";
				foreach($db_element['urls'] as $url){
					$menus.= "[".zbx_jsvalue($url['name']).",".zbx_jsvalue($url['url']).", 'nosid'],";
				}
			}

			$menus = trim($menus,',');
			$menus = 'show_popup_menu(event,['.$menus.'],180); cancelEvent(event);';

			$back = get_png_by_selement($db_element, $map_info[$db_element['selementid']]);
			if(!$back) continue;
			$r_area = new CArea(
				array(
					$db_element['x'],
					$db_element['y'],
					$db_element['x'] + imagesx($back),
					$db_element['y'] + imagesy($back)),
				'', '', 'rect'
			);

			if(!empty($menus))
				$r_area->addAction('onclick', 'javascript: '.$menus);

			$action_map->addItem($r_area);
		}

		$jsmenu = new CPUMenu(null,170);
		$jsmenu->InsertJavaScript();

	return $action_map;
	}

	function get_icon_center_by_selement($element, $info=null){

		$x = $element['x'];
		$y = $element['y'];

		$image = get_png_by_selement($element, $info);
		if($image){
			$x += imagesx($image) / 2;
			$y += imagesy($image) / 2;
		}

	return array($x, $y);
	}

	function MyDrawLine($image,$x1,$y1,$x2,$y2,$color,$drawtype){
		if($drawtype == MAP_LINK_DRAWTYPE_BOLD_LINE){
			imageline($image,$x1,$y1,$x2,$y2,$color);
			if(abs($x1-$x2) < abs($y1-$y2)){
				$x1++;
				$x2++;
			}
			else{
				$y1++;
				$y2++;
			}

			imageline($image,$x1,$y1,$x2,$y2,$color);
		}
		else if($drawtype == MAP_LINK_DRAWTYPE_DASHED_LINE){
			if(function_exists('imagesetstyle')){
/* Use imagesetstyle+imageline instead of bugged ImageDashedLine */
				$style = array(
					$color, $color, $color, $color,
					IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT
					);

				imagesetstyle($image, $style);
				imageline($image,$x1,$y1,$x2,$y2,IMG_COLOR_STYLED);
			}
			else{
				ImageDashedLine($image,$x1,$y1,$x2,$y2,$color);
			}
		}
		else if($drawtype == MAP_LINK_DRAWTYPE_DOT && function_exists('imagesetstyle')){
			$style = array($color,IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT);
			imagesetstyle($image, $style);
			imageline($image,$x1,$y1,$x2,$y2,IMG_COLOR_STYLED);
		}
		else{
			imageline($image,$x1,$y1,$x2,$y2,$color);
		}
	}

	function get_png_by_selement($selement, $info){

		switch($info['icon_type']){
			case SYSMAP_ELEMENT_ICON_ON:
				$info['iconid'] = $selement['iconid_on'];
				break;
			case SYSMAP_ELEMENT_ICON_MAINTENANCE:
				$info['iconid'] = $selement['iconid_maintenance'];
				break;
			case SYSMAP_ELEMENT_ICON_DISABLED:
				$info['iconid'] = $selement['iconid_disabled'];
				break;
			case SYSMAP_ELEMENT_ICON_OFF:
			default:
// element image
				$info['iconid'] = $selement['iconid_off'];
				break;
		}

// Process for default icons
		if($info['iconid'] == 0) $info['iconid'] = $selement['iconid_off'];
//------

		$image = get_image_by_imageid($info['iconid']);

		if(!$image){
			return get_default_image(true);
		}

	return imagecreatefromstring($image['image']);
	}

	function get_selement_iconid($selement, $info=null){
		if($selement['selementid'] > 0){
			if(is_null($info)){
// get sysmap
				$options = array(
					'sysmapids' => $selement['sysmapid'],
					'output' => API_OUTPUT_EXTEND
				);
				$sysmaps = API::Map()->get($options);
				$sysmap = reset($sysmaps);
				$sysmap['selements'] = array($selement);

				$map_info = getSelementsInfo($sysmap);
				$info = reset($map_info);
//-----
			}
//SDI($info);

			switch($info['icon_type']){
				case SYSMAP_ELEMENT_ICON_OFF:
					$info['iconid'] = $selement['iconid_off'];
					break;
				case SYSMAP_ELEMENT_ICON_ON:
					$info['iconid'] = $selement['iconid_on'];
					break;
				case SYSMAP_ELEMENT_ICON_MAINTENANCE:
					$info['iconid'] = $selement['iconid_maintenance'];
					break;
			}

// Process for default icons
			if($info['iconid'] == 0) $info['iconid'] = $selement['iconid_off'];
//------
		}
		else{
			$info['iconid'] = $selement['iconid_off'];
		}

	return $info['iconid'];
	}

	function get_selement_icons(){
// ICONS
		$el_form_menu = array();
		$el_form_menu['icons'] = array();

		$result = DBselect('SELECT * FROM images WHERE imagetype=1 AND '.DBin_node('imageid').' ORDER BY name');
		while($row=DBfetch($result)){
			$row['name'] = get_node_name_by_elid($row['imageid']).$row['name'];
			$el_form_menu['icons'][$row['imageid']] = $row['name'];
		}

		$menu = 'var zbxSelementIcons = '.zbx_jsvalue($el_form_menu, true).';';

	return $menu;
	}

	function convertColor($im,$color){

		$RGB = array(
			hexdec('0x'.substr($color, 0,2)),
			hexdec('0x'.substr($color, 2,2)),
			hexdec('0x'.substr($color, 4,2))
			);


	return imagecolorallocate($im,$RGB[0],$RGB[1],$RGB[2]);
	}

	function expandMapLabels(&$map){
		foreach($map['selements'] as $snum => $selement){
			$map['selements'][$snum]['label_expanded'] = expand_map_element_label_by_data($selement);
		}

		foreach($map['links'] as $lnum => $link){
			$map['links'][$lnum]['label_expanded'] = expand_map_element_label_by_data(null, $link);
		}
	}
/*
 * Function: expand_map_element_label_by_data
 *
 * Description:
 *     substitute simple macros {HOSTNAME}, {HOST.CONN}, {HOST.DNS}, {IPADDRESS} and
 *     functions {hostname:key.min/max/avg/last(...)}
 *     in data string with real values
 *
 * Author:
 *     Aleksander Vladishev
 *
 */
	function expand_map_element_label_by_data($db_element, $link = null){
		$label = !is_null($db_element) ? $db_element['label'] : $link['label'];

		if(!is_null($db_element)){
			switch($db_element['elementtype']){
				case SYSMAP_ELEMENT_TYPE_HOST:
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					while(zbx_strstr($label, '{HOSTNAME}') ||
							zbx_strstr($label, '{HOST.DNS}') ||
							zbx_strstr($label, '{IPADDRESS}') ||
							zbx_strstr($label, '{HOST.CONN}'))
					{
						if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST){
							$sql = 'SELECT hi.*, h.host '.
									' FROM interface hi,hosts h '.
									' WHERE hi.hostid=h.hostid '.
										' AND hi.hostid='.$db_element['elementid'];
						}
						else if($db_element['elementtype'] == SYSMAP_ELEMENT_TYPE_TRIGGER)
							$sql =	'SELECT hi.*, h.host '.
								' FROM interface hi,items i,functions f,hosts h '.
								' WHERE h.hostid=hi.hostid '.
									' AND hi.hostid=i.hostid '.
									' AND i.itemid=f.itemid '.
									' AND f.triggerid='.$db_element['elementid'];
						else{
	// Should never be here
						}

						$db_hosts = DBselect($sql);

						if($db_host = DBfetch($db_hosts)){
							if(zbx_strstr($label, '{HOSTNAME}')){
								$label = str_replace('{HOSTNAME}', $db_host['host'], $label);
							}

							if(zbx_strstr($label, '{HOST.DNS}')){
								$label = str_replace('{HOST.DNS}', $db_host['dns'], $label);
							}

							if(zbx_strstr($label, '{IPADDRESS}')){
								$label = str_replace('{IPADDRESS}', $db_host['ip'], $label);
							}

							if(zbx_strstr($label, '{HOST.CONN}')){
								$label = str_replace('{HOST.CONN}', $db_host['useip'] ? $db_host['ip'] : $db_host['dns'], $label);
							}
						}
					}
				break;
			}

			switch($db_element['elementtype']){
				case SYSMAP_ELEMENT_TYPE_HOST:
				case SYSMAP_ELEMENT_TYPE_MAP:
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					while(zbx_strstr($label, '{TRIGGERS.UNACK}')){
						$label = str_replace('{TRIGGERS.UNACK}', get_triggers_unacknowledged($db_element), $label);
					}
					while(zbx_strstr($label, '{TRIGGERS.PROBLEM.UNACK}')){
						$label = str_replace('{TRIGGERS.PROBLEM.UNACK}', get_triggers_unacknowledged($db_element, true), $label);
					}
					while(zbx_strstr($label, '{TRIGGER.EVENTS.UNACK}')){
						$label = str_replace('{TRIGGER.EVENTS.UNACK}', get_events_unacknowledged($db_element), $label);
					}
					while(zbx_strstr($label, '{TRIGGER.EVENTS.PROBLEM.UNACK}')){
						$label = str_replace('{TRIGGER.EVENTS.PROBLEM.UNACK}', get_events_unacknowledged($db_element, null, TRIGGER_VALUE_TRUE), $label);
					}
					while(zbx_strstr($label, '{TRIGGER.PROBLEM.EVENTS.PROBLEM.UNACK}')){
						$label = str_replace('{TRIGGER.PROBLEM.EVENTS.PROBLEM.UNACK}', get_events_unacknowledged($db_element, TRIGGER_VALUE_TRUE, TRIGGER_VALUE_TRUE), $label);
					}
					while(zbx_strstr($label, '{TRIGGERS.ACK}')){
						$label = str_replace('{TRIGGERS.ACK}', get_triggers_unacknowledged($db_element, null, true), $label);
					}
					while(zbx_strstr($label, '{TRIGGERS.PROBLEM.ACK}')){
						$label = str_replace('{TRIGGERS.PROBLEM.ACK}', get_triggers_unacknowledged($db_element, true, true), $label);
					}
					while(zbx_strstr($label, '{TRIGGER.EVENTS.ACK}')){
						$label = str_replace('{TRIGGER.EVENTS.ACK}', get_events_unacknowledged($db_element, null, null, true), $label);
					}
					while(zbx_strstr($label, '{TRIGGER.EVENTS.PROBLEM.ACK}')){
						$label = str_replace('{TRIGGER.EVENTS.PROBLEM.ACK}', get_events_unacknowledged($db_element, null, TRIGGER_VALUE_TRUE, true), $label);
					}
					while(zbx_strstr($label, '{TRIGGER.PROBLEM.EVENTS.PROBLEM.ACK}')){
						$label = str_replace('{TRIGGER.PROBLEM.EVENTS.PROBLEM.ACK}', get_events_unacknowledged($db_element, TRIGGER_VALUE_TRUE, TRIGGER_VALUE_TRUE, true), $label);
					}
					break;
			}
		}

		$pattern = "/{".ZBX_PREG_HOST_FORMAT.":.+\.\w+\(.+\)}/Uu";

		preg_match_all($pattern, $label, $matches);
		foreach($matches[0] as $num => $expr){
			$trigExpr = new CTriggerExpression(array('expression' => $expr));
			if(!empty($trigExpr->errors)) continue;

			$host = reset($trigExpr->data['hosts']);
			$key = reset($trigExpr->data['items']);
			$function = reset($trigExpr->data['functions']);
			$parameter = reset($trigExpr->data['functionParams']);

			$db_items = API::Item()->get(array(
				'filter' => array(
					'host' => $host,
					'key_' => $key,
					'flags' => array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_CREATED),
				),
				'output' => API_OUTPUT_EXTEND
			));
			$db_item = reset($db_items);

			if(!$db_item){
				$label = str_replace($expr, '???', $label);
				continue;
			}

			switch($db_item['value_type']){
				case ITEM_VALUE_TYPE_FLOAT:
					$history_table = 'history';
					$order_field = 'clock';
					break;
				case ITEM_VALUE_TYPE_UINT64:
					$history_table = 'history_uint';
					$order_field = 'clock';
					break;
				case ITEM_VALUE_TYPE_TEXT:
					$history_table = 'history_text';
					$order_field = 'id';
					break;
				case ITEM_VALUE_TYPE_LOG:
					$history_table = 'history_log';
					$order_field = 'id';
					break;
				default:
// ITEM_VALUE_TYPE_STR
					$history_table = 'history_str';
					$order_field = 'clock';
			}

			if(0 == strcmp($function, 'last')){
				$sql = 'SELECT value '.
						' FROM '.$history_table.
						' WHERE itemid='.$db_item['itemid'].
						' ORDER BY '.$order_field.' DESC';

				$result = DBselect($sql, 1);
				if(NULL == ($row = DBfetch($result)))
					$label = str_replace($expr, '('.S_NO_DATA_SMALL.')', $label);
				else{
					switch($db_item['value_type']){
						case ITEM_VALUE_TYPE_FLOAT:
						case ITEM_VALUE_TYPE_UINT64:
							$value = convert_units($row['value'], $db_item['units']);
							break;
						default:
							$value = $row['value'];
					}

					$label = str_replace($expr, $value, $label);
				}
			}
			else if((0 == strcmp($function, 'min')) || (0 == strcmp($function, 'max')) || (0 == strcmp($function, 'avg'))){

				if($db_item['value_type'] != ITEM_VALUE_TYPE_FLOAT && $db_item['value_type'] != ITEM_VALUE_TYPE_UINT64){
					$label = str_replace($expr, '???', $label);
					continue;
				}

				$now = time(NULL) - $parameter;
				$sql = 'SELECT '.$function.'(value) as value '.
						' FROM '.$history_table.
						' WHERE clock>'.$now.
							' AND itemid='.$db_item['itemid'];

				$result = DBselect($sql);
				if(NULL == ($row = DBfetch($result)) || is_null($row['value']))
					$label = str_replace($expr, '('.S_NO_DATA_SMALL.')', $label);
				else
					$label = str_replace($expr, convert_units($row['value'], $db_item['units']), $label);
			}
			else{
				$label = str_replace($expr, '???', $label);
				continue;
			}
		}

	return $label;
	}

	function get_map_elements($db_element, &$elements){
		switch($db_element['elementtype']){
			case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
				$elements['hosts_groups'][] = $db_element['elementid'];
				break;
			case SYSMAP_ELEMENT_TYPE_HOST:
				$elements['hosts'][] = $db_element['elementid'];
				break;
			case SYSMAP_ELEMENT_TYPE_TRIGGER:
				$elements['triggers'][] = $db_element['elementid'];
				break;
			case SYSMAP_ELEMENT_TYPE_MAP:
				$sql = 'SELECT DISTINCT elementtype,elementid'.
						' FROM sysmaps_elements'.
						' WHERE sysmapid='.$db_element['elementid'];
				$db_mapselements = DBselect($sql);
				while($db_mapelement = DBfetch($db_mapselements)){
					get_map_elements($db_mapelement, $elements);
				}
				break;
		}
	}

	function add_elementNames(&$selements){
		$hostids = array();
		$triggerids = array();
		$mapids = array();
		$hostgroupids = array();

		foreach($selements as $snum => $selement){
			switch($selement['elementtype']){
				case SYSMAP_ELEMENT_TYPE_HOST:
					$hostids[] = $selement['elementid'];
					break;
				case SYSMAP_ELEMENT_TYPE_MAP:
					$mapids[] = $selement['elementid'];
					break;
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					$triggerids[] = $selement['elementid'];
					break;
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					$hostgroupids[] = $selement['elementid'];
					break;
				case SYSMAP_ELEMENT_TYPE_IMAGE:
				default:
					break;
			}
		}


		$hosts = API::Host()->get(array('hostids'=>$hostids, 'output'=>API_OUTPUT_EXTEND, 'nopermissions'=>1, 'nodeids' => get_current_nodeid(true)));
		$hosts = zbx_toHash($hosts, 'hostid');

		$maps = API::Map()->get(array('mapids'=>$mapids, 'output'=>API_OUTPUT_EXTEND, 'nopermissions'=>1, 'nodeids' => get_current_nodeid(true)));
		$maps = zbx_toHash($maps, 'sysmapid');

		$triggers = API::Trigger()->get(array('triggerids'=>$triggerids, 'output'=>API_OUTPUT_EXTEND, 'nopermissions'=>1, 'nodeids' => get_current_nodeid(true)));
		$triggers = zbx_toHash($triggers, 'triggerid');

		$hostgroups = API::HostGroup()->get(array('hostgroupids'=>$hostgroupids, 'output'=>API_OUTPUT_EXTEND, 'nopermissions'=>1, 'nodeids' => get_current_nodeid(true)));
		$hostgroups = zbx_toHash($hostgroups, 'groupid');

		foreach($selements as $snum => $selement){
			switch($selement['elementtype']){
				case SYSMAP_ELEMENT_TYPE_HOST:
					$selements[$snum]['elementName'] = $hosts[$selement['elementid']]['host'];
					break;
				case SYSMAP_ELEMENT_TYPE_MAP:
					$selements[$snum]['elementName'] = $maps[$selement['elementid']]['name'];
					break;
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					$selements[$snum]['elementName'] = expand_trigger_description_by_data($triggers[$selement['elementid']]);
					break;
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					$selements[$snum]['elementName'] = $hostgroups[$selement['elementid']]['name'];
					break;
				case SYSMAP_ELEMENT_TYPE_IMAGE:
				default:
					$selements[$snum]['elementName'] = 'image';
			}
		}
	}

//------------------------------------

 	function getTriggersInfo($selement, $i){
		global $colors;

		$info = array(
			'latelyChanged' => $i['latelyChanged'],
			'ack' => $i['ack'],
			'priority' => $i['priority'],
		);

		if($i['problem']){
			$info['iconid'] = $selement['iconid_on'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_ON;
			$info['info'] = array();
			$info['info']['unack'] = array(
				'msg' => S_PROBLEM_BIG,
				'color' => ($i['priority'] > 3) ? $colors['Red'] : $colors['Dark Red']
			);
		}
		else if($i['trigger_disabled']){
			$info['iconid'] = $selement['iconid_disabled'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_DISABLED;
			$info['info'] = array(
				'status' => array(
					'msg' => S_DISABLED_BIG,
					'color' => $colors['Dark Red']
				)
			);
		}
		else{
			$info['iconid'] = $selement['iconid_off'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_OFF;
			$info['info'] = array(
				'unknown' => array(
					'msg' => S_OK_BIG,
					'color' => $colors['Dark Green'],
				)
			);
		}

		return $info;
	}

 	function getHostsInfo($selement, $i, $show_unack){
		global $colors;

		$info = array(
			'latelyChanged' => $i['latelyChanged'],
			'ack' => $i['ack'],
			'priority' => $i['priority'],
			'info' => array(),
		);
		$has_problem = false;

		if($i['problem']){
			$info['iconid'] = $selement['iconid_on'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_ON;

			if(in_array($show_unack, array(EXTACK_OPTION_ALL, EXTACK_OPTION_BOTH))){
				if($i['problem'] > 1)
					$msg = $i['problem'].' '.S_PROBLEMS;
				else if(isset($i['problem_title']))
					$msg = $i['problem_title'];
				else
					$msg = '1 '.S_PROBLEM;

				$info['info']['problem'] = array(
					'msg' => $msg,
					'color' => ($i['priority'] > 3) ? $colors['Red'] : $colors['Dark Red']
				);
			}

			if(in_array($show_unack, array(EXTACK_OPTION_UNACK, EXTACK_OPTION_BOTH)) && $i['problem_unack']){
				$info['info']['unack'] = array(
					'msg' => $i['problem_unack'] . ' '.S_UNACKNOWLEDGED,
					'color' => $colors['Dark Red']
				);
			}

			if($i['unknown']){
				$info['info']['unknown'] = array(
					'msg' => $i['unknown'] . ' ' . S_UNKNOWN,
					'color' => $colors['Gray']
				);
			}
			$has_problem = true;
		}

		if($i['maintenance']){
			$info['iconid'] = $selement['iconid_maintenance'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_MAINTENANCE;
			$info['info']['maintenance'] = array(
				'msg' => S_MAINTENANCE_BIG . ' ('.$i['maintenance_title'] . ')',
				'color' => $colors['Orange'],
			);
		}
		else if($i['disabled']){
			$info['iconid'] = $selement['iconid_disabled'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_DISABLED;
			$info['info']['status'] = array(
				'msg' => S_DISABLED_BIG,
				'color' => $colors['Dark Red']
			);
		}
		else if(!$has_problem){
			$info['iconid'] = $selement['iconid_off'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_OFF;
			$info['info']['unknown'] = array(
				'msg' => S_OK_BIG,
				'color' => $colors['Dark Green'],
			);
		}

		return $info;
	}

 	function getHostGroupsInfo($selement, $i, $show_unack){
		global $colors;

		$info = array(
			'latelyChanged' => $i['latelyChanged'],
			'ack' => $i['ack'],
			'priority' => $i['priority'],
			'info' => array(),
		);
		$has_problem = false;
		$has_status = false;

		if($i['problem']){
			$info['iconid'] = $selement['iconid_on'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_ON;

			if(in_array($show_unack, array(EXTACK_OPTION_ALL, EXTACK_OPTION_BOTH))){
				if($i['problem'] > 1)
					$msg = $i['problem'].' '.S_PROBLEMS;
				else if(isset($i['problem_title']))
					$msg = $i['problem_title'];
				else
					$msg = '1 '.S_PROBLEM;

				$info['info']['problem'] = array(
					'msg' => $msg,
					'color' => ($i['priority'] > 3) ? $colors['Red'] : $colors['Dark Red']
				);
			}

			if(in_array($show_unack, array(EXTACK_OPTION_UNACK, EXTACK_OPTION_BOTH)) && $i['problem_unack']){
				$info['info']['unack'] = array(
					'msg' => $i['problem_unack'] . ' '.S_UNACKNOWLEDGED,
					'color' => $colors['Dark Red']
				);
			}

			if($i['unknown']){
				$info['info']['unknown'] = array(
					'msg' => $i['unknown'] . ' ' . S_UNKNOWN,
					'color' => $colors['Gray']
				);
			}
			$has_problem = true;
		}

		if($i['maintenance']){
			if(!$has_problem){
				$info['iconid'] = $selement['iconid_maintenance'];
				$info['icon_type'] = SYSMAP_ELEMENT_ICON_MAINTENANCE;
			}
			$info['info']['maintenance'] = array(
				'msg' => $i['maintenance'] . ' ' .S_MAINTENANCE,
				'color' => $colors['Orange'],
			);
			$has_status = true;
		}
		else if($i['disabled']){
			if(!$has_problem){
				$info['icon_type'] = SYSMAP_ELEMENT_ICON_DISABLED;
				$info['iconid'] = $selement['iconid_disabled'];
			}
			$info['info']['disabled'] = array(
				'msg' => S_DISABLED_BIG,
				'color' => $colors['Dark Red']
			);
			$has_status = true;
		}

		if(!$has_status && !$has_problem){
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_OFF;
			$info['iconid'] = $selement['iconid_off'];
			$info['info']['unknown'] = array(
				'msg' => S_OK_BIG,
				'color' => $colors['Dark Green'],
			);
		}
		return $info;
	}

 	function getMapsInfo($selement, $i, $show_unack){
		global $colors;

		$info = array(
			'latelyChanged' => $i['latelyChanged'],
			'ack' => $i['ack'],
			'priority' => $i['priority'],
			'info' => array(),
		);

		$has_problem = false;
		$has_status = false;

		if($i['problem']){
			$info['iconid'] = $selement['iconid_on'];
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_ON;
			if(in_array($show_unack, array(EXTACK_OPTION_ALL, EXTACK_OPTION_BOTH))){
				if($i['problem'] > 1)
					$msg = $i['problem'].' '.S_PROBLEMS;
				else if(isset($i['problem_title']))
					$msg = $i['problem_title'];
				else
					$msg = '1 '.S_PROBLEM;

				$info['info']['problem'] = array(
					'msg' => $msg,
					'color' => ($i['priority'] > 3) ? $colors['Red'] : $colors['Dark Red']
				);
			}

			if(in_array($show_unack, array(EXTACK_OPTION_UNACK, EXTACK_OPTION_BOTH)) && $i['problem_unack']){
				$info['info']['unack'] = array(
					'msg' => $i['problem_unack'] . ' '.S_UNACKNOWLEDGED,
					'color' => $colors['Dark Red']
				);
			}

			if($i['unknown']){
				$info['info']['unknown'] = array(
					'msg' => $i['unknown'] . ' ' . S_UNKNOWN,
					'color' => $colors['Gray']
				);
			}
			$has_problem = true;
		}

		if($i['maintenance']){
			if(!$has_problem){
				$info['iconid'] = $selement['iconid_maintenance'];
				$info['icon_type'] = SYSMAP_ELEMENT_ICON_MAINTENANCE;
			}
			$info['info']['maintenance'] = array(
				'msg' => $i['maintenance'] . ' ' .S_MAINTENANCE,
				'color' => $colors['Orange'],
			);
			$has_status = true;
		}
		else if($i['disabled']){
			if(!$has_problem){
				$info['icon_type'] = SYSMAP_ELEMENT_ICON_DISABLED;
				$info['iconid'] = $selement['iconid_disabled'];
			}
			$info['info']['disabled'] = array(
				'msg' => S_DISABLED_BIG,
				'color' => $colors['Dark Red']
			);
			$has_status = true;
		}

		if(!$has_status && !$has_problem){
			$info['icon_type'] = SYSMAP_ELEMENT_ICON_OFF;
			$info['iconid'] = $selement['iconid_off'];
			$info['info']['unknown'] = array(
				'msg' => S_OK_BIG,
				'color' => $colors['Dark Green'],
			);
		}

		return $info;
	}

	function getImagesInfo($selement){
		$info = array(
			'iconid' => $selement['iconid_off'],
			'icon_type' => SYSMAP_ELEMENT_ICON_OFF,
			'name' => S_IMAGE,
			'latelyChanged' => false,
		);

		return $info;
	}

	function getSelementsInfo($sysmap){
		$config = select_config();
		$show_unack = $config['event_ack_enable'] ? $sysmap['show_unack'] : EXTACK_OPTION_ALL;

		$triggers_map = array();
		$triggers_map_submaps = array();
		$hostgroups_map = array();
		$hosts_map = array();

		$selements = zbx_toHash($sysmap['selements'], 'selementid');
		foreach($selements as $selementid => $selement){
			$selements[$selementid]['hosts'] = array();
			$selements[$selementid]['triggers'] = array();

			switch($selement['elementtype']){
				case SYSMAP_ELEMENT_TYPE_MAP:
					$mapids = array($selement['elementid']);

					while(!empty($mapids)){
						$options = array(
							'sysmapids' => $mapids,
							'output' => API_OUTPUT_EXTEND,
							'select_selements' => API_OUTPUT_EXTEND,
							'nopermissions' => 1,
							'nodeids' => get_current_nodeid(true)
						);
						$maps = API::Map()->get($options);

						$mapids = array();
						foreach($maps as $map){
							foreach($map['selements'] as $sel){
								switch($sel['elementtype']){
									case SYSMAP_ELEMENT_TYPE_MAP:
										$mapids[] = $sel['elementid'];
									break;
									case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
										$hostgroups_map[$sel['elementid']][$selementid] = $selementid;
									break;
									case SYSMAP_ELEMENT_TYPE_HOST:
										$hosts_map[$sel['elementid']][$selementid] = $selementid;
									break;
									case SYSMAP_ELEMENT_TYPE_TRIGGER:
										$triggers_map_submaps[$sel['elementid']][$selementid] = $selementid;
									break;
								}
							}
						}
					}
				break;
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					$hostgroups_map[$selement['elementid']][$selement['selementid']] = $selement['selementid'];
				break;
				case SYSMAP_ELEMENT_TYPE_HOST:
					$hosts_map[$selement['elementid']][$selement['selementid']] = $selement['selementid'];
				break;
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					$triggers_map[$selement['elementid']][$selement['selementid']] = $selement['selementid'];
				break;
			}
		}


// get hosts data {{{
		$all_hosts = array();
		if(!empty($hosts_map)){
			$options = array(
				'hostids' => array_keys($hosts_map),
				'output' => API_OUTPUT_EXTEND,
				'nopermissions' => 1,
				'nodeids' => get_current_nodeid(true),
			);
			$hosts = API::Host()->get($options);
			$all_hosts = array_merge($all_hosts, $hosts);
			foreach($hosts as $host){
				foreach($hosts_map[$host['hostid']] as $belongs_to_sel){
					$selements[$belongs_to_sel]['hosts'][$host['hostid']] = $host['hostid'];
				}
			}
		}

		if(!empty($hostgroups_map)){
			$options = array(
				'groupids' => array_keys($hostgroups_map),
				'output' => API_OUTPUT_EXTEND,
				'nopermissions' => 1,
				'nodeids' => get_current_nodeid(true),
			);
			$hosts = API::Host()->get($options);
			$all_hosts = array_merge($all_hosts, $hosts);
			foreach($hosts as $host){
				foreach($host['groups'] as $group){
					foreach($hostgroups_map[$group['groupid']] as $belongs_to_sel){
						$selements[$belongs_to_sel]['hosts'][$host['hostid']] = $host['hostid'];

// add hosts to hosts_map for trigger selection;
						if(!isset($hosts_map[$host['hostid']])) $hosts_map[$host['hostid']] = array();
						$hosts_map[$host['hostid']][$belongs_to_sel] = $belongs_to_sel;
					}
				}
			}
		}
		$all_hosts = zbx_toHash($all_hosts, 'hostid');

		$monitored_hostids = array();
		foreach($all_hosts as $hostid => $host){
			if(($host['status'] == HOST_STATUS_MONITORED))
				$monitored_hostids[$hostid] = $hostid;
		}
// }}}


// get triggers data {{{
		$all_triggers = array();
// triggers from current map, select all

		if(!empty($triggers_map)){
			$options = array(
				'nodeids' => get_current_nodeid(true),
				'triggerids' => array_keys($triggers_map),
				'filter' => array(
					'value_flags' => null
				),
				'output' => API_OUTPUT_EXTEND,
				'nopermissions' => 1
			);
			$triggers = API::Trigger()->get($options);
			$all_triggers = array_merge($all_triggers, $triggers);

			foreach($triggers as $trigger){
				foreach($triggers_map[$trigger['triggerid']] as $belongs_to_sel){
					$selements[$belongs_to_sel]['triggers'][$trigger['triggerid']] = $trigger['triggerid'];
				}
			}
		}

// triggers from submaps, skip dependent
		if(!empty($triggers_map_submaps)){
			$options = array(
				'nodeids' => get_current_nodeid(true),
				'triggerids' => array_keys($triggers_map_submaps),
				'filter' => array(
//					'value' => TRIGGER_VALUE_TRUE,
					'value_flags' => null
				),
				'skipDependent' => 1,
				'output' => API_OUTPUT_EXTEND,
				'nopermissions' => 1,
			);
			$triggers = API::Trigger()->get($options);
			$all_triggers = array_merge($all_triggers, $triggers);
			foreach($triggers as $trigger){
				foreach($triggers_map_submaps[$trigger['triggerid']] as $belongs_to_sel){
					$selements[$belongs_to_sel]['triggers'][$trigger['triggerid']] = $trigger['triggerid'];
				}
			}
		}


// triggers from all hosts/hostgroups, skip dependent
		if(!empty($monitored_hostids)){
			$options = array(
				'hostids' => $monitored_hostids,
				'output' => API_OUTPUT_EXTEND,
				'nopermissions' => 1,
				'filter' => array(
//					'value' => TRIGGER_VALUE_TRUE,
					'value_flags' => null,
				),
				'nodeids' => get_current_nodeid(true),
				'monitored' => true,
				'skipDependent' => 1,
			);
			$triggers = API::Trigger()->get($options);

			$all_triggers = array_merge($all_triggers, $triggers);
			foreach($triggers as $trigger){
				foreach($trigger['hosts'] as $host){
					foreach($hosts_map[$host['hostid']] as $belongs_to_sel){
						$selements[$belongs_to_sel]['triggers'][$trigger['triggerid']] = $trigger['triggerid'];
					}
				}
			}
		}
		$all_triggers = zbx_toHash($all_triggers, 'triggerid');

		$options = array(
			'triggerids' => array_keys($all_triggers),
			'withLastEventUnacknowledged' => true,
			'output' => API_OUTPUT_SHORTEN,
			'nodeids' => get_current_nodeid(true),
			'nopermissions' => 1,
			'monitored' => true,
			'filter' => array(
				'value' => TRIGGER_VALUE_TRUE,
				'value_flags' => null
			),
		);
		$unack_triggerids = API::Trigger()->get($options);
		$unack_triggerids = zbx_toHash($unack_triggerids, 'triggerid');
// }}}


		$info = array();
		foreach($selements as $selementid => $selement){
			$i = array(
				'disabled' => 0,
				'maintenance' => 0,
				'problem' => 0,
				'problem_unack' => 0,
				'unknown' => 0,
				'priority' => 0,
				'trigger_disabled' => 0,
				'latelyChanged' => false,
				'ack' => true,
			);

			foreach($selement['hosts'] as $hostid){
				$host = $all_hosts[$hostid];
				$last_hostid = $hostid;

				if($host['status'] == HOST_STATUS_NOT_MONITORED)
					$i['disabled']++;
				else if($host['maintenance_status'] == HOST_MAINTENANCE_STATUS_ON)
					$i['maintenance']++;
			}

			foreach($selement['triggers'] as $triggerid){
				$trigger = $all_triggers[$triggerid];

				if($trigger['status'] == TRIGGER_STATUS_DISABLED){
					$i['trigger_disabled']++;
				}
				else{
					if($trigger['value'] == TRIGGER_VALUE_TRUE){
						$i['problem']++;
						$last_problemid = $triggerid;
						if($i['priority'] < $trigger['priority'])
							$i['priority'] = $trigger['priority'];
					}

					if(isset($unack_triggerids[$triggerid]))
						$i['problem_unack']++;

					$i['latelyChanged'] |= ((time() - $trigger['lastchange']) < TRIGGER_BLINK_PERIOD);
				}
			}

			$i['ack'] = (bool) !($i['problem_unack']);

			if($sysmap['expandproblem'] && ($i['problem'] == 1)){
				$i['problem_title'] = expand_trigger_description_by_data($all_triggers[$last_problemid]);
			}

			if(($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST) && ($i['maintenance'] == 1)){
				$mnt = get_maintenance_by_maintenanceid($all_hosts[$last_hostid]['maintenanceid']);
				$i['maintenance_title'] = $mnt['name'];
			}

			switch($selement['elementtype']){
				case SYSMAP_ELEMENT_TYPE_MAP:
					$info[$selementid] = getMapsInfo($selement, $i, $show_unack);
				break;
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					$info[$selementid] = getHostGroupsInfo($selement, $i, $show_unack);
				break;
				case SYSMAP_ELEMENT_TYPE_HOST:
					$info[$selementid] = getHostsInfo($selement, $i, $show_unack);
				break;
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					$info[$selementid] = getTriggersInfo($selement, $i);
				break;
				case SYSMAP_ELEMENT_TYPE_IMAGE:
					$info[$selementid] = getImagesInfo($selement);
				break;
			}
		}

		if($sysmap['label_format'] == SYSMAP_LABEL_ADVANCED_OFF){
			$hlabel = $hglabel = $tlabel = $mlabel = ($sysmap['label_type'] == MAP_LABEL_TYPE_NAME);
		}
		else{
			$hlabel = $sysmap['label_type_host'] == MAP_LABEL_TYPE_NAME;
			$hglabel = $sysmap['label_type_hostgroup'] == MAP_LABEL_TYPE_NAME;
			$tlabel = $sysmap['label_type_trigger'] == MAP_LABEL_TYPE_NAME;
			$mlabel = $sysmap['label_type_map'] == MAP_LABEL_TYPE_NAME;
		}

// get names if is needed
		$elems = separateMapElements($sysmap);
		if(!empty($elems['sysmaps']) && $mlabel){
			$maps = API::Map()->get(array(
				'sysmapids' => zbx_objectValues($elems['sysmaps'], 'elementid'),
				'nopermissions' => 1,
				'output' => API_OUTPUT_EXTEND,
			));
			$maps = zbx_toHash($maps, 'sysmapid');
			foreach($elems['sysmaps'] as $elem){
				$info[$elem['selementid']]['name'] = $maps[$elem['elementid']]['name'];
			}
		}
		if(!empty($elems['hostgroups']) && $hglabel){
			$hostgroups = API::HostGroup()->get(array(
				'groupids' => zbx_objectValues($elems['hostgroups'], 'elementid'),
				'nopermissions' => 1,
				'output' => API_OUTPUT_EXTEND,
			));
			$hostgroups = zbx_toHash($hostgroups, 'groupid');
			foreach($elems['hostgroups'] as $elem){
				$info[$elem['selementid']]['name'] = $hostgroups[$elem['elementid']]['name'];
			}
		}

		if(!empty($elems['triggers']) && $tlabel){
			foreach($elems['triggers'] as $elem){
				$info[$elem['selementid']]['name'] = expand_trigger_description_by_data($all_triggers[$elem['elementid']]);
			}
		}
		if(!empty($elems['hosts']) && $hlabel){
			foreach($elems['hosts'] as $elem){
				$info[$elem['selementid']]['name'] = $all_hosts[$elem['elementid']]['host'];;
			}
		}

		return $info;
	}

	function separateMapElements($sysmap){
		$elements = array(
			'sysmaps' => array(),
			'hostgroups' => array(),
			'hosts' => array(),
			'triggers' => array(),
			'images' => array()
		);

		foreach($sysmap['selements'] as $snum => $selement){
			switch($selement['elementtype']){
				case SYSMAP_ELEMENT_TYPE_MAP:
					$elements['sysmaps'][$selement['selementid']] = $selement;
				break;
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					$elements['hostgroups'][$selement['selementid']] = $selement;
				break;
				case SYSMAP_ELEMENT_TYPE_HOST:
					$elements['hosts'][$selement['selementid']] = $selement;
				break;
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					$elements['triggers'][$selement['selementid']] = $selement;
				break;
				case SYSMAP_ELEMENT_TYPE_IMAGE:
				default:
					$elements['images'][$selement['selementid']] = $selement;
			}
		}

	return $elements;
	}

	function prepareMapExport(&$exportMaps){

		$sysmaps = array();
		$hostgroups = array();
		$hosts = array();
		$triggers = array();
		$images = array();

		foreach($exportMaps as $mnum => $sysmap){
			$selements = separateMapElements($sysmap);

			$sysmaps += zbx_objectValues($selements['sysmaps'], 'elementid');
			$hostgroups += zbx_objectValues($selements['hostgroups'], 'elementid');
			$hosts += zbx_objectValues($selements['hosts'], 'elementid');
			$triggers += zbx_objectValues($selements['triggers'], 'elementid');
			$images += zbx_objectValues($selements['images'], 'elementid');

			foreach($sysmap['selements'] as $snum => $selement){
				if($selement['iconid_off'] > 0) $images[$selement['iconid_off']] = $selement['iconid_off'];
				if($selement['iconid_on'] > 0) $images[$selement['iconid_on']] = $selement['iconid_on'];
				if($selement['iconid_disabled'] > 0) $images[$selement['iconid_disabled']] = $selement['iconid_disabled'];
				if($selement['iconid_maintenance'] > 0) $images[$selement['iconid_maintenance']] = $selement['iconid_maintenance'];
			}

			$images[$sysmap['backgroundid']] = $sysmap['backgroundid'];

			foreach($sysmap['links'] as $lnum => $link){
				foreach($link['linktriggers'] as $ltnum => $linktrigger){
					array_push($triggers, $linktrigger['triggerid']);
				}
			}
		}

		$sysmaps = sysmapIdents($sysmaps);
		$hostgroups = hostgroupIdents($hostgroups);
		$hosts = hostIdents($hosts);
		$triggers = triggerIdents($triggers);
		$images = imageIdents($images);

		try{
			foreach($exportMaps as $mnum => &$sysmap){
				unset($sysmap['sysmapid']);
				foreach($sysmap['urls'] as $unum => $url){
					unset($sysmap['urls'][$unum]['sysmapurlid']);
				}

				$sysmap['backgroundid'] = ($sysmap['backgroundid'] > 0)?$images[$sysmap['backgroundid']]:'';

				foreach($sysmap['selements'] as $snum => &$selement){
					unset($selement['sysmapid']);

					foreach($selement['urls'] as $unum => $url){
						unset($selement['urls'][$unum]['sysmapelementurlid']);
						unset($selement['urls'][$unum]['selementid']);
					}
					switch($selement['elementtype']){
						case SYSMAP_ELEMENT_TYPE_MAP:
							$selement['elementid'] = $sysmaps[$selement['elementid']];
						break;
						case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
							$selement['elementid'] = $hostgroups[$selement['elementid']];
						break;
						case SYSMAP_ELEMENT_TYPE_HOST:
							$selement['elementid'] = $hosts[$selement['elementid']];
						break;
						case SYSMAP_ELEMENT_TYPE_TRIGGER:
							$selement['elementid'] = $triggers[$selement['elementid']];
						break;
						case SYSMAP_ELEMENT_TYPE_IMAGE:
						default:
							$selement['elementid'] = $images[$selement['elementid']];
					}

					$selement['iconid_off'] = ($selement['iconid_off'] > 0)?$images[$selement['iconid_off']]:'';
					$selement['iconid_on'] = ($selement['iconid_on'] > 0)?$images[$selement['iconid_on']]:'';
					$selement['iconid_disabled'] = ($selement['iconid_disabled'] > 0)?$images[$selement['iconid_disabled']]:'';
					$selement['iconid_maintenance'] = ($selement['iconid_maintenance'] > 0)?$images[$selement['iconid_maintenance']]:'';
				}
				unset($selement);

				foreach($sysmap['links'] as $lnum => &$link){
					unset($link['sysmapid']);
					unset($link['linkid']);
					foreach($link['linktriggers'] as $ltnum => &$linktrigger){
						unset($linktrigger['linktriggerid']);
						unset($linktrigger['linkid']);
						$linktrigger['triggerid'] = $triggers[$linktrigger['triggerid']];
					}
				}
				unset($linktrigger);
				unset($link);
			}
			unset($sysmap);
		}
		catch(Exception $e){
			throw new Exception($e->getMessage());
		}
//SDII($exportMaps);
	}

	function prepareImageExport($images){
		$formatted = array();

		foreach($images as $inum => $image){
			$formatted[] = array(
				'name' => $image['name'],
				'imagetype' => $image['imagetype'],
				'encodedImage' => $image['image'],
			);
		}
		return $formatted;
	}

	function drawMapConnectors(&$im, &$map, &$map_info){

		$links = $map['links'];
		$selements = $map['selements'];

		foreach($links as $lnum => $link){
			if(empty($link)) continue;

			$selement = $selements[$link['selementid1']];
			list($x1, $y1) = get_icon_center_by_selement($selement, $map_info[$link['selementid1']]);

			$selement = $selements[$link['selementid2']];
			list($x2, $y2) = get_icon_center_by_selement($selement, $map_info[$link['selementid2']]);

			$drawtype = $link['drawtype'];
			$color = convertColor($im,$link['color']);

			$linktriggers = $link['linktriggers'];
			order_result($linktriggers, 'triggerid');

			if(!empty($linktriggers)){
				$max_severity=0;

				$triggers = array();
				foreach($linktriggers as $lt_num => $link_trigger){
					if($link_trigger['triggerid'] == 0) continue;
					$id = $link_trigger['linktriggerid'];

					$triggers[$id] = zbx_array_merge($link_trigger,get_trigger_by_triggerid($link_trigger['triggerid']));
					if(($triggers[$id]['status'] == TRIGGER_STATUS_ENABLED) && ($triggers[$id]['value'] == TRIGGER_VALUE_TRUE)){
						if($triggers[$id]['priority'] >= $max_severity){
							$drawtype = $triggers[$id]['drawtype'];
							$color = convertColor($im,$triggers[$id]['color']);
							$max_severity = $triggers[$id]['priority'];
						}
					}
				}
			}

			MyDrawLine($im,$x1,$y1,$x2,$y2,$color,$drawtype);
		}
	}

	function drawMapSelements(&$im, &$map, &$map_info){
		$selements = $map['selements'];

		foreach($selements as $selementid => $selement){
			if(empty($selement)) continue;

			$el_info = $map_info[$selementid];
			$img = get_png_by_selement($selement, $el_info);

			$iconX = imagesx($img);
			$iconY = imagesy($img);

			imagecopy($im,$img,$selement['x'],$selement['y'],0,0,$iconX,$iconY);
		}
	}

	function drawMapHighligts(&$im, &$map, &$map_info){
		$selements = $map['selements'];

		foreach($selements as $selementid => $selement){
			if(empty($selement)) continue;

			$el_info = $map_info[$selementid];
			$img = get_png_by_selement($selement, $el_info);

			$iconX = imagesx($img);
			$iconY = imagesy($img);

			if(($map['highlight']%2) == SYSMAP_HIGHLIGHT_ON){
				$hl_color = null;
				$st_color = null;

				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_ON){
					$hl_color = hex2rgb(getSeverityColor($el_info['priority']));
				}

				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_MAINTENANCE) $st_color = hex2rgb('FF9933');
				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_DISABLED) $st_color = hex2rgb('EEEEEE');

				$mainProblems = array(
					SYSMAP_ELEMENT_TYPE_HOST_GROUP => 1,
					SYSMAP_ELEMENT_TYPE_MAP => 1
				);

				if(isset($mainProblems[$selement['elementtype']])){
					if(!is_null($hl_color)) $st_color = null;
				}
				else if(!is_null($st_color)){
					$hl_color = null;
				}

				if(!is_null($st_color)){
					$r = $st_color[0];
					$g = $st_color[1];
					$b = $st_color[2];

					imagefilledrectangle($im,
							$selement['x'] - 2,
							$selement['y'] - 2,
							$selement['x'] + $iconX + 2,
							$selement['y'] + $iconY + 2,
							imagecolorallocatealpha($im,$r,$g,$b, 0)
						);
// shadow
					imagerectangle($im,
							$selement['x'] - 2 - 1,
							$selement['y'] - 2 - 1,
							$selement['x'] + $iconX + 2 + 1,
							$selement['y'] + $iconY + 2 + 1,
							imagecolorallocate($im,120,120,120)
						);

					imagerectangle($im,
							$selement['x'] - 2 - 2,
							$selement['y'] - 2 - 2,
							$selement['x'] + $iconX + 2 + 2,
							$selement['y'] + $iconY + 2 + 2,
							imagecolorallocate($im,220,220,220)
						);
				}

				if(!is_null($hl_color)){
					$r = $hl_color[0];
					$g = $hl_color[1];
					$b = $hl_color[2];

					imagefilledellipse($im,
							$selement['x'] + ($iconX / 2),
							$selement['y'] + ($iconY / 2),
							$iconX+20,
							$iconX+20,
							imagecolorallocatealpha($im,$r,$g,$b, 0)
						);

					imageellipse($im,
							$selement['x'] + ($iconX / 2),
							$selement['y'] + ($iconY / 2),
							$iconX+20+1,
							$iconX+20+1,
							imagecolorallocate($im,120,120,120)
					);

					$config = select_config();
					if(isset($el_info['ack']) && $el_info['ack'] && $config['event_ack_enable']){
						imagesetthickness($im, 5);
						imagearc($im,
							$selement['x'] + ($iconX / 2),
							$selement['y'] + ($iconY / 2),
							$iconX+20-3,
							$iconX+20-3,
							0,
							359,
							imagecolorallocate($im,50,150,50)
						);
						imagesetthickness($im, 1);
					}
				}
			}
		}
	}


	function drawMapSelemetsMarks(&$im, &$map, &$map_info){
		global $colors;

		$selements = $map['selements'];

		foreach($selements as $selementid => $selement){
			if(empty($selement)) continue;

			$el_info = $map_info[$selementid];
			if(!$el_info['latelyChanged']) continue;

			$img = get_png_by_selement($selement, $el_info);

			$iconX = imagesx($img);
			$iconY = imagesy($img);

			$hl_color = null;
			$st_color = null;
			if(!isset($_REQUEST['noselements']) && (($map['highlight']%2) == SYSMAP_HIGHLIGHT_ON)){
				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_ON) $hl_color = true;

				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_MAINTENANCE) $st_color = true;
				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_DISABLED) $st_color = true;
			}

			$mainProblems = array(
				SYSMAP_ELEMENT_TYPE_HOST_GROUP => 1,
				SYSMAP_ELEMENT_TYPE_MAP => 1
			);

			if(isset($mainProblems[$selement['elementtype']])) if(!is_null($hl_color)) $st_color = null;
			else if(!is_null($st_color)) $hl_color = null;

			$markSize = $iconX/2;//sqrt(pow($iconX/2,2) + pow($iconX/2,2));
			if($hl_color) $markSize += 12;
			else if($st_color) $markSize += 8;
			else $markSize += 3;


			$marks = 'tlbr';
			if($map['label_type'] != MAP_LABEL_TYPE_NOTHING){
				$label_location = $selement['label_location'];
				if(is_null($label_location) || ($label_location < 0)) $label_location = $map['label_location'];

				switch($label_location){
					case MAP_LABEL_LOC_TOP: $marks = 'lbr'; break;
					case MAP_LABEL_LOC_LEFT: $marks = 'tbr'; break;
					case MAP_LABEL_LOC_RIGHT: $marks = 'tlb'; break;
					case MAP_LABEL_LOC_BOTTOM:
					default: $marks = 'tlr';
				}
			}

			imageVerticalMarks($im, $selement['x']+($iconX/2), $selement['y']+($iconY/2), $markSize, $colors['Red'], $marks);
//*/
		}
	}
	function drawMapLinkLabels(&$im, &$map, &$map_info){
		global $colors;

		$links = $map['links'];
		$selements = $map['selements'];

		foreach($links as $lnum => $link){
			if(empty($link)) continue;
			if(empty($link['label'])) continue;

			$selement = $selements[$link['selementid1']];
			list($x1, $y1) = get_icon_center_by_selement($selement, $map_info[$link['selementid1']]);

			$selement = $selements[$link['selementid2']];
			list($x2, $y2) = get_icon_center_by_selement($selement, $map_info[$link['selementid2']]);

			$drawtype = $link['drawtype'];
			$color = convertColor($im,$link['color']);

			$linktriggers = $link['linktriggers'];
			order_result($linktriggers, 'triggerid');

			if(!empty($linktriggers)){
				$max_severity=0;

				$triggers = array();
				foreach($linktriggers as $lt_num => $link_trigger){
					if($link_trigger['triggerid'] == 0) continue;
					$id = $link_trigger['linktriggerid'];

					$triggers[$id] = zbx_array_merge($link_trigger, get_trigger_by_triggerid($link_trigger['triggerid']));
					if(($triggers[$id]['status'] == TRIGGER_STATUS_ENABLED) && ($triggers[$id]['value'] == TRIGGER_VALUE_TRUE)){
						if($triggers[$id]['priority'] >= $max_severity){
							$drawtype = $triggers[$id]['drawtype'];
							$color = convertColor($im,$triggers[$id]['color']);
							$max_severity = $triggers[$id]['priority'];
						}
					}
				}
			}

			$label = $link['label'];

			$label = str_replace("\r", '', $label);
			$strings = explode("\n", $label);

			$box_width = 0;
			$box_height = 0;

			foreach($strings as $snum => $str)
				$strings[$snum] = expand_map_element_label_by_data(null, array('label'=>$str));

			foreach($strings as $snum => $str){
				$dims = imageTextSize(8,0,$str);

				$box_width = ($box_width > $dims['width'])?$box_width:$dims['width'];
				$box_height+= $dims['height']+2;
			}

			$boxX_left = round(($x1 + $x2) / 2 - ($box_width/2) - 6);
			$boxX_right = round(($x1 + $x2) / 2 + ($box_width/2) + 6);

			$boxY_top = round(($y1 + $y2) / 2 - ($box_height/2) - 4);
			$boxY_bottom = round(($y1 + $y2) / 2 + ($box_height/2) + 2);

			switch($drawtype){
				case MAP_LINK_DRAWTYPE_DASHED_LINE:
				case MAP_LINK_DRAWTYPE_DOT:
					dashedrectangle($im, $boxX_left, $boxY_top, $boxX_right, $boxY_bottom, $color);
					break;
				case MAP_LINK_DRAWTYPE_BOLD_LINE:
					imagerectangle($im, $boxX_left-1, $boxY_top-1, $boxX_right+1, $boxY_bottom+1, $color);
				case MAP_LINK_DRAWTYPE_LINE:
				default:
					imagerectangle($im, $boxX_left, $boxY_top, $boxX_right, $boxY_bottom, $color);
			}

			imagefilledrectangle($im, $boxX_left+1, $boxY_top+1, $boxX_right-1, $boxY_bottom-1, $colors['White']);


			$increasey = 4;
			foreach($strings as $snum => $str){
				$dims = imageTextSize(8,0,$str);

				$labelx = ($x1 + $x2) / 2 - ($dims['width']/2);
				$labely = $boxY_top + $increasey;

				imagetext($im, 8, 0, $labelx, $labely+$dims['height'], $colors['Black'], $str);

				$increasey += $dims['height']+2;
			}
		}
	}

	function drawMapLabels(&$im, &$map, &$map_info){
		global $colors;

		if(($map['label_type'] == MAP_LABEL_TYPE_NOTHING) && ($map['label_format'] == SYSMAP_LABEL_ADVANCED_OFF))
			return;

		$selements = $map['selements'];
		$all_strings = '';
		$label_lines = array();
		$status_lines = array();

		foreach($selements as $selementid => $selement){
			$selements[$selementid]['label_type'] = $map['label_type'];

			if($map['label_format'] == SYSMAP_LABEL_ADVANCED_OFF) continue;

			switch($selement['elementtype']){
				case SYSMAP_ELEMENT_TYPE_HOST_GROUP:
					$selements[$selementid]['label_type'] = $map['label_type_hostgroup'];
					if($map['label_type_hostgroup'] == MAP_LABEL_TYPE_CUSTOM)
						$selements[$selementid]['label'] = $map['label_string_hostgroup'];
					break;
				case SYSMAP_ELEMENT_TYPE_HOST:
					$selements[$selementid]['label_type'] = $map['label_type_host'];
					if($map['label_type_host'] == MAP_LABEL_TYPE_CUSTOM)
						$selements[$selementid]['label'] = $map['label_string_host'];
					break;
				case SYSMAP_ELEMENT_TYPE_TRIGGER:
					$selements[$selementid]['label_type'] = $map['label_type_trigger'];
					if($map['label_type_trigger'] == MAP_LABEL_TYPE_CUSTOM)
						$selements[$selementid]['label'] = $map['label_string_trigger'];
					break;
				case SYSMAP_ELEMENT_TYPE_MAP:
					$selements[$selementid]['label_type'] = $map['label_type_map'];
					if($map['label_type_map'] == MAP_LABEL_TYPE_CUSTOM)
						$selements[$selementid]['label'] = $map['label_string_map'];
					break;
				case SYSMAP_ELEMENT_TYPE_IMAGE:
					$selements[$selementid]['label_type'] = $map['label_type_image'];
					if($map['label_type_image'] == MAP_LABEL_TYPE_CUSTOM)
						$selements[$selementid]['label'] = $map['label_string_image'];
					break;
			}
		}

		foreach($selements as $selementid => $selement){
			if(!isset($label_lines[$selementid])) $label_lines[$selementid] = array();
			if(!isset($status_lines[$selementid])) $status_lines[$selementid] = array();

			$msg = expand_map_element_label_by_data($selement);
			$all_strings .= $msg;
			$msgs = explode("\n", $msg);
			foreach($msgs as $msg){
				$label_lines[$selementid][] = array('msg' => $msg);
			}

			$el_info = $map_info[$selementid];

			$el_msgs = array('problem', 'unack', 'maintenance', 'unknown', 'ok', 'status');
			foreach($el_msgs as $key => $caption){
				if(!isset($el_info['info'][$caption]) || zbx_empty($el_info['info'][$caption]['msg'])) continue;

				$status_lines[$selementid][] = array(
					'msg' => $el_info['info'][$caption]['msg'],
					'color' => $el_info['info'][$caption]['color']
				);

				$all_strings .= $el_info['info'][$caption]['msg'];
			}
		}

		$allLabelsSize = imageTextSize(8, 0, str_replace("\r", '', str_replace("\n", '', $all_strings)));
		$labelFontHeight = $allLabelsSize['height'];
		$labelFontBaseline = $allLabelsSize['baseline'];


		$elementsHostids = array();
		foreach($selements as $selementid => $selement){
			if($selement['label_type'] != MAP_LABEL_TYPE_IP) continue;

			if($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST)
				$elementsHostids[] = $selement['elementid'];
		}

		if(!empty($elementsHostids)){
			$mapHosts = API::Host()->get(array(
				'hostids' => $elementsHostids,
				'output' => API_OUTPUT_SHORTEN,
				'selectInterfaces' => API_OUTPUT_EXTEND,
			));
			$mapHosts = zbx_toHash($mapHosts, 'hostid');
		}

// DRAW
		foreach($selements as $selementid => $selement){
			if(empty($selement) || (($selement['label_type'] == MAP_LABEL_TYPE_NOTHING))) continue;

			$el_info = $map_info[$selementid];

			$hl_color = null;
			$st_color = null;
			if(!isset($_REQUEST['noselements']) && (($map['highlight']%2) == SYSMAP_HIGHLIGHT_ON)){
				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_ON) $hl_color = true;

				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_MAINTENANCE) $st_color = true;
				if($el_info['icon_type'] == SYSMAP_ELEMENT_ICON_DISABLED) $st_color = true;
			}

			if(in_array($selement['elementtype'], array(SYSMAP_ELEMENT_TYPE_HOST_GROUP, SYSMAP_ELEMENT_TYPE_MAP)) && !is_null($hl_color))
				$st_color = null;
			else if(!is_null($st_color))
				$hl_color = null;


			$label_location = (is_null($selement['label_location']) || ($selement['label_location'] < 0))
					? $map['label_location'] : $selement['label_location'];

			$label = array();
			if(($selement['label_type'] == MAP_LABEL_TYPE_IP) && ($selement['elementtype'] == SYSMAP_ELEMENT_TYPE_HOST)){
				$interface = reset($mapHosts[$selement['elementid']]['interfaces']);

				$label[] = array('msg' => $interface['ip']);
				$label = array_merge($label, $status_lines[$selementid]);
			}
			else if($selement['label_type'] == MAP_LABEL_TYPE_STATUS){
				$label = $status_lines[$selementid];
			}
			else if($selement['label_type'] == MAP_LABEL_TYPE_NAME){
				$label[] = array('msg' => $el_info['name']);
				$label = array_merge($label, $status_lines[$selementid]);
			}
			else{
				$label = array_merge($label_lines[$selementid], $status_lines[$selementid]);
			}
			if(empty($label)) continue;

			$w = 0;
			foreach($label as $str){
				$dims = imageTextSize(8, 0, $str['msg']);
				$w = max($w, $dims['width']);
			}
			$h = count($label) * $labelFontHeight;

			$x = $selement['x'];
			$y = $selement['y'];
			$img = get_png_by_selement($selement, $el_info);
			$iconX = imagesx($img);
			$iconY = imagesy($img);

			if(!is_null($hl_color)) $icon_hl = 14;
			else if(!is_null($st_color)) $icon_hl = 6;
			else $icon_hl = 2;

			switch($label_location){
				case MAP_LABEL_LOC_TOP:
					$y_rec = $y - $icon_hl - $h - 6;
					$x_rec = $x + $iconX/2 - $w/2;
					break;
				case MAP_LABEL_LOC_LEFT:
					$y_rec = $y - $h/2 + $iconY/2;
					$x_rec = $x - $icon_hl - $w;
					break;
				case MAP_LABEL_LOC_RIGHT:
					$y_rec = $y - $h/2 + $iconY/2;
					$x_rec = $x + $iconX + $icon_hl;
					break;
				case MAP_LABEL_LOC_BOTTOM:
				default:
					$y_rec = $y + $iconY + $icon_hl;
					$x_rec = $x + $iconX/2 - $w/2;
			}
//		$y_rec += 30;
//		imagerectangle($im, $x_rec-2-1, $y_rec-3, $x_rec+$w+2+1, $y_rec+($oc*4)+$h+3, $label_color);
//		imagefilledrectangle($im, $x_rec-2, $y_rec-2, $x_rec+$w+2, $y_rec+($oc*4)+$h-2, $colors['White']);

			$increasey = 12;
			foreach($label as $line){
				if(zbx_empty($line['msg'])) continue;

				$str = str_replace("\r", '', $line['msg']);
				$color = isset($line['color']) ? $line['color'] : $colors['Black'];

				$dims = imageTextSize(8, 0, $str);
//				$dims['height'] = $labelFontHeight;
				//$str .= ' - '.$labelFontHeight.' - '.$dims['height'];
				//$str = $dims['width'].'x'.$dims['height'];

				if($label_location == MAP_LABEL_LOC_TOP || $label_location == MAP_LABEL_LOC_BOTTOM){
					$x_label = $x + ceil($iconX/2) - ceil($dims['width']/2);
				}
				else if($label_location == MAP_LABEL_LOC_LEFT)
					$x_label = $x_rec + $w - $dims['width'];
				else
					$x_label = $x_rec;

				imagefilledrectangle(
					$im,
					$x_label-1, $y_rec+$increasey-$labelFontHeight+$labelFontBaseline,
					$x_label+$dims['width']+1, $y_rec+$increasey+$labelFontBaseline,
					$colors['White']
				);
				imagetext($im, 8, 0, $x_label, $y_rec+$increasey, $color, $str);

				$increasey += $labelFontHeight+1;
			}
		}
	}
?>
