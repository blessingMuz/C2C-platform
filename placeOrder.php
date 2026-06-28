<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$data    = json_decode(file_get_contents('php://input'), true);

if(!$data){
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit();
}

// Sanitize inputs
$full_name      = mysqli_real_escape_string($conn, $data['full_name']);
$email          = mysqli_real_escape_string($conn, $data['email']);
$phone          = mysqli_real_escape_string($conn, $data['phone']);
$address        = mysqli_real_escape_string($conn, $data['address']);
$city           = mysqli_real_escape_string($conn, $data['city']);
$province       = mysqli_real_escape_string($conn, $data['province']);
$postal_code    = mysqli_real_escape_string($conn, $data['postal_code']);
$delivery_notes = mysqli_real_escape_string($conn, $data['delivery_notes']);
$payment_method = mysqli_real_escape_string($conn, $data['payment_method']);
$subtotal       = (float)$data['subtotal'];
$delivery_fee   = (float)$data['delivery_fee'];
$discount       = (float)$data['discount'];
$total          = (float)$data['total'];

// Get cart items
$cart_result = mysqli_query($conn,
    "SELECT cart.*, products.name, products.price, products.image, products.seller_id
     FROM cart
     JOIN products ON cart.product_id = products.id
     WHERE cart.user_id = $user_id");

if(mysqli_num_rows($cart_result) === 0){
    echo json_encode(["status" => "error", "message" => "Cart is empty"]);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert checkout order
    $sql = "INSERT INTO checkout_orders
            (user_id, full_name, email, phone, address, city, province,
             postal_code, delivery_notes, payment_method, subtotal, delivery_fee, total)
            VALUES
            ($user_id, '$full_name', '$email', '$phone', '$address', '$city', '$province',
             '$postal_code', '$delivery_notes', '$payment_method', $subtotal, $delivery_fee, $total)";

    mysqli_query($conn, $sql);
    $order_id = mysqli_insert_id($conn);

    // Insert each cart item as order item
    while($item = mysqli_fetch_assoc($cart_result)){
        $product_id   = (int)$item['product_id'];
        $product_name = mysqli_real_escape_string($conn, $item['name']);
        $quantity     = (int)$item['quantity'];
        $price        = (float)$item['price'];

        mysqli_query($conn,
            "INSERT INTO checkout_order_items
             (checkout_order_id, product_id, product_name, quantity, price)
             VALUES ($order_id, $product_id, '$product_name', $quantity, $price)");

        // Also insert into main orders table per product
        mysqli_query($conn,
            "INSERT INTO orders (user_id, product_id, quantity, total, status,
              full_name, email, phone, address, city, province,
              postal_code, delivery_notes, payment_method)
             VALUES ($user_id, $product_id, $quantity, " . ($price * $quantity) . ", 'pending',
              '$full_name', '$email', '$phone', '$address', '$city', '$province',
              '$postal_code', '$delivery_notes', '$payment_method')");
    }

    // Clear cart
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");

    mysqli_commit($conn);

    echo json_encode([
        "status"   => "success",
        "order_id" => $order_id,
        "message"  => "Order placed successfully"
    ]);

} catch(Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
?>
