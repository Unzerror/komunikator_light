#!/usr/bin/php -q
<?php
/*
 *  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 *    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
 *    Copyright (C) 2012-2017, ООО «Телефонные системы»
 *    ЭТОТ ФАЙЛ является частью проекта «Komunikator»
 *    Сайт проекта «Komunikator»: http://komunikator.ru/
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
 *    Copyright (C) 2012-2017, "Telephonnyie sistemy" Ltd.
 *    THIS FILE is an integral part of the project "Komunikator"
 *    "Komunikator" project site: http://komunikator.ru/
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

 require_once("libyate.php");
require_once("lib_queries.php");

$type_debug = 'register';

$gateway_ev = array();
$callFrom = '';                      //проблемы - используется call.route - переписать код с ней!!!

$s_statusaccounts = array();

$next_time = 0;            //время апдейта статусов
$time_step = 90;           //шаг апдейта статусов
$channel_type = array ("sip", "iax");     //типы каналов


//типы сообщений
//
//Убрал в функцию
$msg_keys["type"]["base_type"] = ["cdr","connect","full","history"];
$msg_keys["type"]["cdr"] = ["call.cdr","chan.startup"];
$msg_keys["type"]["connect"] = ["chan.connected","chan.disconnected","call.answered","chan.hangup"];
$msg_keys["type"]["dynamic"] = ["route.register","rec.vm"];


//Работа с cdr
//параметры Cdr сообщений
$msg_keys["cdr"]["param"] = ["time", "id", "chan", "address","direction","billid","caller","called","duration",
                             "billtime","ringtime","status","reason","callid","operation","calledfull",
                             "username","timestamp","callnumber","callbillid","ended","gateway","SQL"];
/*@"SQL" - insert,update,delet=update  + для виртуальных сущностей unset без SQL
@"type"  - тип исходного
*/
//SQL таблица хранения данных
$msg_keys["cdr"]["sql_table"] = ("call_logs");
//записываемые в SQL параметры
$msg_keys["cdr"]["sql"] = ["time", "chan", "address","direction","billid","caller","called","duration",
                           "billtime","ringtime","status","reason","callid",//"calledfull",
                           "callbillid","ended","gateway"];
//SQL ключи для записи или апдейта
$msg_keys["cdr"]["sql_key"]   = ["time", "chan"];

//полное сообщение
$msg_keys["full"]["param"] = ["timestamp","operation","time","connect","disconnect","answer",
                              "chan","id","address","direction","billid","callbillid","connect_type",
                              "peerid","targetid","lastpeerid","username",
                              "callnumber","caller","called","calledfull","callid",
                              "caller_gateway","called_gateway","caller_type","called_type",
                              "gateway",
                              "duration","billtime","ringtime",
                              "status","reason","ended"];
//сообщение об соединениях
//поменял CHAN на ID!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
$msg_keys["connect"]["param"] = ["timestamp","operation","connect","disconnect","answer",
                                 "id","direction","billid","callbillid",
                                 "chan","peerid","targetid","lastpeerid","username","address",
                                 "callnumber","caller","called","calledfull","callid",
                                 "caller_gateway","called_gateway","caller_type","called_type",                                 
                                 "duration","billtime","ringtime",
                                 "status","reason","ended","SQL"];
$msg_keys["connect"]["sql"] = ["connect","disconnect","answer","chan","peerid","targetid","billid","callbillid",
                               "caller","called","caller_gateway","called_gateway","caller_type","called_type",
                               "status","reason"];
$msg_keys["connect"]["sql_table"] = ("chan_switch");                            
$msg_keys["connect"]["sql_key"] = ["connect", "chan"];

$msg_keys["activ_conf_room"]["param"] = ["connect","disconnect","chan","targetid","billid","callbillid","caller","called","caller_gateway","caller_type"];//,"SQL"];
$msg_keys["activ_conf_room"]["sql_key"]   = ["connect", "chan"];

$msg_keys["queue"]["param"] = ["chan","peerid","targetid","billid","callbillid","caller","called"];//,"SQL"];

$msg_keys["gateways"]["param"] = ["account","gateway","protocol","server","username","enabled","description","domain",
                                  "localaddress","status","interval","callerid","callername","send_extension"];

$msg_keys["history"]["param"] = ["connect","disconnect","answer","duration","connect_type","chan","peerid",
                                 "billid","callbillid","caller","called",
                                 "caller_gateway","called_gateway","caller_type","called_type","record",
                                 "ended","status","reason","SQL"];
$msg_keys["history"]["sql"] = ["connect","duration","connect_type","callbillid","caller","called",
                                 "caller_gateway","called_gateway","caller_type","called_type","record",
                                 "ended","status","reason"];
$msg_keys["history"]["sql_table"] = ("history");
$msg_keys["history"]["sql_key"] = ["connect","caller","called","callbillid"];



class YMessage {
   public $param = array();
   public $type;
   private $keys = array();   

   //* версия 1
   function __construct($type) {
      global $msg_keys;
      $this->type = $type;
      if ($type !== "dynamic")      
          $this->keys = $msg_keys[$type]["param"];    
   }

   function ReadMsg($ev) {
       if ($this->type == "dynamic")
            $this->keys = explode(";",$ev->GetValue("keys"));
       
       foreach ($this->keys as $row)
             $this->param[$row] = $ev->GetValue($row);
       $this->param["timestamp"] = microtime(true);
       $this->param["operation"] = is_null($this->GetValue("operation")) ? $ev->name : $this->param["operation"];
       
       $parametrs = $ev->GetValue("dynamic_parametrs");
       if (!is_null($parametrs)) {
            $dynamic_parametrs= explode(";",$parametrs);
            foreach($dynamic_parametrs as $name)  {
                $indx = 0;
                $this->param[$name] = $ev->GetValue($name);                
                while (!is_null($ev->GetValue($name.".".$indx)))
                    $this->param[$name.".".$indx] = $ev->GetValue($name.".".$indx++);
            }
        } 
    }

   function msgToSQL($operation = "") {
       global $msg_keys;

       if ($operation == "")
            $operation = $this->GetValue("SQL");

       $sql_data = $this->reduceMessage("sql");
       $id = $msg_keys[$this->type]["sql_key"];
       foreach ($id as $s_key) {
            if (!isset($sql_data[$s_key]))
                  $operation = "no_sql";
       }
       
       $sql_data = array_diff($sql_data, array(''));
       if ($operation == "insert") {
            $query = "INSERT INTO ".$msg_keys[$this->type]["sql_table"]." (`".implode("`, `", array_keys($sql_data))."`) VALUES ('".implode("', '", $sql_data)."')";    
       } elseif ($operation == "update" or $operation == "delete") {            
            foreach ($sql_data as $key => $value) {
                if (in_array($key,$id))
                     $condition[] = "`".$key."`". " = '".$value."'";
                else
                     $updates[] = "`".$key."`". " = '".$value."'";
            }
            $query = sprintf("UPDATE %s SET %s WHERE %s", $msg_keys[$this->type]["sql_table"], implode(', ', $updates), implode(' and ', $condition));
       } else
          return false;
       query_nores($query);
       return true;
   }

    function reduceMessage($type) {
        global $msg_keys;
        $keys = $msg_keys[$this->type][$type];
        foreach ($keys as $row)
           $new_data[$row] = empty($this->param[$row]) ? NULL : $this->param[$row];
        return $new_data;
    }

    function convertMessageType($type) {
        global $msg_keys;

        $this->type = $type;
        $this->keys = $msg_keys[$type]["param"];
        $data = $this->param;
        $this->param = array();        
        foreach ($this->keys as $row)
                 $this->param[$row] = empty($data[$row]) ? NULL : $data[$row];
    }

    function GetValue($key, $defvalue = null) {
        if (isset($this->param[$key]))
            return $this->param[$key];
        return $defvalue;
    }

    function CopyDataFromMsg($source_msg) {
        foreach ($this->keys as $row) {            
            $this->param[$row] = $source_msg->GetValue($row);
        }
    }

    function UpdateFromMessage($source_msg) {
        foreach ($this->keys as $row)
            $this->param[$row] = is_null($source_msg->GetValue($row)) ? $this->param[$row] : $source_msg->GetValue($row);
    }

    function UpdateValue($key, $value) {
        $this->param[$key] = $value;
    }

    function InsertRowData($row_data) {
         foreach ($this->keys as $key)
              $this->param[$key] = isset($row_data[$key]) ? $row_data[$key] : NULL;
    }

    function LogMsg($InitStr = "") {
        debug("msg[".$this->type."]='".implode("','", $this->param)."'");
    }
}

//Данные для обработки
//перечень хранимых данных
$data_strct["cdr"]["keys"] = ["time","chan","address","direction","billid","callbillid","caller","called",
                              "callnumber","duration","billtime","ringtime","status","reason","ended","gateway","callid","SQL"];
//SQL источник данных при загрузке " `table_name` WHERE `a`=12"
$data_strct["cdr"]["source"] = ("`activ_channels` ORDER BY `time`");  
//SQL считываемые данные
//$data_strct["cdr"]["sql_data"] = $data_strct["cdr"]["keys"];

$data_strct["connect"]["keys"] = ["connect","disconnect","answer","chan","peerid","targetid","billid","callbillid",
                                            "caller","called","caller_gateway","called_gateway","caller_type","called_type",
                                            "connect_type","status","reason","SQL"];
$data_strct["connect"]["source"] = ("activ_connections");
//$data_strct["connect"]["sql_data"] = $data_strct["connect"]["keys"];

//Шлюзы
//SELECT description FROM gateways WHERE status='online' and 
//(username = '".$ev->GetValue("username")."' or "."username = '".$ev->GetValue("caller")."') LIMIT 1
$data_strct["gateways"]["keys"] = ["gateway","protocol","server","username","enabled","description","domain",
                                    "localaddress","status","interval","callerid","callername","send_extension","SQL"];
$data_strct["gateways"]["source"] = ("gateways");
//$data_strct["gateways"]["sql_data"] = $data_strct["gateways"]["keys"];
$data_strct["activ_conf_room"]["keys"] = ["start","connect","disconnect","duration","chan","targetid","billid","callbillid","root_peer",
                                          "caller","called","caller_gateway","caller_type"];//,"SQL"];
$data_strct["activ_conf_room"]["source"] = ("activ_conf_room");
$data_strct["queue"]["keys"] = ["chan","targetid","billid","callbillid","caller","called"];//,"SQL"];
$data_strct["queue"]["source"] = ("activ_queue");


$data_strct["history"]["keys"] = ["connect","duration","connect_type","chan","peerid","billid","callbillid","caller","called",
                                  "caller_gateway","called_gateway","caller_type","called_type","record",
                                  "ended","status","reason","SQL"];
$data_strct["history"]["source"] = ("`history` WHERE `ended`=0");
//$data_strct["history"]["sql_data"] = $data_strct["gateways"]["keys"];


class ActivObjects
{    
    private $keys = array();
    private $type ;
    public $events = array();
    private $events_org = array();

    function __construct($type) {
        global $data_strct;        
        
        $this->type = $type;
        $this->keys = $data_strct[$type]["keys"];
        $this->ReadActiveData();        
    }

    function ReadActiveData() {
        global $data_strct;
        
        $sql="SELECT * FROM ".$data_strct[$this->type]["source"];
        $res = query_to_array($sql);
        foreach ($res as $ev_indx => $row)                
            foreach ($this->keys as $arg_indx => $value)
                      $this->events[$ev_indx][$arg_indx] = empty($row[$value]) ? NULL : $row[$value];
    }

    /*индексы ключей массива 
     *$keys: NULL - все, "chan" -конкретный №, array = ["chan","time"] - список*/
    function colFind($keys = NULL) {
        global $data_strct;
        if (is_array($keys))
            foreach ($keys as $indx => $key)
                      $col[$indx] = array_search($key,$data_strct[$this->type]["keys"]);
        elseif (is_null($keys))
            $col = range(0,count($data_strct[$this->type]["keys"])-1);
        else
            $col = array_search($keys,$data_strct[$this->type]["keys"]);             
        return $col;    
    }

    /*№ ключа в ключевое слово
     *$cols: NULL - все, "1" -конкретнле значение, array = [1,0,5] - список */
    function keyFind($cols = NULL) {
        global $data_strct;
        if (is_array($cols))
             foreach ($cols as $indx => $col)
                       $key[$indx] = $data_strct[$this->type]["keys"][$col];
        elseif (is_null($cols))
            $key = $data_strct[$this->type]["keys"];
        else
           $key = $data_strct[$this->type]["keys"][$cols];
        return $key;    
    }

    function getCellValue($col,$row)  {
        global $data_strct;
        if ( is_numeric($row)&& is_numeric($col))
            if ($row>=0 && $row<count($this->events))
                if ($col>=0 && $col<count($data_strct[$this->type]["keys"]))
                     return $this->events[$row][$col];
        return NULL;
    }
    
    function getCellValueFromKey($key,$row)  {
        $col = $this->colFind($key);
        return $this->getCellValue($col,$row);
    }
    
    function GetValue($keys = NULL, $rows = NULL, $direct = "key") {
        $cols = $this->colFind($keys);
        if(is_null($rows)) 
            $rows = range(0,count($this->events)-1);
        if(!is_array($cols)) {
            if (!is_array($rows)) 
                return $this->getCellValue($cols,$rows);
            $cols = array($cols);            
        }
        if (!is_array($rows))
            $rows = array($rows);
        $res = array();
        foreach ($rows as $row_indx => $row)            
            foreach ($cols as $col)
                if ($direct == "key")             
                   $res[$this->keyFind($col)][$row_indx] = $this->getCellValue($col,$row);
                elseif ($direct == "rows")
                   $res[$row_indx][$this->keyFind($col)] = $this->getCellValue($col,$row);
                   //$res[$row_indx][$this->keyFind($col)] = $this->getCellValue($col,$row);   //было, как лучше получать данных???
                                                                                               //лучше строками или столбцами???
        return $res;
    }

    function SearchRowWithValue($keys, $value = [""], $row_number = null) {
        $res = array();

        $cols = $this->colFind($keys);
        if(!is_array($cols)) {
            $cols = [$cols];
        }
        if(!is_array($value)) {
            $value = [$value];
        }

        if (count($this->events) > 0) {
            if(!isset($row_number)) 
               $row_number = range(0,count($this->events)-1);
            foreach ($cols as $col) {
               if (is_int($col))
                  foreach ($row_number as $row) {
                     if (array_search($this->events[$row][$col],$value) !== FALSE) {
                          $res["row"][] = $row;
                          $res["col"][] = $col;
                          $res["value"][] = $this->events[$row][$col];
                     }
                  }
            }
        }
        return $res;
    }

    function MessageInsert($msg) {
        global $data_strct;

        $indx = count($this->events);
        foreach ($this->keys as $col=>$key)
            $this->events[$indx][$col] = $msg->GetValue($key);
        if(!empty($this->colFind("SQL")))
            $this->events[$indx][$this->colFind("SQL")] = "insert";
    }

    function DeletRow($rows){
        rsort($rows);
        foreach ($rows as $row) {
           if (array_key_exists($row, $this->events)) {
                   unset($this->events[$row]);
                   $this->events = array_values($this->events);            
           }
        }
    }

    function UpdateValue($key,$rows,$value) {
        if (!is_array($rows))
             $rows = [$rows];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                  $this->events[$row][$this->colFind($key)] = $value;                  //проверить если $row=3 ???
                  if(!empty($this->colFind("SQL")))
                      if ( is_null($this->events[$row][$this->colFind("SQL")]))
                            $this->events[$row][$this->colFind("SQL")] = "update";
            }
        }
    }

    function UpdateFromMessage($row,$msg) {
        foreach ($this->keys as $col=>$key) {                        
             $this->events[$row][$col] = isset($msg->param[$key]) ? $msg->GetValue($key) : $this->events[$row][$col];
        }
        if(!empty($this->colFind("SQL")))
            $this->events[$row][$this->colFind("SQL")] = ($this->events[$row][$this->colFind("SQL")] == "insert") ? "insert" : "update";        
    }

    function UpDateMsg($msg) {
        global $msg_keys;
        
        $keys = $msg_keys[$this->type]["sql_key"];
        foreach ($keys as $s_key) {
            if (is_null($msg->GetValue($s_key)))
                return false;
            else
                $key_value [] = $msg->GetValue($s_key);
        }
        
        $result = $this->SearchRowWithValue($keys,$key_value);
        if(!empty($result)) {            
            $rows = array_count_values($result["row"]);
            foreach ($rows as $row=>$count) {
                if (current($rows)>=count($keys)) {
                     $this->UpdateFromMessage(key($rows),$msg);
                     return true;
                }
            }
        }
        $this->MessageInsert($msg);
        return true; 
    }

    function GetRow($row,$diff="normal") {
        global $data_strct;
        if ($diff=="normal") {
             foreach ($data_strct[$this->type]["keys"] as $indx => $key)
                 $res[$key] = $this->events[$row][$indx];
        } else {
            foreach ($data_strct[$this->type]["keys"] as $indx => $key)
                 $res[$key] = ($this->events[$row][$indx] == $this->events_org[$row][$indx]) ? NULL : $this->events[$row][$indx];
        }
        return $res;
    }

    function LogTable()  {
        foreach ($this->events as $indx=>$data)
           debug($this->type."[".$indx."]='".implode("','", $data)."'");
    }

    //получение msg из строки для SQL
    function GetMsgFromRow($row, $type, $dtype="normal") {        
        if ($dtype == "normal")
             $diff="normal";
        else {
            /*if($this->getCellValueFromKey("SQL",$row) == "insert")   >>>>>>>>>>>>>Пока без дифф записи
                $diff="normal";
            else
                $diff="diff";*/
            $diff="normal";
        }
        
        $msg = new YMessage($type);
        $msg->InsertRowData($this->GetRow($row,$diff));
        return $msg;
    }
    
    //Дифф копия для UPDATE-ов
    function FixDiff($rows = "",$type="no_full") {
        $this->events_org = $this->events;
        //if (($type == "full") and                              //>>>>>>>>>>>>>>>>Добавить по строкам и для фиксации DELET
        //     $this->
    }

    function DataToMySQL($rows = null) {

        $count_events = count($this->events);        
        if ($count_events>0)  {            
            if (is_null($rows))  {
                $all_rows = range(0,$count_events-1);
                $res = $this->SearchRowWithValue("SQL");
                if (empty($res))
                     $rows = $all_rows;
                else
                    $rows = array_diff($all_rows, $res["row"]);                
            }            
            if(empty($rows))
                 return true;
            
            $type = $this->type;
            $msg = new YMessage($type);
            foreach ($rows as $indx=>$row) {
                $msg->InsertRowData($this->GetRow($row));                
                if( $msg->msgToSQL() )
                    $this->events[$row][$this->colFind("SQL")] = NULL;                
            }
        }
    }

    //поиск не пустых или заданного типа
    function SearchRowWithType($key,$type = null,$number_rows = null) {
        $res = array();
        $count_events = count($this->events);
        $col = $this->colFind($key);
        if($count_events>0 and isset($col))  {
            if (!isset($number_rows))
                 $number_rows = range(0,$count_events-1);
            $result = $this->SearchRowWithValue($key,[""],$number_rows);
            if (empty($result))
                $rows1 = $number_rows;
            else
                $rows1 = array_diff($number_rows, $result["row"]);
            if (isset($type)) {
                foreach($rows1 as $row)
                   if ( chektype($this->getCellValue($col,$row)) == $type )
                         $res[] = $row;
            } else
                $res = $rows1;
        }
        return $res;
    }
}


function ClearActiveTable($msg) {
    global $activ_channels;    
    global $activ_connections;
    global $call_history;
    global $conf_room;
    global $active_queue;                                                            //удалять в другом месте

    if ($msg->param["operation"] == "finalize") {
        $callbillid = $msg->GetValue("callbillid");
        $active_peers = $activ_channels->SearchRowWithValue("callbillid",$callbillid);
        $ends_peers = $activ_channels->SearchRowWithValue("ended",0,$active_peers["row"]);
        if (empty($ends_peers)) {
             $activ_channels->DeletRow($active_peers["row"]);
             $active_con = $activ_connections->SearchRowWithValue("callbillid",$callbillid);
             if (!empty($active_con))
                   $activ_connections->DeletRow($active_con["row"]);

             $history = $call_history->SearchRowWithValue("callbillid",$callbillid);             
             if (!empty($history)) {
                //call_rec.finalize             
                $calls_rec = $call_history->GetValue("record",$history["row"]);
                $ended_recid = array_diff($calls_rec["record"], array(''));
                $m = new Yate("register.endcall");
                $m->params["dynamic_parametrs"] = "record";
                $id =0;
                foreach ($ended_recid as $recid) 
                   $m->params["record.".$id++] = $recid;
                if ($id>0)
                    $m->Dispatch();

                $call_history->DeletRow($history["row"]);
             }
             $room = $conf_room->SearchRowWithValue("callbillid",$callbillid);
             if (!empty($room))
                   $conf_room->DeletRow($room["row"]);

        }
    }    
}

function RegisterInfo($msg)   {
    global $call_history;
    global $conf_room;

    $new_keys = array();
    $dynamic_info_keys["call"] = ["connect","duration","chan","peerid","callbillid","caller","called",
                                  "caller_gateway","called_gateway","caller_type","called_type","record","ended"];
    $dynamic_info_keys["conf"] = ["called","callbillid","record","ended","dynamic_parametrs"];
    $dynamic_info_keys["conf.dynamic_parametrs"] = ["connect","chan","callbillid","caller","caller_gateway","caller_type"];

    $new_events = $call_history->SearchRowWithType("SQL");

    if (!empty($new_events)) {
        $m = new Yate("register.info");

        $calls = $call_history->SearchRowWithType("connect_type","call",$new_events);
        if (!empty($calls)) {
            $new_keys[] = "call";
            $m->params["call"] = implode(";", $dynamic_info_keys["call"]);
            $new_data = 0;
            $call_data = $call_history->GetValue($dynamic_info_keys["call"],$calls,"rows");
            foreach ($call_data as $data)                
                $m->params["call.".$new_data++] = implode("|", $data);
        }
        
        $confs = $call_history->SearchRowWithType("connect_type","conf",$new_events);
        if (!empty($confs)) {

            $new_keys[] = "conf";
            $m->params["conf"] = implode(";", $dynamic_info_keys["conf"]);
            $m->params["conf.dynamic_parametrs"] = implode(";", $dynamic_info_keys["conf.dynamic_parametrs"]);
            $conf_count = 0;
            
            $conf_rooms = $conf_room->SearchRowWithValue("connect");                                //постоянные конференции
            $open_cnf_rows = $call_history->SearchRowWithValue("ended",0,$confs);
            if (!empty($open_cnf_rows)) {

                
                
                $close_cnf_rows = array_diff($confs,$open_cnf_rows["row"]);
                
                $open_rows = $call_history->GetValue("called",$open_cnf_rows["row"]);

                
                $open_data = $call_history->GetValue($dynamic_info_keys["conf"],$open_cnf_rows["row"],"rows");
                foreach ($open_data as $data) {
                    $m->params["conf.".$conf_count] = implode("|", $data);
                    $peer = $conf_room->SearchRowWithValue("called",$data["called"]);
                    $act_peer = $conf_room->SearchRowWithValue("disconnect",[""],$peer["row"]);
                    $active = array_diff($act_peer["row"],$conf_rooms["row"]);
                    $active_data = $conf_room->GetValue($dynamic_info_keys["conf.dynamic_parametrs"],$active,"rows");
                    $act_count = 0;
                    foreach ($active_data as $a_data) {
                        $m->params["conf.".$conf_count.".".$act_count++] = implode("|", $a_data);
                    }
                    $conf_count++;
                }
            } else {
                $close_cnf_rows = $confs;
                $open_rows["called"] = array();
            }
            
            if (!empty($close_cnf_rows)) {
                $close_rows = $call_history->GetValue("called",$close_cnf_rows);
                $close_num = array_unique($close_rows["called"]);
                $open_num = array_unique($open_rows["called"]);
                $full_close = array_diff($close_num,$open_num);
                if(!empty($full_close)) {
                    $full_close_rows = $call_history->SearchRowWithValue("called", $full_close, $close_cnf_rows);                    
                    $close_data = $call_history->GetValue($dynamic_info_keys["conf"], $full_close_rows["row"],"rows");
                    foreach ($close_data as $s_data) {
                        $m->params["conf.".$conf_count++] = implode("|", $s_data);                        
                    }
                }
            }
        }       

        //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        //Добавить очередь и дозвон на кого идет
        //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        if (!empty($new_keys)) {
            $m->params["dynamic_parametrs"] = implode(";", $new_keys);            
            $m->Dispatch();
        }
    }
}

function closePhpScripts($msg) {
    global $activ_connections;

    if (($msg->param["operation"] == "chan.hangup")  and ($msg->param["peerid"] == "ExtModule")) {
        
        $type = chektype($msg->GetValue("targetid"));
        if (in_array($type, ["auto_attendant", "leavemaildb"]))
            $ourcallid = $msg->GetValue("targetid");
        else {
            $rows = $activ_connections->SearchRowWithValue("disconnect",$msg->GetValue("disconnect"));
            if (!empty($rows))
                 $ourcallid = $activ_connections->getCellValueFromKey("peerid",$rows["row"][0]);
            else
                return false;
        }
        $m = new Yate("chan.hangup");        
        $m->params["id"] = $ourcallid;
        $m->params["billid"] = $msg->GetValue("billid");
        $m->params["answered"] = 'true';        
        $m->Dispatch();
    }
}

function UpdateHistory($msg)  {
    global $call_history;
    global $activ_connections;
    global $conf_room;

    $delta_time = 0.2;
    
    $new_data_rows = $activ_connections->SearchRowWithType("SQL");    
    if (!empty($new_data_rows)) {
        $conf_rows = $activ_connections->SearchRowWithValue("connect_type","conf",$new_data_rows);        
        if(!empty($conf_rows)) {            
            $new_data_rows = array_diff($new_data_rows,$conf_rows["row"]);                                    //исключение конференций
            
            //$callbill_ids = $activ_connections->GetValue("callbillid",$conf_rows["row"]);
            $targets_ids = $activ_connections->GetValue("targetid",$conf_rows["row"]);
            $targets = array_count_values($targets_ids["targetid"]);
            
           $conf_rows_static = $conf_room->SearchRowWithValue("connect");
           if(empty($conf_rows_static)) {
                $conf_rows_static["row"] = array();
           }

           foreach ($targets as $targetid=>$count) {
                $conf_rows_targets = $conf_room->SearchRowWithValue("targetid",$targetid);
                $conf_rows_targets["row"] = array_diff($conf_rows_targets["row"],$conf_rows_static["row"]);                                  //все 

                $conf_connect_time = $conf_room->GetValue(["connect","disconnect"],$conf_rows_targets["row"]);
                $time_connects = array_merge($conf_connect_time["connect"],$conf_connect_time["disconnect"]);
                $time_connects = array_diff($time_connects, array(''));
                rsort($time_connects);                                                 //поток событий                
                $duration = 0;
                if (count($time_connects)>1)    {
                    $history_conf = $call_history->SearchRowWithValue("connect",$time_connects[1]);
                    if (!empty($history_conf)) {
                        if(count($history_conf["row"])>1) {
                            $history_conf1 = $call_history->SearchRowWithValue("connect_type","conf",$history_conf["row"]);
                            $history_conf["row"] = $history_conf1["row"];                              
                        }
                        if (!empty($history_conf)) {
                            $conf_msg = new YMessage("history");
                            $conf_msg->param["connect"] = $time_connects[1];                            
                            $conf_msg->param["ended"] = 1;
                            $conf_msg->param["SQL"] = "update";
                            $duration = $time_connects[0] - $time_connects[1];
                            $conf_msg->param["duration"] = $duration > $delta_time ? (int)round($duration) : -1;
                            $call_history->UpdateFromMessage($history_conf["row"][0],$conf_msg);
                        }
                    }
                }
                $history_conf_new = $call_history->SearchRowWithValue("connect",$time_connects[0]);
                if (!empty($history_conf_new)) {
                    $history_conf_new = $call_history->SearchRowWithValue("connect_type","conf",$history_conf["row"]);
                }
                if (empty($history_conf_new)) {
                    $conf_active = $conf_room->SearchRowWithValue("disconnect",[""],$conf_rows_targets["row"]);                    
                    if(!empty($conf_active)) {
                        $conf_msg = new YMessage("history");
                        $conf_msg->param["connect"] = $time_connects[0];                        
                        $conf_msg->param["connect_type"] = "conf";
                        $conf_msg->param["ended"] = 0;                        
                        $conf_msg->param["SQL"] = "insert";

                        $conf_msg->param["callbillid"]= $conf_room->getCellValueFromKey("callbillid",$conf_active["row"][0]);
                        $conf_msg->param["called"]= $conf_room->getCellValueFromKey("called",$conf_active["row"][0]);
                        $caller = $conf_room->GetValue("caller",$conf_active["row"]);
                        $callers = implode("/", $caller["caller"]);
                        $conf_msg->param["caller"] = $callers;
                        //$conf_msg->param["record"] = $duration > $delta_time ? $call_history->getCellValueFromKey("record",$history_conf["row"][0]) : $time_connects[0].uniqid();
                        if ( $duration > 0  and $duration < $delta_time) {
                            $conf_msg->param["record"] = $call_history->getCellValueFromKey("record",$history_conf["row"][0]);
                        } else
                            $conf_msg->param["record"] = $time_connects[0].uniqid();
                        $call_history->MessageInsert($conf_msg);
                    }
                }
            }        
            //Закрытие Конференций и открытие новых в истории
        }
        
        foreach($new_data_rows as $new_data_row) {
            $msg = $activ_connections->GetMsgFromRow($new_data_row,"history");            
            if (!is_null($msg->GetValue("caller")) && !is_null($msg->GetValue("called")) && !is_null($msg->GetValue("callbillid")) && !is_null($msg->GetValue("connect"))) {
                if ($msg->getValue("SQL") == "update")  {
                    $connect = $msg->GetValue("connect");
                    $rows_history = $call_history->SearchRowWithValue("connect",$connect);                    
                    if (count($rows_history["row"])>1) {
                        $row_add = $call_history->SearchRowWithValue("callbillid", $msg->GetValue("callbillid"), $rows_history["row"]);         //$rows["row"][0]
                        $rows_history["row"] = $row_add["row"];
                    }
                    if (!empty($rows_history))   {
                        switch ($msg->GetValue("connect_type")) {
                            case "fork":
                                $caller = $msg->GetValue("called");
                                $caller_gate = $msg->GetValue("called_gateway");
                                $caller_direction = $msg->GetValue("called_type");
                                $msg->param["called"] = $msg->GetValue("caller");
                                $msg->param["called_gateway"] = $msg->GetValue("caller_gateway");
                                $msg->param["called_type"] = $msg->GetValue("caller_type");
                                $msg->param["caller"] = $caller;
                                $msg->param["caller_gateway"] = $caller_gate;
                                $msg->param["caller_type"] = $caller_direction;
                                break;                            
                            case "telephony":
                                $caller = $msg->GetValue("caller");
                                $answer = $msg->GetValue("answer");
                                //$connect = $msg->GetValue("connect");                                
                                if(is_null($answer)) {
                                    $msg->param["connect_type"] = "fork";
                                } else {
                                    $msg->param["connect_type"] = "call";
                                    $delta = $answer - $connect;
                                    if ($delta > $delta_time)  {                                                        //порог разделения событий - можно ввести больший запас                                         
                                        $msg->param["connect"] = $answer;                                        
                                        if (is_null($call_history->getCellValueFromKey("ended",$rows_history["row"][0])))    {                                              
                                              $msg->param["SQL"] = "insert";
                                              $call_history->MessageInsert($msg);
                                              $msg->param["duration"] = (int)round($delta);
                                              $msg->param["connect_type"] = "fork";
                                              $msg->param["connect"] = $connect;
                                              $msg->param["ended"] = 1;
                                              $msg->param["SQL"] = "update";
                                        }
                                    }  elseif (is_null($call_history->getCellValueFromKey("record",$rows_history["row"][0])))    {
                                        $msg->param["record"] = $msg->GetValue("connect").uniqid();
                                    }
                                }
                                break;
                            default:
                                break;
                        }

                        if (!is_null($msg->GetValue("disconnect"))) {
                              $msg->param["duration"] = (int)round($msg->GetValue("disconnect") - $msg->GetValue("connect"));
                              $msg->param["ended"] = 1;
                        }
                        $call_history->UpdateFromMessage($rows_history["row"][0],$msg);
                    } else  {
                       $msg->param["SQL"] = "insert";
                    }
                }

                if ($msg->getValue("SQL") == "insert") {                    
                    switch ($msg->GetValue("connect_type")) {
                        case "fork":
                            $caller = $msg->GetValue("called");
                            $caller_gate = $msg->GetValue("called_gateway");
                            $caller_direction = $msg->GetValue("called_type");
                            $msg->param["called"] = $msg->GetValue("caller");
                            $msg->param["called_gateway"] = $msg->GetValue("caller_gateway");
                            $msg->param["called_type"] = $msg->GetValue("caller_type");
                            $msg->param["caller"] = $caller;
                            $msg->param["caller_gateway"] = $caller_gate;
                            $msg->param["caller_type"] = $caller_direction;
                            break;
                        case "telephony":
                            if(is_null($msg->GetValue("answer")))
                                 $msg->param["connect_type"] = "fork";              //можно ввести другой тип звонка direct_call
                            else {
                                 $msg->param["connect_type"] = "call";
                                 $msg->param["record"] = $msg->GetValue("connect").uniqid();
                            }
                            break;
                        default:
                            break;
                    }
                    $call_history->MessageInsert($msg);
                }
            }
        }
    }
}

function UpdateConfQueue($msg) {
    global $conf_room;
    global $activ_connections;
    
    $count_events = count($activ_connections->events);
    if($count_events>0)  {
        $all_rows = range(0,$count_events-1);
        $res = $activ_connections->SearchRowWithValue("SQL");
        if (empty($res))
            $rows = $all_rows;
        else
            $rows = array_diff($all_rows, $res["row"]);            

        if(empty($rows))
            return true;
        
           
        foreach ($rows as $row)
             if (chektype($activ_connections->getCellValueFromKey("peerid",$row)) == "conf") 
                  $actv_peer_conf[$row] = $activ_connections->getCellValueFromKey("SQL",$row);

        if (isset($actv_peer_conf)) {
             foreach ($actv_peer_conf as $row=>$sql) {
                     $msg = $activ_connections->GetMsgFromRow($row,"activ_conf_room");                     
                     $conf_room->UpDateMsg($msg);
            }
        }
    }    
}

function UpdateConnection($msg) {
    global $activ_channels;
    global $activ_connections;

    //UpdateActiveConnects
    //1.FromChannels    
    if (in_array($msg->param["operation"], ["initialize", "chan.startup"])) {
        $act_con = $activ_connections->SearchRowWithValue("chan", $msg->param["chan"]);        
        if (!empty($act_con)) {
            $row = $act_con["row"];            
            $activ_connections->UpdateValue("caller", $row, $msg->param["callnumber"]);
            $activ_connections->UpdateValue("caller_gateway", $row, $msg->param["gateway"]);
            $activ_connections->UpdateValue("caller_type", $row, $msg->param["direction"]);
            if ( $msg->param["operation"] == "initialize") {
                  $activ_connections->UpdateValue("billid", $row, $msg->param["billid"]);
                  $activ_connections->UpdateValue("callbillid", $row, $msg->param["callbillid"]);
            }
        }
        $act_con1 = $activ_connections->SearchRowWithValue("peerid", $msg->param["chan"]);
        if (!empty($act_con1)) {
            $row1 = $act_con1["row"];
            $activ_connections->UpdateValue("called", $row1, $msg->param["callnumber"]);
            $activ_connections->UpdateValue("called_gateway", $row1, $msg->param["gateway"]);
            $activ_connections->UpdateValue("called_type", $row1, $msg->param["direction"]);
            if ( $msg->param["operation"] == "initialize") {
                $activ_connections->UpdateValue("billid", $row1, $msg->param["billid"]);
                $activ_connections->UpdateValue("callbillid", $row1, $msg->param["callbillid"]);
            }
        }
        
    }

    //2. From FORK (called find)    
    $no_called = $activ_connections->SearchRowWithValue("called");
    if (!empty($no_called)) {
        $peer = $activ_connections->GetValue("peerid",$no_called["row"]);
        //if (!empty($peer)) {
            foreach ($peer["peerid"] as $row_num=>$value) {
              $peeer_row_number = $no_called["row"][$row_num];
              if (chektype($value) == "fork") {
                $chan = $activ_connections->getCellValueFromKey("chan",$peeer_row_number);
                $called_chan_find = $activ_channels->SearchRowWithValue("chan",$chan);                
                if (!empty($called_chan_find)) {
                    $called_chan_row = $called_chan_find["row"][0];                    
                    $caller = $activ_channels->getCellValueFromKey("caller",$called_chan_row);
                                                            
                    $caller_info1 = $activ_channels->SearchRowWithValue("callnumber",$caller);
                    if (!empty($caller_info1)) {
                        $caller_info = $activ_channels->SearchRowWithValue("ended",0,$caller_info1["row"]);
                        if (!empty($caller_info)) {
                             //reverse caller - called
                             /*$activ_connections->UpdateValue("called", [$peeer_row_number], $activ_connections->getCellValueFromKey("caller", $peeer_row_number) );
                             $activ_connections->UpdateValue("called_gateway", [$peeer_row_number], $activ_connections->getCellValueFromKey("caller_gateway", $peeer_row_number) );
                             $activ_connections->UpdateValue("called_type", [$peeer_row_number], $activ_connections->getCellValueFromKey("caller_type",$peeer_row_number) );

                             $activ_connections->UpdateValue("caller", [$peeer_row_number], $caller);
                             $activ_connections->UpdateValue("caller_gateway", [$peeer_row_number], $activ_channels->getCellValueFromKey("gateway",$caller_info["row"][0]) );
                             $activ_connections->UpdateValue("caller_type", [$peeer_row_number], $activ_channels->getCellValueFromKey("direction",$caller_info["row"][0]) );  */
                             //ver.2
                             $activ_connections->UpdateValue("called", [$peeer_row_number], $caller);
                             $activ_connections->UpdateValue("called_gateway", [$peeer_row_number], $activ_channels->getCellValueFromKey("gateway",$caller_info["row"][0]) );
                             $activ_connections->UpdateValue("called_type", [$peeer_row_number], $activ_channels->getCellValueFromKey("direction",$caller_info["row"][0]) );
                        }
                    }
                }                                
              } elseif (chektype($value) == "telephony") {
                  //смысла нет, т.к. инфа только в cdr идет
              } elseif (chektype($value) == "tone") {
                   $activ_connections->UpdateValue("called", [$peeer_row_number], "HOLD" );
              } elseif (chektype($value) == "conf") {
              } elseif (chektype($value) == "moh") {                   
                   $hold_from = $activ_connections->getCellValueFromKey("targetid",$peeer_row_number);
                   $hold_chan = $activ_channels->SearchRowWithValue("chan",$hold_from);
                   if(!empty($hold_chan)) {
                       $activ_connections->UpdateValue("called", [$peeer_row_number], "MOH" );
                       $activ_connections->UpdateValue("called_gateway", [$peeer_row_number], $activ_channels->getCellValueFromKey("callnumber",$hold_chan["row"][0]) );
                   }                   
              } elseif (chektype($value) == "auto_attendant") {
                    //$activ_connections->UpdateValue("called", [$peeer_row_number], "AutoAttendant" );         //приходит от route.php
              } elseif (chektype($value) == "leavemaildb") {
                    //$activ_connections->UpdateValue("called", [$peeer_row_number], "VM" );
                    //$activ_connections->UpdateValue("called_gateway", $peeer_row_number, "???" );            //добавить с кого перевод прошел в 
              }
            }
        //}
    }
}

function CreateChannels($msg) {
    global $activ_channels;
    
    if (in_array($msg->param["operation"], ["initialize", "update", "chan.connected", "call.answered", "chan.startup"])) {
        $res = $activ_channels->SearchRowWithValue("chan",$msg->param["chan"]);        
        if (!empty($res))  {
            if (in_array($msg->param["operation"],["initialize", "update", "chan.startup"])) {
                $activ_channels->UpdateFromMessage($res["row"][0],$msg);                
            }
        } else{
           $activ_channels->MessageInsert($msg);
        }
    }    
}

function DisconnectChannels($msg) {
    global $activ_connections;
    
    if (in_array($msg->param["operation"], ["chan.connected", "chan.disconnected", "call.answered", "chan.hangup"])) {
        $actv_conct = $activ_connections->SearchRowWithValue("disconnect");
        if (!empty($actv_conct)) {
            $res = $activ_connections->SearchRowWithValue(["chan","peerid"],[$msg->param["chan"]],$actv_conct["row"]);
            $res1 = $activ_connections->SearchRowWithValue(["chan","peerid"],[$msg->param["peerid"]],$actv_conct["row"]);
            $rows1 = empty($res) ? array() : $res["row"];
            $rows2 = empty($res1) ? array() : $res1["row"];
            if ($msg->param["operation"] == "chan.hangup") 
                $rows = $rows1;
            elseif ($msg->param["operation"] == "chan.disconnected")
                $rows = array_intersect($rows1, $rows2);
            else
                $rows = array_merge(array_diff($rows1, $rows2), array_diff($rows2, $rows1));
            $activ_connections->UpdateValue("disconnect",$rows,$msg->param["timestamp"]);
        }
    }
}

function ConnectChannels($msg) {
    global $activ_connections;

    if (in_array($msg->param["operation"], ["chan.connected", "call.answered"])) {
        $actv_conct = $activ_connections->SearchRowWithValue("disconnect");
        if (!empty($actv_conct)) {
            $res = $activ_connections->SearchRowWithValue(["chan","peer"],[$msg->param["chan"],$msg->param["peerid"]],$actv_conct["row"]);
            if (!empty($res)) {  
                $answer_time = $activ_connections->getCellValueFromKey("answer",$res["row"][0]);
                if ($msg->param["operation"] == "call.answered")  {                    
                    $activ_connections->UpdateFromMessage($res["row"][0],$msg);
                    if (!is_null($answer_time))
                         $activ_connections->UpdateValue("answer",$res["row"][0],$answer_time);
                } elseif (!is_null($msg->GetValue("answer")) && is_null($answer_time))
                     $activ_connections->UpdateValue("answer",$res["row"][0],$msg->GetValue("answer"));
            } else {
                if (is_null($msg->GetValue("connect")))
                     $msg->param["connect"] = $msg->param["timestamp"];
                $activ_connections->MessageInsert($msg);
            }
        } else {
            if (is_null($msg->GetValue("connect")))
                 $msg->param["connect"] = $msg->param["timestamp"];
            $activ_connections->MessageInsert($msg);
        }
    }
}

function CreateConfRoom(&$msg) {
    global $conf_room;

    if (($msg->param["operation"] == "chan.connected") and (chektype($msg->param["peerid"]) == "conf"))  {
        $res = $conf_room->SearchRowWithValue("targetid",$msg->param["targetid"]);
        if(!empty($res))  {
            $msg->param["called"] = $conf_room->getCellValueFromKey("called",$res["row"][0]);
            $active_billid = $conf_room->GetValue("callbillid",$res["row"]);
            if(!empty($active_billid))  {                
                $call_id = $active_billid["callbillid"];
                $call_id [] = $msg->GetValue("callbillid");
                $callbilid = min(array_diff($call_id, array('')));
                if(!empty($callbilid))
                    $msg->param["callbillid"] = $callbilid;
            }
        } else {
            $msg->param["called"] = $msg->getValue("caller");         //взять из Acive_chan         //можно отловить создание conf/x
        }
        //$conf_room->MessageInsert($msg);                      //Убрал в UPDATE по таблицам<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    }
}

function CloseChannesl($msg) {
    global $activ_channels;

    if ($msg->param["operation"] == "finalize") {
        $res = $activ_channels->SearchRowWithValue("chan",$msg->GetValue("chan"));
        $activ_channels->UpdateFromMessage($res["row"][0],$msg);
        $activ_channels->UpdateValue("SQL",$res["row"],"delete");
    }
}

function setGateways(&$msg) {
    global $activ_channels;
    
    $ch_caller = $activ_channels->SearchRowWithValue("chan",$msg->param["chan"]);
    if (!empty($ch_caller)) {        
         $msg->param["caller"] = $activ_channels->getCellValue($activ_channels->colFind("callnumber"),$ch_caller["row"][0]);
         $msg->param["caller_gateway"] = $activ_channels->getCellValue($activ_channels->colFind("gateway"),$ch_caller["row"][0]);
         $msg->param["caller_type"] = $activ_channels->getCellValue($activ_channels->colFind("direction"),$ch_caller["row"][0]);
    }
    
    $ch_called = $activ_channels->SearchRowWithValue("chan",$msg->param["peerid"]);
    if (!empty($ch_called)) {         
         $msg->param["called"] = $activ_channels->getCellValue($activ_channels->colFind("callnumber"),$ch_called["row"][0]);
         $msg->param["called_gateway"] = $activ_channels->getCellValue($activ_channels->colFind("gateway"),$ch_called["row"][0]);
         $msg->param["called_type"] = $activ_channels->getCellValue($activ_channels->colFind("direction"),$ch_called["row"][0]);
    }
}

function SetBillid(&$msg) {
    global $activ_channels;

    $billid = $msg->GetValue("billid");      
    
    if (is_null($billid)) {
        $value = [$msg->GetValue("chan")];
        if(!is_null($msg->GetValue("peerid")))    
           $value[] = $msg->GetValue("peerid");

        $bill_res = $activ_channels->SearchRowWithValue("chan",$value);
        if (!empty($bill_res)) {             
             $billid = $activ_channels->GetValue("billid", $bill_res["row"]);             
             $billid = min(array_diff($billid["billid"], array('')));
             if (!empty($billid)) {
                  $value[] = $billid;
                  $msg->param["billid"] = $billid;
             }
        }
    }
}

function SearchCallBillid(&$msg) {
    global $activ_connections;
    global $activ_channels;

    $billid = $msg->GetValue("billid");
    $chan = $msg->GetValue("chan");
    $peer = $msg->GetValue("peerid");
    
    $value = [$chan];
    if(!is_null($peer))
       $value[] = $peer;
    if (!is_null($billid)) 
         $value[] = $billid;
    /*if (is_null($billid)) {
        $bill_res = $activ_channels->SearchRowWithValue("chan",$value);
        if (!empty($bill_res)) {
             $billid = $activ_channels->GetValue("billid", $bill_res["row"]);
             $billid = min(array_diff($billid["billid"], array('')));
             if (!empty($billid)) {
                  $value[] = $billid;
                  $msg->param["billid"] = $billid;
             }
        }
    } else
        $value[] = $billid;*/
    
    $res = $activ_channels->SearchRowWithValue(["chan","billid"],$value);    
    if (!empty($res))  {
        $cahn_billid = $activ_channels->GetValue("callbillid",$res["row"]);
        $ch_id = array_diff($cahn_billid["callbillid"], array(''));
        $ch_billid = count($ch_id) ? min($ch_id) : $billid;
    }

    $res = $activ_connections->SearchRowWithValue(["chan","peerid","billid"],$value);    
    if (!empty($res)) {
         $active_billid = $activ_connections->GetValue("callbillid",$res["row"]);
         $call_id = array_diff($active_billid["callbillid"], array(''));
         $callbillid = count($call_id) ? min($call_id) : $billid;
         if (isset($ch_billid))
             $callbillid = min(array_diff([$callbillid, $ch_billid, $billid], array('')));
    } else
       $callbillid = isset($ch_billid) ? $ch_billid : $billid;

    //return $callbillid;
    $msg->param["callbillid"] = $callbillid;
}

function chektype($type) {
    global $channel_type;
    
    $count = substr_count($type,'/');
    if ($count>0)
        $ch_type = stristr($type,'/',true);
    else
        $ch_type = $type;

    if(in_array($ch_type,$channel_type))
      return "telephony";
    else
      return $ch_type;
}

function MsgFilling(&$msg) {
    global $active_gates;
    global $activ_channels;

    if(is_null($msg->param["id"]))
       $msg->param["id"] = $msg->param["chan"];       
    else
       $msg->param["chan"] = $msg->param["id"];    

    switch ($msg->param["operation"]) {
        case "finalize":
            //break;
        case "chan.startup":
            if(chektype($msg->param["chan"]) == "fork")
               return false;
        case "update":
            //break;
        case "initialize":            
            if ( $msg->param["direction"] == 'incoming')  {                
                 $msg->param["callnumber"] = $msg->param["caller"];
                 if (isset($msg->param["username"]))
                      $username = $msg->GetValue("username");
            } else {
                 $msg->param["callnumber"] = isset($msg->param["calledfull"]) ? $msg->param["calledfull"] : $msg->param["called"];
                 $msg->param["called"] = $msg->param["callnumber"];
                 $username = $msg->GetValue("caller");
            }    
            $enbl_gtw = $active_gates->SearchRowWithValue("status","online");    
            if(!empty($enbl_gtw) && isset($username)) {
                $res = $active_gates->SearchRowWithValue("username",$username,$enbl_gtw["row"]);
                if (!empty($res))
                    $msg->param["gateway"] = $active_gates->GetValue("description",$res["row"][0]);
            }            
            //$msg->param["gateway"] = searchGateway([$msg->param["username"],$msg->param["caller"]]);
            break;
        case "route.register":
        case "rec.vm":
            return false;
            //break;

        case "chan.connected":
            $module_type=chektype($msg->param["chan"]);
            $peer_type=chektype($msg->param["peerid"]);
            $target_type=chektype($msg->param["targetid"]);
        
            if (($module_type == "q-out") or ($peer_type == "q-out") or ($peer_type == "conf") or ($peer_type == "ExtModule"))
                 return false;    
        
            $msg->param["connect"] = $msg->param["timestamp"];
            if ($module_type == "conf" or $module_type == "tone") {
                $msg->param["chan"] = $msg->param["peerid"];
                $msg->param["peerid"] = $msg->param["id"];
                $msg->param["targetid"] = $msg->param["address"];        
                $msg->param["answer"] = $msg->param["timestamp"];                
            } elseif ($peer_type == "fork") {
                if (substr_count($msg->param["peerid"],'/') == 2) {
                    //$msg->param["targetid"] = substr(strrchr($msg->param["peerid"],'/'),1);
                    //$msg->param["peerid"] = substr($msg->param["peerid"],0,strrpos($msg->param["peerid"],'/'));
                    $msg->param["targetid"] = substr($msg->param["peerid"],0,strrpos($msg->param["peerid"],'/'));
                } else
                    return false;             //не писать инициатора вызова fork/
            } elseif ($peer_type == "moh") {
                $msg->param["targetid"] = $msg->param["lastpeerid"];
                $msg->param["answer"] = $msg->param["timestamp"];                
            } elseif ($peer_type == "q-in")  {
                $msg->param["answer"] = $msg->param["timestamp"];               // можно убрать
                if($target_type == "q-in")
                    $msg->param["peerid"] = $msg->param["lastpeerid"];                
            } elseif ($peer_type == "telephony")  {
                //chekStatusPeer($msg);
                if ($msg->GetValue("status") == "answered")  {
                    $res = $activ_channels->SearchRowWithValue("chan",$msg->GetValue("peerid"));        
                    if (!empty($res))
                        if ($activ_channels->getCellValueFromKey("status",$res["row"][0]) == "answered")  {                 //можно проверить по ключу "answer">0 для обоих кналов
                             $msg->param["answer"] = $msg->param["timestamp"];
                             $msg->param["connect"] = NULL;                                    //Проверить как повлияет
                        }
                }                
                //можно слазить в history за подробностями
                $chan = substr($msg->param["chan"],strrpos($msg->param["chan"],'/')+1);
                $peer = substr($msg->param["peerid"],strrpos($msg->param["peerid"],'/')+1);
                if ($chan > $peer) {
                    $msg->param["chan"] = $msg->param["peerid"];
                    $msg->param["peerid"] = $msg->param["id"];
                }
            }
            $msg->param["id"] = $msg->param["chan"];
            setGateways($msg);
            break;
        case "call.answered":
            $msg->param["chan"] = is_null($msg->param["peerid"]) ? $msg->param["targetid"] : $msg->param["peerid"];    
            $msg->param["peerid"] = $msg->param["id"];            
            $module_type = chektype($msg->param["chan"]);
            $peer_type = chektype($msg->param["peerid"]);    
        
            if (($module_type == "q-out") or ($peer_type == "fork"))
                 return false;
        
            $msg->param["answer"] = $msg->param["timestamp"];
            //$msg->param["connect"] = $msg->param["timestamp"];            
            if (($peer_type ==  "auto_attendant") or ($peer_type ==  "leavemaildb"))
                 $msg->param["targetid"] = "ExtModule";
            
            $msg->param["id"] = $msg->param["chan"];
            setGateways($msg);
            break;
        case "chan.disconnected":
            $module_type = chektype($msg->param["chan"]);    
            if ($module_type == "q-out" or $module_type == "fork") 
                  return false;
            if ($module_type == "telephony")
                 $msg->param["peerid"] = $msg->param["lastpeerid"]; 
            else {
                $msg->param["peerid"] = $msg->param["chan"]; 
                $msg->param["chan"] = $msg->param["lastpeerid"];
                $msg->param["id"] = $msg->param["chan"];
            }               
            $msg->param["disconnect"] = $msg->param["timestamp"]; 
            break;
        case "chan.hangup":
            $module_type = chektype($msg->param["chan"]);    
            if (($module_type == "fork") or ($module_type == "q-out"))
                return false;
            $msg->param["disconnect"] = $msg->param["timestamp"];
            break;            
        default:
           return false;
    }
    
    $msg->param["ended"] = ($msg->param["operation"] == "finalize") ? 1 : 0 ;    
    $msg->param["connect_type"] =isset($msg->param["connect_type"]) ? $msg->param["connect_type"] : chektype($msg->GetValue("peerid"));
    //$msg->param["callbillid"] = SearchCallBillid($msg);
    SetBillid($msg);
    SearchCallBillid($msg);
    
    return true;    
}

function MsgToHistory($msg) {
    global $call_history;
    global $activ_connections;

    $dt = 0.0001;

    //добавить полную запись маршрута из register.php
    if ($msg->GetValue("operation") == "route.register") {
        $dynamic_parametrs= explode(";",$msg->GetValue("dynamic_parametrs"));
        SearchCallBillid($msg);                                                          //В route добавить поиск peer callbilid если это pickup /transfer/ next
        $history_msg = new YMessage("history");
        $history_msg->CopyDataFromMsg($msg);        
        $history_msg->UpdateValue("ended",1);
        $time = $msg->GetValue("connect");
        foreach ($dynamic_parametrs as $parametr) {
            $indx = 0;            
            $data_keys = explode(";",$msg->GetValue($parametr));
            while (!is_null($msg->GetValue($parametr.".".$indx))) {
                $data = explode("|",$msg->GetValue($parametr.".".$indx));
                foreach ($data_keys as $row=>$key)
                    $history_msg->UpdateValue($key,$data[$row]);
                if ($history_msg->GetValue("connect_type") == "next")
                    $history_msg->UpdateValue("connect",NULL);                  //придумать как исправлять  на Route
                else 
                    $history_msg->UpdateValue("connect",$time);
                $time+=$dt;
                $indx++;
                $call_history->MessageInsert($history_msg);
            }            
        }
        //$msg->convertMessageType("history");
        //$msg->UpdateValue("connect_type","route");
        //$msg->UpdateValue("ended",1);        
        //SearchCallBillid($msg);                 //В route добавить поиск peer callbilid если это pickup /transfer/ next
        //$call_history->MessageInsert($msg);
    }

    //запись на автоответчике
    //добавить проверку на отправку
    if ($msg->GetValue("operation") == "rec.vm") {
        $ac_row = $activ_connections->SearchRowWithValue("peerid",$msg->GetValue("peerid"));
        if (!empty($ac_row))  {
            $connect_time = $activ_connections->getCellValueFromKey("connect",$ac_row["row"][0]);
            $history_rows = $call_history->SearchRowWithValue("connect",$connect_time);
            $call_history->UpdateFromMessage($history_rows["row"][0],$msg);
        }
    }        
}


function CheckMsgType ($type) {
    global $msg_keys;
    //$msg_keys["type"]["base_type"] = ["cdr","connect","full","history"];
    //$msg_keys["type"]["cdr"] = ["call.cdr","chan.startup"];
    //$msg_keys["type"]["connect"] = ["chan.connected","chan.disconnected","call.answered","chan.hangup"];
    
    foreach ($msg_keys["type"] as $name=>$keys)        
       if (array_search($type,$keys) !== FALSE)
           return ($name == "base_type") ? $type : $name;
    return FALSE;
}

function MsgHandler($evnt) {
    global $activ_channels;
    global $activ_connections;
    global $call_history;
    
    $type = CheckMsgType($evnt->name);
    if (!$type)
        return false;        
    $msg = new YMessage($type);
    $msg->ReadMsg($evnt);

    MsgToHistory($msg);                                  //Запись register.route >> нужно утащить наружу - не прогонять полностью через обработчик??? (событие - пропущенный вызов???)
    
    $msg->convertMessageType("full");

    $filling = MsgFilling($msg);
    if (!$filling)
        return false;    

    //
    //>function>>UpadateDataFromMsg($msg);
    CreateConfRoom($msg);
    CreateChannels($msg);    
    DisconnectChannels($msg);
    ConnectChannels($msg);
    CloseChannesl($msg);

    //
    //>function>>UpdateData($msg);
    UpdateConnection($msg);                         // базовая операция    
    
    //Update вспомогательных данных
    UpdateConfQueue($msg);
    UpdateHistory($msg);
    
    //External sniffers
    closePhpScripts($msg);                                      //Костыли к Yate
    RegisterInfo($msg);
    
    $activ_connections->DataToMySQL();
    $activ_channels->DataToMySQL();
    $call_history->DataToMySQL();

    ClearActiveTable($msg);

    return true;
}

//утащить в общий обработчик событий с апдейтом базы и т.п.
function GatewaysStatusUpdate($event,$status = "update") {
    global $active_gates;
    
    $gate = $active_gates->SearchRowWithValue("gateway",$event->GetValue("account"));
    if ($status == "info") {
        $msg = new YMessage("gateways");
        $msg->ReadMsg($event);
        $msg->param["gateway"] = $event->GetValue("account");
        if(empty($gate)) 
            $active_gates->MessageInsert($msg);
        else
            $active_gates->UpdateFromMessage($gate["row"][0],$msg);
    } else {
        if(!empty($gate)) {
           $status = ($event->GetValue("registered") == 'false') ? "offline" : "online";
           $active_gates->UpdateValue("status",$gate["row"][0],$status);
        }
    }
}

// Always the first action to do 
Yate::Init();
chek_debug();

//Утащить в отдельный файл - чтобы перезагружать register.php
//добить нехватающими параметрами
if (Yate::Arg()) {
    Yate::Output("Executing startup time CDR cleanup");
    $query = "UPDATE call_logs SET ended= 1 where ended = 0 or ended IS NULL";
    query_nores($query);
    $query = "UPDATE extensions SET inuse_count=0";
    query_nores($query);
}
/*-------------------------------------------------------------------------------------*/
/* Set tracking name for all installed handlers */
Yate::SetLocal("trackparam","register.php");

// Install handler for the wave end notify messages 
Yate::Watch("engine.timer");
Yate::Install("user.register");
Yate::Install("user.unregister");
Yate::Install("user.auth");
Yate::Install("call.cdr");
Yate::Install("chan.startup", 80);

Yate::Install("chan.hangup",80);
Yate::Install("chan.connected", 80);
Yate::Install("chan.disconnected", 80);
Yate::Install("call.answered", 80);
Yate::Install("route.register");
Yate::Install("rec.vm");

Yate::Install("user.notify");
Yate::Install("engine.status");
Yate::Install("engine.command");
Yate::Install("engine.debug");

// Ask to be restarted if dying unexpectedly 
Yate::SetLocal("restart", "true");

$activ_channels = new ActivObjects("cdr");
$activ_connections = new ActivObjects("connect");
$active_gates = new ActivObjects("gateways");                           //!!!!!!!не читать все данные!!!!
$conf_room = new ActivObjects("activ_conf_room");
$active_queue = new ActivObjects("queue");
$call_history = new ActivObjects("history");

$query = "SELECT enabled, protocol, username, description, 'interval', formats, authname, password, server, domain, outbound , localaddress, modified, gateway as account, gateway_id, status, 1 AS gw, ip_transport FROM gateways WHERE enabled = 1 AND gateway IS NOT NULL AND username IS NOT NULL ORDER BY gateway";
$res = query_to_array($query);
for ($i = 0; $i < count($res); $i++) {
    $m = new Yate("user.login");
    $m->params = $res[$i];
    $m->Dispatch();
}


// The main loop. We pick events and handle them 
for (;;) {
    $ev = Yate::GetEvent();
    // If Yate disconnected us then exit cleanly
    if ($ev === false)
        break;
    // No need to handle empty events in this application
    if ($ev === true)
        continue;
    // If we reached here we should have a valid object
    switch ($ev->type) {
        case "incoming":
            switch ($ev->name) {
                case "engine.debug":
                    $module = $ev->GetValue("module");
                    if ($module != "register")
                        break;
                    $line = $ev->GetValue("line");
                    if ($line == "on") 
                        set_debug(true);
                    elseif ($line == "off") 
                        set_debug(false);
                    else
                        break;
                    $ev->handled = true;
                    break;
                case "engine.command":
                    debug("Got engine.command : line=" . $ev->GetValue("line"));
                    $line = $ev->GetValue("line");
                    if ($line == "register query on") {
                        $query_on = true;
                        Yate::Output(">>> Enabling query register.php module");
                    } elseif ($line == "register query off") {
                        $query_on = false;
                        Yate::Output(">>> Disable query register.php module");
                    } else
                        break;
                    $ev->handled = true;
                    break;
                case "engine.status":
                    $module = $ev->GetValue("module");
                    if ($module && $module != "register.php" && $module != "misc")
                        break;
                    $query = "SELECT gateway,(CASE WHEN status IS NULL THEN 'offline' else status END) as status FROM gateways WHERE enabled = 1 AND username IS NOT NULL";
                    $res = query_to_array($query);
                    $str = $ev->retval;                    
                    $str .= 'name=register.php;gateways=' . count($res);
                    for ($i = 0; $i < count($res); $i++) {
                        $str .= ($i) ? "," : ";";
                        $str .= $res[$i]["gateway"] . '=' . $res[$i]["status"];
                    }
                    $str .= "\r\n";
                    $ev->retval = $str;
                    $ev->handled = false;
                    break;
                case "user.notify":
                    $gateway = $ev->GetValue("account") . '(' . $ev->GetValue("protocol") . ')';
                    $status = ($ev->GetValue("registered") != 'false') ? "online" : "offline";
                    $s_statusaccounts[$gateway] = $status;
                    GatewaysStatusUpdate($ev);
                    $query = "UPDATE gateways SET status='$status' WHERE gateway='" . $ev->GetValue("account") . "'";
                    $res = query_nores($query);
                    break;
                case "user.auth":
                    if (!$ev->GetValue("username"))
                        break;
                    $query = "SELECT password FROM extensions WHERE extension='" . $ev->GetValue("username") . "'";
                    $res = query($query);
                    if ($res)
                        $row = $res->fetchRow();
                    if ($row)
                        foreach ($row as $key => $value) {
                            $ev->retval = $value;
                            $ev->handled = true;
                     }
                    break;
                case "user.register":
                    $location = $ev->GetValue("data");
                    $ppos = stripos($location,";");
                    if ( $ppos !== false)
                            $location = substr($location, 0 ,  $ppos);
                    //$query = "INSERT INTO ext_connection (extension,location,expires) VALUES ('" . $ev->GetValue("username") . "','$location','" . (time() + $ev->GetValue("expires")) . "') ON DUPLICATE KEY UPDATE expires='" . (time() + $ev->GetValue("expires")) . "'";
                    $query = "INSERT INTO ext_connection (extension_id, extension,location,expires) VALUES ( (SELECT extension_id FROM extensions WHERE extension='" . $ev->GetValue("username") . "')    ,'" . $ev->GetValue("username") . "','$location','" . (time() + $ev->GetValue("expires")) . "') ON DUPLICATE KEY UPDATE expires='" . (time() + $ev->GetValue("expires")) . "'";
                    $res = query_nores($query);
                    $ev->handled = true;
                    break;
                case "user.unregister":
                    $location = $ev->GetValue("data");
                    $ppos = stripos($location,";");                    
                    if ( $ppos !== false)
                            $location = substr($location, 0 ,  $ppos);
                    $query = "DELETE FROM ext_connection WHERE extension ='" . $ev->GetValue("username") . "' AND location = '$location'";
                    $res = query_nores($query);
                    $ev->handled = true;
                    break;
                default:
                    /*if (!MsgHandler($ev))
                        debug("[".$ev->name."] Skip Events!!!");*/
                    MsgHandler($ev);
                    break;
				}
            // This is extremely important.
            //  We MUST let messages return, handled or not
            if ($ev)
                $ev->Acknowledge();
            break;
        case "answer":
            switch ($ev->name) {
                case "engine.timer":
                    $time = $ev->GetValue("time");
                    if ($time < $next_time)
                        break;
                    $next_time = $time + $time_step;
                    //проверка шлюза на изменеие параметров в интерфейсе
                    $query = "SELECT enabled, protocol, username, description, 'interval', formats, authname, password, server, domain, outbound , localaddress, modified, gateway as account, gateway_id, status, 1 AS gw FROM gateways WHERE modified = 1 AND username is NOT NULL";
                    $res = query_to_array($query);
                    for ($i = 0; $i < count($res); $i++) {
                        $m = new Yate("user.login");
                        $m->params = $res[$i];
                        if (!$res[$i]["enabled"])
                             $m->params["operation"] = "logout";
                        $m->Dispatch();
                        GatewaysStatusUpdate($m,"info");
                    }                    
                    $query = "DELETE FROM ext_connection WHERE expires<=" . time();
                    $res = query_nores($query);
                    $query = "UPDATE gateways SET modified=0 WHERE modified=1 AND username IS NOT NULL";
                    $res = query_nores($query);
                    break;
            }
            // Yate::Debug("PHP Answered: " . $ev->name . " id: " . $ev->id);
            break;
        case "installed":
            // Yate::Debug("PHP Installed: " . $ev->name);
            break;
        case "uninstalled":
            // Yate::Debug("PHP Uninstalled: " . $ev->name);
            break;
        default:
        // Yate::Output("PHP Event: " . $ev->type);
    }
}

/* vi: set ts=8 sw=4 sts=4 noet: */
?>