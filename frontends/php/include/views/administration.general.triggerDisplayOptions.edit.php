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


include('include/views/js/administration.general.triggerDisplayOptions.js.php');

$widget = (new CWidget())
	->setTitle(_('Trigger displaying options'))
	->setControls((new CForm())
		->cleanItems()
		->addItem((new CList())->addItem(makeAdministrationGeneralMenu('adm.triggerdisplayoptions.php')))
	);

$triggerDOFormList = new CFormList();

$headerDiv = (new CDiv(_('Colour')))
	->addClass('inlineblock')
	->addClass('trigger_displaying_form_col')
	->addStyle('margin-left: 2px;');
$triggerDOFormList->addRow(SPACE, [$headerDiv, _('Blinking')]);

// Unacknowledged problem events
$triggerDOFormList->addRow(
	_('Unacknowledged PROBLEM events'),
	[
		(new CDiv(new CColor('problem_unack_color', $data['problem_unack_color'])))
			->addClass('inlineblock')
			->addClass('trigger_displaying_form_col'),
		(new CCheckBox('problem_unack_style'))->setChecked($data['problem_unack_style'] == 1)
	]
);

// Acknowledged problem events
$triggerDOFormList->addRow(
	_('Acknowledged PROBLEM events'),
	[
		(new CDiv(
			new CColor('problem_ack_color', $data['problem_ack_color'])))
				->addClass('inlineblock')
				->addClass('trigger_displaying_form_col'),
		(new CCheckBox('problem_ack_style'))->setChecked($data['problem_ack_style'] == 1)
	]
);

// Unacknowledged recovery events
$triggerDOFormList->addRow(
	_('Unacknowledged OK events'),
	[
		(new CDiv(new CColor('ok_unack_color', $data['ok_unack_color'])))
			->addClass('inlineblock')
			->addClass('trigger_displaying_form_col'),
		(new CCheckBox('ok_unack_style'))->setChecked($data['ok_unack_style'] == 1)
	]
);

// Acknowledged recovery events
$triggerDOFormList->addRow(
	_('Acknowledged OK events'),
	[
		(new CDiv(new CColor('ok_ack_color', $data['ok_ack_color'])))
			->addClass('inlineblock')
			->addClass('trigger_displaying_form_col'),
		(new CCheckBox('ok_ack_style'))->setChecked($data['ok_ack_style'] == 1)
	]
);

// some air between the sections
$triggerDOFormList->addRow(BR());

// Display OK triggers
$okPeriodTextBox = new CTextBox('ok_period', $data['ok_period']);
$okPeriodTextBox->addStyle('width: 4em;');
$okPeriodTextBox->setAttribute('maxlength', '6');
$triggerDOFormList->addRow(_('Display OK triggers for'), [$okPeriodTextBox, SPACE, _('seconds')]);

// Triggers blink on status change
$okPeriodTextBox = new CTextBox('blink_period', $data['blink_period']);
$okPeriodTextBox->addStyle('width: 4em;');
$okPeriodTextBox->setAttribute('maxlength', '6');
$triggerDOFormList->addRow(_('On status change triggers blink for'), [$okPeriodTextBox, SPACE, _('seconds')]);

$severityView = new CTabView();
$severityView->addTab('triggerdo', _('Trigger displaying options'), $triggerDOFormList);

$severityForm = new CForm();
$severityForm->setName('triggerDisplayOptions');

$severityView->setFooter(makeFormFooter(
	new CSubmit('update', _('Update')),
	[new CButton('resetDefaults', _('Reset defaults'))]
));

$severityForm->addItem($severityView);

$widget->addItem($severityForm);

return $widget;
