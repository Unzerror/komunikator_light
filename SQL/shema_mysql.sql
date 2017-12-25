/*
*  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

*    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
*    Copyright (C) 2012-2013, ООО «Телефонные системы»

*    ЭТОТ ФАЙЛ является частью проекта «Komunikator»

*    Сайт проекта «Komunikator»: http://4yate.ru/
*    Служба технической поддержки проекта «Komunikator»: E-mail: support@4yate.ru

*    В проекте «Komunikator» используются:
*      исходные коды проекта «YATE», http://yate.null.ro/pmwiki/
*      исходные коды проекта «FREESENTRAL», http://www.freesentral.com/
*      библиотеки проекта «Sencha Ext JS», http://www.sencha.com/products/extjs

*    Web-приложение «Komunikator» является свободным и открытым программным обеспечением. Тем самым
*  давая пользователю право на распространение и (или) модификацию данного Web-приложения (а также
*  и иные права) согласно условиям GNU General Public License, опубликованной
*  Free Software Foundation, версии 3.

*    В случае отсутствия файла «License» (идущего вместе с исходными кодами программного обеспечения)
*  описывающего условия GNU General Public License версии 3, можно посетить официальный сайт
*  http://www.gnu.org/licenses/ , где опубликованы условия GNU General Public License
*  различных версий (в том числе и версии 3).

*  | ENG | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

*    "Komunikator" is a web interface for IP-PBX "YATE" configuration and management
*    Copyright (C) 2012-2013, "Telephonnyie sistemy" Ltd.

*    THIS FILE is an integral part of the project "Komunikator"

*    "Komunikator" project site: http://4yate.ru/
*    "Komunikator" technical support e-mail: support@4yate.ru

*    The project "Komunikator" are used:
*      the source code of "YATE" project, http://yate.null.ro/pmwiki/
*      the source code of "FREESENTRAL" project, http://www.freesentral.com/
*      "Sencha Ext JS" project libraries, http://www.sencha.com/products/extjs

*    "Komunikator" web application is a free/libre and open-source software. Therefore it grants user rights
*  for distribution and (or) modification (including other rights) of this programming solution according
*  to GNU General Public License terms and conditions published by Free Software Foundation in version 3.

*    In case the file "License" that describes GNU General Public License terms and conditions,
*  version 3, is missing (initially goes with software source code), you can visit the official site
*  http://www.gnu.org/licenses/ and find terms specified in appropriate GNU General Public License
*  version (version 3 as well).

*  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
*/


--
-- Table structure for table `actionlogs`
--

DROP TABLE IF EXISTS `actionlogs`;

CREATE TABLE `actionlogs` (
  `date` decimal(17,3) NOT NULL,
  `log` text,
  `performer_id` text,
  `performer` text,
  `real_performer_id` text,
  `object` text,
  `query` text,
  `ip` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `additional_settings`
--

DROP TABLE IF EXISTS `additional_settings`;

CREATE TABLE `additional_settings` (
  `settings_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(250) DEFAULT NULL,
  `value` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`settings_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `additional_settings` WRITE;

INSERT INTO `additional_settings` (
  `settings_id`,
 `description`,
  `value`
)
VALUES 
(1, 'call_history_lifespan', '12'), 
(2, 'call_records_lifespan', '12');

UNLOCK TABLES;


--
-- Table structure for table `call_back`
--

DROP TABLE IF EXISTS `call_back`;

CREATE TABLE `call_back` (
  `call_back_id` int(11) NOT NULL AUTO_INCREMENT,
  `destination` varchar(254) DEFAULT NULL,
  `name_site` varchar(254) DEFAULT NULL,
  `description` varchar(254) DEFAULT NULL,
  `callthrough_time` varchar(3) DEFAULT NULL,
  `settings` varchar(5000) DEFAULT NULL,
  PRIMARY KEY (`call_back_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `call_logs`
--

DROP TABLE IF EXISTS `call_logs`;

CREATE TABLE `call_logs` (
  `time` decimal(17,4) NOT NULL,
  `chan` varchar(300) NOT NULL,
  `address` varchar(40) DEFAULT NULL,
  `direction` varchar(50) DEFAULT NULL,
  `billid` varchar(20) NOT NULL,
  `callbillid` varchar(20) NOT NULL,
  `caller` varchar(20) DEFAULT NULL,
  `called` varchar(20) DEFAULT NULL,
  `duration` decimal(7,3) DEFAULT NULL,
  `billtime` decimal(7,3) DEFAULT NULL,
  `ringtime` decimal(7,3) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `reason` varchar(64) DEFAULT NULL,
  `ended` tinyint(1) DEFAULT 0,
  `gateway` varchar(1024) DEFAULT NULL,
  `callid` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`billid`,`chan`),
  UNIQUE KEY `time_indx` (`time`) USING HASH,
  KEY `billid_indx` (`billid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `call_history`;

--
-- Table structure for table `call_history`
--
CREATE TABLE `call_history` (
  `time` decimal(17,3) NOT NULL,
  `chan` varchar(300) DEFAULT NULL,
  `address` varchar(40) DEFAULT NULL,
  `direction` varchar(350) DEFAULT NULL,
  `billid` varchar(20) DEFAULT NULL,
  `caller` varchar(20) DEFAULT NULL,
  `called` varchar(20) DEFAULT NULL,
  `duration` decimal(7,3) DEFAULT NULL,
  `billtime` decimal(7,3) DEFAULT NULL,
  `ringtime` decimal(7,3) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `reason` varchar(64) DEFAULT NULL,
  `ended` tinyint(1) DEFAULT NULL,
  `gateway` varchar(1024) DEFAULT NULL,
  UNIQUE KEY `time_indx` (`time`) USING HASH,
  KEY `billid_indx` (`billid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Table structure for table `call_records`
--
DROP TABLE IF EXISTS `call_records`;

CREATE TABLE `call_records` (
`call_records_id` int(11) NOT NULL AUTO_INCREMENT,
`caller_number` varchar(250) DEFAULT NULL,
`caller_group` varchar(250) DEFAULT NULL,
`type` varchar(250) DEFAULT NULL,
`gateway` varchar(250) DEFAULT NULL,
`called_number` varchar(250) DEFAULT NULL,
`called_group` varchar(250) DEFAULT NULL,
`enabled` tinyint(1) DEFAULT NULL,
`description` text,
PRIMARY KEY (`call_records_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `card_confs`
--

DROP TABLE IF EXISTS `card_confs`;

CREATE TABLE `card_confs` (
  `param_name` text,
  `param_value` text,
  `section_name` text,
  `module_name` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `card_ports`
--

DROP TABLE IF EXISTS `card_ports`;

CREATE TABLE `card_ports` (
  `BUS` int(11) DEFAULT NULL,
  `SLOT` int(11) DEFAULT NULL,
  `PORT` int(11) DEFAULT NULL,
  `filename` text,
  `span` text,
  `type` text,
  `card_type` text,
  `voice_interface` text,
  `sig_interface` text,
  `voice_chans` text,
  `sig_chans` text,
  `echocancel` tinyint(1) DEFAULT NULL,
  `dtmfdetect` tinyint(1) DEFAULT NULL,
  `name` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `detailed_infocall`
--
CREATE TABLE `detailed_infocall` (
  `detailed_infocall_id` int(11) NOT NULL AUTO_INCREMENT,
  `time` decimal(17,3) NOT NULL,
  `billid` varchar(20) DEFAULT NULL,
  `caller` varchar(20) DEFAULT NULL,
  `called` varchar(20) DEFAULT NULL,
  `detailed` varchar(1024) DEFAULT NULL,
  `reason` varchar(64) DEFAULT NULL,
  `ended` tinyint(1) DEFAULT NULL,
  `gateway` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`detailed_infocall_id`),
  UNIQUE KEY `billid_indx` (`billid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `dial_plans`
--

DROP TABLE IF EXISTS `dial_plans`;

CREATE TABLE `dial_plans` (
  `dial_plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `dial_plan` text,
  `priority` int(11) DEFAULT NULL,
  `prefix` varchar(32) DEFAULT NULL,
  `gateway_id` int(11) DEFAULT NULL,
  `nr_of_digits_to_cut` int(11) DEFAULT NULL,
  `position_to_start_cutting` int(11) DEFAULT NULL,
  `nr_of_digits_to_replace` int(11) DEFAULT NULL,
  `digits_to_replace_with` text,
  `position_to_start_replacing` int(11) DEFAULT NULL,
  `position_to_start_adding` int(11) DEFAULT NULL,
  `digits_to_add` text,
  PRIMARY KEY (`dial_plan_id`),
  UNIQUE KEY `priority` (`priority`) USING BTREE,
  UNIQUE KEY `prefix` (`prefix`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `dids`
--

DROP TABLE IF EXISTS `dids`;

CREATE TABLE `dids` (
  `did_id` int(11) NOT NULL AUTO_INCREMENT,
  `did` text,
  `number` varchar(25) DEFAULT NULL,
  `destination` text,
  `description` text,
  `extension_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`did_id`),
  UNIQUE KEY `number` (`number`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `extensions`
--

DROP TABLE IF EXISTS `extensions`;

CREATE TABLE `extensions` (
  `extension_id` int(11) NOT NULL AUTO_INCREMENT,
  `extension` varchar(3) NOT NULL,
  `password` text,
  `firstname` text,
  `lastname` text,
  `address` text,
  `inuse` int(11) DEFAULT NULL,
  `location` text,
  `expires` decimal(17,3) DEFAULT NULL,
  `max_minutes` time DEFAULT NULL,
  `used_minutes` time DEFAULT NULL,
  `inuse_count` int(11) DEFAULT NULL,
  `inuse_last` decimal(17,3) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT NULL,
  `line_limit` tinyint(3) DEFAULT 1,
  `full_limit` tinyint(3) DEFAULT 1,
  PRIMARY KEY (`extension_id`),
  UNIQUE KEY `extension` (`extension`),
  KEY `extension_id` (`extension_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `gateways`
--

DROP TABLE IF EXISTS `gateways`;

CREATE TABLE `gateways` (
  `gateway_id` int(11) NOT NULL AUTO_INCREMENT,
  `gateway` text,
  `protocol` text,
  `server` text,
  `type` text,
  `username` text,
  `password` text,
  `enabled` tinyint(1) DEFAULT NULL,
  `description` text,
  `interval` text,
  `authname` text,
  `domain` text,
  `outbound` text,
  `localaddress` text,
  `formats` text,
  `rtp_localip` text,
  `ip_transport` text,
  `oip_transport` text,
  `port` text,
  `iaxuser` text,
  `iaxcontext` text,
  `rtp_forward` tinyint(1) DEFAULT NULL,
  `status` text,
  `modified` tinyint(1) DEFAULT NULL,
  `callerid` text,
  `callername` text,
  `send_extension` tinyint(1) DEFAULT NULL,
  `trusted` tinyint(1) DEFAULT NULL,
  `sig_trunk_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`gateway_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `group_members`
--

DROP TABLE IF EXISTS `group_members`;

CREATE TABLE `group_members` (
  `group_member_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `extension_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`group_member_id`),
  KEY `group_id` (`group_id`),
  KEY `extension_id` (`extension_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `group_priority`
--

DROP TABLE IF EXISTS `group_priority`;

CREATE TABLE `group_priority` (
  `group_id` int(11) NOT NULL,
  `extension_id` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`extension_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group` varchar(50) DEFAULT NULL,
  `description` text,
  `extension` varchar(2) DEFAULT NULL,
  `mintime` int(11) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `maxout` int(11) DEFAULT NULL,
  `greeting` text,
  `maxcall` int(11) DEFAULT NULL,
  `prompt` text,
  `detail` tinyint(1) DEFAULT NULL,
  `playlist_id` int(11) DEFAULT NULL,
  `last_priority` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group` (`group`),
  UNIQUE KEY `extension` (`extension`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `groups` WRITE;

INSERT INTO `groups` (
  `group_id`,
  `group`,
  `description`,
  `extension`,
  `mintime`,
  `length`,
  `maxout`,
  `greeting`,
  `maxcall`,
  `prompt`,
  `detail`,
  `playlist_id`
)
VALUES
(NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

UNLOCK TABLES;


--
-- Table structure for table `incoming_gateways`
--

DROP TABLE IF EXISTS `incoming_gateways`;

CREATE TABLE `incoming_gateways` (
  `incoming_gateway_id` int(11) NOT NULL AUTO_INCREMENT,
  `incoming_gateway` text,
  `gateway_id` int(11) DEFAULT NULL,
  `ip` text,
  PRIMARY KEY (`incoming_gateway_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `keys`
--

DROP TABLE IF EXISTS `keys`;

CREATE TABLE `keys` (
  `key_id` int(11) NOT NULL AUTO_INCREMENT,
  `key` text,
  `prompt_id` int(11) DEFAULT NULL,
  `destination` text,
  `description` text,
  PRIMARY KEY (`key_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `limits_international`
--

DROP TABLE IF EXISTS `limits_international`;

CREATE TABLE `limits_international` (
  `limit_international_id` int(11) NOT NULL AUTO_INCREMENT,
  `limit_international` text,
  `name` text,
  `value` text,
  PRIMARY KEY (`limit_international_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;

CREATE TABLE `modules` (
  `module_name_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(70) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL,
  `condition` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`module_name_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `modules` WRITE;

INSERT INTO `modules` (
  `module_name_id`,
  `module_name`,
  `description`,
  `version`,
  `condition`
)
VALUES 
(1, 'Call_website_Grid', 'text_call_website', '1.0', 0), 
(2, 'Mail_Settings_Panel', 'text_mail_Settings', '1.0', 0),
(3, 'Call_Record_Grid', 'text_call_record', '1.0', 0),
(4, 'Call_back_Grid', 'text_call_back', '1.0', 0);

UNLOCK TABLES;


--
-- Table structure for table `music_on_hold`
--

DROP TABLE IF EXISTS `music_on_hold`;

CREATE TABLE `music_on_hold` (
  `music_on_hold_id` int(11) NOT NULL AUTO_INCREMENT,
  `music_on_hold` text,
  `description` text,
  `file` text,
  PRIMARY KEY (`music_on_hold_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `music_on_hold` WRITE;

INSERT INTO `music_on_hold` (
  `music_on_hold_id`,
  `music_on_hold`,
  `description`,
  `file`
)
VALUES 
(1, 'kpv.mp3', 'Ringback tone', 'kpv.mp3');

UNLOCK TABLES;

--
-- Table structure for table `network_interfaces`
--

DROP TABLE IF EXISTS `network_interfaces`;

CREATE TABLE `network_interfaces` (
  `network_interface_id` int(11) NOT NULL AUTO_INCREMENT,
  `network_interface` text,
  `protocol` text,
  `ip_address` text,
  `netmask` text,
  `gateway` text,
  PRIMARY KEY (`network_interface_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `ntn_settings`
--

DROP TABLE IF EXISTS `ntn_settings`;

CREATE TABLE `ntn_settings` (
  `ntn_setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `param` text,
  `value` text,
  `description` text,
  PRIMARY KEY (`ntn_setting_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `pbx_settings`
--

DROP TABLE IF EXISTS `pbx_settings`;

CREATE TABLE `pbx_settings` (
  `pbx_setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `extension_id` int(11) DEFAULT NULL,
  `param` text,
  `value` text,
  PRIMARY KEY (`pbx_setting_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `playlist_items`
--

DROP TABLE IF EXISTS `playlist_items`;

CREATE TABLE `playlist_items` (
  `playlist_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `playlist_id` int(11) DEFAULT NULL,
  `music_on_hold_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`playlist_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `playlist_items` WRITE;

INSERT INTO `playlist_items` (
  `playlist_item_id`,
  `playlist_id`,
  `music_on_hold_id`
)
VALUES
(1, 2, 1);

UNLOCK TABLES;

--
-- Table structure for table `playlists`
--

DROP TABLE IF EXISTS `playlists`;

CREATE TABLE `playlists` (
  `playlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `playlist` text,
  `in_use` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`playlist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `playlists` WRITE;

INSERT INTO `playlists` (
  `playlist_id`,
  `playlist`,
  `in_use`
)
VALUES
(NULL, '', '0'),
(2, 'default', '1');

UNLOCK TABLES;

--
-- Table structure for table `prefixes`
--

DROP TABLE IF EXISTS `prefixes`;

CREATE TABLE `prefixes` (
  `prefix_id` int(11) NOT NULL AUTO_INCREMENT,
  `prefix` text,
  `name` text,
  `international` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`prefix_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `prompts`
--

DROP TABLE IF EXISTS `prompts`;

CREATE TABLE `prompts` (
  `prompt_id` int(11) NOT NULL AUTO_INCREMENT,
  `prompt` text,
  `description` text,
  `status` text,
  `file` text,
  PRIMARY KEY (`prompt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `prompts` WRITE;

INSERT INTO `prompts` (
  `prompt_id`,
  `prompt`,
  `status`,
  `file`
)
VALUES
(1, 'online', 'online', 'online.wav'),
(2, 'offline', 'offline', 'offline.wav');

UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `param` text,
  `value` text,
  `description` text,
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `short_names`
--

DROP TABLE IF EXISTS `short_names`;

CREATE TABLE `short_names` (
  `short_name_id` int(11) NOT NULL AUTO_INCREMENT,
  `short_name` varchar(20) DEFAULT NULL,
  `name` text,
  `number` varchar(3) DEFAULT NULL,
  `extension_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`short_name_id`),
  UNIQUE KEY `short_name` (`short_name`) USING BTREE,
  UNIQUE KEY `number` (`number`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE VIEW history_temp AS select '' AS `id`,`a`.`time` AS `time`,(case when ((`a`.`direction` = 'order_call') and ((`c`.`detailed` <> NULL) or (`c`.`detailed` <> ''))) then concat('Перезвоните мне: ',`c`.`detailed`) else `a`.`direction` end) AS `type`,(case when isnull(`x1`.`firstname`) then `a`.`caller` else concat(`x1`.`firstname`,' ',`x1`.`lastname`,' (',`a`.`caller`,')') end) AS `caller`,(case when isnull(`x2`.`firstname`) then `a`.`called` else concat(`x2`.`firstname`,' ',`x2`.`lastname`,' (',`a`.`called`,')') end) AS `called`,round(`a`.`billtime`,0) AS `duration`,(case when ((`g`.`description` is not null) and (`g`.`description` <> '')) then `g`.`description` when (`g`.`gateway` is not null) then `g`.`gateway` when (`g`.`authname` is not null) then `g`.`authname` else `a`.`gateway` end) AS `gateway`,`a`.`status` AS `status`,(case when (`a`.`time` is not null) then concat(date_format(from_unixtime(`a`.`time`),'%d_%m_%Y_%H_%i_%s'),'~',`a`.`caller`,'~',`a`.`called`) else NULL end) AS `record` from ((((`call_history` `a` left join `extensions` `x1` on((`x1`.`extension` = `a`.`caller`))) left join `extensions` `x2` on((`x2`.`extension` = `a`.`called`))) left join `gateways` `g` on((`g`.`authname` = `a`.`gateway`))) left join `detailed_infocall` `c` on(((`c`.`billid` = `a`.`billid`) and (`c`.`time` = `a`.`time`))));


--
-- Table structure for table `sig_trunks`
--

DROP TABLE IF EXISTS `sig_trunks`;

CREATE TABLE `sig_trunks` (
  `sig_trunk_id` int(11) NOT NULL AUTO_INCREMENT,
  `sig_trunk` text,
  `enable` text,
  `type` text,
  `switchtype` text,
  `sig` text,
  `voice` text,
  `number` text,
  `rxunderrun` int(11) DEFAULT NULL,
  `strategy` text,
  `strategy-restrict` text,
  `userparttest` int(11) DEFAULT NULL,
  `channelsync` int(11) DEFAULT NULL,
  `channellock` int(11) DEFAULT NULL,
  `numplan` text,
  `numtype` text,
  `presentation` text,
  `screening` text,
  `format` text,
  `print-messages` text,
  `print-frames` text,
  `extended-debug` text,
  `layer2dump` text,
  `layer3dump` text,
  `port` text,
  PRIMARY KEY (`sig_trunk_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `time_frames`
--

DROP TABLE IF EXISTS `time_frames`;

CREATE TABLE `time_frames` (
  `time_frame_id` int(11) NOT NULL AUTO_INCREMENT,
  `prompt_id` int(11) DEFAULT NULL,
  `day` varchar(12) DEFAULT NULL,
  `start_hour` varchar(5) DEFAULT NULL,
  `end_hour` varchar(5) DEFAULT NULL,
  `numeric_day` int(11) DEFAULT NULL,
  PRIMARY KEY (`time_frame_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `time_frames` WRITE;

INSERT INTO `time_frames` (
  `time_frame_id`,
  `prompt_id`,
  `day`,
  `start_hour`,
  `end_hour`,
  `numeric_day`
)
VALUES
(NULL, '1', 'Sunday',     NULL, NULL, '0'),
(NULL, '1', 'Monday',     '5',  '14', '1'),
(NULL, '1', 'Tuesday',    '5',  '14', '2'),
(NULL, '1', 'Wednesday',  '5',  '14', '3'),
(NULL, '1', 'Thursday',   '5',  '14', '4'),
(NULL, '1', 'Friday',     '5',  '14', '5'),
(NULL, '1', 'Saturday',   NULL, NULL, '6');

UNLOCK TABLES;


--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` text,
  `password` text,
  `firstname` text,
  `lastname` text,
  `email` text,
  `description` text,
  `fax_number` text,
  `ident` text,
  `login_attempts` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `users` WRITE;

INSERT INTO `users` (
  `user_id`,
  `username`,
  `password`,
  `firstname`,
  `lastname`,
  `email`,
  `description`,
  `fax_number`,
  `ident`,
  `login_attempts`
)
VALUES
(NULL, 'admin', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

UNLOCK TABLES;



--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `param` text,
  `value` text,
  `description` text,
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `settings` WRITE;

INSERT INTO `settings` (
  `setting_id`,
  `param`,
  `value`,
  `description`
)
VALUES 
(NULL, 'vm', 'external/nodata/leavemaildb.php', NULL), 
(NULL, 'version', '1.5.a0', NULL), 
(NULL, 'annonymous_calls', 'yes', NULL), 
(NULL, 'callername', NULL, NULL), 
(NULL, 'prefix', NULL, NULL), 
(NULL, 'callerid', NULL, NULL), 
(NULL, 'international_calls', 'yes', NULL), 
(NULL, 'international_calls_live', 'yes', NULL),
(NULL, 'debug', 1, 'register'), 
(NULL, 'query', 1, 'register'), 
(NULL, 'debug', 1, 'route'), 
(NULL, 'query', 1, 'route'), 
(NULL, 'debug', 1, 'record'), 
(NULL, 'query', 1, 'record'), 
(NULL, 'debug', 1, 'leavemaildb'), 
(NULL, 'query', 1, 'leavemaildb'), 
(NULL, 'debug', 1, 'voicemaildb'), 
(NULL, 'query', 1, 'voicemaildb'), 
(NULL, 'debug', 1, 'auto_attendant'), 
(NULL, 'query', 1, 'auto_attendant'), 
(NULL, 'cs_attendant', 'external/nodata/auto_attendant.php', NULL);

UNLOCK TABLES;


--
-- Table structure for table `call_group_history`
--

DROP TABLE IF EXISTS `call_group_history`;

CREATE TABLE `call_group_history` (
  `time` decimal(17,4) NOT NULL,
  `chan` varchar(300) NOT NULL,
  `called` varchar(2) DEFAULT NULL,
  `billid` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`time`,`chan`),
  UNIQUE KEY `time_indx` (`time`) USING HASH
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `call_rec`
--

DROP TABLE IF EXISTS `call_rec`;

CREATE TABLE `call_rec` (
  `start` decimal(17,4) NOT NULL,
  `duration` decimal(6,2) DEFAULT NULL,
  `record` varchar(32) NOT NULL,
  `part` int(11) NOT NULL DEFAULT '0',
  `connect_type` varchar(10) DEFAULT 'call',
  `peer_count` int(11) NOT NULL DEFAULT '2',
  `called` varchar(1024) DEFAULT NULL,
  `chan` varchar(300) NOT NULL,
  `close` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `record_indx` (`record`,`part`,`chan`) USING HASH
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `ext_connection`
--

DROP TABLE IF EXISTS `ext_connection`;


CREATE TABLE `ext_connection` (
  `extension_id` int(11) NOT NULL,
  `extension` varchar(3) NOT NULL,
  `prelocation` text,
  `location` varchar(200) NOT NULL,
  `expires` decimal(17,3) NOT NULL,
  `inuse_count` int(2) NOT NULL DEFAULT '0',
  `line_limit` tinyint(3) DEFAULT 1,
  `video_supply` tinyint(4) DEFAULT NULL,
  `acodec` text,
  `vcodec` text,
  PRIMARY KEY (`extension`,`location`),
  UNIQUE KEY `location` (`location`) USING BTREE,
  KEY `extension` (`extension`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `call_route`
--

DROP TABLE IF EXISTS `call_route`;

CREATE TABLE `call_route` (
  `time` decimal(17,4) NOT NULL,
  `chan` varchar(300) DEFAULT NULL,
  `direction` varchar(350) DEFAULT NULL,
  `billid` varchar(20) DEFAULT NULL,
  `caller` varchar(20) DEFAULT NULL,
  `called` varchar(20) DEFAULT NULL,
  `duration` decimal(7,3) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `reason` varchar(64) DEFAULT NULL,
  UNIQUE KEY `time_indx` (`time`) USING HASH,
  KEY `billid_indx` (`billid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;

CREATE TABLE `history` (
  `connect` decimal(17,4) NOT NULL,
  `duration` int DEFAULT NULL,
  `connect_type` varchar(20) DEFAULT NULL,
  `callbillid` varchar(20) DEFAULT NULL,
  `caller` varchar(1024) DEFAULT NULL,
  `called` varchar(20) DEFAULT NULL,
  `caller_gateway` varchar(120) DEFAULT NULL,
  `called_gateway` varchar(120) DEFAULT NULL,
  `caller_type` varchar(10) DEFAULT NULL,
  `called_type` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `reason` varchar(20) DEFAULT NULL,
  `record` varchar(32) DEFAULT NULL,
  `ended` tinyint(1) DEFAULT NULL,
  UNIQUE KEY `connect_indx` (`connect`) USING HASH,
  KEY `callbillid_indx` (`callbillid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `chan_start`
--

DROP TABLE IF EXISTS `chan_start`;

CREATE TABLE `chan_start` (
  `start` decimal(17,4) DEFAULT NULL,
  `hangup` decimal(17,4) DEFAULT NULL,
  `chan` varchar(300) NOT NULL,
  `billid` varchar(20) NOT NULL,
  `callbillid` varchar(20) DEFAULT NULL,
  `callnumber` varchar(20) DEFAULT NULL,
  `callid` varchar(1024) DEFAULT NULL,
  `direction` varchar(350) DEFAULT NULL,
  PRIMARY KEY (`billid`,`chan`),
  KEY `billid_indx` (`billid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `chan_switch`
--

DROP TABLE IF EXISTS `chan_switch`;

CREATE TABLE `chan_switch` (
  `connect` decimal(17,4) NOT NULL,
  `disconnect` decimal(17,4) DEFAULT NULL,
  `answer` decimal(17,4) DEFAULT NULL,
  `chan` varchar(300) NOT NULL,
  `peerid` varchar(300) DEFAULT NULL,
  `targetid` varchar(300) DEFAULT NULL,
  `billid` varchar(20) DEFAULT NULL,
  `callbillid` varchar(20) DEFAULT NULL,
  `caller` varchar(20) DEFAULT NULL,
  `called` varchar(20) DEFAULT NULL,
  `caller_gateway` varchar(1024) DEFAULT NULL,
  `called_gateway` varchar(1024) DEFAULT NULL,
  `caller_type` varchar(20) DEFAULT NULL,
  `called_type` varchar(20) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `reason` varchar(64) DEFAULT NULL,
  UNIQUE KEY `connect_indx` (`chan`,`connect`) USING HASH,
  KEY `chan_indx` (`chan`),
  KEY `billid_indx` (`billid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- View `activ_channels`
--

CREATE OR REPLACE 
VIEW `activ_channels` 
AS SELECT 
   `time`, `chan`, `address`, `direction`, `billid`, `callbillid`,
  `caller`, `called`, `duration`, `billtime`, `ringtime`, `status`,
  `reason`, `ended`, `gateway`, `callid`,
   IF(`direction`='incoming',`caller`,`called`) as `callnumber`,
   ROUND(UNIX_TIMESTAMP()-`time`) as `fullduration`,
   IF((`billtime`>0)or(`status`='answered'),ROUND(UNIX_TIMESTAMP()-`time`-`ringtime`),0) as `callduration`
FROM call_logs WHERE ended = '0';


--
-- View `activ_conf_room`
--

CREATE OR REPLACE 
VIEW `activ_conf_room`
AS 
SELECT 
ac.start as start,
c.connect,
ROUND(UNIX_TIMESTAMP()-c.connect) as duration,
c.chan,c.targetid, c.billid, ac.callbillid, ac.root_peer, c.caller as caller, c.called as called
FROM chan_switch c
LEFT JOIN (SELECT min(connect) as start,chan as root_peer,peerid,targetid,callbillid
           FROM chan_switch 
           WHERE peerid LIKE 'conf/%' 
                and ((disconnect IS NULL) or (disconnect=0))
		  GROUP BY targetid,callbillid) ac
ON c.targetid=ac.targetid
WHERE c.peerid LIKE 'conf/%' 
      and ((c.disconnect IS NULL) or (c.disconnect=0))
UNION
SELECT NULL as start, NULL as connect, NULL as duration, NULL as chan, 
	  SUBSTRING(d.destination,6,100) as targetid, NULL as billid, NULL as callbillid, NULL as root_peer, 
      NULL as caller, d.number as called 
FROM dids d WHERE d.destination LIKE 'conf/%' 
and NOT EXISTS (SELECT * FROM chan_switch cs WHERE cs.peerid LIKE 'conf/%' and ((cs.disconnect IS NULL) or (cs.disconnect=0))
                                     and SUBSTRING(d.destination,6,100) = cs.targetid)
UNION                                     
SELECT min(i.connect) as start, NULL as connect, NULL as duration,
	   NULL as chan, i.targetid, i.callbillid as billid, i.callbillid as callbillid, NULL as root_peer, NULL as caller, i.called
FROM activ_channels p, chan_switch i
WHERE i.billid = p.billid and i.chan = p.chan
      and i.peerid LIKE 'conf/%' and i.disconnect is NOT NULL
and NOT EXISTS (SELECT * FROM chan_switch cs1 WHERE cs1.peerid LIKE 'conf/%' and ((cs1.disconnect IS NULL) or (cs1.disconnect=0))
                                     and cs1.targetid = i.targetid)
GROUP BY i.targetid,i.callbillid;


--
-- View `activ_connections`
--

CREATE OR REPLACE 
VIEW `activ_connections`
AS SELECT 
   connect,
   disconnect,
   answer,
   IF ((disconnect is NULL) or (disconnect=0), ROUND(UNIX_TIMESTAMP()- connect), disconnect - connect) as duration,
   IF ((answer is NULL) or (answer=0), 0, IF ((disconnect is NULL) or (disconnect=0), ROUND(UNIX_TIMESTAMP()-answer), disconnect - answer)) as callduration,   
   chan,
   peerid,
   targetid,   
   billid,
   callbillid,
   caller,
   called,
   caller_gateway,
   called_gateway,
   caller_type,
   called_type,
   status,
   reason
FROM chan_switch WHERE callbillid in
(SELECT callbillid FROM chan_switch WHERE disconnect is NULL or disconnect=0) or disconnect is NULL or disconnect=0;


--
-- View `activ_queue`
--

CREATE OR REPLACE 
VIEW `activ_queue`
AS
SELECT 
NULL as connect, NULL as duration, NULL as chan, NULL as peerid, 
groups.group_id as targetid,
NULL as billid, NULL as callbillid, 
NULL as caller, 
groups.extension as called
FROM groups
WHERE groups.extension is NOT NULL
UNION
SELECT
connect,
ROUND(UNIX_TIMESTAMP()-connect) as duration,
chan,peerid,targetid,billid,
callbillid, caller, called
           FROM chan_switch
           WHERE peerid LIKE 'q-in/%' 
                and ((disconnect IS NULL) or (disconnect=0))
		   GROUP BY targetid
           ORDER BY targetid, connect;
