<?php
/*
** Zabbix
** Copyright (C) 2001-2016 Zabbix SIA
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


require_once dirname(__FILE__).'/js/configuration.triggers.edit.js.php';

$widget = (new CWidget())->setTitle(_('Triggers'));

// append host summary to widget header
if (!empty($data['hostid'])) {
	$widget->addItem(get_header_host_table('triggers', $data['hostid']));
}

// create form
$triggersForm = (new CForm())
	->setName('triggersForm')
	->addVar('form', $data['form'])
	->addVar('hostid', $data['hostid'])
	->addVar('expression_constructor', $data['expression_constructor'])
	->addVar('recovery_expression_constructor', $data['recovery_expression_constructor'])
	->addVar('toggle_expression_constructor', '')
	->addVar('toggle_recovery_expression_constructor', '')
	->addVar('remove_expression', '')
	->addVar('remove_recovery_expression', '')
	->addVar('recovery_mode', $data['recovery_mode']);

if ($data['triggerid'] !== null) {
	$triggersForm->addVar('triggerid', $data['triggerid']);
}

// create form list
$triggersFormList = new CFormList('triggersFormList');
if (!empty($data['templates'])) {
	$triggersFormList->addRow(_('Parent triggers'), $data['templates']);
}
$triggersFormList->addRow(_('Name'),
	(new CTextBox('description', $data['description'], $data['limited']))
		->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		->setAttribute('autofocus', 'autofocus')
);

// append expression to form list
if ($data['expression_field_readonly']) {
	$triggersForm->addVar('expression', $data['expression']);
}

if ($data['recovery_expression_field_readonly']) {
	$triggersForm->addVar('recovery_expression', $data['recovery_expression']);
}

$add_expression_button = (new CButton('insert', ($data['expression_constructor'] == IM_TREE) ? _('Edit') : _('Add')))
	->addClass(ZBX_STYLE_BTN_GREY)
	->onClick(
		'return PopUp("popup_trexpr.php?dstfrm='.$triggersForm->getName().
			'&dstfld1='.$data['expression_field_name'].'&srctbl='.$data['expression_field_name'].
			'&srcfld1='.$data['expression_field_name'].
			'&expression="+encodeURIComponent(jQuery(\'[name="'.$data['expression_field_name'].'"]\').val()));'
	);
if ($data['limited']) {
	$add_expression_button->setAttribute('disabled', 'disabled');
}
$expression_row = [
	(new CTextArea(
		$data['expression_field_name'],
		$data['expression_field_value'],
		['readonly' => $data['expression_field_readonly']]
	))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
	(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
	$add_expression_button
];

if ($data['expression_constructor'] == IM_TREE) {
	// insert macro button
	$insertMacroButton = (new CButton('insert_macro', _('Insert expression')))
		->addClass(ZBX_STYLE_BTN_GREY)
		->setMenuPopup(CMenuPopupHelper::getTriggerMacro());
	if ($data['limited']) {
		$insertMacroButton->setAttribute('disabled', 'disabled');
	}
	$expression_row[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
	$expression_row[] = $insertMacroButton;
	$expression_row[] = BR();

	if ($data['expression_formula'] === '') {
		// add button
		$add_expression_button = (new CSubmit('add_expression', _('Add')))->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$add_expression_button->setAttribute('disabled', 'disabled');
		}
		$expression_row[] = $add_expression_button;
	}
	else {
		// add button
		$add_expression_button = (new CSubmit('and_expression', _('And')))->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$add_expression_button->setAttribute('disabled', 'disabled');
		}
		$expression_row[] = $add_expression_button;

		// or button
		$orExpressionButton = (new CSubmit('or_expression', _('Or')))->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$orExpressionButton->setAttribute('disabled', 'disabled');
		}
		$expression_row[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$expression_row[] = $orExpressionButton;

		// replace button
		$replaceExpressionButton = (new CSubmit('replace_expression', _('Replace')))->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$replaceExpressionButton->setAttribute('disabled', 'disabled');
		}
		$expression_row[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$expression_row[] = $replaceExpressionButton;
	}
}
elseif ($data['expression_constructor'] != IM_FORCED) {
	$input_method_toggle = (new CButton(null, _('Expression constructor')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->onClick('javascript: '.
			'document.getElementById("toggle_expression_constructor").value=1;'.
			'document.getElementById("expression_constructor").value='.
				(($data['expression_constructor'] == IM_TREE) ? IM_ESTABLISHED : IM_TREE).';'.
			'document.forms["'.$triggersForm->getName().'"].submit();');
	$expression_row[] = [BR(), $input_method_toggle];
}

$triggersFormList->addRow(_('Expression'), $expression_row, 'expression_row');

// Append expression table to form list.
if ($data['expression_constructor'] == IM_TREE) {
	$expressionTable = (new CTable())
		->setAttribute('style', 'width: 100%;')
		->setId('exp_list')
		->setHeader([
			$data['limited'] ? null : _('Target'),
			_('Expression'),
			$data['limited'] ? null : _('Action'),
			_('Info')
		]);

	$allowed_testing = true;
	if ($data['expression_tree']) {
		foreach ($data['expression_tree'] as $i => $e) {
			if (!isset($e['expression']['levelErrors'])) {
				$errorImg = '';
			}
			else {
				$allowed_testing = false;
				$errors = [];

				if (is_array($e['expression']['levelErrors'])) {
					foreach ($e['expression']['levelErrors'] as $expVal => $errTxt) {
						if ($errors) {
							$errors[] = BR();
						}
						$errors[] = $expVal.':'.$errTxt;
					}
				}

				$errorImg = makeErrorIcon($errors);
			}

			// templated trigger
			if ($data['limited']) {
				// make all links inside inactive
				foreach ($e['list'] as &$obj) {
					if (gettype($obj) === 'object' && get_class($obj) === 'CSpan'
							&& $obj->getAttribute('class') == ZBX_STYLE_LINK_ACTION) {
						$obj->removeAttribute('class');
						$obj->onClick(null);
					}
				}
				unset($obj);
			}

			$expressionTable->addRow(
				new CRow([
					!$data['limited']
						? (new CCheckBox('expr_target_single', $e['id']))
							->setChecked($i == 0)
							->onClick('check_target(this, '.TRIGGER_EXPRESSION.');')
						: null,
					$e['list'],
					!$data['limited']
						? (new CCol(
							(new CButton(null, _('Remove')))
								->addClass(ZBX_STYLE_BTN_LINK)
								->onClick('javascript:'.
									' if (confirm('.CJs::encodeJson(_('Delete expression?')).')) {'.
										' delete_expression("'.$e['id'] .'", '.TRIGGER_EXPRESSION.');'.
										' document.forms["'.$triggersForm->getName().'"].submit();'.
									' }'
								)
						))->addClass(ZBX_STYLE_NOWRAP)
						: null,
					$errorImg
				])
			);
		}
	}
	else {
		$allowed_testing = false;
		$data['expression_formula'] = '';
	}

	$testButton = (new CButton('test_expression', _('Test')))
		->onClick('openWinCentered("tr_testexpr.php?expression="+'.
			'encodeURIComponent(this.form.elements["expression"].value),'.
			'"ExpressionTest", 950, 650, "titlebar=no, resizable=yes, scrollbars=yes"); return false;'
		)
		->addClass(ZBX_STYLE_BTN_LINK);
	if (!$allowed_testing) {
		$testButton->setAttribute('disabled', 'disabled');
	}
	if ($data['expression_formula'] === '') {
		$testButton->setAttribute('disabled', 'disabled');
	}

	$wrapOutline = new CSpan([$data['expression_formula']]);
	$triggersFormList->addRow(null, [
		$wrapOutline,
		BR(),
		BR(),
		(new CDiv([$expressionTable, $testButton]))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
	]);

	$input_method_toggle = (new CButton(null, _('Close expression constructor')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->onClick('javascript: '.
			'document.getElementById("toggle_expression_constructor").value=1;'.
			'document.getElementById("expression_constructor").value='.IM_ESTABLISHED.';'.
			'document.forms["'.$triggersForm->getName().'"].submit();');
	$triggersFormList->addRow(null, [$input_method_toggle, BR()]);
}
$event_generation = (new CRadioButtonList('recovery_mode', (int) $data['recovery_mode']))
	->addValue(_('Expression'), ZBX_RECOVERY_MODE_EXPRESSION)
	->addValue(_('Recovery expression'), ZBX_RECOVERY_MODE_RECOVERY_EXPRESSION)
	->addValue(_('None'), ZBX_RECOVERY_MODE_NONE)
	->setModern(true);

if ($data['limited']) {
	$event_generation->setEnabled(false);
}

$triggersFormList->addRow(_('OK event generation'), $event_generation);

$add_recovery_expression_button = (new CButton('insert',
		($data['recovery_expression_constructor'] == IM_TREE) ? _('Edit') : _('Add'))
	)
	->addClass(ZBX_STYLE_BTN_GREY)
	->onClick(
		'return PopUp("popup_trexpr.php?dstfrm='.$triggersForm->getName().
			'&dstfld1='.$data['recovery_expression_field_name'].
			'&srctbl='.$data['recovery_expression_field_name'].'&srcfld1='.$data['recovery_expression_field_name'].
			'&expression="+encodeURIComponent(jQuery(\'[name="'.$data['recovery_expression_field_name'].'"]\').val()));'
	);

if ($data['limited']) {
	$add_recovery_expression_button->setAttribute('disabled', 'disabled');
}

$recovery_expression_row = [
	(new CTextArea(
		$data['recovery_expression_field_name'],
		$data['recovery_expression_field_value'],
		['readonly' => $data['recovery_expression_field_readonly']]
	))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
	(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
	$add_recovery_expression_button
];

if ($data['recovery_expression_constructor'] == IM_TREE) {
	$recovery_expression_row[] = BR();

	if ($data['recovery_expression_formula'] === '') {
		// add button
		$add_recovery_expression_button = (new CSubmit('add_recovery_expression', _('Add')))
			->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$add_recovery_expression_button->setAttribute('disabled', 'disabled');
		}
		$recovery_expression_row[] = $add_recovery_expression_button;
	}
	else {
		// add button
		$add_recovery_expression_button = (new CSubmit('and_recovery_expression', _('And')))
			->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$add_recovery_expression_button->setAttribute('disabled', 'disabled');
		}
		$recovery_expression_row[] = $add_recovery_expression_button;

		// or button
		$or_recovery_expression_button = (new CSubmit('or_recovery_expression', _('Or')))->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$or_recovery_expression_button->setAttribute('disabled', 'disabled');
		}
		$recovery_expression_row[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$recovery_expression_row[] = $or_recovery_expression_button;

		// replace button
		$replace_recovery_expression_button = (new CSubmit('replace_recovery_expression', _('Replace')))
			->addClass(ZBX_STYLE_BTN_GREY);
		if ($data['limited']) {
			$replace_recovery_expression_button->setAttribute('disabled', 'disabled');
		}
		$recovery_expression_row[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$recovery_expression_row[] = $replace_recovery_expression_button;
	}
}
elseif ($data['recovery_expression_constructor'] != IM_FORCED) {
	$input_method_toggle = (new CButton(null, _('Expression constructor')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->onClick('javascript: '.
			'document.getElementById("toggle_recovery_expression_constructor").value=1;'.
			'document.getElementById("recovery_expression_constructor").value='.
				(($data['recovery_expression_constructor'] == IM_TREE) ? IM_ESTABLISHED : IM_TREE).';'.
			'document.forms["'.$triggersForm->getName().'"].submit();'
		);
	$recovery_expression_row[] = [BR(), $input_method_toggle];
}

$triggersFormList->addRow(_('Recovery expression'), $recovery_expression_row, null,
	'recovery_expression_constructor_row'
);

// Append expression table to form list.
if ($data['recovery_expression_constructor'] == IM_TREE) {
	$recovery_expression_table = (new CTable())
		->setAttribute('style', 'width: 100%;')
		->setId('exp_list')
		->setHeader([
			$data['limited'] ? null : _('Target'),
			_('Expression'),
			$data['limited'] ? null : _('Action'),
			_('Info')
		]);

	$allowed_testing = true;

	if ($data['recovery_expression_tree']) {
		foreach ($data['recovery_expression_tree'] as $i => $e) {
			if (!isset($e['recovery_expression']['levelErrors'])) {
				$errorImg = '';
			}
			else {
				$allowed_testing = false;
				$errors = [];

				if (is_array($e['recovery_expression']['levelErrors'])) {
					foreach ($e['recovery_expression']['levelErrors'] as $expVal => $errTxt) {
						if ($errors) {
							$errors[] = BR();
						}
						$errors[] = $expVal.':'.$errTxt;
					}
				}

				$errorImg = makeErrorIcon($errors);
			}

			// templated trigger
			if ($data['limited']) {
				// make all links inside inactive
				foreach ($e['list'] as &$obj) {
					if (gettype($obj) === 'object' && get_class($obj) === 'CSpan'
							&& $obj->getAttribute('class') == ZBX_STYLE_LINK_ACTION) {
						$obj->removeAttribute('class');
						$obj->onClick(null);
					}
				}
				unset($obj);
			}

			$recovery_expression_table->addRow(
				new CRow([
					!$data['limited']
						? (new CCheckBox('recovery_expr_target_single', $e['id']))
							->setChecked($i == 0)
							->onClick('check_target(this, '.TRIGGER_RECOVERY_EXPRESSION.');')
						: null,
					$e['list'],
					!$data['limited']
						? (new CCol(
							(new CButton(null, _('Remove')))
								->addClass(ZBX_STYLE_BTN_LINK)
								->onClick('javascript:'.
									' if (confirm('.CJs::encodeJson(_('Delete expression?')).')) {'.
										' delete_expression("'.$e['id'] .'", '.TRIGGER_RECOVERY_EXPRESSION.');'.
										' document.forms["'.$triggersForm->getName().'"].submit();'.
									' }'
								)
						))->addClass(ZBX_STYLE_NOWRAP)
						: null,
					$errorImg
				])
			);
		}
	}
	else {
		$allowed_testing = false;
		$data['recovery_expression_formula'] = '';
	}

	$testButton = (new CButton('test_expression', _('Test')))
		->onClick('openWinCentered("tr_testexpr.php?expression="'.
			'+encodeURIComponent(this.form.elements["recovery_expression"].value),'.
			'"ExpressionTest", 950, 650, "titlebar=no, resizable=yes, scrollbars=yes"); return false;'
		)
		->addClass(ZBX_STYLE_BTN_LINK);
	if (!$allowed_testing) {
		$testButton->setAttribute('disabled', 'disabled');
	}
	if ($data['recovery_expression_formula'] === '') {
		$testButton->setAttribute('disabled', 'disabled');
	}

	$wrapOutline = new CSpan([$data['recovery_expression_formula']]);
	$triggersFormList->addRow(null, [
		$wrapOutline,
		BR(),
		BR(),
		(new CDiv([$recovery_expression_table, $testButton]))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
	], null, 'recovery_expression_constructor_row');

	$input_method_toggle = (new CButton(null, _('Close expression constructor')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->onClick('javascript: '.
			'document.getElementById("toggle_recovery_expression_constructor").value=1;'.
			'document.getElementById("recovery_expression_constructor").value='.IM_ESTABLISHED.';'.
			'document.forms["'.$triggersForm->getName().'"].submit();'
		);
	$triggersFormList->addRow(null, [$input_method_toggle, BR()], null, 'recovery_expression_constructor_row');
}

$triggersFormList
	->addRow(_('PROBLEM event generation mode'),
		(new CRadioButtonList('type', (int) $data['type']))
			->addValue(_('Single'), TRIGGER_MULT_EVENT_DISABLED)
			->addValue(_('Multiple'), TRIGGER_MULT_EVENT_ENABLED)
			->setModern(true)
	)
	->addRow(_('Description'),
		(new CTextArea('comments', $data['comments']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow(_('URL'), (new CTextBox('url', $data['url']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH))
	->addRow(_('Severity'), new CSeverity(['name' => 'priority', 'value' => (int) $data['priority']]));

// append status to form list
if (empty($data['triggerid']) && empty($data['form_refresh'])) {
	$status = true;
}
else {
	$status = ($data['status'] == 0);
}
$triggersFormList->addRow(_('Enabled'), (new CCheckBox('status'))->setChecked($status));

// append tabs to form
$triggersTab = new CTabView();
if (!$data['form_refresh']) {
	$triggersTab->setSelected(0);
}
$triggersTab->addTab('triggersTab', _('Trigger'), $triggersFormList);

/*
 * Dependencies tab
 */
$dependenciesFormList = new CFormList('dependenciesFormList');
$dependenciesTable = (new CTable())
	->setAttribute('style', 'width: 100%;')
	->setHeader([_('Name'), _('Action')]);

foreach ($data['db_dependencies'] as $dependency) {
	$triggersForm->addVar('dependencies[]', $dependency['triggerid'], 'dependencies_'.$dependency['triggerid']);

	$depTriggerDescription = CHtml::encode(
		implode(', ', zbx_objectValues($dependency['hosts'], 'name')).NAME_DELIMITER.$dependency['description']
	);

	if ($dependency['flags'] == ZBX_FLAG_DISCOVERY_NORMAL) {
		$description = (new CLink($depTriggerDescription, 'triggers.php?form=update&triggerid='.$dependency['triggerid']))
			->setAttribute('target', '_blank');
	}
	else {
		$description = $depTriggerDescription;
	}

	$dependenciesTable->addRow(
		(new CRow([
			$description,
			(new CCol(
				(new CButton('remove', _('Remove')))
					->onClick('javascript: removeDependency("'.$dependency['triggerid'].'");')
					->addClass(ZBX_STYLE_BTN_LINK)
			))->addClass(ZBX_STYLE_NOWRAP)
		]))->setId('dependency_'.$dependency['triggerid'])
	);
}

$dependenciesFormList->addRow(_('Dependencies'),
	(new CDiv([
		$dependenciesTable,
		(new CButton('bnt1', _('Add')))
			->onClick('return PopUp("popup.php?srctbl=triggers&srcfld1=triggerid&reference=deptrigger&multiselect=1'.
				'&with_triggers=1&noempty=1");')
			->addClass(ZBX_STYLE_BTN_LINK)
	]))
		->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
		->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
);
$triggersTab->addTab('dependenciesTab', _('Dependencies'), $dependenciesFormList);

// append buttons to form
if (!empty($data['triggerid'])) {
	$deleteButton = new CButtonDelete(_('Delete trigger?'), url_params(['form', 'hostid', 'triggerid']));
	if ($data['limited']) {
		$deleteButton->setAttribute('disabled', 'disabled');
	}

	$triggersTab->setFooter(makeFormFooter(
		new CSubmit('update', _('Update')), [
			new CSubmit('clone', _('Clone')),
			$deleteButton,
			new CButtonCancel(url_param('hostid'))
		]
	));
}
else {
	$triggersTab->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('hostid'))]
	));
}

// append tabs to form
$triggersForm->addItem($triggersTab);

$widget->addItem($triggersForm);

return $widget;
