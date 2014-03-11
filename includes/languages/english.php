<?php
//    Note: If an item occurs many times in different files, the value in the comment indicates how many times
//    the item appears. For example '// x3' indicates an item appears 3 times throughout the program.

//// File: \admin\admin_game_list.php
$lang['Manage Games'] = "Manage Games";
$lang['Game ID'] = "Game ID";
$lang['Initiator'] = "Initiator";
$lang['Start Time'] = "Start Time";
$lang['Status'] = "Status";
$lang['Active'] = "Active";
$lang['Completed'] = "Completed";
$lang['Waiting to be accepted'] = "Waiting to be accepted";
$lang['Pending'] = "Pending";
$lang['Completion<br/>Status'] = "Completion<br/>Status";
$lang['White Won'] = "White Won";
$lang['Black Won'] = "Black Won";
$lang['Draw'] = "Draw";		// x2
$lang['Incomplete'] = "Incomplete";
$lang['White'] = "White";
$lang['Black'] = "Black";
$lang['View Board'] = "View Board";		// x2
$lang['Add'] = "Add";		// x2
$lang['Edit'] = "Edit";		// x2
$lang['Next Move'] = "Next Move";
$lang['Castle White Short'] = "Castle White Short";
$lang['Castle White Long'] = "Castle White Long";
$lang['Castle Black Short'] = "Castle Black Short";
$lang['Castle Black Long'] = "Castle Black Long";
$lang['Rate Game?'] = "Rate Game?";
$lang['Find'] = "Find";		// x4
$lang['Displaying {from} to {to} of {total} items'] = "Displaying {from} to {to} of {total} items";		// x2
$lang['Page'] = "Page";		// x2
$lang['of'] = "of";		// x2
$lang['Processing, please wait ...'] = "Processing, please wait ...";		// x2
$lang['No items'] = "No items";		// x2
$lang['Connection Error'] = "Connection Error";		// x2
$lang['Same person cannot play both sides'] = "Same person cannot play both sides";
$lang['Unable to create game'] = "Unable to create game";
$lang['Cannot delete game. No game id provided.'] = "Cannot delete game. No game id provided.";
$lang["Query to delete c4m_tournamentgames from 'game' t...343a69"] = "Query to delete c4m_tournamentgames from 'game' table failed.";
$lang["Query to delete game reference from 'c4m_tourname...653f4a"] = "Query to delete game reference from 'c4m_tournamentgames' table failed.";
$lang["Query to delete game reference from 'cfm_game_opt...58dbb1"] = "Query to delete game reference from 'cfm_game_options' table failed. ";
$lang["Query to delete game references from 'moves_histo...be1891"] = "Query to delete game references from 'moves_history' table failed.";
$lang["Query to delete game reference from 'cfm_gamesrea...dddc7e"] = "Query to delete game reference from 'cfm_gamesrealtime' table failed.";
$lang["Query to delete game reference from 'c4m_gamechat...88116c"] = "Query to delete game reference from 'c4m_gamechat' table failed.";
$lang["Query to delete game reference from 'c4m_gamedraw...22eef7"] = "Query to delete game reference from 'c4m_gamedraws' table failed.";
$lang["Query to delete game reference from 'c4m_newgameo...702c7b"] = "Query to delete game reference from 'c4m_newgameotherfen' table failed.";
$lang['Unable to delete game or related data.'] = "Unable to delete game or related data.";
$lang['Administration Page - All Games'] = "Administration Page - All Games";

//// File: \admin\admin_player_list2.php
$lang['All Players'] = "All Players";
$lang['ID'] = "ID";
$lang['Username'] = "Username";
$lang['Email'] = "Email";
$lang['Password'] = "Password";
$lang['Signup Time'] = "Signup Time";
$lang['Administration Page - Players'] = "Administration Page - Players";		// x2

//// File: \admin\cfg_avatar_settings.php
$lang["Administration Page - Avatar Settings"] = "Administration Page - Avatar Settings";

//// File: \admin\edit_faq.php
$lang['Edit FAQ'] = "Edit FAQ";		// x4

//// File: \admin\manage_news_data.php
$lang['Administration Page - News/Misc Data'] = "Administration Page - News/Misc Data";

//// File: \admin\tournament_game_add.php
$lang['Time Controls'] = "Time Controls";
$lang['Error inserting record into timed_games table'] = 'Error inserting record into timed_games table';
$lang['Value must be a number.'] = "Value must be a number.";
$lang['Value must be a number larger than 0.'] = "Value must be a number larger than 0.";

//// File: \chess_cfg_avatar.php
$lang['No image has been uploaded. Please select an imag...24b3b3'] = 'No image has been uploaded. Please select an image.';
$lang['The file type is not allowed. Upload only .bmp, ....0830bc'] = 'The file type is not allowed. Upload only .bmp, .gif, .jpg or .png files.';		// x2
$lang['Unable to work with image because it is too large...2059b4'] = 'Unable to work with image because it is too large. It has to be {size} bytes or less.';
$lang['The image file size is too large. It cannot be la...6d3d05'] = 'The image file size is too large. It cannot be larger than {size} bytes.';
$lang['The file could not be moved to a temporary locati...708f95'] = 'The file could not be moved to a temporary location on the server.';
$lang['Unable to create an image from uploaded file. Ens...ea7020'] = 'Unable to create an image from uploaded file. Ensure the file format is supported.';
$lang['Configuration - Manage Avatar'] = "Configuration - Manage Avatar";

//// File: \chess_game_rt.php
$lang['Game'] = "Game";
$lang['Select piece to promote pawn to'] = "Select piece to promote pawn to";
$lang['You are in check!'] = "You are in check!";
$lang['You are in check mate'] = "You are in check mate";
$lang['You have won!'] = "You have won!";
$lang['You have lost!'] = "You have lost!";
$lang['Game is a draw'] = "Game is a draw";
$lang['%name% has requested a draw. Do you want to accep...f98b08'] = '%name% has requested a draw. Do you want to accept it?';
$lang['Are you sure you want to request a draw?'] = "Are you sure you want to request a draw?";
$lang['You have requested a draw'] = "You have requested a draw";
$lang['Revoke Draw'] = "Revoke Draw";
$lang['Accept Draw'] = "Accept Draw";
$lang['Yes'] = "Yes";
$lang['No'] = "No";
$lang['Accept'] = "Accept";
$lang['Decline'] = "Decline";
$lang['Are you sure you want to resign?'] = "Are you sure you want to resign?";
$lang['Unable to send move'] = "Unable to send move";
$lang['Unable to query for a new game state update'] = "Unable to query for a new game state update";
$lang['Days Remaining: {d}'] = "Days Remaining: {d}";		// x2
$lang['Time Remaining: {h}:{m}'] = "Time Remaining: {h}:{m}";		// x2
$lang['Time Remaining: {m}:{s}'] = "Time Remaining: {m}:{s}";		// x2
$lang['Game has timed out'] = "Game has timed out";		// x2

//// File: \chess_register.php
$lang['One or more required fields was left blank!'] = "One or more required fields was left blank!";
$lang['That userid is taken, please reregister with a di...1c6b55'] = 'That userid is taken, please reregister with a different id!';
$lang['Your account has been created. An initial passwor...21599e'] = 'Your account has been created. An initial password will be emailed to the address you specified.';
$lang['Your account has been created. Your account will ...f5a017'] = 'Your account has been created. Your account will be enabled when the administrator approves it.';
$lang['Invalid characters detected.'] = "Invalid characters detected.";
$lang['Name must be less than or equal to 11 characters.'] = "Name must be less than or equal to 11 characters.";
$lang['Return to the login page'] = "Return to the login page";

//// File: \includes\cells\cell_admin_cfg_srv_game_opts.php
$lang['TIMING MODE'] = "TIMING MODE";
$lang['Time Game'] = "Time Game";
$lang['Time Players'] = "Time Players";

//// File: \includes\cells\cell_admin_edit_faq.php
$lang['Save'] = "Save";		// x2
$lang['Back To Main Menu'] = "Back To Main Menu";
$lang['FAQ was updated'] = "FAQ was updated";

//// File: \includes\cells\cell_admin_game_list.php
$lang['Encountered Validation Errors:'] = "Encountered Validation Errors:";		// x2
$lang['field'] = "field";		// x2
$lang['A value is required'] = "A value is required";		// x2
$lang['A positive integer value is expected.'] = "A positive integer value is expected.";		// x2
$lang['A integer value is expected.'] = "A integer value is expected.";		// x2
$lang['A positive number is expected.'] = "A positive number is expected.";		// x2
$lang['A number is expected.'] = "A number is expected.";		// x2
$lang['The value contains invalid characters or is in th...c7c85c'] = 'The value contains invalid characters or is in the wrong format';		// x2
$lang['The value must be unique. Another record already ...4b0da6'] = 'The value must be unique. Another record already uses this value';		// x2
$lang['The value is too long.'] = "The value is too long.";		// x2
$lang['The value is invalid. Please use one from this li...c711a3'] = 'The value is invalid. Please use one from this list: ';		// x2
$lang['The value does not meet a constraint set in the d...22354c'] = 'The value does not meet a constraint set in the database';		// x2

//// File: \includes\cells\cell_admin_manage_players.php
$lang['Manage Current Players'] = "Manage Current Players";
$lang['Disable/Enable Players'] = "Disable/Enable Players";

//// File: \includes\cells\cell_cfg_avatar.php
$lang["Manage Avatar"] = "Manage Avatar";
$lang['This is your current avatar:'] = "This is your current avatar:";
$lang['Upload new picture'] = "Upload new picture";
$lang['Select from gallery'] = "Select from gallery";
$lang['Select an image to use as your avatar:'] = "Select an image to use as your avatar:";
$lang['Upload'] = "Upload";
$lang['Uploading, please wait...'] = "Uploading, please wait...";
$lang["You can now crop the image if you wish to. Simply...9b9390"] = "You can now crop the image if you wish to. Simply click on the image and drag the cursor to draw a rectangle over the part of the image you wish to use. On touch devices, the region is selected by tapping twice (one tap to select one corner of the region and another to select the opposite corner). When done click the 'Save' button under the preview image.";
$lang['Original image:'] = "Original image:";
$lang['Image Preview:'] = "Image Preview:";
$lang["Back To Main Page"] = "Back To Main Page";		// x2
$lang["Back To Configuration"] = "Back To Configuration";

//// File: \includes\cells\cell_cfg_avatar_settings.php
$lang["Manage Avatar Settings"] = "Manage Avatar Settings";
$lang["Please select your avatar setting below:"] = "Please select your avatar setting below:";
$lang["Allow uploads"] = "Allow uploads";
$lang["Save"] = "Save";
$lang["Back To Server Management"] = "Back To Server Management";

//// File: \includes\cells\cell_chess_member.php
$lang['Game Time'] = "Game Time";
$lang['Advanced settings'] = "Advanced settings";
$lang['Time Control 1'] = "Time Control 1";
$lang['moves adds'] = "moves adds";		// x2
$lang['minutes'] = "minutes";		// x2
$lang['Time Control 2'] = "Time Control 2";
$lang['Cancel'] = "Cancel";
$lang['Recently Finished Games'] = "Recently Finished Games";

//// File: \includes\cells\cell_game_rt.php
$lang['Back to Player Home'] = "Back to Player Home";
$lang['View Replay'] = "View Replay";
$lang['Challenge to Rematch'] = "Challenge to Rematch";
$lang['White has captured:'] = "White has captured:";
$lang['Black has captured:'] = "Black has captured:";
$lang['Send Message'] = "Send Message";
$lang['Resign'] = "Resign";
?>