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
				    $secureGen, $expirationManager) {
		$this->logger = $logger;
		$this->appName = $appName;
		$this->backendConfig = $backendConfig;
		$this->userManager = $userManager;
		$this->userAttrManager = $userAttrManager;
		$this->identityMapper = $identityMapper;
		$this->secureGen = $secureGen;
		$this->expirationManager = $expirationManager;
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

		if (($this->backendConfig['active'] !== true)
		   || ($this->userAttrManager->checkAttributes() !== true)) {
			return false;
		}
		$uid = $this->userAttrManager->getOcUid();
		// Reject login if uid is invalid or new
		if (!$uid) { return false; }

		// Reject login if user account needs to be created manually.
		// Create new account if needed otherwise.
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
		} else {
			$user = $this->userManager->get($uid);
			# Re-enable user if account is expired
			if ($user && !$user->isEnabled()) {
				$this->expirationManager->unexpire($user);
			}
		}
		$this->logger->info(sprintf('Logging in user: %s (%s) '
			.'with session: %s',
			$uid, $this->userAttrManager->getDisplayName(),
			$this->userAttrManager->getSessionId()),
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
		/* If search is an existing ownCloud user uid,
		 * return nothing and rely on Database backend
		 * for providing the DisplayName */
		if (\OCP\User::userExists($search)) { return array(); }

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
