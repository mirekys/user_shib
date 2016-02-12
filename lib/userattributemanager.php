<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2016 */

namespace OCA\User_Shib;

class UserAttributeManager {

	private $config;
	private $backendConfig;
	private $serverVars;
	private $appName;
	private $userManager;
	private $identityMapper;
	private $logger;
	private $logCtx;

	public function __construct($appName, $ocConfig, $backendConfig,
				    $serverVars, $userManager, $identityMapper,
				    $logger) {
		$this->serverVars = $serverVars;
		$this->appName = $appName;
		$this->config = $ocConfig;
		$this->backendConfig = $backendConfig;
		$this->userManager = $userManager;
		$this->identityMapper = $identityMapper;
		$this->logger = $logger;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Checks if the user has all required attributes
	 * and, if successfull, returns user's owncloud uid.
	 *
	 * @return false|string false if requirements not met, OC uid otherwise
	 */
	public function checkAttributes() {
		// Shibboleth/SAML uid is always required
		$shibUid = $this->getShibUid();
		if (! $shibUid ) { return false; }
		// TODO: Move email to $backendConfig['required_attrs']
		// TODO: Require email for all users (not only newly created)
		// when all IdP's will provide it for us. Then move getOcUid
		// call to the end of this method.
		$ocUid = $this->getOcUid();
		if (! $this->getEmail() && ! \OCP\User::userExists($ocUid)) {
			return false;
		}
		// Check for additional required attributes
		$missingAttrs = '';
		foreach ($this->backendConfig['required_attrs'] as &$attr) {
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
		return $ocUid;
	}

	/**
	 * Get the user id from $_SERVER environment
	 *
	 * @return string|false if attribute not found
	 */
	public function getShibUid() {
		return $this->getAttributeFirst('userid');
	}

	/**
	 * Get the internal user id, which corresponds to
	 * the external Shibboleth user id.
	 * If 'autocreate' option is enabled, it automatically tries
	 * to create new identity mapping from SAML uid to OC uid,
	 * when such a mapping doesn't yet exist
	 *
	 * @return string|false corresponding OC uid or false if not found
	 */
	public function getOcUid() {
		$shibUid = $this->getShibUid();
		if (! $shibUid) { return false; }

		$ocUid = $this->identityMapper->getOcUid($shibUid);
		if (! $ocUid && $this->backendConfig['autocreate']) {
			$this->identityMapper->addIdentity(
				$shibUid, $this->getEmail(), time(), $shibUid);
			$ocUid = $this->identityMapper->getOcUid($shibUid);
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
		return $dn;
	}

	/**
	 * Get the user groups from $_SERVER environment
	 *
	 * @return string|false if attribute not found
	 */
	public function getGroups() {
		return $this->getAttribute('groups');
	}

	/**
	 * Get the user affiliation from $_SERVER environment
	 *
	 * @deprecated This attribute is not needed and will be
	 * removed in next versions
	 * @return string|false if attribute not found
	 */
	public function getAffiliation() {
		return $this->getAttribute('affiliation');
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
			$oldEmail = $this->config->getUserValue(
					$uid, 'settings', 'email');
			if (($newEmail !== $oldEmail) && $newEmail) {
				$this->logger->warning(
					sprintf('Updating user: %s'
						.' email: %s -> %s',
						$uid, $oldEmail, $newEmail),
					$this->logCtx);
				$this->config->setUserValue(
					$uid, 'settings', 'email', $newEmail);
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
			return $val[0];
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
	 * @return string|array(string)|false attribute value or
	 * false when attribute not found
	 */
	private function getAttribute($name) {
		$mappingName = $this->getAttrMapping($name);
		if (($mappingName)
		    && (array_key_exists($mappingName, $this->serverVars))) {
			$val = $this->serverVars[$mappingName];
			if (strpos($val, ';') !== FALSE) {
				return explode(';', $val);
			} else {
				return $val;
			}
		} else {
			$this->logger->debug(sprintf(
				'Attribute %s [%s] not found',
				$name, $mappingName),
				$this->logCtx);
			return false;
		}
	}

	 /**
	 * Returns a configured attribute mapping name
	 * and prepends a mapping_prefix to it.
	 *
	 * @param string $key mapping key
	 * @see \OCA\User_Shib\Controllers\SettingsController
	 * @return string prefixed attribute mapping
	 */
	private function getAttrMapping($key) {
		$prefix = $this->config->getAppValue(
				$this->appName, 'mapping_prefix', '');
		$value = $prefix . $this->config->getAppValue(
					$this->appName,
					'mapping_' . $key,
					false
		);
		return $value;
	}
}
