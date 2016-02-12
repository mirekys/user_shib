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

namespace OCA\User_Shib\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class IdentityMapper extends Mapper {

	private $logger;
	private $logCtx;
	private $appName;
	private $userManager;
	protected $db;

	public function __construct($appName, $logger, $db, $userManager) {
		parent::__construct($db, 'users_mapping');
		$this->appName = $appName;
		$this->logger = $logger;
		$this->db = $db;
		$this->userManager = $userManager;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Adds a new SAML -> OC identity mapping
	 *
	 * @param string $samlUid SAML uid of the user
	 * @param string $samlEmail SAML email of the user
	 * @param int $samlLastSeen timestamp the of last
	 * successfull login with this SAML uid
	 * @param string $ocUid target OC user uid
	 */
	public function addIdentity($samlUid, $samlEmail,
				    $samlLastSeen, $ocUid) {
		if (! $samlUid || $samlUid === '') {
			$this->logger->error('Cannot add SAML identity with'
				. ' empty SAML uid.', $this->logCtx);
			return;
		}
		$identity = new Identity();
		$identity->setSamlUid($samlUid);
		$identity->setSamlEmail($samlEmail);
		$identity->setLastSeen($samlLastSeen);
		$identity->setOcUid($ocUid);
		$this->logger->info(sprintf('Creating identity'
			.' mapping: %s -> %s.', $samlUid, $ocUid),
			$this->logCtx);
		try {
			$this->insert($identity);
		} catch (UniqueConstraintViolationException $e) {
			$this->logger->error(sprintf('Failed to create mapping:'
				.' %s -> %s. Mapping for this SAML identity'
				.' already exists.', $samlUid, $ocUid),
				$this->logCtx);
		}
	}

	/**
	 * Returns all SAML identities associated with the OC user
	 * 
	 * @param string $ocUid OC user uid
	 * @return array(\OCA\User_Shib\Db\Identity) associated SAML idenitites
	 */
	public function getAllIdentities($ocUid) {
		$result = array();
		if (! $this->userManager->userExists($ocUid)) {
			$this->logger->error(sprintf(
				'OC user with uid %s doesn\'t exist',
				$ocUid), $this->logCtx);
			return $result;
		}
		$sql = sprintf('SELECT * FROM `%s` WHERE `oc_uid`=?',
			$this->getTableName());
		return $this->findEntities($sql, [$ocUid]);
	}

	/**
	 * Finds an identity by its SAML uid
	 *
	 * @param string $samlUid SAML identity uid
	 * @return \OCA\User_Shib\Db\Identity|null identity DB entity
	 * or null if not found
	 */
	private function getIdentity($samlUid) {
		$identity = null;
		$sql = sprintf('SELECT * FROM `%s` WHERE `saml_uid`=?',
			$this->getTableName());

		if (! $samlUid || $samlUid === '') { return null; }

		try {
			$identity = $this->findEntity($sql, [$samlUid]);
		} catch (DoesNotExistException $e) {
			$this->logger->warning('Couldn\'t find identity'
				. ' for SAML uid: ' . $samlUid, $this->logCtx);
		} catch (MultipleObjectsReturnedException $e) {
			$this->logger->error('SAML identity: ' . $samlUid
				. ' is assigned to multiple OC uids!',
				$this->logCtx);
		}
		return $identity;
	}

        /**
         * Finds all identities by its $samlEmail or $samlUid
	 *
         * @param string $search pattern
         * @param int $limit the maximum number of rows
         * @param int $offset from which row we want to start
         * @return array(\OCA\User_Shib\Db\Identity) identities found
         */
        public function findIdentities($search = '', $limit=null, $offset=null) {
		$sql = sprintf('SELECT * FROM `%s` WHERE LOWER(`saml_uid`) = LOWER(?)'
			. ' OR LOWER(`saml_email`) = LOWER(?)',
			$this->getTableName());
		return $this->findEntities($sql,
			array($search, $search), $limit, $offset);
	}

	/**
	 * Removes a SAML identity mapping to OC uid
	 *
	 * @param string $samlUid SAML identity to be removed
	 */
	public function removeIdentity($samlUid) {
		$identity = $this->getIdentity($samlUid);
		if ($identity) {
			$this->logger->info('Deleting SAML identity: '
				. $samlUid, $this->logCtx);
			$this->delete($identity);
		}
	}

	/**
	 * Updates meta-information for this SAML identity 
	 *
	 * @param string $samlUid SAML identity uid
	 * @param string $samlEmail SAML email
	 * @param int $lastSeen timestamp of last access with this identity
	 */
	public function updateIdentity($samlUid, $samlEmail, $lastSeen) {
		$identity = $this->getIdentity($samlUid);
		if ($identity) {
			$identity->setSamlEmail($samlEmail);
			$identity->setlastSeen($lastSeen);
			$this->update($identity);
		}
	}

	/**
	 * Returns an OC account uid assigned with the SAML uid
	 *
	 * @param string $samlUid SAML identity uid
	 * @return string|false owncloud user uid or false
	 * if mapping to oc uid doesn't exist or is invalid
	 */
	public function getOcUid($samlUid) {
		$identity = $this->getIdentity($samlUid);
		if ($identity) {
			return $identity->getOcUid();
		} else {
			return false;
		}
	}
}
