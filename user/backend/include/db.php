<?php 

header('Access-Control-Allow-Origin: *');

// use mysqli to fetch database (host, username, password, dbname)
 $connection = new mysqli("localhost:8889", "root", "root", "myshop");

// check if connection created or not  
if ($connection->connect_errno) {
    echo json_encode(['error' => $connection->connect_error]);
    exit();
}

