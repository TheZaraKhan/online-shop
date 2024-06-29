<?php

header('Content-Type: application/json');

require "./include/session.php";
require "./include/db.php";

// Handle GET, POST, PATCH, and DELETE requests
switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        if (isset($_SESSION["logged_user"])) {
            getUserCart();
        } else {
            getGuestCart();
        }
        break;
    case "POST":
        if (isset($_SESSION["logged_user"])) {
            addToUserCart();
        } else {
            addToGuestCart();
        }
        break;
    case "PATCH":
        parse_str(file_get_contents('php://input'), $_PATCH);
        if (isset($_SESSION["logged_user"])) {
            updateUserCart($_PATCH);
        } else {
            updateGuestCart($_PATCH);
        }
        break;
    case "DELETE":
        parse_str(file_get_contents('php://input'), $_DELETE);
        if (isset($_SESSION["logged_user"])) {
            deleteUserProduct($_DELETE);
        } else {
            deleteGuestProduct($_DELETE);
        }
        break;
    default:
        http_response_code(405); // Method Not Allowed
        break;
}

function getGuestCart() {
    if (!isset($_SESSION["cart"])) {
        $_SESSION["cart"] = array();
    }
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

function addToGuestCart() {
    $id = $_POST["id"] ?? null;
    $image = $_POST["image"] ?? null;
    $name = $_POST["name"] ?? null;
    $quantity = isset($_POST["quantity"]) ? (int)$_POST["quantity"] : null;
    $stock = $_POST["stock"] ?? null;
 

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

    $price = getProdPrice($id);
    $_SESSION['cart'][$id]['price'] = round($price * $_SESSION['cart'][$id]['quantity'], 2);

    updateTotal();
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

function updateGuestCart($_PATCH) {
    $id = $_PATCH['id'] ?? null;
    $quantity = $_PATCH['quantity'] ?? null;

    
    $_SESSION['cart'][$id]['quantity'] = $quantity;
    $price = $quantity * getProdPrice($id);
    $_SESSION['cart'][$id]['price'] = round($price, 2);
    updateTotal();
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

function deleteGuestProduct($_DELETE) {
    $id = $_DELETE["id"] ?? null;

    

    unset($_SESSION["cart"][$id]);
    updateTotal();
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

function getUserCart() {
    $cart = array();
    $userId = getUserId();
    $cartId = getCartId($userId);

    if ($cartId != null) {
        $cart = getAllcartitems($cartId);
    }

    echo json_encode(["cart" => $cart]);
}

function addToUserCart() {
    global $connection;

    // Log incoming POST data
    error_log("Received POST data: " . print_r($_POST, true));

    // Fetch POST variables with null coalescing operator
    $prod_id = $_POST["id"] ?? null;
    $quantity = $_POST["quantity"] ?? null;

 
    $quantity = (int)$quantity;
    $cart_id = getCartId(getUserId());

    // Prepare SQL statement with error handling
    try {
        $stmt = $connection->prepare("INSERT INTO cart_item (cart_id, prod_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $stmt->bind_param("iiii", $cart_id, $prod_id, $quantity, $quantity);
        $stmt->execute();
        $stmt->close();

        $cart = getAllcartitems($cart_id);
        echo json_encode(["cart" => $cart]);
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to add item to cart']);
    }
}

function updateUserCart($_PATCH) {
    global $connection;

    // Fetch PATCH variables with null coalescing operator
    $prod_id = $_PATCH['id'] ?? null;
    $quantity = $_PATCH['quantity'] ?? null;

    

    $quantity = (int)$quantity;
    $cart_id = getCartId(getUserId());

    // Prepare SQL statement with error handling
    try {
        $stmt = $connection->prepare("UPDATE cart_item SET quantity = ? WHERE cart_id = ? AND prod_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $prod_id);
        $stmt->execute();
        $stmt->close();

        $cart = getAllcartitems($cart_id);
        echo json_encode(["cart" => $cart]);
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to update cart item']);
    }
}

function deleteUserProduct($_DELETE) {
    global $connection;

    // Fetch DELETE variables with null coalescing operator
    $prod_id = $_DELETE["id"] ?? null;

 
    $cart_id = getCartId(getUserId());

    // Prepare SQL statement with error handling
    try {
        $stmt = $connection->prepare("DELETE FROM cart_item WHERE cart_id = ? AND prod_id = ?");
        $stmt->bind_param("ii", $cart_id, $prod_id);
        $stmt->execute();
        $stmt->close();

        $cart = getAllcartitems($cart_id);
        echo json_encode(["cart" => $cart]);
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete cart item']);
    }
}

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

function updateTotal() {
    $total = 0.00;
    foreach ($_SESSION["cart"] as $id => $item) {
        if (is_array($item) && isset($item["price"])) {
            $total += $item["price"];
        }
    }
    $_SESSION["cart"]["total"] = round($total, 2);
}

function getUserId() {
    return $_SESSION["logged_user"]["id"];
}

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
        $stmt = $connection->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $cartId = $connection->insert_id;
        }
    }

    return $cartId;
}

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

function updateLoggedCart($cart) {
    $total = 0.00;
    foreach ($cart as $item) {
        $total += $item["price"];
    }
    $cart["total"] = round($total, 2);
    return $cart;
}