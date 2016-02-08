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

// Initialize application container
$app = new Application();
$c = $app->getContainer();

// Register itself as User Backend
$c->query('UserManager')->registerBackend($c->query('UserBackend'));

// Register Hooks
$c->query('UserHooks')->register();

// Prepare login URL with possible redirect URL
$urlGen = $c->query('URLGenerator');
$urlParams = $c->query('Request')->getParams();

if (array_key_exists('redirect_url', $urlParams)) {
	$loginRoute = $urlGen->linkToRoute(
			'user_shib.session.login',
			array(
				'redirect_url' => $urlParams['redirect_url'],
				'requesttoken' =>
					\OCP\Util::callRegister()
			)
	);
} else {
	$loginRoute = $urlGen->linkToRoute(
			'user_shib.session.login',
			array(
				'requesttoken' =>
					\OCP\Util::callRegister()
			)
	);
}

// Templates registration
// TODO: Couldn't find an \OCP way for achieving this
\OC_App::registerLogIn(
	array(
		'name' => $c->query('L10N')->t('eduID Federated Login'),
		'href' => $loginRoute
	)
);
\OCP\App::registerAdmin($c->query('AppName'), 'admin');
\OCP\App::registerPersonal($c->query('AppName'), 'personal');
