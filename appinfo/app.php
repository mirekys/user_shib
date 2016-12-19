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

// Register BackgroundJobs
\OCP\BackgroundJob::registerJob('\OCA\User_Shib\Cron\ExpireUsers');

// It is not necessary to activate Shibboleth backend
// for these URLs. The list comes from here:
// https://doc.owncloud.com/server/8.2/admin_manual/enterprise_user_management/user_auth_shibboleth.html#apache-configuration
$nonShibUrls = '^/'
. '(status.php'
. '|remote.php'
. '|index.php/s/'
. '|public.php'
. '|cron.php'
. '|core/img/'
. '|index.php/apps/files_sharing/ajax/publicpreview.php$'
. '|index.php/apps/files/ajax/upload.php$'
. '|apps/files/templates/fileexists.html$'
. '|index.php/apps/files/ajax/mimeicon.php$'
. '|apps/gallery/templates/slideshow.html$'
. '|index.php/apps/gallery/ajax/getimages.php'
. '|index.php/apps/gallery/ajax/thumbnail.php'
. '|index.php/apps/gallery/ajax/image.php'
. '|.*\.css$'
. '|.*\.js$'
. '|.*\.woff$'
// Following routes requires this backend to be inactive
. '|index.php/settings/personal/changepassword'
. '|ocs'
. ')';
$nonShibRegex = '/' . str_replace('/', '\/', $nonShibUrls) . '/i';

$request = $c->query('Request');
$requestUri = $request->getRequestUri();

if (!\OC::$CLI && !preg_match($nonShibRegex, $requestUri) ) {
	// Register itself as User Backend
	$c->query('UserManager')->registerBackend($c->query('UserBackend'));

	// Register Hooks
	$c->query('UserHooks')->register();
	$c->query('UserHooks')->registerPostSetPassword();

	// Prepare login URL with possible redirect URL
	$urlGen = $c->query('URLGenerator');
	$urlParams = $request->getParams();
	if (array_key_exists('redirect_url', $urlParams)) {
		$loginRoute = $urlGen->linkToRoute(
				'user_shib.session.login',
				array(
					'redirect_url' =>
						$urlParams['redirect_url'],
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
			'name' => 'Shibboleth Login',
			'href' => $loginRoute
		)
	);
	\OCP\App::registerAdmin($c->query('AppName'), 'admin');
	\OCP\App::registerPersonal($c->query('AppName'), 'personal');
} elseif ($requestUri === '/index.php/settings/personal/changepassword') {
	// Register only Non-Shib Hook for password setting
	$c->query('UserHooks')->registerPostSetPassword();
}
