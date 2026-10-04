<?php
require 'conn.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$receipt = null;
if (isset($_GET['receipt'])) {
    $stmt = $pdo->prepare("SELECT p.*, l.lot_no, s.section_name
                           FROM payment p
                           JOIN reservation r ON r.reservation_id = p.reservation_id
                           JOIN cemetery_lot l ON l.lot_id = r.lot_id
                           JOIN cemetery_section s ON s.section_id = l.section_id
                           WHERE p.payment_id = ?");
    $stmt->execute([$_GET['receipt']]);
    $receipt = $stmt->fetch();
}

if ($_POST) {
    $stmt = $pdo->prepare("INSERT INTO payment (reservation_id, amount, payment_date, payment_status, payment_method)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['reservation_id'], $_POST['amount'], $_POST['date'], $_POST['status'], $_POST['method']]);
    $_SESSION['msg'] = 'Payment recorded.';
    header('Location: payments.php');
    exit;
}

$openReservations = $pdo->query("SELECT r.reservation_id, l.lot_no, l.price, s.section_name
                                 FROM reservation r
                                 JOIN cemetery_lot l ON l.lot_id = r.lot_id
                                 JOIN cemetery_section s ON s.section_id = l.section_id
                                 WHERE r.reservation_status <> 'cancelled'
                                 ORDER BY r.reservation_id DESC")->fetchAll();

$payments = $pdo->query("SELECT p.*, l.lot_no, s.section_name
                         FROM payment p
                         JOIN reservation r ON r.reservation_id = p.reservation_id
                         JOIN cemetery_lot l ON l.lot_id = r.lot_id
                         JOIN cemetery_section s ON s.section_id = l.section_id
                         ORDER BY p.payment_id DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payments - Cemetery Lot Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<aside>
  <h1>Cemetery Lot<br>Management</h1>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="lots.php">Sections &amp; Lots</a>
    <a href="reservations.php">Reservations</a>
    <a href="payments.php" class="on">Payments</a>
    <a href="burials.php">Burial Records</a>
    <a href="search.php">Search &amp; Reports</a>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
  </nav>
  <div class="me"><?= htmlspecialchars($_SESSION['user']['fname']) ?> (<?= $_SESSION['user']['role'] ?>)<br><a href="logout.php">Log out</a></div>
</aside>
<main>
  <?php if ($receipt): ?>
    <h2>Official Receipt</h2>
    <div class="card" style="max-width:480px">
      <table>
        <tr><th>Receipt no.</th><td><?= str_pad($receipt['payment_id'], 5, '0', STR_PAD_LEFT) ?></td></tr>
        <tr><th>Reservation</th><td>#<?= $receipt['reservation_id'] ?> - <?= htmlspecialchars($receipt['section_name'] . ' / ' . $receipt['lot_no']) ?></td></tr>
        <tr><th>Date</th><td><?= htmlspecialchars($receipt['payment_date']) ?></td></tr>
        <tr><th>Method</th><td><?= htmlspecialchars($receipt['payment_method']) ?></td></tr>
        <tr><th>Status</th><td><span class="b <?= $receipt['payment_status'] ?>"><?= $receipt['payment_status'] ?></span></td></tr>
        <tr><th>Amount</th><td><b>₱<?= number_format($receipt['amount'], 2) ?></b></td></tr>
      </table>
      <p class="noprint"><button onclick="print()">Print</button> <a class="btn s" href="payments.php">Back</a></p>
    </div>
  <?php else: ?>
    <h2>Payments</h2>
    <?php if (!empty($_SESSION['msg'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['msg']) ?></div><?php unset($_SESSION['msg']); endif; ?>

    <div class="card">
      <h3>Record payment</h3>
      <form class="row" method="post">
        <label>Reservation
          <select name="reservation_id" required>
            <?php foreach ($openReservations as $r): ?>
              <option value="<?= $r['reservation_id'] ?>">#<?= $r['reservation_id'] ?> - <?= htmlspecialchars($r['section_name'] . ' / ' . $r['lot_no']) ?> (₱<?= number_format($r['price'], 2) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Amount <input type="number" step="0.01" min="0.01" name="amount" required></label>
        <label>Date <input type="date" name="date" value="<?= date('Y-m-d') ?>" required></label>
        <label>Method
          <select name="method"><option>Cash</option><option>GCash</option><option>Bank Transfer</option></select>
        </label>
        <label>Status
          <select name="status"><option>paid</option><option>pending</option></select>
        </label>
        <button>Save payment</button>
      </form>
    </div>

    <div class="card">
      <table>
        <tr><th>Receipt</th><th>Reservation</th><th>Date</th><th>Method</th><th>Amount</th><th>Status</th><th></th></tr>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= str_pad($p['payment_id'], 5, '0', STR_PAD_LEFT) ?></td>
            <td>#<?= $p['reservation_id'] ?> - <?= htmlspecialchars($p['section_name'] . ' / ' . $p['lot_no']) ?></td>
            <td><?= htmlspecialchars($p['payment_date']) ?></td>
            <td><?= htmlspecialchars($p['payment_method']) ?></td>
            <td>₱<?= number_format($p['amount'], 2) ?></td>
            <td><span class="b <?= $p['payment_status'] ?>"><?= $p['payment_status'] ?></span></td>
            <td><a class="btn s" href="?receipt=<?= $p['payment_id'] ?>">Receipt</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
