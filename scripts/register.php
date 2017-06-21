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

require_once (__DIR__.'/vendor/autoload.php');
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$log = new Logger('register');
$log->pushHandler(new StreamHandler('/var/tmp/register.log', Logger::DEBUG));
$log->addInfo('==register.php logger start==');

require_once("libyate.php");
require_once("lib_queries.php");

$type_debug = 'register';

$gateway_ev = array();
$callFrom = '';                      //проблемы - используется call.route - переписать код с ней!!!

$s_statusaccounts = array();

$next_time = 0;            //время апдейта статусов
$time_step = 90;           //шаг апдейта статусов


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
                    $query = "INSERT INTO ext_connection (extension,location,expires) VALUES ('" . $ev->GetValue("username") . "','$location','" . (time() + $ev->GetValue("expires")) . "') ON DUPLICATE KEY UPDATE expires='" . (time() + $ev->GetValue("expires")) . "'";
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
                case "call.cdr":                    
                    /*
                    $operation = $ev->GetValue("operation");
                    $reason = $ev->GetValue("reason");

                    $ended_initialize = 0;
                    $ended_finalize = 1;

                    switch ($operation) {
                        case "initialize":


                            $gateway_name = '';
                            $gateway_sql = "SELECT username FROM gateways";
                            $gateway_ev = query_to_array($gateway_sql);
                            //для тестовых таблиц------------------------------------------------------------------------------------------
                            //пропускаем значения звонящего и принимающего через цикл сравнения и тех и других с шлюзами
                            $billid_ev = $ev->GetValue("billid");
                            $i = 0;
                            while ($i <= count($gateway_ev)) {
                                if ($ev->GetValue("caller") == $gateway_ev[$i]['username']) {
                                    $gateway_name = $ev->GetValue("caller");
                                } else if ($ev->GetValue("called") == $gateway_ev[$i]['username']) {
                                    $gateway_name = $ev->GetValue("called");
                                }
                                $i = $i + 1;
                            }

                            $chan_ev = $ev->GetValue("chan");
                            $ended_ev = $ended_initialize;
                            $direction_ev = $ev->GetValue("direction");

                            // обрабатываем событие - звонок на голосовую почту
                            if ($ev->GetValue("status") == 'cs_voicemail' OR $ev->GetValue("status") == 'cs_attendant') {
                                $direction_ev = 'outgoing';
                                $ended_ev = $ended_finalize;
                            }

                            $query = "INSERT INTO call_logs (time, chan, address, direction, billid, caller, called, duration, billtime, ringtime, status, reason, ended, gateway)"
                                    . " VALUES ("
                                    . $ev->GetValue("time") . ", '"
                                    . $ev->GetValue("chan") . "', '"
                                    . $ev->GetValue("address") . "', '"
                                    . $direction_ev . "', '"
                                    . $ev->GetValue("billid") . "', '"
                                    . $ev->GetValue("caller") . "', '"
                                    . $ev->GetValue("called") . "', "
                                    . $ev->GetValue("duration") . ", "
                                    . $ev->GetValue("billtime") . ", "
                                    . $ev->GetValue("ringtime") . ", '"
                                    . $ev->GetValue("status") . "', '$reason', '$ended_ev', '$gateway_name')";
                            $res = query_nores($query);
                            $query1 = "UPDATE extensions SET inuse_count=(CASE WHEN inuse_count IS NOT NULL THEN inuse_count+1 ELSE 1 END) WHERE extension='" . $ev->GetValue("external") . "'";
                            $res1 = query_nores($query1);


                            break;

                        case "update":

                            $chan_ev = $ev->GetValue("chan");
                            $caller_ev = $ev->GetValue("caller");
                            $called_ev = $ev->GetValue("called");
                            $direction_ev = $ev->GetValue("direction");
                            $ended_ev = 0;
                            
                            if (substr($chan_ev, 0, 11) == 'ctc-dialer/') {
                                if ($callFrom && ($callFrom !== '' || $callFrom !==null)) {
                                    $callFrom = urldecode($callFrom);
                                    $chan_ev = substr_replace($ev->GetValue("chan"), 'order_call', 0, 10);
                                    $query1 = "INSERT INTO detailed_infocall(billid, caller, called, detailed)"
                                            . " SELECT'"
                                            . $ev->GetValue("billid") . "', '"
                                            . $ev->GetValue("called") . "', '"
                                            . $ev->GetValue("caller") . "', '"
                                            . $callFrom . "' FROM dual
                                              WHERE NOT EXISTS (     
                                              SELECT * FROM detailed_infocall
                                              WHERE billid = '" . $ev->GetValue("billid") . "' 
                                                  AND caller = '" . $ev->GetValue("called") . "' 
                                                  AND called = '" . $ev->GetValue("caller") . "' 
                                                  AND detailed = '" . $callFrom . "');";
                                    $res1 = query_nores($query1);
                                }
                                $direction_ev = "incoming";
                                $query = "UPDATE call_logs SET chan = '" . $chan_ev . "', address='" . $ev->GetValue("address") . "', direction='" . $direction_ev . "', billid='" . $ev->GetValue("billid") .
                                        "', caller='" . $called_ev . "', called='" . $caller_ev . "', duration=" . $ev->GetValue("duration") . ", billtime=" .
                                        $ev->GetValue("billtime") . ", ringtime=" . $ev->GetValue("ringtime") . ", status='" . $ev->GetValue("status") .
                                        "', reason='$reason' WHERE (chan='" . $ev->GetValue("chan") . "' OR chan = '" . substr_replace($ev->GetValue("chan"), 'order_call', 0, 10) . "') AND time=" . $ev->GetValue("time");
                            } else {
                                $query = "UPDATE call_logs SET address='" . $ev->GetValue("address") . "', direction='" . $direction_ev . "', billid='" . $ev->GetValue("billid") .
                                        "', caller='" . $caller_ev . "', called='" . $called_ev . "', duration=" . $ev->GetValue("duration") . ", billtime=" .
                                        $ev->GetValue("billtime") . ", ringtime=" . $ev->GetValue("ringtime") . ", status='" . $ev->GetValue("status") .
                                        "', reason='$reason' WHERE chan='" . $ev->GetValue("chan") . "' AND time=" . $ev->GetValue("time");
                            }
                            $res = query_nores($query);
                            $query1 = "UPDATE call_logs t1 ".
                                      "JOIN call_logs t2 ON t2.billid = t1.billid ".
                                      "SET t1.direction = 'unknown' ".
                                      "WHERE t1.called = '" . $called_ev . "' and t1.billid = '" . $ev->GetValue("billid") . "' and (SUBSTRING(t1.chan,1, 11)!= 'ctc-dialer/' OR SUBSTRING(t1.chan,1, 11)!= 'order_call/') ".
                                      "AND ".
                                      "t2.caller = '" . $called_ev . "' and t2.billid = '" . $ev->GetValue("billid") . "' and (SUBSTRING(t2.chan,1, 11) = 'ctc-dialer/' OR SUBSTRING(t2.chan,1, 11) = 'order_call/')";
                            $res1 = query_nores($query1);
                            break;

                        case "finalize":
                            $billid_ev = $ev->GetValue("billid");
                            $callFrom = null;
                            $query = "UPDATE call_logs SET address='" . $ev->GetValue("address") . "', billid='" . $ev->GetValue("billid") .
                                    "', caller='" . $ev->GetValue("caller") . "', called='" . $ev->GetValue("called") . "', duration=" . $ev->GetValue("duration") . ", billtime=" .
                                    $ev->GetValue("billtime") . ", ringtime=" . $ev->GetValue("ringtime") . ", status='" . $ev->GetValue("status") . "', reason='$reason', ended=1 WHERE chan='" .
                                    $ev->GetValue("chan") . "' AND time=" . $ev->GetValue("time");

                            $res = query_nores($query);

                            $query = "INSERT INTO call_history (time, chan, address, direction, billid, caller, called, duration, billtime, ringtime, status, ended, gateway) " .
                                     "SELECT b.time, b.chan, b.address, ".
                                     " CASE ".
                                     "     WHEN SUBSTRING(a.chan,1, 11) = 'order_call/' OR SUBSTRING(b.chan,1, 11) = 'order_call/' ".
                                     "           THEN 'order_call' ".
                                     "        WHEN x1.extension IS NOT NULL AND x2.extension IS NOT NULL ".
                                     "          THEN 'internal' ".
                                     "        WHEN x1.extension IS NOT NULL ".
                                     "            THEN 'outgoing' ".
                                     "        ELSE 'incoming' ".
                                     "    END direction, ".
                                     "    b.billid, ".
                                     "    CASE ".
                                     "        WHEN x1.firstname IS NULL ".
                                     "            THEN a.caller ".
                                     "        ELSE  a.caller ".
                                     "    END caller, ".
                                     "    CASE ".
                                     "        WHEN x2.firstname IS NULL ".
                                     "            THEN b.called ".
                                     "        ELSE b.called ".
                                     "    END called, ".
                                     "    b.duration, ".
                                     "    b.billtime, ".
                                     "    b.ringtime, ".
                                     "    CASE ".
                                     "        WHEN b.reason = '' ".
                                     "            THEN b.status ".
                                     "        ELSE REPLACE( LOWER(b.reason), ' ', '_' ) ".
                                     "    END status, ".
                                     "    CASE ".
                                     "        WHEN SUBSTRING(b.chan,1, 11)!= 'ctc-dialer/' ".
                                     "            THEN b.ended = '1' " .
                                     "        WHEN SUBSTRING(b.chan,1, 11)!= 'order_call/' ".
                                     "            THEN b.ended = '1' ".
                                     "        ELSE b.ended " .
                                     "    END ended, ".
                                     "    CASE ".
                                     "        WHEN b.gateway = '' ".
                                     "            THEN a.gateway ".
                                     "        ELSE b.gateway ".
                                     "    END gateway ".
                                     "FROM call_logs a ".
                                     "JOIN call_logs b ON b.billid = a.billid AND b.ended = 1 AND b.direction = 'outgoing' ".
                                     "LEFT JOIN extensions x1 ON x1.extension = a.caller ".
                                     "LEFT JOIN extensions x2 ON x2.extension = a.called ".
                                     "WHERE  a.direction = 'incoming' AND b.billid = '$billid_ev' ";
                            $res = query_nores($query);

                            $query1 = "UPDATE detailed_infocall AS a ".
                                      "INNER JOIN call_history  AS b ON a.billid = b.billid AND a.caller = b.caller AND a.called = b.called ".
                                      "SET a.time = b.time ".
                                      "WHERE b.billid = '$billid_ev'";
                            $res1 = query_nores($query1);
                            //очищаем массив и удаляем ненужные записи- - - - - - - - -
                            //$sql = "DELETE FROM call_logs WHERE billid = '" . $billid_ev . "'";
                            //$res = query_nores($sql);


                            $query = "UPDATE extensions SET inuse_count=(CASE WHEN inuse_count>0 THEN inuse_count-1 ELSE 0 END), inuse_last=" . time() . " WHERE extension='" . $ev->GetValue("external") . "'";
                            $res = query_nores($query);
                            break;
                    }
                    break;*/
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