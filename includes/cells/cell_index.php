<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }
  
?>
<table width="100%" cellspacing="0" cellpadding="10" padding=4>
  <tr>
    <td class="white"><table width="100%" border="0" cellspacing="8" cellpadding="0">
					  <tr>
						
                      <td width="364"><img src="<?php echo $Root_Path."skins/".$SkinName."/images/welcome_img.jpg";?>" width="364" height="212" /></td>
						<td>
							<h2 class="title_h2">Welcome to <?php echo $SiteName?></h2>
							<p>	<?php  $oFrontNews->GetFrontNews();  echo "<br>";?>	</p>						</td>
					  </tr>
					</table>	</td> 
  </tr>
  <tr><Td class="white"><table width="100%" border="0" cellspacing="8" cellpadding="0">
					  <tr>
						<td width="50%"><?php include($Root_Path."skins/".$SkinName."/left_menu_login.php");?>
						</td>
						<td width="50%"><?php 
							

if($bLimit == false){



if($RequiresPayment == true){

?>

<table border='0' align='center'  cellpadding="3" cellspacing="1" width='95%'>

<tr>

<td class="row2">

<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_GREETING", $config);?>

</td>

<td class="row2">

<img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/paypal1.gif'>

</td>

</tr>

<tr>

<td class="row2">



</td>

</tr>

</table>



<br>

<?php

}



if($RequiresPayment == true){

?>

<form name='frmRegister' method='post' action='./paybypaypal.php'>

<?php

}else{

?>

<form name='frmRegister' method='post' action='./chess_register.php'>

<?php

}

?>






<h3 class="title_h3">&nbsp;&nbsp;<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_HEADER", $config);?></h3>

<table border='0' align='center' cellpadding="0" cellspacing="8"  class="white" >

<tr>

<td colspan='3' align='right'><a href="./chess_faq.php"><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_1", $config);?> </a> <img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/help.gif' width='15' height='15'></td>

</tr>

<tr>

<td ><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_TXT_1", $config);?></td>
<td  colspan='2'><Input type='text' name='txtName' size='30' class="input_text" ></td>

</tr>



<tr>

<td ><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_TXT_2", $config);?></td><td colspan='2'><Input type='text' name='txtEmail' size='30' class="input_text" ></td>

</tr>



<?php

 

  // Create the random sting

  srand((double)microtime()*1000000);  

  $string = md5(rand(0,9999));  

  $new_string = substr($string, 17, 5); 



  $_SESSION['new_string'] = $new_string;



?>



<tr>

<td ><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_TXT_3", $config);?></td><td ><img src='./bin/img_validate.php?RandNum=<?php echo $new_string;?>'></td><td width="285" ><Input type='text' name='txtVI' size='20' class="input_text" ></td>

</tr>



<tr>

<td colspan='3' class='tableheadercolor'>

<input type='submit' name='cmdRegister' value='<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_BTN_REGISTER", $config);?>' class="input_btn">

<input type='Reset' name='cmdReset' value='<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_BTN_RESET", $config);?>' class="input_btn">

</td>

</tr>



<?php

if($ReturnStatus != ""){

?>



<tr>

<td colspan='3' class="row2">

<?php

echo $ReturnStatus;

?>

</td>

</tr>



<?php

}

?>



</table>

</form>



<?php

if($RequiresPayment == true){

?>

<br>

<center><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_MESSAGE_TXT_1", $config);?></center>

<?php

}





}else{

?>

<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>

<tr><td class='row2'><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_MESSAGE_TXT_2", $config);?></td></tr>

</table>

<?php

}
?></Td></tr>
						</table></Td></tr>
  <tr>
    <td class="white">

<!--<img src='<?php //echo $Root_Path."skins/".$SkinName."/";?>images/index_ban1.gif'> -->





<?php

echo "<br>";



  echo "<br>";



  $AllGames = 0;

  $TGames = 0;

  $PCount = 0; 



  $oR3DCQuery->GetOngoingGameCount2($config, $AllGames, $TGames);

  $oR3DCQuery->GetPlayerCount($config, $PCount);



  echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";

  echo "<tr>";

  echo "<td class='row1'>".GetStringFromStringTable("IDS_INDEX_TXT_1", $config)."</td><td class='row1'>".GetStringFromStringTable("IDS_INDEX_TXT_2", $config)."</td><td class='row1'>".GetStringFromStringTable("IDS_INDEX_TXT_3", $config)."</td>";

  echo "</tr>";



  echo "<tr>";

  echo "<td class='row2'>".$PCount."</td><td class='row2'>".$AllGames."</td><td class='row2'>".$TGames."</td>";

  echo "</tr>";

  echo "</table>"; 

  echo "<br>";


echo "<h3 class=other_h2>&nbsp;&nbsp;". GetStringFromStringTable("IDS_INDEX_TXT_4", $config)."</h3>";

  echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";


  echo "<tr>";

  echo "<td class='row2'>";

  $oR3DCQuery->GetOnlinePlayerList();

  echo "</td>";

  echo "</tr>";

  echo "</table>"; 
?>
</td>
  </tr>
</table>