#!/usr/bin/php -q
<?php

require_once("lib_queries.php");
require_once("libyate.php");

set_time_limit($time_out);

$ourcallid = "bx24/" . uniqid(rand(), 1);
$type_debug = 'bx24';
//$wait_time = 1; //number of seconds that script has to wait after user input in order to see if another digit will be pressed
//$hold_keys = '#';          // Добавить в интерфейс отработку # для автоматического приема звонка в Автосееретаря
$hold_keys = '0'; //set default value for a hold key
$count = false; //start to count seconds since a digit has been pressed
$state = "enter";
$max_len_inside_number = 5;

function format_array($arr) {
    $str = str_replace("\n", "", print_r($arr, true));
    $str = str_replace("\t", "", $str);
    while (strlen($str) != strlen(str_replace("  ", " ", $str)))
        $str = str_replace("  ", " ", $str);
    return $str;
}


function getPBXstatus() {
    $status = 'offline';
    $day_week = date('w') ;
    $hour = date('H') * 1;
    
    $query = "select start_hour, end_hour FROM time_frames";
    $res = query_to_array($query);
    if (count($res)) {
        $res1[0] = $res[$day_week];
        $next_day = ($day_week + 1) % 7;
        $perv_day = ($day_week + 6) % 7;
        if ($res[$next_day]["start_hour"] < 0) {
            $res1[1]["start_hour"] = $res[$next_day]["start_hour"] + 24;
            $res1[1]["end_hour"] = $res[$next_day]["end_hour"] + 24;
        } elseif ($res[$perv_day]["end_hour"] > 24) {
            $res1[1]["start_hour"] = $res[$perv_day]["start_hour"] - 24;                             
            $res1[1]["end_hour"] = $res[$perv_day]["end_hour"] - 24;
        }
        foreach ($res1 as $row) {
            if ($row["start_hour"] <= $hour && $hour < $row["end_hour"])
                $status = 'online';
        }
    }
    return $status;
}

function setState($newstate) {
    global $ourcallid;
    global $partycallid;
    global $state;
    global $uploaded_prompts;
    global $keys;
    global $wait_time;
    global $caller;
    global $hold_keys;
    global $destination;
    global $called;
    global $ev;
    global $billid;

    // are we exiting?
    if ($state == "")
        return;

    debug("setState('$newstate') state: $state");

    $state = $newstate;
    // always obey a return to prompt
    switch ($newstate) {
        case "greeting":
            $hold_keys = '';
            $m = new Yate("chan.attach");
            $m->params["source"] = "wave/play//var/lib/misc/auto_attendant/online.wav";
            //$m->params["source"] = "wave/play/$uploaded_prompts/auto_attendant/$prompt";
            $m->params["notify"] = $ourcallid;
            $m->Dispatch();
            break;
        // case "prolong_greeting":
        //     $m = new Yate("chan.attach");
        //     $m->params["consumer"] = "wave/record/-";
        //     $m->params["notify"] = $ourcallid;
        //     //вытаскивать инфу из звонка
        //     $m->params["maxlen"] = $wait_time * 16000;
        //     $m->Dispatch();
        //     break;
        case "goodbye":
            $m = new Yate("chan.attach");
            $m->params["source"] = "tone/congestion";
            $m->params["consumer"] = "wave/record/-";
            $m->params["maxlen"] = 32000;
            $m->params["notify"] = $ourcallid;
            $m->Dispatch();
            break;
        case "call.route":
            // $query = "SELECT location FROM kommunikator.dids JOIN ext_connection ON ext_connection.extension_id=dids.extension_id WHERE dids.number='$caller' and dids.destination='external/nodata/bx24.php'";
            // $res = query_to_array($query);
            // if(!count($res)) {
            //     debug("Could not find BX24 worked gateway");         ///ТЕКСТ - Офисная АТС не подключена к шлюзу - проверьте соединение
            //     setState("goodbye");                
            //     setState("hangup");
            // } else {
                $m = new Yate("call.route");
                $m->params["caller"] = $caller;
                $m->params["called"] = "bx24-".$called;
                $m->params["id"] = $ourcallid;
                $m->params["billid"] = $billid;
                $m->params["already-auth"] = "yes";
                $m->params["call_type"] = "from outside";
                $m->Dispatch();
            // }
            break;

            // debug('3000_call');
            //     $ev->retval = "sip/sip:79061395330@172.17.2.6:5060";
            //     $ev->params["caller"] = "000";
            //     $ev->params["called"] = "79061395330";
            //     $ev->params["callername"] = "999";
            //     $ev->params["username"] = "000";
            //     //$ev->params["domain"] = "172.17.3.119";
            //     $ev->params["authname"] = "000";
            //     //$ev->params["already-auth"] = "yes";
            //     //$ev->params["trusted-auth"] = "yes";
            //     //$ev->params["line"] = "000";
            //     $ev->handled = true;
            //     return true;    

        case "send_call":
            $m = new Yate("chan.masquerade");
            $m->params = $ev->params;
            $m->params["message"] = "call.execute";
            $m->params["id"] = $partycallid;
            $m->params["callto"] = $destination;
            $m->params["fork.fake"] = "tone/ring";
            //$m->params["direction"] = "outgoing";
            $m->params["complete_minimal"] = true;
            $m->Dispatch();

            break;
        case "fake_ring":
            //$log->debug('fake call');
            $m = new Yate("chan.attach");
            $m->params["source"] = "moh/madplay";
            $m->params["mohlist"] = '/var/lib/misc/moh/kpv.mp3';
            $m->Dispatch();
            break;
        case "hangup":            
            //$log->debug('chan_hangup');
            $m = new Yate("chan.hangup");
            //$m = new Yate("chan.disconected");
            $m->params["id"] = $ourcallid;
            $m->params["billid"] = $billid;
            $m->params["answered"] = 'true';
            //$m->params["reason"] = 'true';
            $m->Dispatch();
            //Yate::Uninstall("chan.dtmf");
            //Yate::Uninstall("chan.notify");
    }
}

/* Handle all DTMFs here */

function gotDTMF($text) {
    global $state;
    global $keys;
    global $destination;
    global $hold_keys;
    global $count;

    debug("gotDTMF('$text') state: $state");

    // Reset default key
    if (!$count)
        $hold_keys = '';
    $count = true;
    switch ($state) {
        // case "greeting":
        // case "prolong_greeting":
        //     if ($text != "#" && $text != "*")
        //         $hold_keys .= $text;
        //     else {
        //         //i will consider that this are accelerating keys
        //         setState("call.route");
        //         break;
        //     }
        //     return;
    }
}

function gotNotify($reason) {
    global $state;
    global $ourcallid;

    debug("gotNotify('$reason') state: $state");
    if ($reason == "replaced")
        return;

    switch ($state) {
        case "greeting":
            setState("call.route");
            break;
        // case "prolong_greeting":
        //     setState("call.route");
        //     break;
        case "goodbye":
            //$query = "UPDATE call_logs SET reason='time_out' WHERE chan='" .$ourcallid. "'";   //Нужно ли для register.php без MySQL?
            //$res = query_nores($query);            
            setState("");
            break;
    }
}

Yate::Init();

chek_debug();

/* Install filtered handlers for the wave end and dtmf notify messages */
// chan.dtmf should have a greater priority than that of pbxassist(by default 15)
//Yate::Install("engine.timer", 100);
Yate::Install("chan.notify", 100, "targetid", $ourcallid);
Yate::Install("chan.connected", 100);

/* The main loop. We pick events and handle them */
while ($state != "") {
    $ev = Yate::GetEvent();
    /* If Yate disconnected us then exit cleanly */
    if ($ev === false)
        break;
    /* No need to handle empty events in this application */
    if ($ev === true)
        continue;
    /* If we reached here we should have a valid object */

    debug(format_array($ev));

    switch ($ev->type) {
        case "incoming":
            Yate::Debug(">>>>>>bx24 incoming: " . $ev->name . " id: " . $ev->id);
            switch ($ev->name) {
                case "call.execute":
                    $partycallid = $ev->GetValue("id");
                    $caller = $ev->GetValue("caller");
                    $called = $ev->GetValue("called");
                    $billid = $ev->GetValue("billid");

                    if ($ev->GetValue("debug_on") == "yes") {
                        Yate::Output(true);
                        Yate::Debug(true);
                    }

                    if ($ev->GetValue("query_on") == "yes") {
                        $query_on = true;
                    }

                    $ev->params["targetid"] = $ourcallid;
                    $ev->handled = true;
                    /* We must ACK this message before dispatching a call.answered */
                    $ev->Acknowledge();
                    /* Prevent a warning if trying to ACK this message again */
                    // $ev = false;  // перенесено ниже

                    /* Signal we are answering the call */
                    $m = new Yate("call.answered");

                    $m->params["id"] = $ourcallid;
                    $m->params["targetid"] = $partycallid;

                    // - - - - - - - - - - - - - - - - - - - - - - - - -
                    /*  (этого блока ранее не было)  */

                    /*  определение правого плеча путем присвоения ему идентификатора вызова (billid) левого плеча  */
                    /*  автосекретарь -> группа -> внутр. номер  */

                    //$m->params["direction"] = $ev->GetValue("direction");
                    $m->params["direction"] = 'outgoing';
                    $m->params["billid"] = $ev->GetValue("billid");
                    $m->params["caller"] = $caller;
                    //$m->params["status"] = $ev->GetValue("status");                    
                    $m->params["status"] = 'answered';
                    $m->params["called"] = 'bx24';
                    //$m->params["cdrcreate"] = 'false';
                    // $m->params["reason"] = $ev->GetValue("reason");
                    // - - - - - - - - - - - - - - - - - - - - - - - - -

                    $ev = false;

                    $m->Dispatch();

                    setState("greeting");

                    break;  // case "call.execute":

                case "chan.notify":
                    gotNotify($ev->GetValue("reason"));
                    $ev->handled = true;
                    break;

                // case "chan.dtmf":
                //     $text = $ev->GetValue("text");
                //     for ($i = 0; $i < strlen($text); $i++)
                //         gotDTMF($text[$i]);
                //     $ev->handled = true;
                //     break;

                // case "engine.timer":
                //     if (!$count)
                //         break;
                //     if ($count === true)
                //         $count = 1;
                //     else
                //         $count++;
                //     if ($count == $wait_time) {
                //         setState("call.route");
                            
                //     } elseif ($count > $wait_time + 5 ){
                //         setState("");
                //     }                       
                //     break;
    
                case "chan.connected":
                    // if( $billid == $ev->GetValue("billid") ) {
                    //     if ( $ev->GetValue("status") == "answered" || $ev->GetValue("status") == "ringing" )
                    //         Yate::Install("chan.dtmf", 10, "targetid", $ev->GetValue("targetid"));
                    //     else
                    //         Yate::Install("chan.dtmf", 10, "targetid", $ourcallid);
                    // }
                    break;
            }
            /* This is extremely important.
              We MUST let messages return, handled or not */
            if ($ev)
                $ev->Acknowledge();
            break;
        case "answer":
            Yate::Debug(">>>>>>bx24 Answered: " . $ev->name . " id: " . $ev->id);
            if ($ev->name == "call.route") {
                debug(">>>>>>call.route");
                $destination = $ev->retval;
                if ($destination) {
                    //setState("fake_ring");
                    $cs_status = "send_call";
                }   else {                    
                    $cs_status = "goodbye";                    
                }
                //$query = "UPDATE call_logs SET reason='" .$cs_status. "' WHERE chan='" .$ourcallid. "'";                ////Нужно ли для register.php без MySQL?
                //$res = query_nores($query);
                setState($cs_status);                
                setState("hangup");
            }
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

Yate::Output("PHP Auto Attendant: bye!");
/* vi: set ts=8 sw=4 sts=4 noet: */
?>