<?php
session_start();
require_once __DIR__."/config/database.php";

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
    $_SESSION['login_error'] = "Заполните логин и пароль.";
    header("Location:index.php");
    exit;
}

// Сначала ищем введённый логин как есть.
$stmt = $pdo->prepare("SELECT * FROM users WHERE login=? AND status='active' LIMIT 1");
$stmt->execute([$login]);
$u = $stmt->fetch();

if (!$u || !password_verify($password, $u['password'])) {
    $_SESSION['login_error'] = "Неверный логин или пароль.";
    header("Location:index.php");
    exit;
}

// Для преподавателя и студента дополнительно проверяем наличие профильной записи,
// чтобы после входа не открывался пустой/сломанный кабинет.
if ($u['role'] === 'teacher') {
    $profile = $pdo->prepare("SELECT id FROM teachers WHERE user_id=? LIMIT 1");
    $profile->execute([$u['id']]);
    if (!$profile->fetchColumn()) {
        $_SESSION['login_error'] = "Для этого преподавателя не найден профиль. Обратитесь к администратору.";
        header("Location:index.php");
        exit;
    }
}
if ($u['role'] === 'student') {
    $profile = $pdo->prepare("SELECT id FROM students WHERE user_id=? LIMIT 1");
    $profile->execute([$u['id']]);
    if (!$profile->fetchColumn()) {
        $_SESSION['login_error'] = "Для этого студента не найден профиль. Обратитесь к администратору.";
        header("Location:index.php");
        exit;
    }
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => $u['id'],
    'login' => $u['login'],
    'role' => $u['role']
];

header("Location: ".$u['role']."/dashboard.php");
exit;
