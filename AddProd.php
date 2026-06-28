<?php
session_start();
include("config/db.php");

// Only sellers can add products
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller'){
    header("Location: login.html");
    exit();
}

$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $seller_id   = $_SESSION['user_id'];
    $name        = mysqli_real_escape_string($conn, $_POST['name']);
    $price       = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category    = mysqli_real_escape_string($conn, $_POST['category']);

    // Handle image upload
    $image = "";
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
        $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName  = "product_" . time() . "." . $ext;
        $uploadPath = "images/" . $imageName;

        if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)){
            $image = $imageName;
        }
    }

    $sql = "INSERT INTO products (name, price, description, stock, seller_id, category, image) 
        VALUES ('$name', '$price', '$description', '$stock', '$seller_id', '$category', '$image')";
    if(mysqli_query($conn, $sql)){
        header("Location: AddProd.html?success=Product+added+successfully");
        exit();
    } else {
        header("Location: AddProd.html?error=Something+went+wrong");
        exit();
    }
}
?>