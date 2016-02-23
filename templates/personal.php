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

script('user_shib', 'personal');
style('user_shib', 'style');
?>

<div class="section" id="user_shib_personal">
	<h2><?php p($l->t('Client login credentials')); ?></h2>
	<span>
		<?php p($l->t('Set password for your clients here')); ?>
	</span>
	<h2>Username</h2>
	<span id="client_username"><?php p($_['username']); ?></span>
	<?php if (! $_['token_valid']) : ?>
	<input id="reset_client_password" type="submit"
		value="<?php p($l->t('Reset client password')); ?>" >
	</input>
	<?php endif; ?>
</div>
