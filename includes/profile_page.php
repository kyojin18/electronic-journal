<?php
$userId=(int)$_SESSION['user']['id'];
$role=$_SESSION['user']['role'];
$message='';
if($role==='student'){
    $q=$pdo->prepare("SELECT st.*,g.name group_name,g.specialty,g.course,u.login,u.email user_email,u.avatar,u.created_at FROM students st JOIN groups g ON g.id=st.group_id JOIN users u ON u.id=st.user_id WHERE st.user_id=?");
}elseif($role==='teacher'){
    $q=$pdo->prepare("SELECT t.*,u.login,u.email user_email,u.avatar,u.created_at FROM teachers t JOIN users u ON u.id=t.user_id WHERE t.user_id=?");
}else{
    $q=$pdo->prepare("SELECT id,login,email user_email,avatar,created_at FROM users WHERE id=?");
}
$q->execute([$userId]);$profile=$q->fetch()?:[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'profile';

    if($action==='avatar'){
        if(!isset($_FILES['avatar']) || $_FILES['avatar']['error']===UPLOAD_ERR_NO_FILE){
            $_SESSION['flash_error']='Сначала выберите изображение.';
            header('Location: profile.php');exit;
        }
        $f=$_FILES['avatar'];
        if($f['error']!==UPLOAD_ERR_OK){
            $_SESSION['flash_error']='Ошибка загрузки файла. Попробуйте другое изображение.';
            header('Location: profile.php');exit;
        }
        if($f['size']>3145728){
            $_SESSION['flash_error']='Фото должно быть не больше 3 МБ.';
            header('Location: profile.php');exit;
        }
        $mime='';
        if(class_exists('finfo')){$fi=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$fi->file($f['tmp_name']);}
        if($mime==='' && function_exists('getimagesize')){$img=@getimagesize($f['tmp_name']);$mime=$img['mime']??'';}
        $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??'';
        if(!$ext){
            $_SESSION['flash_error']='Разрешены только JPG, PNG и WEBP.';
            header('Location: profile.php');exit;
        }
        $dir=__DIR__.'/../assets/uploads/avatars';
        if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)){
            $_SESSION['flash_error']='Не удалось создать папку для фотографий.';
            header('Location: profile.php');exit;
        }
        $name='user_'.$userId.'_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;
        $relative='assets/uploads/avatars/'.$name;
        if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)){
            $_SESSION['flash_error']='Не удалось сохранить фото.';
            header('Location: profile.php');exit;
        }
        $oldAvatar=(string)($profile['avatar']??'');
        try{
            $pdo->prepare("UPDATE users SET avatar=? WHERE id=?")->execute([$relative,$userId]);
        }catch(Throwable $e){
            @unlink($dir.'/'.$name);
            $_SESSION['flash_error']='Файл загружен, но не удалось сохранить фото в профиле.';
            header('Location: profile.php');exit;
        }
        if($oldAvatar!=='' && strpos($oldAvatar,'assets/uploads/avatars/')===0){
            $oldPath=__DIR__.'/../'.$oldAvatar;
            if(is_file($oldPath))@unlink($oldPath);
        }
        $_SESSION['flash_success']='Фото профиля обновлено.';
        header('Location: profile.php');exit;
    }

    if($action==='delete_avatar'){
        $oldAvatar=(string)($profile['avatar']??'');
        $pdo->prepare("UPDATE users SET avatar='' WHERE id=?")->execute([$userId]);
        if($oldAvatar!=='' && strpos($oldAvatar,'assets/uploads/avatars/')===0){
            $oldPath=__DIR__.'/../'.$oldAvatar;
            if(is_file($oldPath))@unlink($oldPath);
        }
        $_SESSION['flash_success']='Фото профиля удалено.';
        header('Location: profile.php');exit;
    }

    $email=trim($_POST['email']??'');
    if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)){
        $_SESSION['flash_error']='Введите корректный email.';
    } else {
        try{
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email,$userId]);
            if($role==='student'){
                $phone=trim($_POST['phone']??'');
                $pdo->prepare("UPDATE students SET phone=?,email=? WHERE user_id=?")->execute([$phone,$email,$userId]);
            } elseif($role==='teacher'){
                $phone=trim($_POST['phone']??'');
                $pdo->prepare("UPDATE teachers SET phone=?,email=? WHERE user_id=?")->execute([$phone,$email,$userId]);
            }
            $pdo->commit();
            $_SESSION['flash_success']='Профиль сохранён.';
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['flash_error']='Не удалось сохранить профиль.';}
    }
    header('Location: profile.php');exit;
}
$fullName='Администратор';
if($role!=='admin')$fullName=trim(($profile['surname']??'').' '.($profile['name']??'').' '.($profile['patronymic']??''));
$sourceName=$fullName?:($_SESSION['user']['login']??'U'); $initial=function_exists('mb_substr')?mb_strtoupper(mb_substr($sourceName,0,1,'UTF-8'),'UTF-8'):strtoupper(substr($sourceName,0,1));
$page_title='Мой профиль'; include '../includes/header.php';
?>
<style>
/* Изолированная верстка профиля v3 — не зависит от старых правил style.css */
.user-profile-v3{display:grid!important;grid-template-columns:180px minmax(0,1fr)!important;gap:32px!important;align-items:start!important;padding:28px!important;overflow:visible!important}
.user-profile-v3__left{display:flex!important;flex-direction:column!important;align-items:center!important;gap:14px!important;min-width:0!important}
.user-profile-v3__avatar,.user-profile-v3__photo{display:grid!important;place-items:center!important;width:128px!important;height:128px!important;min-width:128px!important;min-height:128px!important;border-radius:28px!important;object-fit:cover!important;overflow:hidden!important;box-shadow:0 10px 28px rgba(15,23,42,.14)!important}
.user-profile-v3__avatar{background:linear-gradient(135deg,#2563eb,#7c3aed)!important;color:#fff!important;font-size:42px!important;font-weight:900!important}
.user-profile-v3__upload{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:8px!important;width:100%!important;max-width:180px!important;margin:0!important;padding:0!important}
.user-profile-v3__file{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important}
.user-profile-v3__upload-btn{display:flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;width:100%!important;min-height:42px!important;padding:10px 12px!important;border:0!important;border-radius:10px!important;background:#e8eef8!important;color:#1e293b!important;font-weight:800!important;cursor:pointer!important;text-align:center!important;white-space:normal!important;line-height:1.2!important}
.user-profile-v3__upload small{display:block!important;text-align:center!important;color:#64748b!important;font-size:12px!important;line-height:1.45!important;margin:0!important}
.user-profile-v3__delete{width:100%!important;max-width:180px!important;margin:0!important}.user-profile-v3__delete-btn{width:100%!important;min-height:40px!important;padding:9px 12px!important}
.user-profile-v3__info{min-width:0!important;padding-top:4px!important}
.user-profile-v3__info h2{margin:0 0 14px!important;font-size:22px!important;line-height:1.3!important;color:#182230!important}
.user-profile-v3__rows{display:grid!important;gap:9px!important;margin:0!important}
.user-profile-v3__row{display:grid!important;grid-template-columns:145px minmax(0,1fr)!important;gap:12px!important;align-items:baseline!important;margin:0!important;line-height:1.4!important;color:#475569!important}
.user-profile-v3__row b{color:#334155!important}
.user-profile-v3__value{min-width:0!important;overflow-wrap:anywhere!important}
.user-profile-v3__actions{display:flex!important;gap:10px!important;flex-wrap:wrap!important;margin-top:22px!important;position:static!important}
.user-profile-v3__actions .btn{position:static!important;margin:0!important}
.profile-contact-v3{margin-top:20px!important}
@media(max-width:760px){
  .user-profile-v3{grid-template-columns:1fr!important;gap:22px!important;text-align:left!important;padding:22px!important}
  .user-profile-v3__left{max-width:220px!important;margin:0 auto!important}
  .user-profile-v3__info h2{text-align:center!important}
  .user-profile-v3__row{grid-template-columns:1fr!important;gap:2px!important}
  .user-profile-v3__actions{justify-content:center!important}
}
</style>
<div class="panel user-profile-v3">
  <div class="user-profile-v3__left">
    <?php if(!empty($profile['avatar'])): ?>
      <img class="user-profile-v3__photo" src="../<?=htmlspecialchars($profile['avatar'])?>" alt="Фото профиля">
    <?php else: ?>
      <div class="user-profile-v3__avatar"><?=htmlspecialchars($initial)?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="user-profile-v3__upload">
      <input type="hidden" name="action" value="avatar">
      <label class="user-profile-v3__upload-btn" for="profileAvatarInput">📷 Выбрать фото</label>
      <input class="user-profile-v3__file" id="profileAvatarInput" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" onchange="if(this.files && this.files.length){this.form.submit();}">
      <small>JPG, PNG или WEBP<br>до 3 МБ</small>
    </form>
    <?php if(!empty($profile['avatar'])): ?>
      <form method="post" class="user-profile-v3__delete" onsubmit="return confirm('Удалить фото профиля?');">
        <input type="hidden" name="action" value="delete_avatar">
        <button class="btn danger user-profile-v3__delete-btn" type="submit">🗑 Удалить фото</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="user-profile-v3__info">
    <h2><?=htmlspecialchars($fullName)?></h2>
    <div class="user-profile-v3__rows">
      <p class="user-profile-v3__row"><b>Логин:</b><span class="user-profile-v3__value"><?=htmlspecialchars($profile['login']??$_SESSION['user']['login'])?></span></p>
      <p class="user-profile-v3__row"><b>Роль:</b><span class="user-profile-v3__value"><?=htmlspecialchars(['admin'=>'Администратор','teacher'=>'Преподаватель','student'=>'Студент'][$role]??$role)?></span></p>
      <?php if($role==='student'): ?>
        <p class="user-profile-v3__row"><b>Группа:</b><span class="user-profile-v3__value"><?=htmlspecialchars($profile['group_name']??'')?> · <?=htmlspecialchars($profile['specialty']??'')?> · <?=intval($profile['course']??0)?> курс</span></p>
      <?php endif; ?>
      <p class="user-profile-v3__row"><b>Аккаунт создан:</b><span class="user-profile-v3__value"><?=!empty($profile['created_at'])?date('d.m.Y',strtotime($profile['created_at'])):'—'?></span></p>
    </div>
    <div class="user-profile-v3__actions">
      <a class="btn secondary" href="change_password.php">🔐 Сменить пароль</a>
    </div>
  </div>
</div>

<div class="panel profile-contact-v3">
  <h2>Контактные данные</h2>
  <form method="post">
    <div class="form-grid">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?=htmlspecialchars($profile['user_email']??$profile['email']??'')?>" placeholder="name@example.com">
      </div>
      <?php if($role!=='admin'): ?>
        <div class="form-group">
          <label>Телефон</label>
          <input name="phone" value="<?=htmlspecialchars($profile['phone']??'')?>" placeholder="+7 ...">
        </div>
      <?php endif; ?>
    </div>
    <br>
    <button class="btn" type="submit">Сохранить изменения</button>
  </form>
</div>
<?php include '../includes/footer.php'; ?>
