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


class CComboItem extends CTag {

	public function __construct($value, $caption = null, $selected = null, $enabled = null) {
		parent::__construct('option', true);
		$this->setAttribute('value', $value);
		$this->addItem($caption);
		$this->setSelected($selected);

		if ($enabled !== null) {
			$this->setEnabled($enabled);
		}
	}

	public function setValue($value) {
		return $this->attributes['value'] = $value;
	}

	public function getValue() {
		return $this->getAttribute('value');
	}

	public function setCaption($value = null) {
		$this->addItem(nbsp($value));
	}

	public function setSelected($value = 'yes') {
		if ((is_string($value) && ($value == 'yes' || $value == 'selected' || $value == 'on')) || (is_int($value) && $value <> 0)) {
			return $this->attributes['selected'] = 'selected';
		}
		$this->removeAttribute('selected');
	}

	/**
	 * Enable or disable the element.
	 *
	 * @param bool $value
	 */
	public function setEnabled($value) {
		if ($value) {
			$this->removeAttribute('disabled');
		}
		else {
			$this->setAttribute('disabled', 'disabled');
		}
	}
}
