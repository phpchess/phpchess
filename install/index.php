<?php
// Direct user to the appriopriate page. The install.txt determines if phpchess
// is considered to be installed.

if(!file_exists('../bin/installed.txt'))
	header('Location: install.php');
else
	header('Location: upgrade.php');

?>