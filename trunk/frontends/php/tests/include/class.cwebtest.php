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
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

require_once(dirname(__FILE__).'/../../include/defines.inc.php');
require_once(dirname(__FILE__).'/dbfunc.php');

class CWebTest extends PHPUnit_Extensions_SeleniumTestCase
{
	protected $captureScreenshotOnFailure = TRUE;
	protected $screenshotPath = '/home/hudson/public_html/screenshots';
	protected $screenshotUrl = 'http://192.168.3.32/~hudson/screenshots';

	// List of strings that should NOT appear on any page
	public $failIfExists = array (
		"pg_query",
		"Error in",
		"expects parameter",
		"Undefined index",
		"Undefined variable",
		"Undefined offset",
		"Fatal error",
		"Call to undefined method",
		"Invalid argument supplied",
		"Missing argument",
		"Warning:",
		"PHP notice",
		"PHP warning",
		"Use of undefined",
		"You must login",
		"DEBUG INFO",
		"Cannot modify header"
	);

	// List of strings that SHOULD appear on every page
	public $failIfNotExists = array (
		"Help",
		"Get support",
		"Print",
		"Profile",
		"Logout",
		"Connected",
		"Admin"
	);

	protected function setUp()
	{
		global $DB;

		$this->setHost('hudson');
		$this->setBrowser('*firefox');
		if(strstr(PHPUNIT_URL,'http://'))
		{
			$this->setBrowserUrl(PHPUNIT_URL);
		} else {
			$this->setBrowserUrl('http://hudson/~hudson/'.PHPUNIT_URL.'/frontends/php/');
		}

/*		if(!DBConnect($error))
		{
			$this->assertTrue(FALSE,'Unable to connect to the database:'.$error);
			exit;
		}*/

		if(!isset($DB['DB'])) DBConnect($error);
	}

	protected function tearDown()
	{
		DBclose();
	}

	public function login($url = NULL)
	{
		$this->open('index.php');
		$this->wait();
		// Login if not logged in already
		if($this->isElementPresent('id=password'))
		{
			$this->input_type('name','Admin');
			$this->input_type('password','zabbix');
			$this->click('enter');
			$this->wait();
		}
		if(isset($url))
		{
			$this->open($url);
			$this->wait();
		}
		$this->ok('Admin');
		$this->nok('Login name or password is incorrect');
	}

	public function logout()
	{
		$this->click('link=Logout');
		$this->wait();
	}

	public function checkFatalErrors()
	{
		foreach($this->failIfExists as $str)
		{
			$this->assertTextNotPresent($str,"Chuck Norris: I do not expect string '$str' here.");
		}
	}

	public function ok($strings)
	{
		if(!is_array($strings))	$strings=array($strings);
		foreach($strings as $string)
			$this->assertTextPresent($string,"Chuck Norris: I expect string '$string' here");
	}

	public function nok($strings)
	{
		if(!is_array($strings))	$strings=array($strings);
		foreach($strings as $string)
			$this->assertTextNotPresent($string,"Chuck Norris: I do not expect string '$string' here");
	}

	public function button_click($a)
	{
		$this->click($a);
	}

	public function href_click($a)
	{
		$this->click("xpath=//a[contains(@href,'$a')]");
	}

	public function checkbox_select($a)
	{
		if(!$this->isChecked($a)) $this->click($a);
	}

	public function checkbox_unselect($a)
	{
		if($this->isChecked($a)) $this->click($a);
	}

	public function input_type($id,$str)
	{
		$this->type($id,$str);
	}

	public function dropdown_select($id,$str)
	{
		$this->assertSelectHasOption($id,$str);
		$this->select($id,$str);
	}

	public function dropdown_select_wait($id,$str)
	{
		$selected = $this->getSelectedLabel($id);
		$this->dropdown_select($id, $str);
		// Wait only if drop down selection was changed
		if($selected != $str)	$this->wait();
	}

	public function wait()
	{
		$this->waitForPageToLoad();
		$this->checkFatalErrors();
	}

	public function tab_switch($tab){
		// switches tab by receiving tab title text
		$this->click("xpath=//div[@id='tabs']/ul/li/a[text()='$tab']");
		$this->waitForElementPresent("xpath=//li[contains(@class, 'ui-tabs-selected')]/a[text()='$tab']");
		$this->checkFatalErrors();
	}

	public function template_unlink($template){
		// clicks button named "Unlink" next to template named $template
		$this->click("xpath=//div[text()='$template']/../div[@class='dd']/input[@value='Unlink']");
	}

	public function template_unlink_and_clear($template){
		// WARNING: not tested yet
		// clicks button named "Unlink and clear" next to template named $template
		$this->click("xpath=//div[text()='$template']/../div[@class='dd']/input[@value='Unlink']/../input[@value='Unlink and clear']");
	}

	public function template_link($template){
		// adds template $template to the list of linked template list
		// for now, ignores the fact that template might be already linked
//		$this->button_click('add');
		// the above does not seem to work, thus this ugly method has to be used - at least until buttons get unique names...
		$this->click("//input[@id='add' and @name='add' and @value='Add' and @type='button' and contains(@onclick, 'return PopUp')]");

		// zbx_popup is the default opened window id if none is passed
		$this->waitForPopUp('zbx_popup',6000);
		$this->selectWindow('zbx_popup');
		$this->checkFatalErrors();

		$this->dropdown_select_wait('groupid','Templates');

		$this->check("//input[@value='$template' and @type='checkbox']");
		$this->button_click('select');

		$this->selectWindow();
		$this->wait();
		$this->ok('Template_Linux');

	}

}
?>
