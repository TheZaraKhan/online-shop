<?php

require "./cart.php";
require "../vendor/autoload.php";

// Test API key.
$stripeSecretKey = 'sk_test_51PUMPqDFkssOFOanuAzUEIqfH9N5S1BCJ9jAoSrNsoN64n0YjlgeAV6vLk74Pwyw8XP0yDHwibpMHNwAqrDYJk530041vNxkiZ';

\Stripe\Stripe::setApiKey($stripeSecretKey);

header('Content-Type: application/json');

// Initialize variables
$cart = [];
$logged_user_id = null;
$cart_id = null;
$total_price = 0;
$name = '';
$stripe_data = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    session_start();
    global $connection;

    if (isset($_SESSION["logged_user"])) {
        $name = $_SESSION["logged_user"]["name"];
        $logged_user_id = $_SESSION["logged_user"]["id"];
        $cart_id = getCartId($logged_user_id);
        $cart = getAllcartitems($cart_id);
    } else {
        $cart = $_SESSION["cart"];
        $name = $_POST["name"];
        $address = $_POST["address"];
        $postal_code = $_POST["postal_code"];
        $city = $_POST["city"];
        $email = $_POST["email"];
    }

    $total_price = $cart["total"];
    unset($cart["total"]); 

    $stripe_data = initiateCheckout($total_price);
    addToDB($cart, $logged_user_id, $name, $total_price, $stripe_data, $cart_id, $address, $postal_code, $city, $email);
    clearCart();

    echo json_encode(["url" => $stripe_data['url']]);
    
}

function initiateCheckout($total_price) {
    $DOMAIN = 'http://localhost:8081/user/frontend/';
    $checkout_session = \Stripe\Checkout\Session::create([
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Total Bill',
                ],
                'unit_amount' => $total_price * 100,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $DOMAIN . 'success.html',
        'cancel_url' => $DOMAIN . 'cancel.html',
    ]);
    return [
        'url' => $checkout_session->url,
        'payment_id' => $checkout_session->payment_intent
    ];
}

function addToDB($cart, $logged_user_id, $name, $total_price, $stripe_data, $cart_id, $address, $postal_code, $city, $email) {
    global $connection;

    $user_id = $logged_user_id == null ? 'NULL' : $logged_user_id;
    $payment_id = $stripe_data["payment_id"]; 
    $stmt = $connection->prepare("INSERT INTO orders (user_id, name, address, postal_code, city, email, total_price, payment_id, cart_id, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssssdsis", $user_id, $name, $address, $postal_code, $city, $email, $total_price, $payment_id, $cart_id);
    $stmt->execute();

    $order_id = $connection->insert_id;
    $stmt->close();

    foreach ($cart as $id => $item) { 
        $prod_id = $id; // Ensure this is the correct product ID
        $quantity = $item["quantity"];
        $price = $item["price"];
        $stmt = $connection->prepare("INSERT INTO order_item (order_id, prod_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $prod_id, $quantity, $price);
        $stmt->execute();
    }
    $stmt->close();
}

function clearCart() {
    if (isset($_SESSION["logged_user"])) {
        // Clear user's cart in the database
        $cart_id = getCartId($_SESSION["logged_user"]["id"]);
        global $connection;
        $stmt = $connection->prepare("DELETE FROM cart_item WHERE cart_id = ?");
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Clear guest cart session
        unset($_SESSION["cart"]);
    }
}
