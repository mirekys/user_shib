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

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\Security\ISecureRandom;

class UserMailer {

	private $appName;
	private $l10n;
	private $ocConfig;
	private $mailer;
	private $logger;
	private $defaults;
	private $urlGenerator;
	private $secureGen;
	private $timeFactory;

	public function __construct($appName, $l10n, $ocConfig, $mailer,
				    $defaults, $logger, $fromMailAddress,
				    $urlGenerator, $secureGenerator,
				    $timeFactory) {
		$this->appName = $appName;
		$this->l10n = $l10n;
		$this->ocConfig = $ocConfig;
		$this->mailer = $mailer;
		$this->logger = $logger;
		$this->defaults  = $defaults;
		$this->fromMailAddress = $fromMailAddress;
		$this->urlGenerator = $urlGenerator;
		$this->secureGen = $secureGenerator;
		$this->timeFactory = $timeFactory;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Send message to Lubos
	 *
	 * @param string message
	 */
	public function mailLubos($message) {
		$body = new DataDisplayResponse($message);
		$this->sendMail('Lubos', 'lubos.kopecky@cesnet.cz',
			'Missing Attribute', $body, $body);
	}

	/**
	 * Send new user mail
	 *
	 * @param string $uid username
	 * @param string $emailAddress user's e-mail address
	 */
	public function mailNewUser($uid, $emailAddress) {
		$mailData = array(
			'username' => $uid,
			'url' => $this->urlGenerator->getAbsoluteURL('/'),
			'pwlink' => $this->getPasswordResetLink($uid)
		);
		$subject = $this->l10n->t('Your %s account was created',
				[$this->defaults->getName()]);

		$html = new TemplateResponse($this->appName,
				'email.new_user', $mailData, 'blank');
		$plain = new TemplateResponse($this->appName,
				'email.new_user_plain_text',
				$mailData, 'blank');
		$this->sendMail($uid, $emailAddress, $subject, $html, $plain);
	}

	/**
	 * Send a notice about user account being expired
	 *
	 * @param string $uid username
	 */
	public function mailExpirationNotice($uid) {
		$recipient = $this->ocConfig->getUserValue(
				$uid, 'settings', 'email');
		$mailData = array(
			'username' => $uid,
			'url' => $this->urlGenerator->getAbsoluteURL('/'),
		);
		$subject = $this->l10n->t('Your %s account has expired',
				[$this->defaults->getName()]);
		$html = new TemplateResponse($this->appName,
				'email.expiration', $mailData, 'blank');
		$plain = new TemplateResponse($this->appName,
				'email.expiration_plain_text',
				$mailData, 'blank');
		$this->sendMail($uid, $recipient, $subject, $html, $plain);
	}

	/**
	 * Send a warning about user account expiration
	 *
	 * @param string $uid username
	 */
	public function mailExpirationWarning($uid) {
		$recipient = $this->ocConfig->getUserValue(
				$uid, 'settings', 'email');
		$mailData = array(
			'username' => $uid,
			'url' => $this->urlGenerator->getAbsoluteURL('/'),
		);
		$subject = $this->l10n->t('Your %s account is about to be expired',
				[$this->defaults->getName()]);
		$html = new TemplateResponse($this->appName,
				'email.expiration_warning', $mailData, 'blank');
		$plain = new TemplateResponse($this->appName,
				'email.expiration_warning_plain_text',
				$mailData, 'blank');
		$this->sendMail($uid, $recipient, $subject, $html, $plain);
	}

	/**
	 * Send password-change mail
	 *
	 * @param string $uid username
	 * @param string $emailAddress user's e-mail address
	 */
	public function mailPasswordChange($uid) {
		$recipient = $this->ocConfig->getUserValue(
				$uid, 'settings', 'email');
		$mailData = array(
			'username' => $uid,
			'url' => $this->urlGenerator->getAbsoluteURL('/')
		);
		$subject = $this->l10n->t('Your password has been changed');
		$html = new TemplateResponse('user_shib',
				'email.password_change', $mailData, 'blank');
		$plain = new TemplateResponse('user_shib',
				'email.password_change_plain_text',
				$mailData, 'blank');
		$this->sendMail($uid, $recipient, $subject, $html, $plain);
	}

	/**
	 * Get a password (re)set link for the user
	 *
	 * @param string $uid username
	 * @return string link to reset password page
	 */
	private function getPasswordResetLink($uid) {
		$token = $this->secureGen->generate(21,
				ISecureRandom::CHAR_DIGITS.
				ISecureRandom::CHAR_LOWER.
				ISecureRandom::CHAR_UPPER);
		$this->ocConfig->setUserValue($uid,
			'owncloud', 'lostpassword',
			$this->timeFactory->getTime() . ':' . $token
		);
		return $this->urlGenerator->linkToRouteAbsolute(
			'core.lost.resetform',
			array('userId' => $uid, 'token' => $token)
		);
	}

	/**
	 * Send an e-mail with content from templates
	 *
	 * @param \OCP\AppFramework\Http\TemplateResponse $htmlTemplate HTML
	 * mail content
	 * @param \OCP\AppFramework\Http\TemplateResponse $plainTemplate mail
	 * content in plaintext
	 */
	private function sendMail($uid, $emailAddress, $subject,
				  $htmlTemplate, $plainTemplate) {
		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$emailAddress => $uid]);
			$message->setSubject($subject);
			$message->setHtmlBody($htmlTemplate->render());
			$message->setPlainBody($plainTemplate->render());
			$message->setFrom([
				$this->fromMailAddress =>
					$this->defaults->getName()
			]);
			$this->mailer->send($message);
		} catch(\Exception $e) {
			$this->logger->error('Can\'t send new user mail to'
				. $emailAddress . ': ' . $e->getMessage(),
				$this->logCtx);
		}
	}
}
