<?php
//    Note: If an item occurs many times in different files, the value in the comment indicates how many times
//    the item appears. For example '// x3' indicates an item appears 3 times throughout the program.

//// File: \admin\admin_game_list.php
$lang['Manage Games'] = "G&eacute;rer parties";
$lang['Game ID'] = "ID partie";
$lang['Initiator'] = "Initiateur";
$lang['Start Time'] = "Heure d&eacute;but";
$lang['Status'] = "Statut";
$lang['Active'] = "En cours";
$lang['Completed'] = "Termin&eacute;";
$lang['Waiting to be accepted'] = "Attente acceptation";
$lang['Pending'] = "En attente";
$lang['Completion<br/>Status'] = "Etat<br/>avancement";
$lang['White Won'] = "Les blancs ont Gagné";
$lang['Black Won'] = "Les noirs ont Gagné";
$lang['Draw'] = "Pat";		// x2
$lang['Incomplete'] = "Non fini";
$lang['White'] = "Blanc";
$lang['Black'] = "Noir";
$lang['View Board'] = "Voir &eacute;chiquier";		// x2
$lang['Add'] = "Ajouter";		// x2
$lang['Edit'] = "Modifier";		// x2
$lang['Next Move'] = "Next Move";
$lang['Castle White Short'] = "Castle White Short";
$lang['Castle White Long'] = "Castle White Long";
$lang['Castle Black Short'] = "Castle Black Short";
$lang['Castle Black Long'] = "Castle Black Long";
$lang['Rate Game?'] = "Rate Game?";
$lang['Find'] = "Trouver";		// x4
$lang['Displaying {from} to {to} of {total} items'] = "Displaying {from} to {to} of {total} items";		// x2
$lang['Page'] = "Page";		// x2
$lang['of'] = "de";		// x2
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
$lang['All Players'] = "Tous les joueurs";
$lang['ID'] = "ID";
$lang['Username'] = "Username";
$lang['Email'] = "Email";
$lang['Password'] = "Password";
$lang['Signup Time'] = "Membre depuis";
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
$lang['No image has been uploaded. Please select an imag...24b3b3'] = 'Aucune image t&eacute;l&eacute;charg&eacute;e. Selecttionner une image.';
$lang['The file type is not allowed. Upload only .bmp, ....0830bc'] = 'Type non support&eacute;. Extension possibles .bmp, .gif, .jpg or .png files.';		// x2
$lang['Unable to work with image because it is too large...2059b4'] = 'Image trop lourde. Limite {size} bytes ou moins.';
$lang['The image file size is too large. It cannot be la...6d3d05'] = 'Image trop lourde. Limite {size} bytes ou moins.';
$lang['The file could not be moved to a temporary locati...708f95'] = 'Le fichier ne peut &ecirc;tre plac&eacute; sur le r&eacute;pertoire temporaire du serveur.';
$lang['Unable to create an image from uploaded file. Ens...ea7020'] = 'Impossible de cr&eacute;er nu image depuis le fichier t&eacute;l&eacute;charg&eacute;.';
$lang['Configuration - Manage Avatar'] = "Configuration - Gestion Avatar";

//// File: \chess_game_rt.php
$lang['Game'] = "Game";
$lang['Select piece to promote pawn to'] = "Selectionner la pi&egrave;ce pour promouvoir le pion";
$lang['You are in check!'] = "Vous &ecirc;tes &eacute;chec!";
$lang['You are in check mate'] = "Vous &ecirc;tes &eacute;chec et mat";
$lang['You have won!'] = "You avez vaincu!";
$lang['You have lost!'] = "You avez perdu";
$lang['Game is a draw'] = "Le jeu se termine en pat";
$lang['%name% has requested a draw. Do you want to accep...f98b08'] = '%name% a demand&eacute; le pat. Acceptez vous?';
$lang['Are you sure you want to request a draw?'] = "Confirmez vous demander le pat?";
$lang['You have requested a draw'] = "Vous avez demand&eacute; le pat";
$lang['Revoke Draw'] = "Annuler la demande de pat";
$lang['Accept Draw'] = "Accepter le pat";
$lang['Yes'] = "Oui";
$lang['No'] = "Non";
$lang['Accept'] = "Accepter";
$lang['Decline'] = "Refuser";
$lang['Are you sure you want to resign?'] = "Voulez vous vraiment abandonner?";
$lang['Unable to send move'] = "Le mouvement ne peut &ecirc;tre envoy&eacute;";
$lang['Unable to query for a new game state update'] = "Impossible de requ&ecirc;ter le statut de la partie";
$lang['Days Remaining: {d}'] = "Jours restants: {d}";		// x2
$lang['Time Remaining: {h}:{m}'] = "Temps restant: {h}:{m}";		// x2
$lang['Time Remaining: {m}:{s}'] = "Temps restant: {m}:{s}";		// x2
$lang['Game has timed out'] = "Le temps de la partie est &eacute;coul&eacute;";		// x2

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
$lang['Save'] = "Enregistrer";		// x2
$lang['Back To Main Menu'] = "Retour au menu principal";
$lang['FAQ was updated'] = "FAQ mise &agrave; jour";

//// File: \includes\cells\cell_admin_game_list.php
$lang['Encountered Validation Errors:'] = "Erreurs de validation:";		// x2
$lang['field'] = "champ";		// x2
$lang['A value is required'] = "Une valeur est requise";		// x2
$lang['A positive integer value is expected.'] = "Un entier positif est attendu";		// x2
$lang['A integer value is expected.'] = "Un entier est attendu.";		// x2
$lang['A positive number is expected.'] = "Un nombre positif est attendu.";		// x2
$lang['A number is expected.'] = "Un nombre est attendu.";		// x2
$lang['The value contains invalid characters or is in th...c7c85c'] = 'La valeur contient des caract&egrave;res non valides.';		// x2
$lang['The value must be unique. Another record already ...4b0da6'] = 'La valeur doit &ecirc;tre unique. Un enregistrement existe avec cette valeur.';		// x2
$lang['The value is too long.'] = "La valeur est trop longue.";		// x2
$lang['The value is invalid. Please use one from this li...c711a3'] = 'La valeur est invalide. Utilisez une valeur de la liste : ';		// x2
$lang['The value does not meet a constraint set in the d...22354c'] = 'La valeur ne correspond pas aux contraintes impos&eacute;es par la base de donn&eacutees';		// x2

//// File: \includes\cells\cell_admin_manage_players.php
$lang['Manage Current Players'] = "G&eacute;rer les joueurs";
$lang['Disable/Enable Players'] = "D&eacute;sactiver ou activer des joueurs";

//// File: \includes\cells\cell_cfg_avatar.php
$lang["Manage Avatar"] = "G&eacute;rer votre avatar";
$lang['This is your current avatar:'] = "Avatar actuel :";
$lang['Upload new picture'] = "Nouvelle image";
$lang['Select from gallery'] = "S&eacute;lectionner depuis la galerie";
$lang['Select an image to use as your avatar:'] = "S&eacute;lectionner une image pour utiliser comme avatar :";
$lang['Upload'] = "Télécharger";
$lang['Uploading, please wait...'] = "T&eacute;l&eacute;chargement, veuillez patienter...";
$lang["You can now crop the image if you wish to. Simply...9b9390"] = "Vous pouvez recadrer cette image par un click sur l image la s&eacute;lection du rectangle. Sur un mobile, la r&eacute;gion est s&eacute;lectionn&eacute;e par 2 taps. puis cliquer sur enregistrer.";
$lang['Original image:'] = "Image originale :";
$lang['Image Preview:'] = "Pr&eacute;visualisation :";
$lang["Back To Main Page"] = "Retour page principale";		// x2
$lang["Back To Configuration"] = "Retour page configuration";

//// File: \includes\cells\cell_cfg_avatar_settings.php
$lang["Manage Avatar Settings"] = "Gestion options avatars";
$lang["Please select your avatar setting below:"] = "S&eacute;lectionner les options avatars ci dessous:";
$lang["Allow uploads"] = "Authoriser les t&eacute;l&eacute;chargements";
$lang["Save"] = "Sauvegarder";
$lang["Back To Server Management"] = "Retour gestion serveur";

//// File: \includes\cells\cell_chess_member.php
$lang['Game Time'] = "Temps par coup";
$lang['Advanced settings'] = "Avanc&eacute;";
$lang['Time Control 1'] = "Contr&ocirc;le temps 1";
$lang['moves adds'] = " mouvements ajoute ";		// x2
$lang['minutes'] = "minutes";		// x2
$lang['Time Control 2'] = "Contr&ocirc;le temps 2";
$lang['Cancel'] = "Annuler";
$lang['Recently Finished Games'] = "Parties r&eacute;centes termin&eacute;es";

//// File: \includes\cells\cell_game_rt.php
$lang['Back to Player Home'] = "Retour accueil";
$lang['View Replay'] = "Revoir";
$lang['Challenge to Rematch'] = "Demander la revanche";
$lang['White has captured:'] = "Les blancs ont captur&eacute;:";
$lang['Black has captured:'] = "Les noirs ont captur&eacute;:";
$lang['Send Message'] = "Envoyer message";
$lang['Resign'] = "Abandonner";
?>
