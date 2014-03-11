<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }


/*********************************************************************************
* Main Site Config
*
*/

//////////////////////////////////////////////////////////////////////////////////
// Drag & Drop Chessboard Settings
//////////////////////////////////////////////////////////////////////////////////

define("CFG_CHESSBOARD_IMG_SIZE", 48);
define("CFG_CHESSBOARD_TABLE_SIZE", 414);
define("CFG_CHESSBOARD_IFRAME_SIZE", 388);

//////////////////////////////////////////////////////////////////////////////////
// chess_club_list.php
//////////////////////////////////////////////////////////////////////////////////

// GetClubListHTML() - CR3DCQuery.php
define("CFG_GETCLUBLISTHTML_TABLE1_WIDTH", 500);
define("CFG_GETCLUBLISTHTML_TABLE1_BORDER", 0);
define("CFG_GETCLUBLISTHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETCLUBLISTHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETCLUBLISTHTML_TABLE1_ALIGN", "center");
define("CFG_GETCLUBLISTHTML_ROW1_WIDTH", 300);

//////////////////////////////////////////////////////////////////////////////////
// chess_tournament_status.php
//////////////////////////////////////////////////////////////////////////////////

// v2GetActiveTournamentListHTML() - CR3DCQuery.php
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_WIDTH", 600);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_BORDER", 0);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_CELLPADDING", 3);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_CELLSPACING", 1);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_ALIGN", "center");

define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_WIDTH", 600);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_BORDER", 0);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_CELLPADDING", 3);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_CELLSPACING", 1);
define("CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_ALIGN", "center");

//GetClientTournamentGameList() - CR3DCQuery.php
define("CFG_GETCLIENTTOURNAMENTLIST_TABLE1_WIDTH", 600);
define("CFG_GETCLIENTTOURNAMENTLIST_TABLE1_BORDER", 0);
define("CFG_GETCLIENTTOURNAMENTLIST_TABLE1_CELLPADDING", 3);
define("CFG_GETCLIENTTOURNAMENTLIST_TABLE1_CELLSPACING", 1);
define("CFG_GETCLIENTTOURNAMENTLIST_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// chess_v2_tournament_status.php
//////////////////////////////////////////////////////////////////////////////////

// v2ViewTournamentGameStatusCalendar() - CR3DCQuery.php
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_WIDTH", 500);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_BORDER", 0);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_CELLPADDING", 1);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_CELLSPACING", 0);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_ALIGN", "center");
define("CFG_GETCLUBLISTHTML_ROWX1_WIDTH", 21);

define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_WIDTH", 500);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_BORDER", 1);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_CELLPADDING", 1);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_CELLSPACING", 0);
define("CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_ALIGN", "center");
define("CFG_GETCLUBLISTHTML_ROWX2_WIDTH", 70);
define("CFG_GETCLUBLISTHTML_ROWX2_HEIGHT", 70);

// v2GenerateTournamentResultTable() - CR3DCQuery.php
define("CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_WIDTH", 500);
define("CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_BORDER", 0);
define("CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_CELLPADDING", 3);
define("CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_CELLSPACING", 1);
define("CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_ALIGN", "center");

// v2GetCurrentTournamentGamesHTML() - CR3DCQuery.php
define("CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_WIDTH", "95%");
define("CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_BORDER", 0);
define("CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_CELLPADDING", 3);
define("CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_CELLSPACING", 1);
define("CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// chess_members.php
//////////////////////////////////////////////////////////////////////////////////

// v2ActiveTournamentManagement() - CR3DCQuery.php
define("CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_WIDTH", "100%");
define("CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_BORDER", 0);
define("CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_CELLPADDING", 3);
define("CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_CELLSPACING", 1);
define("CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_ALIGN", "center");
define("CFG_V2ACTIVETOURNAMENTMANAGEMENT_ROWX1_WIDTH", 100);

// GetOngoingGameCount() - CR3DCQuery.php
define("CFG_GETONGOINGGAMECOUNT_TABLE1_WIDTH", "100%");
define("CFG_GETONGOINGGAMECOUNT_TABLE1_BORDER", 0);
define("CFG_GETONGOINGGAMECOUNT_TABLE1_CELLPADDING", 3);
define("CFG_GETONGOINGGAMECOUNT_TABLE1_CELLSPACING", 1);
define("CFG_GETONGOINGGAMECOUNT_TABLE1_ALIGN", "center");

// GetMessageCount() - CR3DCQuery.php
define("CFG_GETMESSAGECOUNT_TABLE1_WIDTH", "100%");
define("CFG_GETMESSAGECOUNT_TABLE1_BORDER", 0);
define("CFG_GETMESSAGECOUNT_TABLE1_CELLPADDING", 3);
define("CFG_GETMESSAGECOUNT_TABLE1_CELLSPACING", 1);
define("CFG_GETMESSAGECOUNT_TABLE1_ALIGN", "center");

// GetServerMessages() - CServMsg.php
define("CFG_GETSERVERMESSAGES_TABLE1_WIDTH", "100%");
define("CFG_GETSERVERMESSAGES_TABLE1_BORDER", 0);
define("CFG_GETSERVERMESSAGES_TABLE1_CELLPADDING", 3);
define("CFG_GETSERVERMESSAGES_TABLE1_CELLSPACING", 1);
define("CFG_GETSERVERMESSAGES_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// chess_view_games.php
//////////////////////////////////////////////////////////////////////////////////

// GetActiveGamesList() - CR3DCQuery.php
define("CFG_GETACTIVEGAMESLIST_TABLE1_WIDTH", "450");
define("CFG_GETACTIVEGAMESLIST_TABLE1_BORDER", 0);
define("CFG_GETACTIVEGAMESLIST_TABLE1_CELLPADDING", 3);
define("CFG_GETACTIVEGAMESLIST_TABLE1_CELLSPACING", 1);
define("CFG_GETACTIVEGAMESLIST_TABLE1_ALIGN", "center");

// SearchGames() - CR3DCQuery.php 
define("CFG_SEARCHGAMES_TABLE1_WIDTH", "450");
define("CFG_SEARCHGAMES_TABLE1_BORDER", 0);
define("CFG_SEARCHGAMES_TABLE1_CELLPADDING", 3);
define("CFG_SEARCHGAMES_TABLE1_CELLSPACING", 1);
define("CFG_SEARCHGAMES_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// chess_find_player.php
//////////////////////////////////////////////////////////////////////////////////

// SearchPlayers() - CR3DCQuery.php
define("CFG_SEARCHPLAYERS_TABLE1_WIDTH", "400");
define("CFG_SEARCHPLAYERS_TABLE1_BORDER", 0);
define("CFG_SEARCHPLAYERS_TABLE1_CELLPADDING", 3);
define("CFG_SEARCHPLAYERS_TABLE1_CELLSPACING", 1);
define("CFG_SEARCHPLAYERS_TABLE1_ALIGN", "center");

// FindPlayersByPoints() - CR3DCQuery.php
define("CFG_FINDPLAYERSBYPOINTS_TABLE1_WIDTH", "400");
define("CFG_FINDPLAYERSBYPOINTS_TABLE1_BORDER", 0);
define("CFG_FINDPLAYERSBYPOINTS_TABLE1_CELLPADDING", 3);
define("CFG_FINDPLAYERSBYPOINTS_TABLE1_CELLSPACING", 1);
define("CFG_FINDPLAYERSBYPOINTS_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// chess_buddy_list.php
//////////////////////////////////////////////////////////////////////////////////

// GetBuddyList() - CBuddyList.php
define("CFG_GETBUDDYLIST_TABLE1_WIDTH", "450");
define("CFG_GETBUDDYLIST_TABLE1_BORDER", 0);
define("CFG_GETBUDDYLIST_TABLE1_CELLPADDING", 3);
define("CFG_GETBUDDYLIST_TABLE1_CELLSPACING", 1);
define("CFG_GETBUDDYLIST_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// chess_activities.php
//////////////////////////////////////////////////////////////////////////////////

// GetPersonalActivityStatsHTML() - CR3DCQuery.php
define("CFG_GETPERSONALACTIVITYSTATSHTML_TABLE1_WIDTH", "95%");
define("CFG_GETPERSONALACTIVITYSTATSHTML_TABLE1_BORDER", 0);
define("CFG_GETPERSONALACTIVITYSTATSHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETPERSONALACTIVITYSTATSHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETPERSONALACTIVITYSTATSHTML_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// chess_buy_credits.php
//////////////////////////////////////////////////////////////////////////////////

// GetPurchaseCreditHTMLForm() - CR3DCQuery.php
define("CFG_GETPURCHASECREDITHTMLFORM_TABLE1_WIDTH", "95%");
define("CFG_GETPURCHASECREDITHTMLFORM_TABLE1_BORDER", 0);
define("CFG_GETPURCHASECREDITHTMLFORM_TABLE1_CELLPADDING", 3);
define("CFG_GETPURCHASECREDITHTMLFORM_TABLE1_CELLSPACING", 1);
define("CFG_GETPURCHASECREDITHTMLFORM_TABLE1_ALIGN", "center");
define("CFG_GETPURCHASECREDITHTMLFORM_ROW1_WIDTH", "30%");

//////////////////////////////////////////////////////////////////////////////////
// chess_get_activities.php
//////////////////////////////////////////////////////////////////////////////////

// GetActivityListForPurchaseHTML() - CR3DCQuery.php
define("CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_WIDTH", "95%");
define("CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_BORDER", 0);
define("CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_ALIGN", "center");
define("CFG_GETACTIVITYLISTFORPURCHASEHTML_ROW1_WIDTH", "20%");

//////////////////////////////////////////////////////////////////////////////////
// chess_view_activities.php
//////////////////////////////////////////////////////////////////////////////////

// GetPersonalActivityListHTML() - CR3DCQuery.php
define("CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_WIDTH", "95%");
define("CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_BORDER", 0);
define("CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_ALIGN", "center");


/*********************************************************************************
* Admin Site Config
*
*/

//////////////////////////////////////////////////////////////////////////////////
// chess_accept_tournament_v2.php
//////////////////////////////////////////////////////////////////////////////////

// v2NewTournamentListHTML() - CR3DCQuery.php
define("CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_WIDTH", 538);
define("CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_BORDER", 0);
define("CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_CELLPADDING", 3);
define("CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_CELLSPACING", 1);
define("CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// admin_new_players.php
//////////////////////////////////////////////////////////////////////////////////

// GetNewPlayers() - CR3DCQuery.php
define("CFG_GETNEWPLAYERS_TABLE1_WIDTH", "95%");
define("CFG_GETNEWPLAYERS_TABLE1_BORDER", 0);
define("CFG_GETNEWPLAYERS_TABLE1_CELLPADDING", 0);
define("CFG_GETNEWPLAYERS_TABLE1_CELLSPACING", 0);
define("CFG_GETNEWPLAYERS_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// cfg_email_log.php
//////////////////////////////////////////////////////////////////////////////////

// GetEmailLogHTML() - CR3DCQuery.php
define("CFG_GETEMAILLOGHTML_TABLE1_WIDTH", "100%");
define("CFG_GETEMAILLOGHTML_TABLE1_BORDER", 0);
define("CFG_GETEMAILLOGHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETEMAILLOGHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETEMAILLOGHTML_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// create_activity.php
//////////////////////////////////////////////////////////////////////////////////

// GetAdminActivityListHTML() - CR3DCQuery.php
define("CFG_GETADMINACTIVITYLISTHTML_TABLE1_WIDTH", "95%");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE1_BORDER", 0);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE1_ALIGN", "center");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE1_ROW1_WIDTH", "25%");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE2_WIDTH", "95%");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE2_BORDER", 0);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE2_CELLPADDING", 3);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE2_CELLSPACING", 1);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE2_ALIGN", "center");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE2_ROW1_WIDTH", "25%");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE3_WIDTH", "95%");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE3_BORDER", 0);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE3_CELLPADDING", 3);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE3_CELLSPACING", 1);
define("CFG_GETADMINACTIVITYLISTHTML_TABLE3_ALIGN", "center");
define("CFG_GETADMINACTIVITYLISTHTML_TABLE3_ROW1_WIDTH", "25%");

//////////////////////////////////////////////////////////////////////////////////
// edit_activity.php
//////////////////////////////////////////////////////////////////////////////////

// GetActivityInfoByIDHTML() - CR3DCQuery.php
define("CFG_GETACTIVITYINFOBYIDHTML_TABLE1_WIDTH", "95%");
define("CFG_GETACTIVITYINFOBYIDHTML_TABLE1_BORDER", 0);
define("CFG_GETACTIVITYINFOBYIDHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETACTIVITYINFOBYIDHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETACTIVITYINFOBYIDHTML_TABLE1_ALIGN", "center");
define("CFG_GETACTIVITYINFOBYIDHTML_TABLE1_ROW1_WIDTH", "20%");

//////////////////////////////////////////////////////////////////////////////////
// admin_player_list.php
//////////////////////////////////////////////////////////////////////////////////

// ListAvailablePlayers2() - CR3DCQuery.php
define("CFG_LISTAVAILABLEPLAYERS2_TABLE1_WIDTH", 400);
define("CFG_LISTAVAILABLEPLAYERS2_TABLE1_BORDER", 0);
define("CFG_LISTAVAILABLEPLAYERS2_TABLE1_CELLPADDING", 3);
define("CFG_LISTAVAILABLEPLAYERS2_TABLE1_CELLSPACING", 1);
define("CFG_LISTAVAILABLEPLAYERS2_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// manage_new_bills.php
//////////////////////////////////////////////////////////////////////////////////

// GetOrders() - CBilling.php
define("CFG_GETORDERS_TABLE1_WIDTH", "95%");
define("CFG_GETORDERS_TABLE1_BORDER", 0);
define("CFG_GETORDERS_TABLE1_CELLPADDING", 3);
define("CFG_GETORDERS_TABLE1_CELLSPACING", 1);
define("CFG_GETORDERS_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// view_old_bills.php
//////////////////////////////////////////////////////////////////////////////////

// GetOldOrders() - CBilling.php
define("CFG_GETOLDORDERS_TABLE1_WIDTH", "95%");
define("CFG_GETOLDORDERS_TABLE1_BORDER", 0);
define("CFG_GETOLDORDERS_TABLE1_CELLPADDING", 3);
define("CFG_GETOLDORDERS_TABLE1_CELLSPACING", 1);
define("CFG_GETOLDORDERS_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// manage_multiredemption.php
//////////////////////////////////////////////////////////////////////////////////

// GetMultiUserRedemptionCodes() - CR3DCQuery.php
define("CFG_GETMULTIUSERREDEMPTIONCODES_TABLE1_WIDTH", "100%");
define("CFG_GETMULTIUSERREDEMPTIONCODES_TABLE1_BORDER", 0);

//////////////////////////////////////////////////////////////////////////////////
// cfg_player_credit_requests.php
//////////////////////////////////////////////////////////////////////////////////

// GetCreditRequestsAdminHTML - CR3DCQuery.php
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE1_WIDTH", "95%");
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE1_BORDER", 0);
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE1_CELLPADDING", 3);
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE1_CELLSPACING", 1);
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE1_ALIGN", "center");
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE2_WIDTH", "95%");
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE2_BORDER", 0);
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE2_CELLPADDING", 3);
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE2_CELLSPACING", 1);
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE2_ALIGN", "center");
define("CFG_GETCREDITREQUESTSADMINHTML_TABLE2_ROW1_WIDTH", "30%");

//////////////////////////////////////////////////////////////////////////////////
// manage_front_news.php
//////////////////////////////////////////////////////////////////////////////////

// GetFrontNewsAdmin() - CFrontNews.php
define("CFG_GETFRONTNEWSADMIN_TABLE1_WIDTH", "95%");
define("CFG_GETFRONTNEWSADMIN_TABLE1_BORDER", 0);
define("CFG_GETFRONTNEWSADMIN_TABLE1_CELLPADDING", 3);
define("CFG_GETFRONTNEWSADMIN_TABLE1_CELLSPACING", 1);
define("CFG_GETFRONTNEWSADMIN_TABLE1_ALIGN", "center");
define("CFG_GETFRONTNEWSADMIN_TABLE1_ROW1_WIDTH", "30%");

//////////////////////////////////////////////////////////////////////////////////
// manage_tipoftheday.php
//////////////////////////////////////////////////////////////////////////////////

// GetTips() - CTipOfTheDay.php
define("CFG_GETTIPS_TABLE1_WIDTH", "100%");
define("CFG_GETTIPS_TABLE1_BORDER", 0);
define("CFG_GETTIPS_TABLE1_CELLPADDING", 0);
define("CFG_GETTIPS_TABLE1_CELLSPACING", 0);
define("CFG_GETTIPS_TABLE1_ALIGN", "center");

//////////////////////////////////////////////////////////////////////////////////
// manage_servermsg.php
//////////////////////////////////////////////////////////////////////////////////

// GetMessages() - CServMsg.php
define("CFG_GETMESSAGES_TABLE1_WIDTH", "100%");
define("CFG_GETMESSAGES_TABLE1_BORDER", 0);
define("CFG_GETMESSAGES_TABLE1_CELLPADDING", 0);
define("CFG_GETMESSAGES_TABLE1_CELLSPACING", 0);
define("CFG_GETMESSAGES_TABLE1_ALIGN", "center");

/*********************************************************************************
* Javascript Popup Window Config
*
*/

// PopupHelpWin
define("CFG_POPUPHELPWIN_WIDTH", 500);
define("CFG_POPUPHELPWIN_HEIGHT", 400);

// PopupWindowRT
define("CFG_POPUPWINDOWRT_WIDTH", 580);
define("CFG_POPUPWINDOWRT_HEIGHT", 420);

// PopupWindow
define("CFG_POPUPWINDOW_WIDTH", 500);
define("CFG_POPUPWINDOW_HEIGHT", 400);

// PopupWindowTM
define("CFG_POPUPWINDOWTM_WIDTH", 910);
define("CFG_POPUPWINDOWTM_HEIGHT", 600);
define("CFG_POPUPWINDOWTM_MENU_SIZE", 18);

define("CFG_POPUPWINDOWTM_CHAT_ROWS", 16);
define("CFG_POPUPWINDOWTM_CHAT_COLS", 84);

// PlayerChat
define("CFG_PLAYERCHAT_WIDTH", 900);
define("CFG_PLAYERCHAT_HEIGHT", 470);
define("CFG_PLAYERCHAT_ROWS", 25);
define("CFG_PLAYERCHAT_COLS", 83);

// ViewLiveGame
define("CFG_VIEWLIVEGAME_WIDTH", 500);
define("CFG_VIEWLIVEGAME_HEIGHT", 523);

// ViewOldGame
define("CFG_VIEWOLDGAME_WIDTH", 500);
define("CFG_VIEWOLDGAME_HEIGHT", 523);

// PopupWindowMP
define("CFG_POPUPWINDOWMP_WIDTH", 910);
define("CFG_POPUPWINDOWMP_HEIGHT", 700);

// PopupWindowP
define("CFG_POPUPWINDOWP_WIDTH", 900);
define("CFG_POPUPWINDOWP_HEIGHT", 600);

// PopupPGNGame
define("CFG_POPUPPGNGAME_WIDTH", 610);
define("CFG_POPUPPGNGAME_HEIGHT", 600);

// PopupWindowTInfo
define("CFG_POPUPWINDOWTINFO_WIDTH", 600);
define("CFG_POPUPWINDOWTINFO_HEIGHT", 580);

// PopupWindowActivity

// addResource
define("CFG_POPUPWINDOWACTIVITY_ADDRESOURCE_WIDTH", 400);
define("CFG_POPUPWINDOWACTIVITY_ADDRESOURCE_HEIGHT", 210);

// CreatePage
define("CFG_POPUPWINDOWACTIVITY_CREATEPAGE_WIDTH", 600);
define("CFG_POPUPWINDOWACTIVITY_CREATEPAGE_HEIGHT", 590);

// Preview
define("CFG_POPUPWINDOWACTIVITY_PREVIEW_WIDTH", 600);
define("CFG_POPUPWINDOWACTIVITY_PREVIEW_HEIGHT", 505);

// PopupUserNameChange
define("CFG_POPUPUSERNAMECHANGE_WIDTH", 500);
define("CFG_POPUPUSERNAMECHANGE_HEIGHT", 400);

?>