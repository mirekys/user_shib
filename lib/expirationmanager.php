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
		$expPeriod = $this->backendConfig['expiration_period'];
		$gracePeriod = $this->backendConfig['expiration_warning'];
		$currTs = $this->timeFactory->getTime();
		$expTreshold = $currTs - ($expPeriod * 24 * 3600);
		$warnTreshold = $expTreshold + ($gracePeriod * 24 * 3600);

		$toBeExpired = $this->getUsersBelowTreshold($expTreshold);
		$toBeWarned = $this->getUsersBelowTreshold($warnTreshold);
		$toBeWarned = array_filter($toBeWarned,
			function($user) use($toBeExpired) {
				return !in_array($user, $toBeExpired, TRUE);
			});

		if ($gracePeriod > 0 && $expPeriod > 0) {
			$this->logger->info(sprintf(
				'Warning %d users with last_seen < %d',
				count($toBeWarned), $warnTreshold),
				$this->logCtx);
			foreach ($toBeWarned as $user) {
				$this->warn($user);
			}
		}
		if ($expPeriod > 0) {
			$this->logger->info(sprintf(
				'Expiring %d users with last_seen < %d',
				count($toBeExpired), $expTreshold),
				$this->logCtx);
			foreach ($toBeExpired as $user) {
				$this->expire($user);
			}
		}
	}

	/*
	 * Returns the users having last_seen older than treshold
	 *
	 * @param int expiration treshold timestamp
	 * @return array(\OC\User\User)
	 */
	public function getUsersBelowTreshold($treshold) {
		$uids = $this->identityMapper->findExpired($treshold);
		$usrs = array();
		foreach ($uids as $uid) {
			$usrs[] = $this->userManager->get($uid);
		}
		return $usrs;
	}

	/*
	 * Warns a user about the expiration happening soon
	 *
	 * @param \OC\User\User $user to be warned
	 */
	public function warn($user) {
		if (!$user) { return; }
		if (!$user->isEnabled()) { return; }

		$recipient = $user->getEMailAddress();
		if ($recipient) {
			$this->mailer->mailExpirationWarning($recipient);
			$this->logger->info('Sent expiration warning to: '
				. $user->getUID(), $this->logCtx);
		} else {
			$this->logger->error('Couldn\'t send an expiration'
				. ' warning to: '. $user->getUID(),
				$this->logCtx);
		}
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
		$recipient = $user->getEMailAddress();
		if ($recipient) {
			$this->mailer->mailExpirationNotice($recipient);
			$this->logger->info('Expired account: '
				. $user->getUID(), $this->logCtx);
		} else {
			$this->logger->error('Couldn\'t send an expiration'
				. ' notice to: '. $user->getUID(),
				$this->logCtx);
		}
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
