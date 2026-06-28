<?php
include("config/db.php");

$product_id = (int)$_GET['product_id'];

$sql = "SELECT reviews.*, users.name as buyer_name
        FROM reviews
        JOIN users ON reviews.user_id = users.id
        WHERE reviews.product_id = $product_id
        ORDER BY reviews.created_at DESC";

$result  = mysqli_query($conn, $sql);
$reviews = [];

while($row = mysqli_fetch_assoc($result)){
    $reviews[] = $row;
}

header('Content-Type: application/json');
echo json_encode($reviews);
?>