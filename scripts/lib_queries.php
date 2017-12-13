<?php
/**
 * lib_queries.php
 * This file is part of the FreeSentral Project http://freesentral.com
 *
 * FreeSentral - is a Web Graphical User Interface for easy configuration of the Yate PBX software
 * Copyright (C) 2008-2009 Null Team
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

require_once("config.php");
//require_once("libyate.php");

function query_to_array($query) {
    global $conn, $query_on, $type_debug;
    $array  = $conn->getAll($query);
    if($query_on) {
        Yate::Output("Executed[$type_debug]: $query");
        Yate::Output("Result[$type_debug]:".json_encode($array)."\n");
    }
    return $array;
}

function query($query) {
    global $conn, $query_on, $type_debug;
    global $dsn;
    $resets = 0;
    if ($conn)
             $res = $conn->query($query);
    if($query_on)
        Yate::Output("Executed[$type_debug]: $query"."\n");
    return $res;
}

function query_nores($query) {
    $res = query($query);
}

function getCustomVoicemailDir($called) {
    global $vm_base;

    $last = $called[strlen($called)-1];
    $alast = $called[strlen($called)-2];

    $dir = "$vm_base/$last";
    if (!is_dir($dir)) {
        mkdir($dir,0750);
        //chown($dir,"www-data");
    }
    $dir = "$vm_base/$last/$alast/";
    if (!is_dir($dir)) {
        mkdir($dir,0750);
        //chown($dir,"www-data");
    }
    $dir = "$vm_base/$last/$alast/$called";
    if (!is_dir($dir)) {
        mkdir($dir,0750);
        //chown($dir,"www-data");
    }
    return $dir;
}

function chek_debug() {
    global  $query_on, $type_debug;
    //$type_debug = 'register';                           //one rules to ALL .php
    $res = query_to_array("SELECT param, value FROM settings where description = '$type_debug'");
    foreach ($res as $row) {       
       if ($row["param"] == "debug")
            set_debug($row["value"], $type_debug);
       elseif ($row["param"] == "query") {
            $query_on = $row["value"];
            if ($query_on)
                 Yate::Output(">>> Enabling query ".$type_debug.".php module");
            else
                 Yate::Output(">>> Disable query ".$type_debug.".php module");
       }
    }
}

function set_debug($enbl) {
    global $type_debug, $debug_on;    
    if ($enbl) {
        $debug_on = true;
        Yate::Debug(true);
        Yate::Output(true);
        Yate::Output(">>> Enabling debug ".$type_debug.".php module");
    } else {
        $debug_on = false;
        Yate::Output(">>> Disable debug ".$type_debug.".php module");        
        Yate::Output(false);
        Yate::Debug(false);
    }    
}

function debug($mess) {
    global $type_debug, $debug_on;

    Yate::Debug("[DEBUG ".$type_debug.".php]: " . $mess);
}
?>