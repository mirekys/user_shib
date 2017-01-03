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

$application->add(new OCA\User_Shib\Command\ExpireUsers());
$application->add(new OCA\User_Shib\Command\Expire());
$application->add(new OCA\User_Shib\Command\Unexpire());
