<?php
session_start();
require_once "connect.php";

// Create database connection
$db = new Database();
$conn = $db->connect();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['product_id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['product_id']);

// Check if product already in cart
$stmt = $conn->prepare("SELECT cart_id, quantity FROM tbl_cart WHERE user_id=? AND product_id=? AND status='active'");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update quantity
    $row = $result->fetch_assoc();
    $new_qty = $row['quantity'] + 1;
    $update = $conn->prepare("UPDATE tbl_cart SET quantity=? WHERE cart_id=?");
    $update->bind_param("ii", $new_qty, $row['cart_id']);
    $update->execute();
    $update->close();
} else {
    // Get product price
    $priceStmt = $conn->prepare("SELECT price FROM tbl_products WHERE product_id=?");
    $priceStmt->bind_param("i", $product_id);
    $priceStmt->execute();
    $priceRes = $priceStmt->get_result();
    $priceRow = $priceRes->fetch_assoc();
    $price = $priceRow['price'] ?? 0;
    $priceStmt->close();

    // Insert into cart
    $insert = $conn->prepare("INSERT INTO tbl_cart (user_id, product_id, quantity, unit_price) VALUES (?, ?, 1, ?)");
$insert->bind_param("iid", $user_id, $product_id, $price);
    $insert->execute();
    $insert->close();
}

$stmt->close();
$conn->close();

header("Location: cart.php");
exit();
?>
