#!/usr/bin/php -q
<?php
/**
 * This file is part of the YATE Project http://YATE.null.ro
 *
 * Yet Another Telephony Engine - a fully featured software PBX and IVR
 * Copyright (C) 2004-2012 Null Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 */

require_once("libyate.php");
require_once("lib_queries.php");

require_once (__DIR__.'/vendor/autoload.php');
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$log = new Logger('record');
$log->pushHandler(new StreamHandler('/var/tmp/register.log', Logger::DEBUG));
$log->addInfo('==record.php logger start==');

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
    global $log;

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
                        //$log->debug($event."[".$indx."][".$parametr."] = ".$data[$i]);
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
                        //$log->debug($event."[".$indx."][".$parametr."] = ".$data[$i]);
                    }
                    $peer_indx = 0;
                    while (!is_null($ev->GetValue("$event.$indx.$peer_indx"))) {
                        $peer_data = explode("|",$ev->GetValue("$event.$indx.$peer_indx"));
                        foreach ($peer_parametrs as $j=>$peer_parametr)  {
                            $peer[$event][$indx][$peer_indx][$peer_parametr] = $peer_data[$j];
                            //$log->debug($event."[".$indx."][".$peer_indx."][".$peer_parametr."] = ".$peer_data[$j]);
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
    global $log;
    
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
        //$log->debug(implode(",", array_keys($record_chanels)));
        $max_confs = max(array_keys($active_paar["conf"]));
        for ($indx = 0; $indx<=$max_confs; $indx++) {
            $part = 0;
            $rec_id = $active_paar["conf"][$indx]["record"];
            $called = $active_paar["conf"][$indx]["called"];
            foreach ($record_chanels as $id=>$channels)  {                
                //$log->debug("1.Type[".$id."]:".$channels[0]["called"]);
                //$log->debug(implode(",", array_keys($channels[0])));
                if (($channels[0]["connect_type"] == "conf") and ($channels[0]["called"]) == $called)  {
                    $part1 = max(array_keys($channels));
                    //$log->debug("Search:".$part);
                    if (is_null($record_chanels[$id][$part1]["duration"]))  {
                        $record_chanels[$id][$part1]["duration"] = round($time - $channels[$part1]["start"], 1);                        
                        $new_data[] = [$id, $part1];
                        if ($id == $rec_id)
                            $part = $part1 + 1;
                        //$log->debug("Update[".($part)."]:".$id);
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
                        //$log->debug("Insert ".$new_indx."[".$peer_paar[$new_indx]["id"]."]:".$peer_paar[$new_indx]["record"]);
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
        //$log->debug($query);
        $res = query_nores($query);
    }

    return $peer_paar;
}

function recorder($ev) {
    //global $record_chanels;
    global $legs_path;
    global $log;
    
    $active_paar = geet_active_peer_paar($ev);
    $peer_paar = filter_peer($active_paar);

    foreach($peer_paar as $peer) {
        //$log->debug("msg:".$peer["id"]);
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

function recorder1($ev) {
    global $record_chanels;
    global $log;
    
    $active_paar = geet_active_peer_paar($ev);
    $peer_paar = filter_peer($active_paar);

    $indx = 0;
    $sql = array();
    while (isset($peer_paar["call"][$indx])) {
        $time = microtime(true);
        if($peer_paar["call"][$indx]["ended"]) {
            //метка времени завершения для sox
            //$query = "UPDATE `call_rec` SET `duration`=($time-`start`) WHERE `record`='".$peer_paar["call"][$indx]["record"]."'";     //Добавить  PART для HOLD            
            //$log->debug("stop");
        } else {
            //$log->debug("start");
            $m = new Yate("chan.masquerade");
            $m->params["message"] = 'chan.record';
            $m->params["id"] = $peer_paar["call"][$indx]["peerid"];            
            $m->params["call"] =  'wave/record//var/lib/misc/records/leg/'.$peer_paar["call"][$indx]["record"].'_0.slin';
            $m->params["peer"] =  'wave/record//var/lib/misc/records/leg/'.$peer_paar["call"][$indx]["record"].'_1.slin';
            $m->Dispatch();            
            //$query = "INSERT INTO `call_rec` (`start`,`record`) VALUES ($time,'".$peer_paar["call"][$indx]["record"]."')";   //Добавить  PART для HOLD            
        }                
        //$res = query_nores($query);
        $indx++;
    }

    $indx = 0;
    while (isset($peer_paar["conf"][$indx])) {
        $conf = $peer_paar["conf"][$indx];
        //$log->debug("Conf:".$conf);
        //$log->debug("MSG [`".implode("`,`", array_keys($conf))."`] VALUES ('".implode("','", $conf)."')");
        
        if($peer_paar["conf"][$indx]["ended"]) {
            //$log->debug("stop conf");
        } else {
            //$log->debug("start conf");
            if (isset($peer_paar["conf"][$indx][1])) {
                $time = microtime(true);
                $peer_indx = 0;
                while (isset($peer_paar["conf"][$indx][$peer_indx])) {
                    $m = new Yate("chan.masquerade");
                    $m->params["message"] = 'chan.record';
                    $m->params["id"] = $peer_paar["conf"][$indx][$peer_indx]["chan"];            
                    $m->params["call"] =  'wave/record//var/lib/misc/records/leg/'.$peer_paar["conf"][$indx]["record"].'_'.$peer_indx.'.au';                
                    $m->Dispatch();
                    $peer_indx++;
                }
                //$query = "INSERT INTO `call_rec` (`start`,`record`,`connect_type`,`peer_count`,`called`) VALUES ($time,'".$peer_paar["conf"][$indx]["record"]."','conf',$peer_indx,'".$peer_paar["conf"][$indx]["called"]."')";   //Добавить  PART для HOLD
                //$log->debug($query);
                //$res = query_nores($query);
            }
        }
        $indx++;
    }
}

function batch_file($ev) {
    global $record_chanels;
    global $log;
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
    global $log;

    static $queue = array();
    static $process;
    
    if (!empty($records)) {
        foreach ($records as $recid)
            if (!in_array($recid,$queue))
                $queue[]=$recid;
        //$log->debug("input:".implode(",", $records)."|".implode(",", $queue));
        //$queue = array_merge($queue, $records);        
    }

    if (is_resource($process)) {
        $meta_info = proc_get_status($process);
        $log->debug(microtime(true)." batch:".implode(",", $meta_info)."|".$meta_info["running"]);
        if(!$meta_info["running"]) {
            $rec_id = array_shift($queue);                                                                                  //удалять из mysql
            $exit_code = proc_close($process);
            if ($meta_info["exitcode"] == 0)
                $query = "DELETE FROM `call_rec` WHERE `record` = '".$rec_id."'";
            else
                $query = "UPDATE `call_rec` SET `close`='".$meta_info["exitcode"]."' WHERE `record`='".$rec_id."'";
            $res = query_nores($query);
            //$log->debug(microtime(true)." close[".$rec_id."]".$exit_code);
            if(empty($queue))
                $time_step = 3600;
        }
    }

    if (!is_resource($process) && !empty($queue))  {
        $log->debug("Start");
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
        $log->debug(microtime(true)." Start batch:".$cmd_parts);

        $process = proc_open($cmd_parts, $descriptor, $pipes);
        $next_time = 0;
        $time_step = 2;        
        $meta_info = proc_get_status($process);
        $log->debug(microtime(true)." batch:".implode(",", $meta_info)."|".$meta_info["running"]);
    } 

}

function merge_file1($records = array()) {
    global $record_chanels;
    global $log;
    global $log;
    
    static $queue = array();
    //static $stdout;
    static $pipes = array();

    static  $descriptor = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w'),
        2 => array('pipe', 'w'),
    );
    static $process;


    if (empty($records)) {
        if (is_resource($process)) {
            $meta_info = proc_get_status($process);
            $log->debug(microtime(true)." batch:".implode(",", $meta_info));
            
            
            //$log->debug("Pooling:".$a);
            /*$output = array();
            $a = fread($pipes[2],1);*/            
            /*$StdOut = '';
            //while(!feof($pipes[1])) {                
                $StdOut .= fgets($pipes[1]);
                //$log->debug("Pooling:".$StdOut);
            //}
            $log->debug("Pooling check:".implode(",", $output));
            //$output = stream_get_contents($pipes[1],4);        
            //$log->debug("check:".$output);*/
        }
    } else {
        $queue = array_merge($queue, $records);
    //$rec_id = $queue[0];
    $rec_id = array_shift($queue);
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
    
    //$cmd_parts = "/usr/share/yate/scripts/ts.sh 12 32"; 
    //passthru("/usr/share/yate/scripts/script.sh ".$cmd_parts);
    
    
    //$process = proc_open($cmd_parts, $descriptor, $pipes);
    if (!is_resource($process)) { 
        //$process = proc_open("bash", $descriptor, $pipes);
        $process = proc_open($cmd_parts, $descriptor, $pipes);
        $meta_info = proc_get_status($process);
        $log->debug("Start batch:".implode(",", $meta_info));
    }

    /*if (is_resource($process)) {
        $log->debug($cmd_parts);
        fwrite($pipes[0], $cmd_parts."\n");
        $meta_info = proc_get_status($process);
        $log->debug("START batch:".implode(",", $meta_info));
        //sleep(2);
        //$result = fread($pipes[1],15);
        //$result = fread($pipes[2],1);
        $result = fgets($pipes[2]);
        $log->debug("1/check:".$result);        
        //$output = stream_get_contents($pipes[1],4);        
        //$log->debug("check:".$output);
    }    */
    /*$cmd_parts = " ts.sh\n";

    $process = proc_open('bash', $descriptorspec, $pipes);
    if (is_resource($process)) {
        $log->debug($cmd_parts);
        fwrite($pipes[0], "source $cmd_parts\n");
        $log->debug("Starts sh");
    }*/
    }

        

   



    /*if (empty($stdout) && !empty($queue)) {        
        $log->debug("Merge queue:".implode(",", $queue));
        $rec_id = $queue[0];
        $max_parts = max(array_keys($record_chanels[$rec_id]));
        $parts_count = 0;
        $cmd_parts = "";
        for ($parts = 0; $parts <= $max_parts; $parts++) {
            //if ($record_chanels[$rec_id][$parts]["duration"]>0.1) {
                $parts_count++;
                $cmd_parts = $cmd_parts." ".$parts." ".$record_chanels[$rec_id][$parts]["peer_count"]." ".$record_chanels[$rec_id][$parts]["duration"];
            //}
        }
        $cmd_parts = $rec_id." ".$parts_count.$cmd_parts;
        $log->debug("/usr/share/yate/scripts/script.sh ".$cmd_parts);


        $stdout = popen("/usr/share/yate/scripts/script.sh ".$cmd_parts, 'r');
        $log->debug("Start:".$stdout);

        $query = "UPDATE `call_rec` SET `close`=2 WHERE `record`='".$rec_id."'";    
        $res = query_nores($query);
        //pclose($stdout);            
        //$log->debug($stdout);
        return true;
    } */
    
    
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