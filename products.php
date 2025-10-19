<?php
session_start();
require_once "connect.php"; // Database connection
$db = new Database();
$database = $db->connect();
// ===============================
// Class: ProductManager
// ===============================
class ProductManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fetch all categories (no status column now)
    public function getCategories() {
        $sql = "SELECT category_id, category_name FROM tbl_category ORDER BY category_name ASC";
        $result = $this->conn->query($sql);
        $categories = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    // Fetch products by category (only active products)
    public function getProductsByCategory($category_id) {
        $stmt = $this->conn->prepare("SELECT product_id, product_name, pro_description, price, product_image 
                                      FROM tbl_products 
                                      WHERE category_id = ? ");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        $stmt->close();
        return $products;
    }
}



$productManager = new ProductManager($database);
$categories = $productManager->getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
.container { margin-top: 40px; }
.category-title { font-size: 28px; font-weight: 600; color: #333; margin: 40px 0 20px; border-bottom: 3px solid #007bff; display: inline-block; padding-bottom: 5px; }
.card { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: 0.3s; }
.card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
.card-img-top { height: 220px; object-fit: cover; border-radius: 15px 15px 0 0; }
.card-body { padding: 20px; text-align: center; }
.card-title { font-size: 20px; font-weight: 600; color: #333; }
.card-text { font-size: 15px; color: #666; height: 60px; overflow: hidden; }
.price { color: #007bff; font-weight: 600; font-size: 18px; }
.btn-add { background-color: #007bff; color: white; border-radius: 25px; padding: 8px 20px; transition: 0.3s; text-decoration: none; }
.btn-add:hover { background-color: #0056b3; color: white; }
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
    <h1 class="text-center mb-4">Our Products</h1>

    <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $cat): ?>
            <h2 class="category-title"><?= htmlspecialchars($cat['category_name']); ?></h2>
            <?php
            $products = $productManager->getProductsByCategory($cat['category_id']);
            ?>
            <?php if (!empty($products)): ?>
                <div class="row">
                    <?php foreach ($products as $row): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card">
                                <img src="uploads/<?= htmlspecialchars($row['product_image']); ?>" class="card-img-top" alt="<?= htmlspecialchars($row['product_name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($row['product_name']); ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($row['pro_description']); ?></p>
                                    <p class="price">₹<?= htmlspecialchars($row['price']); ?></p>
                                    <a href="add_to_cart.php?product_id=<?= $row['product_id']; ?>" class="btn btn-add">Add to Cart</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted ms-2">No products found in this category.</p>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-danger">No categories available in database.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
