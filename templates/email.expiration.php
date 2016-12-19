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
							print_unescaped($l->t('Hello,<br><br>your ownCloud account <b>%s</b> at Data Storage CESNET has been disabled because of expiration<br><br>',
							array($_['username'])));
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
