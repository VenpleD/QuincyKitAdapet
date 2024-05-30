<?php

	/*
	* Author: Andreas Linde <mail@andreaslinde.de>
	*
	* Copyright (c) 2009-2011 Andreas Linde.
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
	 
//
// Download the crash log data in plain format
//
// This script is used by the remote symbolicate process to get the
// crash log data for a given crash id
//
 
require_once('../config.php');

function end_with_result($result)
{
	return '<html><body>'.$result.'</body></html>'; 
}

$allowed_args = ',id,';

$link = mysqli_connect($server, $loginsql, $passsql)
    or die(end_with_result('No database connection'));
mysqli_select_db($link,$base) or die(end_with_result('No database connection'));

foreach(array_keys($_GET) as $k) {
    $temp = ",$k,";
    if(strpos($allowed_args,$temp) !== false) { $$k = $_GET[$k]; }
}

if (!isset($id)) $id = "";

if ($id == "") die(end_with_result('Wrong parameters'));

$query = "SELECT log FROM ".$dbcrashtable." WHERE id = ".$id;
$result = mysqli_query($link,$query) or die(end_with_result('Error in SQL '.$dbversiontable));

$numrows = mysqli_num_rows($result);
if ($numrows > 0) {
	while ($row = mysqli_fetch_row($result))
	{
		echo $row[0];
	}
	mysqli_free_result($result);
}

mysqli_close($link);


?>