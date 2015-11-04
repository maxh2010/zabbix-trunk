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

$widget = (new CWidget())
	->setTitle(_('Maps'))
	->setControls((new CForm('get'))
		->cleanItems()
		->addItem((new CList())
			->addItem(new CSubmit('form', _('Create map')))
			->addItem((new CButton('form', _('Import')))->onClick('redirect("conf.import.php?rules_preset=map")'))
		)
	);

// create form
$sysmapForm = (new CForm())->setName('frm_maps');

// create table
$sysmapTable = (new CTableInfo())
	->setHeader([
		(new CColHeader(
			(new CCheckBox('all_maps'))->onClick("checkAll('".$sysmapForm->getName()."', 'all_maps', 'maps');")
		))->addClass(ZBX_STYLE_CELL_WIDTH),
		make_sorting_header(_('Name'), 'name', $this->data['sort'], $this->data['sortorder']),
		make_sorting_header(_('Width'), 'width', $this->data['sort'], $this->data['sortorder']),
		make_sorting_header(_('Height'), 'height', $this->data['sort'], $this->data['sortorder']),
		_('Map')
	]);

foreach ($this->data['maps'] as $map) {
	$sysmapTable->addRow([
		new CCheckBox('maps['.$map['sysmapid'].']', $map['sysmapid']),
		new CLink($map['name'], 'sysmaps.php?form=update&sysmapid='.$map['sysmapid'].'#form'),
		$map['width'],
		$map['height'],
		new CLink(_('Edit'), 'sysmap.php?sysmapid='.$map['sysmapid']),
	]);
}

// append table to form
$sysmapForm->addItem([
	$sysmapTable,
	$this->data['paging'],
	new CActionButtonList('action', 'maps', [
		'map.export' => ['name' => _('Export')],
		'map.massdelete' => ['name' => _('Delete'), 'confirm' => _('Delete selected maps?')]
	])
]);

// append form to widget
$widget->addItem($sysmapForm);

return $widget;