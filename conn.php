<?php
require __DIR__ . '/settings.php';
session_start();
error_reporting(E_ALL & ~E_DEPRECATED);

set_exception_handler(function ($e) {
    $line = date('[Y-m-d H:i:s] ') . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ")\n";
    error_log($line, 3, __DIR__ . '/errors.log');
    http_response_code(500);
    echo "<div style='font:16px sans-serif;max-width:640px;margin:40px auto;padding:20px;border:2px solid #9a2f22;border-radius:8px'>";
    echo "<h2>Something went wrong</h2>";
    echo "<p>Open <a href='check.php'>check.php</a> to diagnose the problem.</p>";
    if (DEBUG) {
        echo '<pre style="white-space:pre-wrap">' . htmlspecialchars($line) . '</pre>';
    }
    echo '</div>';
});

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    throw new RuntimeException('Cannot connect to database "' . DB_NAME . '". Is MySQL running and is schema.sql imported? Detail: ' . $e->getMessage());
}
