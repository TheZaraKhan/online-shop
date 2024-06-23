<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');

// use mysqli to fetch database (host, username, password, dbname)
 $connection = new mysqli("localhost:8889", "root", "root", "myshop");

// check if connection created or not  
if ($connection->connect_errno) {
    echo json_encode(['error' => $connection->connect_error]);
    exit();
}

