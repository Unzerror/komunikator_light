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

function chektype($type) {
    global $channel_type;

    $ch_type = stristr($type,'/',true);
    if(in_array($ch_type,$channel_type) or in_array($type,$channel_type))
      return "telephony";
    else
      return $ch_type;
}

function search_disconnect($id,$peerid,$res,&$connected) {
    $sql = array();
    foreach ($res as $row) {
        if (($row["chan"]==$id and $row["peerid"]==$peerid) or ($row["chan"]==$peerid and $row["peerid"]==$id)){
            $connected ["connect"] = $row["connect"];
            $connected ["chan"] = $row["chan"];
        }else
           $sql[]= '("'.$row["connect"].'","'.$row["chan"].'")';
    }
    return implode(',', $sql); 
}

function search_discnct($id,$peerid,$res,&$connected) {
    $sql = array();
    foreach ($res as $row) {
        if ($row["chan"]==$id and $row["peerid"]==$peerid) {
            $connected ["connect"] = $row["connect"];
            $connected ["chan"] = $row["chan"];
        }else
           $sql[]= '("'.$row["connect"].'","'.$row["chan"].'")';
    }
    return implode(',', $sql); 
}

function chan_startup() {
    global $ev;

    $module_type=chektype($ev->GetValue("module"));    

    if ( $module_type == "telephony") {
        $start_time = microtime(true);
        $id = $ev->GetValue("id");
        if ( $ev->GetValue("direction") == 'incoming') {
            $callnumber = $ev->GetValue("caller");
            $called = $ev->GetValue("called");
        } else {
            $peerid = $ev->GetValue("peerid");
            $callnumber = $ev->GetValue("calledfull");
            $called = $callnumber;            
        }
        
        $query = "INSERT INTO call_logs (time, chan, address, direction, billid, caller, called, ended, gateway,callid)".
                 " VALUES (".
                 $start_time.", '".$ev->GetValue("id")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                 $ev->GetValue("billid")."', '".$ev->GetValue("caller")."', '".$called."', '0', ".
                 "(SELECT description FROM gateways WHERE status='online' and (username = '".$ev->GetValue("username")."' or ".
                 "username = '".$ev->GetValue("caller")."') LIMIT 1), '".$ev->GetValue("callid")."')";
        $res = query_nores($query);


        //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        //Доделать для случая когда connect раньше start (через execute)
        //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        /*$query1 = "UPDATE chan_switch SET billid = '".$ev->GetValue("billid")."', status = '".$ev->GetValue("status")."', reason = '".$ev->GetValue("reason")."' ".
                  "WHERE ((chan = '".$id."' and peerid = '".$peerid."') or (chan = '".$peerid."' and peerid = '".$id."')) and disconnect IS NULL";
        $res1 = query_nores($query1);*/

        $query2 = "INSERT INTO chan_start (start, chan, direction, billid, callid, callnumber)".
             " VALUES (".
             $start_time.", '".$ev->GetValue("id")."', '".$ev->GetValue("direction")."', '".
             $ev->GetValue("billid")."', '".$ev->GetValue("callid")."', '".$callnumber ."')";
        $res2 = query_nores($query2);
    }
}

function chan_connected() {
    global $ev;

    $connect_time = microtime(true);
    $id = $ev->GetValue("id");
    $type = chektype($id);
    $peerid = $ev->GetValue("peerid");    
    $peertype = chektype($peerid);
    $targetid = '';
    $connected = array();
    $answer = 'NULL';
    $billid = $ev->GetValue("billid");
    $callbillid = $billid;

    if (($type == "q-out") or ($peertype == "q-out") or ($peertype == "conf") or ($peerid == "ExtModule"))
         return false;
        
    if ($type == "conf" or $type == "tone") {
        $id = $peerid;
        $peerid = $ev->GetValue("id");
        $targetid = $ev->GetValue("address");
        $answer = $connect_time;
        if ($type == "conf")
            $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, o.called, o.called as called_gateway, ".
                                          "i.billid as billid, IF(i.billid<o.billid,i.billid,o.billid) as callbillid, i.direction as caller_type, NULL as called_type ".
                                  "FROM activ_channels i, ".
                                  "(SELECT called, billid FROM activ_conf_room WHERE targetid = '".$targetid."' ".
                                  "UNION SELECT callnumber as called, billid FROM activ_channels WHERE chan='".$id."' ".
                                                   "and NOT EXISTS (SELECT * FROM activ_conf_room WHERE targetid = '".$targetid."')) o ".
                                  "WHERE i.chan='".$id."'";
        else 
            $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, NULL as called, NULL as called_gateway, ".
                                  "i.billid as billid, i.billid as callbillid, i.direction as caller_type, NULL as called_type ".
                                  "FROM activ_channels i WHERE  i.chan = '".$id."' ";
    } elseif ($peertype == "fork") {
        if (substr_count($peerid,'/') == 2) {
            $targetid = substr(strrchr($peerid,'/'),1);
            $peerid = substr($peerid,0,strrpos($peerid,'/'));
            $sql_activ_channels = "SELECT p.caller as caller, i.gateway as caller_gateway, p.called as called, o.gateway as called_gateway, ".
                                  "i.billid as billid, IF(i.billid<o.billid,i.billid,o.billid) as callbillid, i.direction as caller_type, o.direction as called_type ".
                                  "FROM activ_channels p, activ_channels i, activ_channels o WHERE  p.chan = '".$id."' and i.callnumber = p.caller and o.callnumber = p.called";            
        } else {
            return false;             //не писать инициатора вызова fork/
        }
    } elseif ($peertype == "moh") {
        $targetid = $ev->GetValue("lastpeerid");
        $answer = $connect_time;
        $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, 'MoH' as called, o.callnumber as called_gateway, ".
                              "i.billid as billid, IF(i.billid<o.billid,i.billid,o.billid) as callbillid, i.direction as caller_type, NULL as called_type ".
                              "FROM activ_channels i, activ_channels o WHERE  i.chan = '".$id."'  and o.chan = '".$targetid."' ";
    } elseif ($peertype == "q-in")  {
        if ($ev->GetValue("status") == "answered") {
            $answer = $connect_time;
            $targetid = $ev->GetValue("targetid");
            if(chektype($targetid) == "q-in"){
                $peerid = $targetid;
                $targetid = $ev->GetValue("lastpeerid");
            }
        }

        if (chektype($targetid) == "telephony") {
            $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, o.called as called, p.callnumber as called_gateway, ".
                                  "i.billid as billid, IF(i.billid<o.billid or o.billid='',i.billid,o.billid) as callbillid, i.direction as caller_type, NULL as called_type ".
                                  "FROM activ_channels i, ".
                                  "(SELECT called, billid FROM call_group_history WHERE chan='".$id."' ORDER BY time DESC LIMIT 1) o, ".
                                  "(SELECT IF(direction='incoming',caller,called) as callnumber FROM call_logs WHERE chan='".$targetid."' and billid='".$billid."') p  ".
                                  "WHERE  i.chan = '".$id."'";
        } else {
            $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, o.called as called, NULL as called_gateway, ".
                              "i.billid as billid, IF(i.billid<o.billid or o.billid='',i.billid,o.billid) as callbillid, i.direction as caller_type, NULL as called_type ".
                              "FROM activ_channels i, ".
                              "(SELECT called, billid FROM call_group_history WHERE chan='".$id."' ORDER BY time DESC LIMIT 1) o ".                              
                              "WHERE  i.chan = '".$id."'";
        }
    }

    if ($type == $peertype) {
        $select_type = "(chan = '".$id."' or peerid = '".$peerid."' or chan = '".$peerid."' or peerid = '".$id."')";
        $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, o.callnumber as called, o.gateway as called_gateway, ".
                              "i.billid as billid, IF(i.billid<o.billid,i.billid,o.billid) as callbillid, i.direction as caller_type, o.direction as called_type  ".
                              "FROM activ_channels i, activ_channels o WHERE  i.chan = '".$id."' and o.chan = '".$peerid."' ";
    } else {
        $select_type = "(chan = '".$id."' or peerid = '".$id."')";
    }
      

    $query = "SELECT connect, chan, peerid, billid  FROM chan_switch WHERE ".$select_type." and  disconnect is NULL";
    $res = query_to_array($query);

    if (!empty($res)) {       
        $callbillid = min($res["billid"]);                                // проверить и переделать!!! на callbillid
        if ($type == $peertype) {
            $disc_sql = search_disconnect($id,$peerid,$res,$connected);
            $answer = $connect_time;
        } else
            $disc_sql = search_discnct($id,$peerid,$res,$connected);
        if ($disc_sql != '' ) {
            $query1 = "INSERT INTO chan_switch (connect, chan) VALUES ".$disc_sql." ON DUPLICATE KEY UPDATE disconnect = ".$connect_time;
            $res1 = query_nores($query1);
        }
    }
    
    //где-то добавить проверку на answerd!!!!
    if (empty($connected)) {
        /*$query2 = "INSERT INTO chan_switch (connect, answer, chan, peerid, targetid, billid, status, reason)".
                  " VALUES (".
                  $connect_time.", ".$answer.", '".$id."', '".$peerid."', '".$targetid."', '".
                  $ev->GetValue("billid")."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";*/
        $query2 = "INSERT INTO chan_switch (connect, answer, chan, peerid, targetid, billid, callbillid, caller, caller_gateway, ".
                  "called, called_gateway, caller_type, called_type, status, reason) ".
                  "SELECT ".
                  $connect_time.", ".$answer.", '".$id."', '".$peerid."', '".$targetid."', IF('".$billid."'='', t.billid, '".$billid."'), ".
                  "IF(('".$callbillid."'='' or t.callbillid<'".$callbillid."'), t.callbillid,'".$callbillid."'), t.caller, t.caller_gateway, t.called, t.called_gateway, ".
                  "t.caller_type, t.called_type,'".$ev->GetValue("status")."', '".$ev->GetValue("reason")."' ".
                  "FROM ( $sql_activ_channels ) t";
        $res2 = query_nores($query2);
    }

    /*$query1 = "UPDATE chan_switch SET disconnect = ".$connect_time." WHERE chan = '".$id."' and disconnect IS NULL"; // and connect != ".$connect_time;
    $res1 = query_nores($query1);

    $query = "INSERT INTO chan_switch (connect, chan, peerid, targetid, lastpeerid, address, direction, billid, callid,  status, reason)".
                             " VALUES (".
                             $connect_time.", '".$id."', '".$ev->GetValue("peerid")."', '".$targetid."', '".
                             $ev->GetValue("lastpeerid")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                             $ev->GetValue("billid")."', '".$ev->GetValue("callid")."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";
    $res = query_nores($query);*/
    /*$query1 = "BEGIN; ".
              "INSERT INTO chan_switch (connect, chan, peerid) ".
              "VALUES (".$connect_time.", 'fip/21', 'tree'); ".
              "INSERT INTO chan_switch (connect, chan, peerid) ".
              "VALUES (".$connect_time.", 'fip/22', 'tree'); ".
              "COMMIT;";
    $res1 = query_nores($query1);*/

}  

function chan_disconnected() {
    global $ev;
    
    $id = $ev->GetValue("id");
    $type = chektype($id);
    $peerid = $ev->GetValue("lastpeerid");

    if ($type == "q-out")
         return false;

    $query = "UPDATE chan_switch SET disconnect = ".microtime(true).", status = '".$ev->GetValue("status")."', reason = '".$ev->GetValue("reason")."' ".
             "WHERE ((chan = '".$id."' and peerid = '".$peerid."') or (chan = '".$peerid."' and peerid = '".$id."')) and disconnect IS NULL";
    $res = query_nores($query);
}

function chan_hangup() {
    global $ev;

    $id = $ev->GetValue("id");
    $type = chektype($id);

    if (($type == "fork") or ($type == "q-out")) {
         return false;
    } elseif ( $type == "auto_attendant") {
        $query1 = "UPDATE chan_switch SET disconnect = ".microtime(true).",  status = '".$ev->GetValue("status")."', reason = '".$ev->GetValue("reason")."' ".
                  "WHERE targetid = '".$id."' and disconnect IS NULL";
    } else {
        $query1 = "UPDATE chan_switch SET disconnect = ".microtime(true).",  status = '".$ev->GetValue("status")."', reason = '".$ev->GetValue("reason")."' ".
                   "WHERE (chan = '".$id."' or peerid = '".$id."') and disconnect IS NULL";
    }
    $res1 = query_nores($query1);

    if ( $ev->GetValue("module") == 'sip') {
           $query = "UPDATE chan_start SET hangup = ".microtime(true)." WHERE chan = '".$ev->GetValue("id")."' and hangup is NULL";
           $res = query_nores($query);
    }
}

function call_answered() {
    global $ev;

    $connect_time = microtime(true);
    $id = $ev->GetValue("peerid");
    if (!isset($id)) {
        $id = $ev->GetValue("targetid");
    }    
    $type = chektype($id);    
    $peerid = $ev->GetValue("id");    
    $peertype = chektype($peerid);
    $targetid = '';
    $connected = array();
    $billid = $ev->GetValue("billid");
    $callbillid = $billid;

    if (($type == "q-out") or ($peertype == "fork"))
         return false;
    
    if (($peertype ==  "auto_attendant") or ($peertype ==  "leavemaildb")) {
        //$id = $ev->GetValue("targetid");        
        $targetid = $peerid;
        $peerid = "ExtModule";
        $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, '".$peertype."' as called, NULL as called_gateway, ".
                              "i.billid as billid, i.billid as callbillid, i.direction as caller_type, NULL as called_type  ".
                              "FROM activ_channels i WHERE  i.chan = '".$id."' ";
    } else {
        $sql_activ_channels = "SELECT i.callnumber as caller, i.gateway as caller_gateway, o.callnumber as called, o.gateway as called_gateway, ".
                              "i.billid as billid, IF(i.billid<o.billid,i.billid,o.billid) as callbillid, i.direction as caller_type, o.direction as called_type  ".
                              "FROM activ_channels i, activ_channels o WHERE  i.chan = '".$id."' and o.chan = '".$peerid."' ";

    }
     
    
       
    $query = "SELECT connect, chan, peerid FROM chan_switch WHERE (chan = '".$id."' or peerid = '".$peerid."' or chan = '".$peerid."' or peerid = '".$id."') and  disconnect is NULL";
    $res = query_to_array($query);
    
    if (!empty($res)) {       
        if ($type == $peertype) 
             $disc_sql = search_disconnect($id,$peerid,$res,$connected);
        else
            $disc_sql = search_discnct($id,$peerid,$res,$connected);
        if ($disc_sql != '' ) {
              $query1 = "INSERT INTO chan_switch (connect, chan) VALUES ".$disc_sql." ON DUPLICATE KEY UPDATE disconnect = ".$connect_time;
              $res1 = query_nores($query1);
        }
    }

    if (empty($connected)) {
        /*$query2 = "INSERT INTO chan_switch (connect, answer, chan, peerid, targetid, billid, status, reason)".
                  " VALUES (".
                  $connect_time.", ".$connect_time.", '".$id."', '".$peerid."', '".$targetid."', '".
                  $ev->GetValue("billid")."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";*/
        $query2 = "INSERT INTO chan_switch (connect, answer, chan, peerid, targetid, billid, callbillid, caller, caller_gateway, ".
                  "called, called_gateway, caller_type, called_type, status, reason) ".
                  "SELECT ".
                  $connect_time.", ".$connect_time.", '".$id."', '".$peerid."', '".$targetid."', IF('".$billid."'='', t.billid, '".$billid."'), ".
                  "IF('".$callbillid."'='' or t.callbillid<'".$callbillid."', t.callbillid,'".$callbillid."'), t.caller, t.caller_gateway, t.called, t.called_gateway, ".
                  "t.caller_type, t.called_type,'".$ev->GetValue("status")."', '".$ev->GetValue("reason")."' ".
                  "FROM ( $sql_activ_channels ) t";
    } else {
        $query2 = "UPDATE chan_switch SET answer = ".$connect_time.",  status = '".$ev->GetValue("status")."', reason = '".$ev->GetValue("reason")."', ".
                  "chan = '".$id."', peerid = '".$peerid."'  ".
                  "WHERE chan = '".$connected["chan"]."' and connect = ".$connected["connect"];
    }
    $res2 = query_nores($query2);
    /*$query = "SELECT connect,chan FROM chan_switch WHERE ((chan = '".$id."' and peerid = '".$peerid."') or (chan = '".$peerid."' and peerid = '".$id."')) and  disconnect is NULL";
    $res = query_to_array($query);

    if (empty($res)) {
        $query1 = "INSERT INTO chan_switch (connect, answer, chan, peerid, targetid, status, reason)".
                  " VALUES (".
                  microtime(true)."', ".microtime(true).", '".$id.", '".$peerid."', '".$targetid."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";    
    } else {
        $query1 = "UPDATE chan_switch SET answer = ".microtime(true).",  status = '".$ev->GetValue("status")."', reason = '".$ev->GetValue("reason")."', ".
                  "chan = '".$id."', peerid = '".$peerid."'  ".
                  "WHERE chan = '".$res[0]["chan"]."' and connect = ".$res[0]["connect"];
    }
                
    $res1 = query_nores($query1);*/
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

Yate::Install("chan.hangup",80);
Yate::Install("chan.connected", 80);
Yate::Install("chan.disconnected", 80);
Yate::Install("call.answered", 80);
Yate::Install("chan.startup", 80);

Yate::Install("user.notify");
Yate::Install("engine.status");
Yate::Install("engine.command");
Yate::Install("engine.debug");

// Ask to be restarted if dying unexpectedly 
Yate::SetLocal("restart", "true");

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
                    case "chan.hangup":
                    if ($ev->GetValue("lastpeerid") == "ExtModule") {
                        $billid = $ev->GetValue("billid");
                        $query = "SELECT  chan FROM call_logs WHERE billid='$billid' AND direction='outgoing' AND ended=0";
                        $res = query_to_array($query);
                        if(count($res)) {
                            $m = new Yate("chan.hangup");
                            $m->params["id"] = $res[0]["chan"];
                            $m->params["billid"] = $billid;
                            $m->params["targetid"] = $ev->GetValue("id");
                            //$m->params["reason"] = 'hangup';
                            $m->params["answered"] = 'true';                            
                            $m->Dispatch();
                        }
                        /*$targetid = $ev->GetValue("targetid");
                        $billid = $ev->GetValue("billid");
                        if ( substr($targetid,0,4) == "fork" ) {
                            $query = "SELECT  chan FROM call_logs WHERE billid='$billid' AND direction='outgoing' AND ended=0";
                            $res = query_to_array($query);
                            $targetid = $res[0]["chan"];
                        }
                        $m = new Yate("chan.hangup");
                        $m->params["id"] = $targetid;
                        $m->params["billid"] = $billid;
                        $m->params["targetid"] = $ev->GetValue("id");
                        $m->params["reason"] = 'hangup';
                        $m->params["answered"] = 'true';                        
                        $m->Dispatch();*/
                        //вариант 2
                        //$query = "UPDATE call_logs a1 SET a1.duration=($time-a1.time), a1.billtime=a1.duration, ended=1 WHERE billid='" . $ev->GetValue("billid").  "' AND chan='" .$ev->GetValue("targetid"). "' AND ended=0";
                        //$res = query_to_array($query);                        
                    }
                    $query1 = "INSERT INTO chan_switch1 (time, type, chan, address, direction, billid, callid, peerid, targetid, lastpeerid, status, reason)".
                             " VALUES (".
                             microtime(true).", 'hangup','".$ev->GetValue("id")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                             $ev->GetValue("billid")."', '".$ev->GetValue("callid")."', '".$ev->GetValue("peerid")."', '".$ev->GetValue("targetid")."', '".
                             $ev->GetValue("lastpeerid")."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";
                    $res1 = query_nores($query1);

                    chan_hangup();

                    break;
                case "call.answered":
                    $query1 = "INSERT INTO chan_switch1 (time, type, chan, address, direction, billid, callid, peerid, targetid, lastpeerid, status, reason)".
                             " VALUES (".
                             microtime(true).", 'answered','".$ev->GetValue("id")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                             $ev->GetValue("billid")."', '".$ev->GetValue("callid")."', '".$ev->GetValue("peerid")."', '".$ev->GetValue("targetid")."', '".
                             $ev->GetValue("lastpeerid")."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";
                    $res1 = query_nores($query1);
                    
                    call_answered();

                    break;
                case "chan.startup":
                    chan_startup();
                    $query1 = "INSERT INTO chan_switch1 (time, type, chan, address, direction, billid, callid, peerid, targetid, caller, called, calledfull, lastpeerid, status, reason)".
                             " VALUES (".
                             microtime(true).", 'startup','".$ev->GetValue("id")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                             $ev->GetValue("billid")."', '".$ev->GetValue("callid")."', '".$ev->GetValue("peerid")."', '".$ev->GetValue("targetid")."', '".
                             $ev->GetValue("caller")."', '".$ev->GetValue("called")."', '".$ev->GetValue("calledfull")."', '".$ev->GetValue("lastpeerid")."', '".
                             $ev->GetValue("status")."', '".$ev->GetValue("reason")."')";
                    $res1 = query_nores($query1);
                    
                    
                    
                    break;
                case "chan.connected":
                    $query1 = "INSERT INTO chan_switch1 (time, type, chan, address, direction, billid, callid, peerid, targetid, lastpeerid, status, reason)".
                             " VALUES (".
                             microtime(true).", 'connected','".$ev->GetValue("id")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                             $ev->GetValue("billid")."', '".$ev->GetValue("callid")."', '".$ev->GetValue("peerid")."', '".$ev->GetValue("targetid")."', '".
                             $ev->GetValue("lastpeerid")."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";
                    $res1 = query_nores($query1);

                    chan_connected();

                    break;
                case "chan.disconnected":
                    $query1 = "INSERT INTO chan_switch1 (time, type, chan, address, direction, billid, callid, peerid, targetid, lastpeerid, status, reason)".
                             " VALUES (".
                             microtime(true).", 'disconnected','".$ev->GetValue("id")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                             $ev->GetValue("billid")."', '".$ev->GetValue("callid")."', '".$ev->GetValue("peerid")."', '".$ev->GetValue("targetid")."', '".
                             $ev->GetValue("lastpeerid")."', '".$ev->GetValue("status")."', '".$ev->GetValue("reason")."')";
                    $res1 = query_nores($query1);
                    /*if ($ev->GetValue("lastpeerid") == "ExtModule") {
                        $targetid = $ev->GetValue("targetid");
                        $query = "SELECT  chan FROM call_logs WHERE billid='$billid' AND direction='outgoing' AND ended=0";
                        $res = query_to_array($query);
                        if(count($res)) {
                            $m = new Yate("chan.hangup");
                            $m->params["id"] = $res[0]["chan"];
                            $m->params["billid"] = $billid;
                            $m->params["targetid"] = $ev->GetValue("id");
                            $m->params["reason"] = 'hangup';
                            $m->params["answered"] = 'true';                        
                            $m->Dispatch();
                        }
                    }*/

                    chan_disconnected();

                    break;
                case "call.cdr":
                 $operation = $ev->GetValue("operation");
				 $reason = $ev->GetValue("reason");
                 if(empty($ev->GetValue("calledfull")))                      
                     $called = $ev->GetValue("called");
                 else
                     $called = $ev->GetValue("calledfull");
                  switch($operation) {
					case "initialize":
                       /* $query = "INSERT INTO call_logs (time, chan, address, direction, billid, caller, called, duration, billtime, ringtime, status, reason, ended, gateway,callid)".
                                    " VALUES (".
                                    $ev->GetValue("time").", '".$ev->GetValue("chan")."', '".$ev->GetValue("address")."', '".$ev->GetValue("direction")."', '".
                                    $ev->GetValue("billid")."', '".$ev->GetValue("caller")."', '".$called."', ".$ev->GetValue("duration").", ".
                                    $ev->GetValue("billtime").", ".$ev->GetValue("ringtime").", '".$ev->GetValue("status")."', '$reason', '0', ".
                                    "(SELECT description FROM gateways WHERE status='online' and (username = '".$ev->GetValue("username")."' or ".
                                    "username = '".$ev->GetValue("caller")."') LIMIT 1), '".$ev->GetValue("callid")."')";*/
                        $query = "UPDATE call_logs SET address='".$ev->GetValue("address")."', direction='".$ev->GetValue("direction")."', time=" . $ev->GetValue("time").
                                    ", caller='".$ev->GetValue("caller")."', called='".$called."', duration=" . $ev->GetValue("duration"). 
                                    ", billtime=".$ev->GetValue("billtime").", ringtime=".$ev->GetValue("ringtime").", status='".$ev->GetValue("status").
                                    "', reason='$reason', callid='".$ev->GetValue("callid")."' WHERE chan='" . $ev->GetValue("chan") . "' AND billid='".$ev->GetValue("billid")."' ";
                        $res = query_nores($query);
						break;
					case "update":
						$query = "UPDATE call_logs SET address='".$ev->GetValue("address")."', direction='".$ev->GetValue("direction")."', billid='".$ev->GetValue("billid").
                                        "', caller='".$ev->GetValue("caller")."', called='".$called."', duration=" . $ev->GetValue("duration"). 
                                        ", billtime=".$ev->GetValue("billtime").", ringtime=".$ev->GetValue("ringtime").", status='".$ev->GetValue("status").
                                        "', reason='$reason', callid='".$ev->GetValue("callid")."' WHERE chan='" . $ev->GetValue("chan") . "' AND time=" . $ev->GetValue("time");
						$res = query_nores($query);
                        break;
					case "finalize":
						$query = "UPDATE call_logs SET address='" . $ev->GetValue("address") ."', direction='".$ev->GetValue("direction"). "', billid='" . $ev->GetValue("billid") .
                                    "', caller='" . $ev->GetValue("caller") . "', called='" . $called . "', duration= IF(" . $ev->GetValue("duration") . ">1E6,0," . 
                                    $ev->GetValue("duration") . "), billtime=" . $ev->GetValue("billtime") . ", ringtime=" . $ev->GetValue("ringtime") . ", status='" . $ev->GetValue("status") . 
                                    "', reason=IF('$reason'='',reason,'$reason'), ended=1 WHERE chan='" .  $ev->GetValue("chan") . "' AND time=" . $ev->GetValue("time");
                        $res = query_nores($query);
						break;
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
                    if ($time < $next_time)
                        break;
                    $next_time = $time + $time_step;
                    //проверка шлюза на изменеие параметров
                    $query = "SELECT enabled, protocol, username, description, 'interval', formats, authname, password, server, domain, outbound , localaddress, modified, gateway as account, gateway_id, status, 1 AS gw FROM gateways WHERE enabled = 1 AND modified = 1 AND username is NOT NULL";
                    $res = query_to_array($query);
                    for ($i = 0; $i < count($res); $i++) {
                        $m = new Yate("user.login");
                        $m->params = $res[$i];
                        $m->Dispatch();
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