<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>


<?php
//////////////////////////////////////////////////////////////////////////
//Instantiate the CAvatars Class

$image = $oAvatars->GetAvatarImageName($_SESSION['id']);
if($image == '') $image = 'noimage.jpg';
?>
<div class="forumline" style="padding: 5px;">

<h2 class="sitemenuheader"><?php echo __l("Manage Avatar");?></h2>

<table>
	<tr>
		<td style="text-align: center">
			<?php echo __l('This is your current avatar:'); ?>
		</td>
	</tr>
	<tr>
		<td style="text-align: center">
			<img id="current_avatar" src='./avatars/<?php echo $image;?>' />
		</td>
	</tr>
</table>

<br/><br/>

<?php if($avatarmethod == 1): ?>
<button id="view_upload" class="mainoption"><?php echo __l('Upload new picture'); ?></button>
<button id="view_gallery" class="mainoption"><?php echo __l('Select from gallery'); ?></button>
<?php endif; ?>
<hr>

<div id="upload_view" style="display: none; padding-left: 5px;">

<div id="file_select" style="">
	<?php echo __l('Select an image to use as your avatar:'); ?><br/>
	<form id="upload_form" method="post" action="chess_cfg_avatar.php">
		<input id="file" name="image" type="file" />
		<input name="action" value="upload" type="hidden" />
		<input name="uploading" value="1" type="hidden" />
	</form>
	<button id="btn_upload" class="mainoption" ><?php echo __l('Upload'); ?></button>
	<br/>
	<span id="loading" style="display: none"><?php echo __l('Uploading, please wait...'); ?></span>
</div>

<div id="crop_section" style="clear: right; height: 700px; display: none">

	<p><?php echo __l("You can now crop the image if you wish to. Simply click on the image and drag the cursor to draw a rectangle over the part of the image you wish to use. On touch devices, the region is selected by tapping twice (one tap to select one corner of the region and another to select the opposite corner). When done click the 'Save' button under the preview image."); ?></p>

	<div style="width: 750px; float: left; position: relative; ">
		<p><?php echo __l('Original image:'); ?></p>
		<div id="org_img_container">
			<img id="org_img"/>
		</div>
	</div>
	<div style="width: 150px; position: relative; float: left">
		<p><?php echo __l('Image Preview:'); ?></p>
		<div id="preview_img_container" style="width: 100px; height: 100px; overflow: hidden">
			<img id="crop_img" src="./avatars/USER/tmp/test.jpg" style="border: none"/>
		</div>
		<p><button id="save" class="mainoption" ><?php echo __l('Save'); ?></button></p>
	</div>
	<div id="selection_mask" style="position: absolute; display: none; border: 1px solid red;"></div>
	<div id="selection_start" style="position: absolute; display: none; width: 3px; height: 3px; background-color: red;"></div>
	<div id="selection_end" style="position: absolute; display: none; width: 3px; height: 3px; background-color: red;"></div>
</div>

</div>	<!-- upload view -->

<div id="gallery_view" style="display: none">
	<table>
		<?php echo $oAvatars->GetAvatarDriveList(); ?>
	</table>
</div>

</div>

<script type="text/javascript">

var selection_start = undefined;		// pixels on screen
var selection_end = undefined;
var img_selection_start = undefined;	// pixels on image	
var img_selection_end = undefined;
var IAS = undefined;					// image area select object
var Jcrop = undefined;					// Jcrop object
var html5_method = false;				// Using html5 to crop image on brower and send data to server?

var prv_w = 100;
var prv_h = 100;
var crop = {top: 0, left: 0, width: 0, height: 0};

$(document).ready(function(){
	var method = '<?php echo $avatarmethod; ?>';
	if(method == '1')
		$('#upload_view').show();
	else
		$('#gallery_view').show();
	$('#view_upload').click(function(){ $('#upload_view').show(); $('#gallery_view').hide() });
	$('#view_gallery').click(function(){ $('#upload_view').hide(); $('#gallery_view').show() });
	init_upload();
	init_crop();
});

function init_upload()
{
	// Check for the various File API support.
	var hasFileAPI = false;
	if (window.File && window.FileReader && window.FileList && window.Blob)
		hasFileAPI = true;

	$('#preview_img_container').width(prv_w).height(prv_h);
	
	// If the File API is available, can preview image selected by user and select cropping
	// parameters before doing upload.
	if(hasFileAPI && isCanvasSupported())
	{
		html5_method = true;
		$('#btn_upload').hide();
		$('#file_select').change(function(evt){
			
			$('#crop_section').hide();
			
			if(evt.target.files.length == 0) return;	// no file selected
			var file = evt.target.files[0];
			if(!file.type.match('image.*'))
			{
				alert('<?php echo __l('The file type is not allowed. Upload only .bmp, .gif, .jpg or .png files.'); ?>');
				return;
			}
			
			var reader = new FileReader();

			reader.onload = function(e){
				// Ensure there is no width or height set, otherwise the loaded image will be forced to the
				// last image's size.
				$('#org_img').css('width', '').css('height', '');
				$('#crop_section').show();
				$('#org_img').attr('src', e.target.result);
				$('#crop_img').attr('src', e.target.result);
				//console.log('file size is: ' + e.total);
			}
			// Read in the image file as a data URL
			reader.readAsDataURL(file);
			
		});
	}
	else	// Have to submit file to server and then crop it.
	{
		$('#upload_form').ajaxForm();
		$('#btn_upload').click(function(){
			$('#loading').show();
			$('#crop_section').hide();
			$('#upload_form').ajaxSubmit({success: form_returns});
			return false;
		});
	}
}

function form_returns(responseText, statusText, xhr, $form)
{
	try{
		var response = jQuery.parseJSON(responseText);

		if(!response.success)
		{
			alert('Error occurred:\n' + response.error);
			return;
		}
		// Ensure there is no width or height set, otherwise the loaded image will be forced to the
		// last image's size.
		$('#org_img').css('width', '').css('height', '');
		// Set the temp image to use for cropping
		var d = new Date();
		$('#org_img').attr('src', './avatars/USER/tmp/' + response.filename + '?t=' + d.getTime());
		$('#crop_img').attr('src', './avatars/USER/tmp/' + response.filename + '?t=' + d.getTime());

		$('#crop_section').show();
	}
	catch(ex)
	{
		alert('An error occurred parsing the server response. Unable to proceed.');
	}
	$('#loading').hide();
}

function init_crop()
{
	var is_touch = false;
	if(('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch) {
          is_touch = true;
    }
	
	// Initialise Jcrop when the image has been loaded.
	$('#org_img').load(function(){
		reinit_Jcrop();
	});
	
	$('#save').click(function(){
		if(html5_method)	// Generate cropped image on browser and send to server.
		{
			var canvas = document.createElement('canvas');
			var ctx = canvas.getContext("2d");
			canvas.width = prv_w;
			canvas.height = prv_h;
			// after the source image is specified the next 4 params relate to the source image and the last 
			// four to the target (left, top, width, height)
			ctx.drawImage($('#org_img')[0], crop.left, crop.top, crop.width, crop.height, 0, 0, prv_w, prv_h);
			
			var fd = new FormData();
			fd.append("action", "upload_and_assign");
			fd.append("uploading", "1");
			fd.append("ignore", "1");
			fd.append("image", canvas.toDataURL("image/png"));	// generates a base 64 encoded string of the image
			//console.log(fd);
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "chess_cfg_avatar.php");
			xhr.onreadystatechange = function()
			{
				if (xhr.readyState==4 && xhr.status==200)
				{
					var response = jQuery.parseJSON(xhr.response);
					if(!response.success)
						alert(response.error);
					else
					{
						alert('Avatar updated');
						$('#current_avatar').attr('src', '');
						var d = new Date();
						$('#current_avatar').attr('src', './avatars/' + response.image_url + '?t=' + d.getTime());
					}
				}
			} 
			xhr.send(fd);
		}
		else	// Old browser method. File already exists on server and just needs the crop parameters.
		{
			$.post('chess_cfg_avatar.php', {action: 'crop_and_assign', width: crop.width, height: crop.height, top: crop.top, left: crop.left, uploading: 1}, function(response){
				var response = jQuery.parseJSON(response);
				if(!response.success)
					alert(response.error);
				else
				{
					alert('Avatar updated');
					$('#current_avatar').attr('src', '');
					var d = new Date();
					$('#current_avatar').attr('src', './avatars/' + response.image_url + '?t=' + d.getTime());
					//console.log('success!');
				}
				//console.log('done', response);
			});
		}
	});
	
}

// To be called once the source image has been loaded.
function reinit_Jcrop()
{
	// If previous instance exists need to destroy it so that the image can be changed. Jcrop 
	// creates a copy of the image once initialised.
	if(Jcrop !== undefined) Jcrop.destroy();
	// Initialise Jcrop.
	$('#org_img').Jcrop({
		boxWidth: 500, boxHeight: 500,
		onSelect: function(selection){
			preview(selection.x, selection.y, selection.w, selection.h);
		},
		onChange: function(selection){
			preview(selection.x, selection.y, selection.w, selection.h);
		},
		onRelease: function(){
			preview(0, 0, $('#org_img').width(), $('#org_img').height());
		}
	}, function(){
		Jcrop = this;
	});
	
	reset_preview();
}

function preview(x, y, w, h)
{
	var img = $('#org_img');
	var scaleX = prv_w / w;
	var scaleY = prv_h / h;
	$('#crop_img').css({
		width: scaleX * img.width(),
		height: scaleY * img.height(),
		marginLeft: -x * scaleX,
		marginTop: -y * scaleY
	});

	crop.left = x;
	crop.top = y;
	crop.width = w;
	crop.height = h;
}

function reset_preview()
{
	$('#crop_img').css({
		width: prv_w,
		height: prv_h,
		marginLeft: 0,
		marginTop: 0
	});
	
	crop.left = 0;
	crop.top = 0;
	crop.width = $('#org_img').width();
	crop.height = $('#org_img').height();
}

function isCanvasSupported(){
  var elem = document.createElement('canvas');
  return !!(elem.getContext && elem.getContext('2d'));
}

</script>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo __l("Back To Main Page"); ?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo __l("Back To Configuration");?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>