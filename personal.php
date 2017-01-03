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

use OCA\User_Shib\AppInfo\Application;

$app = new Application();
$response = $app->getContainer()->query('SettingsController')->personalIndex();
return $response->render();
