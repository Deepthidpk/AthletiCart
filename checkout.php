<?php
session_start();
require_once "connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = new Database();
$conn = $db->connect();

// Fetch active cart items
$sql = "SELECT c.cart_id, c.product_id, c.quantity, c.unit_price, p.product_name 
        FROM tbl_cart c 
        JOIN tbl_products p ON c.product_id = p.product_id 
        WHERE c.user_id = ? AND c.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += $row['quantity'] * $row['unit_price'];
}

if (empty($cart_items)) {
    echo "<h2>Your cart is empty. <a href='products.php'>Shop Now</a></h2>";
    exit();
}

// Razorpay Key
$razorpayKey = "rzp_test_RVGdzeQ7cuMtJ1";
$amountPaise = $total * 100;
$orderId = "ORDER_" . time();

// Check if Razorpay returned payment_id (form submission)
if (isset($_POST['razorpay_payment_id'])) {
    $payment_id = $_POST['razorpay_payment_id'];

    // Insert payment
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

    // Redirect to payment success page
    header("Location: payment_success.php?pid=$payment_id&amt=$total");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout - AthletiCart</title>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<style>
body { font-family: Arial, sans-serif; background: #f7f7f8; color: #222; padding: 30px; }
.container { background: #fff; border-radius: 10px; padding: 25px; max-width: 700px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
th { background: #f9f9f9; }
.price { color: #1b7a3d; font-weight: bold; }
.btn { display: inline-block; padding: 10px 18px; border: none; border-radius: 6px; background: #28a745; color: #fff; font-weight: bold; cursor: pointer; font-size: 16px; }
.total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 15px; }
</style>
</head>
<body>
<div class="container">
<h2>Checkout Summary</h2>
<form method="POST" id="checkoutForm">
<table>
<thead>
<tr>
<th>Product</th>
<th>Qty</th>
<th>Price</th>
<th>Total</th>
</tr>
</thead>
<tbody>
<?php foreach ($cart_items as $item): ?>
<tr>
<td><?php echo htmlspecialchars($item['product_name']); ?></td>
<td><?php echo intval($item['quantity']); ?></td>
<td>₹<?php echo number_format($item['unit_price'], 2); ?></td>
<td class="price">₹<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="total">Grand Total: ₹<?php echo number_format($total, 2); ?></div>
<br>
<input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
<button type="button" id="rzp-button" class="btn">Pay Now</button>
</form>
</div>

<script>
var options = {
    "key": "<?php echo $razorpayKey; ?>",
    "amount": "<?php echo $amountPaise; ?>",
    "currency": "INR",
    "name": "AthletiCart",
    "description": "<?php echo $orderId; ?>",
    "handler": function (response){
        // Put payment ID in hidden field and submit form
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('checkoutForm').submit();
    },
    "prefill": {
        "name": "<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>",
        "email": "<?php echo htmlspecialchars($_SESSION['email'] ?? 'user@example.com'); ?>"
    },
    "theme": { "color": "#28a745" }
};

document.getElementById('rzp-button').onclick = function(e){
    var rzp = new Razorpay(options);
    rzp.open();
    e.preventDefault();
}
</script>
</body>
</html>
