<?php
session_start();

// ✅ Require admin login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ==================== DATABASE CONNECTION ====================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blog_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->select_db($dbname);

// ✅ Ensure users table has role column
$conn->query("
ALTER TABLE users
ADD COLUMN IF NOT EXISTS role ENUM('admin','user') DEFAULT 'user';
");

// ==================== ACTIONS ====================
if (isset($_GET['promote'])) {
    $id = intval($_GET['promote']);
    $conn->query("UPDATE users SET role='admin' WHERE id=$id");
    header("Location: manage_users.php");
    exit;
}
if (isset($_GET['demote'])) {
    $id = intval($_GET['demote']);
    $conn->query("UPDATE users SET role='user' WHERE id=$id");
    header("Location: manage_users.php");
    exit;
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Prevent deleting yourself
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id=$id");
    }
    header("Location: manage_users.php");
    exit;
}

// ==================== FETCH USERS ====================
$users = $conn->query("SELECT id, username, email, role FROM users ORDER BY role DESC, username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | Admin</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
header h2 {
    margin: 0;
}
a.btn {
    background: #007bff;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
}
a.btn:hover { background: #0056b3; }
a.danger { background: #dc3545; }
a.danger:hover { background: #b02a37; }
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}
th {
    background: #007bff;
    color: white;
}
tr:nth-child(even) { background: #f9f9f9; }
footer {
    text-align: center;
    margin-top: 25px;
    font-size: 13px;
    color: #666;
}
</style>
</head>
<body>
<div class="container">
<header>
  <h2>👥 Manage Users</h2>
  <div>
    Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (Admin)
    | <a href="blog_post.php" class="btn">🏠 Dashboard</a>
    | <a href="login.php?logout=1" class="btn danger">Logout</a>
  </div>
</header>

<table>
  <tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Role</th>
    <th>Actions</th>
  </tr>
  <?php while ($u = $users->fetch_assoc()): ?>
  <tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= htmlspecialchars($u['role']) ?></td>
    <td>
      <?php if ($u['id'] != $_SESSION['user_id']): ?>
        <?php if ($u['role'] === 'user'): ?>
          <a href="?promote=<?= $u['id'] ?>" class="btn">Promote</a>
        <?php else: ?>
          <a href="?demote=<?= $u['id'] ?>" class="btn">Demote</a>
        <?php endif; ?>
        <a href="?delete=<?= $u['id'] ?>" class="btn danger" onclick="return confirm('Delete this user?')">Delete</a>
      <?php else: ?>
        <em>(You)</em>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>

<footer>
  &copy; <?= date('Y') ?> Blog Admin System — Manage Users
</footer>
</div>
</body>
</html>
