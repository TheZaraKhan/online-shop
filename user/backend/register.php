<?php
// Include database and session files
require "./include/db.php";
require "./include/session.php";

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if all required fields are present in the POST data
    if (!isset($_POST['firstName']) || !isset($_POST['lastName']) || !isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['password'])) {
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    } 

    // Sanitize input data
    $firstName = mysqli_real_escape_string($connection, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($connection, $_POST['lastName']);
    $username = mysqli_real_escape_string($connection, $_POST['username']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);

    // Validate input data
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
        echo json_encode(['error' => 'All fields are required']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email']);
        exit();
    }

    if (strlen($password) < 6) {
        echo json_encode(['error' => 'Password must be at least 6 characters long']);
        exit();
    }

    // Check if the username is already taken
    $sql = "SELECT 1 FROM user WHERE username = ? LIMIT 1";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['error' => 'Username already taken']);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Check if the email is already taken
    $sql = "SELECT 1 FROM user WHERE email = ? LIMIT 1";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['error' => 'Email already taken']);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // If all checks pass, insert the user into the database
    $sql = "INSERT INTO user (firstname, lastname, username, email, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('sssss', $firstName, $lastName, $username, $email, $password);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to insert user into database']);
    }
    $stmt->close();
    $connection->close();
}