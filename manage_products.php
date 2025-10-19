<?php
session_start();
require_once "connect.php";

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Ensure DB connection
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli("localhost", "root", "", "athleticart");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
}


/* ===========================
   CATEGORY MANAGEMENT
=========================== */
if (isset($_POST['add_category']) && isset($_POST['category_name'])) {
    header('Content-Type: application/json');
    $category_name = trim($_POST['category_name']);
    $response = ['success' => false, 'message' => ''];

    if ($category_name !== "") {
        // Prevent duplicate category names
        $stmtCheck = $conn->prepare("SELECT * FROM tbl_category WHERE category_name=?");
        $stmtCheck->bind_param("s", $category_name);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($resCheck->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO tbl_category (category_name) VALUES (?)");
            $stmt->bind_param("s", $category_name);
            if($stmt->execute()) {
                $response['success'] = true;
                $response['category_id'] = $conn->insert_id;
                $response['category_name'] = $category_name;
            } else {
                $response['message'] = 'Failed to insert category';
            }
        } else {
            $response['message'] = 'Category already exists';
        }
    } else {
        $response['message'] = 'Category name cannot be empty';
    }

    echo json_encode($response);
    exit;
}


/* ===========================
   PRODUCT MANAGEMENT CLASS
=========================== */
class ProductManager {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function getAllProducts() {
        return $this->conn->query("SELECT p.*, c.category_name 
                                   FROM tbl_products p 
                                   LEFT JOIN tbl_category c ON p.category_id = c.category_id");
    }

   public function addProduct($name, $desc, $qty, $price, $image, $cat) {
    $stmt = $this->conn->prepare("INSERT INTO tbl_products 
        (product_name, pro_description, quantity, price, product_image, category_id) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidsi", $name, $desc, $qty, $price, $image, $cat);
    return $stmt->execute();
}


    public function deleteProduct($id) {
        $stmt = $this->conn->prepare("DELETE FROM tbl_products WHERE product_id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}

$productObj = new ProductManager($conn);

/* ===========================
   ADD PRODUCT
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['product_name'];
    $desc = $_POST['pro_description'];
    $qty = $_POST['quantity'];
    $price = $_POST['price'];
    $cat = $_POST['category_id'];
    $imageName = "";

    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $imageName = time() . "_" . basename($_FILES['product_image']['name']);
        move_uploaded_file($_FILES['product_image']['tmp_name'], $targetDir . $imageName);
    }

    if ($productObj->addProduct($name, $desc, $qty, $price, $imageName, $cat)) {
        echo "<script>alert('Product added successfully!'); window.location='manage_products.php';</script>";
    } else {
        echo "<script>alert('Failed to add product');</script>";
    }
}

/* ===========================
   DELETE PRODUCT
=========================== */
if (isset($_GET['delete_product'])) {
    $productObj->deleteProduct($_GET['delete_product']);
    header("Location: manage_products.php");
    exit();
}

/* ===========================
   FETCH DATA
=========================== */
$products = $productObj->getAllProducts();
$categories = $conn->query("SELECT * FROM tbl_category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Products</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<style>
/* Styles unchanged from your previous code */
body { font-family: 'Poppins', sans-serif; margin: 0; display: flex; background-color: #f5f7fa; }
.sidebar { width: 250px; background-color: #2b2b52; color: #fff; height: 100vh; padding: 20px; position: fixed; }
.sidebar h3 { text-align: center; color: #00ccff; margin-bottom: 20px; }
.sidebar a { display: block; color: #fff; text-decoration: none; padding: 10px 0; margin: 10px 0; }
.sidebar a:hover { background-color: #00ccff; border-radius: 5px; padding-left: 10px; }
.main { margin-left: 270px; padding: 20px; width: calc(100% - 270px); }
h1 { color: #2b2b52; }
form { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input, select, textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; }
button { background-color: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
button:hover { background-color: #219150; }
.error { color: #e74c3c; font-size: 13px; margin-top: -5px; margin-bottom: 8px; display: block; }
.input-error { border-color: #e74c3c !important; }
.add-cat-btn { background-color: #2980b9; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; margin-left: 10px; }
.add-cat-btn:hover { background-color: #2471a3; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
.modal-content { background: white; padding: 20px; border-radius: 10px; width: 350px; text-align: center; }
.modal input { width: 100%; margin-bottom: 10px; }
.modal button { margin: 5px; }
table { width: 100%; border-collapse: collapse; background-color: white; }
table, th, td { border: 1px solid #ddd; }
th, td { padding: 10px; text-align: center; }
th { background-color: #2b2b52; color: white; }
.btn-delete { background-color: #e74c3c; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer; }
.btn-delete:hover { background-color: #c0392b; }
</style>
</head>
<body>

<div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="admindashboard.php"><i class="fa fa-home"></i> Dashboard</a>
    <a href="manage_users.php"><i class="fa fa-home"></i> Manage Users</a>
    <a href="manage_products.php"><i class="fa fa-box"></i> Manage Products</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h1>Manage Products</h1>

    <!-- Add Product Form -->
    <form id="productForm" method="POST" enctype="multipart/form-data" novalidate>
        <h2>Add Product</h2>
        <label for="product_name">Product Name:</label>
        <input type="text" name="product_name" id="product_name">
        <label for="pro_description">Description:</label>
        <textarea name="pro_description" id="pro_description" rows="3"></textarea>
        <label for="quantity">Quantity:</label>
        <input type="number" name="quantity" id="quantity" min="1">
        <label for="price">Price (₹):</label>
        <input type="number" step="0.01" name="price" id="price" min="1">
        <label for="product_image">Product Image:</label>
        <input type="file" name="product_image" id="product_image" accept="image/*">
        <label for="category_id">Category:</label>
        <div style="display:flex; align-items:center;">
            <select name="category_id" id="category_id" style="flex:1;">
                <option value="">Select Category</option>
                <?php 
                $categories->data_seek(0);
                while($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
                <?php endwhile; ?>
            </select>
            <button type="button" class="add-cat-btn" id="openCategoryModal">+ Add Category</button>
        </div>
        <button type="submit" name="add_product">Add Product</button>
    </form>

    <!-- Products Table -->
    <h2>Existing Products</h2>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Quantity</th><th>Price</th><th>Category</th><th>Image</th><th>Action</th>
        </tr>
        <?php while($row = $products->fetch_assoc()): ?>
        <tr>
            <td><?= $row['product_id']; ?></td>
            <td><?= htmlspecialchars($row['product_name']); ?></td>
            <td><?= htmlspecialchars($row['quantity']); ?></td>
            <td>₹<?= htmlspecialchars($row['price']); ?></td>
            <td><?= htmlspecialchars($row['category_name']); ?></td>
            <td><img src="uploads/<?= htmlspecialchars($row['product_image']); ?>" width="60"></td>
            <td>
                <a href="?delete_product=<?= $row['product_id']; ?>" onclick="return confirm('Delete this product?');">
                    <button class="btn-delete">Delete</button>
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Category Modal -->
<div class="modal" id="categoryModal">
    <div class="modal-content">
        <h3>Add New Category</h3>
        <input type="text" id="new_category_name" placeholder="Enter category name">
        <br>
        <button id="saveCategory">Save</button>
        <button id="closeCategoryModal">Cancel</button>
    </div>
</div>

<script>
$(document).ready(function() {
    // Open & close modal
    $("#openCategoryModal").click(function(){ $("#categoryModal").fadeIn(); });
    $("#closeCategoryModal").click(function(){ $("#categoryModal").fadeOut(); });

    // Add new category via AJAX without reload
    $("#saveCategory").click(function(){
        var catName = $("#new_category_name").val().trim();
        if(catName === "") { alert("Please enter a category name"); return; }
        $.ajax({
            url: 'manage_products.php',
            type: 'POST',
            data: {add_category: true, category_name: catName},
            dataType: 'json',
            success: function(response){
                if(response.success) {
                    // Add new option to select box
                    $("#category_id").append('<option value="'+response.category_id+'" selected>'+response.category_name+'</option>');
                    alert('Category added successfully!');
                    $("#categoryModal").fadeOut();
                    $("#new_category_name").val('');
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Error adding category');
            }
        });
    });

    // Validation
    $("#productForm").validate({
        ignore: [],
        errorElement: "div",
        errorClass: "error",
        rules: {
            product_name: { required: true },
            pro_description: { required: true },
            quantity: { required: true, number: true, min: 1 },
            price: { required: true, number: true, min: 1 },
            product_image: { required: true, extension: "jpg|jpeg|png|gif" },
            category_id: { required: true }
        },
        messages: {
            product_name: "Please enter product name",
            pro_description: "Please enter product description",
            quantity: { required: "Please enter quantity", number: "Quantity must be a number", min: "Minimum quantity is 1" },
            price: { required: "Please enter price", number: "Price must be a number", min: "Minimum price is 1" },
            product_image: { required: "Please upload an image", extension: "Allowed file types: jpg, jpeg, png, gif" },
            category_id: "Please select a category"
        },
        errorPlacement: function(error, element) { error.insertAfter(element); },
        highlight: function(element) { $(element).addClass("input-error"); },
        unhighlight: function(element) { $(element).removeClass("input-error"); },
        onkeyup: function(element) { $(element).valid(); },
        onfocusout: function(element) { $(element).valid(); },
        submitHandler: function(form) { form.submit(); }
    });
});
</script>

</body>
</html>
