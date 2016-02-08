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

class UserMailer {

	private $appName;
	private $l10n;
	private $mailer;
	private $logger;
	private $defaults;
	private $urlGenerator;

	public function __construct($appName, $l10n, $mailer, $defaults,
				    $logger, $fromMailAddress, $urlGenerator) {
		$this->appName = $appName;
		$this->l10n = $l10n;
		$this->mailer = $mailer;
		$this->logger = $logger;
		$this->defaults  = $defaults;
		$this->fromMailAddress = $fromMailAddress;
		$this->urlGenerator = $urlGenerator;
		$this->logCtx = array('app' => $this->appName);
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
			'url' => $this->urlGenerator->getAbsoluteURL('/')
		);
		$html = new TemplateResponse('settings',
				'email.new_user', $mailData, 'blank');
		$plain = new TemplateResponse('settings',
				'email.new_user_plain_text',
				$mailData, 'blank');
		$this->sendMail($uid, $emailAddress, $html, $plain);
	}

	/**
	 * Send password-change mail
	 *
	 * @param string $uid username
	 * @param string $emailAddress user's e-mail address
	 */
	public function mailPasswordChange($uid, $emailAddress) {
		$mailData = array(
			'username' => $uid,
			'url' => $this->urlGenerator->getAbsoluteURL('/')
		);
		$html = new TemplateResponse('user_shib',
				'email.password_change', $mailData, 'blank');
		$plain = new TemplateResponse('user_shib',
				'email.password_change_plain_text',
				$mailData, 'blank');
		$this->sendMail($uid, $emailAddress, $html, $plain);
	}

	/**
	 * Send an e-mail with content from templates
	 *
	 * @param \OCP\AppFramework\Http\TemplateResponse $htmlTemplate HTML
	 * mail content
	 * @param \OCP\AppFramework\Http\TemplateResponse $plainTemplate mail
	 * content in plaintext
	 */
	private function sendMail($uid, $emailAddress,
				  $htmlTemplate, $plainTemplate) {

		$subject = $this->l10n->t('Your %s account was created',
				[$this->defaults->getName()]);
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
