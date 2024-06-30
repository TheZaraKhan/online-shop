<?php

// Enable CORS to allow requests from http://localhost:5500
header('Access-Control-Allow-Origin: http://localhost:8081');
header('Access-Control-Allow-Credentials: true');

// Include necessary files for session and database connection
require "./include/session.php";
require "./include/db.php";

// Handle POST request for login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve username and password from POST data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare SQL statement to check user credentials
    $sql = "SELECT * FROM `user` WHERE username = ? AND password = ?;";
    $prep_stmt = $connection->prepare($sql);
    $prep_stmt->bind_param('ss', $username, $password);
    $prep_stmt->execute();
    $result = $prep_stmt->get_result();

    // If user is found, store user data in session and return username
    if ($row = $result->fetch_assoc()) {
        $_SESSION['logged_user'] = [
            'name' => $row['username'],
            'id' => $row['id'],
            'email' => $row['email']

        ];

        // Remove guest cart from session
        unset($_SESSION['cart']);
        echo json_encode(['user' => $_SESSION['logged_user']['name']]);
    } else {
        // If user is not found, return an error message
        echo json_encode(['error' => 'Invalid username or password']);
    }

    // Close prepared statement and exit script
    $prep_stmt->close();
    exit();
}

// Handle GET request to check login status
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // If user is logged in, return username
    if (isset($_SESSION['logged_user'])) {
        echo json_encode(['user' => $_SESSION['logged_user']['name']]);
    } else {
        // If user is not logged in, return null
        echo json_encode(['user' => null]);
    }
    exit();
}

