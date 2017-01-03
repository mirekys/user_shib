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

namespace OCA\User_Shib;

class ExpirationManager {

	private $appName;
	private $backendConfig;
	private $identityMapper;
	private $userManager;
	private $timeFactory;
	private $mailer;
	private $logger;
	private $logCtx;

	public function __construct($appName, $backendConfig,
				    $identityMapper, $userManager,
				    $timeFactory, $mailer, $logger) {
		$this->appName = $appName;
		$this->backendConfig = $backendConfig;
		$this->identityMapper = $identityMapper;
		$this->userManager = $userManager;
		$this->timeFactory = $timeFactory;
		$this->mailer = $mailer;
		$this->logger = $logger;
		$this->logCtx = array('app' => $this->appName);
	}

	/*
	 * Expire all users which did not log in with Shibboleth
	 * for longer than the expiration period.
	 */
	public function expireUsers() {
		$period = $this->backendConfig['expiration_period'];
		if ($period >= 0) { return; }	

		$currTs = $this->timeFactory->getTime();
		$expTreshold = $currTs - ($period * 24 * 3600);

		$this->logger->info('Expiring users with last_seen < '
			. $expTreshold, $this->logCtx);
		$expired = $this->getUsersToExpire($expTreshold);
		foreach ($expired as $user) {
			$this->expire($user);
		}
	}

	/*
	 * Returns the users that are past their expiration period
	 *
	 * @param int expiration treshold timestamp
	 * @return array(\OC\User\User)
	 */
	public function getUsersToExpire($expTreshold) {
		$uids = $this->identityMapper->findExpired($expTreshold);
		$usrs = array();
		foreach ($uids as $uid) {
			$usrs[] = $this->userManager->get($uid);
		}
		return $usrs;
	}

	/*
	 * Disables the account and sends the expiration notice to the user
	 *
	 * @param \OC\User\User $user to be expired
	 */
	public function expire($user) {
		if (!$user) { return; }
		if (!$user->isEnabled()) { return; }

		$user->setEnabled(false);
		$user->setQuota('0 B');
		$user->setDisplayName($user->getDisplayName().' (expired)');
		$this->mailer->mailExpirationNotice('bauer@cesnet.cz');
		$this->logger->info('Expired account: '
			. $user->getUID(), $this->logCtx);
	}

	/*
	 * Re-enables an expired user account
	 *
	 * @param \OC\User\User $user to be re-enabled
	 */
	public function unexpire($user) {
		if (!$user) { return; }
		if ($user->isEnabled()) { return; }

		$user->setEnabled(true);
		$user->setQuota('default');
		$dn = $user->getDisplayName();
		$user->setDisplayName(preg_replace('/ \(expired\)/', '', $dn));
		$this->logger->info('Re-enabled account: '
			. $user->getUID(), $this->logCtx);
	}
}
