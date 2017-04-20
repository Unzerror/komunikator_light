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
 

need_user();

$input = file_get_contents("php://input"); 
$data = json_decode($input);
$rows = array();
$exten = $_SESSION['caller'];
$caller_group_id = "''";
$called_group_id = "''";
if ($data && !is_array($data))
    $data = array($data);

foreach ($data as $row) {
    $values = array();
    foreach ($row as $key => $value) {
        if ($key == 'id') {
            $id = $key;
        } else {
            if ($key == 'caller_group' && $value != '') {
                $caller_group = $value;
                $sql = "SELECT group_id FROM groups WHERE groups.group = '$caller_group'";
                $result1 = query_to_array($sql);
                $caller_group_id = $result1[0]['group_id'];
            }
            if ($key == 'called_group' && $value != '') {
                $called_group = $value;
                $sql = "SELECT group_id FROM groups WHERE groups.group = '$called_group'";
                $result1 = query_to_array($sql);
                $called_group_id = $result1[0]['group_id'];
            } else {
                
            }
            if ($key == 'gateway' && $value != '' && $value != '*') {
                $gateway = $value;
                $sql = "SELECT gateway_id FROM gateways WHERE gateways.gateway = '$gateway'";
                $result1 = query_to_array($sql);
                $gateway_id = $result1[0]['gateway_id'];
                $values['gateway'] = "'$gateway_id'";
            } else {
                $values[$key] = "'$value'";
            }
        };
    }
    $rows[] = $values;
    $rows[0]['caller_group'] = $caller_group_id;
    $rows[0]['called_group'] = $called_group_id;
    if ($rows[0]['caller_number'] == "''" && $rows[0]['caller_group'] == "''") {
        $rows[0]['caller_number'] = "'*'";
    }
    if ($rows[0]['called_number'] == "''" && $rows[0]['called_group'] == "''") {
        $rows[0]['called_number'] = "'*'";
    }
    if ($rows[0]['type'] == "''") {
        $rows[0]['type'] = "'*'";
    }
    if ($rows[0]['gateway'] == "''") {
        $rows[0]['gateway'] = "'*'";
    }
}

require_once("create.php");
?>