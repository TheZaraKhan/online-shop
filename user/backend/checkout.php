<?php

//This PHP script handles the checkout process for an online shop integrated with Stripe payments. It begins by initializing necessary components such as the shopping cart and Stripe's SDK. Depending on whether the user is logged in or not, it retrieves user information such as name, email, and cart items either from session data or POST requests. Using this data, it initiates a Stripe checkout session with the total price calculated from the cart items. Upon successful checkout initiation, it stores order details including user information, products, and payment status in the database. Finally, it clears the user's cart and returns a JSON response containing the checkout URL provided by Stripe for payment processing.




// Set the content type header for JSON
header("Content-Type: application/json");

// Start output buffering to manage output before sending headers
ob_start();

// Include necessary files
require "./cart.php";  
require "../../vendor/autoload.php"; // Include Stripe PHP library
ob_end_clean(); // Clean the output buffer

// Declare variables used in the script
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

// Handle POST request method
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if user is logged in via session
    if (isset($_SESSION["logged_user"])) {
        // Assign user details from session
        $name = $_SESSION["logged_user"]["name"];
        $logged_user_id = $_SESSION["logged_user"]["id"];
        $email = $_SESSION["logged_user"]["email"];
        $cart_id = getCartId($logged_user_id); // Retrieve cart ID for logged in user
        $cart = getAllcartitems($cart_id); // Retrieve cart items for logged in user
    } else {
        // For guest user, get details from POST data
        $cart = $_SESSION["cart"];
        $name = $_POST["name"];
        $email = $_POST["email"];
    }

    // Calculate total price of items in the cart
    $total_price = $cart["total"];

    // Initiate checkout session with Stripe
    $stripe_data = initiateCheckout($total_price);

    // Add order details to database and clear cart
    addtoOrderTable($stripe_data['session_id']);
    clearCart();

    // Return success response with Stripe checkout URL
    echo json_encode([
        "status" => "success",
        "url" => $stripe_data['url'],
    ]);
    exit(); // End script execution
}

// Function to initiate Stripe checkout session
function initiateCheckout($total_price)
{
    // Replace with your actual domain and Stripe secret key
    $YOUR_DOMAIN = "http://localhost:8081/user/";
    $stripeSecretKey = "sk_test_51PUMPqDFkssOFOanuAzUEIqfH9N5S1BCJ9jAoSrNsoN64n0YjlgeAV6vLk74Pwyw8XP0yDHwibpMHNwAqrDYJk530041vNxkiZ";

    // Initialize Stripe client
    $stripe  = new \Stripe\StripeClient($stripeSecretKey);

    // Create checkout session with Stripe
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
        'success_url' => $YOUR_DOMAIN . 'backend/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $YOUR_DOMAIN . 'cancel.html',
    ]);

    // Return session details
    return [
        'url' => $session->url,
        'session_id' => $session->id,
    ];
}

// Function to add order details to database
function addtoOrderTable($session_id)
{
    // Access global variables needed for database interaction
    global $connection, $cart, $logged_user_id, $total_price, $name, $email;

    // Get address details from POST data or set empty if not provided
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $postcode = isset($_POST['postcode']) ? $_POST['postcode'] : '';
    $city = isset($_POST['city']) ? $_POST['city'] : '';

    // Prepare SQL statement for inserting order into database
    if ($logged_user_id === null) {
        // For guest user, prepare SQL without user_id
        $sql = "INSERT INTO orders (user_id, name, address, postal_code, city, email, total_price, session_id, payment_id, order_date, order_status) 
         VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, NULL, NOW(), 'pending')";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("sssssss", $name, $address, $postcode, $city, $email, $total_price, $session_id);
    } else {
        // For logged in user, prepare SQL with user_id
        $sql = "INSERT INTO orders (user_id, name, address, postal_code, city, email, total_price, session_id, payment_id, order_date, order_status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW(), 'pending')";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("isssssss", $logged_user_id, $name, $address, $postcode, $city, $email, $total_price, $session_id);
    }

    // Execute SQL statement
    $stmt->execute();

    // Get the newly inserted order ID
    $order_id = $connection->insert_id;

    // Store the order_id in session for later use
    $_SESSION['order_id'] = $order_id;

    // Insert each product in the cart into the order_items table
    foreach ($cart as $prod_id => $product) {
        if (is_array($product)) {
            $quantity = isset($product["quantity"]) ? $product["quantity"] : 0;
            $price = isset($product["price"]) ? $product["price"] : 0.0;

            if ($price > 0.00) {
                // Prepare SQL for inserting order item
                $sql = "INSERT INTO order_item (order_id, prod_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("iiid", $order_id, $prod_id, $quantity, $price);
                $stmt->execute();
            }
        }
    }
}

// Function to clear cart items after successful order placement
function clearCart()
{
    global $connection, $cart_id;

    // Begin database transaction for atomic operations
    $connection->begin_transaction();

    // Delete cart items associated with the cart_id
    $sql_delete_items = "DELETE FROM cart_item WHERE cart_id = ?";
    $stmt_delete_items = $connection->prepare($sql_delete_items);
    $stmt_delete_items->bind_param("i", $cart_id);
    $stmt_delete_items->execute();

    // Check if cart is stored in session and unset it
    if (isset($_SESSION["cart"])) {
        unset($_SESSION["cart"]);
    } else {
        // If cart is not in session, delete cart entry from database
        $sql_delete_cart = "DELETE FROM cart WHERE id = ?";
        $stmt_delete_cart = $connection->prepare($sql_delete_cart);
        $stmt_delete_cart->bind_param("i", $cart_id);
        $stmt_delete_cart->execute();
    }

    // Commit transaction to apply changes
    $connection->commit();
}
