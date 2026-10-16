<?php
require_once '../config/database.php';require_once '../includes/auth.php';require_role('admin');$page_title='Статистика';
$rows=$pdo->query("SELECT st.id,CONCAT(st.surname,' ',st.name) student_name,g.name group_name,SUM(a.status='PRESENT') present_count,SUM(a.status='ABSENT') absent_count,SUM(a.status='LATE') late_count,SUM(a.status='EXCUSED') excused_count,COUNT(a.id) total,(SELECT ROUND(AVG(gr.grade),2) FROM grades gr WHERE gr.student_id=st.id) avg_grade FROM students st JOIN groups g ON g.id=st.group_id LEFT JOIN attendance a ON a.student_id=st.id GROUP BY st.id ORDER BY g.name,st.surname")->fetchAll();
$overall=(float)$pdo->query("SELECT COALESCE(ROUND(100*SUM(status IN ('PRESENT','LATE'))/NULLIF(COUNT(*),0),1),0) FROM attendance")->fetchColumn();$avg=$pdo->query('SELECT ROUND(AVG(grade),2) FROM grades')->fetchColumn();$gradeCount=(int)$pdo->query('SELECT COUNT(*) FROM grades')->fetchColumn();
$groupStats=$pdo->query("SELECT g.id,g.name,(SELECT COUNT(*) FROM students st WHERE st.group_id=g.id) students,(SELECT ROUND(AVG(gr.grade),2) FROM grades gr JOIN students st2 ON st2.id=gr.student_id WHERE st2.group_id=g.id) avg_grade,(SELECT COALESCE(ROUND(100*SUM(a.status IN ('PRESENT','LATE'))/NULLIF(COUNT(*),0),1),0) FROM attendance a JOIN students st3 ON st3.id=a.student_id WHERE st3.group_id=g.id) attendance,(SELECT COUNT(*) FROM attendance a JOIN students st4 ON st4.id=a.student_id WHERE st4.group_id=g.id AND a.status='PRESENT') present_count,(SELECT COUNT(*) FROM attendance a JOIN students st5 ON st5.id=a.student_id WHERE st5.group_id=g.id AND a.status='LATE') late_count,(SELECT COUNT(*) FROM attendance a JOIN students st6 ON st6.id=a.student_id WHERE st6.group_id=g.id AND a.status='EXCUSED') excused_count,(SELECT COUNT(*) FROM attendance a JOIN students st7 ON st7.id=a.student_id WHERE st7.group_id=g.id AND a.status='ABSENT') absent_count FROM groups g ORDER BY g.id")->fetchAll();
$statusTotals=$pdo->query("SELECT SUM(status='PRESENT') present_count,SUM(status='LATE') late_count,SUM(status='EXCUSED') excused_count,SUM(status='ABSENT') absent_count FROM attendance")->fetch() ?: ['present_count'=>0,'late_count'=>0,'excused_count'=>0,'absent_count'=>0];
$subjectStats=$pdo->query("SELECT s.name,COUNT(DISTINCT l.id) lessons,COUNT(DISTINCT gr.id) grades,ROUND(AVG(gr.grade),2) avg_grade FROM subjects s LEFT JOIN lessons l ON l.subject_id=s.id LEFT JOIN grades gr ON gr.subject_id=s.id GROUP BY s.id ORDER BY s.name")->fetchAll();
$monthly=[];for($i=5;$i>=0;$i--){$key=date('Y-m',strtotime('-'.$i.' months'));$g=$pdo->prepare("SELECT ROUND(AVG(grade),2) FROM grades WHERE DATE_FORMAT(grade_date,'%Y-%m')=?");$g->execute([$key]);$a=$pdo->prepare("SELECT COALESCE(ROUND(100*SUM(a.status IN ('PRESENT','LATE'))/NULLIF(COUNT(*),0),1),0) FROM attendance a JOIN lessons l ON l.id=a.lesson_id WHERE DATE_FORMAT(l.lesson_date,'%Y-%m')=?");$a->execute([$key]);$monthly[]=['label'=>date('m.Y',strtotime('-'.$i.' months')),'grade'=>(float)($g->fetchColumn()?:0),'attendance'=>(float)($a->fetchColumn()?:0)];}
include '../includes/header.php';?>
<div class="cards"><div class="card"><div class="label">Студентов</div><div class="value"><?=count($rows)?></div></div><div class="card"><div class="label">Общая посещаемость</div><div class="value"><?=$overall?>%</div></div><div class="card"><div class="label">Средний балл</div><div class="value"><?=$avg?:'—'?></div></div><div class="card"><div class="label">Всего оценок</div><div class="value"><?=$gradeCount?></div></div></div>
<div class="cards status-cards" style="margin-top:16px"><div class="card"><div class="label">Присутствует</div><div class="value"><?=$statusTotals['present_count']?></div></div><div class="card"><div class="label">Опоздания</div><div class="value"><?=$statusTotals['late_count']?></div></div><div class="card"><div class="label">Уважительно</div><div class="value"><?=$statusTotals['excused_count']?></div></div><div class="card"><div class="label">Отсутствует</div><div class="value"><?=$statusTotals['absent_count']?></div></div></div>
<div class="chart-grid" style="margin-top:20px">
<div class="panel">
<h2>Средний балл по месяцам</h2>
<div style="position:relative;height:280px;padding:18px 12px 34px 42px;border-left:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;background:repeating-linear-gradient(to top,#ffffff 0,#ffffff 44px,#eef2f7 45px);overflow:hidden">
<div style="position:absolute;left:8px;top:14px;font-size:11px;color:#64748b">5</div>
<div style="position:absolute;left:8px;top:58px;font-size:11px;color:#64748b">4</div>
<div style="position:absolute;left:8px;top:102px;font-size:11px;color:#64748b">3</div>
<div style="position:absolute;left:8px;top:146px;font-size:11px;color:#64748b">2</div>
<div style="position:absolute;left:8px;top:190px;font-size:11px;color:#64748b">1</div>
<div style="height:225px;display:flex;align-items:flex-end;justify-content:space-around;gap:14px">
<?php foreach($monthly as $m): $grade=(float)$m['grade']; $barH=$grade>0?max(8,round(($grade/5)*205)):4; ?>
<div style="height:225px;flex:1;min-width:54px;max-width:120px;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;position:relative">
<div style="font-weight:800;font-size:13px;margin-bottom:6px;color:#1e293b"><?=$grade>0?number_format($grade,2,'.',''):'—'?></div>
<div title="<?=$m['label']?>: <?=$grade>0?$grade:'нет оценок'?>" style="width:54px;max-width:72%;height:<?=$barH?>px;min-height:4px;background:#4f46e5;border-radius:9px 9px 2px 2px;box-shadow:0 4px 12px rgba(79,70,229,.22)"></div>
<div style="position:absolute;bottom:-28px;font-size:11px;color:#64748b;white-space:nowrap"><?=$m['label']?></div>
</div>
<?php endforeach;?>
</div>
</div>
</div>
<div class="panel">
<h2>Посещаемость по месяцам</h2>
<div style="position:relative;height:280px;padding:18px 12px 34px 42px;border-left:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;background:repeating-linear-gradient(to top,#ffffff 0,#ffffff 44px,#eef2f7 45px);overflow:hidden">
<div style="position:absolute;left:4px;top:14px;font-size:11px;color:#64748b">100%</div>
<div style="position:absolute;left:10px;top:68px;font-size:11px;color:#64748b">75%</div>
<div style="position:absolute;left:10px;top:122px;font-size:11px;color:#64748b">50%</div>
<div style="position:absolute;left:10px;top:176px;font-size:11px;color:#64748b">25%</div>
<div style="height:225px;display:flex;align-items:flex-end;justify-content:space-around;gap:14px">
<?php foreach($monthly as $m): $att=(float)$m['attendance']; $barH=$att>0?max(8,round(($att/100)*205)):4; ?>
<div style="height:225px;flex:1;min-width:54px;max-width:120px;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;position:relative">
<div style="font-weight:800;font-size:13px;margin-bottom:6px;color:#1e293b"><?=number_format($att,1,'.','')?>%</div>
<div title="<?=$m['label']?>: <?=$att?>%" style="width:54px;max-width:72%;height:<?=$barH?>px;min-height:4px;background:#059669;border-radius:9px 9px 2px 2px;box-shadow:0 4px 12px rgba(5,150,105,.20)"></div>
<div style="position:absolute;bottom:-28px;font-size:11px;color:#64748b;white-space:nowrap"><?=$m['label']?></div>
</div>
<?php endforeach;?>
</div>
</div>
</div>
</div><div class="statistics-grid">
<div class="panel statistics-panel"><h2>Статистика по группам</h2><div class="table-wrap"><table><tr><th>ID</th><th>Группа</th><th>Студентов</th><th>Посещаемость</th><th>Присутств.</th><th>Опозд.</th><th>Уважит.</th><th>Пропуски</th><th>Средний балл</th></tr><?php foreach($groupStats as $i=>$r):?><tr><td><?=$i+1?></td><td><b><?=htmlspecialchars($r['name'])?></b></td><td><?=$r['students']?></td><td><b><?=$r['attendance']?>%</b><div class="progress"><i style="width:<?=$r['attendance']?>%"></i></div></td><td><?=$r['present_count']?></td><td><?=$r['late_count']?></td><td><?=$r['excused_count']?></td><td><?=$r['absent_count']?></td><td><b><?=$r['avg_grade']?:'—'?></b></td></tr><?php endforeach;?></table></div></div>
<div class="panel statistics-panel"><h2>Статистика по предметам</h2><div class="table-wrap"><table><tr><th>Предмет</th><th>Занятий</th><th>Оценок</th><th>Средний</th></tr><?php foreach($subjectStats as $r):?><tr><td><?=htmlspecialchars($r['name'])?></td><td><?=$r['lessons']?></td><td><?=$r['grades']?></td><td><b><?=$r['avg_grade']?:'—'?></b></td></tr><?php endforeach;?></table></div></div>
</div>
<div class="panel statistics-panel" style="margin-top:20px"><div class="section-head"><h2>Студенты: посещаемость и успеваемость</h2><input class="search" id="q" oninput="filterTable('q','statsTable')" placeholder="🔍 Поиск"></div><div class="table-wrap"><table id="statsTable"><tbody><tr><th>Студент</th><th>Группа</th><th>Посещено</th><th>Пропущено</th><th>Опозданий</th><th>Уважительно</th><th>Посещаемость</th><th>Средний балл</th></tr><?php foreach($rows as $r):$pct=$r['total']?round(($r['present_count']+$r['late_count'])/$r['total']*100):0;?><tr><td><?=htmlspecialchars($r['student_name'])?></td><td><?=htmlspecialchars($r['group_name'])?></td><td><?=$r['present_count']?></td><td><?=$r['absent_count']?></td><td><?=$r['late_count']?></td><td><?=$r['excused_count']?></td><td><b><?=$pct?>%</b><div class="progress"><i style="width:<?=$pct?>%"></i></div></td><td><b><?=$r['avg_grade']?:'—'?></b></td></tr><?php endforeach;?></tbody></table></div></div>
<?php include '../includes/footer.php';?>
