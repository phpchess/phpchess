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

  $host = $_SERVER['HTTP_HOST'];
  $self = $_SERVER['PHP_SELF'];
  $query = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
  $url = !empty($query) ? "http://$host$self?$query" : "http://$host$self";

  header("Content-Type: text/html; charset=utf-8");
  session_start();
  ob_start();  

  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_cfg_avatar.php";  

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "./skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  include_once($Root_Path."bin/CAvatars.php");
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");
  require($Root_Path."bin/LanguageParser.php");

  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oAvatars = new CAvatars($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  ///////////////////////////////////////////////////////////////////
  //Check if the logged in user has access
  if(!isset($_SESSION['sid']) && !isset($_SESSION['user']) && !isset($_SESSION['id']) ){
    $_SESSION['PageRef'] = $url;
    header('Location: ./chess_login.php');
  }else{
  
    $oR3DCQuery->CheckSIDTimeout();

    if($oR3DCQuery->CheckLogin($config, $_SESSION['sid']) == false){
      $_SESSION['PageRef'] = $url;
      header('Location: ./chess_login.php');
    }else{
      $_SESSION['PageRef'] = "";
      $oR3DCQuery->UpdateSIDTimeout($ConfigFile, $_SESSION['sid']);
      $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);
    }

    if(!$bCronEnabled){

      if($oR3DCQuery->ELOIsActive()){
        $oR3DCQuery->ELOCreateRatings();
      }

      $oR3DCQuery->MangeGameTimeOuts();
    }
  }
  ///////////////////////////////////////////////////////////////////////

  LanguageFile::load_language_file($Root_Path . 'includes/languages/' . preg_replace('/\.txt/', '.php', $_SESSION['language']));
  
  $allowed_file_types = array('image/bmp', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png');  // includes pjpeg (progressive jpeg) for stupid IE version < 10

  /**********************************************************************
  * UploadImageFile
  *
  */
  
  function UploadImageFile($UploadDrive, $MaxFileSize, $MaxWidth, $MaxHeight){
	 // Declarations
    $aError = array("IDS_NO_IMAGE_UPLOADED", "", "");

    // Check if an image was uploaded
    if($_FILES['fFileName']['error'] != 4)
	{
		// Gen unique ID
		$UniqueID = substr(md5(rand(0,100000)), 5);
		// Sets the upload drive
		$uploadFile = $UploadDrive.$UniqueID.$_FILES['fFileName']['name'];
		// Check if the image is less than the max file size
		if($_FILES['fFileName']['size'] <= $MaxFileSize)
		{
			$file_type = $_FILES['fFileName']['type'];
			$file_name = $_FILES['fFileName']['tmp_name'];
			// Check if the file is a valid type
			if(in_array($file_type, array("image/gif", "image/jpeg", "image/png", "image/bmp")))
			{
				// Resize image (keeping the aspect ration) if it is too big.
				list($width, $height, $type, $attr) = getimagesize($file_name);
				//exit();
				if($width <= $MaxWidth && $height <= $MaxHeight)
				{
					// upload the image
					if(move_uploaded_file($_FILES['fFileName']['tmp_name'], $uploadFile))
					{
						chmod($uploadFile, 0755);
						// Clear the error variable
						$aError = array("", $uploadFile, $UniqueID.$_FILES['fFileName']['name']);
					}
					else
					{
						// Setup error message
						$aError = array("IDS_IMAGE_MOVE_ERROR", "", "");
						// Unlink the temp file
						unlink($_FILES['fFileName']['tmp_name']);
					}
				}
				else
				{
					switch($file_type)
					{
						case 'image/gif':
							$image = imagecreatefromgif($file_name);
							break;
						case 'image/jpeg':
							$image = imagecreatefromjpeg($file_name);
							break;
						case 'image/png':
							$image = imagecreatefrompng($file_name);
							break;
						case 'image/bmp':
							$image = imagecreatefromgif($file_name);
							break;
						default:
							$image = false;
					}
					if($image == false)
					{
						return array("IDS_IMAGE_RESIZE_ERROR", "", "");
					}
					// Work out new width/height that keeps the same aspect ratio.
					$ratio = $width/$height;
					if($width > $height)
					{
						$newWidth = $MaxWidth;
						$newHeight = round($MaxWidth / $ratio);
					}
					else
					{
						$newHeight = $MaxHeight;
						$newWidth = round($MaxHeight * $ratio);
					}
					// Create a new image, copy the uploaded image to it, so it can be resampled to the correct size.
					$resized_image = imagecreatetruecolor($newWidth, $newHeight);
					imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
					imagepng($resized_image, $uploadFile, 9);
					
					chmod($uploadFile, 0755);
					$aError = array("", $uploadFile, $UniqueID.$_FILES['fFileName']['name']);
				}
			}
			else
			{
				// Setup error message
				$aError = array("IDS_IMAGE_TYPE_ERROR", "", "");
				// Unlink the temp file
				unlink($_FILES['fFileName']['tmp_name']);
			}
		}
		else
		{
			// Setup error message
			$aError = array("IDS_IMAGE_TOO_LARGE", "", "");
			// Unlink the temp file
			unlink($_FILES['fFileName']['tmp_name']);
		}
	}

    return $aError;
  }
  

  $avatarmethod = $oAvatars->GetAdminAvatarSettings();

  if($avatarmethod == 1 && isset($_POST['uploading'])){

    // $FileName = $_POST['txtFileName'];
    // $cmdAddAvatar = $_POST['cmdAddAvatar'];

    // $errorid = "";

    // $BanDirAbsolute = $conf['avatar_absolute_directory_location']."USER/";

    // if($cmdAddAvatar != ""){

      // $aReturn = array();
      // $aReturn = UploadImageFile($BanDirAbsolute, $conf['avatar_image_disk_size_in_bytes'], $conf['avatar_image_width'], $conf['avatar_image_height']);

      // if($aReturn[0] == "" && $aReturn[1] != "" && $aReturn[2] != ""){

        // //Add The image to db
        // $oAvatars->CreateUpdateAvatar2($_SESSION['id'], "USER/".$aReturn[2]);

      // }else{
        // //Error
        // $errorid = $aReturn[0];
      // }

    // }
	///----------------
	
	$errors = array(
		'NO_FILE_UPLOADED' => __l('No image has been uploaded. Please select an image.'),
		'INVALID_FILE_TYPE' => __l('The file type is not allowed. Upload only .bmp, .gif, .jpg or .png files.'),
		'TEMP_FILE_SIZE_EXCEEDED' => __l('Unable to work with image because it is too large. It has to be {size} bytes or less.'),
		'FILE_SIZE_EXCEEDED' => __l('The image file size is too large. It cannot be larger than {size} bytes.'),
		'UNABLE_TO_MOVE_FILE_TO_TEMP_LOCATION' => __l('The file could not be moved to a temporary location on the server.'),
		'COULD_NOT_CREATE_IMAGE' => __l('Unable to create an image from uploaded file. Ensure the file format is supported.')
	);
	$errors['FILE_SIZE_EXCEEDED'] = preg_replace('/\{size\}/', $conf['avatar_image_disk_size_in_bytes'], $errors['FILE_SIZE_EXCEEDED']);
	
	if($_POST['action'] == 'upload')
	{
		$res = upload_image($conf, $allowed_file_types);
		if(!$res['success'])
		{
			$res['error'] = $errors[$res['error']];
		}
		echo json_encode($res);
		exit();
	}
	elseif($_POST['action'] == 'crop_and_assign')
	{
		$res = crop_image($conf);
		if($res['success'])
		{
			assign_image($oAvatars);
			$res['image_url'] = 'USER/' . $_SESSION['id'] . '.png';
		}
		else
		{
			$res['error'] = $errors[$res['error']];
		}
		echo json_encode($res);
		exit();
	}
	elseif($_POST['action'] == 'upload_and_assign')
	{
		$res = upload_and_assign($conf, $oAvatars);
		if($res['success'])
		{
			$res['image_url'] = 'USER/' . $_SESSION['id'] . '.png';
		}
		else
		{
			$res['error'] = $errors[$res['error']];
		}
		echo json_encode($res);
		//upload_image($conf);
		//$res = crop_image($conf);
		//assign_image($oAvatars);
		exit();
	}

  }
  else
  {
    $avatar = $_GET['avatar'];
    $cmdAddAvatar = $_GET['cmdAddAvatar'];

    if($cmdAddAvatar != ""){
      $oAvatars->CreateUpdateAvatar($_SESSION['id'], $avatar);
    }

  }
  
  function upload_image($conf, $allowed_file_types)
  {
	$result = array('success' => TRUE);
	$avatar_dir = $conf['avatar_absolute_directory_location'];
	$user_id = $_SESSION['id'];
	
	if(isset($_FILES['image']))
	{
		$file = $_FILES['image'];
		//echo "file type is" . $file['type'];
		// Must be allowable image type.
		if(!in_array($file['type'], $allowed_file_types))
		{
			$result['success'] = FALSE;
			$result['error'] = 'INVALID_FILE_TYPE';
		}
		// Temp image must not exceed 2MB.
		else if($file['size'] > 2097152)
		{
			$result['success'] = FALSE;
			$result['error'] = 'TEMP_FILE_SIZE_EXCEEDED';
		}
		// Move to temp location if all is good
		if($result['success'])
		{
			if(move_uploaded_file($file['tmp_name'], $avatar_dir . '/USER/tmp/' . $user_id))
			{
				chmod($avatar_dir . '/USER/tmp/' . $user_id, 0755);
				$result['filetype'] = $file['type'];
				$result['size'] = $file['size'];
				$result['filename'] = $user_id;
			}
			else
			{
				$result['success'] = FALSE;
				$result['error'] = 'UNABLE_TO_MOVE_FILE_TO_TEMP_LOCATION';
			}
		}
	}
	else
	{
		$result['success'] = FALSE;
		$result['error'] = 'NO_FILE_UPLOADED';
	}
	return $result;
  }
  
  function crop_image($conf)
  {
	// Need to crop the user's temp image file and save it in the USERS folder.
	$user_id = $_SESSION['id'];
	$avatar_dir = $conf['avatar_absolute_directory_location'];
	$crop_width = $_POST['width'];
	$crop_height = $_POST['height'];
	$crop_top = $_POST['top'];
	$crop_left = $_POST['left'];
	$max_width = $conf['avatar_image_width'];
	$max_height = $conf['avatar_image_height'];
	
	$dest_file = $avatar_dir . 'USER/' . $_SESSION['id'] . '.png';
	$src_file = $avatar_dir . 'USER/tmp/' . $_SESSION['id'];

	list($width, $height, $type, $attr) = getimagesize($src_file);

	// if($type == IMAGETYPE_GIF)
		// $filetype = 'image/gif';
	// if($type == IMAGETYPE_JPEG)
		// $filetype = 'image/jpeg';
	// if($type == IMAGETYPE_BMP)
		// $filetype = 'image/bmp';
	// if($type == IMAGETYPE_PNG)
		// $filetype = 'image/png';
	
	switch($type)
	{
		case IMAGETYPE_GIF:
			$image = imagecreatefromgif($src_file);
			break;
		case IMAGETYPE_JPEG:
			$image = imagecreatefromjpeg($src_file);
			break;
		case IMAGETYPE_PNG:
			$image = imagecreatefrompng($src_file);
			break;
		case IMAGETYPE_BMP:
			$image = imagecreatefromgif($src_file);
			break;
		default:
			$image = false;
	}
	if($image == false)
	{
		return array('success' => FALSE, 'error' => 'COULD_NOT_CREATE_IMAGE');
	}
	// // Work out new width/height that keeps the same aspect ratio.
	// $ratio = $width/$height;
	// if($width > $height)
	// {
		// $newWidth = $MaxWidth;
		// $newHeight = round($MaxWidth / $ratio);
	// }
	// else
	// {
		// $newHeight = $MaxHeight;
		// $newWidth = round($MaxHeight * $ratio);
	// }
	
	// Create a new image, copy the uploaded image to it, so it can be resampled to the correct size.
	$resized_image = imagecreatetruecolor($max_width, $max_height);
	//echo "crop to left: $crop_left, top: $crop_top, width: $crop_width, height: $crop_height, max: $max_width x $max_height";
	imagecopyresampled($resized_image, $image, 0, 0, $crop_left, $crop_top, $max_width, $max_height, $crop_width, $crop_height);
	imagepng($resized_image, $dest_file, 9);
	
	chmod($dest_file, 0755);

	return array('success' => TRUE);
	//$aError = array("", $uploadFile, $UniqueID.$_FILES['fFileName']['name']);
  }
  
  function assign_image($oAvatars)
  {
	$oAvatars->SetAvatar($_SESSION['id'], 'USER/' . $_SESSION['id'] . '.png');
  }
  
  function upload_and_assign($conf, $oAvatars)
  {
	$file = base64_decode(substr($_POST['image'], 22));	// remove 'data:image/png;base64,' from the start of the string
	$filesize = strlen($file);
	$result = array('success' => TRUE);
	
	if($filesize > $conf['avatar_image_disk_size_in_bytes'])
	{
		$result['success'] = FALSE;
		$result['error'] = 'FILE_SIZE_EXCEEDED';
		return $result;
	}
	
	// Create temp file to check mime type is image/png
	// $temp = tmpfile();
	// fwrite($temp, $file);
	//$mime = mime_content_type($temp);
	// $finfo = new finfo(FILEINFO_MIME_TYPE);
	// $mime = $finfo->buffer($file);
	// if($mime != 'image/png')
	// {
	// 	$result['success'] = FALSE;
	// 	$result['error'] = 'INVALID_FILE_TYPE';
	// 	return $result;
	// }
	// fclose($temp);
	
	// Save file
	file_put_contents('./avatars/USER/' . $_SESSION['id'] . '.png', $file);
	$path = $conf['avatar_absolute_directory_location'] . 'USER/' . $_SESSION['id'] . '.png';
	chmod($path, 0755);
	
	assign_image($oAvatars);
	
	return $result;
  }
  
  function clear_old_tmp_files()
  {
  
  }

?>

<html>
<head>
<title><?php echo __l('Configuration - Manage Avatar'); ?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript.php");?>

<script src="./includes/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>
<script type="text/javascript" src="./includes/jcrop/js/jquery.Jcrop.min.js"></script>
<script type="text/javascript" src="./includes/jquery/jquery.form.min.js"></script>
<link rel="stylesheet" type="text/css" href="./includes/jcrop/css/jquery.Jcrop.css" />
  
</head>
<body>

<?php include("./skins/".$SkinName."/layout_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  $oAvatars->Close();
  unset($oR3DCQuery);
  unset($oAvatars);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>