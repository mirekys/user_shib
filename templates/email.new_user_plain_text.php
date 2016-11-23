<?php
print_unescaped($l->t("Hello,\n\nyour ownCloud account at Data Storage CESNET has been succesfully created for you.\n\nBelow are listed details about your account.\n\nYour username: %s\nServer addresss: %s\n\nIf you wish to use synchronization client apps, please set your password here: %s\n\nYour account is bound to Identity Provider used at first login. If you have identities at multiple organizations, always use your identity used at first login in order to access your data.\n\nIf you have any questions or problems, feel free to contact us at du-support@cesnet.cz.\n\n",
array($_['username'], $_['url'], $_['pwlink'])));

// TRANSLATORS term at the end of a mail
p($l->t("Your CESNET Storage Department Team"));
?>

	--
<?php p($theme->getName()); ?>
<?php print_unescaped("\n".$theme->getBaseUrl());
