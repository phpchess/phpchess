<?php

class LanguageParser
{

	private $parse_data = array();

	public function ParseFiles($directory)
	{
		$files = $this->get_file_names($directory, '');
		$results = array();
		foreach($files as $file)
		{
			$results[$file] = $this->parse_file($directory . $file);
		}
		ksort($results);
		$this->parse_data = $results;
	}
	
	private function get_file_names($base, $curdir)
	{
		$files = array();
		foreach (new DirectoryIterator($base . $curdir) as $fileInfo) {
			if($fileInfo->isDot()) continue;
			if($fileInfo->isFile())
			{
				$ext = pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION);
				if($ext == "php" || $ext == "inc")
				{
					$files[] = $curdir . '\\' . $fileInfo->getFilename();
				}
			}
			else
			{
				$files = array_merge($files, $this->get_file_names($base, $curdir . '\\' . $fileInfo->getFilename()));
			}
		}
		return $files;
	}
	
	private function parse_file($file)
	{
		// Might have to manually go through character by character later on. Code can be commented out
		// which is something not tested for here.

		$results = array();
		$lines = file($file);
		if($lines === FALSE) return;
		$num = 1;
		foreach($lines as $linenum => $line)
		{
			// Use 2nd backreference to match opening quote. Stop when a closing ) or a , is
			// found (there can be more than one parameter passed to the function).
			if(preg_match('/(?<!function )__l\(\s*(("|\').*?\2)\s*(\)|,\s*\'(.*)\')/', $line, $matches))
			{
				$res = array('line' => $num, 'value' => $matches[1], 'enclosed' => $matches[2], 'custom' => FALSE);
				$results[] = $res;
			}
			$num++;
		}
		return $results;
	}
	
	public function generate_language_file($filepathname)
	{
		LanguageFile::load_language_file($filepathname);
	
		$processed = array();
		foreach($this->parse_data as $file => $parse_data)
		{
			for($i = 0; $i < count($parse_data); $i++)
			{
				$data = $parse_data[$i];
				$val = substr($data['value'], 1, strlen($data['value']) - 2);
				if(strlen($val) > 50)
				{
					$val = substr($val, 0, 49) . '...' . substr(md5($val), 0, 6);
				}
				$val = $data['enclosed'] . $val . $data['enclosed'];
				$data['file'] = $file;
				if(!isset($processed[$val]))
				{
					$processed[$val] = $data;
					$processed[$val]['occurances'] = 1;
				}
				else
				{
					$processed[$val]['occurances']++;
				}
			}
		}
		
		$content = "<?php\n";	
		$content .= <<<qq
//    Note: If an item occurs many times in different files, the value in the comment indicates how many times
//    the item appears. For example '// x3' indicates an item appears 3 times throughout the program.

qq;
		$curfile = "";
		foreach($processed as $key => $data)
		{
			if($curfile != $data['file'])
			{
				$curfile = $data['file'];
				$content .= "\n//// File: $curfile\n";
			}
			
			$exists = LanguageFile::get_string_bool(substr($key, 1, strlen($key) - 2));
			if($exists)
				$content .= '$lang[' . $key . '] = "' . $exists . '";';
			else
				$content .= '$lang[' . $key . '] = ' . $data['value'] . ';';
			
			if($data['occurances'] != 1)
				$content .= "\t\t// x" . $data['occurances'];
			$content .= "\n";
		}
		$content .= '?>';
		return file_put_contents($filepathname, $content);
	}

}

class LanguageFile
{
	private static $loaded = FALSE;
	private static $lang;

	public static function load_language_file($file)
	{
		if(LanguageFile::$loaded) return;
		if(file_exists($file))
			include_once($file);
		else
			$lang = array();
		LanguageFile::$lang = $lang;
		LanguageFile::$loaded = TRUE;
	}
	// Get language file by reading value in the db. Use this function when the user's session
	// doesn't have the language file set.
	public static function load_language_file2($conf)
	{
		$host = $conf['database_host'];
		$dbnm = $conf['database_name'];
		$user = $conf['database_login'];
		$pass = $conf['database_pass'];

		$link = mysql_connect($host, $user, $pass);
		mysql_select_db($dbnm);

		$query = "SELECT * FROM server_language WHERE o_id=1";
		$return = mysql_query($query, $link) or die(mysql_error());
		$num = mysql_numrows($return);

		if($num != 0){
			$LanguageFile = $conf['absolute_directory_location']."includes/languages/".mysql_result($return, 0, "o_languagefile");
		}
		
		LanguageFile::load_language_file(preg_replace('/\.txt/', '.php', $LanguageFile));
	}
	
	public static function get_string($lang_key)
	{
		if(strlen($lang_key) > 50)
		{
			$lang_key = substr($lang_key, 0, 49) . '...' . substr(md5($lang_key), 0, 6);
		}
		if(isset(LanguageFile::$lang[$lang_key]))
			return LanguageFile::$lang[$lang_key];
		else
			return "!!STRING NOT FOUND!!";
	}
	
	public static function get_string_bool($lang_key)
	{
		if(strlen($lang_key) > 50)
		{
			$lang_key = substr($lang_key, 0, 49) . '...' . substr(md5($lang_key), 0, 6);
		}
		if(isset(LanguageFile::$lang[$lang_key]))
			return LanguageFile::$lang[$lang_key];
		else
			return FALSE;
	}

}


function __l($lang_key, $arg2 = null)
{
	return LanguageFile::get_string($lang_key);
}

?>