<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr><td>
		<table cellspacing="0" cellpadding="0" border="0" width="600px">
			<tr>
				<td bgcolor="<?php p($theme->getMailHeaderColor());?>" width="20px">&nbsp;</td>
				<td bgcolor="<?php p($theme->getMailHeaderColor());?>">
					<img src="<?php p(\OCP\Util::linkToAbsolute('' , image_path('', 'logo-mail.gif'))); ?>" alt="<?php p($theme->getName()); ?>"/>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td width="20px">&nbsp;</td>
				<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
					<?php
					print_unescaped($l->t('Hello,<br><br>we would like to inform you that your ownCloud password for the client apps has been changed.<br><br>If you did not change your password, please contact us on du-support@cesnet.cz.<br><br>', array()));

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
