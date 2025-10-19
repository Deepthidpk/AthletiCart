<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "connect.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'Not logged in']);
    exit;
}

if (!isset($_POST['payment_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'Payment ID missing']);
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_id = trim($_POST['payment_id']);

$db = new Database();
$conn = $db->connect();

// Fetch active cart items
$stmt = $conn->prepare("SELECT * FROM tbl_cart WHERE user_id=? AND status='active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$cart_items) {
    echo json_encode(['status'=>'error', 'message'=>'Cart is empty']);
    exit;
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['unit_price'] * $item['quantity'];
}

// Insert payment record
$stmt = $conn->prepare("INSERT INTO tbl_payments (user_id, payment_id, amount, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("isd", $user_id, $payment_id, $total);
$stmt->execute();
$stmt->close();

// Insert orders
$order_stmt = $conn->prepare("INSERT INTO tbl_orders (user_id, product_id, quantity, unit_price, payment_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
foreach ($cart_items as $item) {
    $order_stmt->bind_param("iiids", $user_id, $item['product_id'], $item['quantity'], $item['unit_price'], $payment_id);
    $order_stmt->execute();
}
$order_stmt->close();

// Mark cart items inactive
$update_cart = $conn->prepare("UPDATE tbl_cart SET status='inactive' WHERE user_id=? AND status='active'");
$update_cart->bind_param("i", $user_id);
$update_cart->execute();
$update_cart->close();

// Return success JSON
echo json_encode(['status'=>'success', 'payment_id'=>$payment_id, 'amount'=>$total]);
exit;
