<?php

require_once('../../../../wp-load.php');

//Typical headers
header('Content-Type: text/html');
send_nosniff_header();

//Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');

//Allow Cross-Origin Resource Sharing
header("Access-Control-Allow-Origin: *");	
