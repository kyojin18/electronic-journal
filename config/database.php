<?php
$host = "127.0.0.1";
$port = 3307;
$dbname = "electronic_journal";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    require_once __DIR__ . '/schema_upgrade.php';
    ensure_schema($pdo);
} catch (Throwable $e) {
    $msg = $e instanceof PDOException ? 'Ошибка подключения к MySQL' : 'Ошибка подготовки базы данных';
    die($msg . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
