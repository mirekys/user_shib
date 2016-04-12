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

script('user_shib', 'admin');
?>

<div class="section" id="user_shib">
	<h2><?php p($l->t('Shibboleth - User Backend')); ?></h2>
	<form id="user_shib_mapping" class='mail_settings'>
		<p><?php p($l->t('Attribute mapping configuration:')); ?></p>
		<p>
			<label for="user_shib_prefix">
				<?php p($l->t( 'Attribute prefix' )); ?>
			</label>
			<input type="text" name="user_shib_prefix"
				id="user_shib_prefix"
				value="<?php p($_['mapping_prefix']); ?>">
			</input>
		</p>
		<p>
			<label for="user_shib_userid">
				<?php p($l->t( 'Username' )); ?>
			</label>
			<input type="text" name="user_shib_userid"
				id="user_shib_userid"
				value="<?php p($_['mapping_userid']); ?>">
			</input>
			<em><?php p($l->t( 'Required' )); ?></em>
		</p>
		<p>
			<label for="user_shib_dn">
				<?php p($l->t( 'Full name' )); ?>
			</label>
			<input type="text" name="user_shib_dn"
				id="user_shib_dn"
				value="<?php p($_['mapping_dn']); ?>">
			</input>
			<input type="checkbox" name="user_shib_dn_required"
				id="user_shib_dn_required" value="0"
				<?php if (in_array('dn', $_['required_attrs'])) {
					print_unescaped('checked="checked"'); } ?> >
			</input>
			<label for="user_shib_dn_required">
				<em><?php p($l->t( 'Required' )); ?></em>
			</label>
		</p>
		<p>
			<label for="user_shib_firstname">
				<?php p($l->t( 'First name' )); ?>
			</label>
			<input type="text" name="user_shib_firstname"
				id="user_shib_firstname"
				value="<?php p($_['mapping_firstname']); ?>">
			</input>
			<input type="checkbox" name="user_shib_firstname_required"
				id="user_shib_firstname_required" value="0"
				<?php if (in_array('firstname', $_['required_attrs'])) {
					print_unescaped('checked="checked"'); } ?> >
			</input>
			<label for="user_shib_firstname_required">
				<em><?php p($l->t( 'Required' )); ?></em>
			</label>
		</p>
		<p>
			<label for="user_shib_surname">
				<?php p($l->t( 'Surname' )); ?>
			</label>
			<input type="text" name="user_shib_surname"
				id="user_shib_surname"
				value="<?php p($_['mapping_surname']); ?>">
			</input>
			<input type="checkbox" name="user_shib_surname_required"
				id="user_shib_surname_required" value="0"
				<?php if (in_array('surname', $_['required_attrs'])) {
					print_unescaped('checked="checked"'); } ?> >
			</input>
			<label for="user_shib_surname_required">
				<em><?php p($l->t( 'Required' )); ?></em>
			</label>
		</p>
		<p>
			<label for="user_shib_email">
				<?php p($l->t( 'E-mail' )); ?>
			</label>
			<input type="text" name="user_shib_email"
				id="user_shib_email"
				value="<?php p($_['mapping_email']); ?>">
			</input>
			<input type="checkbox" name="user_shib_email_required"
				id="user_shib_email_required" value="0"
				<?php if (in_array('email', $_['required_attrs'])) {
					print_unescaped('checked="checked"'); } ?> >
			</input>
			<label for="user_shib_email_required">
				<em><?php p($l->t( 'Required' )); ?></em>
			</label>
		</p>
		<p>
			<label for="user_shib_groups">
				<?php p($l->t( 'Groups' )); ?>
			</label>
			<input type="text" name="user_shib_groups"
				id="user_shib_groups"
				value="<?php p($_['mapping_groups']); ?>">
			</input>
			<input type="checkbox" name="user_shib_groups_required"
				id="user_shib_groups_required" value="0"
				<?php if (in_array('groups', $_['required_attrs'])) {
					print_unescaped('checked="checked"'); } ?> >
			</input>
			<label for="user_shib_groups_required">
				<em><?php p($l->t( 'Required' )); ?></em>
			</label>
		</p>
		<input name="user_shib_mapping_submit"
			id="user_shib_mapping_submit"
			value="Save" type="submit">
		</input>
		<span id="user_shib_mapping_msg" class="msg"></span>
	</form>
	<form id="user_shib_backend" class='mail_settings'>
		<p><?php p($l->t('Backend configuration:')); ?></p>
		<p>
			<label for="user_shib_active">
				<?php p($l->t( 'Backend activated' )); ?>
			</label>
			<input type="checkbox" name="user_shib_active"
				id="user_shib_active" value="1" 
				<?php if ($_['active'] === 'true')
					print_unescaped('checked="checked"'); ?> >
			</input>
		</p>
		<p>
			<label for="user_shib_autocreate">
				<?php p($l->t( 'Autocreate accounts' )); ?>
			</label>
			<input type="checkbox" name="user_shib_autocreate"
				id="user_shib_autocreate" value="1" 
				<?php if ($_['autocreate'] === 'true')
					print_unescaped('checked="checked"'); ?> >
			</input>
		</p>
		<p>
			<label for="user_shib_autoupdate">
				<?php p($l->t( 'Update user info on login' )); ?>
			</label>
			<input type="checkbox" name="user_shib_autoupdate"
				id="user_shib_autoupdate" value="1" 
				<?php if ($_['autoupdate'] === 'true')
					print_unescaped('checked="checked"'); ?> >
			</input>
		</p>
		<p>
			<label for="user_shib_protected_groups">
				<?php p($l->t( 'Protected Groups' )); ?>
			</label>
			<select id="user_shib_protected_groups"
				name="user_shib_protected_groups"
				multiple="multiple"
				class="groupsselect multiselect button"
				data-placehoder="Groups" title="no group">
				<option value="admin">no group</option>
			</select>
		</p>
		<input name="user_shib_backend_submit"
			id="user_shib_backend_submit"
			value="Save" type="submit">
		</input>
		<span id="user_shib_backend_msg" class="msg"></span>
	</form>
</div>
