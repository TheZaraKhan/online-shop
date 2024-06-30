<?php
require '../../vendor/autoload.php';
require './include/session.php';
require './include/db.php'; // Your database connection file

$stripeSecretKey = "sk_test_51PUMPqDFkssOFOanuAzUEIqfH9N5S1BCJ9jAoSrNsoN64n0YjlgeAV6vLk74Pwyw8XP0yDHwibpMHNwAqrDYJk530041vNxkiZ";
\Stripe\Stripe::setApiKey($stripeSecretKey);

// Get the session ID from the URL
$session_id = $_GET['session_id'];
if ($session_id) {
    try {
        // Retrieve the session from Stripe
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        $payment_intent_id = $session->payment_intent;

        // Debugging: Ensure we have the IDs
        echo "Session ID: " . $session_id . "<br>";
        echo "Payment Intent ID: " . $payment_intent_id . "<br>";

        // Assuming the $order_id is stored in the session or passed through some other means
        $order_id = $_SESSION['order_id'];

        // Update the orders table
        $sql = "UPDATE orders SET payment_id = ?, session_id = ? WHERE id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssi", $payment_intent_id, $session_id, $order_id);

        if ($stmt->execute()) {
            echo "Payment successful! Your payment ID is: " . $payment_intent_id;

            // Redirect to the success page
            $redirect_url = "$domain/#checkout-done";
            header("Location: $redirect_url");
            exit();
        } else {
            echo "Error updating the order: " . $stmt->error;
        }

        $stmt->close();
        $connection->close();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo 'Error retrieving session: ', $e->getMessage();
    }
} else {
    echo "No session ID found.";
}
