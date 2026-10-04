<?php
require __DIR__ . '/settings.php';
if (!DEBUG) {
    exit('Diagnostics are disabled (DEBUG is false in settings.php).');
}

$results = [];
$results[] = [version_compare(PHP_VERSION, '7.4', '>='), 'PHP version ' . PHP_VERSION . ' (needs 7.4+)', 'Update XAMPP.'];
$results[] = [extension_loaded('pdo_mysql'), 'PHP extension pdo_mysql is enabled', 'Enable extension=pdo_mysql in php.ini.'];

$pdo = null;
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $results[] = [true, 'Connected to database "' . DB_NAME . '"', ''];
} catch (Throwable $e) {
    $results[] = [false, 'Connect to database "' . DB_NAME . '"', 'Start MySQL and import schema.sql. Detail: ' . $e->getMessage()];
}

if ($pdo) {
    foreach (['user', 'cemetery_section', 'cemetery_lot', 'reservation', 'payment', 'burial_record'] as $table) {
        try {
            $n = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $results[] = [true, "Table $table exists ($n rows)", ''];
        } catch (Throwable $e) {
            $results[] = [false, "Table $table exists", 'Re-import schema.sql.'];
        }
    }
}

$results[] = [is_writable(__DIR__), 'Folder is writable (for errors.log)', 'Check folder permissions.'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>System check</title>
  <style>
    body { font: 17px/1.6 system-ui, sans-serif; max-width: 760px; margin: 30px auto; padding: 0 16px; }
    .ok  { color: #1b6b3a; }
    .bad { color: #b3261e; font-weight: 600; }
    li   { margin-bottom: 8px; }
    small { display: block; color: #555; }
    pre  { background: #f4f3ef; padding: 12px; overflow: auto; }
  </style>
</head>
<body>
  <h1>System check</h1>
  <ul>
    <?php foreach ($results as [$ok, $what, $fix]): ?>
      <li class="<?= $ok ? 'ok' : 'bad' ?>">
        <?= $ok ? '✔' : '✖' ?> <?= htmlspecialchars($what) ?>
        <?php if (!$ok && $fix): ?><small>Fix: <?= htmlspecialchars($fix) ?></small><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <h3>Last lines of errors.log</h3>
  <pre><?php
    $log = __DIR__ . '/errors.log';
    echo file_exists($log) ? htmlspecialchars(implode('', array_slice(file($log), -10))) : '(no errors logged yet)';
  ?></pre>
  <p><a href="login.php">Go to login</a></p>
</body>
</html>
