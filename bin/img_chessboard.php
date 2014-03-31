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

  $light = $_GET['w'];
  $dark = $_GET['b'];

  $imagesize = $_GET['imgsize'];

  if(!is_numeric($imagesize)){
    $imagesize = 38;
  }

  if($imagesize <= 0){
    $imagesize = 38;
  }

  // split the color code into chars
  $charsW = preg_split('//', $light, -1, PREG_SPLIT_NO_EMPTY);
  $charsB = preg_split('//', $dark, -1, PREG_SPLIT_NO_EMPTY);

  // light color
  $w1 = "0x";
  $w2 = "0x";
  $w3 = "0x";

  $i=0;
  $nCount = count($charsW);
  while($i < $nCount){

    if($i == 0 || $i == 1){
      $w1 = $w1."".$charsW[$i];
    }elseif($i == 2 || $i == 3){
      $w2 = $w2."".$charsW[$i];
    }elseif($i == 4 || $i == 5){
      $w3 = $w3."".$charsW[$i];
    }

    $i++;
  }

  // dark color
  $b1 = "0x";
  $b2 = "0x";
  $b3 = "0x";

  $i=0;
  $nCount = count($charsB);
  while($i < $nCount){

    if($i == 0 || $i == 1){
      $b1 = $b1."".$charsB[$i];
    }elseif($i == 2 || $i == 3){
      $b2 = $b2."".$charsB[$i];
    }elseif($i == 4 || $i == 5){
      $b3 = $b3."".$charsB[$i];
    }

    $i++;
  }

  Header("Content-Type: image/png"); 

  // Create the image
  $img = ImageCreate(($imagesize * 2), ($imagesize * 2));  

  // Variable to store color
  $white = ImageColorAllocate($img, hexdec($w1), hexdec($w2), hexdec($w3)); 
  $black = ImageColorAllocate($img, hexdec($b1), hexdec($b2), hexdec($b3)); 

  // Fill the image with black
  ImageFill($img, 0, 0, $white); 

  // create the board
  imagefilledrectangle($img, $imagesize, 0, ($imagesize * 2), $imagesize, $black);
  imagefilledrectangle($img, 0, $imagesize, $imagesize, ($imagesize * 2), $black);

  // Output
  imagepng($img);
  imagedestroy($img);

?>