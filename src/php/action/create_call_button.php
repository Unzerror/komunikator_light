<?php

/*
 *  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

 *    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
 *    Copyright (C) 2012-2017, ООО «Телефонные системы»

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
 *    Copyright (C) 2012-2017, "Telephonnyie sistemy" Ltd.

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


/* - - - - -  функция – генератор паролей (НАЧАЛО)  - - - - - */

function password_generator() {

    $s = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $k = 8;
    $d = '';

    for ($j = 1; $j <= $k; $j++) {
        $i = mt_rand(0, 61);
        $d = $d . $s[$i];
    }
    $result = $d;
    return $result;
}

/* - - - - -  функция – генератор паролей (КОНЕЦ)  - - - - - */


/* - - - - -  функция – генератор UUID (Universally Unique Identifier) (НАЧАЛО)  - - - - - */

function UUID_generator() {
    $s = '0123456789abcdef';
    $k = 32;
    $d = '';
    for ($j = 1; $j <= $k; $j++) {
        $i = mt_rand(0, 15);
        $d = $d . $s[$i];

        if ($j == 8) {
            $d = $d . '-';
        }
        if ($j == 12) {
            $d = $d . '-';
        }
        if ($j == 16) {
            $d = $d . '-';
        }
        if ($j == 20) {
            $d = $d . '-';
        }
    }
    $result = $d;
    return $result;
}

/* - - - - -  функция – генератор UUID (Universally Unique Identifier) (КОНЕЦ)  - - - - - */

need_user();

$data = json_decode($HTTP_RAW_POST_DATA);
if ($data && !is_array($data))
    $data = array($data);

/* - - - - -  получение ключевых переменных (НАЧАЛО)  - - - - - */

foreach ($data as $row) {
    foreach ($row as $key => $value) {
        if ($key == 'description') {
            $sda_description = $value;
        }  // значение поля «Описание»
        if ($key == 'destination') {
            $sda_extension_number = $value;
        }  // значение поля «Назначение»
        if ($key == 'short_name') {
            $sda_from_whom = $value;
        }  // значение поля «Псевдоним»     
    }
}

$sda_ip_address = $_SERVER['SERVER_ADDR'];  // IP-адрес IP-АТС

/* - - - - -  получение ключевых переменных (КОНЕЦ)  - - - - - */



/* - - - - -  сопоставление символьных обозначений номерам групп (НАЧАЛО)  - - - - - */

$sda_get_groups = $_SESSION["get_groups"];  // 1 - group, 3 - extension
foreach ($sda_get_groups as $row) {
    if ($row[1] == $sda_extension_number) {
        $sda_extension_number = $row[3];
        break;
    }
}

/* - - - - -  сопоставление символьных обозначений номерам групп (КОНЕЦ)  - - - - - */

/* - - - - -  получение значений инкрементов (НАЧАЛО)  - - - - - */

$sda_query = "SELECT * FROM sqlite_sequence";
$data = compact_array(query_to_array($sda_query));

if (!is_array($data["data"]))
    echo out(array("success" => false, "message" => $data));

$sda_sequence_number_table_account = 0;
$sda_sequence_number_table_account_sip = 0;
$sda_sequence_number_table_account_sip_caller = 0;

foreach ($data["data"] as $row) {
    if ($row[0] == 'account') {
        $sda_sequence_number_table_account = $row[1];
    };
    if ($row[0] == 'account_sip') {
        $sda_sequence_number_table_account_sip = $row[1];
    };
    if ($row[0] == 'account_sip_caller') {
        $sda_sequence_number_table_account_sip_caller = $row[1];
    };
}

/* - - - - -  получение значений инкрементов (КОНЕЦ)  - - - - - */

/* - - - - -  добавление записи в таблицу account (НАЧАЛО)  - - - - - */

$sda_id_table_account = $sda_sequence_number_table_account + 1;
$sda_softVersion_table_account = 1;
$sda_databaseVersion_table_account = 1;
$sda_name_table_account = $sda_description;
$sda_email_table_account = 'komunikator@' . $sda_from_whom;
$sda_password_table_account = password_generator();
$sda_auxiliary_variable = $sda_password_table_account . ':' . $sda_email_table_account . ':click2call.org';
$sda_auth_token_table_account = MD5($sda_auxiliary_variable);
$sda_activated_table_account = 1;
$sda_activation_code_table_account = UUID_generator();
$sda_epoch_table_account = time();


$sda_query_table_account[id] = "'$sda_id_table_account'";

$sda_query_table_account[softVersion] = "'$sda_softVersion_table_account'";

$sda_query_table_account[databaseVersion] = "'$sda_databaseVersion_table_account'";

$sda_query_table_account[name] = "'$sda_name_table_account'";

$sda_query_table_account[email] = "'$sda_email_table_account'";

$sda_query_table_account[password] = "'$sda_password_table_account'";

$sda_query_table_account[auth_token] = "'$sda_auth_token_table_account'";

$sda_query_table_account[activated] = "'$sda_activated_table_account'";

$sda_query_table_account[activation_code] = "'$sda_activation_code_table_account'";

$sda_query_table_account[epoch] = "'$sda_epoch_table_account'";

/* - - - - -  добавление записи в таблицу account (КОНЕЦ)  - - - - - */


/* - - - - -  добавление записи в таблицу account_sip (НАЧАЛО)  - - - - - */

$sda_id_table_account_sip = $sda_sequence_number_table_account_sip + 1;
$sda_auxiliary_variable = 'sip:' . $sda_extension_number . '@' . $sda_ip_address;
$sda_address_table_account_sip = base64_encode($sda_auxiliary_variable);
$sda_account_id_table_account_sip = $sda_id_table_account;


$sda_query_table_account_sip[id] = "'$sda_id_table_account_sip'";
$sda_query_table_account_sip[address] = "'$sda_address_table_account_sip'";
$sda_query_table_account_sip[account_id] = "'$sda_account_id_table_account_sip'";

/* - - - - -  добавление записи в таблицу account_sip (КОНЕЦ)  - - - - - */



/* - - - - -  добавление записи в таблицу account_sip_caller (НАЧАЛО)  - - - - - */

$sda_id_table_account_sip_caller = $sda_sequence_number_table_account_sip_caller + 1;

$sda_display_name_table_account_sip_caller = $sda_from_whom;

$sda_impu_table_account_sip_caller = 'sip:' . $sda_from_whom . '@' . $sda_ip_address;

$sda_impi_table_account_sip_caller = $sda_from_whom;

$sda_realm_table_account_sip_caller = $sda_ip_address;

$sda_auxiliary_variable = $sda_from_whom . ':' . $sda_ip_address . ':' . $sda_from_whom;
$sda_ha1_table_account_sip_caller = MD5($sda_auxiliary_variable);

$sda_account_sip_id_table_account_sip_caller = $sda_id_table_account_sip;



$sda_query_table_account_sip_caller[id] = "'$sda_id_table_account_sip_caller'";

$sda_query_table_account_sip_caller[display_name] = "'$sda_display_name_table_account_sip_caller'";

$sda_query_table_account_sip_caller[impu] = "'$sda_impu_table_account_sip_caller'";

$sda_query_table_account_sip_caller[impi] = "'$sda_impi_table_account_sip_caller'";

$sda_query_table_account_sip_caller[realm] = "'$sda_realm_table_account_sip_caller'";

$sda_query_table_account_sip_caller[ha1] = "'$sda_ha1_table_account_sip_caller'";

$sda_query_table_account_sip_caller[account_sip_id] = "'$sda_account_sip_id_table_account_sip_caller'";

/* - - - - -  добавление записи в таблицу account_sip_caller (КОНЕЦ)  - - - - - */

$sda_action = 'stop';
include("addition_call_button.php");

$need_out = false;

$rows = array();
$rows[] = $sda_query_table_account;
$action = 'create_account';
include("create.php");

$need_out = false;

$rows = array();
$rows[] = $sda_query_table_account_sip;

$action = 'create_account_sip';
include("create.php");

$rows = array();
$rows[] = $sda_query_table_account_sip_caller;

$action = 'create_account_sip_caller';
include("create.php");

$conn->disconnect();

$sda_action = 'start';
include("addition_call_button.php");
?>