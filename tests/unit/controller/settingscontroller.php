<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2017
 */

namespace OCA\User_Shib\Controller;

use PHPUnit_Framework_TestCase;

use OCP\AppFramework\Http\TemplateResponse;
use OC\AppFramework\Utility\TimeFactory;


class SettingsControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;
	private $userid = 'john@doe.com';
	private $ocConfig = null;
	private $timeFactory = null;

	public function setUp() {
		$request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$appConfig = $this->getMockBuilder('OCP\IAppConfig')->getMock();
		$l10n = $this->getMockBuilder('OCP\IL10N')->getMock();
		$this->ocConfig = \OC::$server->getConfig();
		$this->timeFactory = new TimeFactory();
		$this->serverVars = array(
			'du_Shib-Session-ID' => 'abcd',
			'du_eppn' => $this->userid,
			'du_cn' => 'John Doe',
			'du_givenName' => 'John',
			'du_surname' => 'Doe',
			'du_mail' => 'johndoe@gmail.com',
			'du_perunPrincipalName' => 'john@doe.com;doe@john.com',
			'du_perunVoName' => 'VO_group1;VO_group2:subgroup',
		);
		$this->setResetToken('');
		$this->controller = new SettingsController(
			'user_shib', $request, $this->userid, $appConfig,
			$this->ocConfig, $this->serverVars, $this->timeFactory, $l10n);
	}

	/**
	 * Personal index tests
	 */
	public function testPersonalIndexNoToken() {
		$result = $this->controller->personalIndex();
		$this->ocConfig->setUserValue(
                        $this->userid, 'owncloud', 'lostpassword', '');
		$this->assertEquals(
			['username' => $this->userid, 'token_valid' => false],
			$result->getParams());
		$this->assertEquals('personal', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}

	public function testPersonalIndexValidToken() {
		$this->setResetToken(time() -600 . ':abcd');

		$result = $this->controller->personalIndex();
		$this->assertEquals(
			['username' => $this->userid, 'token_valid' => true],
			$result->getParams());
		$this->assertEquals('personal', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}

	public function testPersonalIndexExpiredToken() {
		$this->setResetToken($this->timeFactory->getTime() - 3700*12 . ':abcd');
		
		$result = $this->controller->personalIndex();
		$this->assertEquals(
			['username' => $this->userid, 'token_valid' => false],
			$result->getParams());
                $this->assertEquals('personal', $result->getTemplateName());
                $this->assertTrue($result instanceof TemplateResponse);
	}

	private function setResetToken($value) {
		$this->ocConfig->setUserValue(
			$this->userid, 'owncloud', 'lostpassword', $value);
	}

	public function tearDown() {
		$this->setResetToken('');
		parent::tearDown();
	}
}
