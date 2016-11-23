<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2016
 */

namespace OCA\User_Shib;

use PHPUnit_Framework_TestCase;

use OCA\User_Shib\Db\IdentityMapper;
use OC\AppFramework\Utility\TimeFactory;
use OCP\AppFramework\Http\TemplateResponse;

class UserAttributeManagerTest extends PHPUnit_Framework_TestCase {

	private $userid;
	private $userObj;
	private $attrMgr;
	private $logger;
	private $ocConfig;
	private $backendConfig;
	private $identityMapper;
	private $serverVars;

	public function setUp() {
		# Update keys to ones set by your Shibboleth instance
		$this->userid = 'john@doe.com';
		$this->serverVars = array(
			'du_eppn' => $this->userid,
			'du_cn' => 'John Doe',
			'du_givenName' => 'John',
			'du_surname' => 'Doe',
			'du_mail' => 'johndoe@gmail.com',
			'du_perunPrincipalName' => 'john@doe.com;doe@john.com',
			'du_perunUniqueGroupName' => 'VO_group1;VO_group2:subgroup',	
		);
		$this->backendConfig = array('active' => true, 'autocreate' => true,
			'autoupdate' => true, 'protected_groups' => array(),
			'required_attrs' => array('userid', 'email'));
		$this->ocConfig = \OC::$server->getConfig();		
		$this->userManager = \OC::$server->getUserManager();
		$this->logger = $this->getMockBuilder('OCP\ILogger')->getMock();

		$db = \OC::$server->getDatabaseConnection();
		$this->identityMapper = new IdentityMapper(
			'user_shib', $this->logger, $db, $this->userManager);
		$this->userObj = $this->userManager->createUser(
			$this->userid, '349dwqd9s9#@XsO');
		$this->addOcUid();
	}

	private function getAttrMgr() {
		$userMailer = $this->getMockBuilder('OCA\User_shib\UserMailer')->getMock();
		return new UserAttributeManager(
			'user_shib', $this->ocConfig, $this->backendConfig,
			$this->serverVars, $this->userManager,
			$this->identityMapper, $this->logger, $userMailer);
	}

	private function addOcUid($samlUid=null, $ocUid=null) {
		$samlUid = $samlUid === null? $this->userid : $samlUid;
		$ocUid = $ocUid === null? $samlUid : $ocUid;
		$this->identityMapper->addIdentity($samlUid,
			$this->serverVars['du_mail'], 0, $ocUid);
	}

	private function rmOcUid($samlUid=null) {
		$samlUid = $samlUid === null? $this->userid : $samlUid;
		$this->identityMapper->removeIdentity($samlUid);
	}

	public function testCheckAttributes() {
		// When all requirements met
		$this->assertTrue($this->getAttrMgr()->checkAttributes());
		// When there is no userid
		$origVars = $this->serverVars;
		unset($this->serverVars['du_eppn']);
		$this->assertFalse($this->getAttrMgr()->checkAttributes());
		$this->serverVars = $origVars;
		// When required attributes are missing
		unset($this->serverVars['du_mail']);
                $this->assertFalse($this->getAttrMgr()->checkAttributes());
		$this->serverVars = $origVars;
	}

	public function testGetShibUid() {
                $this->assertEquals(
			$this->serverVars['du_eppn'],
			$this->getAttrMgr()->getShibUid());
		unset($this->serverVars['du_eppn']);
                $this->assertFalse($this->getAttrMgr()->getShibUid());
	}

	public function testGetOcUid() {
		// Without any SAML uid
		unset($this->serverVars['du_eppn']);
		$this->assertFalse($this->getAttrMgr()->getOcUid());
		// For current SAML uid (without OC mapping)
		$this->serverVars['du_eppn'] = $this->userid;
		$this->rmOcUid();
		$this->assertFalse($this->getAttrMgr()->getOcUid());
		// For current SAML uid (with OC mapping)
		$this->addOcUid($this->userid, 'doe@john.com');
		$this->assertEquals('doe@john.com', $this->getAttrMgr()->getOcUid());
		$this->rmOcUid();
		// For different SAML uid (without OC mapping)
		$this->assertFalse(
			$this->getAttrMgr()->getOcUid('doe@john.com'));
		// For different SAML uid (with OC mapping)
		$this->addOcUid('doe@john.com', 'john@doe.com');
		$this->assertEquals('john@doe.com',
			$this->getAttrMgr()->getOcUid('doe@john.com'));
		$this->rmOcUid('doe@john.com');
	}

	public function testGetEmail() {
		$this->assertEquals($this->serverVars['du_mail'],
			$this->getAttrMgr()->getEmail());
		unset($this->serverVars['du_mail']);
		$this->assertFalse($this->getAttrMgr()->getEmail());
	}

	public function testGetDisplayName() {
		$this->assertEquals($this->serverVars['du_cn'],
			$this->getAttrMgr()->getDisplayName());
		unset($this->serverVars['du_cn']);
		$this->assertEquals($this->serverVars['du_givenName']
			. ' ' .$this->serverVars['du_surname'],
			$this->getAttrMgr()->getDisplayName());
		unset($this->serverVars['du_givenName'],
			$this->serverVars['du_surname']);
		$this->assertFalse($this->getAttrMgr()->getDisplayName());
	}

	public function testGetGroups() {
		$this->assertEquals(
			explode(';', $this->serverVars['du_perunUniqueGroupName']),
			$this->getAttrMgr()->getGroups());
		unset($this->serverVars['du_perunUniqueGroupName']);
		$this->assertFalse($this->getAttrMgr()->getGroups());
	}

	public function testGetExternalIds() {
		$this->assertEquals(
			explode(';', $this->serverVars['du_perunPrincipalName']),
			$this->getAttrMgr()->getExternalIds());
		unset($this->serverVars['du_perunPrincipalName']);
		$this->assertFalse($this->getAttrMgr()->getExternalIds());
	}

	public function testGetNewIdentities() {
		$this->assertEquals(array(1 => 'doe@john.com'), $this->getAttrMgr()->getNewIdentities());
		$this->serverVars['du_perunPrincipalName'] = array('john@doe.com');
		$this->assertEquals(array(), $this->getAttrMgr()->getNewIdentities());
		// Without any external ID
		unset($this->serverVars['du_perunPrincipalName']);
		$this->assertEquals(array(), $this->getAttrMgr()->getNewIdentities());
		// With External IDs == Internal IDs
		$this->addOcUid('doe@john.com', 'john@doe.com');
		$this->assertEquals(array(), $this->getAttrMgr()->getNewIdentities());
	}

	public function testGetUnlinkedIdentities() {
		$this->addOcUid('unlinked@doe.com', 'john@doe.com');
		$this->assertEquals(array(1 => 'unlinked@doe.com'),
			$this->getAttrMgr()->getUnlinkedIdentities());
		// Without any external ID
		unset($this->serverVars['du_perunPrincipalName']);
		$this->assertEquals(array(), $this->getAttrMgr()->getUnlinkedIdentities());
		// With External IDs == Internal IDs
		$this->rmOcUid('unlinked@doe.com');
		$this->assertEquals(array(), $this->getAttrMgr()->getUnlinkedIdentities());
	}

	public function testGetLastSeen() {
		$this->assertEquals(0, $this->getAttrMgr()->getLastSeen());
	}

	public function testUpdateEmail() {
		$omail = $this->serverVars['du_mail'];
		// Set new email
		$this->getAttrMgr()->updateEmail();
		$this->assertEquals($omail, $this->ocConfig->getUserValue(
			$this->userid, 'settings', 'email'));
		// Empty mail
		unset($this->serverVars['du_mail']);
		$this->getAttrMgr()->updateEmail();
		$this->assertEquals($omail,$this->ocConfig->getUserValue(
			$this->userid, 'settings', 'email'));
	}

	public function testUpdateDisplayName() {
		$dn = $this->getAttrMgr()->getDisplayName();
		// Set new display name
		$this->getAttrMgr()->updateDisplayName();
		$this->assertEquals($dn, $this->userObj->getDisplayName());
		// Try to set empy display name
		unset($this->serverVars['du_cn'],
			$this->serverVars['du_givenName'],
			$this->serverVars['du_surname']);
		$this->getAttrMgr()->updateDisplayName();
		$this->assertEquals($dn, $this->userObj->getDisplayName());
	}

	public function testUpdateIdentity() {
		$this->identityMapper->updateIdentity($this->userid, 'new@email.com', 12345);
		$identity = $this->identityMapper->getIdentity($this->userid);
		$this->assertEquals($identity->getSamlEmail(), 'new@email.com');
		$this->assertEquals($identity->getLastSeen(), 12345);
	}

	public function testCreateMappingCaseSensitivity() {
		$this->addOcUid('John@Doe.Com', 'John@Doe.Com');
		$this->assertFalse($this->getAttrMgr()->getOcUid('John@Doe.Com'));
	}

	public function testUpdateIdentityMappings() {
		$this->getAttrMgr()->updateIdentityMappings();
	}

	public function tearDown() {
		$this->rmOcUid();
		$this->rmOcUid('doe@john.com');
		$this->rmOcUid('unlinked@doe.com');
		parent::tearDown();
		\OC_User::deleteUser($this->userid);
	}
}
