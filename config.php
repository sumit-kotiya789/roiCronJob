<?php

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'u255754182_eightcap';

// Establish connection
$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$con) {
    // Log error securely
    file_put_contents(__DIR__.'/cron_errors.log', 
        date('Y-m-d H:i:s')." - DB Connection Failed: ".mysqli_connect_error()."\n", 
        FILE_APPEND);
    exit;
}

// Local testing override (for when you run locally)
//if (php_sapi_name() === 'cli' && gethostname() === 'your-local-machine-name') {
  //  $con = mysqli_connect('localhost', 'root', '', 'local_roi_db');
//}
