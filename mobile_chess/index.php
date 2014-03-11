<?
	if (isset($_SERVER["HTTP_VAR_SESSION"]))
		session_id($_SERVER["HTTP_VAR_SESSION"]);

	session_start();


        ////////////////////////////////////////
        // Config file includes and settings
        ////////////////////////////////////////

	include("../bin/config.php");

	$mysql["host"] = $conf['database_host'];
	$mysql["username"] = $conf['database_login'];
	$mysql["password"] = $conf['database_pass'];
	$mysql["database"] = $conf['database_name'];

	$server["timelimit"] = 3600;

        ////////////////////////////////////////

	include("scripts/general/mysql.php");

	if (!isset($_GET["action"]))
		$_GET["action"] = "";

	if (isset($_SESSION["id"]))
	{
		include("scripts/general/authentification.php");

		switch ($_GET["action"])
		{
			case "getimage" :
			{
				include("scripts/inside/getimage_get.php");
			}; break;
			case "getimagewhite" :
			{
				include("scripts/inside/getimagewhite_get.php");
			}; break;
			case "getimageblack" :
			{
				include("scripts/inside/getimageblack_get.php");
			}; break;
			case "getboardsettings" :
			{
				include("scripts/inside/getboardsettings_get.php");
			}; break;
			case "getonlineplayers" :
			{
				include("scripts/inside/getonlineplayers_get.php");
			}; break;
			case "getplayerinfo" :
			{
				include("scripts/inside/getplayerinfo_get.php");
			}; break;
			case "setchallenge" :
			{
				include("scripts/inside/setchallenge_post.php");
			}; break;
			case "gettipoftheday" :
			{
				include("scripts/inside/gettipoftheday_get.php");
			}; break;
			case "msglist" :
			{
				include("scripts/inside/msglist_get.php");
			}; break;
			case "msgread" :
			{
				include("scripts/inside/msgread_get.php");
			}; break;
			case "msgdelete" :
			{
				include("scripts/inside/msgdelete_post.php");
			}; break;
			case "msgdeleteall" :
			{
				include("scripts/inside/msgdeleteall_post.php");
			}; break;
			case "msgwrite" :
			{
				include("scripts/inside/msgwrite_post.php");
			}; break;
			case "getplayerslist" :
			{
				include("scripts/inside/getplayerslist_get.php");
			}; break;
			case "getservermsg" :
			{
				include("scripts/inside/getservermsg_get.php");
			}; break;
			case "getchallengesincoming" :
			{
				include("scripts/inside/getchallengesincoming_get.php");
			}; break;
			case "getgames" :
			{
				include("scripts/inside/getchallengesall_get.php");
			}; break;
			case "getoldgames" :
			{
				include("scripts/inside/getchallengesold_get.php");
			}; break;
			case "getchallengestatus" :
			{
				include("scripts/inside/getchallengestatus_get.php");
			}; break;
			case "acceptchallenge" :
			{
				include("scripts/inside/setchallengestatus_post.php");
			}; break;
			case "declinechallenge" :
			{
				include("scripts/inside/setchallengestatus_post.php");
			}; break;
			case "getallmoves" :
			{
				include("scripts/inside/getchallengemoves_get.php");
			}; break;
			case "getnewmove" :
			{
				include("scripts/inside/getchallengenewmove_get.php");
			}; break;
			case "sendmove" :
			{
				include("scripts/inside/setchallengemove_post.php");
			}; break;
			case "setchallengestate" :
			{
				include("scripts/inside/setchallengestate_post.php");
			}; break;
			default :
			{
				header("var_session: ".session_id());
			}
		};
	} else
	{
		switch($_GET["action"])
		{
			case "getimage" :
			{
				include("scripts/outside/getimage_get.php");
			}; break;
			case "getimagewhite" :
			{
				include("scripts/outside/getimagewhite_get.php");
			}; break;
			case "getimageblack" :
			{
				include("scripts/outside/getimageblack_get.php");
			}; break;
			case "getboardsettings" :
			{
				include("scripts/outside/getboardsettings_get.php");
			}; break;
			case "login" :
			{
				include("scripts/outside/login_post.php");
			}; break;
			default :
			{
				//include("scripts/inside/setchallenge_post.php");
				header("var_session: ".session_id());
			}
		}
	}
?>
