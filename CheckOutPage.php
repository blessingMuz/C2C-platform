<?php
include("config/db.php");

$message = "";

if(isset($_POST['place_order'])){

    // GET FORM DATA
    $fullname        = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email           = mysqli_real_escape_string($conn, $_POST['email']);
    $phone           = mysqli_real_escape_string($conn, $_POST['phone']);
    $address         = mysqli_real_escape_string($conn, $_POST['address']);
    $city            = mysqli_real_escape_string($conn, $_POST['city']);
    $province        = mysqli_real_escape_string($conn, $_POST['province']);
    $postal_code     = mysqli_real_escape_string($conn, $_POST['postal_code']);
    $notes           = mysqli_real_escape_string($conn, $_POST['notes']);
    $delivery_method = mysqli_real_escape_string($conn, $_POST['delivery_method'] ?? 'standard');
    $coupon_code     = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['coupon_code'] ?? '')));

    $delivery_prices = [
        'standard' => 100,
        'express'  => 180,
        'pickup'   => 0,
    ];

    // PRICING — cast to float so no string injection
    $subtotal     = (float) ($_POST['subtotal'] ?? 0);
    $vat          = round($subtotal * 0.15, 2);
    $delivery_fee = $delivery_prices[$delivery_method] ?? 100;
    $discount     = 0;

    // Auto free delivery over R1500
    if($subtotal >= 1500) $delivery_fee = 0;

    // Validate coupon against the database
    $coupon_row = null;
    if($coupon_code){
        $result = mysqli_query($conn,
            "SELECT * FROM coupons
             WHERE code = '$coupon_code'
               AND is_active = 1
             LIMIT 1"
        );

        if($result && mysqli_num_rows($result) > 0){
            $coupon_row = mysqli_fetch_assoc($result);

            // Re-run all validity checks server-side
            $expired   = $coupon_row['expires_at'] && strtotime($coupon_row['expires_at']) < strtotime('today');
            $maxed_out = $coupon_row['max_uses'] !== null && $coupon_row['uses_so_far'] >= $coupon_row['max_uses'];
            $min_ok    = $subtotal >= (float) $coupon_row['min_order'];

            if(!$expired && !$maxed_out && $min_ok){
                if($coupon_row['type'] === 'percent'){
                    $discount = round(($subtotal * (float)$coupon_row['value']) / 100, 2);
                } elseif($coupon_row['type'] === 'free_delivery'){
                    $delivery_fee = 0;
                }
            } else {
                // Coupon failed server-side checks — clear it
                $coupon_code = '';
                $coupon_row  = null;
            }
        } else {
            $coupon_code = '';
        }
    }

    // Recalculate total server-side so client can't manipulate it
    $total = $subtotal + $delivery_fee + $vat - $discount;
    if($total < 0) $total = 0;

    // INSERT INTO DATABASE
    $sql = "INSERT INTO orders
            (fullname, email, phone, address, city, province, postal_code,
             notes, delivery_method, delivery_fee, subtotal, vat, discount,
             coupon_code, total_price)
            VALUES
            ('$fullname', '$email', '$phone', '$address',
             '$city', '$province', '$postal_code', '$notes',
             '$delivery_method', '$delivery_fee', '$subtotal',
             '$vat', '$discount', '$coupon_code', '$total')";

    if(mysqli_query($conn, $sql)){

        $message = "Order placed successfully!";

        // Increment coupon usage counter now that order is confirmed
        if($coupon_code && $coupon_row){
            mysqli_query($conn,
                "UPDATE coupons SET uses_so_far = uses_so_far + 1 WHERE code = '$coupon_code'"
            );
        }

    } else {

        $message = "Failed to place order.";

    }

}
?>