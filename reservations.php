<?php
require 'conn.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_POST) {
    $act = $_POST['act'];

    if ($act === 'new') {
        $stmt = $pdo->prepare("SELECT status FROM cemetery_lot WHERE lot_id = ?");
        $stmt->execute([$_POST['lot_id']]);
        $lot = $stmt->fetch();

        if (!$lot || $lot['status'] !== 'available') {
            $_SESSION['err'] = 'That lot is no longer available.';
        } else {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO reservation (lot_id, reservation_date) VALUES (?, ?)");
            $stmt->execute([$_POST['lot_id'], $_POST['date']]);
            $stmt = $pdo->prepare("UPDATE cemetery_lot SET status = 'reserved' WHERE lot_id = ?");
            $stmt->execute([$_POST['lot_id']]);
            $pdo->commit();
            $_SESSION['msg'] = 'Reservation created.';
        }
    }

    if ($act === 'confirm') {
        $stmt = $pdo->prepare("UPDATE reservation SET reservation_status = 'confirmed' WHERE reservation_id = ?");
        $stmt->execute([$_POST['id']]);
        $_SESSION['msg'] = 'Reservation confirmed.';
    }

    if ($act === 'cancel') {
        $stmt = $pdo->prepare("SELECT lot_id FROM reservation WHERE reservation_id = ?");
        $stmt->execute([$_POST['id']]);
        $reservation = $stmt->fetch();

        if ($reservation) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE reservation SET reservation_status = 'cancelled' WHERE reservation_id = ?");
            $stmt->execute([$_POST['id']]);

            $stmt = $pdo->prepare("SELECT 1 FROM burial_record WHERE lot_id = ?");
            $stmt->execute([$reservation['lot_id']]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE cemetery_lot SET status = 'available' WHERE lot_id = ?");
                $stmt->execute([$reservation['lot_id']]);
            }
            $pdo->commit();
            $_SESSION['msg'] = 'Reservation cancelled.';
        }
    }

    header('Location: reservations.php');
    exit;
}

$availableLots = $pdo->query("SELECT l.lot_id, l.lot_no, l.price, s.section_name
                              FROM cemetery_lot l
                              JOIN cemetery_section s ON s.section_id = l.section_id
                              WHERE l.status = 'available'
                              ORDER BY s.section_name, l.lot_no")->fetchAll();

$reservations = $pdo->query("SELECT r.*, l.lot_no, l.price, s.section_name,
                                    (SELECT COALESCE(SUM(p.amount), 0) FROM payment p
                                     WHERE p.reservation_id = r.reservation_id AND p.payment_status = 'paid') AS paid
                             FROM reservation r
                             JOIN cemetery_lot l ON l.lot_id = r.lot_id
                             JOIN cemetery_section s ON s.section_id = l.section_id
                             ORDER BY r.reservation_id DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reservations - Cemetery Lot Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<aside>
  <h1>Cemetery Lot<br>Management</h1>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="lots.php">Sections &amp; Lots</a>
    <a href="reservations.php" class="on">Reservations</a>
    <a href="payments.php">Payments</a>
    <a href="burials.php">Burial Records</a>
    <a href="search.php">Search &amp; Reports</a>
    <?php if ($_SESSION['user']['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
  </nav>
  <div class="me"><?= htmlspecialchars($_SESSION['user']['fname']) ?> (<?= $_SESSION['user']['role'] ?>)<br><a href="logout.php">Log out</a></div>
</aside>
<main>
  <h2>Reservations</h2>
  <?php if (!empty($_SESSION['msg'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['msg']) ?></div><?php unset($_SESSION['msg']); endif; ?>
  <?php if (!empty($_SESSION['err'])): ?><div class="flash err"><?= htmlspecialchars($_SESSION['err']) ?></div><?php unset($_SESSION['err']); endif; ?>

  <div class="card">
    <h3>New reservation</h3>
    <form class="row" method="post">
      <input type="hidden" name="act" value="new">
      <label>Available lot
        <select name="lot_id" required>
          <?php foreach ($availableLots as $l): ?>
            <option value="<?= $l['lot_id'] ?>"><?= htmlspecialchars($l['section_name'] . ' / ' . $l['lot_no']) ?> - ₱<?= number_format($l['price'], 2) ?></option>
          <?php endforeach; ?>
          <?php if (!$availableLots): ?><option value="">-- no lots available --</option><?php endif; ?>
        </select>
      </label>
      <label>Date <input type="date" name="date" value="<?= date('Y-m-d') ?>" required></label>
      <button>Reserve</button>
    </form>
  </div>

  <div class="card">
    <table>
      <tr><th>#</th><th>Lot</th><th>Date</th><th>Price</th><th>Paid</th><th>Status</th><th></th></tr>
      <?php foreach ($reservations as $r): ?>
        <tr>
          <td><?= $r['reservation_id'] ?></td>
          <td><?= htmlspecialchars($r['section_name'] . ' / ' . $r['lot_no']) ?></td>
          <td><?= htmlspecialchars($r['reservation_date']) ?></td>
          <td>₱<?= number_format($r['price'], 2) ?></td>
          <td>₱<?= number_format($r['paid'], 2) ?></td>
          <td><span class="b <?= $r['reservation_status'] ?>"><?= $r['reservation_status'] ?></span></td>
          <td>
            <?php if ($r['reservation_status'] === 'pending'): ?>
              <form class="i" method="post">
                <input type="hidden" name="act" value="confirm">
                <input type="hidden" name="id" value="<?= $r['reservation_id'] ?>">
                <button class="s">Confirm</button>
              </form>
            <?php endif; ?>
            <?php if ($r['reservation_status'] !== 'cancelled'): ?>
              <form class="i" method="post" onsubmit="return confirm('Cancel this reservation?')">
                <input type="hidden" name="act" value="cancel">
                <input type="hidden" name="id" value="<?= $r['reservation_id'] ?>">
                <button class="d">Cancel</button>
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
