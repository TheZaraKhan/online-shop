<?php
// This script provides robust functionality for managing shopping carts for both guest users and logged-in users, integrating with a backend database for persistent storage.


header('Content-Type: application/json');

// Include necessary files
require "./include/session.php";
require "./include/db.php";

// Handle GET, POST, PATCH, and DELETE requests
switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        if (isset($_SESSION["logged_user"])) {
            getUserCart(); // Retrieve user's cart if logged in
        } else {
            getGuestCart(); // Retrieve guest user's cart
        }
        break;
    case "POST":
        if (isset($_SESSION["logged_user"])) {
            addToUserCart(); // Add item to user's cart
        } else {
            addToGuestCart(); // Add item to guest user's cart
        }
        break;
    case "PATCH":
        parse_str(file_get_contents('php://input'), $_PATCH);
        if (isset($_SESSION["logged_user"])) {
            updateUserCart($_PATCH); // Update item in user's cart
        } else {
            updateGuestCart($_PATCH); // Update item in guest user's cart
        }
        break;
    case "DELETE":
        parse_str(file_get_contents('php://input'), $_DELETE);
        if (isset($_SESSION["logged_user"])) {
            deleteUserProduct($_DELETE); // Delete item from user's cart
        } else {
            deleteGuestProduct($_DELETE); // Delete item from guest user's cart
        }
        break;
    default:
        http_response_code(405); // Method Not Allowed
        break;
}

// Function to retrieve guest user's cart
function getGuestCart() {
    if (!isset($_SESSION["cart"])) {
        $_SESSION["cart"] = array(); // Initialize cart if not set
    }
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

// Function to add item to guest user's cart
function addToGuestCart() {
    $id = $_POST["id"] ?? null;
    $image = $_POST["image"] ?? null;
    $name = $_POST["name"] ?? null;
    $quantity = isset($_POST["quantity"]) ? (int)$_POST["quantity"] : null;
    $stock = $_POST["stock"] ?? null;

    // Add item to guest cart or update quantity if item exists
    if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = [
            "name" => $name,
            "image" => $image,
            "stock" => $stock,
            "quantity" => $quantity,
            "price" => 0
        ];
    } else {
        $_SESSION['cart'][$id]['quantity'] += $quantity;
    }

    // Calculate and update item price in cart
    $price = getProdPrice($id);
    $_SESSION['cart'][$id]['price'] = round($price * $_SESSION['cart'][$id]['quantity'], 2);

    updateTotal(); // Update total price of cart
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

// Function to update item in guest user's cart
function updateGuestCart($_PATCH) {
    $id = $_PATCH['id'] ?? null;
    $quantity = $_PATCH['quantity'] ?? null;

    // Update item quantity and price in guest cart
    $_SESSION['cart'][$id]['quantity'] = $quantity;
    $price = $quantity * getProdPrice($id);
    $_SESSION['cart'][$id]['price'] = round($price, 2);

    updateTotal(); // Update total price of cart
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

// Function to delete item from guest user's cart
function deleteGuestProduct($_DELETE) {
    $id = $_DELETE["id"] ?? null;

    // Delete item from guest cart
    unset($_SESSION["cart"][$id]);

    updateTotal(); // Update total price of cart
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

// Function to retrieve user's cart
function getUserCart() {
    $cart = array();
    $userId = getUserId();
    $cartId = getCartId($userId);

    // Fetch user's cart items if cart exists
    if ($cartId != null) {
        $cart = getAllcartitems($cartId);
    }

    echo json_encode(["cart" => $cart]);
}

// Function to add item to user's cart
function addToUserCart() {
    global $connection;

    // Fetch POST variables with null coalescing operator
    $prod_id = $_POST["id"] ?? null;
    $quantity = $_POST["quantity"] ?? null;

    $quantity = (int)$quantity;
    $cart_id = getCartId(getUserId());

    // Insert or update item in user's cart in the database
    try {
        $stmt = $connection->prepare("INSERT INTO cart_item (cart_id, prod_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $stmt->bind_param("iiii", $cart_id, $prod_id, $quantity, $quantity);
        $stmt->execute();
        $stmt->close();

        // Retrieve updated cart items
        $cart = getAllcartitems($cart_id);
        echo json_encode(["cart" => $cart]);
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to add item to cart']);
    }
}

// Function to update item in user's cart
function updateUserCart($_PATCH) {
    global $connection;

    // Fetch PATCH variables with null coalescing operator
    $prod_id = $_PATCH['id'] ?? null;
    $quantity = $_PATCH['quantity'] ?? null;

    $quantity = (int)$quantity;
    $cart_id = getCartId(getUserId());

    // Update item quantity in user's cart in the database
    try {
        $stmt = $connection->prepare("UPDATE cart_item SET quantity = ? WHERE cart_id = ? AND prod_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $prod_id);
        $stmt->execute();
        $stmt->close();

        // Retrieve updated cart items
        $cart = getAllcartitems($cart_id);
        echo json_encode(["cart" => $cart]);
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to update cart item']);
    }
}

// Function to delete item from user's cart
function deleteUserProduct($_DELETE) {
    global $connection;

    // Fetch DELETE variables with null coalescing operator
    $prod_id = $_DELETE["id"] ?? null;

    $cart_id = getCartId(getUserId());

    // Delete item from user's cart in the database
    try {
        $stmt = $connection->prepare("DELETE FROM cart_item WHERE cart_id = ? AND prod_id = ?");
        $stmt->bind_param("ii", $cart_id, $prod_id);
        $stmt->execute();
        $stmt->close();

        // Retrieve updated cart items
        $cart = getAllcartitems($cart_id);
        echo json_encode(["cart" => $cart]);
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete cart item']);
    }
}

// Function to retrieve product price from database
function getProdPrice($id) {
    global $connection;
    $stmt = $connection->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['price'];
    } else {
        return -1;
    }
}

// Function to update total price of cart
function updateTotal() {
    $total = 0.00;
    foreach ($_SESSION["cart"] as $id => $item) {
        if (is_array($item) && isset($item["price"])) {
            $total += $item["price"];
        }
    }
    $_SESSION["cart"]["total"] = round($total, 2);
}

// Function to retrieve user ID from session
function getUserId() {
    return $_SESSION["logged_user"]["id"];
}

// Function to retrieve or create cart ID for user
function getCartId($userId) {
    global $connection;
    $stmt = $connection->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartId = null;

    if ($row = $result->fetch_assoc()) {
        $cartId = $row['id'];
    } else {
        // Create new cart if none exists for the user
        $stmt = $connection->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $cartId = $connection->insert_id;
        }
    }

    return $cartId;
}

// Function to retrieve all cart items for a given cart ID
function getAllcartitems($cartId) {
    global $connection;
    $stmt = $connection->prepare("
        SELECT p.id, p.name, p.image, ci.quantity, p.price, TRUNCATE((p.price * ci.quantity), 2) AS total_price, p.stock 
        FROM products p 
        INNER JOIN cart_item ci ON p.id = ci.prod_id 
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart = array();

    // Fetch and format cart items
    while ($row = $result->fetch_assoc()) {
        $id = $row["id"];
        $cart[$id] = [
            'image' => $row['image'],
            'name' => $row['name'],
            'stock' => $row['stock'],
            'quantity' => $row['quantity'],
            'price' => $row['total_price']
        ];
    }

    return updateLoggedCart($cart);
}

// Function to update total price of cart and return updated cart array
function updateLoggedCart($cart) {
    $total = 0.00;
    foreach ($cart as $item) {
        $total += $item["price"];
    }
    $cart["total"] = round($total, 2);
    return $cart;
}
