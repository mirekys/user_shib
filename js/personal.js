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
});
