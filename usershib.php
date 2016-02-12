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

use OCP\Security\ISecureRandom;

class UserShib extends \OC_User_Backend implements \OCP\IUserBackend {

	private $logger;
	private $appName;
	private $backendConfig;
	private $userManager;
	private $userAttrManager;
	private $identityMapper;
	private $secureGen;
	private $logCtx;

	public function __construct($appName, $userManager, $userAttrManager,
				    $identityMapper, $logger, $backendConfig,
				    $secureGen) {
		$this->logger = $logger;
		$this->appName = $appName;
		$this->backendConfig = $backendConfig;
		$this->userManager = $userManager;
		$this->userAttrManager = $userAttrManager;
		$this->identityMapper = $identityMapper;
		$this->secureGen = $secureGen;
		$this->possibleActions = array(
			self::CHECK_PASSWORD => 'checkPassword',
		);
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Check if the user has been authenticated
	 * by Shibboleth and has all reqired attributes.
	 *
	 * Before returning user id, user account with
	 * this uid must exist within ownCloud.
	 * If 'autocreate' option is enabled, new account
	 * will be created when needed.
	 *
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string returns the user id or false
	 */
	public function checkPassword($uid, $password) {
		if (!$this->backendConfig['active']) { return false; }

		$uid = $this->userAttrManager->checkAttributes();

		// Reject login if uid is invalid or new
		// user account needs to be created manually.
		if (!$uid) { return false; }
		if (! $this->userManager->userExists($uid)) {
			if (! $this->backendConfig['autocreate']) {
				return false;
			} else {
				$this->logger->info(
					'Creating new account: '.$uid,
					$this->logCtx);
				$this->userManager->createUser(
					$uid, $this->secureGen->generate(21,
						ISecureRandom::CHAR_DIGITS
                        			. ISecureRandom::CHAR_LOWER
                        			. ISecureRandom::CHAR_UPPER
					));
			}
		}
		$this->logger->debug(sprintf('Logging in user: %s (%s)',
			$uid, $this->userAttrManager->getDisplayName()),
			$this->logCtx);
		return $uid;
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return false;
	}

	/**
	 * Backend display name
	 *
	 * @return string
	 */
	public function getBackendName() {
		return 'Shibboleth';
	}

	/**
	 * Get display names of users matching a pattern
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of all displayNames (value) and the corresponding uids (key)
	 */
	public function getDisplayNames($search = '',
					$limit = null, $offset = null) {
		$displayNames = array();
		$identities = $this->identityMapper->findIdentities(
				$search, $limit, $offset);
		foreach($identities as $identity) {
			$ocUid = $identity->getOcUid();
			$dn = \OCP\User::getDisplayName($ocUid);
			$displayNames[$ocUid] = $dn;
		}
		return $displayNames;
	}
}
