<?php
require_once "../config/database.php"; require_once "../includes/auth.php"; require_role("admin"); $page_title="Преподаватели";
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0);$surname=trim($_POST['surname']??'');$name=trim($_POST['name']??'');$patronymic=trim($_POST['patronymic']??'');$phone=trim($_POST['phone']??'');$email=trim($_POST['email']??'');
    try{
        if($surname===''||$name==='') throw new RuntimeException('Заполните фамилию и имя.');
        if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Введите корректный email.');
        $pdo->beginTransaction();
        if($id){
            $pdo->prepare("UPDATE teachers SET surname=?,name=?,patronymic=?,phone=?,email=? WHERE id=?")->execute([$surname,$name,$patronymic,$phone,$email,$id]);
            $qq=$pdo->prepare("SELECT user_id FROM teachers WHERE id=?");$qq->execute([$id]);$uid=$qq->fetchColumn();if($uid)$pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email,$uid]);
        } else {
            $login=trim($_POST['login']??'');$password=$_POST['password']??'';
            if(!preg_match('/^[A-Za-z0-9_.-]{3,100}$/',$login)) throw new RuntimeException('Логин: 3–100 символов, латинские буквы, цифры, точка, дефис или _.'); if(strlen($password)<6) throw new RuntimeException('Пароль должен содержать минимум 6 символов.');
            $pass=password_hash($password,PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users(login,email,password,role,status) VALUES(?,?,?,'teacher','active')")->execute([$login,$email,$pass]);$uid=$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO teachers(user_id,surname,name,patronymic,phone,email) VALUES(?,?,?,?,?,?)")->execute([$uid,$surname,$name,$patronymic,$phone,$email]);
        }
        $pdo->commit();$_SESSION['flash_success']='Данные преподавателя сохранены.';
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['flash_error']=$e instanceof RuntimeException?$e->getMessage():'Не удалось сохранить преподавателя. Проверьте логин и данные.';}
    header("Location:teachers.php");exit;
}
if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    try{
        $s=$pdo->prepare("SELECT user_id FROM teachers WHERE id=?");$s->execute([$id]);$uid=$s->fetchColumn();
        if(!$uid) throw new RuntimeException('Преподаватель не найден.');
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE groups SET curator_teacher_id=NULL WHERE curator_teacher_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM teachers WHERE id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        $pdo->commit();$_SESSION['flash_success']='Преподаватель удалён.';
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['flash_error']='Нельзя удалить преподавателя, пока за ним закреплены занятия. Сначала измените преподавателя у этих занятий.';}
    header("Location:teachers.php");exit;
}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM teachers WHERE id=?");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$rows=$pdo->query("SELECT * FROM teachers ORDER BY surname,name")->fetchAll();include "../includes/header.php"; ?>
<div class="toolbar"><input class="search" id="q" oninput="filterTable('q','tbl')" placeholder="🔍 Поиск преподавателя"><a class="btn" href="?action=add">+ Добавить преподавателя</a></div>
<?php if(isset($_GET['action'])||$edit): ?><div class="panel" style="margin-bottom:20px"><h2><?=$edit?'Редактировать':'Новый преподаватель'?></h2><form method="post"><input type="hidden" name="id" value="<?=$edit['id']??0?>"><div class="form-grid">
<div class="form-group"><label>Фамилия</label><input name="surname" required value="<?=htmlspecialchars($edit['surname']??'')?>"></div><div class="form-group"><label>Имя</label><input name="name" required value="<?=htmlspecialchars($edit['name']??'')?>"></div><div class="form-group"><label>Отчество</label><input name="patronymic" value="<?=htmlspecialchars($edit['patronymic']??'')?>"></div><div class="form-group"><label>Телефон</label><input name="phone" value="<?=htmlspecialchars($edit['phone']??'')?>"></div><div class="form-group"><label>Email</label><input name="email" type="email" value="<?=htmlspecialchars($edit['email']??'')?>"></div>
<?php if(!$edit): ?><div class="form-group"><label>Логин</label><input name="login" required></div><div class="form-group"><label>Пароль</label><input name="password" type="password" required></div><?php endif; ?></div><br><button class="btn">Сохранить</button> <a class="btn secondary" href="teachers.php">Отмена</a></form></div><?php endif; ?>
<div class="panel"><div class="table-wrap"><table id="tbl"><tr><th>ФИО</th><th>Телефон</th><th>Email</th><th></th></tr><?php foreach($rows as $r): ?><tr><td><?=htmlspecialchars("$r[surname] $r[name] $r[patronymic]")?></td><td><?=htmlspecialchars($r['phone'])?></td><td><?=htmlspecialchars($r['email'])?></td><td class="actions"><a class="btn secondary" href="?edit=<?=$r['id']?>">Изменить</a><a class="btn danger" href="?delete=<?=$r['id']?>" onclick="return confirm('Удалить?')">Удалить</a></td></tr><?php endforeach;?></table></div></div><?php include "../includes/footer.php"; ?>