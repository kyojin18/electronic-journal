<?php
require_once __DIR__ . "/auth.php";
$user = $_SESSION['user'] ?? null;
$roleLabels = ['admin'=>'Администратор','teacher'=>'Преподаватель','student'=>'Студент'];$academicPeriod='';try{$academicPeriod=$pdo->query("SELECT CONCAT(a.name,' · ',COALESCE(s.name,'')) FROM academic_years a LEFT JOIN semesters s ON s.academic_year_id=a.id AND s.is_active=1 WHERE a.is_active=1 LIMIT 1")->fetchColumn()?:'';}catch(Throwable $e){}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($page_title ?? "Электронный журнал") ?></title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= @filemtime(__DIR__.'/../assets/css/style.css') ?: time() ?>">
<script defer src="../assets/js/script.js?v=<?= @filemtime(__DIR__.'/../assets/js/script.js') ?: time() ?>"></script>
</head>
<body>
<div class="app">
<aside class="sidebar" id="sidebar">
  <div class="brand"><span class="brand-mark">✓</span><div><b>Электронный</b><small>Журнал</small></div></div>
  <nav>
    <?php if (($user['role'] ?? '') === 'admin'): ?>
      <a href="dashboard.php">🏠 Главная</a>
      <a href="students.php">👨‍🎓 Студенты</a>
      <a href="groups.php">👥 Группы</a>
      <a href="teachers.php">👨‍🏫 Преподаватели</a>
      <a href="subjects.php">📚 Предметы</a>
      <a href="lessons.php">📅 Расписание</a>
      <a href="attendance.php">✅ Посещаемость</a>
      <a href="grades.php">⭐ Оценки</a>
      <a href="statistics.php">📊 Статистика</a>
      <a href="academic.php">🎓 Учебные периоды</a>
      <a href="users.php">👤 Пользователи</a>
      <a href="profile.php">⚙️ Профиль</a>
    <?php elseif (($user['role'] ?? '') === 'teacher'): ?>
      <a href="dashboard.php">🏠 Главная</a>
      <a href="lessons.php">📅 Расписание</a>
      <a href="attendance.php">✅ Посещаемость</a>
      <a href="grades.php">⭐ Журнал оценок</a>
      <a href="statistics.php">📊 Статистика</a>
      <a href="profile.php">⚙️ Профиль</a>
    <?php else: ?>
      <a href="dashboard.php">🏠 Главная</a>
      <a href="schedule.php">📅 Моё расписание</a>
      <a href="attendance.php">✅ Посещаемость</a>
      <a href="grades.php">⭐ Мои оценки</a>
      <a href="statistics.php">📊 Статистика</a>
      <a href="profile.php">⚙️ Профиль</a>
    <?php endif; ?>
  </nav>
  <button class="logout logout-btn" type="button" onclick="openLogoutModal()">🚪 Выйти</button>
</aside>
<main class="main">
<header class="topbar">
  <button class="menu-btn" onclick="toggleMenu()">☰</button>
  <div>
    <h1><?= htmlspecialchars($page_title ?? "Электронный журнал") ?></h1>
    <p><?= date("d.m.Y") ?><?php if($academicPeriod): ?> · <span class="academic-period"><?=htmlspecialchars($academicPeriod)?></span><?php endif; ?></p>
  </div>
  <a class="profile profile-link" href="profile.php"><span><?= htmlspecialchars($user['login'] ?? '') ?></span><em><?= htmlspecialchars($roleLabels[$user['role'] ?? ''] ?? '') ?></em></a>
</header>
<section class="content">
<?php if (!empty($_SESSION['flash_success'])): ?><div class="notice success"><?=htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']);?></div><?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?><div class="notice error"><?=htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']);?></div><?php endif; ?>

<div class="modal" id="logoutModal" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
  <div class="modal-box logout-modal-box">
    <div class="logout-icon">🚪</div>
    <h2 id="logoutTitle">Вы уверены?</h2>
    <p>Вы действительно хотите выйти из аккаунта?</p>
    <div class="logout-modal-actions">
      <button class="btn secondary" type="button" onclick="closeLogoutModal()">Отмена</button>
      <a class="btn danger" href="../logout.php">Да, выйти</a>
    </div>
  </div>
</div>
