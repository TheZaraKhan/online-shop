<?php
 
require "./include/db.php";

 
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');  

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM banner WHERE status=1;";
    if ($result = $connection->query($sql)) {
        $arr = array();
        while ($rowArray = $result->fetch_assoc()) {
           array_push($arr,$rowArray);
        }
        echo json_encode(['banner' => $arr]);
    } else {
        echo json_encode(['error' => 'Something went wrong']);
    }
    exit();
}
