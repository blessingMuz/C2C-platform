<?php
include("config/db.php");

$data = $_POST;

// Basic validation
if(empty($data)) {
    header('HTTP/1.0 400 Bad Request');
    exit();
}

$payment_id = isset($data['m_payment_id']) ? $data['m_payment_id'] : '';
$pf_status  = isset($data['payment_status']) ? $data['payment_status'] : '';
$amount     = isset($data['amount_gross']) ? (float)$data['amount_gross'] : 0;

// Extract order ID from payment ID 
$order_id = (int)str_replace('ORDER_', '', $payment_id);

if($order_id && $pf_status === 'COMPLETE'){
    $status = 'paid';
    mysqli_query($conn,
        "UPDATE checkout_orders SET payment_status='$status' WHERE id=$order_id");
    mysqli_query($conn,
        "UPDATE orders SET payment_status='$status' WHERE id=$order_id");
}

header('HTTP/1.0 200 OK');
?>
