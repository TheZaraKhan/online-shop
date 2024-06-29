<?php
header("Content-Type: application/json");

ob_start();
require "./cart.php"; 
require "../../vendor/autoload.php"; 
ob_end_clean();

$cart;
$logged_user_id;
$cart_id;
$total_price;
$name;
$address;
$postcode;
$city;
$email;
$stripe_data;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (isset($_SESSION["logged_user"])) {
        $name = $_SESSION["logged_user"]["name"];
        $logged_user_id = $_SESSION["logged_user"]["id"];
        $cart_id = getCartId($logged_user_id);
        $cart = getAllcartitems($cart_id);
    } else {
        $cart = $_SESSION["cart"];
        $name = $_POST["name"];
    }

    $total_price = $cart["total"];
    $stripe_data = initiateCheckout($total_price);

    addtoOrderTable($stripe_data['payment_id']);
    clearCart();

    echo json_encode([
        "status" => "success",
        "url" => $stripe_data['url'],
    ]);
    exit();
}

function initiateCheckout($total_price)
{
    $YOUR_DOMAIN = "http://localhost:8081/user/frontend/";
    $stripeSecretKey = "sk_test_51PUMPqDFkssOFOanuAzUEIqfH9N5S1BCJ9jAoSrNsoN64n0YjlgeAV6vLk74Pwyw8XP0yDHwibpMHNwAqrDYJk530041vNxkiZ";

    $stripe  = new \Stripe\StripeClient($stripeSecretKey);

    $session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Shopping Total',
                ],
                'unit_amount' => $total_price * 100,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $YOUR_DOMAIN . 'success.html',
        'cancel_url' => $YOUR_DOMAIN . 'cancel.html',
    ]);

    return [
        'url' => $session->url,
        'payment_id' => $session->payment_intent, // Ensure this field captures the payment intent ID
    ];
}

function addtoOrderTable($payment_id)
{
    global $connection, $cart, $logged_user_id, $total_price, $name;

    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $postcode = isset($_POST['postcode']) ? $_POST['postcode'] : '';
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';

    $user_id = $logged_user_id == null ? "NULL" : $logged_user_id;

    $sql = "INSERT INTO orders (user_id, name, address, postal_code, city, email, total_price, payment_id, order_date, order_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("issssdsd", $user_id, $name, $address, $postcode, $city, $email, $total_price, $payment_id);
    $stmt->execute();

    $order_id = $connection->insert_id;

    foreach ($cart as $prod_id => $product) {
        $quantity = $product["quantity"];
        $price = $product["price"];

        if ($price > 0.00) {
            $sql = "INSERT INTO order_item (order_id, prod_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("iiid", $order_id, $prod_id, $quantity, $price);
            $stmt->execute();
        }
    }
}

function clearCart()
{
    global $connection, $cart_id;

    $connection->begin_transaction();

    $sql_delete_items = "DELETE FROM cart_item WHERE cart_id = ?";
    $stmt_delete_items = $connection->prepare($sql_delete_items);
    $stmt_delete_items->bind_param("i", $cart_id);
    $stmt_delete_items->execute();

    if (isset($_SESSION["cart"])) {
        unset($_SESSION["cart"]);
    } else {
        $sql_delete_cart = "DELETE FROM cart WHERE id = ?";
        $stmt_delete_cart = $connection->prepare($sql_delete_cart);
        $stmt_delete_cart->bind_param("i", $cart_id);
        $stmt_delete_cart->execute();
    }

    $connection->commit();
}
