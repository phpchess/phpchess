<?php

	if(!defined('CHECK_PHPCHESS')){
		die("Hacking attempt");
		exit;
	}
	// var_dump($__pd);
	// Edit a tournament 
	if(isset($__pd['error']))
	{
		echo "<b>" . $__pd['error'] . "</b>";
		exit;
	}
?>

<style>
	.tournament_info
	{
		font-size: 0.9em;
		margin-bottom: 2em;
		padding-left: 2em;
		font-style: italic;
	}
</style>

<?php
	$_str_update = _T('IDS_Admin_Tournament_Games_Edit_UPDATE', $config);
	$_str_name = _T('IDS_Admin_Tournament_Games_Edit_NAME', $config);
	$_str_comment = _T('IDS_Admin_Tournament_Games_Edit_COMMENT', $config);
	$_str_start = _T('IDS_Admin_Tournament_Games_Edit_START', $config);
	$_str_submiterror = _T('IDS_Form_Message_SUBMITERROR', $config);
	$_str_required = _T('IDS_Form_Message_FIELDREQUIRED', $config);
	$_str_setupboard = _T('IDS_Admin_Tournament_Games_Edit_SETUP', $config);
	$_str_btn_update = _T('IDS_Form_Button_UPDATE', $config);
	$_str_btn_back = _T('IDS_Form_Button_BACK', $config);
?>

<h2><?php echo $_str_update; ?></h2>
<div class="tournament_info">
	<div>
	<?php echo $_str_name . ": " . $__pd['tournament']['t_name']; ?><br />
	<?php echo $_str_comment . ": " . $__pd['tournament']['t_comment']; ?><br />
	<?php 
		$val = $__pd['tournament']['t_startdate'];
		echo "$_str_start: $val";
		//$date = DateTime::createFromFormat("Y-m-d H:i:s", $val);
        //if($date) echo $date->format("M-d-Y H:i:s");
	?>
	</div>
</div>

<?php 
if(isset($__pd['errors']))
{
	echo '<div style="color: red; margin-bottom: 10px;">';
	echo "$_str_submiterror<br/>";
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

	$html = '';
	foreach($__pd['fields'] as $field => $opts)
	{
		$required = "";
		$label = "";
		if(!empty($__pd['fields'][$field]['required']))
			$required = '<span style="color: red" title="$_str_required">*</span>';
		if(isset($__pd['fields'][$field]['label']) && $__pd['fields'][$field]['label'] != '')
		{
			$label = $__pd['fields'][$field]['label'];
		}
		else
		{
			$label = str_replace('_', ' ', $field);
			$label = ucwords($label);
		}
		// The value comes either from what was defined in the field definition, or from a previous form submit, or
		// just an empty string.
		$val = isset($__pd['game'][$field]) ? $__pd['game'][$field] : (isset($opts['value']) ? $opts['value'] : '');
		
		$html .= "<tr><td>$label $required</td><td>";
		$html .= create_html_element($field, $opts, $val);
		if($field == 'fen') $html .= "<button onclick=\"open_FEN_builder();\">$_str_setupboard</button>";
		$html .= "</td></tr>";
	}
	
	echo $html;
	
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
				$ts = strtotime($value);
				$value = date('Y-m-d', $ts);
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
					$html .= '>(' . $id . ') ' . $text;
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
	
	
	<input name="update" type="submit" value="<?php echo $_str_btn_update; ?>" />
	<input name="cancel" type="button" value="<?php echo $_str_btn_back; ?>" onclick="window.location.href='tournament_games.php?id=<?php echo $__pd['tournament']['t_id']; ?>'"/>
</form>

<script>
	function open_FEN_builder(){
		var url = "<?php echo $Root_Path; ?>pgnviewer/board2fen.html";
		var hWnd = window.open(url,"d9d5437c706e1a75aee15a65bcbcca14","width=580,height=420,resizable=no,scrollbars=yes,status=yes");
		if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name="home"; hWnd.location.href=url; }}
	};
</script>
