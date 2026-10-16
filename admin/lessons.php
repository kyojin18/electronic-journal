<?php
require_once "../config/database.php"; require_once "../includes/auth.php"; require_role("admin"); $page_title="Занятия";
if($_SERVER['REQUEST_METHOD']==='POST'){
$id=(int)($_POST['id']??0);$subject=(int)($_POST['subject_id']??0);$teacher=(int)($_POST['teacher_id']??0);$group=(int)($_POST['group_id']??0);$date=trim($_POST['lesson_date']??'');$start=trim($_POST['start_time']??'');$end=trim($_POST['end_time']??'');$room=trim($_POST['classroom']??'');$type=trim($_POST['lesson_type']??'');
try{
 if(!$subject||!$teacher||!$group||$date===''||$start===''||$end==='') throw new RuntimeException('Заполните обязательные поля занятия.');
 $d=DateTime::createFromFormat('Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date)throw new RuntimeException('Укажите корректную дату.');
 if($start>=$end) throw new RuntimeException('Время окончания должно быть позже времени начала.');
 if(!in_array($type,['Лекция','Практика','Лабораторная'],true)) throw new RuntimeException('Выберите корректный тип занятия.');
 foreach([['subjects',$subject],['teachers',$teacher],['groups',$group]] as [$table,$value]){$q=$pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE id=?");$q->execute([$value]);if(!$q->fetchColumn())throw new RuntimeException('Один из выбранных объектов больше не существует. Обновите страницу.');}
 if($id){$q=$pdo->prepare('SELECT COUNT(*) FROM lessons WHERE id=?');$q->execute([$id]);if(!$q->fetchColumn())throw new RuntimeException('Занятие не найдено.');$pdo->prepare("UPDATE lessons SET subject_id=?,teacher_id=?,group_id=?,lesson_date=?,start_time=?,end_time=?,classroom=?,lesson_type=? WHERE id=?")->execute([$subject,$teacher,$group,$date,$start,$end,$room,$type,$id]);}
 else{$pdo->prepare("INSERT INTO lessons(subject_id,teacher_id,group_id,lesson_date,start_time,end_time,classroom,lesson_type) VALUES(?,?,?,?,?,?,?,?)")->execute([$subject,$teacher,$group,$date,$start,$end,$room,$type]);}
 $_SESSION['flash_success']='Занятие сохранено.';
}catch(Throwable $e){$_SESSION['flash_error']=$e instanceof RuntimeException?$e->getMessage():'Не удалось сохранить занятие.';}
header("Location:lessons.php");exit;}
if(isset($_GET['delete'])){try{$pdo->prepare("DELETE FROM lessons WHERE id=?")->execute([(int)$_GET['delete']]);$_SESSION['flash_success']='Занятие удалено вместе с его посещаемостью.';}catch(Throwable $e){$_SESSION['flash_error']='Не удалось удалить занятие.';}header("Location:lessons.php");exit;}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM lessons WHERE id=?");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$subjects=$pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();$teachers=$pdo->query("SELECT * FROM teachers ORDER BY surname,name")->fetchAll();$groups=$pdo->query("SELECT * FROM groups ORDER BY name")->fetchAll();

// Поиск ближайших занятий
$fGroup=(int)($_GET['f_group']??0);$fTeacher=(int)($_GET['f_teacher']??0);$fSubject=(int)($_GET['f_subject']??0);$fDate=trim($_GET['f_date']??'');$fText=trim($_GET['q']??'');
$where=["TIMESTAMP(l.lesson_date,l.start_time) >= NOW()"];$params=[];
if($fGroup){$where[]='l.group_id=?';$params[]=$fGroup;}if($fTeacher){$where[]='l.teacher_id=?';$params[]=$fTeacher;}if($fSubject){$where[]='l.subject_id=?';$params[]=$fSubject;}if($fDate!==''){$where[]='l.lesson_date=?';$params[]=$fDate;}if($fText!==''){$where[]='(s.name LIKE ? OR g.name LIKE ? OR CONCAT(t.surname," ",t.name) LIKE ? OR l.classroom LIKE ?)';$like='%'.$fText.'%';array_push($params,$like,$like,$like,$like);}
$sql="SELECT l.*,s.name subject_name,g.name group_name,CONCAT(t.surname,' ',t.name) teacher_name FROM lessons l JOIN subjects s ON s.id=l.subject_id JOIN groups g ON g.id=l.group_id JOIN teachers t ON t.id=l.teacher_id WHERE ".implode(' AND ',$where)." ORDER BY l.lesson_date ASC,l.start_time ASC LIMIT 30";
$st=$pdo->prepare($sql);$st->execute($params);$upcoming=$st->fetchAll();

$rows=$pdo->query("SELECT l.*,s.name subject_name,g.name group_name,CONCAT(t.surname,' ',t.name) teacher_name FROM lessons l JOIN subjects s ON s.id=l.subject_id JOIN groups g ON g.id=l.group_id JOIN teachers t ON t.id=l.teacher_id ORDER BY l.lesson_date DESC,l.start_time DESC")->fetchAll();
include "../includes/header.php"; ?>
<div class="panel lesson-search-panel">
  <div class="section-head"><div><h2>🔎 Ближайшие пары</h2><div class="muted">Поиск будущих занятий по группе, преподавателю, предмету и дате</div></div></div>
  <form method="get" class="lesson-search-grid">
    <div class="form-group"><label>Поиск</label><input name="q" value="<?=htmlspecialchars($fText)?>" placeholder="Предмет, группа, преподаватель, аудитория"></div>
    <div class="form-group"><label>Группа</label><select name="f_group"><option value="0">Все группы</option><?php foreach($groups as $x):?><option value="<?=$x['id']?>" <?=$fGroup===$x['id']?'selected':''?>><?=htmlspecialchars($x['name'])?></option><?php endforeach;?></select></div>
    <div class="form-group"><label>Преподаватель</label><select name="f_teacher"><option value="0">Все преподаватели</option><?php foreach($teachers as $x):?><option value="<?=$x['id']?>" <?=$fTeacher===$x['id']?'selected':''?>><?=htmlspecialchars($x['surname'].' '.$x['name'])?></option><?php endforeach;?></select></div>
    <div class="form-group"><label>Предмет</label><select name="f_subject"><option value="0">Все предметы</option><?php foreach($subjects as $x):?><option value="<?=$x['id']?>" <?=$fSubject===$x['id']?'selected':''?>><?=htmlspecialchars($x['name'])?></option><?php endforeach;?></select></div>
    <div class="form-group"><label>Дата</label><input type="date" name="f_date" value="<?=htmlspecialchars($fDate)?>"></div>
    <div class="lesson-search-actions"><button class="btn" type="submit">Найти пары</button><a class="btn secondary" href="lessons.php">Сбросить</a></div>
  </form>
  <div class="table-wrap upcoming-table"><table><tr><th>Когда</th><th>Предмет</th><th>Группа</th><th>Преподаватель</th><th>Аудитория</th><th>Тип</th><th></th></tr><?php if(!$upcoming):?><tr><td colspan="7" class="empty">Ближайшие занятия по выбранным условиям не найдены</td></tr><?php else: foreach($upcoming as $r):?><tr><td class="nowrap"><b><?=date('d.m.Y',strtotime($r['lesson_date']))?></b><br><span class="muted"><?=$r['start_time']?>–<?=$r['end_time']?></span></td><td><?=htmlspecialchars($r['subject_name'])?></td><td><span class="badge excused"><?=htmlspecialchars($r['group_name'])?></span></td><td><?=htmlspecialchars($r['teacher_name'])?></td><td><?=htmlspecialchars($r['classroom']?:'—')?></td><td><?=htmlspecialchars($r['lesson_type'])?></td><td><a class="btn secondary" href="attendance.php?lesson=<?=$r['id']?>">Открыть</a></td></tr><?php endforeach; endif;?></table></div>
</div>

<div class="toolbar" style="margin-top:20px"><a class="btn" href="?action=add">+ Добавить занятие</a></div>
<?php if(isset($_GET['action'])||$edit): ?><div class="panel" style="margin-bottom:20px"><h2><?=$edit?'Редактировать':'Новое занятие'?></h2><form method="post"><input type="hidden" name="id" value="<?=$edit['id']??0?>"><div class="form-grid">
<div class="form-group"><label>Предмет</label><select name="subject_id" required><?php foreach($subjects as $x): ?><option value="<?=$x['id']?>" <?=($edit['subject_id']??0)==$x['id']?'selected':''?>><?=htmlspecialchars($x['name'])?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Преподаватель</label><select name="teacher_id" required><?php foreach($teachers as $x): ?><option value="<?=$x['id']?>" <?=($edit['teacher_id']??0)==$x['id']?'selected':''?>><?=htmlspecialchars("$x[surname] $x[name]")?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Группа</label><select name="group_id" required><?php foreach($groups as $x): ?><option value="<?=$x['id']?>" <?=($edit['group_id']??0)==$x['id']?'selected':''?>><?=htmlspecialchars($x['name'])?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Дата</label><input type="date" name="lesson_date" required value="<?=$edit['lesson_date']??date('Y-m-d')?>"></div><div class="form-group"><label>Начало</label><input type="time" name="start_time" required value="<?=$edit['start_time']??'10:00'?>"></div><div class="form-group"><label>Конец</label><input type="time" name="end_time" required value="<?=$edit['end_time']??'11:30'?>"></div><div class="form-group"><label>Аудитория</label><input name="classroom" value="<?=htmlspecialchars($edit['classroom']??'205')?>"></div><div class="form-group"><label>Тип</label><select name="lesson_type"><?php foreach(['Лекция','Практика','Лабораторная работа','Семинар'] as $x): ?><option <?=($edit['lesson_type']??'Практика')===$x?'selected':''?>><?=$x?></option><?php endforeach;?></select></div>
</div><br><button class="btn">Сохранить</button></form></div><?php endif; ?>
<div class="panel"><h2>Все занятия</h2><div class="table-wrap"><table><tr><th>Дата</th><th>Предмет</th><th>Группа</th><th>Преподаватель</th><th>Время</th><th>Тип</th><th></th></tr><?php foreach($rows as $r): ?><tr><td><?=date('d.m.Y',strtotime($r['lesson_date']))?></td><td><?=htmlspecialchars($r['subject_name'])?></td><td><?=htmlspecialchars($r['group_name'])?></td><td><?=htmlspecialchars($r['teacher_name'])?></td><td><?=$r['start_time']?>–<?=$r['end_time']?></td><td><?=htmlspecialchars($r['lesson_type'])?></td><td class="actions"><a class="btn secondary" href="attendance.php?lesson=<?=$r['id']?>">Посещаемость</a><a class="btn secondary" href="?edit=<?=$r['id']?>">Изменить</a><a class="btn danger" href="?delete=<?=$r['id']?>" onclick="return confirm('Удалить занятие?')">Удалить</a></td></tr><?php endforeach;?></table></div></div><?php include "../includes/footer.php"; ?>
