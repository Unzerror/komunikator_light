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

 

function need_user() {
    if (!$_SESSION['user'] && !$_SESSION['extension']) {
        echo (out(array("success" => false, "message" => "User is undefined")));
        exit;
    }
}

function getparam($param) {
    $ret = null;
    if (isset($_POST[$param]))
        $ret = $_POST[$param];
    else if (isset($_GET[$param]))
             $ret = $_GET[$param];
         else
             return null;
    return $ret;
}

function compact_array($array) {
    $header = array();
    $data = array();
    if ($array)
        foreach ($array as $array_row) {
            $data_row = array();
            if ($array_row)
                foreach ($array_row as $key => $value) {
                    if (!count($data))
                        $header[] = $key;
                    $data_row[] = $value;
                }
            $data[] = $data_row;
        }
    return array('header' => $header, 'data' => $data);
}

function xml_section_build($dom, $root, $data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key))
                $key = 'item';
            $section = $dom->createElement($key);
            xml_section_build($dom, $section, $value);
        } else {
            if (is_numeric($key))
                $key = 'value' . $key;
            $section = $dom->createElement($key, $value);
        };
        $root->appendChild($section);
    }
}

function out($data) {
    $export = getparam("export");
    if ($export) {
        $columns = $data["header"];
        array_unshift($data["data"], $columns);
        $request_id = uniqid();//создается док-т с уникальным id
        $tmp = sys_get_temp_dir() . "/" . $request_id;//док -т имеет данное название
        $data = array("request_id" => $request_id, "success" => !(file_put_contents($tmp, json_encode($data["data"])) === false));
    }
    $type = getparam("type");
    if ($type == 'xml') {
        $dom = new DomDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $root = $dom->createElement("response");
        $dom->appendChild($root);
        xml_section_build($dom, $root, $data);
        header("Content-Type:text/xml;charset=UTF-8");
        return $dom->saveXML($root);
    }
    else
        return json_encode($data);
}

function get_filter() {
    $filters = parseExtJSFilters();
    return $filters ? " WHERE " . $filters : '';
}

function get_sql_order_limit() {
    $sort = getparam("sort") ? get_sql_field(getparam("sort")) : 1;
    $dir = getparam("dir") ? getparam("dir") : 'DESC';
    return get_filter() . " ORDER BY " . $sort . " " . $dir . get_sql_limit(getparam("start"), getparam("size"));
}

function get_sql_limit($start, $size/* ,$page */) {
    if (!(isset($start)) || !(isset($size)))
        return '';
    //  if ($start==null || $size==null) return '';
    global $db_type_sql;
    if ($db_type_sql == 'mysql')
        return " LIMIT $start,$size";
    return " LIMIT $size OFFSET $start";
}

function get_sql_field($name) {
    global $db_type_sql;
    if ($db_type_sql == 'mysql')
        return "`$name`";
    if ($db_type_sql == 'sqlite3')
        return "`$name`";
    return $name;
}

function get_SQL_concat($data) {
    global $db_type_sql;
    if (!is_array($data))
        return $data;
    if (count($data) == 0)
        return '';
    if (count($data) == 1)
        return $data[0];
    if ($db_type_sql == 'mysql') {
        $str = 'CONCAT(';
        $sep = '';
        foreach ($data as $el) {
            $str .= $sep . $el;
            $sep = ',';
        };
        return $str . ')';
    } else {
        $str = '';
        $sep = '';
        foreach ($data as $el) {
            $str .= $sep . $el;
            $sep = ' || ';
        };
        return $str;
    }
}

// - - перевод текста, возвращаем значение уже по рус/англ
function translate( $data, $lang = 'ru') {
    $file = "js/app/locale/" . $lang . ".js";
    if (!file_exists($file))
        return  $data;
    $text = file_get_contents($file);
// удаляем строки начинающиеся с #
    $text = preg_replace('/#.*/', '', $text);
// удаляем строки начинающиеся с //
    $text = preg_replace('#//.*#', '', $text);
// удаляем многострочные комментарии /* */
    $text = preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#', '', $text);

    $text = str_replace("\r\n", '', $text);
    $text = str_replace("\n", '', $text);

    $text = preg_replace('/(.*app\.msg\s*=\s*)({.*})(\s*;.*)/', '$2', $text);
    $text = preg_replace('/([{,])([\s\"\']*)([\w\(\)\[\]\,\_]+)([\s\"\']*):\s*\"([^"]*)\"/', '$1"$3":"$5"', $text);
    $text = preg_replace('/([{,])([\s\"\']*)([\w\(\)\[\]\,\_]+)([\s\"\']*):\s*\'([^\']*)\'/', '$1"$3":"$5"', $text);

    $words = json_decode($text, true);
    if ($data && $words)
        foreach ( $data as &$row)
            foreach ($row as $key => $el)
                foreach ($words as $word => $value) {
                    if ($word == $el)
                        $row[$key] = $value;
                }
    return  $data;  
}
function parseExtJSFilters() {
    if (getparam('filter') == null) {
        // No filter passed in
        return false;
    };

    $filters = json_decode(getparam('filter')); // Decode the filter
    if ($filters == null) { // If we couldn't decode the filter
        return false;
    }
    $whereClauses = array(); // Stores whereClauses
    foreach ($filters as $filter) {
        switch ($filter->type) {
            case 'boolean':
                $filter->value = ($filter->value === true) ? '1' : '0'; // Convert value for DB
                $whereClauses[] = "$filter->field = $filter->value";
                break;
            case 'date':
                //$filter->value = "'$filter->value'"; // Enclose data in quotes
                $filter->value = strtotime($filter->value); // Enclose data in quotes
            case 'numeric':
                switch ($filter->comparison) {
                    case 'lt': // Less Than
                        $whereClauses[] = "$filter->field < $filter->value";
                        break;
                    case 'gt': // Greather Than
                        $whereClauses[] = "$filter->field > $filter->value";
                        break;
                    case 'eq': // Equal To
                        if ($filter->type == 'date') {
                            $whereClauses[] = "$filter->field < $filter->value+60*60*24";
                            $whereClauses[] = "$filter->field > $filter->value";
                        }
                        else
                            $whereClauses[] = "$filter->field = $filter->value";
                        break;
                }
                break;
            case 'list':
                $listItems = array();
                if (!count($filter->value))
                    break;
                foreach ($filter->value as $value) {
                    $listItems[] = "'$value'";
                };
                $whereClauses[] = "$filter->field IN(" . implode(',', $listItems) . ')';
                break;
            case 'string':
            default: // Assume string
                $whereClauses[] = "(
                    $filter->field LIKE '{$filter->value}%' OR
                    $filter->field LIKE '%{$filter->value}' OR 
                    $filter->field LIKE '%{$filter->value}%' OR
                    $filter->field = '{$filter->value}'
                )";
                break;
        }
    }
    if (count($whereClauses) > 0) {
        return implode(' AND ', $whereClauses);
    }
    return false;
}

$macro_sql = array(
    'caller_called1' => ' a.caller, b.called, ',
    'caller_called' =>
    <<<EOD
		case when (select firstname from extensions where extension = a.caller) is not null then 
		CONCAT((select firstname from extensions where extension = a.caller),' ',
					 (select lastname  from extensions where extension = a.caller),' (',a.caller,')') 
		else 
		a.caller end caller , 
	  case when (select firstname from extensions where extension = b.called) is not null then 
		CONCAT((select firstname from extensions where extension = b.called),' ',
					 (select lastname  from extensions where extension = b.called),' (',b.called,')') 
		else 
		b.called end called , 

EOD
        )
?>
