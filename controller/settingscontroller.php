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

namespace OCA\User_Shib\Controller;

use OCP\IRequest;
use OCP\IAppConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;

class SettingsController extends Controller {

	private $config;
	private $ocConfig;
	private $userid;
	private $timeFactory;
	private $l10n;

	public function __construct($appName, IRequest $request, $userid,
				    IAppConfig $appConfig, $ocConfig,
				    $timeFactory, $l10n) {
		parent::__construct($appName, $request);
		$this->config = $appConfig;
		$this->ocConfig = $ocConfig;
		$this->userid = $userid;
		$this->timeFactory = $timeFactory;
		$this->l10n = $l10n;
	}

	/**
	 * Prints the personal page settings
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function personalIndex() {
		$resetToken = $this->ocConfig->getUserValue(
				$this->userid, 'owncloud', 'lostpassword', '');
		$tokenDat = explode(':', $resetToken);
		if (count($tokenDat) === 2) {
			if ($tokenDat[0] < ($this->timeFactory->getTime() - 60*60*12)) {
				$tokenValid = false;
			} else {
				$tokenValid = true;
			}
		} else {
			$tokenValid = false;
		}
		return new TemplateResponse(
			$this->appName,
			'personal',
			array(
				'username' => $this->userid,
				'token_valid' => $tokenValid,
			),
			'blank'
		);
	}

	/**
	 * Prints the admin settings page form.
	 * 
	 * @NoCSRFRequired
	 */
	public function adminIndex() {
		// Load mapping configuration
		$prefix = $this->config->getValue(
				$this->appName, 'mapping_prefix', '');
		$uid = $this->config->getValue(
				$this->appName, 'mapping_userid', '');
		$dn = $this->config->getValue(
				$this->appName, 'mapping_dn', '');
		$firstname = $this->config->getValue(
				$this->appName, 'mapping_firstname', '');
		$surname = $this->config->getValue(
				$this->appName, 'mapping_surname', '');
		$email = $this->config->getValue(
				$this->appName, 'mapping_email', '');
		$groups = $this->config->getValue(
				$this->appName, 'mapping_group', '');
		$external = $this->config->getValue(
				$this->appName, 'mapping_external', '');
		// Load backend configuration
		$active = $this->config->getValue(
				$this->appName, 'active', 0);
		$autocreate = $this->config->getValue(
				$this->appName, 'autocreate', 0);
		$autoupdate = $this->config->getValue(
				$this->appName, 'autoupdate', 0);
		$updateidmap = $this->config->getValue(
				$this->appName, 'updateidmap', 0);
		$protectedGroups = $this->config->getValue(
				$this->appName, 'protected_groups', '');
		$requiredAttrs = explode(',', $this->config->getValue(
				$this->appName, 'required_attrs', ''));

		return new TemplateResponse(
			$this->appName,
			'admin',
			array(
				'mapping_prefix' => $prefix,
				'mapping_userid' => $uid,
				'mapping_dn' => $dn,
				'mapping_firstname' => $firstname,
				'mapping_surname' => $surname,
				'mapping_email' => $email,
				'mapping_groups' => $groups,
				'mapping_external' => $external,
				'active' => $active,
				'autocreate' => $autocreate,
				'autoupdate' => $autoupdate,
				'updateidmap' => $updateidmap,
				'protected_groups' => $protectedGroups,
				'required_attrs' => $requiredAttrs
			),
			'blank'
		);
	}

	/**
	 * Saves the SAML attribute mapping configuration.
	 */
	public function saveMappings($prefix, $userid, $dn, $firstname,
				     $surname, $email, $groups,
				     $external, $required) {
		$this->config->setValue(
			$this->appName, 'mapping_prefix', $prefix);
		$this->config->setValue(
			$this->appName, 'mapping_userid', $userid);
		$this->config->setValue(
			$this->appName, 'mapping_dn', $dn);
		$this->config->setValue(
			$this->appName, 'mapping_firstname', $firstname);
		$this->config->setValue(
			$this->appName, 'mapping_surname', $surname);
		$this->config->setValue(
			$this->appName, 'mapping_email', $email);
		$this->config->setValue(
			$this->appName, 'mapping_group', $groups);
		$this->config->setValue(
			$this->appName, 'mapping_external', $external);
		$this->config->setValue(
			$this->appName, 'required_attrs', $required);
		return array(
			'status' => 'success',
			'data' => array(
				'message' => (string) $this->l10n->t('Saved')
			)
		);
	}
	
	/**
	 * Saves the user backend configuration.
	 */
	public function saveBackendConfig($active, $autocreate, $autoupdate,
					  $updateidmap, $protectedGroups) {
		$this->config->setValue(
			$this->appName, 'active', $active);
		$this->config->setValue(
			$this->appName, 'autocreate', $autocreate);
		$this->config->setValue(
			$this->appName, 'autoupdate', $autoupdate);
		$this->config->setValue(
			$this->appName, 'updateidmap', $updateidmap);
		$this->config->setValue(
			$this->appName, 'protected_groups', $protectedGroups);
		return array(
			'status' => 'success',
			'data' => array(
				'message' => (string) $this->l10n->t('Saved')
			)
		);
	}
}
