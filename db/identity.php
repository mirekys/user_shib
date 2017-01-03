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

namespace OCA\User_Shib\Db;

use OCP\AppFramework\Db\Entity;

class Identity extends Entity {

	protected $samlUid;
	protected $samlEmail;
	protected $lastSeen;
	protected $ocUid;
}
