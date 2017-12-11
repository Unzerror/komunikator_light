<?php

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

$cur_ver = '1.5.a0';

error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_WARNING));

date_default_timezone_set("UTC");
$def_time_offset = 4;//Смещение метки времени по умолчанию (в часах) при отправке писем   

require_once("DB.php");
require_once("PEAR.php");

function handle_pear_error($e) {
    Yate::Output($e->getMessage() . ' ' . print_r($e->getUserInfo(), true));
}

$db_type_sql = "mysqli";
$db_host = "localhost";
$db_user = "kommunikator";
$db_passwd = "kommunikator";
$db_database = "kommunikator";
$dsn = "$db_type_sql://$db_user:$db_passwd@$db_host/$db_database";

$conn = new DB();
$conn = $conn->Connect($dsn); 
if ($conn->isError($conn)) {
     die($conn->message.'<br>'.$conn->userinfo);
}

$conn->setFetchMode(DB_FETCHMODE_ASSOC);

$query_on = false;
$debug_on = false;

$vm_base = "/var/lib/misc";
$uploaded_prompts = "/var/lib/misc";

/*
$source = array(
    'voicemail' => 'external/nodata/voicemail.php',
    'attendant' => 'external/nodata/auto_attendant.php'
);
 
$key_source = array();
foreach ($source as $key => $value)
    $key_source[$value] = $key;
*/

$time_out = 600;
?>