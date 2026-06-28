<?php
include("config/db.php");

$id     = (int)$_GET['id'];
$result = mysqli_query($conn, "SELECT id, name FROM users WHERE id=$id");
$user   = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode($user);
?>