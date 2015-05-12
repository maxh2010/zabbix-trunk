<?php
/*
** Zabbix
** Copyright (C) 2001-2015 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


global $ZBX_MENU;

$ZBX_MENU = array(
	'view' => array(
		'label'				=> _('Monitoring'),
		'user_type'			=> USER_TYPE_ZABBIX_USER,
		'default_page_id'	=> 0,
		'pages' => array(
			array(
				'url' => 'zabbix.php',
				'action' => 'dashboard.view',
				'active_if' => array('dashboard.view'),
				'label' => _('Dashboard'),
				'sub_pages' => array('dashconf.php')
			),
			array(
				'url' => 'overview.php',
				'label' => _('Overview')
			),
			array(
				'url' => 'httpmon.php',
				'label' => _('Web'),
				'sub_pages' => array('httpdetails.php')
			),
			array(
				'url' => 'latest.php',
				'label' => _('Latest data'),
				'sub_pages' => array('history.php', 'chart.php')
			),
			array(
				'url' => 'tr_status.php',
				'label' => _('Triggers'),
				'sub_pages' => array('acknow.php', 'tr_comments.php', 'chart4.php', 'scripts_exec.php')
			),
			array(
				'url' => 'events.php',
				'label' => _('Events'),
				'sub_pages' => array('tr_events.php')
			),
			array(
				'url' => 'charts.php',
				'label' => _('Graphs'),
				'sub_pages' => array('chart2.php', 'chart3.php', 'chart6.php', 'chart7.php')
			),
			array(
				'url' => 'screens.php',
				'label' => _('Screens'),
				'sub_pages' => array('slides.php')
			),
			array(
				'url' => 'zabbix.php',
				'action' => 'map.view',
				'active_if' => array('map.view'),
				'label' => _('Maps'),
				'sub_pages' => array('map.php')
			),
			array(
				'url' => 'zabbix.php',
				'action' => 'discovery.view',
				'active_if' => array('discovery.view'),
				'label' => _('Discovery'),
				'user_type' => USER_TYPE_ZABBIX_ADMIN
			),
			array(
				'url' => 'srv_status.php',
				'label' => _('IT services'),
				'sub_pages' => array('report3.php', 'chart5.php')
			),
			array(
				'url' => 'chart3.php'
			),
			array(
				'url' => 'imgstore.php'
			),
			array(
				'url' => 'search.php'
			),
			array(
				'url' => 'jsrpc.php'
			)
		)
	),
	'cm' => array(
		'label'				=> _('Inventory'),
		'user_type'			=> USER_TYPE_ZABBIX_USER,
		'default_page_id'	=> 0,
		'pages' => array(
			array(
				'url' => 'hostinventoriesoverview.php',
				'label' => _('Overview')
			),
			array(
				'url' => 'hostinventories.php',
				'label' => _('Hosts')
			)
		)
	),
	'reports' => array(
		'label'				=> _('Reports'),
		'user_type'			=> USER_TYPE_ZABBIX_USER,
		'default_page_id'	=> 0,
		'pages' => array(
			array(
				'url' => 'zabbix.php',
				'action' => 'report.status',
				'active_if' => array('report.status'),
				'label' => _('Status of Zabbix'),
				'user_type' => USER_TYPE_SUPER_ADMIN
			),
			array(
				'url' => 'report2.php',
				'label' => _('Availability report')
			),
			array(
				'url' => 'toptriggers.php',
				'label' => _('Triggers top 100')
			),
			array(
				'url' => 'report6.php',
				'label' => _('Bar reports'),
				'sub_pages' => array('popup_period.php', 'popup_bitem.php', 'chart_bar.php')
			),
			array(
				'url' => 'auditlogs.php',
				'label' => _('Audit'),
				'user_type' => USER_TYPE_ZABBIX_ADMIN
			),
			array(
				'url' => 'auditacts.php',
				'label' => _('Action log'),
				'user_type' => USER_TYPE_ZABBIX_ADMIN
			),
			array(
				'url' => 'report4.php',
				'label' => _('Notifications'),
				'user_type' => USER_TYPE_ZABBIX_ADMIN
			),
			array(
				'url' => 'popup.php'
			),
			array(
				'url' => 'popup_right.php'
			)
		)
	),
	'config' => array(
		'label'				=> _('Configuration'),
		'user_type'			=> USER_TYPE_ZABBIX_ADMIN,
		'default_page_id'	=> 0,
		'pages' => array(
			array(
				'url' => 'conf.import.php'
			),
			array(
				'url' => 'hostgroups.php',
				'label' => _('Host groups')
			),
			array(
				'url' => 'templates.php',
				'label' => _('Templates')
			),
			array(
				'url' => 'hosts.php',
				'label' => _('Hosts'),
				'sub_pages' => array(
					'items.php',
					'triggers.php',
					'graphs.php',
					'applications.php',
					'tr_logform.php',
					'tr_testexpr.php',
					'popup_trexpr.php',
					'host_discovery.php',
					'disc_prototypes.php',
					'trigger_prototypes.php',
					'host_prototypes.php',
					'httpconf.php',
					'popup_httpstep.php'
				)
			),
			array(
				'url' => 'maintenance.php',
				'label' => _('Maintenance')
			),
			array(
				'url' => 'actionconf.php',
				'label' => _('Actions')
			),
			array(
				'url' => 'screenconf.php',
				'label' => _('Screens'),
				'sub_pages' => array('screenedit.php')
			),
			array(
				'url' => 'slideconf.php',
				'label' => _('Slide shows'),
			),
			array(
				'url' => 'sysmaps.php',
				'label' => _('Maps'),
				'sub_pages' => array('image.php', 'sysmap.php')
			),
			array(
				'url' => 'discoveryconf.php',
				'label' => _('Discovery')
			),
			array(
				'url' => 'services.php',
				'label' => _('IT services')
			)
		)
	),
	'admin' => array(
		'label'				=> _('Administration'),
		'user_type'			=> USER_TYPE_SUPER_ADMIN,
		'default_page_id'	=> 0,
		'pages' => array(
			array(
				'url' => 'adm.gui.php',
				'label' => _('General'),
				'sub_pages' => array(
					'adm.housekeeper.php',
					'adm.images.php',
					'adm.iconmapping.php',
					'adm.regexps.php',
					'adm.macros.php',
					'adm.valuemapping.php',
					'adm.workingtime.php',
					'adm.triggerseverities.php',
					'adm.triggerdisplayoptions.php',
					'adm.other.php'
				)
			),
			array(
				'url' => 'zabbix.php',
				'action' => 'proxy.list',
				'active_if' => array('proxy.edit', 'proxy.list'),
				'label' => _('Proxies')
			),
			array(
				'url' => 'authentication.php',
				'label' => _('Authentication')
			),
			array(
				'url' => 'usergrps.php',
				'label' => _('User groups')
			),
			array(
				'url' => 'users.php',
				'label' => _('Users')
			),
			array(
				'url' => 'zabbix.php',
				'action' => 'mediatype.list',
				'active_if' => array('mediatype.edit', 'mediatype.list'),
				'label' => _('Media types')
			),
			array(
				'url' => 'zabbix.php',
				'action' => 'script.list',
				'active_if' => array('script.edit', 'script.list'),
				'label' => _('Scripts')
			),
			array(
				'url' => 'queue.php',
				'label' => _('Queue')
			)
		)
	),
	'login' => array(
		'label'					=> _('Login'),
		'user_type'				=> 0,
		'default_page_id'		=> 0,
		'pages' => array(
			array(
				'url' => 'index.php',
				'sub_pages' => array('profile.php', 'popup_media.php')
			)
		)
	)
);

/**
 * NOTE - menu array format:
 * first level:
 *	'label' = main menu title.
 *	'default_page_id	= default page url from 'pages' then opened menu.
 *	'pages' = collection of pages which are displayed from this menu.
 *	these pages are saved a last visited submenu of main menu.
 *
 * second level (pages):
 *	'url' = real url for this page
 *	'label' =  submenu title, if missing, menu skipped, but remembered as last visited page.
 *	'sub_pages' = collection of pages for displaying but not remembered as last visited.
 */
function zbx_construct_menu(&$main_menu, &$sub_menus, &$page, $action = null) {
	global $ZBX_MENU;

	$denied_page_requested = false;
	$page_exists = false;
	$deny = true;

	foreach ($ZBX_MENU as $label => $menu) {
		$show_menu = true;

		if (isset($menu['user_type'])) {
			$show_menu &= ($menu['user_type'] <= CWebUser::$data['type']);
		}
		if ($label == 'login') {
			$show_menu = false;
		}

		$menu_class = null;
		$sub_menus[$label] = array();

		foreach ($menu['pages'] as $sub_page) {
			$show_sub_menu = true;

			// show check
			if (!isset($sub_page['label'])) {
				$show_sub_menu = false;
			}
			if (!isset($sub_page['user_type'])) {
				$sub_page['user_type'] = $menu['user_type'];
			}
			if (CWebUser::$data['type'] < $sub_page['user_type']) {
				$show_sub_menu = false;
			}

			$row = array(
				'menu_text' => isset($sub_page['label']) ? $sub_page['label'] : '',
				'menu_url' => $sub_page['url'],
				'menu_action' => array_key_exists('action', $sub_page) ? $sub_page['action'] : null,
				'class' => 'highlight',
				'selected' => false
			);

			if ($action == null) {
				$sub_menu_active = ($page['file'] == $sub_page['url']);
				$sub_menu_active |= (isset($sub_page['sub_pages']) && str_in_array($page['file'], $sub_page['sub_pages']));
			}
			else {
				$sub_menu_active = array_key_exists('active_if', $sub_page) && str_in_array($action, $sub_page['active_if']);
			}

			if ($sub_menu_active) {
				// permission check
				$deny &= (CWebUser::$data['type'] < $menu['user_type'] || CWebUser::$data['type'] < $sub_page['user_type']);

				$menu_class = 'selected';
				$page_exists = true;
				$page['menu'] = $label;
				$row['selected'] = true;

				if (!defined('ZBX_PAGE_NO_MENU')) {
					CProfile::update('web.menu.'.$label.'.last', $sub_page['url'], PROFILE_TYPE_STR);
				}
			}

			if ($show_sub_menu) {
				$sub_menus[$label][] = $row;
			}
		}

		if ($page_exists && $deny) {
			$denied_page_requested = true;
		}

		if (!$show_menu) {
			unset($sub_menus[$label]);
			continue;
		}

		if ($sub_menus[$label][$menu['default_page_id']]['menu_action'] === null) {
			$menu_url = $sub_menus[$label][$menu['default_page_id']]['menu_url'];
		}
		else {
			$menu_url = $sub_menus[$label][$menu['default_page_id']]['menu_url'].'?action='.$sub_menus[$label][$menu['default_page_id']]['menu_action'];
		}
		$mmenu_entry = new CListItem(new CLink($menu['label'], $menu_url), $menu_class);
		$mmenu_entry->setAttribute('id', $label);
// click to navigate to other sections, uncomment for old-style navigation
//		$mmenu_entry->addAction('onmouseover', 'javascript: MMenu.mouseOver(\''.$label.'\');');
//		$mmenu_entry->addAction('onmouseout', 'javascript: MMenu.mouseOut();');
		array_push($main_menu, $mmenu_entry);
	}

	if (!$page_exists && $page['type'] != PAGE_TYPE_XML && $page['type'] != PAGE_TYPE_CSV && $page['type'] != PAGE_TYPE_TEXT_FILE) {
		$denied_page_requested = true;
	}

	return $denied_page_requested;
}
