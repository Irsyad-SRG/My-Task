<?php
session_start();

// ✅ Optional: allow browsing without login
// If you want only logged-in users to see it, uncomment below:
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

// ==================== DATABASE CONNECTION ====================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blog_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->select_db($dbname);

// ==================== FETCH ARTICLES ====================
$result = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Blog Articles</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 1000px;
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
    margin-bottom: 25px;
}
header h2 {
    margin: 0;
}
a.btn {
    background: #007bff;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
}
a.btn:hover {
    background: #0056b3;
}
.posts {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.post-card {
    background: #fafafa;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.post-card:hover {
    transform: translateY(-4px);
}
.post-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}
.post-content {
    padding: 15px;
}
.post-content h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}
.post-content p {
    font-size: 14px;
    color: #555;
}
.meta {
    font-size: 12px;
    color: #888;
    margin-top: 8px;
}
footer {
    text-align: center;
    margin-top: 30px;
    font-size: 13px;
    color: #666;
}
</style>
</head>
<body>

<div class="container">
  <header>
    <h2>📰 All Blog Articles</h2>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
      <div>
        Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> 
        (<?= htmlspecialchars($_SESSION['role'] ?? 'user') ?>)
        | <a href="login.php?logout=1" class="btn" style="background:#dc3545;">Logout</a>
      </div>
    <?php else: ?>
      <a href="login.php" class="btn">Login</a>
    <?php endif; ?>
  </header>

  <?php if ($result->num_rows > 0): ?>
    <div class="posts">
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="post-card">
          <img src="<?= htmlspecialchars($row['image'] ?: 'https://via.placeholder.com/300x180?text=No+Image') ?>" alt="Image">
          <div class="post-content">
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <p><?= htmlspecialchars(substr(strip_tags($row['content']), 0, 100)) ?>...</p>
            <div class="meta">
              🏷 <?= htmlspecialchars($row['category']) ?> | 👤 <?= htmlspecialchars($row['author']) ?> | 👁 <?= (int)$row['views'] ?> views
            </div>
            <a href="view_article.php?id=<?= $row['id'] ?>" class="btn" style="margin-top:10px; display:inline-block;">Read More</a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p>No articles published yet.</p>
  <?php endif; ?>

  <footer>
    &copy; <?= date("Y") ?> Your Blog Project | Powered by PHP + MySQL
  </footer>
</div>

</body>
</html>
