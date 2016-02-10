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

namespace OCA\User_Shib\AppInfo;

use OCP\AppFramework\App;
use OCA\User_shib\UserShib;
use OCA\User_shib\UserHooks;
use OCA\User_shib\UserMailer;
use OCA\User_shib\UserAttributeManager;
use OCA\User_shib\Db\IdentityMapper;
use OCA\User_shib\Controller\SessionController;
use OCA\User_shib\Controller\SettingsController;

class Application extends App {
	public function __construct (array $urlParams = array()) {
		parent::__construct('user_shib', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('SettingsController', function($c) {
			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserSession')->getUser()->getUID(),
				$c->query('AppConfig'),
				$c->query('L10N')
			);
		});

		$container->registerService('SessionController', function($c) {
			return new SessionController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('URLGenerator'),
				$c->query('Logger'),
				$c->query('UserSession')
			);
		});

		/**
		 * User Backend
		 */
		$container->registerService('UserBackend', function($c) {
			return new UserShib(
				$c->query('AppName'),
				$c->query('UserManager'),
				$c->query('UserAttributeManager'),
				$c->query('IdentityMapper'),
				$c->query('Logger'),
				$c->query('BackendConfig'),
				$c->query('SecureGenerator')
			);
		});
		
		$container->registerService('BackendConfig', function($c) {
			$appName = $c->query('AppName');
			$config = $c->query('AppConfig');
			$active = $config->getValue($appName, 'active');
			$autocreate = $config->getValue($appName,'autocreate');
			$autoupdate = $config->getValue($appName,'autoupdate');
			$pgrp = $config->getValue(
					$appName, 'protected_groups', array());
			$rqattrs = $config->getValue(
					$appName, 'required_attrs', array());
			return array(
				'active' => (bool) $active,
				'autocreate' => (bool) $autocreate,
				'autoupdate' => (bool) $autoupdate,
				'protected_groups' => $pgrp,
				'required_attrs' => $rqattrs
			);
		});

		/**
		 * Application Services
		 */
		$container->registerService('UserAttributeManager', function($c) {
			return new UserAttributeManager(
				$c->query('AppName'),
				$c->query('OcConfig'),
				$c->query('BackendConfig'),
				$_SERVER,
				$c->query('UserManager'),
				$c->query('IdentityMapper'),
				$c->query('Logger')
			);
		});

		$container->registerService('IdentityMapper', function($c) {
			return new IdentityMapper(
				$c->query('AppName'),
				$c->query('Logger'),
				$c->query('Db'),
				$c->query('UserManager')
			);
		});

		$container->registerService('UserMailer', function($c) {
			return new UserMailer(
				$c->query('AppName'),
				$c->query('L10N'),
				$c->query('Mailer'),
				$c->query('Defaults'),
				$c->query('Logger'),
				$c->query('DefaultMailAddress'),
				$c->query('URLGenerator')
			);
		});

		/**
		 * OC Server Services
		 */
		$container->registerService('UserManager', function($c) {
			return $c->query('ServerContainer')->getUserManager();
		});

		$container->registerService('UserSession', function($c) {
			return $c->query('ServerContainer')->getUserSession();
		});

		$container->registerService('AppConfig', function($c) {
			return $c->query('ServerContainer')->getAppConfig();
		});

		$container->registerService('Defaults', function($c) {
			return new \OC_Defaults;
		});

		$container->registerService('DefaultMailAddress', function($c) {
			return \OCP\Util::getDefaultEmailAddress('no-reply');
		});

		$container->registerService('Mailer', function($c) {
			return $c->query('ServerContainer')->getMailer();
		});

		$container->registerService('OcConfig', function($c) {
			return $c->query('ServerContainer')->getConfig();
		});

		$container->registerService('Logger', function($c) {
			return $c->query('ServerContainer')->getLogger();
		});

		$container->registerService('L10N', function($c) {
			return $c->query('ServerContainer')
				->getL10N($c->query('AppName'));
		});

		$container->registerService('URLGenerator', function($c) {
			return $c->query('ServerContainer')->getURLGenerator();
		});

		$container->registerService('Db', function($c) {
			return $c->query('ServerContainer')->getDb();
		});

		$container->registerService('SecureGenerator', function($c) {
			return $c->query('ServerContainer')->getSecureRandom()
				->getMediumStrengthGenerator();
		});

		/**
		 * Hooks
		 */

		$container->registerService('UserHooks', function($c) {
			return new UserHooks(
				$c->query('AppName'),
				$c->query('Logger'),
				$c->query('UserManager'),
				$c->query('UserAttributeManager'),
				$c->query('UserMailer'),
				$c->query('IdentityMapper'),
				$c->query('BackendConfig'),
				$c->query('URLGenerator'),
				$c->query('Request')
			);
		});
	}
}
