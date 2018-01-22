<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2018
 */

namespace OCA\User_Shib\Controller;

use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;

class SessionController extends Controller {

	private $logger;
	private $urlGenerator;
	private $logCtx;
	private $userSession;

	public function __construct($appName, IRequest $request,
				    IURLGenerator $urlGenerator,
				    $logger, $userSession){
		parent::__construct($appName, $request);

		$this->appName = $appName;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * This method should be associated with a route, on which
	 * the Shibboleth enforces its sessions.
	 * It initiates internal login procedure and then, if succesfull,
	 * redirects to the desired page. Call to $userSession->login() is
	 * processed by this app's backend 'checkPassword' method.
	 * 
	 * @param $redirectUrl where to redirect after succesfull login
	 * @PublicPage
	 * @NoAdminRequired
	 * @UseSession
	 */
	public function login($redirectUrl='index.php') {
		$this->logger->debug(
			'Login attempt from url: '. $redirectUrl,
			$this->logCtx
		);

		$result = $this->userSession->login('', '');

		$user = $this->userSession->getUser();
		if (! $user) {
			return new TemplateResponse('', '403', array(), 'guest');
		}

		$uid = $user->getUID();
		$this->userSession->createSessionToken($this->request, $uid, $uid, '');

		if ($result && $uid) {
			$this->logger->info('Logged in user: '. $uid, $this->logCtx);
			// Prevent redirect loop - do not redirect to itself
			if (strpos($redirectUrl, $this->appName) !== false) {
				$redirectUrl = 'index.php';
			}

			/**
			 * Absolute URL is needed to prevent redirection
			 * to unvalidated URLs (to foreign domains).
			 */
			$redir = $this->urlGenerator->getAbsoluteURL($redirectUrl);
			return new RedirectResponse($redir);
		} else {
			return new TemplateResponse('', '403', array(), 'guest');
		}
	}
}
