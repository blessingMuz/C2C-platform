<?php
include("config/db.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id == 0){
    echo json_encode(null);
    exit();
}

$sql = "SELECT products.*, users.name as seller_name, users.id as seller_user_id
        FROM products 
        JOIN users ON products.seller_id = users.id
        WHERE products.id = $id";

$result  = mysqli_query($conn, $sql);

if(!$result || mysqli_num_rows($result) == 0){
    echo json_encode(["error" => mysqli_error($conn)]);
    exit();
}

$product = mysqli_fetch_assoc($result);
header('Content-Type: application/json');
echo json_encode($product);
?>