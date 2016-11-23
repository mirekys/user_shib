/**
 * ownCloud - user_shib
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer @ CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer @ CESNET 2016
 */


/*global OC, $ */

$(document).ready(function() {
	
	$('#user_shib_mapping_submit').click(function(event) {
		event.preventDefault();	
		$('#user_shib_mapping_submit').attr('disabled', true);
		OC.msg.startAction('#user_shib_mapping_msg',
				t('user_shib', 'Saving...'));
		var requiredAttrs = '';
		requiredAttrs += $('#user_shib_dn_required').is(':checked') ? 'dn,' : '';
		requiredAttrs += $('#user_shib_firstname_required').is(':checked') ? 'firstname,' : '';
		requiredAttrs += $('#user_shib_surname_required').is(':checked') ? 'surname,' : '';
		requiredAttrs += $('#user_shib_email_required').is(':checked') ? 'email,' : '';
		requiredAttrs += $('#user_shib_groups_required').is(':checked') ? 'groups,' : '';
		requiredAttrs += $('#user_shib_external_required').is(':checked') ? 'external,' : '';
		requiredAttrs = requiredAttrs.slice(0, -1);
		$.post(
			OC.generateUrl('/apps/user_shib/ajax/admin.php/mapping'),
			{
				prefix : $('#user_shib_prefix').val(),
				userid : $('#user_shib_userid').val(),
				dn : $('#user_shib_dn').val(),
				firstname : $('#user_shib_firstname').val(),
				surname : $('#user_shib_surname').val(),
				email : $('#user_shib_email').val(),
				groups : $('#user_shib_groups').val(),
				external : $('#user_shib_external').val(),
				required : requiredAttrs,
			},
			function(response) {
				$('#user_shib_mapping_submit').attr(
					'disabled', false
				);
				OC.msg.finishedAction(
					'#user_shib_mapping_msg', response
				);
			}
		);
	});

	$('#user_shib_backend_submit').click(function(event) {
		event.preventDefault();
		$('#user_shib_backend_submit').attr('disabled', true);
		OC.msg.startAction('#user_shib_backend_msg',
				t('user_shib', 'Saving...'));
		$.post(
			OC.generateUrl('/apps/user_shib/ajax/admin.php/backend'),
			{
				active : $('#user_shib_active').is(':checked'),
				autocreate: $('#user_shib_autocreate').is(':checked'),
				autoupdate: $('#user_shib_autoupdate').is(':checked'),
				protectedGroups: $('#user_shib_protected_groups option:selected').val()
			},
			function(response) {
				$('#user_shib_backend_submit').attr(
					'disabled', false
				);
				OC.msg.finishedAction(
					'#user_shib_backend_msg', response
				);
			}
		);
	});
});
