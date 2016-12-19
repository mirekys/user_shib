<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2016 */

namespace OCA\User_Shib\Command;

use \OCA\User_Shib\AppInfo\Application;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

class ExpireUsers extends Command {

	protected function configure() {
		$this->setName('user-shib:expire-users')
			->setDescription('Expire inactive users');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$app = new Application();
		$c = $app->getContainer();
		$output->writeln('Expiring users...');
		$c->query('ExpirationManager')->expireUsers();
		$output->writeln('Done.');
	}
}
