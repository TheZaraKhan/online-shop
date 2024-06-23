<?php

// Enable CORS to allow requests from http://localhost:5500
header('Access-Control-Allow-Origin: http://localhost:');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Include session management
require "./include/session.php";

// Handle POST request for logout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Destroy the session to log out the user
    session_destroy();
    echo json_encode(['success' => true]);
    exit();
}

// If the request method is not POST, return an error
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
?>


