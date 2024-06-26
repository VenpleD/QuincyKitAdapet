<?php

	/*
	* Author: Andreas Linde <mail@andreaslinde.de>
	*
	* Copyright (c) 2009-2014 Andreas Linde.
	* All rights reserved.
	*
	* Permission is hereby granted, free of charge, to any person
	* obtaining a copy of this software and associated documentation
	* files (the "Software"), to deal in the Software without
	* restriction, including without limitation the rights to use,
	* copy, modify, merge, publish, distribute, sublicense, and/or sell
	* copies of the Software, and to permit persons to whom the
	* Software is furnished to do so, subject to the following
	* conditions:
	*
	* The above copyright notice and this permission notice shall be
	* included in all copies or substantial portions of the Software.
	*
	* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	* OTHER DEALINGS IN THE SOFTWARE.
	*/

require_once('../config.php');
require_once('common.inc');

init_database();
parse_parameters(',action,id,groupid,bundleidentifier,version,fixversion,description,');
parse_parameters_post(',action,id,groupid,bundleidentifier,version,fixversion,description,');

if (!isset($action)) $action = "";
if (!isset($id)) $id = "";
if (!isset($groupid)) $groupid = "";
if (!isset($bundleidentifier)) $version = "";
if (!isset($version)) $version = "";
if (!isset($fixversion)) $fixversion = "";
if (!isset($description)) $description = "";

if ($action == "") die('Wrong parameters');

if ($action == "deletecrashid" && $id != "") {
    $query = "DELETE FROM ".$dbsymbolicatetable." WHERE crashid = ".$id;
    $result = mysqli_query($GLOBALS['link'],$GLOBALS['link'],$query) or die('Error in SQL '.$query);

    $query = "DELETE from " . $dbcrashtable . " WHERE id=" . $id;
    $result = mysqli_query($GLOBALS['link'],$GLOBALS['link'],$query) or die('Error in SQL '.$query);
        
    if ($groupid != "" && $groupid > -1) {
        // adjust amount and timestamp
        $query = "SELECT amount, latesttimestamp FROM ".$dbgrouptable." WHERE id = ".$groupid;
        $result = mysqli_query($GLOBALS['link'],$GLOBALS['link'],$query) or die('Error in SQL: '.$query);
        
        $numrows = mysqli_num_rows($result);
        if ($numrows > 0) {
            // get the status
            while ($row = mysqli_fetch_row($result)) {
                $amount = $row[0];
                $latest = $row[1];
                $lastupdate = 0;
                
                if ($amount > 0) {
                    $query2 = "SELECT max(UNIX_TIMESTAMP(timestamp)) FROM ".$dbcrashtable." WHERE groupid = '".$groupid."'";
                    $result2 = mysqli_query($GLOBALS['link'],$GLOBALS['link'],$query2) or die('Error in SQL '.$query2);
                    $numrows2 = mysqli_num_rows($result2);
                    if ($numrows2 > 0) {
                        $row2 = mysqli_fetch_row($result2);
                        $lastupdate = $row2[0];
                        if ($lastupdate == "") $lastupdate = 0;
                    }
                    mysqli_free_result($result2);
                    
                    $query2 = "UPDATE ".$dbgrouptable." SET latesttimestamp = ".$lastupdate." WHERE id = ".$groupid;
                    $result2 = mysqli_query($GLOBALS['link'],$GLOBALS['link'],$query2) or die('Error in SQL '.$query2);
                }
            }
        }
        mysqli_free_result($result);
        
        $query = "UPDATE ".$dbgrouptable." SET amount=amount-1 WHERE id=".$groupid;
        $result = mysqli_query($GLOBALS['link'],$GLOBALS['link'],$query) or die('Error in SQL '.$query);
    }
} else if ($action == "deletegroupid" && $id != "") {
    $query = "DELETE FROM ".$dbsymbolicatetable." WHERE crashid in (select id from ".$dbcrashtable." where groupid = ".$id.")";
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $query = "DELETE FROM ".$dbcrashtable." WHERE groupid = ".$id;
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $query = "DELETE FROM ".$dbgrouptable." WHERE id = ".$id;
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
} else if ($action == "deletegroups" && $bundleidentifier != "" && $version != "") {
    $query = "DELETE FROM ".$dbsymbolicatetable." WHERE crashid in (select id from ".$dbcrashtable." where bundleidentifier = '".$bundleidentifier."' and version = '".$version."')";
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $query = "DELETE FROM ".$dbcrashtable." WHERE bundleidentifier = '".$bundleidentifier."' and version = '".$version."'";
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $query = "DELETE FROM ".$dbgrouptable." WHERE bundleidentifier = '".$bundleidentifier."' and affected = '".$version."'";
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
} else if ($action == "updategroupid" && $id != "") {
  $query = "UPDATE ".$dbgrouptable." SET description = '".mysqli_real_escape_string($description)."' WHERE id = ".$id;
  $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
} else if ($action == "symbolicatecrashid" && $id != "") {
    $query = "SELECT id FROM ".$dbsymbolicatetable." WHERE crashid = ".$id;
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $numrows = mysqli_num_rows($result);
    mysqli_free_result($result);
    
    if ($numrows > 0)
        $query = "UPDATE ".$dbsymbolicatetable." SET done = 0 WHERE crashid = ".$id;
    else
        $query = "INSERT INTO ".$dbsymbolicatetable." (crashid, done) values (".$id.", 0)";
    
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);	
} else if ($action == "getsymbolicationtodo") {
    $crashids = "";
    
    $query = "SELECT crashid FROM ".$dbsymbolicatetable." WHERE done = 0";
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $numrows = mysqli_num_rows($result);
    if ($numrows > 0) {
        while ($row = mysqli_fetch_row($result))
        {
            if ($crashids != '')
                $crashids .= ',';
                
            $crashids .= $row[0];
    
        }
        mysqli_free_result($result);
    }
    
    echo $crashids;
} else if ($action == "getlogcrashid" && $id != "") {
    $query = "SELECT log FROM ".$dbcrashtable." WHERE id = ".$id;
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $numrows = mysqli_num_rows($result);
    if ($numrows > 0) {
        while ($row = mysqli_fetch_row($result))
        {
            echo $row[0];
        }
        mysqli_free_result($result);
    }
} else if ($action == "getdescriptioncrashid" && $id != "") {
    $query = "SELECT description FROM ".$dbcrashtable." WHERE id = ".$id;
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $numrows = mysqli_num_rows($result);
    if ($numrows > 0) {
        while ($row = mysqli_fetch_row($result))
        {
            echo $row[0];
        }
        mysqli_free_result($result);
    }
} else if ($action == "downloadcrashid" && ($id != "" || $groupid != "")) {
    $query = "";
    if ($groupid != "") {
        $query = "SELECT log, timestamp FROM ".$dbcrashtable." WHERE groupid = '".$groupid."' ORDER BY systemversion desc, timestamp desc LIMIT 1";
    } else {
        $query = "SELECT log, timestamp FROM ".$dbcrashtable." WHERE id = '".$id."' ORDER BY systemversion desc, timestamp desc LIMIT 1";
    }
    $result = mysqli_query($GLOBALS['link'],$query) or die('Error in SQL '.$query);
    
    $numrows = mysqli_num_rows($result);
    if ($numrows > 0) {
        // get the status
        $row = mysqli_fetch_row($result);
        $log = $row[0];
        $timestamp = $row[1];
        
        // We'll be outputting a text file
        header('Content-type: application/text');
    
        // It will be called $timestamp.crash
        header('Content-Disposition: attachment; filename="'.$timestamp.'.crash"');
        echo $log;
        
        mysqli_free_result($result);
    }
} else {
    die('Wrong parameters');
}


mysqli_close($link);


?>