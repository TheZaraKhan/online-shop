<?php

require "./include/db.php";

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['category'])) {
    // Array to hold the response
    $response = array();

    // SQL query for randomly giving three products
    $sql_featured = "SELECT * FROM `products` WHERE STATUS = 1 ORDER BY RAND() LIMIT 3;";
    if ($result = $connection->query($sql_featured)) {
        $featured = array();
        while ($row = $result->fetch_assoc()) {
            array_push($featured, $row);
        }
        $response['featured'] = $featured;
    } else {
        $response['error'] = 'Something went wrong with the featured products query';
    }

    // SQL query for the latest three products
    $sql_new_arrivals = "SELECT * FROM `products` WHERE STATUS = 1 ORDER BY added_on DESC LIMIT 3;";
    if ($result = $connection->query($sql_new_arrivals)) {
        $newArrivals = array();
        while ($row = $result->fetch_assoc()) {
            array_push($newArrivals, $row);
        }
        $response['newArrivals'] = $newArrivals;
    } else {
        $response['error'] = 'Something went wrong with the new arrivals query';
    }

    // SQL query for all products
    $sql_allProducts = "SELECT * FROM `products`";
    if ($result = $connection->query($sql_allProducts)) {
        $allProducts = array();
        while ($row = $result->fetch_assoc()) {
            array_push($allProducts, $row);
        }
        $response['allProducts'] = $allProducts;
    } else {
        $response['error'] = 'Something went wrong with the all products query';
    }

    // Output the response as JSON
    echo json_encode($response);
    exit();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['category'])) {
    // Array to hold the response
    $response = array();
    $category = $_GET['category'];

    $sql_getCategory = "SELECT * FROM `products` WHERE status = 1 AND category_id = (SELECT id FROM categories WHERE name = ?);";
    $prep_stmt= $connection->prepare($sql_getCategory);

    $prep_stmt->bind_param('s', $category);
    $prep_stmt->execute();
    if ($result = $prep_stmt->get_result()) {
        $products = array();
        while ($row = $result->fetch_assoc()) {
            array_push($products, $row);
        }
        $response['categoryProducts'] = $products;
        $prep_stmt->close();
    } else {
        $response['error'] = 'Something went wrong with the category products query';
    }

    // Output the response as JSON
    echo json_encode($response);
    exit();
}
