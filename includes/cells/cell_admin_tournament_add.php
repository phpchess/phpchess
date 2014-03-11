<?php

	if(!defined('CHECK_PHPCHESS')){
		die("Hacking attempt");
		exit;
	}
	//var_dump($__pd);
	// Edit a tournament 
	if(isset($__pd['error']))
	{
		echo "<b>" . $__pd['error'] . "</b>";
		exit;
	}
?>

<h2><?php echo _T('IDS_Admin_Tournament_Add_CREATE', $config); ?></h2>

<?php 
if(isset($__pd['errors']))
{
	echo '<div style="color: red; margin-bottom: 10px;">';
	echo _T('IDS_Form_Message_SUBMITERROR', $config) . "<br/>";
	foreach($__pd['errors'] as $field => $msg)
	{
		if(isset($__pd['fields'][$field]['label']))
			$field = $__pd['fields'][$field]['label'];
		echo "$field: $msg<br/>";
	}
	echo "</div>";
}
if(isset($__pd['success']))
{
	echo '<div style="color: green; margin-bottom: 10px;">'. $__pd['success'] . '</div>';
}
?>

<form method="post">
	<table>
<?php

	//$html = '';
	foreach($__pd['fields'] as $field => $opts)
	{
		$required = "";
		$label = "";
		if(!empty($__pd['fields'][$field]['required']))
			$required = '<span style="color: red" title="' . _T('IDS_Form_Message_FIELDREQUIRED', $config) . '">*</span>';
		if(isset($__pd['fields'][$field]['label']) && $__pd['fields'][$field]['label'] != '')
		{
			$label = $__pd['fields'][$field]['label'];
		}
		else
		{
			$label = str_replace('_', ' ', $field);
			$label = ucwords($label);
		}
		
		echo "<tr><td>$label $required</td><td>";
		echo create_html_element($field, $opts, $__pd['tournament'][$field]);
		echo "</td></tr>";
	}
	
	//echo $html;
	
	// Works out what html element to generate based on the options provided.
	function create_html_element($field, $opts, $value)
	{
		$html = "";
		if(!isset($opts['render_type']))
			$opts['render_type'] = 'text';
		switch($opts['render_type'])
		{
			case 'description':
				$html = '<textarea name="' . $field . '"';
				if(isset($opts['id'])) $html .= ' id="' .  $opts['id'] .'"';
				if(isset($opts['width'])) $html .= ' cols="' . $opts['width'] . '"';
				$html .= ' >' . $value . '</textarea>';
				break;
			case 'date':
				$html = '<input name="' . $field . '"';
				if(isset($opts['id'])) $html .= ' id="' .  $opts['id'] .'"';
				if(isset($opts['width'])) $html .= ' size="' . $opts['width'] . '"';
				// Must format date from db format to user format.
				//$date = DateTime::createFromFormat("Y-m-d H:i:s", $value);
                //if($date) $value = $date->format("Y-m-d");
				$html .= ' value="' . $value . '" />';
				break;
			case 'select':
				$html = '<select name="' . $field . '"';
				if(isset($opts['id'])) $html .= ' id="' .  $opts['id'] .'"';
				$html .= '>';
				foreach($opts['items'] as $id => $text)
				{
					$html .= '<option value="' . $id . '"';
					if($value == $id) $html .= ' selected="selected" ';
					$html .= ">$text";
					$html .= '</option>';
				}
				$html .= '</select>';
				break;
			case 'text':
			default:
				$html = '<input name="' . $field . '"';
				if(isset($opts['id'])) $html .= ' id="' .  $opts['id'] .'"';
				if(isset($opts['width'])) $html .= ' size="' . $opts['width'] . '"';
				$html .= ' value="' . $value . '" />';
		
		}
		return $html;
	}

?>
	</table>
	
	
	<input name="create" type="submit" value="<?php echo _T('IDS_Form_Button_CREATE', $config); ?>" /><input name="cancel" type="button" value="<?php echo _T('IDS_Form_Button_BACK', $config); ?>" onclick="window.location.href='chess_tournament_v2.php'"/>
</form>


<script>

	$(document).ready(function()
	{
		$("#_date_cutoff").datepicker({dateFormat: "yy-mm-dd", showButtonPanel: true });
		$("#_date_start").datepicker({dateFormat: "yy-mm-dd", showButtonPanel: true });
	});

	

</script>