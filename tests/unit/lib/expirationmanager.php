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

namespace OCA\User_Shib;

use PHPUnit_Framework_TestCase;

use OCA\User_Shib\Db\IdentityMapper;
use OCA\User_Shib\UserMailer;
use OC\AppFramework\Utility\TimeFactory;
use OCP\AppFramework\Http\TemplateResponse;

class ExpirationManagerTest extends PHPUnit_Framework_TestCase {

	private $userid;
	private $userObj;
	private $expMgr;
	private $logger;
	private $l10n;
	private $mailer;
	private $ocConfig;
	private $backendConfig;
	private $identityMapper;

	public function setUp() {
		$this->userid = 'john@doe.com';
		$this->backendConfig = array('expiration_period' => 0);
		$this->ocConfig = \OC::$server->getConfig();		
		$this->userManager = \OC::$server->getUserManager();
		$this->grpMgr = \OC::$server->getGroupManager();
		$this->logger = $this->getMockBuilder('OCP\ILogger')->getMock();
		$this->l10n = \OC::$server->getL10N('user_shib');
		$this->mailer = \OC::$server->getMailer();
		$urlGenerator = \OC::$server->getURLGenerator();
		$userMailer = new UserMailer('user_shib', $this->l10n, $this->ocConfig,
				$this->mailer, new \OC_Defaults, $this->logger,
				'test@localhost', $urlGenerator, null, null);

		$db = \OC::$server->getDatabaseConnection();
		$this->identityMapper = new IdentityMapper(
			'user_shib', $this->logger, $db, $this->userManager);

		$this->expMgr = new ExpirationManager('user_shib', $this->backendConfig,
					$this->identityMapper, $this->userManager,
					new TimeFactory(), $userMailer, $this->logger);

		$this->userObj = $this->userManager->createUser(
			$this->userid, '349dwqd9s9#@XsO');
		$this->addOcUid(0);
	}

	private function addOcUid($lastSeen, $samlUid=null, $ocUid=null) {
		$samlUid = $samlUid === null? $this->userid : $samlUid;
		$ocUid = $ocUid === null? $samlUid : $ocUid;
		$this->identityMapper->addIdentity($samlUid, '', $lastSeen, $ocUid);
	}

	private function rmOcUid($samlUid=null) {
		$samlUid = $samlUid === null? $this->userid : $samlUid;
		$this->identityMapper->removeIdentity($samlUid);
	}

	public function testGetUsers2Expire() {
		$usrs2expire = $this->expMgr->getUsersToExpire(0);
		$this->assertTrue(count($usrs2expire) === 1);
		$this->assertTrue($usrs2expire[0] instanceof \OC\User\User);
		$this->assertEquals($this->userid, $usrs2expire[0]->getUID());
	}

	public function testExpire() {
		$this->assertTrue($this->userObj->isEnabled());
		$this->expMgr->expire($this->userObj);
		$this->assertFalse($this->userObj->isEnabled());
		$this->assertEquals('0 B', $this->userObj->getQuota());
	}

	public function testUnexpire() {
		$this->expMgr->expire($this->userObj);
		$this->assertFalse($this->userObj->isEnabled());
		$this->expMgr->unexpire($this->userObj);
		$this->assertTrue($this->userObj->isEnabled());
		$this->assertTrue($this->userObj->getQuota() !== '0 B');
	}

	public function tearDown() {
		$this->rmOcUid();
		parent::tearDown();
		\OC::$server->getUserManager()->get($this->userid)->delete();
	}
}
