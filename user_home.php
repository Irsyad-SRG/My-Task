<?php
session_start();

// ✅ Must be logged in to access
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// ✅ Connect to database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blog_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// ✅ Fetch all posts
$searchQuery = "";
if (!empty($_GET['search'])) {
    $keyword = $conn->real_escape_string($_GET['search']);
    $searchQuery = "WHERE title LIKE '%$keyword%' OR content LIKE '%$keyword%'";
}

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM blog_posts $searchQuery");
$totalPosts = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalPosts / $limit);

$posts = $conn->query("SELECT * FROM blog_posts $searchQuery ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard | Blog</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #f4f4f4;
  margin: 40px;
}
.container {
  max-width: 900px;
  margin: auto;
  background: white;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}
button {
  background: #007BFF;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
button:hover { background: #0056b3; }
.post {
  border-bottom: 1px solid #ddd;
  padding: 15px 0;
}
.post img {
  width: 100%;
  max-height: 300px;
  object-fit: cover;
  border-radius: 8px;
  margin-top: 10px;
}
.pagination {
  margin-top: 20px;
  text-align: center;
}
.pagination a {
  margin: 0 5px;
  padding: 8px 12px;
  background: #007BFF;
  color: white;
  border-radius: 4px;
  text-decoration: none;
}
.pagination .current { background: #333; }
.search-box {
  margin-bottom: 20px;
  text-align: right;
}
.search-box input {
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h2>👋 Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
    <form method="GET" action="login.php">
      <button type="submit" name="logout" value="1">🚪 Logout</button>
    </form>
  </div>

  <div class="search-box">
    <form method="GET">
      <input type="search" name="search" placeholder="Search articles..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button>🔍</button>
    </form>
  </div>

  <?php if ($totalPosts == 0): ?>
    <p>No articles found.</p>
  <?php else: ?>
    <?php while ($post = $posts->fetch_assoc()): ?>
      <div class="post">
        <h3><?= htmlspecialchars($post['title']) ?></h3>
        <small><b>Category:</b> <?= htmlspecialchars($post['category']) ?> | <b>Date:</b> <?= $post['created_at'] ?></small>
        <?php if ($post['image']): ?>
          <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image">
        <?php endif; ?>
        <p><?= nl2br(substr(strip_tags($post['content']), 0, 250)) ?>...</p>
        <a href="view_article.php?id=<?= $post['id'] ?>">📰 Read More</a>
      </div>
    <?php endwhile; ?>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>">⬅ Prev</a><?php endif; ?>
      <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= $i==$page?'current':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>">Next ➡</a><?php endif; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
