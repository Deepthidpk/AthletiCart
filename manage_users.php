<?php
session_start();
require_once "connect.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "athleticart");
$result = $conn->query("SELECT * FROM tbl_login WHERE role='user'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f5f7fa; margin: 20px; }
table { width: 100%; border-collapse: collapse; background: white; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background-color: #2b2b52; color: white; }
.btn { border: none; padding: 6px 10px; border-radius: 5px; color: white; cursor: pointer; }
.btn-inactive { background-color: #e74c3c; }
.btn-active { background-color: #2ecc71; }
.msg { padding: 10px; background-color: #d1ecf1; color: #0c5460; border-radius: 5px; margin-bottom: 15px; }
</style>
</head>
<body>

<h2>Manage Users</h2>

<?php if(isset($_SESSION['msg'])): ?>
    <div class="msg"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th><th>Email</th><th>Status</th><th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['login_id']; ?></td>
        <td><?= htmlspecialchars($row['email']); ?></td>
        <td><?= ucfirst($row['status']); ?></td>
        <td>
            <?php if ($row['status'] === 'active'): ?>
                <a href="delete_user.php?id=<?= $row['login_id']; ?>" onclick="return confirm('Deactivate this user?');">
                    <button class="btn btn-inactive">Deactivate</button>
                </a>
            <?php else: ?>
                <a href="delete_user.php?id=<?= $row['login_id']; ?>" onclick="return confirm('Activate this user?');">
                    <button class="btn btn-active">Activate</button>
                </a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
