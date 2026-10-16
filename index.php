<?php
session_start();
if (!empty($_SESSION['user'])) {
    $role=$_SESSION['user']['role'];
    header("Location: $role/dashboard.php"); exit;
}
$error=$_SESSION['login_error']??''; unset($_SESSION['login_error']);
$success=$_SESSION['register_success']??''; unset($_SESSION['register_success']);
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Электронный журнал</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login-page">
<div class="login-box">
  <div class="login-logo"><span class="brand-mark">✓</span></div>
  <h1>Электронный журнал</h1>
  <div class="sub">Система учета посещаемости студентов</div>
  <?php if($success): ?><div class="notice success"><?=htmlspecialchars($success)?></div><?php endif; ?>
  <?php if($error): ?><div class="notice error"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form action="login.php" method="post">
    <div class="form-group"><label>Логин</label><input name="login" required placeholder="Введите логин"></div>
    <div class="form-group"><label>Пароль</label><input name="password" type="password" required placeholder="Введите пароль"></div>
    <button class="btn" type="submit">Войти в систему</button>
  </form>
  <div class="auth-switch">Нет аккаунта? <a href="register.php">Зарегистрироваться</a><br><br><a href="forgot_password.php">Забыли пароль?</a></div>
</div></body></html>