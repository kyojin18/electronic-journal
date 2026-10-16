<?php
$userId=(int)$_SESSION['user']['id'];
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $old=$_POST['old_password']??'';$new=$_POST['new_password']??'';$confirm=$_POST['confirm_password']??'';
    $q=$pdo->prepare("SELECT password FROM users WHERE id=?");$q->execute([$userId]);$hash=$q->fetchColumn();
    if(!$hash || !password_verify($old,$hash))$errors[]='Текущий пароль указан неверно.';
    if(strlen($new)<6)$errors[]='Новый пароль должен содержать минимум 6 символов.';
    if($new!==$confirm)$errors[]='Новые пароли не совпадают.';
    if(!$errors){$pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$userId]);$_SESSION['flash_success']='Пароль успешно изменён.';header('Location: profile.php');exit;}
}
$page_title='Смена пароля';include '../includes/header.php';?>
<div class="panel" style="max-width:720px"><h2>🔐 Смена пароля</h2><p class="muted">Для безопасности сначала укажите текущий пароль.</p>
<?php if($errors):?><div class="notice error"><?php foreach($errors as $e):?><div>• <?=htmlspecialchars($e)?></div><?php endforeach;?></div><?php endif;?>
<form method="post"><div class="form-group"><label>Текущий пароль</label><input type="password" name="old_password" required></div><div class="form-group"><label>Новый пароль</label><input id="newPassword" type="password" name="new_password" minlength="6" required oninput="updatePasswordStrength(this.value)"><div class="password-strength"><i id="passwordStrength"></i></div></div><div class="form-group"><label>Повторите новый пароль</label><input type="password" name="confirm_password" minlength="6" required></div><br><button class="btn">Сохранить пароль</button> <a class="btn secondary" href="profile.php">Отмена</a></form></div>
<?php include '../includes/footer.php';?>
