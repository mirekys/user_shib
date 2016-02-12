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
	$('#user_shib_personal').after($('#passwordform'));
	$('#pass1').attr('placeholder', t('user_shib', 'Old client password'));
	$('#pass2').attr('placeholder', t('user_shib', 'New client password'));
	$('#reset_client_password').before($('#passwordform'));
	$('#reset_client_password').click(function(event) {
		event.preventDefault();
		$('#reset_client_password').attr('disabled', true);
		$.post(
			OC.generateUrl('/lostpassword/email'),
			{
				user : $('#client_username').text()
			},
			function(result) {
				var sendErrorMsg;

				if (result && result.status === 'success'){
					$('#reset_client_password').val(t(
						'user_shib','Reset link sent'));
					$('#reset_client_password')
						.addClass('success')
				} else {
					$('#reset_client_password')
                                                .addClass('warning')
					if (result && result.msg){
						sendErrorMsg = result.msg;
						$('#reset_client_password')
							.val(t(sendErrorMsg));
					}
				}
			}
		);
	});
});

;
