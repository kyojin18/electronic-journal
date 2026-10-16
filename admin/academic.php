<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('admin');
$page_title='Учебные годы и семестры';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $a=$_POST['action']??'';
    try{
        if($a==='year'){
            $name=trim($_POST['name']??'');$start=$_POST['start_date']??'';$end=$_POST['end_date']??'';
            if($name===''||$start===''||$end===''||$start>$end) throw new RuntimeException('Проверьте данные учебного года.');
            $pdo->prepare("INSERT INTO academic_years(name,start_date,end_date,is_active) VALUES(?,?,?,0) ON DUPLICATE KEY UPDATE start_date=VALUES(start_date),end_date=VALUES(end_date)")->execute([$name,$start,$end]);
            $_SESSION['flash_success']='Учебный год сохранён.';
        } elseif($a==='semester'){
            $ay=(int)($_POST['academic_year_id']??0);$num=(int)($_POST['semester_number']??0);$name=trim($_POST['semester_name']??'');$start=$_POST['semester_start']??'';$end=$_POST['semester_end']??'';
            if(!$ay||!in_array($num,[1,2],true)||$name===''||$start===''||$end===''||$start>$end) throw new RuntimeException('Проверьте данные семестра.');
            $yq=$pdo->prepare('SELECT start_date,end_date FROM academic_years WHERE id=?');$yq->execute([$ay]);$yr=$yq->fetch();
            if(!$yr) throw new RuntimeException('Учебный год не найден.');
            if($start<$yr['start_date']||$end>$yr['end_date']) throw new RuntimeException('Даты семестра должны находиться внутри выбранного учебного года.');
            $pdo->prepare("INSERT INTO semesters(academic_year_id,name,semester_number,start_date,end_date,is_active) VALUES(?,?,?,?,?,0) ON DUPLICATE KEY UPDATE name=VALUES(name),start_date=VALUES(start_date),end_date=VALUES(end_date)")->execute([$ay,$name,$num,$start,$end]);
            $_SESSION['flash_success']='Семестр сохранён.';
        } elseif($a==='activate_year'){
            $id=(int)($_POST['id']??0);if(!$id) throw new RuntimeException('Учебный год не найден.');
            $pdo->beginTransaction();
            $pdo->exec("UPDATE academic_years SET is_active=0");
            $pdo->prepare("UPDATE academic_years SET is_active=1 WHERE id=?")->execute([$id]);
            $pdo->exec("UPDATE semesters SET is_active=0");
            $q=$pdo->prepare("SELECT id FROM semesters WHERE academic_year_id=? ORDER BY (CURDATE() BETWEEN start_date AND end_date) DESC,semester_number LIMIT 1");$q->execute([$id]);$sid=(int)$q->fetchColumn();
            if($sid)$pdo->prepare("UPDATE semesters SET is_active=1 WHERE id=?")->execute([$sid]);
            $pdo->commit();$_SESSION['flash_success']='Текущий учебный год изменён.';
        } elseif($a==='activate_semester'){
            $id=(int)($_POST['id']??0);if(!$id) throw new RuntimeException('Семестр не найден.');
            $q=$pdo->prepare("SELECT academic_year_id FROM semesters WHERE id=?");$q->execute([$id]);$ay=(int)$q->fetchColumn();if(!$ay) throw new RuntimeException('Семестр не найден.');
            $pdo->beginTransaction();
            $pdo->exec("UPDATE semesters SET is_active=0");
            $pdo->prepare("UPDATE semesters SET is_active=1 WHERE id=?")->execute([$id]);
            $pdo->exec("UPDATE academic_years SET is_active=0");
            $pdo->prepare("UPDATE academic_years SET is_active=1 WHERE id=?")->execute([$ay]);
            $pdo->commit();$_SESSION['flash_success']='Текущий семестр изменён.';
        }
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['flash_error']=$e instanceof RuntimeException?$e->getMessage():'Не удалось сохранить учебный период.';}
    header('Location:academic.php');exit;
}
$years=$pdo->query("SELECT * FROM academic_years ORDER BY start_date DESC")->fetchAll();
$sems=$pdo->query("SELECT s.*,a.name academic_name FROM semesters s JOIN academic_years a ON a.id=s.academic_year_id ORDER BY a.start_date DESC,s.semester_number")->fetchAll();
include '../includes/header.php';?>
<div class="dashboard-grid"><div class="panel"><h2>Добавить учебный год</h2><form method="post"><input type="hidden" name="action" value="year"><div class="form-grid"><div class="form-group"><label>Название</label><input name="name" placeholder="2026/2027" required></div><div class="form-group"><label>Начало</label><input type="date" name="start_date" required></div><div class="form-group"><label>Окончание</label><input type="date" name="end_date" required></div></div><br><button class="btn">Сохранить</button></form></div><div class="panel"><h2>Добавить семестр</h2><form method="post"><input type="hidden" name="action" value="semester"><div class="form-grid"><div class="form-group"><label>Учебный год</label><select name="academic_year_id"><?php foreach($years as $y):?><option value="<?=$y['id']?>"><?=$y['name']?></option><?php endforeach;?></select></div><div class="form-group"><label>Номер</label><select name="semester_number"><option>1</option><option>2</option></select></div><div class="form-group"><label>Название</label><input name="semester_name" value="1 семестр"></div><div class="form-group"><label>Начало</label><input type="date" name="semester_start" required></div><div class="form-group"><label>Окончание</label><input type="date" name="semester_end" required></div></div><br><button class="btn">Сохранить</button></form></div></div>
<div class="panel" style="margin-top:20px"><h2>Учебные годы</h2><div class="table-wrap"><table><tr><th>Год</th><th>Период</th><th>Статус</th><th></th></tr><?php foreach($years as $y):?><tr><td><b><?=$y['name']?></b></td><td><?=date('d.m.Y',strtotime($y['start_date']))?> — <?=date('d.m.Y',strtotime($y['end_date']))?></td><td><?=$y['is_active']?'Текущий':'—'?></td><td><?php if(!$y['is_active']):?><form method="post"><input type="hidden" name="action" value="activate_year"><input type="hidden" name="id" value="<?=$y['id']?>"><button class="btn secondary">Сделать текущим</button></form><?php endif;?></td></tr><?php endforeach;?></table></div></div><div class="panel" style="margin-top:20px"><h2>Семестры</h2><div class="table-wrap"><table><tr><th>Год</th><th>Семестр</th><th>Период</th><th>Статус</th><th></th></tr><?php foreach($sems as $s):?><tr><td><?=$s['academic_name']?></td><td><b><?=$s['name']?></b></td><td><?=date('d.m.Y',strtotime($s['start_date']))?> — <?=date('d.m.Y',strtotime($s['end_date']))?></td><td><?=$s['is_active']?'Текущий':'—'?></td><td><?php if(!$s['is_active']):?><form method="post"><input type="hidden" name="action" value="activate_semester"><input type="hidden" name="id" value="<?=$s['id']?>"><button class="btn secondary">Сделать текущим</button></form><?php endif;?></td></tr><?php endforeach;?></table></div></div><?php include '../includes/footer.php';?>
