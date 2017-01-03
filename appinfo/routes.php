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

namespace OCA\User_Shib\AppInfo;

$application = new Application();
$application->registerRoutes($this, array(
	'routes' => array(
		array('name' => 'session#login', 'url' => '/login', 'verb' => 'GET'),
		array('name' => 'settings#saveMappings',
			'url' => '/ajax/admin.php/mapping', 'verb' => 'POST'),
		array('name' => 'settings#saveBackendConfig',
			'url' => '/ajax/admin.php/backend', 'verb' => 'POST')
	)
));
