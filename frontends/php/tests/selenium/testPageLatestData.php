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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/
?>
<?php
require_once(dirname(__FILE__).'/../include/class.cwebtest.php');

class testPageLatestData extends CWebTest
{
	public function testPageLatestData_SimpleTest()
	{
		$this->login('latest.php');
		$this->assertTitle('Latest data \[refreshed every 30 sec\]');
		$this->ok('LATEST DATA');
		$this->ok('ITEMS');
		$this->ok(array('Host','Group'));
		$this->ok('Filter');
		$this->ok(array('Host','Name','Last check','Last value','Change','History'));
	}

// Check that no real host or template names displayed
	public function testPageLatestData_NoHostNames()
	{
		$this->login('latest.php');
		$this->assertTitle('Latest data \[refreshed every 30 sec\]');
		$this->checkNoRealHostnames();
	}
}
?>
