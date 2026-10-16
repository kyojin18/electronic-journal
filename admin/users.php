<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
require_role("admin");
$page_title="Пользователи";
if(isset($_GET['toggle'])){$id=(int)$_GET['toggle'];if($id===(int)$_SESSION['user']['id']){$_SESSION['flash_error']='Нельзя заблокировать собственный аккаунт.';}else{$pdo->prepare("UPDATE users SET status=IF(status='active','blocked','active') WHERE id=?")->execute([$id]);$_SESSION['flash_success']='Статус пользователя изменён.';}header("Location:users.php");exit;}
$q=trim($_GET['q']??''); $role=$_GET['role']??''; $status=$_GET['status']??'';
$sql="SELECT u.id,u.login,u.email,u.role,u.status,u.created_at, COALESCE(CONCAT(t.surname,' ',t.name,' ',t.patronymic),CONCAT(s.surname,' ',s.name,' ',s.patronymic),'Администратор') AS full_name FROM users u LEFT JOIN teachers t ON t.user_id=u.id LEFT JOIN students s ON s.user_id=u.id WHERE 1";
$p=[]; if($q!==''){$sql.=" AND (u.login LIKE ? OR u.email LIKE ? OR t.surname LIKE ? OR s.surname LIKE ?)";$like="%$q%";$p=[$like,$like,$like,$like];} if(in_array($role,['admin','teacher','student'],true)){$sql.=" AND u.role=?";$p[]=$role;} if(in_array($status,['active','blocked'],true)){$sql.=" AND u.status=?";$p[]=$status;} $sql.=" ORDER BY FIELD(u.role,'admin','teacher','student'),full_name,u.id";$st=$pdo->prepare($sql);$st->execute($p);$rows=$st->fetchAll();
include "../includes/header.php";?>
<div class="panel"><h2>Пользователи</h2>
<form method="get" class="filters" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px">
<input name="q" value="<?=htmlspecialchars($q)?>" placeholder="ФИО, логин или email" autocomplete="off">
<select name="role"><option value="">Все роли</option><option value="admin" <?=$role==='admin'?'selected':''?>>Администраторы</option><option value="teacher" <?=$role==='teacher'?'selected':''?>>Преподаватели</option><option value="student" <?=$role==='student'?'selected':''?>>Студенты</option></select>
<select name="status"><option value="">Любой статус</option><option value="active" <?=$status==='active'?'selected':''?>>Активные</option><option value="blocked" <?=$status==='blocked'?'selected':''?>>Заблокированные</option></select>
<button class="btn" type="submit">Найти</button><a class="btn secondary" href="users.php">Сбросить</a></form>
<div class="table-wrap"><table><tr><th>№</th><th>ФИО</th><th>Логин</th><th>Email</th><th>Роль</th><th>Статус</th><th>Создан</th><th></th></tr><?php $n=1; foreach($rows as $r):?><tr><td><?=$n++?></td><td><b><?=htmlspecialchars(trim($r['full_name']))?></b></td><td><code><?=htmlspecialchars($r['login'])?></code></td><td><?=htmlspecialchars($r['email'])?></td><td><?=['admin'=>'Администратор','teacher'=>'Преподаватель','student'=>'Студент'][$r['role']]??htmlspecialchars($r['role'])?></td><td><span class="badge <?=$r['status']==='active'?'present':'absent'?>"><?=$r['status']==='active'?'Активен':'Заблокирован'?></span></td><td><?=date('d.m.Y',strtotime($r['created_at']))?></td><td><a class="btn secondary" href="?toggle=<?=$r['id']?>" onclick="return confirm('Изменить статус пользователя?')">Изменить статус</a></td></tr><?php endforeach;?></table></div></div><?php include "../includes/footer.php";?>