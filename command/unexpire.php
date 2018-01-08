<?php
/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2018 */

namespace OCA\User_Shib\Command;

use \OCA\User_Shib\AppInfo\Application;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

class Unexpire extends Command {

	protected function configure() {
		$this->setName('user-shib:unexpire')
			->setDescription('Re-enable an expired user account')
			->addArgument(
				'userid',
				InputArgument::REQUIRED,
				'user account name to be enabled'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$app = new Application();
		$c = $app->getContainer();
		$uid = $input->getArgument('userid');
		$output->writeln('Re-enabling user '. $uid);
		$user = $c->query('UserManager')->get($uid);
		if (!$user) {
			$output->writeln("<error>User with this name doesn't exist.</error>");
			return;
		} else {
			$c->query('ExpirationManager')->unexpire($user);
		}
		$output->writeln('Done.');
	}
}
