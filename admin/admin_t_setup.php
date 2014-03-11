<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

  define('CHECK_PHPCHESS', true);

  header("Content-Type: text/html; charset=utf-8");
  ini_set("output_buffering","1");
  session_start();  

  $isappinstalled = 0;
  include("../includes/install_check2.php");

  if($isappinstalled == 0){
    header("Location: ../not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "../";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_admin_t_setup.php";  

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "../skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CBuddyList.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/language.php");

  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  ////////////////////////////////////////////////
  //Login Processing
  ////////////////////////////////////////////////
  //Check if admin is logged in already
  if(!isset($_SESSION['LOGIN'])){
     $login = "no";
     header('Location: ./index.php');
    
  }else{

    if($_SESSION['LOGIN'] != true){

      if (isset($_SESSION['UNAME'])){
        unset($_SESSION['UNAME']);
      }

      if (isset($_SESSION['LOGIN'])) { 
        unset($_SESSION['LOGIN']);
      }

      $login = "no";
      header('Location: ./index.php');

    }else{
      $login = "yes";
    }

  }
  ////////////////////////////////////////////////

  $m1 = GetStringFromStringTable("IDS_SELECT_MONTH_1", $config);
  $m2 = GetStringFromStringTable("IDS_SELECT_MONTH_2", $config);
  $m3 = GetStringFromStringTable("IDS_SELECT_MONTH_3", $config);
  $m4 = GetStringFromStringTable("IDS_SELECT_MONTH_4", $config);
  $m5 = GetStringFromStringTable("IDS_SELECT_MONTH_5", $config);
  $m6 = GetStringFromStringTable("IDS_SELECT_MONTH_6", $config);
  $m7 = GetStringFromStringTable("IDS_SELECT_MONTH_7", $config);
  $m8 = GetStringFromStringTable("IDS_SELECT_MONTH_8", $config);
  $m9 = GetStringFromStringTable("IDS_SELECT_MONTH_9", $config);
  $m10 = GetStringFromStringTable("IDS_SELECT_MONTH_10", $config);
  $m11 = GetStringFromStringTable("IDS_SELECT_MONTH_11", $config);
  $m12 = GetStringFromStringTable("IDS_SELECT_MONTH_12", $config);


  /**********************************************************************
  * GetCountryCode
  *
  */
  function selectyear(){

    $today = getdate(); 
    $date = $today['year'] + 50;
    $ncount = $today['year'];
   
    while ($ncount <= $date){
      echo "<option value='".$ncount."'>".$ncount."</option>";
      $ncount++;
    }

  }

  $txttname = trim($_POST['txttname']);
  $slcttType = trim($_POST['slcttType']);
  $txtplayernum = trim($_POST['txtplayernum']);
  $slctcpDateMonth = trim($_POST['slctcpDateMonth']);
  $slctcpDateDay = trim($_POST['slctcpDateDay']);
  $slctcpDateYear = trim($_POST['slctcpDateYear']);
  $txtcptime = trim($_POST['txtcptime']);
  $slctsDateMonth = trim($_POST['slctsDateMonth']);
  $slctsDateDay = trim($_POST['slctsDateDay']);
  $slctsDateYear = trim($_POST['slctsDateYear']);
  $txtstime = trim($_POST['txtstime']);
  $txtcomments = trim($_POST['txtcomments']);
  $lstTplayers = trim($_POST['lstTplayers']);
  $txtadd = trim($_POST['txtadd']);
 
  if($txtadd != ""){

    $TID = $oR3DCQuery->CreateTournament($config, $txttname, $slcttType, $txtplayernum, $slctcpDateMonth, $slctcpDateDay, $slctcpDateYear, $txtcptime, $slctsDateMonth, $slctsDateDay, $slctsDateYear, $txtstime, $txtcomments);

    if($TID != 0){

      //Add the players
      for ($i = 0; $i < count($_POST['lstTplayers']); $i++) {
      
        if(trim($_POST['lstTplayers'][$i]) != ""){
          //echo "||".$_POST['lstTplayers'][$i]."||";
     
          $oR3DCQuery->AddTournamentPlayer($config, $TID, trim($_POST['lstTplayers'][$i]));
        }
      }

    }

    if($oR3DCQuery->NewTournamentRequiresApproval() == false){
      $oR3DCQuery->AcceptTournamentproposal($config, $TID);
    }
    
  }

  // Get current time
  $todaystime = date("H:i:s");                    

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_3", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript_admin.php");?>
<script language="JavaScript" src="../includes/selectbox.js"></script>
</head>
<body>

<script language='javascript'>
function ValidateNumField(frm, field){
   var testfield = field.value;
   
   if (testfield == parseInt(testfield)){

      if(testfield >=0){
        //Calculate(frm);
      }else{
        alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_1", $config);?>");
        field.select();
        field.focus();
      }

   }else{
      alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_2", $config);?>");
      field.select();
      field.focus();
   }

}

function submitform(){

  var bgoodtoadd = false;


  if(document.frmTProposal.txttname.value == "" || document.frmTProposal.txttname.value == " "){
    alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_3", $config);?>");
    document.frmTProposal.txttname.select();
    document.frmTProposal.txttname.focus();
     
  }else{
    
    if(document.frmTProposal.txtcptime.value == ""){
      alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_4", $config);?>");
      document.frmTProposal.txtcptime.select();
      document.frmTProposal.txtcptime.focus();

    }else{

      if(document.frmTProposal.txtstime.value == ""){
        alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_5", $config);?>");
        document.frmTProposal.txtstime.select();
        document.frmTProposal.txtstime.focus();

      }else{
 
        if(document.frmTProposal.slcttType.value == 1 && document.frmTProposal.txtplayernum.value <= 0){
            alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_6", $config);?>");

        }else{

          if(document.frmTProposal.slcttType.value == 2 && document.frmTProposal.txtplayernum.value <= 0){
              alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_7", $config);?>");

          }else{

            var atime1 = document.frmTProposal.txtcptime.value.split(":");
            var atime2 = document.frmTProposal.txtstime.value.split(":");

            var CurdateVar = new Date();

            var dateVar1 = new Date(document.frmTProposal.slctcpDateYear.value, document.frmTProposal.slctcpDateMonth.value-1, document.frmTProposal.slctcpDateDay.value, atime1[0], atime1[1], atime1[2]);
            var dateVar2 = new Date(document.frmTProposal.slctsDateYear.value, document.frmTProposal.slctsDateMonth.value-1, document.frmTProposal.slctsDateDay.value, atime2[0], atime2[1], atime2[2]);

            if(CurdateVar > dateVar1){
              alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_8", $config);?>");
            }else{

              if(CurdateVar > dateVar2){
                alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_9", $config);?>");
              }else{

                if(dateVar1 >= dateVar2){
                  alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_10", $config);?>");

                }else{
                  
                  if(document.frmTProposal.txtcomments.value == ""){
                    alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_11", $config);?>");
                    document.frmTProposal.txtcomments.select();
                    document.frmTProposal.txtcomments.focus();                      
 
                  }else{

                    if((document.frmTProposal.slcttType.value == 1 || document.frmTProposal.slcttType.value == 2) && document.frmTProposal['lstTplayers[]'].options.length < (document.frmTProposal.txtplayernum.value * 2)){
                      alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_12", $config);?>");

                    }else{

                      if((document.frmTProposal.slcttType.value == 0 || document.frmTProposal.slcttType.value == 3) && document.frmTProposal['lstTplayers[]'].options.length < 2){
                        alert("<?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_13", $config);?>");

                      }else{
                        bgoodtoadd = true;

                      }

                    }
                     
                  }  

                }

              }

            }

          }
        
        }

      }

    }

  }

  if(bgoodtoadd){
    document.frmTProposal.txtadd.value="1";
    selectMatchingOptions(document.frmTProposal['lstTplayers[]'], document.frmTProposal['pattern1'].value);
    document.frmTProposal.submit();
  }

}

function GenerateGroupOutlook(obj, ngrpplayercount, gameoption){

  if(document.all){
    var xMax = screen.width, yMax = screen.height;
  }else{
    if(document.layers){
      var xMax = window.outerWidth, yMax = window.outerHeight;
    }else{
      var xMax = 640, yMax=480;
    }
  }

  var xOffset = (xMax - 500)/2;
  var yOffset = (yMax - 400)/2;

  var generator=window.open('','name','scrollbars=yes,height=400,width=500,screenX='+xOffset+',screenY='+yOffset+',top='+yOffset+',left='+xOffset+'');

  generator.document.write('<html><head><title><?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_14", $config);?></title>');
  generator.document.write('<link rel="stylesheet" href="../skins/<?php echo $SkinName;?>/layout.css" type="text/css">');
  generator.document.write('</head><body>');

  generator.document.write("<table border='0' align='center' cellpadding='3' cellspacing='1' width='98%'>");
  generator.document.write('<tr><td><p><?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_15", $config);?></p></td></tr>');
  generator.document.write('</table><br>');

  var npcount = parseInt(ngrpplayercount.value);
  var ncount = 0;
  var ngroupcount = 1;
  var nswitch = 0;

  if(npcount != 0 && gameoption.value != 0 && gameoption.value != 3){

    while(ncount < obj.length){

      generator.document.write("<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='98%'>");
      generator.document.write('<tr><td class="row1">');
      generator.document.write(ngroupcount);
      generator.document.write('</td></tr>');

      while(nswitch < npcount && ncount < obj.length){

        generator.document.write('<tr><td class="row2">');
        generator.document.write(obj.options[ncount].text);
        generator.document.write('</td></tr>');

        nswitch++;
        ncount++;
      }

      generator.document.write('</table><br>');

      nswitch=0;

      ngroupcount++;

    }

  }else{
    generator.document.write("<table border='0' align='center' cellpadding='3' cellspacing='1' width='98%'>");
    generator.document.write('<tr><td><p><?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_16", $config);?></p></td></tr>');
    generator.document.write('</table><br>');
  }

  generator.document.write("<table border='0' align='center' cellpadding='3' cellspacing='1' width='98%'>");
  generator.document.write('<tr><td><p><?php echo GetStringFromStringTable("IDS_TOURNAMENTPROPOSAL_JAVA_TXT_17", $config);?></p></td></tr>');
  generator.document.write('</table><br>');
  generator.document.write('</body></html>');
  generator.document.close();

}
</script>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
</center>
<br>