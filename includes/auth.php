<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login() {
    if (empty($_SESSION['user'])) {
        header("Location: ../index.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        die("Доступ запрещён.");
    }
}
?>