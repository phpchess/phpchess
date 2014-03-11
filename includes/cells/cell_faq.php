<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

	if(file_exists($Root_Path . 'includes/faq_custom.html'))
		echo file_get_contents($Root_Path . 'includes/faq_custom.html');
	else
		echo file_get_contents($Root_Path . 'includes/faq.html');

?>