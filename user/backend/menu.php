<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require "./include/db.php";

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json'); // Set content type to JSON

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT name FROM categories WHERE status=1;";
    if ($result = $connection->query($sql)) {
        $arr = array();
        while ($row = $result->fetch_assoc() ) {
            array_push($arr, $row['name']);
        }
        echo json_encode(['categories' => $arr]);
    } else {
        echo json_encode(['error' => 'Something went wrong']);
    }
    exit();
}
?>
