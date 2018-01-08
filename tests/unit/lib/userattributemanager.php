<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2018
 */

namespace OCA\User_Shib;

use PHPUnit_Framework_TestCase;

use OCA\User_Shib\Db\IdentityMapper;
use OCA\User_Shib\UserMailer;
use OC\AppFramework\Utility\TimeFactory;
use OCP\AppFramework\Http\TemplateResponse;

class UserAttributeManagerTest extends PHPUnit_Framework_TestCase {

	private $userid;
	private $userObj;
	private $attrMgr;
	private $grpMgr;
	private $logger;
	private $l10n;
	private $mailer;
	private $ocConfig;
	private $backendConfig;
	private $identityMapper;
	private $serverVars;

	public function setUp() {
		# Update keys to ones set by your Shibboleth instance
		$this->userid = 'john@doe.com';
		$this->serverVars = array(
			'du_Shib-Session-ID' => 'abcd',
			'du_unique-id' => '1234@doe.com',
			'du_eppn' => $this->userid,
			'du_cn' => 'John Doe',
			'du_givenName' => 'John',
			'du_surname' => 'Doe',
			'du_mail' => 'johndoe@gmail.com',
			'du_perunPrincipalName' => 'john@doe.com;doe@john.com',
			'du_perunVoName' => 'VO_group1;VO_group2:subgroup',
		);
		$this->backendConfig = array('active' => true, 'autocreate' => true,
			'updategroups' => true, 'autocreate_groups' => true,
			'autoremove_groups' => true, 'autoupdate' => true,
			'protected_groups' => array('admin', 'gallery-users'),
			'group_filter' => '/.*/',
			'required_attrs' => array('userid', 'email'));
		$this->ocConfig = \OC::$server->getConfig();		
		$this->userManager = \OC::$server->getUserManager();
		$this->grpMgr = \OC::$server->getGroupManager();
		$this->logger = $this->getMockBuilder('OCP\ILogger')->getMock();
		$this->l10n = \OC::$server->getL10N('user_shib');
		$this->mailer = \OC::$server->getMailer();

		$db = \OC::$server->getDatabaseConnection();
		$this->identityMapper = new IdentityMapper(
			'user_shib', $this->logger, $db, $this->userManager);
		$this->userObj = $this->userManager->createUser(
			$this->userid, '349dwqd9s9#@XsO');
		$this->addOcUid();
	}

	private function getAttrMgr() {
		$userMailer = new UserMailer('user_shib', $this->l10n, $this->ocConfig,
				$this->mailer, new \OC_Defaults, $this->logger,
				'test@localhost', null, null, null);
		return new UserAttributeManager(
			'user_shib', $this->ocConfig, $this->backendConfig,
			$this->serverVars, $this->userManager, $this->grpMgr,
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
		// When there is no session id
		unset($this->serverVars['du_Shib-Session-ID']);
		$this->assertFalse($this->getAttrMgr()->checkAttributes());
		$this->serverVars = $origVars;
		// When required attributes are missing
		unset($this->serverVars['du_mail']);
                $this->assertFalse($this->getAttrMgr()->checkAttributes());
		$this->serverVars = $origVars;
	}

	public function testGetSessionId() {
		$this->assertEquals(
			$this->serverVars['du_Shib-Session-ID'],
			$this->getAttrMgr()->getSessionId());
		unset($this->serverVars['du_Shib-Session-ID']);
		$this->assertFalse($this->getAttrMgr()->getSessionId());
	}

	public function testGetUniqueId() {
		$this->assertEquals(
			$this->serverVars['du_unique-id'],
			$this->getAttrMgr()->getUniqueId());
		unset($this->serverVars['du_unique-id']);
		$this->assertFalse($this->getAttrMgr()->getUniqueId());
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

		$origGrps = $this->serverVars['du_perunVoName'];

		# 1) Groups are not filtered with disabled group filter
		$this->assertEquals(explode(';', $origGrps),
			$this->getAttrMgr()->getGroups());

		# 2) Test group filtering
		$this->serverVars['du_perunVoName'] = 'agrp1;agrp2;bgrp3';
		$this->backendConfig['group_filter'] = '/a.*/';
		$this->assertEquals(array('agrp1', 'agrp2'),
			$this->getAttrMgr()->getGroups());

		$this->backendConfig['group_filter'] = '/.*/';
		# 3) Attribute missing
		unset($this->serverVars['du_perunVoName']);
		$this->assertFalse($this->getAttrMgr()->getGroups());
		# 4) Attribute empty
		$this->serverVars['du_perunVoName'] = '';
		$this->assertFalse($this->getAttrMgr()->getGroups());
		# 5) Single group present
		$this->serverVars['du_perunVoName'] = 'grp1';
		$this->assertEquals(array('grp1'), $this->getAttrMgr()->getGroups());
		# 6) Multiple groups present
		$this->serverVars['du_perunVoName'] = $origGrps;
		$this->assertEquals(
			explode(';', $this->serverVars['du_perunVoName']),
			$this->getAttrMgr()->getGroups());
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

	public function testUpdateGroups() {
		$usr = $this->userManager->get($this->userid);
		$ocGrps = $this->grpMgr->getUserGroupIds($usr);
		$oldGrps = $this->serverVars['du_perunVoName'];

		# 0) Missing/empty groups attribute doesn't affect group membership
		unset($this->serverVars['du_perunVoName']);
		$this->getAttrMgr()->updateGroups();
		$this->assertEquals($ocGrps, $this->grpMgr->getUserGroupIds($usr));
		$this->serverVars['du_perunVoName'] = '';
		$this->getAttrMgr()->updateGroups();
		$this->assertEquals($ocGrps, $this->grpMgr->getUserGroupIds($usr));
		$this->serverVars['du_perunVoName'] = $oldGrps;

		# 1) Single group tests
		$this->backendConfig['autocreate_groups'] = false;
		$this->backendConfig['autoremove_groups'] = false;
		# 1.1) single saml group that doesn't exist in OC - shouldn' do anything
		$this->serverVars['du_perunVoName'] = 'newgrp';
		$this->getAttrMgr()->updateGroups();
		$this->assertEquals($ocGrps, $this->grpMgr->getUserGroupIds($usr));
		# 1.2) single saml group that exists in OC - should add the user
		$this->grpMgr->createGroup('newgrp');
		$this->serverVars['du_perunVoName'] = 'newgrp';
		$this->getAttrMgr()->updateGroups();
		$this->assertEquals(array_merge($ocGrps, array('newgrp')), $this->grpMgr->getUserGroupIds($usr));
		# 1.3) group that exists in OC but not in SAML - shouldn't remove the user
		$this->serverVars['du_perunVoName'] = '';
		$this->getAttrMgr()->updateGroups();
		$this->assertEquals(array_merge($ocGrps, array('newgrp')), $this->grpMgr->getUserGroupIds($usr));
		# 1.4) ad1.3) with autoremove on - should remove user from the group
		$this->backendConfig['autoremove_groups'] = true;
		$this->getAttrMgr()->updateGroups();
		$this->assertEquals($ocGrps, $this->grpMgr->getUserGroupIds($usr));
		# 1.5) ad1.2) with autocreate on - should create group and add user to it
		$this->backendConfig['autocreate_groups'] = true;
		$this->grpMgr->get('newgrp')->delete();
		$this->serverVars['du_perunVoName'] = 'newgrp';
		$this->getAttrMgr()->updateGroups();
		$this->assertTrue($this->grpMgr->groupExists('newgrp'));
		$this->assertEquals(array_merge($ocGrps, array('newgrp')), $this->grpMgr->getUserGroupIds($usr));
		$this->grpMgr->get('newgrp')->delete();
		# 1.6 try to get into a protected group - user should not be added
		$this->backendConfig['autocreate_groups'] = false;
		$this->backendConfig['autoremove_groups'] = false;
		$this->serverVars['du_perunVoName'] = 'admin';
		$this->getAttrMgr()->updateGroups();
                $this->assertFalse(in_array('admin', $this->grpMgr->getUserGroupIds($usr)));
		# 1.7 user should not be removed from a protected group
                $this->backendConfig['autoremove_groups'] = true;
		$this->grpMgr->get('admin')->addUser($usr);
		$this->serverVars['du_perunVoName'] = '';
		$this->getAttrMgr()->updateGroups();
		$this->assertTrue(in_array('admin', $this->grpMgr->getUserGroupIds($usr)));
		$this->grpMgr->get('admin')->removeUser($usr);

		# 2) Test multiple groups assignment
		$this->backendConfig['autocreate_groups'] = true;
                $this->backendConfig['autoremove_groups'] = true;
		# 2.1) Create & Assign to multiple groups 
		$this->serverVars['du_perunVoName'] = $oldGrps;
		$this->getAttrMgr()->updateGroups();
		$grps = explode(';', $oldGrps);
		foreach ($grps  as $grp) {
			$this->assertTrue($this->grpMgr->groupExists($grp));
		}
		$this->assertEquals(array_merge($ocGrps, $grps), $this->grpMgr->getUserGroupIds($usr));
		# 2.2) Remove from multiple groups
		$this->serverVars['du_perunVoName'] = '';
                $this->getAttrMgr()->updateGroups();
		foreach ($grps  as $grp) {
			$this->assertFalse(in_array($grp, $this->grpMgr->getUserGroupIds($usr)));
		}
		
		# 3) When removing last group user, delete group

	}

	public function testUpdateIdentity() {
		$this->identityMapper->updateIdentity($this->userid, 'new@email.com', 12345);
		$identity = $this->identityMapper->getIdentity($this->userid);
		$this->assertEquals($identity->getSamlEmail(), 'new@email.com');
		$this->assertEquals($identity->getLastSeen(), 12345);
	}

	public function testCreateMappingCaseSensitivity() {
		$this->addOcUid('John@Doe.Com', 'John@Doe.com');
		$this->assertFalse($this->getAttrMgr()->getOcUid('John@Doe.com'));
		$this->rmOcUid('John@Doe.Com');
	}

	public function testUpdateIdentityMappings() {
#		$this->getAttrMgr()->updateIdentityMappings();
	}

	public function tearDown() {
		$this->rmOcUid();
		$this->rmOcUid('doe@john.com');
		$this->rmOcUid('unlinked@doe.com');
		if (isset($this->serverVars['du_perunVoName'])) {
			foreach (explode(';', $this->serverVars['du_perunVoName']) as $grp) {
				$gr = $this->grpMgr->get($grp);
				if ($gr) {
					$gr->delete();
				}
			}
		}
		parent::tearDown();
		\OC::$server->getUserManager()->get($this->userid)->delete();
	}
}
