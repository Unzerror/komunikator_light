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

//$data = json_decode($HTTP_RAW_POST_DATA);
$input = file_get_contents("php://input");
$data = json_decode($input);

$rows = array();

if ($data && !is_array($data))
    $data = array($data);

$row = $data[0];  
$extensions = $row->extension;  
$values = array();
$pbx_values = array();
foreach ($row as $key => $value)
   if (in_array($key, array('forward', 'forward_busy', 'forward_noanswer', 'noanswer_timeout'))) {
        if (!empty($value))
             $pbx_values[$key] = "'$value'";
   } elseif (in_array($key, array('id','status', 'priority'))) {
     if ($key == 'priority')
          $priority = $value;
     } else {
        if ($key == 'group_name')
            $group_name = $value;
        else
           $values[$key] = "'$value'";
     }
$rows[] = $values;

$need_out = false;
include("create.php");




$rows = array();

// Нужна ли проверка на пустоту параметров - см. как работает маршрутизация по полям PBX

$result = query_to_array("SELECT extension_id FROM extensions WHERE extension = $extensions");
$extension_id = $result[0]['extension_id'];

if (!empty($pbx_values))
    foreach ($pbx_values as $pbx_key => $pbx_value) {
        $sql = "INSERT INTO pbx_settings (extension_id, param, value) VALUES ($extension_id, '$pbx_key', $pbx_value)";
        query($sql);
        $sql = "INSERT INTO actionlogs (date, performer, query, ip) VALUES (" . time() . ", \"{$_SESSION['user']}\", \"$sql\", \"{$_SERVER['REMOTE_ADDR']}\")";
        query($sql);       
    }

if (!empty($group_name)) {
    $result = query_to_array("SELECT group_id FROM groups WHERE groups.group = '$group_name'");
    $group_id = $result[0]['group_id'];
    if (!empty($priority)) {
        $sql = "INSERT INTO group_priority (group_id, extension_id, priority) VALUES ($group_id, $extension_id, $priority)";
        query($sql);
    }
    $rows[] = array('extension_id' => $extension_id, 'group_id' => $group_id);
    
}

$action = 'create_group_members';
include("create.php");
?>