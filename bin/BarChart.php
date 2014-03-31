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

  $win = $_GET['win'];
  $loss = $_GET['loss'];
  $draw = $_GET['draw'];

  $values = array($win,$loss,$draw);

  $columns  = count($values);
  $width = 300;
  $height = 200;
  $padding = 5;
  $column_width = $width / $columns ;

  $im = imagecreate($width,$height);
  $gray = imagecolorallocate ($im,0xcc,0xcc,0xcc);
  $gray_lite = imagecolorallocate ($im,0xee,0xee,0xee);
  $gray_dark = imagecolorallocate ($im,0x7f,0x7f,0x7f);
  $white = imagecolorallocate ($im,0xff,0xff,0xff);
	
  imagefilledrectangle($im,0,0,$width,$height,$white);
	
  if($win == 0 && $loss == 0 && $draw == 0){

  }else{
    $maxv = 0;

    for($i=0;$i<$columns;$i++){
      $maxv = max($values[$i],$maxv);
    }
		
    for($i=0;$i<$columns;$i++){

      $column_height = ($height / 100) * (( $values[$i] / $maxv) *100);

      $x1 = $i*$column_width;
      $y1 = $height-$column_height;
      $x2 = (($i+1)*$column_width)-$padding;
      $y2 = $height;

      imagefilledrectangle($im,$x1,$y1,$x2,$y2,$gray);
      imageline($im,$x1,$y1,$x1,$y2,$gray_lite);
      imageline($im,$x1,$y2,$x2,$y2,$gray_lite);
      imageline($im,$x2,$y1,$x2,$y2,$gray_dark);

    }

  }

  header ("Content-type: image/png");
  imagepng($im);

?>