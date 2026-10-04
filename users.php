<?php
require 'conn.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    $_SESSION['err'] = 'Admins only.';
    header('Location: index.php');
    exit;
}

if ($_POST) {
    if ($_POST['act'] === 'add') {
        try {
            $stmt = $pdo->prepare("INSERT INTO user (fname, lname, email, username, password, role)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['fname'],
                $_POST['lname'],
                $_POST['email'],
                $_POST['username'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['role'],
            ]);
            $_SESSION['msg'] = 'User added.';
        } catch (PDOException $e) {
            $_SESSION['err'] = 'That username is already taken.';
        }
    }

    if ($_POST['act'] === 'delete') {
        if ($_POST['id'] == $_SESSION['user']['user_id']) {
            $_SESSION['err'] = 'You cannot delete your own account.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM user WHERE user_id = ?");
            $stmt->execute([$_POST['id']]);
            $_SESSION['msg'] = 'User deleted.';
        }
    }

    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT * FROM user ORDER BY user_id");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Users - Cemetery Lot Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<aside>
  <h1>Cemetery Lot<br>Management</h1>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="lots.php">Sections &amp; Lots</a>
    <a href="reservations.php">Reservations</a>
    <a href="payments.php">Payments</a>
    <a href="burials.php">Burial Records</a>
    <a href="search.php">Search &amp; Reports</a>
    <a href="users.php" class="on">Users</a>
  </nav>
  <div class="me"><?= htmlspecialchars($_SESSION['user']['fname']) ?> (<?= $_SESSION['user']['role'] ?>)<br><a href="logout.php">Log out</a></div>
</aside>
<main>
  <h2>Users</h2>
  <?php if (!empty($_SESSION['msg'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['msg']) ?></div><?php unset($_SESSION['msg']); endif; ?>
  <?php if (!empty($_SESSION['err'])): ?><div class="flash err"><?= htmlspecialchars($_SESSION['err']) ?></div><?php unset($_SESSION['err']); endif; ?>

  <div class="card">
    <h3>Add user</h3>
    <form class="row" method="post">
      <input type="hidden" name="act" value="add">
      <label>First name <input name="fname" required></label>
      <label>Last name <input name="lname" required></label>
      <label>Email <input type="email" name="email"></label>
      <label>Username <input name="username" required></label>
      <label>Password <input type="password" name="password" minlength="6" required></label>
      <label>Role <select name="role"><option>staff</option><option>admin</option></select></label>
      <button>Add user</button>
    </form>
  </div>

  <div class="card">
    <table>
      <tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th></th></tr>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['fname'] . ' ' . $u['lname']) ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td>
            <?php if ($u['user_id'] != $_SESSION['user']['user_id']): ?>
              <form class="i" method="post" onsubmit="return confirm('Delete this user?')">
                <input type="hidden" name="act" value="delete">
                <input type="hidden" name="id" value="<?= $u['user_id'] ?>">
                <button class="d">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>
</body>
</html>
