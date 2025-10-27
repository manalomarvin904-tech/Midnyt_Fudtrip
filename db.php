<?php
// Database connection and global helpers
$db_host = 'localhost';
$db_name = 'midnyt_fudtrip_db';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

session_start();

function flash($msg, $type='success') {
    $_SESSION['flash'] = ['msg'=>$msg, 'type'=>$type];
}

function get_flash() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES);
}
?>
