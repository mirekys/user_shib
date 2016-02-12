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
	private $l10n;

	public function __construct($appName, IRequest $request, $userid,
				    IAppConfig $appConfig, $ocConfig, $l10n) {
		parent::__construct($appName, $request);
		$this->config = $appConfig;
		$this->ocConfig = $ocConfig;
		$this->userid = $userid;
		$this->l10n = $l10n;
	}

	/**
	 * Prints the personal page settings
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function personalIndex() {
		return new TemplateResponse(
			$this->appName,
			'personal',
			array(
				'username' => $this->userid,
				'link_sent' => $this->ocConfig->getUserValue(
					$this->userid, 'owncloud',
					'lostpassword', false)
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
		$affiliation = $this->config->getValue(
				$this->appName, 'mapping_affiliation', '');
		// Load backend configuration
		$active = $this->config->getValue(
				$this->appName, 'active', 0);
		$autocreate = $this->config->getValue(
				$this->appName, 'autocreate', 0);
		$autoupdate = $this->config->getValue(
				$this->appName, 'autoupdate', 0);
		$protectedGroups = $this->config->getValue(
				$this->appName, 'protected_groups', '');
		// TODO: Implement this setting in settings template
		$requiredAttrs = $this->config->getValue(
				$this->appName, 'required_attrs', array());

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
				'mapping_affiliation' => $affiliation,
				'mapping_active' => $active,
				'mapping_autocreate' => $autocreate,
				'mapping_autoupdate' => $autoupdate,
				'active' => $active,
				'autocreate' => $autocreate,
				'autoupdate' => $autoupdate,
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
				     $surname, $email, $groups, $affiliation) {
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
			$this->appName, 'mapping_affiliation', $affiliation);
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
					  $protectedGroups, $rqattrs) {
		$this->config->setValue(
			$this->appName, 'active', $active);
		$this->config->setValue(
			$this->appName, 'autocreate', $autocreate);
		$this->config->setValue(
			$this->appName, 'autoupdate', $autoupdate);
		$this->config->setValue(
			$this->appName, 'protected_groups', $protectedGroups);
		$this->config->setValue(
			$this->appName, 'required_attrs', $rqattrs);
		return array(
			'status' => 'success',
			'data' => array(
				'message' => (string) $this->l10n->t('Saved')
			)
		);
	}
}
