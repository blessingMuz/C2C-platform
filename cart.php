<?php
session_start();
include("config/db.php");

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $product_id = (int)$_POST['product_id'];
    $user_id    = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    if($user_id == 0){
        echo json_encode(["status" => "login"]);
        exit();
    }

    // Check if already in cart
    $check = mysqli_query($conn, "SELECT * FROM cart 
                                  WHERE user_id=$user_id 
                                  AND product_id=$product_id");

    if(mysqli_num_rows($check) > 0){
        // Increase quantity
        mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 
                             WHERE user_id=$user_id 
                             AND product_id=$product_id");
    } else {
        // Add new item
        mysqli_query($conn, "INSERT INTO cart(user_id, product_id, quantity) 
                             VALUES($user_id, $product_id, 1)");
    }

    echo json_encode(["status" => "success"]);
}
?>