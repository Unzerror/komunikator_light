<?php

/*
 *  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

 *    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
 *    Copyright (C) 2012-2013, ООО «Телефонные системы»

 *    ЭТОТ ФАЙЛ является частью проекта «Komunikator»

 *    Сайт проекта «Komunikator»: http://komunikator.ru/
 *    Служба технической поддержки проекта «Komunikator»: E-mail: support@komunikator.ru

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

 *    "Komunikator" project site: http://komunikator.ru/
 *    "Komunikator" technical support e-mail: support@komunikator.ru

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
?><?php

$cur_ver = '1.0.b';
$updates_base = "http://komunikator.ru/repos";
$updates_url = "$updates_base/checkforupdates.php?cur_ver=$cur_ver";
$updates_data_url = "$updates_base/update.tar.gz";

error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_WARNING));

date_default_timezone_set("UTC");
//$def_time_offset = 4;//Смещение метки времени по умолчанию (в часах) при отправке писем   

require_once("DB.php");

function handle_pear_error($e) {
    Yate::Output($e->getMessage() . ' ' . print_r($e->getUserInfo(), true));
}

require_once 'PEAR.php';
//PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'handle_pear_error');
// - - - - -  новый вариант (НАЧАЛО)  - - - - -

if (in_array($action, array('get_call_button', 'create_call_button', 'destroy_call_button', 'update_call_button', 'get_button_code'))) {
    $db_type_sql = "sqlite3";

    $db_sqlite3_path = "/etc/webrtc2sip/c2c_sqlite.db";  // абсолютный путь к файлу БД
    // - - - - - - - - - - - - - - - - - - - -
    // для работы на локальном компьютере под Windows, посредствам Denwer
    // Denwer необходима библиотека sqlite3.php (должна находится c:\WebServers\usr\local\php5\pear\DB\)
    // библиотеку можно скопировать с «тестовой» машины (находится /usr/share/php/DB)
    // $db_sqlite3_path = "Z:\DB_SQLite\c2c_sqlite.db";
    // - - - - - - - - - - - - - - - - - - - -    


    $dsn = $db_type_sql . ":///" . $db_sqlite3_path;
} else {
    $db_type_sql = "mysql";

    $db_host = "localhost";
    $db_user = "kommunikator";
    $db_passwd = "kommunikator";
    $db_database = "kommunikator";

    $dsn = "$db_type_sql://$db_user:$db_passwd@$db_host/$db_database";
}

// - - - - -  новый вариант (КОНЕЦ)  - - - - -
// - - - - -  старый вариант (НАЧАЛО)  - - - - -
/*
  $db_type_sql = "mysql";

  $db_host = "localhost";
  $db_user = "kommunikator";
  $db_passwd = "kommunikator";
  $db_database = "kommunikator";

  $dsn = "$db_type_sql://$db_user:$db_passwd@$db_host/$db_database";
 */
// - - - - -  старый вариант (КОНЕЦ)  - - - - -


$conn = DB::connect($dsn);
$debug_info = true;

if (PEAR::isError($conn)) {
    if ($debug_info)
        echo 'DBMS/Debug Message: ' . $conn->getDebugInfo() . "<br>";
    else
        echo 'Standard Message: ' . $conn->getMessage() . "<br>";
    exit;
}

$conn->setFetchMode(DB_FETCHMODE_ASSOC);


$vm_base = "/var/lib/misc";
$no_groups = false;
$no_pbx = false;
$uploaded_prompts = "/var/lib/misc";
$query_on = false;
$max_resets_conn = 5;

//$calls_email  = "root@localhost"; 
//$fax_call = "root@localhost";
//$calls_email = "info@digt.ru";
//$fax_call = "info@digt.ru";

$source = array(
    'voicemail' => 'external/nodata/voicemail.php',
    'attendant' => 'external/nodata/auto_attendant.php'
);

$key_source = array();
foreach ($source as $key => $value)
    $key_source[$value] = $key;

$time_out = 600;
?>