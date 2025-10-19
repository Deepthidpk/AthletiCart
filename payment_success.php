<?php
session_start();

$payment_id = $_GET['pid'] ?? '';
$amount = $_GET['amt'] ?? '';

if (!$payment_id || !$amount) {
    die("Invalid request");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Success</title>
    <meta http-equiv="refresh" content="3;url=orders.php">
</head>
<body>
<h2>Payment Successful ✅</h2>
<p>Payment ID: <?php echo htmlspecialchars($payment_id); ?></p>
<p>Amount Paid: ₹<?php echo number_format($amount, 2); ?></p>
<p>Redirecting to your orders...</p>
</body>
</html>
