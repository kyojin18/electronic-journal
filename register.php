<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    header("Location: {$role}/dashboard.php");
    exit;
}

$errors = [];
$old = [
    'surname' => trim($_POST['surname'] ?? ''),
    'name' => trim($_POST['name'] ?? ''),
    'patronymic' => trim($_POST['patronymic'] ?? ''),
    'group_id' => (int)($_POST['group_id'] ?? 0),
    'login' => trim($_POST['login'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
];

$groups = $pdo->query("SELECT id, name, specialty, course FROM groups ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($old['surname'] === '' || $old['name'] === '' || $old['login'] === '' || !$old['group_id']) {
        $errors[] = 'Заполните все обязательные поля.';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $old['login'])) {
        $errors[] = 'Логин должен содержать 3–50 символов: латинские буквы, цифры, точку, дефис или _. ';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать не менее 6 символов.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают.';
    }
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный адрес электронной почты.';
    }

    $g = $pdo->prepare("SELECT id FROM groups WHERE id=?");
    $g->execute([$old['group_id']]);
    if (!$g->fetchColumn()) {
        $errors[] = 'Выберите существующую группу.';
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE login=? LIMIT 1");
    $check->execute([$old['login']]);
    if ($check->fetch()) {
        $errors[] = 'Пользователь с таким логином уже существует.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (login, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
            $stmt->execute([$old['login'], $old['email'], password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO students (user_id, group_id, surname, name, patronymic, email, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([
                $userId,
                $old['group_id'],
                $old['surname'],
                $old['name'],
                $old['patronymic'],
                $old['email']
            ]);

            $pdo->commit();
            $_SESSION['register_success'] = 'Регистрация прошла успешно. Теперь войдите в систему.';
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Не удалось завершить регистрацию. Попробуйте ещё раз.';
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Регистрация — Электронный журнал</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
<div class="login-box register-box">
  <div class="login-logo"><span class="brand-mark">✓</span></div>
  <h1>Регистрация</h1>
  <div class="sub">Создание аккаунта студента</div>

  <?php if ($errors): ?>
    <div class="notice error">
      <?php foreach ($errors as $error): ?>
        <div>• <?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$groups): ?>
    <div class="notice error">Сначала администратор должен создать хотя бы одну группу.</div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="form-grid register-grid">
      <div class="form-group">
        <label>Фамилия *</label>
        <input name="surname" required value="<?= htmlspecialchars($old['surname']) ?>" placeholder="Иванов">
      </div>
      <div class="form-group">
        <label>Имя *</label>
        <input name="name" required value="<?= htmlspecialchars($old['name']) ?>" placeholder="Иван">
      </div>
      <div class="form-group">
        <label>Отчество</label>
        <input name="patronymic" value="<?= htmlspecialchars($old['patronymic']) ?>" placeholder="Иванович">
      </div>
      <div class="form-group">
        <label>Группа *</label>
        <select name="group_id" required>
          <option value="">Выберите группу</option>
          <?php foreach ($groups as $group): ?>
            <option value="<?= (int)$group['id'] ?>" <?= $old['group_id'] === (int)$group['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($group['name']) ?> — <?= htmlspecialchars($group['specialty']) ?>, <?= (int)$group['course'] ?> курс
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group full">
        <label>Email</label>
        <input name="email" type="email" value="<?= htmlspecialchars($old['email']) ?>" placeholder="student@example.com">
      </div>
      <div class="form-group full">
        <label>Логин *</label>
        <input name="login" required minlength="3" maxlength="50" value="<?= htmlspecialchars($old['login']) ?>" placeholder="Придумайте логин">
      </div>
      <div class="form-group">
        <label>Пароль *</label>
        <input name="password" type="password" required minlength="6" placeholder="Минимум 6 символов">
      </div>
      <div class="form-group">
        <label>Повторите пароль *</label>
        <input name="password_confirm" type="password" required minlength="6" placeholder="Повторите пароль">
      </div>
    </div>
    <button class="btn" type="submit" <?= !$groups ? 'disabled' : '' ?>>Зарегистрироваться</button>
  </form>

  <div class="auth-switch">Уже есть аккаунт? <a href="index.php">Войти</a></div>
</div>
</body>
</html>
