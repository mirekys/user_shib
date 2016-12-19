<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr><td>
			<table cellspacing="0" cellpadding="0" border="0" width="600px">
				<tr>
					<td bgcolor="<?php p($theme->getMailHeaderColor());?>" width="20px">&nbsp;</td>
					<td bgcolor="<?php p($theme->getMailHeaderColor());?>">
						<img src="<?php p(OC::$server->getURLGenerator()->getAbsoluteURL(image_path('', 'logo-mail.gif'))); ?>" alt="<?php p($theme->getName()); ?>"/>
					</td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td width="20px">&nbsp;</td>
					<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
						<?php
							print_unescaped($l->t('Hello,<br><br>your ownCloud account at Data Storage CESNET has been disabled because of expiration<br><br>Below are listed details about your account.<br><br>Your username: %s<br>Server addresss: <a href="%s">%s</a><br><br>If you wish to use synchronization client apps, please set your password here: <a href="%s">%s</a><br><br>Your account is bound to Identity Provider used at first login. If you have identities at multiple organizations, always use your identity used at first login in order to access your data.<br><br>If you have any questions or problems, feel free to contact us at du-support@cesnet.cz.<br><br>',
							array($_['username'], $_['url'], $_['url'])));
						// TRANSLATORS term at the end of a mail
						p($l->t('Your CESNET Storage Department Team'));
						?>
					</td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td width="20px">&nbsp;</td>
					<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">--<br>
						<?php p($theme->getName()); ?> - <?php print_unescaped($theme->getSlogan()); ?>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
			</table>
		</td></tr>
</table>
