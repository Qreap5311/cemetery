<?php
require 'conn.php';

if ($pdo->query("SELECT COUNT(*) FROM user")->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO user (fname, lname, email, username, password, role)
                           VALUES ('System', 'Admin', '', 'admin', ?, 'admin')");
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
}

$error = '';
if ($_POST) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'Wrong username or password.';
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="login">
    <h2 style="text-align:center">Cemetery Lot Management System</h2>
    <div class="card">
      <?php if ($error): ?><div class="flash err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post">
        <label>Username</label>
        <input name="username" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button>Log in</button>
      </form>
    </div>
  </div>
</body>
</html>
