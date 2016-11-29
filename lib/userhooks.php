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

class UserHooks {

	private $logger;
	private $appName;
	private $userManager;
	private $userAttrManager;
	private $identityMapper;
	private $backendConfig;
	private $urlGenerator;
	private $request;
	private $logCtx;


	public function __construct($appName, $logger, $userManager,
				    $userAttrManager, $mailer, $identityMapper,
				    $backendConfig, $urlGenerator, $request) {
		$this->appName = $appName;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userAttrManager = $userAttrManager;
		$this->mailer = $mailer;
		$this->identityMapper = $identityMapper;
		$this->backendConfig = $backendConfig;
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Register hooks that depends on
	 * User_Shib backend to be activated
	 */
	public function register() {
		$this->userManager->listen(
			'\OC\User', 'postLogin',
			function ($user) {
				$this->onPostLogin($user);
			});
		$this->userManager->listen(
			'\OC\User', 'postCreateUser',
			function ($user, $password) {
				$this->onPostCreateUser($user, $password);
			});
		$this->userManager->listen(
			'\OC\User', 'postDelete',
			function ($user) {
				$this->onPostDelete($user);
			});
		$this->userManager->listen(
			'\OC\User', 'logout',
			function () { $this->onLogout(); });
	}

	/**
	 * Register hooks that doesn't depend on
	 * User_Shib backend to be activated
	 */
	public function registerPostSetPassword() {
		$this->userManager->listen(
			'\OC\User', 'postSetPassword',
			function($user, $password, $recoverPassword) {
				$this->onPostSetPassword(
					$user, $password, $recoverPassword);
			});
	}

	/**
	 * Update user attributes from Shibboleth
	 *
	 * @param \OC\User\User $user
	 */
	private function onPostLogin($user) {
		$uid = $user->getUID();
		if ($this->backendConfig['autoupdate']) {
			$this->logger->info(
				'Updating account: '. $uid, $this->logCtx);
			$this->userAttrManager->updateEmail();
			$this->userAttrManager->updateDisplayName();
			$this->userAttrManager->updateIdentity();
		}
	}

	/**
	 * Setup home directory and create SAML identity
	 * mapping to OC account after user creation
	 *
	 * @param \OC\User\User $user, string $password
	 */
	private function onPostCreateUser($user, $password) {
		$createdUid = $user->getUID();
		$loggedUid = $this->userAttrManager->getOcUid();

		if ($loggedUid !== $createdUid) { return; }

		// TODO: Couln't find a way of achiveing this using public API
		\OC_Util::setupFS($createdUid);
		\OC::$server->getUserFolder($createdUid);

		$this->mailer->mailNewUser($createdUid,
			$this->userAttrManager->getEmail());
	}

	/**
	 * Send mail notification about password change
	 *
	 * @param \OC\User\User $user 
	 * @param string $password newly set password
	 * @param string $recoverPassword
	 */
	private function onPostSetPassword($user, $password, $recoverPassword) {
		$this->mailer->mailPasswordChange($user->getUID());
	}

	/**
	 * Delete all identities associated with the user being deleted
	 *
	 * @param \OC\User\User $user user which has been deleted
	 */
	private function onPostDelete($user) {
		$ids = $this->identityMapper->getAllIdentities($user->getUID());
		foreach ($ids as &$identity) {
			// TODO: When automated identity consolidation is
			// finally implemented, uncomment this line
			// $this->identityMapper->removeIdenity($identity);
		}
	}

	/**
	 * Initiate Shibboleth Logout if neccessary
	 * and then return the user to standard internal
	 * logout URL (it will be in Request URI). If the
	 * user is already logged out of Shibboleth, do nothing.
	 */
	private function onLogout() {
		$suid = $this->userAttrManager->getShibUid();
		if ($suid) {
			$this->logger->info(sprintf(
				'Doing Shibboleth logout for: %s with session %s',
				$suid, $this->userAttrManager->getSessionId()),
				$this->logCtx);
			$shibLogout = $this->urlGenerator->linkTo('',
				'Shibboleth.sso/Logout',
				array(
					'return' =>
						$this->request->getRequestUri()
				)
			);
			\OCP\Response::redirect($shibLogout);
			exit();
		}
	}
}
