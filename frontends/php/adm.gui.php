<?php
/*
** Zabbix
** Copyright (C) 2000-2012 Zabbix SIA
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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/
?>
<?php
require_once('include/config.inc.php');

$page['title'] = _('Configuration of Zabbix');
$page['file'] = 'adm.gui.php';

require_once('include/page_header.php');
?>
<?php
$fields = array(
	// VAR					        TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	'default_theme'=>			array(T_ZBX_STR, O_OPT,	null,			NOT_EMPTY,			'isset({save})'),
	'event_ack_enable'=>		array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	IN('1'),	null),
	'event_expire'=> 			array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,99999),	'isset({save})'),
	'event_show_max'=> 			array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,99999),	'isset({save})'),
	'dropdown_first_entry'=>	array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	IN('0,1,2'),		'isset({save})'),
	'dropdown_first_remember'=>	array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	IN('1'),	null),
	'max_in_table' => 			array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,99999),	'isset({save})'),
	'search_limit' => 			array(T_ZBX_INT, O_OPT, P_SYS|P_ACT,	BETWEEN(1,999999),	'isset({save})'),

	'save'=>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT,	null,	null),
	'form_refresh' =>			array(T_ZBX_INT, O_OPT,	null,	null,	null)
);
?>
<?php
check_fields($fields);


if (isset($_REQUEST['save'])) {
	$configs = array(
		'default_theme' => get_request('default_theme'),
		'event_ack_enable' => (is_null(get_request('event_ack_enable')) ? 0 : 1),
		'event_expire' => get_request('event_expire'),
		'event_show_max' => get_request('event_show_max'),
		'dropdown_first_entry' => get_request('dropdown_first_entry'),
		'dropdown_first_remember' => (is_null(get_request('dropdown_first_remember')) ? 0 : 1),
		'max_in_table' => get_request('max_in_table'),
		'search_limit' => get_request('search_limit'),
	);

	$result = update_config($configs);

	show_messages($result, _('Configuration updated'), _('Cannot update configuration'));

	if ($result) {
		$msg = array();
		$msg[] = _s('Default theme [%1$s]', get_request('default_theme'));
		$msg[] = _s('Event acknowledges [%1$s]', get_request('event_ack_enable'));
		$msg[] = _s('Show events not older than (in days) [%1$s]', get_request('event_expire'));
		$msg[] = _s('Show events max [%1$s]', get_request('event_show_max'));
		$msg[] = _s('Dropdown first entry [%1$s]', get_request('dropdown_first_entry'));
		$msg[] = _s('Dropdown remember selected [%1$s]', get_request('dropdown_first_remember'));
		$msg[] = _s('Max count of elements to show inside table cell [%1$s]', get_request('max_in_table'));

		add_audit(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_ZABBIX_CONFIG, implode('; ', $msg));
	}
}


$form = new CForm();
$form->cleanItems();
$cmbConf = new CComboBox('configDropDown', 'adm.gui.php', 'redirect(this.options[this.selectedIndex].value);');
$cmbConf->addItems(array(
	'adm.gui.php' => _('GUI'),
	'adm.housekeeper.php' => _('Housekeeper'),
	'adm.images.php' => _('Images'),
	'adm.iconmapping.php' => _('Icon mapping'),
	'adm.regexps.php' => _('Regular expressions'),
	'adm.macros.php' => _('Macros'),
	'adm.valuemapping.php' => _('Value mapping'),
	'adm.workingtime.php' => _('Working time'),
	'adm.triggerseverities.php' => _('Trigger severities'),
	'adm.triggerdisplayingoptions.php' => _('Trigger displaying options'),
	'adm.other.php' => _('Other')
));
$form->addItem($cmbConf);

$cnf_wdgt = new CWidget();
$cnf_wdgt->addPageHeader(_('CONFIGURATION OF ZABBIX'), $form);


$data = array();
$data['form_refresh'] = get_request('form_refresh', 0);

if ($data['form_refresh']) {
	$data['config']['default_theme'] = get_request('default_theme');
	$data['config']['event_ack_enable'] = get_request('event_ack_enable');
	$data['config']['dropdown_first_entry'] = get_request('dropdown_first_entry');
	$data['config']['dropdown_first_remember'] = get_request('dropdown_first_remember');
	$data['config']['search_limit'] = get_request('search_limit');
	$data['config']['max_in_table'] = get_request('max_in_table');
	$data['config']['event_expire'] = get_request('event_expire');
	$data['config']['event_show_max'] = get_request('event_show_max');
}
else {
	$data['config'] = select_config(false);
}

$guiForm = new CView('administration.general.gui.edit', $data);
$cnf_wdgt->addItem($guiForm->render());
$cnf_wdgt->show();

require_once('include/page_footer.php');
?>
