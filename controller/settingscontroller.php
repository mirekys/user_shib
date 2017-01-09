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

namespace OCA\User_Shib\Controller;

use OCP\IRequest;
use OCP\IAppConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;

class SettingsController extends Controller {

	private $config;
	private $ocConfig;
	private $serverVars;
	private $userid;
	private $timeFactory;
	private $l10n;

	public function __construct($appName, IRequest $request, $userid,
				    IAppConfig $appConfig, $ocConfig, $serverVars,
				    $timeFactory, $l10n) {
		parent::__construct($appName, $request);
		$this->config = $appConfig;
		$this->ocConfig = $ocConfig;
		$this->serverVars = $serverVars;
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
		$sid = $this->config->getValue(
				$this->appName, 'mapping_sessid', '');
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
		$autocreateGroups = $this->config->getValue(
				$this->appName, 'autocreate_groups', 0);
		$autoremoveGroups = $this->config->getValue(
				$this->appName, 'autoremove_groups', 0);
		$autoupdate = $this->config->getValue(
				$this->appName, 'autoupdate', 0);
		$updateidmap = $this->config->getValue(
				$this->appName, 'updateidmap', 0);
		$updateGroups = $this->config->getValue(
				$this->appName, 'updategroups', 0);
		$protectedGroups = $this->config->getValue(
				$this->appName, 'protected_groups', '');
		$groupFilter = $this->config->getValue(
				$this->appName, 'group_filter', '');
		$expirationPeriod = $this->config->getValue(
				$this->appName, 'expiration_period', '');
		$expirationWarn = $this->config->getValue(
				$this->appName, 'expiration_warning', '');
		$requiredAttrs = explode(',', $this->config->getValue(
				$this->appName, 'required_attrs', ''));
		if ($prefix === '') {
			$shibVars = $this->serverVars;
		} else {
			$shibVars = array_filter($this->serverVars,
				function($var) use($prefix) {
					return strpos($var, $prefix) === 0;
				},ARRAY_FILTER_USE_KEY);
		}
		ksort($shibVars);

		return new TemplateResponse(
			$this->appName,
			'admin',
			array(
				'mapping_prefix' => $prefix,
				'mapping_sessid' => $sid,
				'mapping_userid' => $uid,
				'mapping_dn' => $dn,
				'mapping_firstname' => $firstname,
				'mapping_surname' => $surname,
				'mapping_email' => $email,
				'mapping_groups' => $groups,
				'mapping_external' => $external,
				'server_vars' => $shibVars,
				'active' => $active,
				'autocreate' => $autocreate,
				'autocreate_groups' => $autocreateGroups,
				'autoremove_groups' => $autoremoveGroups,
				'autoupdate' => $autoupdate,
				'updateidmap' => $updateidmap,
				'updategroups' => $updateGroups,
				'protected_groups' => $protectedGroups,
				'group_filter' => $groupFilter,
				'expiration_period' => $expirationPeriod,
				'expiration_warning' => $expirationWarn,
				'required_attrs' => $requiredAttrs
			),
			'blank'
		);
	}

	/**
	 * Saves the SAML attribute mapping configuration.
	 */
	public function saveMappings($prefix, $sessid, $userid, $dn, $firstname,
				     $surname, $email, $groups,
				     $external, $required) {
		$this->config->setValue(
			$this->appName, 'mapping_prefix', $prefix);
		$this->config->setValue(
			$this->appName, 'mapping_sessid', $sessid);
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
	public function saveBackendConfig($active, $autocreate, $autocreateGroups,
					  $autoremoveGroups, $autoupdate, $updateGroups,
					  $protectedGroups, $groupFilter, $expiration,
					  $expirationWarn) {
		$this->config->setValue(
			$this->appName, 'active', $active);
		$this->config->setValue(
			$this->appName, 'autocreate', $autocreate);
		$this->config->setValue(
			$this->appName, 'autocreate_groups', $autocreateGroups);
		$this->config->setValue(
			$this->appName, 'autoremove_groups', $autoremoveGroups);
		$this->config->setValue(
			$this->appName, 'autoupdate', $autoupdate);
		$this->config->setValue(
			$this->appName, 'updateidmap', false);
		$this->config->setValue(
			$this->appName, 'updategroups', $updateGroups);
		$this->config->setValue(
			$this->appName, 'protected_groups', $protectedGroups);
		$this->config->setValue(
			$this->appName, 'expiration_period', preg_replace('/[^0-9]/', '', $expiration));
		$this->config->setValue(
			$this->appName, 'expiration_warning', preg_replace('/[^0-9]/', '', $expirationWarn));

		# Validate regexp
		if ((preg_match($groupFilter, null) === false)
		    && ($groupFilter !== '')) {
			return array(
				'status' => 'error',
				'data' => array(
					'message' => 'Group Filter regexp is invalid.'
					.' Please refer to:'
					.' http://php.net/manual/en/reference.pcre.pattern.syntax.php'
				)
			);
		} else {
			$this->config->setValue($this->appName, 'group_filter', $groupFilter);
		}
		return array(
			'status' => 'success',
			'data' => array(
				'message' => (string) $this->l10n->t('Saved')
			)
		);
	}
}
