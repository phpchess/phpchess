<?
	$photo = ImageCreateFromGif("./images/pieces/".$_SERVER["HTTP_VAR_THEME"]."/".$_SERVER["HTTP_VAR_IMAGE"]."_black.gif");

	$percentage = 1;

	$width = $_SERVER["HTTP_VAR_PHONE_WIDTH"];
	$height = $_SERVER["HTTP_VAR_PHONE_HEIGHT"];

	$width  = (ImageSX($photo)*$width)/400;
	$height = (ImageSY($photo)*$height)/400;

	if (ImageSX($photo) > $width)
    {
        if (ImageSX($photo) > ImageSY($photo))
        {
            $percentage = ImageSX($photo) / $width;
        } else
        {
            $percentage = ImageSY($photo) / $width;
        }
    } else
    {
        if (ImageSY($photo) > $heigh)
        {
            $percentage = ImageSY($photo) / $heigh;
        }
    }

	$photo_small =imagecreate(ImageSX($photo)/$percentage,ImageSY($photo)/$percentage);
	ImageCopyResized($photo_small, $photo, 0, 0, 0, 0, (ImageSX($photo)/$percentage), (ImageSY($photo)/$percentage), ImageSX($photo), ImageSY($photo));

	ob_start();
	ImageJpeg($photo_small);
	$length = ob_get_length();
	ob_end_clean();

	header("Content-type: image/jpeg");
	header("Content-length: ".$length);
	ImageJpeg($photo_small);
?>