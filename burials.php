<?php
require 'conn.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_POST) {
    if ($_POST['act'] === 'add') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO burial_record
                                 (lot_id, deceased_name, birthdate, death_date, burial_date, relative_name, contact_no)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['lot_id'],
            $_POST['name'],
            $_POST['birth'] !== '' ? $_POST['birth'] : null,
            $_POST['death'] !== '' ? $_POST['death'] : null,
            $_POST['burial'] !== '' ? $_POST['burial'] : null,
            $_POST['relative'] !== '' ? $_POST['relative'] : null,
            $_POST['contact'] !== '' ? $_POST['contact'] : null,
        ]);
        $stmt = $pdo->prepare("UPDATE cemetery_lot SET status = 'occupied' WHERE lot_id = ?");
        $stmt->execute([$_POST['lot_id']]);
        $pdo->commit();
        $_SESSION['msg'] = 'Burial record saved.';
    }

    if ($_POST['act'] === 'delete') {
        $stmt = $pdo->prepare("SELECT lot_id FROM burial_record WHERE record_id = ?");
        $stmt->execute([$_POST['id']]);
        $record = $stmt->fetch();

        if ($record) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM burial_record WHERE record_id = ?");
            $stmt->execute([$_POST['id']]);

            $stmt = $pdo->prepare("SELECT 1 FROM burial_record WHERE lot_id = ?");
            $stmt->execute([$record['lot_id']]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE cemetery_lot SET status = 'available' WHERE lot_id = ?");
                $stmt->execute([$record['lot_id']]);
            }
            $pdo->commit();
            $_SESSION['msg'] = 'Burial record deleted.';
        }
    }

    header('Location: burials.php');
    exit;
}

$allLots = $pdo->query("SELECT l.lot_id, l.lot_no, l.status, s.section_name
                        FROM cemetery_lot l
                        JOIN cemetery_section s ON s.section_id = l.section_id
                        ORDER BY s.section_name, l.lot_no")->fetchAll();

$burials = $pdo->query("SELECT b.*, l.lot_no, s.section_name
                        FROM burial_record b
                        JOIN cemetery_lot l ON l.lot_id = b.lot_id
                        JOIN cemetery_section s ON s.section_id = l.section_id
                        ORDER BY b.record_id DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Burial Records - Cemetery Lot Management</title>
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
    <a href="burials.php" class="on">Burial Records</a>
    <a href="search.php">Search &amp; Reports</a>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
  </nav>
  <div class="me"><?= htmlspecialchars($_SESSION['user']['fname']) ?> (<?= $_SESSION['user']['role'] ?>)<br><a href="logout.php">Log out</a></div>
</aside>
<main>
  <h2>Burial Records</h2>
  <?php if (!empty($_SESSION['msg'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['msg']) ?></div><?php unset($_SESSION['msg']); endif; ?>

  <div class="card">
    <h3>Add burial record</h3>
    <form class="row" method="post">
      <input type="hidden" name="act" value="add">
      <label>Lot
        <select name="lot_id" required>
          <?php foreach ($allLots as $l): ?>
            <option value="<?= $l['lot_id'] ?>"><?= htmlspecialchars($l['section_name'] . ' / ' . $l['lot_no']) ?> (<?= $l['status'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Deceased name <input name="name" required></label>
      <label>Birthdate <input type="date" name="birth"></label>
      <label>Death date <input type="date" name="death"></label>
      <label>Burial date <input type="date" name="burial"></label>
      <label>Relative name <input name="relative"></label>
      <label>Contact no. <input name="contact"></label>
      <button>Save record</button>
    </form>
  </div>

  <div class="card">
    <table>
      <tr><th>Deceased</th><th>Lot</th><th>Born</th><th>Died</th><th>Buried</th><th>Relative</th><th>Contact</th><th></th></tr>
      <?php foreach ($burials as $b): ?>
        <tr>
          <td><?= htmlspecialchars($b['deceased_name']) ?></td>
          <td><?= htmlspecialchars($b['section_name'] . ' / ' . $b['lot_no']) ?></td>
          <td><?= htmlspecialchars($b['birthdate']) ?></td>
          <td><?= htmlspecialchars($b['death_date']) ?></td>
          <td><?= htmlspecialchars($b['burial_date']) ?></td>
          <td><?= htmlspecialchars($b['relative_name']) ?></td>
          <td><?= htmlspecialchars($b['contact_no']) ?></td>
          <td>
            <form class="i" method="post" onsubmit="return confirm('Delete this burial record?')">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $b['record_id'] ?>">
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
