<?php
require 'conn.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$lots = ['available' => 0, 'reserved' => 0, 'occupied' => 0];
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM cemetery_lot GROUP BY status") as $row) {
    $lots[$row['status']] = $row['c'];
}

$burialCount = $pdo->query("SELECT COUNT(*) FROM burial_record")->fetchColumn();
$collected = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payment WHERE payment_status = 'paid'")->fetchColumn();

$recentBurials = $pdo->query("SELECT b.deceased_name, b.burial_date, l.lot_no, s.section_name
                              FROM burial_record b
                              JOIN cemetery_lot l ON l.lot_id = b.lot_id
                              JOIN cemetery_section s ON s.section_id = l.section_id
                              ORDER BY b.record_id DESC LIMIT 5");

$pending = $pdo->query("SELECT r.reservation_id, r.reservation_date, l.lot_no, s.section_name
                        FROM reservation r
                        JOIN cemetery_lot l ON l.lot_id = r.lot_id
                        JOIN cemetery_section s ON s.section_id = l.section_id
                        WHERE r.reservation_status = 'pending'
                        ORDER BY r.reservation_id DESC LIMIT 5");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - Cemetery Lot Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<aside>
  <h1>Cemetery Lot<br>Management</h1>
  <nav>
    <a href="index.php" class="on">Dashboard</a>
    <a href="lots.php">Sections &amp; Lots</a>
    <a href="reservations.php">Reservations</a>
    <a href="payments.php">Payments</a>
    <a href="burials.php">Burial Records</a>
    <a href="search.php">Search &amp; Reports</a>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
  </nav>
  <div class="me"><?= htmlspecialchars($_SESSION['user']['fname']) ?> (<?= $_SESSION['user']['role'] ?>)<br><a href="logout.php">Log out</a></div>
</aside>
<main>
  <h2>Dashboard</h2>

  <div class="stats">
    <div class="stat"><b><?= array_sum($lots) ?></b><span>Total lots</span></div>
    <div class="stat"><b><?= $lots['available'] ?></b><span>Available</span></div>
    <div class="stat"><b><?= $lots['reserved'] ?></b><span>Reserved</span></div>
    <div class="stat"><b><?= $lots['occupied'] ?></b><span>Occupied</span></div>
    <div class="stat"><b><?= $burialCount ?></b><span>Burial records</span></div>
    <div class="stat"><b>₱<?= number_format($collected, 2) ?></b><span>Payments collected</span></div>
  </div>

  <div class="card">
    <h3>Recent burial records</h3>
    <table>
      <tr><th>Deceased</th><th>Lot</th><th>Burial date</th></tr>
      <?php foreach ($recentBurials as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['deceased_name']) ?></td>
          <td><?= htmlspecialchars($r['section_name'] . ' / ' . $r['lot_no']) ?></td>
          <td><?= htmlspecialchars($r['burial_date']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <h3>Pending reservations</h3>
    <table>
      <tr><th>#</th><th>Lot</th><th>Date</th></tr>
      <?php foreach ($pending as $r): ?>
        <tr>
          <td><?= $r['reservation_id'] ?></td>
          <td><?= htmlspecialchars($r['section_name'] . ' / ' . $r['lot_no']) ?></td>
          <td><?= htmlspecialchars($r['reservation_date']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>
</body>
</html>
