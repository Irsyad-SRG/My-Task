<?php
session_start();

// ✅ Only logged-in users can view articles
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

// ==================== DATABASE CONNECTION ====================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blog_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->select_db($dbname);

// ==================== FETCH ARTICLE ====================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<h2>❌ Invalid article ID.</h2>");
}

$id = intval($_GET['id']);

// ✅ Increment view count
$conn->query("UPDATE blog_posts SET views = views + 1 WHERE id = $id");

// Fetch the current article
$result = $conn->query("SELECT * FROM blog_posts WHERE id = $id");
if (!$result || $result->num_rows === 0) {
    die("<h2>❌ Article not found.</h2>");
}
$article = $result->fetch_assoc();

// ==================== NEXT / PREVIOUS ARTICLES ====================
$prevRes = $conn->query("SELECT id, title FROM blog_posts WHERE id < $id ORDER BY id DESC LIMIT 1");
$nextRes = $conn->query("SELECT id, title FROM blog_posts WHERE id > $id ORDER BY id ASC LIMIT 1");

$prevArticle = $prevRes && $prevRes->num_rows ? $prevRes->fetch_assoc() : null;
$nextArticle = $nextRes && $nextRes->num_rows ? $nextRes->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($article['title']) ?> - Blog Article</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f9;
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
.article-header {
    text-align: center;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
    padding-bottom: 15px;
}
.article-header h1 {
    margin: 0;
    font-size: 28px;
}
.meta {
    color: #666;
    font-size: 14px;
    margin-top: 10px;
}
.article-image {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 8px;
    margin: 20px 0;
}
.article-content {
    font-size: 16px;
    line-height: 1.7;
    color: #333;
}
a.btn {
    display: inline-block;
    margin-top: 25px;
    padding: 10px 18px;
    background: #007bff;
    color: white;
    border-radius: 6px;
    text-decoration: none;
}
a.btn:hover {
    background: #0056b3;
}
.nav-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
}
.nav-buttons a {
    flex: 1;
    text-align: center;
    padding: 12px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-weight: bold;
}
.prev-btn {
    background: #6c757d;
    margin-right: 10px;
}
.prev-btn:hover {
    background: #5a6268;
}
.next-btn {
    background: #28a745;
    margin-left: 10px;
}
.next-btn:hover {
    background: #218838;
}
.back-link {
    display: inline-block;
    margin-top: 30px;
    padding: 10px 18px;
    background: #17a2b8;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
}
.back-link:hover {
    background: #138496;
}
</style>
</head>
<body>

<div class="container">
  <div class="article-header">
    <h1><?= htmlspecialchars($article['title']) ?></h1>
    <div class="meta">
      🏷 <strong><?= htmlspecialchars($article['category']) ?></strong> |
      👤 <?= htmlspecialchars($article['author']) ?> |
      🕒 <?= date("F j, Y, g:i a", strtotime($article['created_at'])) ?> |
      👁 <?= (int)$article['views'] + 1 ?> views
    </div>
  </div>

  <?php if (!empty($article['image'])): ?>
    <img src="<?= htmlspecialchars($article['image']) ?>" alt="Article Image" class="article-image">
  <?php endif; ?>

  <div class="article-content">
    <?= $article['content'] ?>
  </div>

  <div class="nav-buttons">
    <?php if ($prevArticle): ?>
      <a class="prev-btn" href="view_article.php?id=<?= $prevArticle['id'] ?>">⬅ <?= htmlspecialchars($prevArticle['title']) ?></a>
    <?php else: ?>
      <div></div>
    <?php endif; ?>

    <?php if ($nextArticle): ?>
      <a class="next-btn" href="view_article.php?id=<?= $nextArticle['id'] ?>"><?= htmlspecialchars($nextArticle['title']) ?> ➡</a>
    <?php else: ?>
      <div></div>
    <?php endif; ?>
  </div>

  <?php if ($isAdmin): ?>
    <a href="blog_post.php" class="back-link">⬅ Back to Dashboard</a>
  <?php else: ?>
    <a href="blog_post.php" class="back-link">⬅ Back to Blog</a>
  <?php endif; ?>
</div>

</body>
</html>
