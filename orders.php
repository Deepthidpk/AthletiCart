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

// Fetch all orders with product info
$sql = "SELECT o.order_id, o.payment_id, o.quantity, o.unit_price, o.created_at, p.product_name, p.product_image
        FROM tbl_orders o
        JOIN tbl_products p ON o.product_id = p.product_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders</title>
<style>
body { font-family: Arial, sans-serif; background:#f7f7f8; padding:30px; color:#222; }
.container { max-width: 900px; margin:auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
h1 { color:#333; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:12px; border-bottom:1px solid #eee; text-align:left; }
th { background:#fafafa; font-weight:700; }
.price { color:#1b7a3d; font-weight:bold; }
img { max-width:80px; border-radius:6px; }
.empty { text-align:center; padding:50px; color:#777; font-size:18px; }
/* Navbar */
.navbar {
    background-color: #2b2b52;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 40px;
}
.navbar h2 {
    font-size: 24px;
    color: #fff;
}
.navbar ul {
    list-style: none;
    display: flex;
    gap: 25px;
}
.navbar ul li {
    display: inline;
}
.navbar ul li a {
    color: white;
    text-decoration: none;
    font-weight: 500;
}
.navbar ul li a:hover {
    color: #00ccff;
}

</style>
</head>
<body>
     <!-- Navbar -->
<div class="navbar">
    <h2>🏏 AthletiCart</h2>
    <ul>
        <li><a href="userindex.php">Home</a></li>
         <li><a href="products.php">Products</a></li>
        <li><a href="cart.php">Cart</a></li>
        <li><a href="orders.php">Cart</a></li>
         <li><a href="logout.php">Logout</a></li>
       
    </ul>
</div>
<div class="container">
<h1>My Orders</h1>
<?php if(empty($orders)): ?>
<div class="empty">
<p>You have no orders yet.</p>
<p><a href="products.php" style="color:#28a745; text-decoration:none;">Shop Now</a></p>
</div>
<?php else: ?>
<table>
<thead>
<tr>
<th>Product image</th>
<th>Product</th>
<th>Qty</th>
<th>Unit Price</th>
<th>Total</th>
<th>Payment ID</th>
<th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach($orders as $o): ?>
<tr>
<td>

<?php 
$imgsrc = htmlspecialchars($o['product_image']);
if (!preg_match('#^(https?://|/)#', $imgsrc)) {
    $imgsrc = 'uploads/' . $imgsrc;
}
?>
<img src="<?php echo $imgsrc; ?>" alt="<?php echo htmlspecialchars($o['product_name']); ?>">

</td>
<td>
<?php echo htmlspecialchars($o['product_name']); ?>
</td>
<td><?php echo $o['quantity']; ?></td>
<td class="price">₹<?php echo number_format($o['unit_price'],2); ?></td>
<td class="price">₹<?php echo number_format($o['unit_price']*$o['quantity'],2); ?></td>
<td><?php echo htmlspecialchars($o['payment_id']); ?></td>

<td><?php echo date("d M Y, H:i", strtotime($o['created_at'])); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</body>
</html>
