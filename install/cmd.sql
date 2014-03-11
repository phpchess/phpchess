SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `phpchess`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_sessions`
--

DROP TABLE IF EXISTS `active_sessions`;
CREATE TABLE IF NOT EXISTS `active_sessions` (
  `session` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `player_id` int(11) NOT NULL DEFAULT '0',
  `session_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE IF NOT EXISTS `activities` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_description` text COLLATE utf8_unicode_ci NOT NULL,
  `o_createdby` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_type` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `o_credit` int(11) NOT NULL,
  `o_enabled` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_config`
--

DROP TABLE IF EXISTS `activity_config`;
CREATE TABLE IF NOT EXISTS `activity_config` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_free` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `o_credit` int(11) NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_pages`
--

DROP TABLE IF EXISTS `activity_pages`;
CREATE TABLE IF NOT EXISTS `activity_pages` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_activitiesid` int(11) NOT NULL,
  `o_content1` text COLLATE utf8_unicode_ci NOT NULL,
  `o_content2` text COLLATE utf8_unicode_ci NOT NULL,
  `o_solution` text COLLATE utf8_unicode_ci NOT NULL,
  `o_type` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_resources`
--

DROP TABLE IF EXISTS `activity_resources`;
CREATE TABLE IF NOT EXISTS `activity_resources` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_activitiesid` int(11) NOT NULL,
  `o_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_data` text COLLATE utf8_unicode_ci NOT NULL,
  `o_type` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_avatar_settings`
--

DROP TABLE IF EXISTS `admin_avatar_settings`;
CREATE TABLE IF NOT EXISTS `admin_avatar_settings` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_setting` int(11) NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_chessboard_colors`
--

DROP TABLE IF EXISTS `admin_chessboard_colors`;
CREATE TABLE IF NOT EXISTS `admin_chessboard_colors` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_dcolor` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `o_lcolor` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_game_options`
--

DROP TABLE IF EXISTS `admin_game_options`;
CREATE TABLE IF NOT EXISTS `admin_game_options` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_snail` float NOT NULL DEFAULT '30',
  `o_slow` float NOT NULL DEFAULT '20',
  `o_normal` float NOT NULL DEFAULT '10',
  `o_short` float NOT NULL DEFAULT '2',
  `o_blitz` float NOT NULL DEFAULT '0.01',
  `timing_mode` int(11) DEFAULT NULL,
  PRIMARY KEY (`o_id`),
  KEY `o_normal` (`o_normal`,`o_slow`) USING BTREE
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_player_credits`
--

DROP TABLE IF EXISTS `admin_player_credits`;
CREATE TABLE IF NOT EXISTS `admin_player_credits` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_credits` int(11) NOT NULL,
  `o_exchangerate` decimal(5,2) NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_player_credits_request`
--

DROP TABLE IF EXISTS `admin_player_credits_request`;
CREATE TABLE IF NOT EXISTS `admin_player_credits_request` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_playerid` int(11) NOT NULL,
  `o_userid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_credits` int(11) NOT NULL,
  `o_exchangerate` decimal(5,2) NOT NULL,
  `o_totalamount` decimal(5,2) NOT NULL,
  `o_status` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_admin`
--

DROP TABLE IF EXISTS `c4m_admin`;
CREATE TABLE IF NOT EXISTS `c4m_admin` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_username` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `a_password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_hashed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`a_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_autoaccepttournament`
--

DROP TABLE IF EXISTS `c4m_autoaccepttournament`;
CREATE TABLE IF NOT EXISTS `c4m_autoaccepttournament` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_requiresreg` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`a_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_avatars`
--

DROP TABLE IF EXISTS `c4m_avatars`;
CREATE TABLE IF NOT EXISTS `c4m_avatars` (
  `a_playerid` int(11) NOT NULL DEFAULT '0',
  `a_imgname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `a_datechanges` datetime DEFAULT NULL,
  PRIMARY KEY (`a_playerid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_buddylist`
--

DROP TABLE IF EXISTS `c4m_buddylist`;
CREATE TABLE IF NOT EXISTS `c4m_buddylist` (
  `bl_id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL DEFAULT '0',
  `buddy_id` int(11) NOT NULL DEFAULT '0',
  `bl_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`bl_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_chessboardcolors`
--

DROP TABLE IF EXISTS `c4m_chessboardcolors`;
CREATE TABLE IF NOT EXISTS `c4m_chessboardcolors` (
  `cc_id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL DEFAULT '0',
  `cc_dcolor` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cc_lcolor` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cc_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_commandconfig`
--

DROP TABLE IF EXISTS `c4m_commandconfig`;
CREATE TABLE IF NOT EXISTS `c4m_commandconfig` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_userlimit` int(11) NOT NULL DEFAULT '0',
  `o_enabletournament` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_enableplayerimport` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_emailmessageconfig`
--

DROP TABLE IF EXISTS `c4m_emailmessageconfig`;
CREATE TABLE IF NOT EXISTS `c4m_emailmessageconfig` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_regover` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_frontnews`
--

DROP TABLE IF EXISTS `c4m_frontnews`;
CREATE TABLE IF NOT EXISTS `c4m_frontnews` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT,
  `f_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `f_msg` text COLLATE utf8_unicode_ci NOT NULL,
  `f_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`f_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_gamechat`
--

DROP TABLE IF EXISTS `c4m_gamechat`;
CREATE TABLE IF NOT EXISTS `c4m_gamechat` (
  `tgc_id` int(11) NOT NULL AUTO_INCREMENT,
  `tgc_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tgc_message` text COLLATE utf8_unicode_ci NOT NULL,
  `tgc_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tgc_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_gamedraws`
--

DROP TABLE IF EXISTS `c4m_gamedraws`;
CREATE TABLE IF NOT EXISTS `c4m_gamedraws` (
  `tm_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tm_b` int(11) DEFAULT NULL,
  `tm_w` int(11) DEFAULT NULL,
  PRIMARY KEY (`tm_gameid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_gamerealtime`
--

DROP TABLE IF EXISTS `c4m_gamerealtime`;
CREATE TABLE IF NOT EXISTS `c4m_gamerealtime` (
  `gr_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gr_b` int(11) DEFAULT NULL,
  `gr_w` int(11) DEFAULT NULL,
  PRIMARY KEY (`gr_gameid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_invalid_players`
--

DROP TABLE IF EXISTS `c4m_invalid_players`;
CREATE TABLE IF NOT EXISTS `c4m_invalid_players` (
  `player_id` int(11) NOT NULL,
  `olduserid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `newuserid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `datechanged` datetime NOT NULL,
  PRIMARY KEY (`player_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_mainlink`
--

DROP TABLE IF EXISTS `c4m_mainlink`;
CREATE TABLE IF NOT EXISTS `c4m_mainlink` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `a_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`a_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_msginbox`
--

DROP TABLE IF EXISTS `c4m_msginbox`;
CREATE TABLE IF NOT EXISTS `c4m_msginbox` (
  `inbox_id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL DEFAULT '0',
  `message` blob,
  `msg_posted` int(11) DEFAULT NULL,
  PRIMARY KEY (`inbox_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_msgsaved`
--

DROP TABLE IF EXISTS `c4m_msgsaved`;
CREATE TABLE IF NOT EXISTS `c4m_msgsaved` (
  `saved_id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL DEFAULT '0',
  `message` blob,
  `msg_posted` int(11) DEFAULT NULL,
  PRIMARY KEY (`saved_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_multiuserredemptioncode`
--

DROP TABLE IF EXISTS `c4m_multiuserredemptioncode`;
CREATE TABLE IF NOT EXISTS `c4m_multiuserredemptioncode` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_redemptioncode` text COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_newgameotherfen`
--

DROP TABLE IF EXISTS `c4m_newgameotherfen`;
CREATE TABLE IF NOT EXISTS `c4m_newgameotherfen` (
  `gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fen` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`gameid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_notification`
--

DROP TABLE IF EXISTS `c4m_notification`;
CREATE TABLE IF NOT EXISTS `c4m_notification` (
  `p_playerid` int(11) NOT NULL DEFAULT '0',
  `p_move` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `p_challange` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`p_playerid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_paypal`
--

DROP TABLE IF EXISTS `c4m_paypal`;
CREATE TABLE IF NOT EXISTS `c4m_paypal` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_requirespayment` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`a_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_paypalaccount`
--

DROP TABLE IF EXISTS `c4m_paypalaccount`;
CREATE TABLE IF NOT EXISTS `c4m_paypalaccount` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `p_currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `p_monthlycharge` decimal(5,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`p_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_personalinfo`
--

DROP TABLE IF EXISTS `c4m_personalinfo`;
CREATE TABLE IF NOT EXISTS `c4m_personalinfo` (
  `p_playerid` int(11) NOT NULL DEFAULT '0',
  `p_fullname` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `p_location` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `p_age` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `p_selfrating` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `p_commentmotto` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `p_favouritechessplayer` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`p_playerid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_playerorders`
--

DROP TABLE IF EXISTS `c4m_playerorders`;
CREATE TABLE IF NOT EXISTS `c4m_playerorders` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_firstname` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_lastname` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_address` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_citytown` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_country` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_provincestatearea` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_postalcode` varchar(9) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_email` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_phonea` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_phoneb` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_phonec` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_redemptioncode` text COLLATE utf8_unicode_ci,
  `o_dateoforder` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `o_paymentterm` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `o_datepaid` datetime DEFAULT NULL,
  `o_datedue` datetime DEFAULT NULL,
  `o_orderstatus` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_servermessage`
--

DROP TABLE IF EXISTS `c4m_servermessage`;
CREATE TABLE IF NOT EXISTS `c4m_servermessage` (
  `sm_id` int(11) NOT NULL AUTO_INCREMENT,
  `sm_msg` text COLLATE utf8_unicode_ci NOT NULL,
  `sm_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`sm_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_skins`
--

DROP TABLE IF EXISTS `c4m_skins`;
CREATE TABLE IF NOT EXISTS `c4m_skins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_stringtable`
--

DROP TABLE IF EXISTS `c4m_stringtable`;
CREATE TABLE IF NOT EXISTS `c4m_stringtable` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_text` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tipoftheday`
--

DROP TABLE IF EXISTS `c4m_tipoftheday`;
CREATE TABLE IF NOT EXISTS `c4m_tipoftheday` (
  `tip_id` int(11) NOT NULL AUTO_INCREMENT,
  `tip_tiptext` text COLLATE utf8_unicode_ci NOT NULL,
  `tip_dateadded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tip_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournament`
--

DROP TABLE IF EXISTS `c4m_tournament`;
CREATE TABLE IF NOT EXISTS `c4m_tournament` (
  `t_id` int(11) NOT NULL AUTO_INCREMENT,
  `t_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `t_type` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `t_playernum` int(11) DEFAULT NULL,
  `t_cutoffdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `t_startdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `t_comment` text COLLATE utf8_unicode_ci,
  `t_status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`t_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentgamechat`
--

DROP TABLE IF EXISTS `c4m_tournamentgamechat`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentgamechat` (
  `tgc_id` int(11) NOT NULL AUTO_INCREMENT,
  `tgc_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tgc_message` text COLLATE utf8_unicode_ci NOT NULL,
  `tgc_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tgc_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentgames`
--

DROP TABLE IF EXISTS `c4m_tournamentgames`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentgames` (
  `tg_id` int(11) NOT NULL AUTO_INCREMENT,
  `tg_tmid` int(11) NOT NULL DEFAULT '0',
  `tg_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tg_playerid` int(11) NOT NULL DEFAULT '0',
  `tg_otherplayerid` int(11) NOT NULL DEFAULT '0',
  `tg_playerloggedin` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tg_otherplayerloggedin` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tg_status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`tg_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentmatches`
--

DROP TABLE IF EXISTS `c4m_tournamentmatches`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentmatches` (
  `tm_id` int(11) NOT NULL AUTO_INCREMENT,
  `tm_tournamentid` int(11) NOT NULL DEFAULT '0',
  `tm_status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tm_starttime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tm_endtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`tm_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentplayerpoints`
--

DROP TABLE IF EXISTS `c4m_tournamentplayerpoints`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentplayerpoints` (
  `tpp_id` int(11) NOT NULL AUTO_INCREMENT,
  `tpp_playerid` int(11) NOT NULL DEFAULT '0',
  `tpp_tournamentid` int(11) NOT NULL DEFAULT '0',
  `tpp_wins` int(11) NOT NULL DEFAULT '0',
  `tpp_loss` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tpp_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentplayers`
--

DROP TABLE IF EXISTS `c4m_tournamentplayers`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentplayers` (
  `tp_id` int(11) NOT NULL AUTO_INCREMENT,
  `tp_tournamentid` int(11) NOT NULL DEFAULT '0',
  `tp_playerid` int(11) NOT NULL DEFAULT '0',
  `tp_status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`tp_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentplayervsplayer`
--

DROP TABLE IF EXISTS `c4m_tournamentplayervsplayer`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentplayervsplayer` (
  `tpvp_id` int(11) NOT NULL AUTO_INCREMENT,
  `tpvp_tmid` int(11) NOT NULL DEFAULT '0',
  `tpvp_playerid` int(11) NOT NULL DEFAULT '0',
  `tpvp_otherplayerid` int(11) NOT NULL DEFAULT '0',
  `tpvp_status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`tpvp_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentteampoints`
--

DROP TABLE IF EXISTS `c4m_tournamentteampoints`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentteampoints` (
  `ttp_id` int(11) NOT NULL AUTO_INCREMENT,
  `ttp_teamid` int(11) NOT NULL DEFAULT '0',
  `ttp_tournamentid` int(11) NOT NULL DEFAULT '0',
  `ttp_wins` int(11) NOT NULL DEFAULT '0',
  `ttp_loss` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ttp_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentteams`
--

DROP TABLE IF EXISTS `c4m_tournamentteams`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentteams` (
  `tt_id` int(11) NOT NULL AUTO_INCREMENT,
  `tt_teamid` int(11) NOT NULL DEFAULT '0',
  `tt_tournamentid` int(11) NOT NULL DEFAULT '0',
  `tt_playerid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tt_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentteams_temp`
--

DROP TABLE IF EXISTS `c4m_tournamentteams_temp`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentteams_temp` (
  `tt_id` int(11) NOT NULL AUTO_INCREMENT,
  `tt_teamid` int(11) NOT NULL DEFAULT '0',
  `tt_tournamentid` int(11) NOT NULL DEFAULT '0',
  `tt_playerid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tt_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_tournamentteamvsteam`
--

DROP TABLE IF EXISTS `c4m_tournamentteamvsteam`;
CREATE TABLE IF NOT EXISTS `c4m_tournamentteamvsteam` (
  `ttvt_id` int(11) NOT NULL AUTO_INCREMENT,
  `ttvt_tmid` int(11) NOT NULL DEFAULT '0',
  `ttvt_teamid` int(11) NOT NULL DEFAULT '0',
  `ttvt_otherteamid` int(11) NOT NULL DEFAULT '0',
  `ttvt_status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ttvt_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `c4m_userregistration`
--

DROP TABLE IF EXISTS `c4m_userregistration`;
CREATE TABLE IF NOT EXISTS `c4m_userregistration` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_requiresreg` char(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`a_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cfg_cron_job`
--

DROP TABLE IF EXISTS `cfg_cron_job`;
CREATE TABLE IF NOT EXISTS `cfg_cron_job` (
  `o_cjid` int(11) NOT NULL AUTO_INCREMENT,
  `o_setting` int(11) NOT NULL,
  PRIMARY KEY (`o_cjid`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cfg_player_chat`
--

DROP TABLE IF EXISTS `cfg_player_chat`;
CREATE TABLE IF NOT EXISTS `cfg_player_chat` (
  `o_pcid` int(11) NOT NULL AUTO_INCREMENT,
  `o_setting` int(11) NOT NULL,
  PRIMARY KEY (`o_pcid`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cfm_creategamefen`
--

DROP TABLE IF EXISTS `cfm_creategamefen`;
CREATE TABLE IF NOT EXISTS `cfm_creategamefen` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_fen` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cfm_creategamefen_moves`
--

DROP TABLE IF EXISTS `cfm_creategamefen_moves`;
CREATE TABLE IF NOT EXISTS `cfm_creategamefen_moves` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_cgfid` int(11) NOT NULL,
  `o_move` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cfm_gamesrealtime`
--

DROP TABLE IF EXISTS `cfm_gamesrealtime`;
CREATE TABLE IF NOT EXISTS `cfm_gamesrealtime` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `datecreated` datetime NOT NULL,
  PRIMARY KEY (`id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cfm_game_options`
--

DROP TABLE IF EXISTS `cfm_game_options`;
CREATE TABLE IF NOT EXISTS `cfm_game_options` (
  `o_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `o_rating` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `o_timetype` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `time_mode` int(11) DEFAULT NULL,
  PRIMARY KEY (`o_gameid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cfm_point_caching`
--

DROP TABLE IF EXISTS `cfm_point_caching`;
CREATE TABLE IF NOT EXISTS `cfm_point_caching` (
  `player_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  PRIMARY KEY (`player_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chess_boardstyle`
--

DROP TABLE IF EXISTS `chess_boardstyle`;
CREATE TABLE IF NOT EXISTS `chess_boardstyle` (
  `id` int(11) NOT NULL,
  `style` int(11) NOT NULL,
  PRIMARY KEY (`id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chess_board_type`
--

DROP TABLE IF EXISTS `chess_board_type`;
CREATE TABLE IF NOT EXISTS `chess_board_type` (
  `o_playerid` int(11) NOT NULL,
  `o_isdragdrop` int(11) NOT NULL,
  PRIMARY KEY (`o_playerid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chess_club`
--

DROP TABLE IF EXISTS `chess_club`;
CREATE TABLE IF NOT EXISTS `chess_club` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_clubname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chess_club_members`
--

DROP TABLE IF EXISTS `chess_club_members`;
CREATE TABLE IF NOT EXISTS `chess_club_members` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_chessclubid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  `o_owner` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `o_active` char(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chess_club_page`
--

DROP TABLE IF EXISTS `chess_club_page`;
CREATE TABLE IF NOT EXISTS `chess_club_page` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_chessclubid` int(11) NOT NULL,
  `o_pagehtml` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chess_point_value`
--

DROP TABLE IF EXISTS `chess_point_value`;
CREATE TABLE IF NOT EXISTS `chess_point_value` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_points` int(11) NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ecache`
--

DROP TABLE IF EXISTS `ecache`;
CREATE TABLE IF NOT EXISTS `ecache` (
  `etimer` int(14) NOT NULL DEFAULT '0' COMMENT 'Event servertime and 3 digit id',
  `etype` varchar(1) COLLATE utf8_bin NOT NULL DEFAULT 'm' COMMENT 'm=move, c=challange, c=chat',
  `eid` int(11) NOT NULL COMMENT 'ID of the event (move has game id)',
  `econtent` varchar(255) COLLATE utf8_bin NOT NULL COMMENT 'content of the event',
  UNIQUE KEY `etimer` (`etimer`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Cache table for phpChess message events';

-- --------------------------------------------------------

--
-- Table structure for table `elo_points`
--

DROP TABLE IF EXISTS `elo_points`;
CREATE TABLE IF NOT EXISTS `elo_points` (
  `player_id` int(11) NOT NULL,
  `cpoints` int(11) NOT NULL,
  `opoints` int(11) NOT NULL,
  PRIMARY KEY (`player_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

DROP TABLE IF EXISTS `email_log`;
CREATE TABLE IF NOT EXISTS `email_log` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_fromemail` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_fromname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_body` text COLLATE utf8_unicode_ci NOT NULL,
  `o_errormsg` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE IF NOT EXISTS `game` (
  `game_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `initiator` int(11) DEFAULT NULL,
  `w_player_id` int(11) NOT NULL DEFAULT '0',
  `b_player_id` int(11) NOT NULL DEFAULT '0',
  `status` enum('I','A','T','P','C','W') COLLATE utf8_unicode_ci DEFAULT 'W',
  `completion_status` enum('W','B','D','A','I') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'I',
  `start_time` int(11) DEFAULT NULL,
  `next_move` enum('w','b') COLLATE utf8_unicode_ci DEFAULT NULL,
  `cast_ws` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `cast_wl` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `cast_bs` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `cast_bl` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `draw_requests` enum('white','black','both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `w_time_used` int(11) DEFAULT NULL,
  `b_time_used` int(11) DEFAULT NULL,
  PRIMARY KEY (`game_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_fin`
--

DROP TABLE IF EXISTS `game_fin`;
CREATE TABLE IF NOT EXISTS `game_fin` (
  `game_id` varchar(32) NOT NULL DEFAULT '',
  `initiator` int(11) DEFAULT NULL,
  `w_player_id` int(11) NOT NULL DEFAULT '0',
  `b_player_id` int(11) NOT NULL DEFAULT '0',
  `status` enum('I','A','T','P','C','W') DEFAULT 'W',
  `completion_status` enum('W','B','D','A','I') NOT NULL DEFAULT 'D',
  `start_time` int(11) DEFAULT NULL,
  `cast_ws` enum('0','1') NOT NULL DEFAULT '1',
  `cast_wl` enum('0','1') NOT NULL DEFAULT '1',
  `cast_bs` enum('0','1') NOT NULL DEFAULT '1',
  `cast_bl` enum('0','1') NOT NULL DEFAULT '1',
  `movespgn` longtext NOT NULL,
  `movesw` longtext NOT NULL,
  `movesb` longtext NOT NULL,
  `chat` longtext NOT NULL,
  PRIMARY KEY (`game_id`)
)DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `language_filter`
--

DROP TABLE IF EXISTS `language_filter`;
CREATE TABLE IF NOT EXISTS `language_filter` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_word` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_queue`
--

DROP TABLE IF EXISTS `message_queue`;
CREATE TABLE IF NOT EXISTS `message_queue` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL DEFAULT '0',
  `message` blob,
  `posted` int(11) DEFAULT NULL,
  PRIMARY KEY (`mid`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mobile_client_ip`
--

DROP TABLE IF EXISTS `mobile_client_ip`;
CREATE TABLE IF NOT EXISTS `mobile_client_ip` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_playerid` int(11) NOT NULL,
  `o_ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `move_history`
--

DROP TABLE IF EXISTS `move_history`;
CREATE TABLE IF NOT EXISTS `move_history` (
  `move_id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL DEFAULT '0',
  `player_id` int(11) NOT NULL DEFAULT '0',
  `move` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `game_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`move_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pc_chat_messages`
--

DROP TABLE IF EXISTS `pc_chat_messages`;
CREATE TABLE IF NOT EXISTS `pc_chat_messages` (
  `o_chatid` int(11) NOT NULL AUTO_INCREMENT,
  `o_playerid` int(11) NOT NULL,
  `o_message` text COLLATE utf8_unicode_ci NOT NULL,
  `o_datesent` int(11) NOT NULL,
  PRIMARY KEY (`o_chatid`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pc_chat_players`
--

DROP TABLE IF EXISTS `pc_chat_players`;
CREATE TABLE IF NOT EXISTS `pc_chat_players` (
  `o_cplayer` int(11) NOT NULL AUTO_INCREMENT,
  `o_playerid` int(11) NOT NULL,
  `o_joined` int(11) NOT NULL,
  PRIMARY KEY (`o_cplayer`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pendingplayer`
--

DROP TABLE IF EXISTS `pendingplayer`;
CREATE TABLE IF NOT EXISTS `pendingplayer` (
  `player_id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `PASSWORD` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `signup_time` int(11) NOT NULL DEFAULT '0',
  `STATUS` enum('N','A','I','F','E') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'F',
  `email` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`player_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
  `player_id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_hashed` tinyint(1) NOT NULL DEFAULT '0',
  `signup_time` int(11) NOT NULL DEFAULT '0',
  `status` enum('N','A','I','F','E') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'F',
  `email` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`player_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player2`
--

DROP TABLE IF EXISTS `player2`;
CREATE TABLE IF NOT EXISTS `player2` (
  `player_id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`player_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player3`
--

DROP TABLE IF EXISTS `player3`;
CREATE TABLE IF NOT EXISTS `player3` (
  `player_id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`player_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_archive`
--

DROP TABLE IF EXISTS `player_archive`;
CREATE TABLE IF NOT EXISTS `player_archive` (
  `player_id` int(11) NOT NULL,
  `userid` varchar(32) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `signup_time` int(11) NOT NULL DEFAULT '0',
  `status` enum('N','A','I','F','E') NOT NULL DEFAULT 'F',
  `email` varchar(128) DEFAULT NULL,
  `a_imgname` varchar(255) DEFAULT NULL,
  `cc_dcolor` varchar(32) DEFAULT NULL,
  `cc_lcolor` varchar(32) DEFAULT NULL,
  `p_move` char(1) NOT NULL DEFAULT '',
  `p_challange` char(1) NOT NULL DEFAULT '',
  `p_fullname` varchar(100) DEFAULT NULL,
  `p_location` varchar(100) DEFAULT NULL,
  `p_age` char(3) DEFAULT NULL,
  `p_selfrating` varchar(50) DEFAULT NULL,
  `p_commentmotto` varchar(255) DEFAULT NULL,
  `p_favouritechessplayer` varchar(255) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT '0',
  `style` int(11) NOT NULL DEFAULT '0',
  `o_isdragdrop` int(11) NOT NULL DEFAULT '0',
  `cpoints` int(11) NOT NULL DEFAULT '0',
  `opoints` int(11) NOT NULL DEFAULT '0',
  `o_credits` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player_id`)
)DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `player_credits`
--

DROP TABLE IF EXISTS `player_credits`;
CREATE TABLE IF NOT EXISTS `player_credits` (
  `o_playerid` int(11) NOT NULL AUTO_INCREMENT,
  `o_credits` int(11) NOT NULL,
  PRIMARY KEY (`o_playerid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_last_login`
--

DROP TABLE IF EXISTS `player_last_login`;
CREATE TABLE IF NOT EXISTS `player_last_login` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_playerid` int(11) NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_purchased_activities`
--

DROP TABLE IF EXISTS `player_purchased_activities`;
CREATE TABLE IF NOT EXISTS `player_purchased_activities` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_playerid` int(11) NOT NULL,
  `o_activitiesid` int(11) NOT NULL,
  `o_credit` int(11) NOT NULL,
  `o_complete` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_purchased_activity_pages`
--

DROP TABLE IF EXISTS `player_purchased_activity_pages`;
CREATE TABLE IF NOT EXISTS `player_purchased_activity_pages` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_playerpurchasedactivitiesid` int(11) NOT NULL,
  `o_activitypagesid` int(11) NOT NULL,
  `o_answertype` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `o_date` datetime NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_email_settings`
--

DROP TABLE IF EXISTS `server_email_settings`;
CREATE TABLE IF NOT EXISTS `server_email_settings` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_smtp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_smtp_port` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_language`
--

DROP TABLE IF EXISTS `server_language`;
CREATE TABLE IF NOT EXISTS `server_language` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_languagefile` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_version`
--

DROP TABLE IF EXISTS `server_version`;
CREATE TABLE IF NOT EXISTS `server_version` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_serverid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `o_major` int(11) NOT NULL,
  `o_minor` int(11) NOT NULL,
  `o_build` int(11) NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smtp_settings`
--

DROP TABLE IF EXISTS `smtp_settings`;
CREATE TABLE IF NOT EXISTS `smtp_settings` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `o_pass` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `o_domain` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`o_id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timed_games`
--

DROP TABLE IF EXISTS `timed_games`;
CREATE TABLE IF NOT EXISTS `timed_games` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `moves1` int(11) NOT NULL,
  `time1` int(11) NOT NULL,
  `moves2` int(11) NOT NULL,
  `time2` int(11) NOT NULL,
  PRIMARY KEY (`id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timed_game_stats`
--

DROP TABLE IF EXISTS `timed_game_stats`;
CREATE TABLE IF NOT EXISTS `timed_game_stats` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `endtimew` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `endtimeb` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `whitetime` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `blacktime` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `starttime` int(11) NOT NULL,
  `endtime` int(11) NOT NULL,
  `wtimectrl` int(11) NOT NULL,
  `btimectrl` int(11) NOT NULL,
  PRIMARY KEY (`id`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_captains`
--

DROP TABLE IF EXISTS `v2_tournament_captains`;
CREATE TABLE IF NOT EXISTS `v2_tournament_captains` (
  `o_tcapt` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  `o_note` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_tcapt`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_config_onetomany`
--

DROP TABLE IF EXISTS `v2_tournament_config_onetomany`;
CREATE TABLE IF NOT EXISTS `v2_tournament_config_onetomany` (
  `o_totmid` int(11) NOT NULL AUTO_INCREMENT,
  `o_name` text COLLATE utf8_unicode_ci NOT NULL,
  `o_description` text COLLATE utf8_unicode_ci NOT NULL,
  `o_playercutoffdate` int(11) NOT NULL,
  `o_tournamentstartdate` int(11) NOT NULL,
  `o_tournamentenddate` int(11) NOT NULL,
  `o_timezone` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `o_gametimeout` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `o_playersignuptype` int(11) NOT NULL,
  `o_dateadded` datetime NOT NULL,
  `o_status` char(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_totmid`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_console_chat`
--

DROP TABLE IF EXISTS `v2_tournament_console_chat`;
CREATE TABLE IF NOT EXISTS `v2_tournament_console_chat` (
  `o_chatid` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  `o_message` text COLLATE utf8_unicode_ci NOT NULL,
  `o_datesent` int(11) NOT NULL,
  PRIMARY KEY (`o_chatid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_console_players`
--

DROP TABLE IF EXISTS `v2_tournament_console_players`;
CREATE TABLE IF NOT EXISTS `v2_tournament_console_players` (
  `o_cplayer` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  `o_joined` int(11) NOT NULL,
  PRIMARY KEY (`o_cplayer`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_game_config`
--

DROP TABLE IF EXISTS `v2_tournament_game_config`;
CREATE TABLE IF NOT EXISTS `v2_tournament_game_config` (
  `o_tgc` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `o_wplayerid` int(11) NOT NULL,
  `o_bplayerid` int(11) NOT NULL,
  `o_wplayerln` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `o_bplayerln` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `o_gmtstarttime` int(11) NOT NULL,
  `o_gmtendtime` int(11) NOT NULL,
  `o_status` char(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_tgc`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_game_queue`
--

DROP TABLE IF EXISTS `v2_tournament_game_queue`;
CREATE TABLE IF NOT EXISTS `v2_tournament_game_queue` (
  `o_tgq` int(11) NOT NULL AUTO_INCREMENT,
  `o_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `o_pid` int(11) NOT NULL,
  `o_reload` char(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_tgq`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_onetomany_chat`
--

DROP TABLE IF EXISTS `v2_tournament_onetomany_chat`;
CREATE TABLE IF NOT EXISTS `v2_tournament_onetomany_chat` (
  `o_chatid` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  `o_message` text COLLATE utf8_unicode_ci NOT NULL,
  `o_mtype` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `o_datesent` int(11) NOT NULL,
  PRIMARY KEY (`o_chatid`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_onetomany_gamemove_vote`
--

DROP TABLE IF EXISTS `v2_tournament_onetomany_gamemove_vote`;
CREATE TABLE IF NOT EXISTS `v2_tournament_onetomany_gamemove_vote` (
  `o_tgmv` int(11) NOT NULL AUTO_INCREMENT,
  `o_gameid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `o_pid` int(11) NOT NULL,
  `o_move` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `o_time` int(11) NOT NULL,
  PRIMARY KEY (`o_tgmv`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_onetomany_players`
--

DROP TABLE IF EXISTS `v2_tournament_onetomany_players`;
CREATE TABLE IF NOT EXISTS `v2_tournament_onetomany_players` (
  `o_cplayer` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  `o_joined` int(11) NOT NULL,
  PRIMARY KEY (`o_cplayer`)
)DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_organizers`
--

DROP TABLE IF EXISTS `v2_tournament_organizers`;
CREATE TABLE IF NOT EXISTS `v2_tournament_organizers` (
  `o_torg` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  PRIMARY KEY (`o_torg`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v2_tournament_players`
--

DROP TABLE IF EXISTS `v2_tournament_players`;
CREATE TABLE IF NOT EXISTS `v2_tournament_players` (
  `o_tplayer` int(11) NOT NULL AUTO_INCREMENT,
  `o_ttype` int(11) NOT NULL,
  `o_tid` int(11) NOT NULL,
  `o_playerid` int(11) NOT NULL,
  `o_clubid` int(11) NOT NULL,
  `o_status` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `o_note` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_tplayer`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `whos_online_graph`
--

DROP TABLE IF EXISTS `whos_online_graph`;
CREATE TABLE IF NOT EXISTS `whos_online_graph` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_date` date NOT NULL,
  `o_player` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`o_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
