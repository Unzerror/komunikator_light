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

$type_debug = 'record';

$legs_path = 'wave/record//var/lib/misc/records/leg/';

$record_chanels = array();
$queue = array();

$next_time = 0;
$time_step = 3600;


//проверка на запись канала
function in_record($data,$type) {
    return true;
}

function geet_active_peer_paar($ev) {

    $peer = array();
    $dynamic_parametrs = $ev->GetValue("dynamic_parametrs");
    if (!is_null($dynamic_parametrs)) {        
        $events = explode(";",$dynamic_parametrs);
        foreach($events as $event)  {
            if ($event == "call") {
                $parametrs = explode(";",$ev->GetValue($event));          //можно сделать более общий механизм чтения
                $indx = 0;
                while (!is_null($ev->GetValue($event.".".$indx))) {
                    $data = explode("|",$ev->GetValue($event.".".$indx));
                    foreach ($parametrs as $i=>$parametr) {
                        $peer[$event][$indx][$parametr] = $data[$i];
                    }
                    $indx++;
                }
            } elseif ($event == "conf") {
                $parametrs = explode(";",$ev->GetValue($event));
                $peer_parametrs = explode(";",$ev->GetValue("$event.dynamic_parametrs"));
                $indx = 0;
                while (!is_null($ev->GetValue($event.".".$indx))) {
                    $data = explode("|",$ev->GetValue($event.".".$indx));
                    foreach ($parametrs as $i=>$parametr) {
                        $peer[$event][$indx][$parametr] = $data[$i];
                    }
                    $peer_indx = 0;
                    while (!is_null($ev->GetValue("$event.$indx.$peer_indx"))) {
                        $peer_data = explode("|",$ev->GetValue("$event.$indx.$peer_indx"));
                        foreach ($peer_parametrs as $j=>$peer_parametr)  {
                            $peer[$event][$indx][$peer_indx][$peer_parametr] = $peer_data[$j];
                        }
                        $peer_indx++;
                    }
                    $indx++;
                }
            }
        }        
    }
    return $peer;
}

function filter_peer($active_paar) {
    global $record_chanels;
    
    $time = microtime(true);
    $peer_paar = array();
    $new_data = array(); 

    $new_indx =0;
    if(isset($active_paar["call"])) {
        $max_calls = max(array_keys($active_paar["call"]));
        for ($indx =0; $indx<=$max_calls; $indx++)  {
            $rec_id = $active_paar["call"][$indx]["record"];        
            if($active_paar["call"][$indx]["ended"]) {
                if(isset($record_chanels[$rec_id])) {
                   $part = max(array_keys($record_chanels[$rec_id]));
                   $record_chanels[$rec_id][$part]["duration"] = round($time - $record_chanels[$rec_id][$part]["start"], 2);                   
                   $new_data[] = [$rec_id, $part];
                }
            } else {
                if (in_record($active_paar["call"][$indx],"call"))  {
                    $part = 0;
                    $record_chanels[$rec_id][$part]["start"] = $time;
                    $record_chanels[$rec_id][$part]["connect_type"] = "call";
                    $record_chanels[$rec_id][$part]["record"] = $rec_id;
                    $record_chanels[$rec_id][$part]["chan"][1] = $active_paar["call"][$indx]["chan"];
                    $record_chanels[$rec_id][$part]["chan"][0] = $active_paar["call"][$indx]["peerid"];
                    $record_chanels[$rec_id][$part]["peer_count"] = 2;
                    $record_chanels[$rec_id][$part]["part"] = $part;
                    $record_chanels[$rec_id][$part]["called"] = NULL;
                    $record_chanels[$rec_id][$part]["duration"] = NULL;
                    $new_data[] = [$rec_id, $part];

                    $peer_paar[$new_indx]["connect_type"] = "call";
                    $peer_paar[$new_indx]["id"] = $active_paar["call"][$indx]["peerid"];
                    $peer_paar[$new_indx]["record"] = $rec_id."_p".$part."_";
                    $new_indx++;
                }
            }        
        }
    }

    if (isset($active_paar["conf"])) {  
        $max_confs = max(array_keys($active_paar["conf"]));
        for ($indx = 0; $indx<=$max_confs; $indx++) {
            $part = 0;
            $rec_id = $active_paar["conf"][$indx]["record"];
            $called = $active_paar["conf"][$indx]["called"];
            foreach ($record_chanels as $id=>$channels)  {                
                if (($channels[0]["connect_type"] == "conf") and ($channels[0]["called"]) == $called)  {
                    $part1 = max(array_keys($channels));
                    if (is_null($record_chanels[$id][$part1]["duration"]))  {
                        $record_chanels[$id][$part1]["duration"] = round($time - $channels[$part1]["start"], 1);                        
                        $new_data[] = [$id, $part1];
                        if ($id == $rec_id)
                            $part = $part1 + 1;
                    }
                }                
            }

            if($active_paar["conf"][$indx]["ended"])  {}
            else  {                
                if (isset($active_paar["conf"][$indx][1]) and in_record($active_paar["conf"][$indx],"conf"))  {                    
                    $max_peer = max(array_keys($active_paar["conf"][$indx]));
                    for ($peer_indx = 0; $peer_indx <= $max_peer; $peer_indx++) {
                        $peer_paar[$new_indx]["connect_type"] = "conf";
                        $peer_paar[$new_indx]["id"] = $active_paar["conf"][$indx][$peer_indx]["chan"];
                        $peer_paar[$new_indx]["record"] = $rec_id."_p".$part."_".$peer_indx;
                        $new_indx++;
                        
                        $record_chanels[$rec_id][$part]["chan"][$peer_indx] = $active_paar["conf"][$indx][$peer_indx]["chan"];                        
                    }
                    

                    $record_chanels[$rec_id][$part]["start"] = $time;
                    $record_chanels[$rec_id][$part]["connect_type"] = "conf";
                    $record_chanels[$rec_id][$part]["record"] = $rec_id;
                    $record_chanels[$rec_id][$part]["part"] = $part;
                    $record_chanels[$rec_id][$part]["peer_count"] = $max_peer+1;
                    $record_chanels[$rec_id][$part]["called"] = $called;
                    $record_chanels[$rec_id][$part]["duration"] = NULL;
                    $new_data[] = [$rec_id, $part];                    
                }
            }        
        } 
    }

    $sql_key = ["start","duration","part","record","connect_type","peer_count","called","chan"];
    $sql = array();
    foreach ($new_data as $chan) {        
        $data = $record_chanels[$chan[0]][$chan[1]];        
        $chan = implode("|", $data["chan"]);
        $sql[] = '("'.$data["start"].'","'.$data["duration"].'","'.$data["part"].'","'.$data["record"].'","'.$data["connect_type"].'","'.$data["peer_count"].'","'.$data["called"].'","'.$chan.'")';
        
    }
    if (!empty($sql)) {
        $safe_arr = implode(',', $sql);
        $query = "INSERT INTO `call_rec` (`".implode("`, `", $sql_key)."`) VALUES $safe_arr ON DUPLICATE KEY UPDATE `duration` = VALUES(`duration`)";
        $res = query_nores($query);
    }

    return $peer_paar;
}

function recorder($ev) {
    //global $record_chanels;
    global $legs_path;
    
    $active_paar = geet_active_peer_paar($ev);
    $peer_paar = filter_peer($active_paar);

    foreach($peer_paar as $peer) {
        $m = new Yate("chan.masquerade");
        $m->params["message"] = 'chan.record';
        $m->params["id"] = $peer["id"];
        if ($peer["connect_type"] == "call")  {
            $m->params["call"] = $legs_path.$peer["record"].'0.slin';
            $m->params["peer"] = $legs_path.$peer["record"].'1.slin';
            
        } else 
            $m->params["call"] = $legs_path.$peer["record"].'.slin';
        $m->Dispatch();        
    }
}

function batch_file($ev) {
    global $record_chanels;
    global $stdout;

    Yate::Output("PHP batch file start:".microtime(true));    
    
    $indx = 0;
    $records = array();
    while (!is_null($ev->GetValue("record.".$indx))) {
        $rec_id = $ev->GetValue("record.".$indx++);
        if(isset($record_chanels[$rec_id])) {
            $records[] = $rec_id;
        }
    }
    if (!empty($records))  {
        $data = implode("' or `record`='",$records);
        $query = "UPDATE `call_rec` SET `close`=1 WHERE `record`='".$data."'";    
        $res = query_nores($query);
        
        merge_file($records);

                
        Yate::Output("PHP batch file stop:".microtime(true));
    }
}

function merge_file($records = array()) {
    global $record_chanels;
    global $next_time;
    global $time_step;

    static $queue = array();
    static $process;
    
    if (!empty($records)) {
        foreach ($records as $recid)
            if (!in_array($recid,$queue))
                $queue[]=$recid;
        //$queue = array_merge($queue, $records);        
    }

    if (is_resource($process)) {
        $meta_info = proc_get_status($process);
        if(!$meta_info["running"]) {
            $rec_id = array_shift($queue);                                                                                  //удалять из mysql
            $exit_code = proc_close($process);
            if ($meta_info["exitcode"] == 0)
                $query = "DELETE FROM `call_rec` WHERE `record` = '".$rec_id."'";
            else
                $query = "UPDATE `call_rec` SET `close`='".$meta_info["exitcode"]."' WHERE `record`='".$rec_id."'";
            $res = query_nores($query);
            if(empty($queue))
                $time_step = 3600;
        }
    }

    if (!is_resource($process) && !empty($queue))  {
        $descriptor = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $pipes = array();

        $rec_id = $queue[0];
        $max_parts = max(array_keys($record_chanels[$rec_id]));
        $parts_count = 0;
        $cmd_parts = "";
        for ($parts = 0; $parts <= $max_parts; $parts++) {
            if ( $record_chanels[$rec_id][$parts]["duration"] > 0.1 ) {
               $parts_count++;
               $cmd_parts = $cmd_parts." ".$parts." ".$record_chanels[$rec_id][$parts]["peer_count"]." ".$record_chanels[$rec_id][$parts]["duration"];
            }
        }
        $cmd_parts = "/usr/share/yate/scripts/script.sh ".$rec_id." ".$parts_count.$cmd_parts;

        $process = proc_open($cmd_parts, $descriptor, $pipes);
        $next_time = 0;
        $time_step = 1;                
    } 

}

/* Always the first action to do */
Yate::Init();

/* Comment the next line to get output only in logs, not in rmanager */
chek_debug();

/* Set tracking name for all installed handlers */
Yate::SetLocal("trackparam","record.php");

Yate::Watch("engine.timer");
/* Install a handler for the call routing message */
Yate::Install("engine.command");
//Yate::Install("engine.status");
Yate::Install("engine.debug");
//Yate::Install("call.answered",90);
//Yate::Install("chan.disconnected", 10);
Yate::Install("register.info");
Yate::Install("register.endcall");

// Ask to be restarted if dying unexpectedly 
Yate::SetLocal("restart", "true");

/* The main loop. We pick events and handle them */
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
                    if ($module != "record")
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
                case "engine.status":
                    $module = $ev->GetValue("module");
                    if ($module && $module != "record.php" && $module != "misc")
                        break;
                    $str = $ev->retval;
                    $str .= "name=record.php \r\n";
                    $ev->retval = $str;
                    $ev->handled = false;
                    break;    
                case "register.info":
                    recorder($ev);
                    break;
                case "register.endcall":
                    batch_file($ev);
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
                    if ($next_time < $time) {
                        $next_time = $time + $time_step;
                        Yate::Output("Timer:".microtime(true));
                        merge_file();
                    }
                    break;
            }
            // Yate::Debug("PHP Answered: " . $ev->name . " id: " . $ev->id);
            break;
    }
}

?>