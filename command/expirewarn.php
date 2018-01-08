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

class ExpireWarn extends Command {

	protected function configure() {
		$this->setName('user-shib:expire-warn')
			->setDescription('Warns a user about expiration')
			->addArgument(
				'userid',
				InputArgument::REQUIRED,
				'user to be warned'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$app = new Application();
		$c = $app->getContainer();
		$uid = $input->getArgument('userid');
		$output->writeln('Warning user '. $uid);
		$user = $c->query('UserManager')->get($uid);
		if (!$user) {
			$output->writeln("<error>User with this name doesn't exist.</error>");
			return;
		} else {
			$c->query('ExpirationManager')->warn($user);
		}
		$output->writeln('Done.');
	}
}
