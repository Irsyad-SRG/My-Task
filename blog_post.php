<?php
session_start();

// ==================== LOGIN CHECK ====================
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($_SESSION['role'] === 'admin');

// ==================== DATABASE ====================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blog_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->select_db($dbname);

// ✅ Auto-create blog_posts table if missing
$conn->query("
CREATE TABLE IF NOT EXISTS blog_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(100) DEFAULT 'Uncategorized',
  content TEXT NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  author VARCHAR(100) DEFAULT 'Unknown',
  views INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
");

// ==================== FILE UPLOAD ====================
if (isset($_GET['upload_image'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file']['tmp_name'];
        $filename = time() . '_' . basename($_FILES['file']['name']);
        $target = $uploadDir . $filename;

        if (move_uploaded_file($tmp, $target)) {
            echo json_encode(['location' => 'uploads/' . $filename]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Upload failed']);
        }
    }
    exit;
}

// ==================== CRUD (Admins Only) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $content = $conn->real_escape_string($_POST['content']);
    $author = $_SESSION['username'];
    $imagePath = $_POST['existing_image'] ?? '';

    if (!empty($_FILES['main_image']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $tmp = $_FILES['main_image']['tmp_name'];
        $filename = time() . '_' . basename($_FILES['main_image']['name']);
        $target = $uploadDir . $filename;
        if (move_uploaded_file($tmp, $target)) $imagePath = 'uploads/' . $filename;
    }

    $conn->query("INSERT INTO blog_posts (title, category, content, image, author)
                  VALUES ('$title', '$category', '$content', '$imagePath', '$author')");
    header("Location: blog_post.php");
    exit;
}

if (isset($_GET['delete']) && $isAdmin) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM blog_posts WHERE id=$id");
    header("Location: blog_post.php");
    exit;
}

// ==================== FETCH POSTS ====================
$posts = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Blog Dashboard</title>
<script src="https://cdn.tiny.cloud/1/1g0vczbfqhq23n6qlw45yric64z5e55fjcxeaymbrstdagly/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<style>
body {
  font-family: 'Segoe UI', Arial, sans-serif;
  background: #f4f6f8;
  margin: 40px;
  color: #333;
}
.container {
  max-width: 950px;
  margin: auto;
  background: #fff;
  padding: 25px 35px;
  border-radius: 10px;
  box-shadow: 0 3px 12px rgba(0,0,0,0.1);
}
header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:20px;
  flex-wrap: wrap;
  gap: 10px;
}
button, a.btn {
  background:#007bff;
  color:white;
  border:none;
  padding:8px 14px;
  border-radius:5px;
  text-decoration:none;
  cursor:pointer;
  font-size:14px;
}
button:hover, a.btn:hover { background:#0056b3; }
a.btn-delete { background:#dc3545; }
a.btn-delete:hover { background:#b02a37; }

.post {
  display:flex;
  align-items:flex-start;
  gap: 15px;
  border-bottom:1px solid #ddd;
  padding:20px 0;
}
.post img {
  width:120px;
  height:120px;
  object-fit:cover;
  border-radius:8px;
  flex-shrink:0;
}
.post-content {
  flex:1;
}
.post-content h4 {
  margin:0;
  font-size:18px;
  font-weight:600;
  color:#222;
}
.meta {
  color:#777;
  font-size:13px;
  margin:5px 0 10px;
}
.actions {
  display:flex;
  gap:8px;
}
</style>
</head>
<body>
<div class="container">
<header>
  <h2>📰 Blog Dashboard</h2>
  <div>
    Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
    (<?= htmlspecialchars($_SESSION['role']) ?>)
    <?php if ($isAdmin): ?>
      | <a href="manage_users.php" class="btn">👥 Manage Users</a>
    <?php endif; ?>
    | <a href="login.php?logout=1" class="btn btn-delete">Logout</a>
  </div>
</header>

<?php if ($isAdmin): ?>
  <h3>✍️ Create New Article</h3>
  <form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Enter title" required>
    <select name="category" required>
      <option value="Esports">Esports</option>
      <option value="Sports">Sports</option>
      <option value="TV Show">TV Show</option>
      <option value="Reality TV">Reality TV</option>
      <option value="Video Games">Video Games</option>
    </select>
    <input type="file" name="main_image" accept="image/*">
    <textarea id="content" name="content" placeholder="Write your article..."></textarea>
    <button>Publish</button>
  </form>
<?php else: ?>
  <p style="font-style:italic; color:#666;">(You are logged in as a regular user — view only mode.)</p>
<?php endif; ?>

<hr>
<h3>📚 All Articles</h3>

<?php while ($row = $posts->fetch_assoc()): ?>
  <div class="post">
    <img src="<?= $row['image'] ?: 'https://via.placeholder.com/120x120?text=No+Image' ?>">
    <div class="post-content">
      <h4><?= htmlspecialchars($row['title']) ?></h4>
      <div class="meta">
        <?= htmlspecialchars($row['category']) ?> | 
        👤 <?= htmlspecialchars($row['author'] ?? 'Unknown') ?> | 
        <?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?> | 
        👁 <?= (int)$row['views'] ?> views
      </div>
      <div class="actions">
        <a class="btn" href="view_article.php?id=<?= $row['id'] ?>">View</a>
        <?php if ($isAdmin): ?>
          <a class="btn" href="?edit=<?= $row['id'] ?>">Edit</a>
          <a class="btn btn-delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this post?')">Delete</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endwhile; ?>
</div>

<script>
tinymce.init({
  selector:'#content',
  height:400,
  plugins:'advlist autolink lists link image media charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime table emoticons help wordcount',
  toolbar:'undo redo | styles | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | removeformat | preview fullscreen',
  automatic_uploads:true,
  images_upload_url:'blog_post.php?upload_image=1',
});
</script>
</body>
</html>
