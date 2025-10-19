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

/*
 * ---------- AJAX handlers ----------
 * handle update quantity and delete (make inactive by setting quantity = 0)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'update_quantity') {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        if ($quantity < 0) $quantity = 0;

        // Update quantity for that cart row (only if belongs to this user)
        $uq = "UPDATE tbl_cart SET quantity = ? WHERE cart_id = ? AND user_id = ?";
        $ust = $conn->prepare($uq);
        $ust->bind_param("iii", $quantity, $cart_id, $user_id);
        $ust->execute();
        $ust->close();

        // respond with updated subtotal for this item and new total
        $s1 = $conn->prepare("SELECT quantity, unit_price FROM tbl_cart WHERE cart_id = ? AND user_id = ?");
        $s1->bind_param("ii", $cart_id, $user_id);
        $s1->execute();
        $r1 = $s1->get_result()->fetch_assoc();
        $s1->close();

        $item_subtotal = 0;
        if ($r1 && $r1['quantity'] > 0) {
            $item_subtotal = floatval($r1['quantity']) * floatval($r1['unit_price']);
        }

        $st = $conn->prepare("SELECT IFNULL(SUM(quantity * unit_price),0) AS total FROM tbl_cart WHERE user_id = ?");
        $st->bind_param("i", $user_id);
        $st->execute();
        $totrow = $st->get_result()->fetch_assoc();
        $st->close();

        $total = floatval($totrow['total']);

        echo json_encode([
            'success' => true,
            'cart_id' => $cart_id,
            'item_subtotal' => number_format($item_subtotal, 2, '.', ''),
            'total' => number_format($total, 2, '.', '')
        ]);
        exit();
    }

    if ($action === 'remove_item') {
        $cart_id = intval($_POST['cart_id']);

        // Make inactive by setting quantity = 0 (so it will not be selected/displayed)
        $dq = "UPDATE tbl_cart SET status = 'inactive' WHERE cart_id = ? AND user_id = ?";
        $dst = $conn->prepare($dq);
        $dst->bind_param("ii", $cart_id, $user_id);
        $dst->execute();
        $dst->close();

        $st = $conn->prepare("SELECT IFNULL(SUM(quantity * unit_price),0) AS total FROM tbl_cart WHERE user_id = ?");
        $st->bind_param("i", $user_id);
        $st->execute();
        $totrow = $st->get_result()->fetch_assoc();
        $st->close();

        $total = floatval($totrow['total']);

        echo json_encode([
            'success' => true,
            'cart_id' => $cart_id,
            'total' => number_format($total, 2, '.', '')
        ]);
        exit();
    }

    if ($action === 'clear_cart') {
        $cq = "UPDATE tbl_cart SET quantity = 0 WHERE user_id = ?";
        $cst = $conn->prepare($cq);
        $cst->bind_param("i", $user_id);
        $cst->execute();
        $cst->close();
        echo json_encode(['success' => true]);
        exit();
    }

    // unknown action
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

/*
 * ---------- Fetch cart items ----------
 * Note: we filter out items with quantity <= 0 so 'deleted' items are hidden
 */
$query = "SELECT c.cart_id, c.quantity, c.unit_price, p.product_name, p.product_image,c.status
          FROM tbl_cart c
          JOIN tbl_products p ON c.product_id = p.product_id
          WHERE c.user_id = ? AND c.quantity > 0 AND c.status='active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate grand total for displayed items
$grand_total = 0;
foreach ($cart_items as $item) {
    $total = $item['quantity'] * $item['unit_price'];
    $grand_total += $total;
}

// Do not close $conn here because AJAX handlers earlier might use it in this same request flow
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - AthleticArt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background:#f7f7f8; color:#222; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; vertical-align: middle; }
        th { background: #fafafa; color: #333; font-weight: 700; }
        img { max-width: 100px; height: auto; border-radius:6px; }
        .qty-control { display:flex; align-items:center; gap:8px; }
        .qty-control button { width:34px; height:34px; border-radius:5px; border:1px solid #ddd; background:#fff; cursor:pointer; font-size:18px; }
        .qty-control input { width:60px; text-align:center; padding:6px; border:1px solid #ddd; border-radius:4px; }
        .price { color:#1b7a3d; font-weight:700; }
        .remove-btn { background:#e74c3c; color:#fff; padding:8px 12px; border:none; border-radius:6px; cursor:pointer; }
        .actions { display:flex; justify-content:flex-end; gap:12px; margin-top:18px; }
        .btn { padding:10px 18px; border-radius:6px; border:none; cursor:pointer; font-weight:600; }
        .btn-primary { background:#28a745; color:#fff; }
        .btn-secondary { background:#6c757d; color:#fff; }
        .empty { text-align:center; padding:80px 20px; color:#777; }
        .summary { text-align:right; margin-top:16px; font-size:18px; font-weight:700; color:#1b7a3d; }
        .small-muted { color:#666; font-size:13px; }
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
         <li><a href="orders.php">Orders</a></li>
         <li><a href="logout.php">Logout</a></li>
       
    </ul>
</div>
    <div class="container">
        <h1>Your Shopping Cart</h1>
         <a href="products.php">Continue shopping</a></div>

        <?php if (empty($cart_items)): ?>
            <div class="empty">
                <p>Your cart is empty.</p>
                <p><a href="products.php" class="btn btn-primary">Browse Products</a></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:45%;">Product</th>
                        <th style="width:15%;">Unit Price</th>
                        <th style="width:20%;">Quantity</th>
                        <th style="width:10%;">Total</th>
                        <th style="width:10%;">Action</th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <?php foreach ($cart_items as $item): 
                        $item_total = $item['quantity'] * $item['unit_price'];
                    ?>
                        <tr id="row-<?php echo $item['cart_id']; ?>" data-cart-id="<?php echo $item['cart_id']; ?>">
                            <td>
                                <div style="display:flex; gap:12px; align-items:center;">
                                    <?php
                                        // If stored product_image is full path or only filename: try to display sensibly
                                        $imgsrc = htmlspecialchars($item['product_image']);
                                        // If image stored without folder, assume uploads/
                                        if (!preg_match('#^https?://#', $imgsrc) && !str_starts_with($imgsrc, '/') ) {
                                            $imgsrc = 'uploads/' . $imgsrc;
                                        }
                                    ?>
                                    <img src="<?php echo $imgsrc; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <div>
                                        <div style="font-weight:700;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                       
                                    </div>
                                </div>
                            </td>
                            <td class="price">₹<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>
                                <div class="qty-control">
                                    <button type="button" onclick="changeQty(<?php echo $item['cart_id']; ?>, -1)">−</button>
                                    <input type="number" min="1" id="qty-<?php echo $item['cart_id']; ?>" value="<?php echo intval($item['quantity']); ?>" 
                                           onchange="setQty(<?php echo $item['cart_id']; ?>, this.value)">
                                    <button type="button" onclick="changeQty(<?php echo $item['cart_id']; ?>, 1)">+</button>
                                </div>
                            </td>
                            <td id="subtotal-<?php echo $item['cart_id']; ?>" class="price">₹<?php echo number_format($item_total, 2); ?></td>
                            <td>
                                <button class="remove-btn" onclick="removeItem(<?php echo $item['cart_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary">
                Cart Total: <span id="grand-total">₹<?php echo number_format($grand_total, 2); ?></span>
            </div>

            <div class="actions">
                <button class="btn btn-secondary" onclick="clearCart()">Clear Cart</button>
                <button class="btn btn-primary" onclick="window.location.href='checkout.php?total=<?php echo urlencode(number_format($grand_total,2,'.','')); ?>'">Proceed to Checkout</button>
            </div>
        <?php endif; ?>
    </div>

<script>
/**
 * Helpers that call the PHP AJAX handlers in this same file (POST to cart.php)
 * updateQuantity: sends new qty and updates UI
 * removeItem: sets quantity = 0 server-side and removes row client-side
 * clearCart: sets all quantities to 0
 */

// send AJAX helper
function postAction(data) {
    return fetch('cart.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: new URLSearchParams(data)
    }).then(r => r.json());
}

// increment/decrement
function changeQty(cartId, delta) {
    const input = document.getElementById('qty-' + cartId);
    let val = parseInt(input.value) || 0;
    val += delta;
    if (val < 1) val = 1;
    input.value = val;
    updateQuantityOnServer(cartId, val);
}

// set quantity via typed input
function setQty(cartId, value) {
    let val = parseInt(value) || 1;
    if (val < 1) val = 1;
    document.getElementById('qty-' + cartId).value = val;
    updateQuantityOnServer(cartId, val);
}

// call server and update UI
function updateQuantityOnServer(cartId, qty) {
    document.getElementById('grand-total').textContent = 'Updating...';
    postAction({ action: 'update_quantity', cart_id: cartId, quantity: qty })
    .then(function(resp) {
        if (resp.success) {
            // update item subtotal and grand total
            const subElem = document.getElementById('subtotal-' + cartId);
            if (subElem) subElem.textContent = '₹' + parseFloat(resp.item_subtotal).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('grand-total').textContent = '₹' + parseFloat(resp.total).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            alert('Failed to update quantity');
            location.reload();
        }
    }).catch(function(e){
        console.error(e);
        alert('Error updating quantity');
        location.reload();
    });
}

// remove (make inactive by setting quantity = 0)
function removeItem(cartId) {
    if (!confirm('Remove this item from cart?')) return;
    postAction({ action: 'remove_item', cart_id: cartId })
    .then(function(resp) {
        if (resp.success) {
            // remove row from DOM
            const row = document.getElementById('row-' + cartId);
            if (row) row.remove();

            // update grand total
            document.getElementById('grand-total').textContent = '₹' + parseFloat(resp.total).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // if no rows left, reload to show empty message
            if (!document.querySelectorAll('#cart-body tr').length) {
                location.reload();
            }
        } else {
            alert('Failed to remove item');
        }
    }).catch(function(e){
        console.error(e);
        alert('Error removing item');
    });
}

// clear entire cart (make all items inactive)
function clearCart() {
    if (!confirm('Clear entire cart?')) return;
    postAction({ action: 'clear_cart' })
    .then(function(resp) {
        if (resp.success) location.reload();
        else alert('Failed to clear cart');
    }).catch(function(e) {
        console.error(e);
        alert('Error clearing cart');
    });
}
</script>
</body>
</html>
