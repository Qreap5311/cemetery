<?php
require 'conn.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_POST) {
    $act = $_POST['act'];

    if ($act === 'section') {
        $stmt = $pdo->prepare("INSERT INTO cemetery_section (section_name, location) VALUES (?, ?)");
        $stmt->execute([$_POST['name'], $_POST['location']]);
        $_SESSION['msg'] = 'Section added.';
    }

    if ($act === 'lot') {
        try {
            $stmt = $pdo->prepare("INSERT INTO cemetery_lot (section_id, lot_no, price) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['section_id'], $_POST['lot_no'], $_POST['price']]);
            $_SESSION['msg'] = 'Lot added.';
        } catch (PDOException $e) {
            $_SESSION['err'] = 'That lot number already exists in this section.';
        }
    }

    if ($act === 'status') {
        $stmt = $pdo->prepare("UPDATE cemetery_lot SET status = ? WHERE lot_id = ?");
        $stmt->execute([$_POST['status'], $_POST['id']]);
        $_SESSION['msg'] = 'Lot status updated.';
    }

    if ($act === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM cemetery_lot WHERE lot_id = ?");
            $stmt->execute([$_POST['id']]);
            $_SESSION['msg'] = 'Lot deleted.';
        } catch (PDOException $e) {
            $_SESSION['err'] = 'Cannot delete: this lot has reservations or burial records.';
        }
    }

    header('Location: lots.php');
    exit;
}

$sections = $pdo->query("SELECT * FROM cemetery_section ORDER BY section_name")->fetchAll();
$lots = $pdo->query("SELECT l.*, s.section_name
                     FROM cemetery_lot l
                     JOIN cemetery_section s ON s.section_id = l.section_id
                     ORDER BY s.section_name, l.lot_no");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sections &amp; Lots - Cemetery Lot Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<aside>
  <h1>Cemetery Lot<br>Management</h1>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="lots.php" class="on">Sections &amp; Lots</a>
    <a href="reservations.php">Reservations</a>
    <a href="payments.php">Payments</a>
    <a href="burials.php">Burial Records</a>
    <a href="search.php">Search &amp; Reports</a>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
  </nav>
  <div class="me"><?= htmlspecialchars($_SESSION['user']['fname']) ?> (<?= $_SESSION['user']['role'] ?>)<br><a href="logout.php">Log out</a></div>
</aside>
<main>
  <h2>Sections &amp; Lots</h2>
  <?php if (!empty($_SESSION['msg'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['msg']) ?></div><?php unset($_SESSION['msg']); endif; ?>
  <?php if (!empty($_SESSION['err'])): ?><div class="flash err"><?= htmlspecialchars($_SESSION['err']) ?></div><?php unset($_SESSION['err']); endif; ?>

  <div class="card">
    <h3>Add section</h3>
    <form class="row" method="post">
      <input type="hidden" name="act" value="section">
      <label>Section name <input name="name" required></label>
      <label>Location <input name="location"></label>
      <button>Add section</button>
    </form>
  </div>

  <div class="card">
    <h3>Add lot</h3>
    <form class="row" method="post">
      <input type="hidden" name="act" value="lot">
      <label>Section
        <select name="section_id" required>
          <?php foreach ($sections as $s): ?>
            <option value="<?= $s['section_id'] ?>"><?= htmlspecialchars($s['section_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Lot no. <input name="lot_no" required></label>
      <label>Price <input type="number" step="0.01" min="0" name="price" required></label>
      <button>Add lot</button>
    </form>
  </div>

  <div class="card">
    <h3>All lots</h3>
    <table>
      <tr><th>Section</th><th>Lot no.</th><th>Price</th><th>Status</th><th>Change status</th><th></th></tr>
      <?php foreach ($lots as $l): ?>
        <tr>
          <td><?= htmlspecialchars($l['section_name']) ?></td>
          <td><?= htmlspecialchars($l['lot_no']) ?></td>
          <td>₱<?= number_format($l['price'], 2) ?></td>
          <td><span class="b <?= $l['status'] ?>"><?= $l['status'] ?></span></td>
          <td>
            <form class="i" method="post">
              <input type="hidden" name="act" value="status">
              <input type="hidden" name="id" value="<?= $l['lot_id'] ?>">
              <select name="status" style="min-width:0">
                <?php foreach (['available', 'reserved', 'occupied'] as $opt): ?>
                  <option <?= $opt === $l['status'] ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
              <button class="s">Update</button>
            </form>
          </td>
          <td>
            <form class="i" method="post" onsubmit="return confirm('Delete this lot?')">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $l['lot_id'] ?>">
              <button class="d">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>
</body>
</html>
