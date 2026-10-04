<?php
require 'conn.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$keyword = trim($_GET['q'] ?? '');
$results = [];
if ($keyword !== '') {
    $like = "%$keyword%";
    $stmt = $pdo->prepare("SELECT b.*, l.lot_no, s.section_name
                           FROM burial_record b
                           JOIN cemetery_lot l ON l.lot_id = b.lot_id
                           JOIN cemetery_section s ON s.section_id = l.section_id
                           WHERE b.deceased_name LIKE ? OR b.relative_name LIKE ?
                              OR l.lot_no LIKE ? OR s.section_name LIKE ?
                           ORDER BY b.deceased_name");
    $stmt->execute([$like, $like, $like, $like]);
    $results = $stmt->fetchAll();
}

$bySection = $pdo->query("SELECT s.section_name, COUNT(l.lot_id) AS total,
                                 SUM(l.status = 'available') AS available,
                                 SUM(l.status = 'reserved') AS reserved,
                                 SUM(l.status = 'occupied') AS occupied
                          FROM cemetery_section s
                          LEFT JOIN cemetery_lot l ON l.section_id = s.section_id
                          GROUP BY s.section_id, s.section_name
                          ORDER BY s.section_name");

$totals = $pdo->query("SELECT COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount END), 0) AS paid,
                              COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount END), 0) AS pending
                       FROM payment")->fetch();
$burialCount = $pdo->query("SELECT COUNT(*) FROM burial_record")->fetchColumn();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Search &amp; Reports - Cemetery Lot Management</title>
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
    <a href="search.php" class="on">Search &amp; Reports</a>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
  </nav>
  <div class="me"><?= htmlspecialchars($_SESSION['user']['fname']) ?> (<?= $_SESSION['user']['role'] ?>)<br><a href="logout.php">Log out</a></div>
</aside>
<main>
  <h2>Search &amp; Reports</h2>

  <div class="card noprint">
    <form class="row" method="get">
      <label>Search deceased, relative, section or lot
        <input name="q" value="<?= htmlspecialchars($keyword) ?>" style="min-width:320px" placeholder="e.g. Santos or A-01">
      </label>
      <button>Search</button>
    </form>
  </div>

  <?php if ($keyword !== ''): ?>
    <div class="card">
      <h3>Results for "<?= htmlspecialchars($keyword) ?>" (<?= count($results) ?>)</h3>
      <table>
        <tr><th>Deceased</th><th>Lot</th><th>Burial date</th><th>Relative</th><th>Contact</th></tr>
        <?php foreach ($results as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['deceased_name']) ?></td>
            <td><?= htmlspecialchars($b['section_name'] . ' / ' . $b['lot_no']) ?></td>
            <td><?= htmlspecialchars($b['burial_date']) ?></td>
            <td><?= htmlspecialchars($b['relative_name']) ?></td>
            <td><?= htmlspecialchars($b['contact_no']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$results): ?><tr><td colspan="5">No records found.</td></tr><?php endif; ?>
      </table>
    </div>
  <?php endif; ?>

  <div class="card">
    <h3>Cemetery report - <?= date('F j, Y') ?></h3>
    <table>
      <tr><th>Section</th><th>Total lots</th><th>Available</th><th>Reserved</th><th>Occupied</th></tr>
      <?php foreach ($bySection as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['section_name']) ?></td>
          <td><?= $r['total'] ?></td>
          <td><?= (int)$r['available'] ?></td>
          <td><?= (int)$r['reserved'] ?></td>
          <td><?= (int)$r['occupied'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <p>
      Burial records: <b><?= $burialCount ?></b> &middot;
      Payments collected: <b>₱<?= number_format($totals['paid'], 2) ?></b> &middot;
      Pending: <b>₱<?= number_format($totals['pending'], 2) ?></b>
    </p>
    <button class="noprint" onclick="print()">Print report</button>
  </div>
</main>
</body>
</html>
