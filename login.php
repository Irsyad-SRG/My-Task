<?php
session_start();

// ==================== DATABASE CONNECTION ====================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blog_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Create users table with role field
$conn->query("
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  email VARCHAR(150) UNIQUE DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') DEFAULT 'user',
  reset_code VARCHAR(10) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL
) ENGINE=InnoDB;
");

// ==================== MODE HANDLING ====================
$mode = $_GET['mode'] ?? 'login';
$message = "";

// ==================== LOGIN ====================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $res = $conn->query("SELECT * FROM users WHERE username='$username'");

        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header("Location: blog_post.php");
                exit;
            } else {
                $message = "❌ Incorrect password.";
            }
        } else {
            $message = "❌ Username not found.";
        }
    }

    // ==================== REGISTER ====================
    elseif (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        if (empty($username) || empty($email) || empty($password)) {
            $message = "⚠️ All fields are required.";
        } else {
            $exists = $conn->query("SELECT * FROM users WHERE username='$username' OR email='$email'");
            if ($exists->num_rows > 0) {
                $message = "⚠️ Username or email already exists.";
            } else {
                $conn->query("INSERT INTO users (username,email,password,role)
                              VALUES ('$username','$email','$hashed','$role')");
                $message = "✅ Registration successful! Please login.";
                $mode = 'login';
            }
        }
    }

    // ==================== RESET PASSWORD ====================
    elseif (isset($_POST['reset_password'])) {
        $username = trim($_POST['username']);
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (empty($username) || empty($new) || empty($confirm)) {
            $message = "⚠️ Please fill in all fields.";
        } elseif ($new !== $confirm) {
            $message = "❌ Passwords do not match.";
        } else {
            $user = $conn->query("SELECT * FROM users WHERE username='$username'");
            if ($user && $user->num_rows > 0) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password='$hashed' WHERE username='$username'");
                $message = "✅ Password updated successfully!";
                $mode = 'login';
            } else {
                $message = "❌ Username not found.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= ucfirst($mode) ?> | Blog Admin</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #f5f6fa;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
}
form {
  background: #fff;
  padding: 35px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  width: 350px;
  position: relative;
}
h2 {
  text-align: center;
  margin-bottom: 20px;
}
input, select {
  width: 100%;
  padding: 10px;
  margin: 8px 0;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.pass-wrapper {
  position: relative;
}
.show-pass {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #777;
  font-size: 13px;
}
button {
  width: 100%;
  background: #007bff;
  color: #fff;
  padding: 10px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
button:hover {
  background: #0056b3;
}
.links {
  text-align: center;
  margin-top: 15px;
  font-size: 14px;
}
a {
  color: #007bff;
  text-decoration: none;
}
.message {
  text-align: center;
  margin-bottom: 10px;
  font-weight: bold;
}
</style>
</head>
<body>
<form method="POST">
  <h2>
    <?= $mode === 'register' ? '📝 Register Account' :
       ($mode === 'forgot' ? '🔑 Reset Password' : '🔒 Article Publishment') ?>
  </h2>

  <?php if ($message): ?>
  <div class="message"><?= $message ?></div>
  <?php endif; ?>

  <?php if ($mode === 'login'): ?>
    <input type="text" name="username" placeholder="Username" required>
    <div class="pass-wrapper">
      <input type="password" id="loginPass" name="password" placeholder="Password" required>
      <span class="show-pass" onclick="togglePass('loginPass', this)">👁 Show</span>
    </div>
    <button name="login">Login</button>
    <div class="links">
      <a href="?mode=register">Create account</a> |
      <a href="?mode=forgot">Forgot password?</a>
    </div>

  <?php elseif ($mode === 'register'): ?>
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <select name="role" required>
      <option value="user">Regular User</option>
      <option value="admin">Admin</option>
    </select>
    <div class="pass-wrapper">
      <input type="password" id="regPass" name="password" placeholder="Password" required>
      <span class="show-pass" onclick="togglePass('regPass', this)">👁 Show</span>
    </div>
    <button name="register">Register</button>
    <div class="links"><a href="?mode=login">⬅ Back to login</a></div>

  <?php elseif ($mode === 'forgot'): ?>
    <input type="text" name="username" placeholder="Enter your username" required>
    <div class="pass-wrapper">
      <input type="password" id="newPass" name="new_password" placeholder="New password" required>
      <span class="show-pass" onclick="togglePass('newPass', this)">👁 Show</span>
    </div>
    <div class="pass-wrapper">
      <input type="password" id="confirmPass" name="confirm_password" placeholder="Confirm new password" required>
      <span class="show-pass" onclick="togglePass('confirmPass', this)">👁 Show</span>
    </div>
    <button name="reset_password">Update Password</button>
    <div class="links"><a href="?mode=login">⬅ Back to login</a></div>
  <?php endif; ?>
</form>

<script>
function togglePass(id, el) {
  const input = document.getElementById(id);
  if (input.type === "password") {
    input.type = "text";
    el.textContent = "🙈 Hide";
  } else {
    input.type = "password";
    el.textContent = "👁 Show";
  }
}
</script>
</body>
</html>

