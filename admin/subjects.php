<?php
require_once "../config/database.php"; require_once "../includes/auth.php"; require_role("admin"); $page_title="Предметы";
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');$desc=trim($_POST['description']??'');
    try{
        if($name==='') throw new RuntimeException('Введите название предмета.');
        if($id){$q=$pdo->prepare('SELECT COUNT(*) FROM subjects WHERE id=?');$q->execute([$id]);if(!$q->fetchColumn())throw new RuntimeException('Предмет не найден.');$pdo->prepare("UPDATE subjects SET name=?,description=? WHERE id=?")->execute([$name,$desc,$id]);}
        else{$pdo->prepare("INSERT INTO subjects(name,description) VALUES(?,?)")->execute([$name,$desc]);}
        $_SESSION['flash_success']='Предмет сохранён.';
    }catch(Throwable $e){$_SESSION['flash_error']=$e instanceof RuntimeException?$e->getMessage():'Не удалось сохранить предмет. Возможно, такое название уже существует.';}
    header("Location:subjects.php");exit;
}
if(isset($_GET['delete'])){try{$pdo->prepare("DELETE FROM subjects WHERE id=?")->execute([(int)$_GET['delete']]);$_SESSION['flash_success']='Предмет удалён.';}catch(Throwable $e){$_SESSION['flash_error']='Нельзя удалить предмет, пока у него есть занятия или оценки.';}header("Location:subjects.php");exit;}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM subjects WHERE id=?");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}$rows=$pdo->query("SELECT * FROM subjects ORDER BY id")->fetchAll();include "../includes/header.php"; ?>
<div class="toolbar"><input class="search" id="q" oninput="filterTable('q','subjectsTable')" placeholder="🔍 Поиск предмета"><a class="btn" href="?action=add">+ Добавить предмет</a></div>
<?php if(isset($_GET['action'])||$edit): ?><div class="panel" style="margin-bottom:20px"><h2><?=$edit?'Редактировать':'Новый предмет'?></h2><form method="post"><input type="hidden" name="id" value="<?=$edit['id']??0?>"><div class="form-grid"><div class="form-group"><label>Название</label><input name="name" required value="<?=htmlspecialchars($edit['name']??'')?>"></div><div class="form-group"><label>Описание</label><input name="description" value="<?=htmlspecialchars($edit['description']??'')?>"></div></div><br><button class="btn">Сохранить</button></form></div><?php endif; ?>
<div class="panel"><div class="table-wrap"><table id="subjectsTable"><tr><th>ID</th><th>Предмет</th><th>Описание</th><th></th></tr><?php foreach($rows as $i=>$r): ?><tr><td><?=$i+1?></td><td><b><?=htmlspecialchars($r['name'])?></b></td><td><?=htmlspecialchars($r['description'])?></td><td class="actions"><a class="btn secondary" href="?edit=<?=$r['id']?>">Изменить</a><a class="btn danger" href="?delete=<?=$r['id']?>" onclick="return confirm('Удалить предмет?')">Удалить</a></td></tr><?php endforeach;?></table></div></div><?php include "../includes/footer.php"; ?>