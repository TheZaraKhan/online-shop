<?php


require "./include/session.php";
require "./include/db.php";

// Handle GET and POST requests
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (isset($_SESSION["logged_user"])) {
        getUserCart();
    } else {
        getGuestCart();
    }
    exit();
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_SESSION["logged_user"])) {
        addToUserCart();
    } else {
        addToGuestCart();
    }
    exit();
}
elseif ($_SERVER["REQUEST_METHOD"] === "PATCH") {
 parse_str(file_get_contents('php://input'), $_PATCH);
  
  
    if (isset($_SESSION["logged_user"])) {
        updateUserCart($_PATCH);
    }else {
        updateGuestCart($_PATCH);
    }
    exit();

    
}
// Handle DELETE requests

elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
 parse_str(file_get_contents('php://input'), $_DELETE);
  
  
    if (isset($_SESSION["logged_user"])) {
        deleteUserProduct($_DELETE);
    }else {
        deleteGuestProduct($_DELETE);
    }
    exit();

    
}





function getGuestCart()
{
    if (!isset($_SESSION["cart"])) {
        $_SESSION["cart"] = array();
    }
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

function addToGuestCart()
{
    $id = $_POST["id"];
    $image = $_POST["image"];
    $name = $_POST["name"];
    $quantity = (int)$_POST["quantity"];
    $stock = $_POST["stock"];

    if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = [
            "name" => $name,
            "image" => $image,
            "stock" => $stock,
            "quantity" => $quantity,
            "price" => 0 // Initialize price to 0
        ];
    } else {
        $_SESSION['cart'][$id]['quantity'] += $quantity;
    }

    $price = getProdPrice($id);
    $_SESSION['cart'][$id]['price'] = round($price * $_SESSION['cart'][$id]['quantity'], 2);

    updateTotal();
    echo json_encode(["cart" => $_SESSION["cart"]]);
}

function updateGuestCart($_PATCH)
{

  $id = $_PATCH['id'];
  $quantity = $_PATCH['quantity'];
  $_SESSION['cart'][$id]['quantity'] = $quantity;
  $price = $quantity * getProdPrice($id);
  $price = round($price, 2);
  $_SESSION['cart'][$id]['price'] = $price;
  updateTotal();
  echo json_encode(["cart" => $_SESSION["cart"]]);

}

function deleteGuestProduct($_DELETE) {
    $id = $_DELETE["id"];
    unset($_SESSION["cart"][$id]);
    updateTotal();
    echo json_encode(["cart" => $_SESSION["cart"]]);
}



function getUserCart()
{
    global $connection;
    $cart = array();
$userId = getUserId();

    // step:1 get the id from cart where user id is equal to logged user id

$cartId = getCartId($userId);
    // step:2 get the cart from cart table where cart id is equal to cart id
    if($cartId != null){
        $cart = getAllcartitems($cartId);}
    
echo json_encode(["cart" => $cart]); 
 
}

function addToUserCart()
{
 global $connection;
 $prod_id =  $_POST["id"];
 $quantity = (int)$_POST["quantity"];
 $cart_id = getCartId(getUserId());
 $stmt = "INSERT INTO cart_item (cart_id, prod_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?";
 $stmt = $connection->prepare($stmt);
 $stmt->bind_param("iiii", $cart_id, $prod_id, $quantity, $quantity);
 $stmt->execute();
 $stmt->close(); 
 $cart = getAllcartitems($cart_id);
 echo json_encode(["cart" => $cart]);
}


function updateUserCart($_PATCH){
    global $connection;
    $prod_id = $_PATCH['id'];
    $quantity = $_PATCH['quantity'];
    $cart_id = getCartId(getUserId());
    $stmt = "UPDATE cart_item set quantity = ? where cart_id=$cart_id and prod_id = ?";
    $stmt = $connection->prepare($stmt);
    $stmt->bind_param("ii",$quantity, $prod_id);
    $stmt->execute();
    $stmt->close(); 
    $cart = getAllcartitems($cart_id);
    echo json_encode(["cart" => $cart]); 
    
}
function deleteUserProduct($_DELETE){
    global $connection;
    $prod_id = $_DELETE["id"];
    $cart_id = getCartId(getUserId());
    $stmt = 'DELETE FROM cart_item where cart_id = ? and prod_id = ?  ';
    $stmt = $connection->prepare($stmt);
    $stmt->bind_param("ii",$cart_id, $prod_id);
    $stmt->execute();
    $stmt->close(); 
    $cart = getAllcartitems($cart_id);
    echo json_encode(["cart" => $cart]);
    
}


// Helper functions for user cart

function getProdPrice($id)
{
    global $connection;
    $sql = "SELECT price FROM products WHERE id = ?;";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($result = $stmt->get_result()) {
        $row = $result->fetch_assoc();
        return $row['price']; // Return the price value
    } else {
        return -1;
    }
}

function updateTotal()
{
    $total = 0.00;
    foreach ($_SESSION["cart"] as $item) {
        if (is_array($item) && isset($item["price"])) {
            $total += $item["price"];
        }
    }
    $total = round($total, 2);
    $_SESSION["cart"]["total"] = $total;
}


// Helper functions for logged in user cart
function getUserId(){
    return $_SESSION["logged_user"]["id"];
}
function getCartId($userId){

    global $connection;
    $cartId = null;
    $sql = "SELECT id FROM cart WHERE user_id = $userId;";
    if ($result = $connection->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cartId = $row['id'];
        }
    } else {
        $sql = "INSERT INTO cart (user_id) VALUES ($userId);";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
             $cartId = $connection->insert_id;
            }
        }
        return $cartId; 
}
function getAllcartitems($cartId){
    global $connection;

    $cart = array();
    $stmt = "SELECT p.id ,p.name, p.image, ci.quantity, price, TRUNCATE((p.price * ci.quantity),2) as total_price, p.stock 
    FROM products p INNER JOIN cart_item ci 
    ON p.id = ci.prod_id and ci.cart_id = $cartId
    INNER JOIN inventory i on p.id = i.prod_id;";
    if ($result = $connection->query($stmt)) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row["id"];
                $prod_array[$id]['image'] = $row['image'];
                $prod_array[$id]['name'] = $row['name'];
                $prod_array[$id]['stock'] = $row['stock'];
                $prod_array[$id]['quantity'] = $row['quantity']; 
                $prod_array[$id]['price'] = $row['total_price'];
            }
        }
    }
    return updateLoggedCart($prod_array);
}

function updateLoggedCart($prod_array){
    $total = 0.00;
    foreach ($prod_array as $item) {
        $total += $item["price"];
        $total = round($total, 2);
    } 
    $prod_array["total"] = $total;
    return $prod_array;
}