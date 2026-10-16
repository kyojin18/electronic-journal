<?php
require_once "../config/database.php"; require_once "../includes/auth.php"; require_role("teacher");$page_title="Мои занятия";
$q=$pdo->prepare("SELECT id FROM teachers WHERE user_id=?");$q->execute([$_SESSION['user']['id']]);$tid=(int)$q->fetchColumn();
$groups=$pdo->prepare("SELECT DISTINCT g.id,g.name FROM lessons l JOIN groups g ON g.id=l.group_id WHERE l.teacher_id=? ORDER BY g.name");$groups->execute([$tid]);$groups=$groups->fetchAll();
$subjects=$pdo->prepare("SELECT DISTINCT s.id,s.name FROM lessons l JOIN subjects s ON s.id=l.subject_id WHERE l.teacher_id=? ORDER BY s.name");$subjects->execute([$tid]);$subjects=$subjects->fetchAll();
$fGroup=(int)($_GET['f_group']??0);$fSubject=(int)($_GET['f_subject']??0);$fDate=trim($_GET['f_date']??'');$fText=trim($_GET['q']??'');
$where=['l.teacher_id=?',"TIMESTAMP(l.lesson_date,l.start_time) >= NOW()"];$params=[$tid];
if($fGroup){$where[]='l.group_id=?';$params[]=$fGroup;}if($fSubject){$where[]='l.subject_id=?';$params[]=$fSubject;}if($fDate!==''){$where[]='l.lesson_date=?';$params[]=$fDate;}if($fText!==''){$where[]='(s.name LIKE ? OR g.name LIKE ? OR l.classroom LIKE ?)';$like='%'.$fText.'%';array_push($params,$like,$like,$like);}
$sql="SELECT l.*,s.name subject_name,g.name group_name FROM lessons l JOIN subjects s ON s.id=l.subject_id JOIN groups g ON g.id=l.group_id WHERE ".implode(' AND ',$where)." ORDER BY l.lesson_date ASC,l.start_time ASC LIMIT 30";$q=$pdo->prepare($sql);$q->execute($params);$upcoming=$q->fetchAll();
$q=$pdo->prepare("SELECT l.*,s.name subject_name,g.name group_name FROM lessons l JOIN subjects s ON s.id=l.subject_id JOIN groups g ON g.id=l.group_id WHERE l.teacher_id=? ORDER BY l.lesson_date DESC,l.start_time DESC");$q->execute([$tid]);$rows=$q->fetchAll();
include "../includes/header.php";?>
<div class="panel lesson-search-panel">
  <div class="section-head"><div><h2>🔎 Ближайшие пары</h2><div class="muted">Найди нужное ближайшее занятие по группе, предмету или дате</div></div></div>
  <form method="get" class="lesson-search-grid teacher-search-grid">
    <div class="form-group"><label>Поиск</label><input name="q" value="<?=htmlspecialchars($fText)?>" placeholder="Предмет, группа, аудитория"></div>
    <div class="form-group"><label>Группа</label><select name="f_group"><option value="0">Все группы</option><?php foreach($groups as $x):?><option value="<?=$x['id']?>" <?=$fGroup===$x['id']?'selected':''?>><?=htmlspecialchars($x['name'])?></option><?php endforeach;?></select></div>
    <div class="form-group"><label>Предмет</label><select name="f_subject"><option value="0">Все предметы</option><?php foreach($subjects as $x):?><option value="<?=$x['id']?>" <?=$fSubject===$x['id']?'selected':''?>><?=htmlspecialchars($x['name'])?></option><?php endforeach;?></select></div>
    <div class="form-group"><label>Дата</label><input type="date" name="f_date" value="<?=htmlspecialchars($fDate)?>"></div>
    <div class="lesson-search-actions"><button class="btn" type="submit">Найти пары</button><a class="btn secondary" href="lessons.php">Сбросить</a></div>
  </form>
  <div class="table-wrap upcoming-table"><table><tr><th>Когда</th><th>Предмет</th><th>Группа</th><th>Аудитория</th><th>Тип</th><th></th></tr><?php if(!$upcoming):?><tr><td colspan="6" class="empty">Ближайшие занятия по выбранным условиям не найдены</td></tr><?php else: foreach($upcoming as $r):?><tr><td class="nowrap"><b><?=date('d.m.Y',strtotime($r['lesson_date']))?></b><br><span class="muted"><?=$r['start_time']?>–<?=$r['end_time']?></span></td><td><?=htmlspecialchars($r['subject_name'])?></td><td><span class="badge excused"><?=htmlspecialchars($r['group_name'])?></span></td><td><?=htmlspecialchars($r['classroom']?:'—')?></td><td><?=htmlspecialchars($r['lesson_type'])?></td><td><a class="btn" href="attendance.php?lesson=<?=$r['id']?>">✓ Посещаемость</a></td></tr><?php endforeach; endif;?></table></div>
</div>
<div class="panel" style="margin-top:20px"><h2>Все мои занятия</h2><div class="table-wrap"><table><tr><th>Дата</th><th>Предмет</th><th>Группа</th><th>Время</th><th>Тип</th><th></th></tr><?php foreach($rows as $r):?><tr><td><?=date('d.m.Y',strtotime($r['lesson_date']))?></td><td><?=htmlspecialchars($r['subject_name'])?></td><td><?=htmlspecialchars($r['group_name'])?></td><td><?=$r['start_time']?>–<?=$r['end_time']?></td><td><?=htmlspecialchars($r['lesson_type'])?></td><td><a class="btn" href="attendance.php?lesson=<?=$r['id']?>">✓ Посещаемость</a></td></tr><?php endforeach;?></table></div></div><?php include "../includes/footer.php";?>
