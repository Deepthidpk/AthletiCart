# FILE 1: add_to_cart.php
<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit();
    }
    
    // Get product details
    $product_query = "SELECT price FROM tbl_products WHERE product_id = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        $stmt->close();
        exit();
    }
    
    $product = $result->fetch_assoc();
    $unit_price = $product['price'];
    $stmt->close();
    
    // Check if product already exists in user's cart
    $check_query = "SELECT cart_id, quantity FROM tbl_cart WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update quantity if already in cart
        $cart_item = $check_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        $update_query = "UPDATE tbl_cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product quantity updated in cart']);
            $update_stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
            $update_stmt->close();
        }
    } else {
        // Insert new product in cart
        $insert_query = "INSERT INTO tbl_cart (user_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiid", $user_id, $product_id, $quantity, $unit_price);
        
        if ($insert_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product added to cart']);
            $insert_stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
            $insert_stmt->close();
        }
    }
    
    $check_stmt->close();
    $conn->close();
    exit();
}

// If not POST request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>