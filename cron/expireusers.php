<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2018 */

namespace OCA\User_Shib\Cron;

use \OCA\User_Shib\AppInfo\Application;

class ExpireUsers extends \OC\BackgroundJob\TimedJob {

	public function __construct(){
		$this->setInterval(3600*24);
	}

	/**
	 * @param mixed $argument
	 */
	public function run($argument = null){
		$app = new Application();
		$c = $app->getContainer();
		$c->query('ExpirationManager')->expireUsers();
	}
}
