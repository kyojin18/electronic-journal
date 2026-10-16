<?php
require_once "../config/database.php"; require_once "../includes/auth.php"; require_role("admin");
$page_title="Группы";
if($_SERVER['REQUEST_METHOD']==='POST'){
 $id=(int)($_POST['id']??0); $name=trim($_POST['name']??''); $specialty=trim($_POST['specialty']??''); $course=(int)($_POST['course']??0); $curator=(int)($_POST['curator_teacher_id']??0);
 try{
   if($name===''||$specialty==='') throw new RuntimeException('Заполните название группы и специальность.');
   if($course<1||$course>6) throw new RuntimeException('Курс должен быть от 1 до 6.');
   if($curator){$c=$pdo->prepare('SELECT COUNT(*) FROM teachers WHERE id=?');$c->execute([$curator]);if(!$c->fetchColumn())throw new RuntimeException('Выбранный куратор не найден.');}
   if($id){$c=$pdo->prepare('SELECT COUNT(*) FROM groups WHERE id=?');$c->execute([$id]);if(!$c->fetchColumn())throw new RuntimeException('Группа не найдена.');$pdo->prepare("UPDATE groups SET name=?,specialty=?,course=?,curator_teacher_id=? WHERE id=?")->execute([$name,$specialty,$course,$curator?:null,$id]);}
   else{$pdo->prepare("INSERT INTO groups(name,specialty,course,curator_teacher_id) VALUES(?,?,?,?)")->execute([$name,$specialty,$course,$curator?:null]);}
   $_SESSION['flash_success']='Группа сохранена.';
 }catch(Throwable $e){$_SESSION['flash_error']=$e instanceof RuntimeException?$e->getMessage():'Не удалось сохранить группу. Возможно, группа с таким названием уже существует.';}
 header("Location:groups.php");exit;
}
if(isset($_GET['delete'])){try{$pdo->prepare("DELETE FROM groups WHERE id=?")->execute([(int)$_GET['delete']]);$_SESSION['flash_success']='Группа удалена.';}catch(Throwable $e){$_SESSION['flash_error']='Нельзя удалить группу, пока к ней привязаны студенты или занятия.';}header("Location:groups.php");exit;}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM groups WHERE id=?");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$rows=$pdo->query("SELECT g.*,COUNT(st.id) student_count,CONCAT(t.surname,' ',t.name,' ',t.patronymic) curator_name FROM groups g LEFT JOIN students st ON st.group_id=g.id LEFT JOIN teachers t ON t.id=g.curator_teacher_id GROUP BY g.id ORDER BY g.id")->fetchAll();$teachers=$pdo->query("SELECT id,CONCAT(surname,' ',name,' ',patronymic) full_name FROM teachers ORDER BY surname,name")->fetchAll();
include "../includes/header.php"; ?>
<div class="toolbar"><input class="search" id="q" oninput="filterTable('q','tbl')" placeholder="🔍 Поиск группы"><a class="btn" href="?action=add">+ Добавить группу</a></div>
<?php if(isset($_GET['action'])||$edit): ?><div class="panel" style="margin-bottom:20px"><h2><?=$edit?'Редактировать':'Новая группа'?></h2><form method="post"><input type="hidden" name="id" value="<?=$edit['id']??0?>"><div class="form-grid"><div class="form-group"><label>Название</label><input name="name" required value="<?=htmlspecialchars($edit['name']??'')?>"></div><div class="form-group"><label>Специальность</label><input name="specialty" required value="<?=htmlspecialchars($edit['specialty']??'Программное обеспечение')?>"></div><div class="form-group"><label>Курс</label><input type="number" name="course" min="1" max="6" value="<?=$edit['course']??1?>" required></div><div class="form-group"><label>Куратор группы</label><select name="curator_teacher_id"><option value="0">Не назначен</option><?php foreach($teachers as $t): ?><option value="<?=$t['id']?>" <?=((int)($edit['curator_teacher_id']??0)===(int)$t['id'])?'selected':''?>><?=htmlspecialchars($t['full_name'])?></option><?php endforeach; ?></select></div></div><br><button class="btn">Сохранить</button> <a class="btn secondary" href="groups.php">Отмена</a></form></div><?php endif; ?>
<div class="panel"><div class="table-wrap"><table id="tbl"><thead><tr><th>ID</th><th>Группа</th><th>Специальность</th><th>Курс</th><th>Куратор</th><th>Студентов</th><th></th></tr></thead><tbody>
<?php foreach($rows as $i=>$r): ?><tr><td><?=$i+1?></td><td><b><?=htmlspecialchars($r['name'])?></b></td><td><?=htmlspecialchars($r['specialty'])?></td><td><?=$r['course']?></td><td><?=htmlspecialchars($r['curator_name']?:'Не назначен')?></td><td><?=$r['student_count']?></td><td class="actions"><a class="btn" href="group.php?id=<?=$r['id']?>">Открыть</a><a class="btn secondary" href="?edit=<?=$r['id']?>">Изменить</a><a class="btn danger" href="?delete=<?=$r['id']?>" onclick="return confirm('Удалить группу?')">Удалить</a></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php include "../includes/footer.php"; ?>