<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $user_id    = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $rating     = (int)$_POST['rating'];
    $comment    = mysqli_real_escape_string($conn, $_POST['comment']);

    // Check if user already reviewed this product
    $check = mysqli_query($conn, "SELECT * FROM reviews 
                                  WHERE user_id=$user_id 
                                  AND product_id=$product_id");

    if(mysqli_num_rows($check) > 0){
        echo json_encode(["status" => "exists"]);
        exit();
    }

    $sql = "INSERT INTO reviews(product_id, user_id, rating, comment)
            VALUES($product_id, $user_id, $rating, '$comment')";

    if(mysqli_query($conn, $sql)){
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}
?>