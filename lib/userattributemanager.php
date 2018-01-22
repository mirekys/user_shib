<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2018 */

namespace OCA\User_Shib;

use OCA\User_shib\Db\Identity;

class UserAttributeManager {

	private $config;
	private $backendConfig;
	private $serverVars;
	private $appName;
	private $userManager;
	private $identityMapper;
	private $logger;
	private $logCtx;
	private $userMailer;

	public function __construct($appName, $ocConfig, $backendConfig,
				    $serverVars, $userManager, $groupManager,
				    $identityMapper, $logger, $userMailer) {
		$this->serverVars = $serverVars;
		$this->appName = $appName;
		$this->config = $ocConfig;
		$this->backendConfig = $backendConfig;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->identityMapper = $identityMapper;
		$this->logger = $logger;
		$this->userMailer = $userMailer;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Checks if the user has all required attributes
	 * and, if successfull, returns user's owncloud uid.
	 *
	 * @return boolean false if requirements not met
	 */
	public function checkAttributes() {
		// Shibboleth/SAML SID & uid are always required
		$sid = $this->getSessionId();
		$shibUid = $this->getShibUid();
		if (! $shibUid || !$sid ) { return false; }

		// Check for additional required attributes
		$missingAttrs = '';
		foreach ($this->getRequiredAttributes() as $attr) {
			if (! $this->getAttribute($attr)) {
				$missingAttrs .= $attr . ' ';
			}
		}
		if ($missingAttrs !== '') {
			$this->logger->warning(sprintf('User: %s is'
				. ' missing required attributes: %s',
				$shibUid, $missingAttrs), $this->logCtx);
			return false;
		}
		return true;
	}

	/**
	 * Get the Shibboleth Session ID from $_SERVER environment
	 *
	 * @return string|false if session id not found
	 */
	public function getSessionId() {
		return $this->getAttributeFirst('sessid');
	}

	/**
	 * Get the user id from $_SERVER environment
	 *
	 * @return string|false if attribute not found
	 */
	public function getShibUid() {
		return $this->getAttributeFirst('userid');
	}

	public function getUniqueId() {
		return $this->getAttributeFirst('uniqueid');
	}

	/**
	 * Get the internal user id, which corresponds to
	 * the external Shibboleth user id.
	 * If 'autocreate' option is enabled, it automatically tries
	 * to create new identity mapping from SAML uid to OC uid,
	 * when such a mapping doesn't exist yet
	 *
	 * @param string SAML identity of the user
	 * @return string|false corresponding OC uid or false if not found
	 */
	public function getOcUid($samlUid = null) {
		if (! $samlUid) {
			$samlUid = $this->getShibUid();
			if (! $samlUid) { return false; }
		}
		$ocUid = $this->identityMapper->getOcUid($samlUid);
		if (! $ocUid && $this->backendConfig['autocreate']) {
			$this->identityMapper->addIdentity(
				$samlUid, $this->getEmail(), time(), $samlUid);
			$ocUid = $this->identityMapper->getOcUid($samlUid);
		}
		return $ocUid;
	}

	/**
	 * Get the user email from $_SERVER environment
	 *
	 * @return string|false if attribute not found
	 */
	public function getEmail() {
		return $this->getAttributeFirst('email');
	}

	/**
	 * Get the user display name from $_SERVER environment
	 *
	 * @return string|false if attribute not found
	 */
	public function getDisplayName() {
		$dn = $this->getAttributeFirst('dn');
		if (! $dn) {
			$dn = '';
			$fn = $this->getAttributeFirst('firstname');
			$sn = $this->getAttributeFirst('surname');
			if ($fn) { $dn = $fn; }
			if ($sn) { $dn .= ' ' . $sn; }
		}
		return $dn === ''? false : $dn;
	}

	/**
	 * Get the user groups from $_SERVER environment
	 *
	 * @return array(string)|false if attribute not found
	 */
	public function getGroups() {
		$filter = $this->backendConfig['group_filter'];
		$groups = $this->getAttribute('group');
		if (!$groups) { return false; }

		$fgroups = array_filter($groups,
			function($group) use ($filter) {
				return preg_match($filter, $group) === 1;
			}
		);
		return $fgroups !== null ? $fgroups : false ;
	}

	/**
	 * Get all external identities associated with the user
	 * from the $_SERVER environment
	 *
	 * @return array(string)|false if attribute not found
	 */
	public function getExternalIds() {
		return $this->getAttribute('external');
	}

	/**
	 * Get user's External IDs, that are not yet
	 * known to ownCloud.
	 *
	 * @return array(string)
	 */
	public function getNewIdentities() {
		$result = array();
		$extIds = $this->getExternalIds();
		if (($extIds !== false ) && (! empty($extIds))) {
			$result = array_filter($extIds, function($id) {
				return ! $this->getOcUid($id);
			});
		}
		return $result;
	}

	/**
	 * Get user's ownCloud IDs that are no longer
	 * between his External IDs.
	 *
	 * @return array(string)
	 */
	public function getUnlinkedIdentities() {
		$result = array();
		$extUids = $this->getExternalIds();
		if (($extUids !== false ) && (! empty($extUids))) {
			$ids = $this->identityMapper->getAllIdentities($this->getOcUid());
			$result = array_diff(array_map(function ($id) {
					return $id->getSamlUid(); }, $ids),
					$extUids);
		}
		return $result;
	}

	/**
	 * Returns the timestamp of the user's last login
	 * or 0 if the user did never log in or doesn't exist yet
	 *
	 * @return int
	 */
	public function getLastSeen() {
		if ($user = $this->getUser()) {
			return $user->getLastLogin();
		}
		return 0;
	}

	/**
	 * Updates user's configured email with current one, if necessary
	 */
	public function updateEmail() {
		if ($user = $this->getUser()) {
			$uid = $user->getUID();
			$newEmail = $this->getEmail();
			$oldEmail = $user->getEMailAddress();
			if (($newEmail !== $oldEmail) && $newEmail) {
				$this->logger->warning(
					sprintf('Updating user: %s'
						.' email: %s -> %s',
						$uid, $oldEmail, $newEmail),
					$this->logCtx);
				$user->setEMailAddress($newEmail);
			}
		}
	}

	/**
	 * Updates user's configured display
	 * name with current one, if necessary
	 */
	public function updateDisplayName() {
		if ($user = $this->getUser()) {
			$newDn = $this->getDisplayName();
			$oldDn = $user->getDisplayName();
			if (($newDn !== $oldDn) && $newDn) {
				$user->setDisplayName($newDn);
			}
		}
	}

	/**
	 * Updates information of this
	 * SAML to OC identity mapping
	 */
	public function updateIdentity() {
		$uid = $this->getShibUid();
		$email = $this->getEmail();
		$lastSeen = $this->getLastSeen();
		$this->identityMapper->updateIdentity($uid, $email, $lastSeen);
	}

	/**
	 * Update group membership of the user
	 */
	public function updateGroups() {
		$samlGroups = $this->getGroups();
		if ($samlGroups === false) {
			if ($this->backendConfig['autoremove_groups'] === false) {
				return;
			} else {
				$samlGroups = array();
			}
		}

		$user = $this->getUser();
		$ocGroups = $this->groupManager->getUserGroupIds($user);
		$newGroups = array_diff($samlGroups, $ocGroups);
		$missingGroups = array_diff($ocGroups, $samlGroups);
		$skippedGroups = array();

		foreach ($newGroups as $grp) {
			if ($this->groupManager->groupExists($grp)) {
				$this->addToGroup($user, $grp);
			} elseif ($this->backendConfig['autocreate_groups'] === true) {
				$this->groupManager->createGroup($grp);
				$this->addToGroup($user, $grp);
			} else {
				$skippedGroups[] = $grp;
			}
		}
		if ($this->backendConfig['autoremove_groups'] === true) {
			foreach ($missingGroups as $grp) {
				$this->removeFromGroup($user, $grp);
			}
		}
		if (!empty($skippedGroups)) {
			$this->logger->warning(sprintf('Couldn\'t assign the '
				.'following non-existing groups to user: %s :(%s).'
				.' Please create the groups manually.',
				$this->getUser()->getUID(), implode(',', $skippedGroups)),
				$this->logCtx);
		}
	}

	/**
	 * Checks and updates user's identity mappings when necessary
	 */
	public function updateIdentityMappings() {
		if ($this->backendConfig['updateidmap'] === false) { return; }
		$currentOid = $this->getOcUid();

		// Link new identities to the current OC uid
		$newIds = $this->getNewIdentities();
		if ($currentOid !== false) {
			foreach ($newIds as $nid) {
				$this->identityMapper->addIdentity(
					$nid, '', 0, $currentOid);
			}
		}

		$unlinkedIds = $this->getUnlinkedIdentities();

		# We are interested only in mappings leading
		# to different than current OC uid
		$oeids = array_filter($oeids, function($id) {
				return $id !== $currentOid;
		});
		if (array_unique($oeids) !== array(false)){
			# There is conflicting mapping to another OC uid
			$this->logger->error(sprintf(
				'Multiple target OC uids for %s (%s)',
				$currentOid, print_r($oeids, TRUE)),
				$this->logCtx);
		} else {
			# Map extIds with missing OC account
			# mapping to $currentOid
			foreach ($oeids as $eid => $oeid) {
				$this->identityMapper->addIdentity(
					$eid, '', 0, $currentOid);
			}
		}
	}

	/**
	 * Try to get user object from uid attribute
	 *
	 * @return \OC\User\User|null if user doesn't exist
	 */
	private function getUser() {
		return $this->userManager->get($this->getOcUid());
	}

	/**
	 * Returns a first value of an user attribute
	 *
	 * @param string $name attribute name in the appConfig
	 * @return string|false attribute value or
	 * false when attribute not found
	 */
	private function getAttributeFirst($name) {
		$val = $this->getAttribute($name);
		if (is_array($val)) {
			if (count($val) >= 1) {
				return $val[0];
			} else {
				return false;
			}
		} else {
			return $val;
		}
	}

	/**
	 * Returns an user attribute value set by Shibboleth in
	 * the $_SERVER array.
	 *
	 * @param string $name attribute name in the appConfig
	 * @see \OCA\User_Shib\Controllers\SettingsController
	 * @return array(string)|false attribute value or
	 * false when attribute not found
	 */
	private function getAttribute($name) {
		$mappingName = $this->getAttrMapping($name);
		if (($mappingName)
		    && (array_key_exists($mappingName, $this->serverVars))) {
			$val = $this->serverVars[$mappingName];
			if (is_array($val)) {
				return $val;
			} elseif ($val === '') {
				return array();
			} else {
				return explode(';', $val);
			}
		} else {
			$this->logger->warning(sprintf(
				'Attribute %s [%s] not found',
				$name, $mappingName),
				$this->logCtx);
			return false;
		}
	}

	 /**
	 * Returns a configured attribute mapping name
	 * and prepends a mapping_prefix to it, when necessary.
	 *
	 * @param string $key mapping key
	 * @see \OCA\User_Shib\Controllers\SettingsController
	 * @return string prefixed attribute mapping
	 */
	private function getAttrMapping($key) {
		$prefix = $this->config->getAppValue(
				$this->appName, 'mapping_prefix', '');
		$value = $this->config->getAppValue(
					$this->appName,
					'mapping_' . $key,
					false
		);
		if (! $value) { return false; }
		return strpos($value, $prefix) === 0? $value : $prefix . $value;
	}

	/**
	 * Get a list of required attribute names
	 * that must be set prior to user's login.
	 *
	 * @return array(string) required attributes
	 */
	private function getRequiredAttributes() {
		return $this->backendConfig['required_attrs'];
	}

	/**
         * add a user to the group
         *
         * @param \OC\User\User $user
	 * @param string group id
         */
	private function addToGroup($user, $gid) {
		if (!in_array($gid, $this->backendConfig['protected_groups'])) {
			$this->groupManager->get($gid)->addUser($user);
			$this->logger->info(sprintf('Adding user: %s to group: %s',
				$user->getUID(), $gid), $this->logCtx);
		} else {
			$this->logger->warning(sprintf(
				'Refused to add user: %s to protected group: %s',
				$user->getUID(), $gid), $this->logCtx);
		}
	}

	private function removeFromGroup($user, $gid) {
		if (!in_array($gid, $this->backendConfig['protected_groups'])) {
			$this->logger->info(sprintf('Removing user: %s from group: %s',
				$user->getUID(), $gid), $this->logCtx);
			$this->groupManager->get($gid)->removeUser($user);
		}
	}
}
