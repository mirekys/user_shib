<?php
print_unescaped($l->t("Hello,\n\nwe would like to inform you that your ownCloud password for the client apps has been changed.\n\nIf you did not change your password, please contact us on du-support@cesnet.cz.\n\n", array()));

// TRANSLATORS term at the end of a mail
p($l->t("Your CESNET Storage Department Team"));
?>

	--
<?php p($theme->getName()); ?>
<?php print_unescaped("\n".$theme->getBaseUrl());
