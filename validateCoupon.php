<?php
include("config/db.php");
header('Content-Type: application/json');

$code     = strtoupper(trim($_POST['code']     ?? ''));
$subtotal = (float)        ($_POST['subtotal'] ?? 0);

//Basic input check 
if(!$code){
    echo json_encode(['status' => 'error', 'message' => 'Please enter a coupon code.']);
    exit;
}

$code_safe = mysqli_real_escape_string($conn, $code);

// Look up in the database 
$result = mysqli_query($conn,
    "SELECT * FROM coupons
     WHERE code = '$code_safe'
     LIMIT 1"
);

if(!$result || mysqli_num_rows($result) === 0){
    echo json_encode(['status' => 'error', 'message' => 'Invalid coupon code.']);
    exit;
}

$coupon = mysqli_fetch_assoc($result);

if(!$coupon['is_active']){
    echo json_encode(['status' => 'error', 'message' => 'This coupon is no longer active.']);
    exit;
}

if($coupon['expires_at'] && strtotime($coupon['expires_at']) < strtotime('today')){
    echo json_encode(['status' => 'error', 'message' => 'This coupon has expired.']);
    exit;
}
 
if($coupon['max_uses'] !== null && $coupon['uses_so_far'] >= $coupon['max_uses']){
    echo json_encode(['status' => 'error', 'message' => 'This coupon has reached its usage limit.']);
    exit;
}

// Check minimum order amount 
if($subtotal < (float)$coupon['min_order']){
    echo json_encode([
        'status'  => 'error',
        'message' => 'Minimum order of R' . number_format($coupon['min_order'], 2) . ' required for this code.'
    ]);
    exit;
}
$label = $coupon['type'] === 'percent'
    ? $coupon['value'] . '% off'
    : 'Free Delivery';

echo json_encode([
    'status' => 'valid',
    'type'   => $coupon['type'],
    'value'  => (float) $coupon['value'],
    'label'  => $label,
]);
