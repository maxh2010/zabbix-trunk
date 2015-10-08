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


class CTweenBox {

	public function __construct(&$form, $name, $value = null, $size = 10) {
		$this->form = &$form;
		$this->name = $name.'_tweenbox';
		$this->varname = $name;
		$this->value = zbx_toHash($value);
		$this->id_l = $this->varname.'_left';
		$this->id_r = $this->varname.'_right';
		$this->lbox = new CListBox($this->id_l, null, $size);
		$this->rbox = new CListBox($this->id_r, null, $size);
		$this->lbox->setAttribute('style', 'width: 280px;');
		$this->rbox->setAttribute('style', 'width: 280px;');
	}

	public function setName($name = null) {
		if (is_string($name)) {
			$this->name = $name;
		}
	}

	public function getName() {
		return $this->name;
	}

	public function addItem($value, $caption, $selected = null, $enabled = true) {
		if (is_null($selected)) {
			if (is_array($this->value)) {
				if (isset($this->value[$value])) {
					$selected = 1;
				}
			}
			elseif (strcmp($value, $this->value) == 0) {
				$selected = 1;
			}
		}
		if ((is_bool($selected) && $selected)
				|| (is_int($selected) && $selected != 0)
				|| (is_string($selected) && ($selected == 'yes' || $selected == 'selected' || $selected == 'on'))) {
			$this->lbox->addItem($value, $caption, null, $enabled);
			$this->form->addVar($this->varname.'['.$value.']', $value);
		}
		else {
			$this->rbox->addItem($value, $caption, null, $enabled);
		}
		return $this;
	}

	public function get($caption_l = null, $caption_r = null) {
		if (empty($caption_l)) {
			$caption_l = _('In');
		}
		if (empty($caption_r)) {
			$caption_r = _('Other');
		}

		$grp_tab = (new CTable())
			->addClass('tweenBoxTable')
			->setAttribute('name', $this->name)
			->setId('id', zbx_formatDomId($this->name))
			->setCellSpacing(0)
			->setCellPadding(0);

		if (!is_null($caption_l) || !is_null($caption_r)) {
			$grp_tab->addRow([$caption_l, '', $caption_r]);
		}

		$add_btn = (new CButton('add', (new CSpan())->addClass('arrow-left')))
			->addClass(ZBX_STYLE_BTN_GREY)
			->onClick('moveListBoxSelectedItem("'.$this->varname.'", "'.$this->id_r.'", "'.$this->id_l.'", "add");');
		$rmv_btn = (new CButton('remove', (new CSpan())->addClass('arrow-right')))
			->addClass(ZBX_STYLE_BTN_GREY)
			->onClick('moveListBoxSelectedItem("'.$this->varname.'", "'.$this->id_l.'", "'.$this->id_r.'", "rmv");');

		$grp_tab->addRow([$this->lbox, (new CCol([$add_btn, BR(), $rmv_btn]))->addClass(ZBX_STYLE_CENTER), $this->rbox]);
		return $grp_tab;
	}

	public function show($caption_l = null, $caption_r = null) {
		if (empty($caption_l)) {
			$caption_l = _('In');
		}
		if (empty($caption_r)) {
			$caption_r = _('Other');
		}
		$tab = $this->get($caption_l, $caption_r);
		$tab->show();
		return $this;
	}

	public function toString() {
		$tab = $this->get();
		return $tab->toString();
	}
}
