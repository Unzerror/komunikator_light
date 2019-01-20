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

$type_debug = 'route';

$pickup_key = "000"; //key marking that a certain call is a pickup
$adb_keys = "**";  //keys marking that the address book should be used to find the real called number

$s_moh = array();
$moh_time_step = 60 * 5; // 5 minutes
$moh_next_time = 0;

$route_phat = array(); //история маршрутизации вызова
$max_routes = 3; //число альтернативных шлюзов для звонка

$s_fallbacks = array();
$s_params_assistant_outgoing = array();
$stoperror = array("busy", "noanswer", "looping", "Request Terminated", "Routing loop detected");

function chek_voicemail() {
    global $voicemail;

        $query = "SELECT value FROM settings WHERE param='vm'";
        $res = query_to_array($query);
        if (!$res || !count($res)) {
            debug("Voicemail is not set!!!");
            $voicemail = NULL;
            return false;
        } else
            $voicemail = $res[0]["value"];
}

function format_array($arr) {
    $str = str_replace("\n", "", print_r($arr, true));
    $str = str_replace("\t", "", $str);
    while (strlen($str) != strlen(str_replace("  ", " ", $str)))
        $str = str_replace("  ", " ", $str);
    return $str;
}

/**
 * Build the location to send a call depending on the protocol
 * @param $params array of type "field_name"=>"field_value"
 * @param $called Number where the call will be sent to
 * @return String representing the resource the call will be made to
 */
function build_location($params, $called, &$copy_ev) {
    set_additional_params($params, $copy_ev);

    if ($params["username"] && $params["username"] != '') {
        // this is a gateway with registration
        $copy_ev["line"] = $params["gateway"];
        return "line/$called";
    } else {
        switch ($params["protocol"]) {
            case "sip":
                return "sip/sip:$called@" . $params["server"] . ":" . $params["port"];
            case "h323":
                return "h323/$called@" . $params["server"] . ":" . $params["port"];
            case "pstn":
                $params["link"] = $params["gateway"];
                $copy_ev["link"] = $params["link"];
                return "sig/" . $called;
            case "PRI":
            case "BRI":
                $query = "SELECT sig_trunk FROM sig_trunks WHERE sig_trunk_id=" . $params["sig_trunk_id"];
                $res = query_to_array($query);
                if (!count($res))
                    debug("Can't find sig_trunk for gateway " . $params["gateway"]);
                $params["link"] = (count($res)) ? $res[0]["sig_trunk"] : $params["gateway"];
                $copy_ev["link"] = $params["link"];
                return "sig/" . $called;
            case "iax":
                if (!$params["iaxuser"])
                    $params["iaxuser"] = "";
                $location = "iax/" . $params["iaxuser"] . "@" . $params["server"] . ":" . $params["port"] . "/" . $called;
                if ($params["iaxcontext"])
                    $location .= "@" . $params["iaxcontext"];
                return $location;
        }
    }
    return NULL;
}

function set_additional_params($gateway, &$copy_ev) {
    $to_set = array("interval", "authname", "domain", "outbound", "localaddress", "rtp_localip", "oip_transport");

    foreach ($gateway as $name => $val)
        if (in_array($name, $to_set) && $val) {
            $copy_ev[$name] = $val;
        }
}

/**
 * Get the modified number
 * @param $route Array of params representing the modificatios resulted usually from an sql query
 * @param $nr Number before rewriting
 * @return Number resulted after the modifications were applied. If resulted number is empty then original number is returned
 * Note!! The order in with the operations are performed is : cut, replace, add. So replacing will be performed on the resulted number after cutting. One must keep this in mind when using multiple transformations.
 */
function rewrite_digits($route, $nr) {
    $result = $nr;
    if ($route["nr_of_digits_to_cut"] && $route["position_to_start_cutting"]) {
        $result = substr($nr, 0, $route["position_to_start_cutting"] - 1) . substr($nr, $route["position_to_start_cutting"] - 1 + $route["nr_of_digits_to_cut"], strlen($nr));
    }
    if ($route["position_to_start_replacing"] && $route["digits_to_replace_with"]) {
        if (!$route["nr_of_digits_to_replace"])
            return $route["digits_to_replace_with"];
        $result = substr($result, 0, $route["position_to_start_replacing"] - 1) . $route["digits_to_replace_with"] . substr($result, $route["position_to_start_replacing"] + $route["nr_of_digits_to_replace"] - 1, strlen($result));
    }
    if ($route["position_to_start_adding"] && $route["digits_to_add"]) {
        $result = substr($result, 0, $route["position_to_start_adding"] - 1) . $route["digits_to_add"] . substr($result, $route["position_to_start_adding"] - 1, strlen($result));
    }
    if (!$result) {
        debug("Wrong: resulted number is empty when nr='$nr' and route=" . format_array($route));
        return $nr;
    }
    return $result;
}

/**
 * Handle the call.route message.
  */
function get_SQL_concat($data) {
    global $db_type_sql;
    if (!is_array($data))
        return $data;
    if (count($data) == 0)
        return '';
    if (count($data) == 1)
        return $data[0];
    if ($db_type_sql == 'mysqli') {
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

function makeRoutePhat($rout_hop,$reason) {
    global $route_phat;

    $r_hop = count($route_phat);
            
    if ($reason == "Start") {
        $route_phat = array();        
        $route_phat[0]["route_hop"] = $rout_hop;
    } elseif (is_numeric($reason)) {
        $route_phat[$r_hop]["route_hop"] = $route_phat[$r_hop-1]["route_hop"];
        $route_phat[$r_hop-1]["route_hop"] = $rout_hop;
        $route_phat[$r_hop-1]["reason"] = $reason;        
    } else {       
        $route_phat[$r_hop-1]["reason"] = $reason;
        $route_phat[$r_hop]["route_hop"] = $rout_hop;
    }      
}

//Убрать из Mysql transfer и pickup!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//где-то есть update NEXT в route
//Для pickup - поиск billid сделать
//Запись очередей!!!
///!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
function writeRoute($route_status) {
    global $route_phat;
    global $ev;

    $m = new Yate("route.register");
    $m->params["connect"] = microtime(true);
    $m->params["id"] = $ev->GetValue("id");
    $m->params["chan"] = $ev->GetValue("id");
    $m->params["billid"] = $ev->GetValue("billid");
    $m->params["caller"] = $ev->GetValue("caller");
    $m->params["keys"] = "connect;id;chan;billid;caller;called;dynamic_parametrs";
    $m->params["dynamic_parametrs"] = "route";
    $m->params["route"] = "connect_type;called;duration;status";

    $rout_max_hop = count($route_phat)-1;    
    $type_message = "route";
    $duration = 0;
    for ($r_hop = 0; $r_hop < $rout_max_hop; $r_hop++) {
        $r_reason = $route_phat[$r_hop]["reason"];
        $r_call = $route_phat[$r_hop]["route_hop"];
        if (is_numeric($r_reason)) {           
           $type_message = "next";
           $duration = $r_reason;
           $r_reason = $route_phat[$r_hop+1]["route_hop"];
           if ($r_call == 'vm') {
               $r_call = 'cs_voicemail';                   
           }  else
               $r_call = $route_phat[$r_hop]["route_hop"];
        }
        if ($r_reason == 'VM')
            $r_call = $route_phat[$r_hop+1]["route_hop"];

        
        $m->params["route.".$r_hop] = $type_message."|".$r_call."|".$duration."|".$r_reason;
    }    
    
    if ((substr($route_phat[$rout_max_hop]["route_hop"],0,9)) == "external/")
             $rout_max_hop = $rout_max_hop - 1;

    $ev->params["called"] = $route_phat[$rout_max_hop]["route_hop"];
    
    $m->params["called"] = $route_phat[$rout_max_hop]["route_hop"];
    $m->Dispatch();    
}

function writeRoute1($route_status) {
    global $route_phat;
    global $ev;

    $rout_max_hop = count($route_phat)-1;    
    $time_route = microtime(true);
    $billid = $ev->GetValue("billid");
    //$chan = $ev->GetValue("chan");
    $chan = $ev->GetValue("id");
    $caller = $ev->GetValue("caller");

    $sql = array();
    $dt = 0.001;
    $dt_to_call = 0.2;
    $duration = 0;
    $type_message = "route";    
    for ($r_hop = 0; $r_hop < $rout_max_hop; $r_hop++) {    
            $time_route = $time_route + $dt;
            $r_reason = $route_phat[$r_hop]["reason"];
            $r_call = $route_phat[$r_hop]["route_hop"];
            if (is_numeric($r_reason)) {
               $time_route = $time_route + $dt_to_call;
               $type_message = "next";
               $duration = $r_reason;
               /*
               $r_call = $route_phat[$r_hop+1]["route_hop"];
               if ($r_call == 'vm') {
                   $r_reason = 'cs_voicemail';                   
               }  else
                   $r_reason = $route_phat[$r_hop]["route_hop"];
               */
               $r_reason = $route_phat[$r_hop+1]["route_hop"];
               if ($r_call == 'vm') {
                   $r_call = 'cs_voicemail';                   
               }  else
                   $r_call = $route_phat[$r_hop]["route_hop"];
            }
            if ($r_reason == 'VM')
                $r_call = $route_phat[$r_hop+1]["route_hop"];
            $sql[]= '("'.$time_route.'","'.$chan.'","'.$type_message.'","'.$billid.'","'.$caller.'","'.$r_call.'","'.$duration.'","'.$r_reason.'")';
    }
    $safe_arr = implode(',', $sql);     
    $query = "INSERT INTO call_route (time, chan, direction, billid, caller, called, duration, status) VALUES $safe_arr";         //Добавить таблицу  call_route
    $res = query_nores($query);  

    if ((substr($route_phat[$rout_max_hop]["route_hop"],0,9)) == "external/")
         $rout_max_hop = $rout_max_hop - 1;

    $ev->params["called"] = $route_phat[$rout_max_hop]["route_hop"];

    $m = new Yate("route.register");
    $m->params["connect"] = microtime(true);
    $m->params["id"] = $ev->GetValue("id");
    $m->params["chan"] = $ev->GetValue("id");
    $m->params["billid"] = $ev->GetValue("billid");
    $m->params["caller"] = $ev->GetValue("caller");
    $m->params["called"] = $route_phat[$rout_max_hop]["route_hop"];
    foreach ($route_phat as $num_hop=>$data) {
        $m->params["route.".$num_hop] = $data["route_hop"];
        $m->params["reason.".$num_hop] = $data["reason"];        
    }    
    $m->params["keys"] = "connect;id;billid;caller;called";
    $m->Dispatch(); 

    /*$m = new Yate("route.register");
    $m->params["connect"] = microtime(true);
    $m->params["id"] = $chan;
    $m->params["billid"] = $billid;
    $m->params["caller"] = $caller;
    $m->params["called"] = $route_phat[$rout_max_hop]["route_hop"];
    $m->params["keys"] = "connect;id;billid;caller;called";
    $m->Dispatch();*/
}

//убрать дубль routetoDiD()
function get_DID($called) {
    //global $system_prefix;
    global $ev;

    $query = "SELECT destination FROM dids WHERE number='$called'";
    $res = query_to_array($query);
    if (count($res)) {
        if (isset($ev->params["pbxoper"])) {
             if ($ev->params["pbxoper"] == "fortransfer")
                 return $called;
        }                 
        return $res[0]["destination"];
    }
    return false;   
}

/**
 * Get the location where to send a call
 * @param $called Number the call was placed to
 * @return String representing the resource where to place the call
 * Note!! this function is used only when diverting calls. does not check for any kind of forward, and mimics the fallback when diverting using fork to send the call to each destination
 */
function get_distantion($called) {

   // Number on PBX ???   
   $query = "SELECT extension_id, inuse_count FROM extensions WHERE extension='$called'";
   $res = query_to_array($query);
   if (!count($res))
        return NULL;                           // no find extension number on PBX
   
    $extension_id = $res[0]["extension_id"];
    $busy_extension = $res[0]["inuse_count"];
      
    //read route parametrs
    $query = "SELECT param, value FROM pbx_settings WHERE extension_id='$extension_id'";
    $res = query_to_array($query);
    foreach ($res as $row) 
       $forward[$row["param"]] = $row["value"];
    
    //distantion if always forward
    if (isset($forward["forward"]) && $forward["forward"] != '') {
         $distantion = $forward["forward"];
         makeRoutePhat($distantion,"Always");
         return $distantion;
    }

    //distantion if busy
    //>>>>>Добавить проверку для занятости линий
    //Добавить настройку 1 номер - 1 линия
    //$query = "SELECT extension_id  FROM ext_connection WHERE extension='$called' and inuse_count > 0";
    //$res = query_to_array($query);
    //
    //Доделать в маршрутах проверку на занятость номера
    //
    if ($busy_extension > 0 && isset($forward["forward_busy"]) && $forward["forward_busy"] != '' ) {
         $distantion = $forward["forward_busy"];         
         makeRoutePhat($distantion,"Busy");
         return $distantion;
    }
   
    //extension enabled ???
    //Звонок на занятый телефон    
    //$chek_busy = '= 0';   // Утащить выше
    //
    //$query = "SELECT GROUP_CONCAT(location SEPARATOR ' ') as location FROM ext_connection WHERE extension='$called' and inuse_count '$chek_busy'";
    $query = "SELECT GROUP_CONCAT(location SEPARATOR ' ') as location FROM ext_connection WHERE extension='$called'";
    $res = query_to_array($query);          
    if ($res[0]["location"]) {
         $destination = 'fork '.$res[0]["location"];                    //direct path to extension
         //makeRoutePhat($called,"Call");
               
         //alternative path of routing after time except
         if (isset($forward["forward_noanswer"]) && $forward["forward_noanswer"] != '' ) {
              if (!isset($forward["noanswer_timeout"]) || $forward["noanswer_timeout"] == '')
                   $forward["noanswer_timeout"] = 30;                      //defualt time to hold 30sec
              if ($forward["forward_noanswer"] == "vm") {
                   $destination = $destination.' |exec='.($forward["noanswer_timeout"]*1000).' lateroute/vm_'.$called;
                   makeRoutePhat('vm', $forward["noanswer_timeout"]);
              } else {                   
                   $destination = $destination.' |exec='.($forward["noanswer_timeout"]*1000).' lateroute/'.$forward["forward_noanswer"].' | lateroute/vm_'.$called;                   
                   makeRoutePhat($forward["forward_noanswer"], $forward["noanswer_timeout"]);
              }
         } else {
             $destination = $destination.' | lateroute/vm_'.$called;
             //makeRoutePhat('vm', 'error_route');
         }
    } else {

         //extension disabled
         if (isset($forward["forward_noanswer"]) && $forward["forward_noanswer"] != '') 
              $destination = $forward["forward_noanswer"];              
         else
              $destination = "vm";
         makeRoutePhat($destination,"Disabled");         
    } 
    return $destination;
}

/**
 * Route a call to an extension. Set the params for all the types of divert.
 * @param $called Number the call was placed to
 * @return Bool true if number was routed, false otherwise
 */
function routeToExtension($caller,&$called) {
    global $ev, $voicemail, $system_prefix, $query_on, $debug_on;

    $hop = 0;                                    //количество иттераций пиоска маршрута
    $distantion = array();
    $distantion[$hop] = $called;                 //маршрут переадрессации    
    $cycle = 0;                                              //счетчик повторов
    //поиск маршрута
    while (strlen($distantion[$hop]) == 3 && $cycle < 2 && $distantion[$hop] != $caller) {
        $hop = $hop + 1;
        $distantion[$hop] = get_distantion($distantion[$hop-1]);
        if ($distantion[$hop] != $caller) {            
            $dids = get_DID($distantion[$hop]);
            if ($dids) {
                $distantion[$hop] = $dids;            
                makeRoutePhat($dids,"DiD");
           }
           $cycle = 0;
           foreach ($distantion as $path) {
                if ($path == $distantion[$hop])
                    $cycle = $cycle + 1;
           }
        }                 
    }

    if ( $distantion[$hop] == '' )
        return false;
    elseif ($distantion[$hop] == $caller)
        $cycle = 3;

    if ((substr($distantion[$hop],0,2)) == "vm" || $cycle > 1 ) {
        $destination = $voicemail;

        if (strlen($distantion[$hop]) > 3)
            $called = substr($called,3,6);
        elseif ($cycle > 1) {
            $called = $distantion[0];            
            makeRoutePhat($called,"Cycling");
        } else
            $called = $distantion[$hop-1];
        $ev->params["called"] = $called;
        makeRoutePhat($called,"VM");
    
    } elseif (is_numeric($distantion[$hop])) {
        if (strlen($distantion[$hop]) == 2) {
            $query = "SELECT group_id FROM groups WHERE extension='$distantion[$hop]'";
            $res = query_to_array($query);
            if ($res[0]["group_id"]) {
                routeToGroup($distantion[$hop]);
                return true;
            }
        } 
        if ($hop > 0) {
             $ev->params["call_type"] = "from inside";
             $system_prefix = "from inside";
        }   
        $called = $distantion[$hop];
        return false;
    
    } else {
       /*if ((substr($distantion[$hop],0,9)) == "external/")
          $ev->params["called"] = $distantion[$hop-1];*/
       $destination = $distantion[$hop];
       $ev->params["call_type"] = "from inside";       
    }
    
    $ev->params["query_on"] = ($query_on) ? "yes" : "no";
    $ev->params["debug_on"] = ($debug_on) ? "yes" : "no";
    //$ev->params["mohlist"] = "/var/lib/misc/moh/kpv.mp3";
    
    set_retval($destination, "offline");
    return true;
}

/**
 * Set the array of music_on_hold by playlist. This array is updated periodically.
 * @param $time, Time when function was called. It's called with this param after engine.timer, If empty i want to update the list, probably because i didn't have the moh for a certain playlist
 */
function set_moh($time = NULL) {
    global $moh_time_step, $moh_next_time, $s_moh, $last_time, $uploaded_prompts;

    if (!$time)
        $time = $last_time;
    $moh_next_time = $time + $moh_time_step;
    $query = "SELECT playlists.playlist_id, playlists.in_use, music_on_hold.file as music_on_hold FROM playlists, music_on_hold, playlist_items WHERE playlists.playlist_id=playlist_items.playlist_id AND playlist_items.music_on_hold_id=music_on_hold.music_on_hold_id ORDER BY playlists.playlist_id";
    $playlists = query_to_array($query);    
    $l_moh = array();
    for ($i = 0; $i < count($playlists); $i++) {
        $playlist_id = $playlists[$i]["playlist_id"];
        if (!isset($l_moh[$playlist_id]))
            $l_moh[$playlist_id] = '';
        $moh = "$uploaded_prompts/moh/" . $playlists[$i]["music_on_hold"];
        $l_moh[$playlist_id] .= ($l_moh[$playlist_id] != '') ? ' ' . $moh : $moh;
    }
    $s_moh = $l_moh;
    
}

/**
 * Route a call to a group. Using this function implies that the queues module is configured.
 * @param $called Number where the call was placed to
 * @return Bool true if call was routed to a group, false otherwise
 */
function routeToGroup($called) {
    global $ev, $s_moh, $uploaded_prompts;

    //  debug("entered routeToGroup('$called')");   
    if (strlen($called) == 2 ) {
        debug("trying routeToGroup('$called')");
        // call to a group
        $query = "SELECT group_id, (CASE WHEN playlist_id IS NULL THEN (SELECT playlist_id FROM playlists WHERE in_use=1) else playlist_id END) as playlist_id FROM groups WHERE extension='$called'";        
        $res = query_to_array($query);
        if (!count($res))
            return false;
        if (isset($ev->params["pbxoper"])) {
            if ($ev->params["pbxoper"] == "fortransfer") {
                  return false;                                                  //>>>переделать на VM
            }
        }

        $query1 = "INSERT INTO call_group_history (time, chan, called, billid) VALUES (".microtime(true).",'".$ev->GetValue("id")."','$called','".$ev->GetValue("billid")."')";
        $res1 = query_nores($query1);
        set_retval("queue/" . $res[0]["group_id"]);
        if (!isset($s_moh[$res[0]["playlist_id"]]))
            set_moh();
        $ev->params["mohlist"] = $s_moh[$res[0]["playlist_id"]];
        if ($ev->GetValue("copyparams"))
            $ev->params["copyparams"] .= ",caller,callername,billid,orig_called";
        else
            $ev->params["copyparams"] = "caller,callername,billid,orig_called";
        return true;
    }
    return false;
}

/**
 * Detect whether a call is a pickup or not. Route the call to the appropriate resource if so
 * @param $called Number where the call was placed to
 * @param $caller Who innitiated the call
 * @return Bool true if call is a pickup. False otherwise
 */
function makePickUp($called, $caller) {
    global $ev;
    global $pickup_key;

    //debug("entered makePickUp(called='$called',caller='$caller')");

    $keyforgroup = strlen($pickup_key) + 2;
    if (strlen($called) == $keyforgroup && substr($called, 0, strlen($pickup_key)) == $pickup_key) {
        // someone is trying to pickup a call that was made to a group, (make sure caller is in that group)
        $extension = substr($called, strlen($pickup_key), strlen($called));
        $query = "SELECT group_id FROM groups WHERE extension='$extension' AND group_id IN (SELECT group_id FROM group_members, extensions WHERE group_members.extension_id=extensions.extension_id AND extensions.extension='$caller')";
        $res = query_to_array($query);
        if (!count($res))
            set_retval("tone/congestion");
        else
            set_retval("pickup/" . $res[0]["group_id"]);
        return true;
    }

    if (substr($called, 0, strlen($pickup_key)) == $pickup_key) {
        // try to improvize a pick up -> pick up the current call of a extension that is in the same group as the caller
        $extension = substr($called, strlen($pickup_key), strlen($called));
        $query = "SELECT chan,billid FROM call_logs, extensions, group_members WHERE direction='outgoing' AND ended = 0 AND extensions.extension=call_logs.called AND extensions.extension='$extension' AND extensions.extension_id=group_members.extension_id AND group_members.group_id IN (SELECT group_id FROM group_members NATURAL JOIN extensions WHERE extensions.extension='$caller')";
        $res = query_to_array($query);
        if (count($res)) {
            
            $ev->params["billid"] = $res[0]["billid"];     // не помогает - второе плечо пднимает с исходным billid
            $m = new Yate("call.update");
            $m->params["id"] = $ev->params["id"];
            $m->params["module"] = $ev->params["module"];
            $m->params["status"] = $ev->params["status"];
            $m->params["caller"] = "0900";
            $m->params["called"] = "0300";
            $m->params["address"] = $ev->params["address"];
            $m->params["billid"] = $res[0]["billid"];
            $m->params["answered"] = $ev->params["answered"];
            $m->params["direction"] = $ev->params["direction"];
            $m->params["domain"] = $ev->params["domain"];
            $m->params["callid"] = $ev->params["callid"];
            $m->params["operation"] = "cdrbuild";
            //$m->params = $ev->params;            
            $m->Dispatch();
            
            set_retval("pickup/" . $res[0]["chan"]);  //make the pickup
        } else
            set_retval("tone/congestion");   //no call for this extension
        return true;
    }
    return false;
}

/**
 * Verify whether $called is a defined did
 * @param $called Number that the call was sent to.
 * @return Bool value, true if destination is a script, false
 */
function routeToDid(&$called) {    
    global $query_on, $debug_on, $ev;

    debug("entered routeToDid('$called')");
    // default route is a did
    $query = "SELECT destination FROM dids WHERE number='$called'";
    $res = query_to_array($query);
    if (count($res)) {
        if (is_numeric($res[0]["destination"])) {
        // just translate the called number
            $called = $res[0]["destination"];
            makeRoutePhat($called,"DiD");
        } else {
            if (isset($ev->params["pbxoper"])) {
                if ($ev->params["pbxoper"] == "fortransfer")
                     return false;
            }
            $ev->params["query_on"] = ($query_on) ? "yes" : "no";
            $ev->params["debug_on"] = ($debug_on) ? "yes" : "no";
            // route to a script
            //$ev->params["called"] = 'cs_attendant';
			makeRoutePhat($called,"DiD");
            set_retval($res[0]["destination"]);            
            return true;
        }
    }
    return false;
}

/**
 * Generate all the possible names that could match a certain number
 * @param $number The number that was received
 * @return String containing all the names separated by "', '"
 */
function get_possible_options($number) {
    $posib = array();

    $alph = array(
        2 => array("a", "b", "c"),
        3 => array("d", "e", "f"),
        4 => array("g", "h", "i"),
        5 => array("j", "k", "l"),
        6 => array("m", "n", "o"),
        7 => array("p", "q", "r", "s"),
        8 => array("t", "u", "v"),
        9 => array("w", "x", "y", "z")
    );

    for ($i = 0; $i < strlen($number); $i++) {
        $digit = $number[$i];
        $letters = $alph[$digit];
        if (!count($posib)) {
            $posib = $letters;
            continue;
        }
        $s_posib = $posib;
        for ($k = 0; $k < count($letters); $k++) {
            if ($k == 0)
                for ($j = 0; $j < count($posib); $j++)
                    $posib[$j] .= $letters[$k];
            else
                for ($j = 0; $j < count($s_posib); $j++)
                    array_push($posib, $s_posib[$j] . $letters[$k]);
        }
    }
    $options = implode("', '", $posib);
    return "'$options'";
}

/**
 * See if this call uses the address book. If so then find the real number the call should be sent to and modify $called param
 * @param $called Number the call was placed to
 */
function routeToAddressBook(&$called, $username) {
    global $adb_keys;

    debug("entered routeToAddressBook(called='$called', username='$username')");

    if (substr($called, 0, strlen($adb_keys)) != $adb_keys)
        return;

    debug("trying routeToAddressBook(called='$called', username='$username')");

    $number = substr($called, strlen($adb_keys), strlen($called));
    $possible_names = get_possible_options($number);
    $query = "SELECT short_names.number, 1 as option_nr FROM short_names, extensions WHERE extensions.extension='$username' AND extensions.extension_id=short_names.extension_id AND short_name IN ($possible_names) UNION SELECT number, 2 as option_nr FROM short_names WHERE extension_id IS NULL AND short_name IN ($possible_names) ORDER BY option_nr";
    $res = query_to_array($query);
    if (count($res)) {
        if (count($res) > 1)
            Yate::Output("!!!!!!! Problem with finding real number from address book. Multiple mathces. Picking first one");
        $called = $res[0]["number"];
        makeRoutePhat($called,"AddressBook");
    }
    else
        debug("Called number '$called' seems to be using the address book. No match found. Left routing to continue.");
    return;
}

/**
 * Set the params needed for routing a call
 * @param $callto Resource were to place the call
 * @param $error If callto param is not set one can set an error. Ex: offline
 * @return Bool true if the event was handled, false otherwise
 */
function set_retval($callto, $error = NULL) {
    global $ev, $s_params_assistant_outgoing;

    if ($callto) {
        $id = $ev->GetValue("id");
        $s_params_assistant_outgoing[$id] = array();
        $s_params_assistant_outgoing[$id]["pbxparams"] = $ev->GetValue("pbxparams");
        $s_params_assistant_outgoing[$id]["copyparams"] = $ev->GetValue("copyparams");
        if ($ev->GetValue("line")) {
            // call is for outside
            $s_params_assistant_outgoing[$id]["pbxguest"] = true;
            $s_params_assistant_outgoing[$id]["already-auth"] = $ev->GetValue("already-auth");
            $s_params_assistant_outgoing[$id]["call_type"] = "from outside";
        } else {
            // call is for inside -> we can say the call_type for this party will be from inside
            $s_params_assistant_outgoing[$id]["already-auth"] = $ev->GetValue("already-auth");
            $s_params_assistant_outgoing[$id]["call_type"] = "from inside";
        }
        $ev->retval = $callto;
        $ev->handled = true;
        return true;
    }
    if ($error) {
        $ev->params["error"] = $error;
        //  $ev->handled = true;
    }
    return false;
}

function last_route($called) {    
    global $ev, $route_phat, $voicemail;

        $counter_route = count($route_phat);
        if ($counter_route>1) {
             makeRoutePhat($called,"error_route");
             for ($i = $counter_route-2; $i>=0 ; $i--) {
                 $last_route = $route_phat[$i]["route_hop"];
                 if (strlen($last_route) == 3) {
                     $query = "SELECT extension_id FROM extensions WHERE extension='$last_route'";
                     $res = query_to_array($query);
                     if($res) {                         
                         makeRoutePhat($last_route,"VM");
                         $ev->params["called"] = $last_route;                         
                         set_retval($voicemail, "offline");                         
                         return true;
                     }
                 }
             }
        }
    return false;
}

function reversCall($username,$caller){    
}

function return_route($called, $caller, $no_forward = false) {
    global $ev, $pickup_key, $no_pbx;
    global $max_routes, $s_fallbacks, $caller_id, $caller_name;
    

    $rtp_f = $ev->GetValue("rtp_forward");

    // keep the initial called number
    $initial_called_number = $called;

    $username = $ev->GetValue("username");
    $address = $ev->GetValue("address");
    $address = explode(":", $address);
    $address = $address[0];

    $reason = $ev->GetValue("reason");
    $already_auth = $ev->GetValue("already-auth");
    $trusted_auth = $ev->GetValue("trusted-auth");
    $call_type = $ev->GetValue("call_type");

    debug("entered return_route(called='$called',caller='$caller',username='$username',address='$address',already-auth='$already_auth',reason='$reason', trusted='$trusted_auth', call_type='$call_type')");

    $params_to_copy = "maxcall,call_type,already-auth,trusted-auth";
    // make sure that if we forward any calls and for calls from pbxassist are accepted    
    $ev->params["copyparams"] = $params_to_copy;
    $ev->params["pbxparams"] = "$params_to_copy,copyparams";

    if ($already_auth != "yes" && $reason != "divert_busy" && $reason != "divert_noanswer") {
        if (!$username) {
              //$query = "SELECT * FROM extensions WHERE extension='$caller'";              //выводит в лог пароли
              $query = "SELECT extension_id FROM extensions WHERE extension='$caller'";
              $res = query_to_array($query);
              if (count($res)) {
                   debug("could not auth call but '$caller' seems to be in extensions");
                   set_retval(NULL, "noauth");
                   return false;
              }
        } else {
            $domain = $ev->GetValue("domain");
            $query = "SELECT extension_id,1 as trusted,'from inside' as call_type FROM extensions WHERE extension='$username' UNION SELECT incoming_gateway_id, trusted, 'from outside' as call_type FROM incoming_gateways,gateways WHERE gateways.gateway_id=incoming_gateways.gateway_id AND gateways.username='$username' AND (incoming_gateways.ip='$address' OR incoming_gateways.ip='$domain' OR gateways.domain='$domain')";
            $res = query_to_array($query);
        }
        if (!count($res)) {
			debug("could not auth call");
			set_retval(NULL, "noauth");
			return false;
		}
        $trusted_auth = ($res[0]["trusted"] == 1) ? "yes" : "no";
        $call_type = $res[0]["call_type"]; //($username) ? "from inside" : "from outside";  // from inside/outside of freesentral
    }
    
    //шлюзование через extension c проверкой на то что номер начинается с 7 и 11 значный
    //можно добавить переадрессацию с шлюзов
    if(substr($called,0,5) == 'bx24-') {
        $query = "SELECT location FROM kommunikator.dids JOIN ext_connection ON ext_connection.extension_id=dids.extension_id WHERE dids.number='$caller' and dids.destination='external/nodata/bx24.php'";
        $res = query_to_array($query);
        debug("BX24 route call to $called from $caller");
        if (!count($res)) {
            debug("Could not find BX24 worked external gateway");        //нежно проговорить текст? : закомментировать и раскоментировать кусок в bx4.php
            return false;
        }

        $called_num = substr($called,5);
        $location = $res[0]["location"];
        $gateway_location = substr($location,strrpos($location, '@'));
        $gateway_num = substr($location,8,3);

        $ev->retval = 'sip/sip:'.$called_num.$gateway_location;
        $ev->params["caller"] = $gateway_num;
        $ev->params["called"] = $called_num;
        $ev->params["callername"] = $caller;
        $ev->params["username"] = $gateway_num;
        $ev->params["authname"] = $gateway_num;
        $ev->handled = true;    
        return true;
    }

    if ( (substr($called,0,1) == 7) && (strlen($called) == 11) ) {
        $query = "SELECT * FROM kommunikator.dids WHERE dids.number='$caller' and dids.destination='external/nodata/bx24.php'";
        $res = query_to_array($query);
        if ( count($res)) {
            $replaced_num = $res[0]["description"];
            if ( is_numeric($replaced_num) )        
                $ev->params["called"] = $replaced_num.substr($called,1);

            //makeRoutePhat($called,"DiD");
            $ev->params["query_on"] = ($query_on) ? "yes" : "no";
            $ev->params["debug_on"] = ($debug_on) ? "yes" : "no";
            set_retval($res[0]["destination"]);
            return true;
        }
    }

    ChekNextRoute($caller,$called);

    debug("classified call as being '$call_type'");
    // mark call as already autentified
    $ev->params["already-auth"] = "yes";
    $ev->params["trusted-auth"] = $trusted_auth;
    $ev->params["call_type"] = $call_type;
    
    if (isset($ev->params["pbxoper"])) {
        if ($ev->params["pbxoper"] == "fortransfer")
              $ev->params["fork.fake"] = "tone/ring";
        elseif ($ev->params["pbxoper"] == "transfer") {
            $ev->params["fork.fake"] = "tone/ring";
            $query1 = "INSERT INTO call_route (time, chan, direction, billid, caller, called, duration, status, reason) ".
                      "VALUES (".microtime(true).", '".$ev->GetValue("id")."', 'transfer','".$ev->GetValue("billid")."', '".
                      $ev->GetValue("caller")."', '".$ev->GetValue("called")."', 0, 'transfer', '".$ev->GetValue("diverter")."')";
            $res1 = query_nores($query1);
        }
    }
    
    if ($call_type)
         if ($call_type != "from inside")    
               $ev->params["pbxguest"] = true;

    if (reversCall($username, $caller)) {
        makeRoutePhat($called,"reversCall");
        return true;
    }

    routeToAddressBook($called, $username);

    if (routeToDid($called))
        return true; 
    
    if (routeToGroup($called))
         return true;
    
    if (makePickUp($called, $caller)) {        
        $query1 = "INSERT INTO call_route (time, chan, direction, billid, caller, called, duration, status, reason) ".
                  "VALUES (".microtime(true).", '".$ev->GetValue("id")."', 'pickup','".$ev->GetValue("billid")."', '".
                   $ev->GetValue("caller")."', '".substr($called, strlen($pickup_key), strlen($called))."', 0, 'pickup', '".$ev->GetValue("caller")."')";
        $res1 = query_nores($query1);
        return true;
    }

    
    if (routeToExtension($caller,$called))
           return true;
    
    if ($call_type == "from outside" && $initial_called_number == $called && $trusted_auth != "yes") {
        // if this is a call from outside our system and would be routed outside(from first step) and the number that was initially called was not modified with passing thought any of the above steps  => don't send it
        debug("forbidding call to '$initial_called_number' because call is 'from outside'");
        makeRoutePhat($called,"noroute");
        set_retval(null, "noautoauth");
        return false;
    }

   $query = "SELECT dial_plan_id,dial_plan,priority,prefix,d.gateway_id,nr_of_digits_to_cut,position_to_start_cutting,nr_of_digits_to_replace,digits_to_replace_with,".
             "position_to_start_replacing,position_to_start_adding,digits_to_add,g.gateway_id,gateway,protocol,server,type,username,password,enabled,description,`interval`,".
             "authname,domain,outbound,localaddress,formats,rtp_localip,ip_transport,oip_transport,port,iaxuser,iaxcontext,rtp_forward,status,modified,callerid,".
             "case when callername is null then '$caller' else callername end callername,send_extension,trusted,sig_trunk_id ".
             "FROM dial_plans d INNER JOIN gateways g ON d.gateway_id=g.gateway_id WHERE (prefix IS NULL OR '$called' LIKE ".get_SQL_concat(array("prefix", "'%'")).
             ") AND (g.username IS NULL OR g.status='online') ORDER BY length(coalesce(prefix,'')) DESC, priority LIMIT $max_routes";
    $res = query_to_array($query);
    
    if (!count($res)) {
        debug("Could not find a matching dial plan=> rejecting with error: noroute");
        if(!last_route($called)) {
           set_retval(NULL, "noroute");
           makeRoutePhat($called,"noroute");
           return false;
        } else
           return true;
    }
    $id = ($ev->GetValue("true_party")) ? $ev->GetValue("true_party") : $ev->GetValue("id");
    $start = count($res) - 1;
    $j = 0;
    $fallback = array();
    for ($i = $start; $i >= 0; $i--) {
        $fallback[$j] = $ev->params;
        $custom_caller_id = ($res[$i]["callerid"]) ? $res[$i]["callerid"] : $caller_id;
        $custom_caller_name = ($res[$i]["callername"]) ? $res[$i]["callername"] : $caller_name;
        $custom_domain = $res[$i]["domain"];
        if ($res[$i]["send_extension"] == 0) {
            $fallback[$j]["caller"] = $custom_caller_id;
            if ($custom_domain)
                $fallback[$j]["domain"] = $custom_domain;
            $fallback[$j]["callername"] = $custom_caller_name;
        }elseif ($system_prefix && $call_type == "from inside")
            $fallback[$j]["caller"] = $system_prefix . $fallback[$j]["caller"];
        $fallback[$j]["called"] = rewrite_digits($res[$i], $called);
        $fallback[$j]["gateway"] = $res[$i]["gateway"];
        if ($res[$i]["formats"] != NULL) {
            $fallback[$j]["formats"] = $res[$i]["formats"];
        } elseif ($ev->GetValue("formats")) {
            $fallback[$j]["formats"] = $ev->GetValue("formats");
        }
        $fallback[$j]["rtp_forward"] = ($rtp_f == "possible" && $res[$i]["rtp_forward"] == 1) ? "yes" : "no";
        $location = build_location($res[$i], rewrite_digits($res[$i], $called), $fallback[$j]);        
        if (!$location)
            continue;
        $fallback[$j]["location"] = $location;
        $j++;        
    }
    if (!count($fallback)) {
        set_retval(NULL, "noroute");
        makeRoutePhat($called,"noroute");
        return false;
    }
    $best_option = count($fallback) - 1;
    set_retval($fallback[$best_option]["location"]);
    makeRoutePhat($fallback[$best_option]["called"],"Getway");
    //makeRoutePhat($fallback[$best_option]["gateway"],"Getway");
    debug("Sending $id to " . $fallback[$best_option]["location"]);
    unset($fallback[$best_option]["location"]);
    $ev->params = $fallback[$best_option];
    unset($fallback[$best_option]);
    if (count($fallback))
        $s_fallbacks[$id] = $fallback;
    //  debug("There are ".count($s_fallbacks)." in fallback : ".format_array($s_fallbacks));
    //makeRoutePhat(,"Getway")
    debug("There are " . count($s_fallbacks) . " in fallback");
    return true;
}

function ChekNextRoute($caller,$called) {    
    global $ev;

    //<<добавиьт проверку по полю status на caller
    //$query = "SELECT * FROM call_route WHERE billid = '".$ev->GetValue("billid")."' ORDER BY call_route.time DESC LIMIT 1";
    $query = "SELECT * FROM call_route WHERE billid = '".$ev->GetValue("billid")."' and billid is NOT NULL ORDER BY call_route.time DESC LIMIT 1";
    $res = query_to_array($query);
    //<<добавиьт проверку по полю status на caller    

    if (!empty($res)) {
         if ($res[0]["direction"] == "next" and $res[0]["caller"]==$caller and ($res[0]["chan"] = $ev->GetValue("id")  or substr($res[0]["chan"],0,15) == 'auto_attendant/') ) {
              if ( ($res[0]["time"]+$res[0]["duration"]) < (microtime(true)+1) ) {
                $query = "UPDATE call_route SET direction = 'route' WHERE direction = 'next and 'billid = '".$ev->GetValue("billid")."' and time = '".$res[0]["time"]."' ";
                $res = query_nores($query);
              }              
         }
    }
}

/* Always the first action to do */
Yate::Init();

/* Comment the next line to get output only in logs, not in rmanager */
chek_debug();
chek_voicemail();

/* Set tracking name for all installed handlers */
Yate::SetLocal("trackparam","route.php");

/* Install a handler for the call routing message */
Yate::Watch("engine.timer");

Yate::Install("call.route");
Yate::Install("engine.command");
Yate::Install("engine.status");
Yate::Install("engine.debug");
Yate::Install("call.answered", 50);
Yate::Install("chan.hangup", 80);

// Ask to be restarted if dying unexpectedly 
Yate::SetLocal("restart", "true");

set_moh();

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
					if ($module != "route")
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
					if ($line == "route query on") {
						$query_on = true;
						Yate::Output(">>> Enabling query route.php module");
					} elseif ($line == "route query off") {
						$query_on = false;
						Yate::Output(">>> Disable query route.php module");
					} else
						break;
					$ev->handled = true;
					break;
				case "engine.status":
					$module = $ev->GetValue("module");
					if ($module && $module != "route.php" && $module != "misc")
						break;
					$str = $ev->retval;
					$str .= "name=route.php \r\n";
					$ev->retval = $str;
					$ev->handled = false;
					break;
				case "call.route":                    
					$caller = $ev->getValue("caller");
					$called = $ev->getValue("called");
					makeRoutePhat($called,"Start");
					//return_route($called,$caller);
					$route_status = return_route($called, $caller);
					if (count($route_phat)>1)
				  		writeRoute($route_status);				    
					break;
				case "call.answered":
					$id = $ev->GetValue("targetid");                    
					if (isset($s_params_assistant_outgoing[$id])) {
						$params = $s_params_assistant_outgoing[$id];
						$m = new Yate("chan.operation");
						$m->params["id"] = $ev->GetValue("id");
						$m->params["operation"] = "setstate";
						$m->params["state"] = "";
						foreach ($params as $key => $value)
							$m->params[$key] = $value;
						$m->Dispatch();
					}
					break;
				case "chan.hangup":
					$id = $ev->GetValue("id");
					$reason = $ev->GetValue("reason");                    
					if (isset($params_assistant_outgoing[$id])) {
						debug("Dropping pbxassist params for $id's party");
						unset($s_params_assistant_outgoing[$id]);
					}
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
				if ($moh_next_time < $time)
					set_moh($time);                    
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

//Yate::Output("PHP: bye!");

/* vi: set ts=8 sw=4 sts=4 noet: */
?>
