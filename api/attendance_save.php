<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../config/database.php";

function respond_json(bool $error, string $message, int $status=200): void {
    http_response_code($status);
    echo json_encode(['error'=>$error,'message'=>$message], JSON_UNESCAPED_UNICODE);
    exit;
}

if(empty($_SESSION['user'])||!in_array($_SESSION['user']['role'],['admin','teacher'],true)){
    respond_json(true,'Доступ запрещён',403);
}
if($_SERVER['REQUEST_METHOD']!=='POST'){
    respond_json(true,'Метод не поддерживается',405);
}
$lesson=(int)($_POST['lesson_id']??0);
$statuses=$_POST['status']??[];
$comments=$_POST['comment']??[];
if(!$lesson||!is_array($statuses)||!is_array($comments)){
    respond_json(true,'Некорректные данные',400);
}
$q=$pdo->prepare("SELECT group_id,teacher_id FROM lessons WHERE id=?");$q->execute([$lesson]);$lessonRow=$q->fetch();
if(!$lessonRow) respond_json(true,'Занятие не найдено',404);

if($_SESSION['user']['role']==='teacher'){
    $q=$pdo->prepare("SELECT id FROM teachers WHERE user_id=?");$q->execute([$_SESSION['user']['id']]);$teacherId=(int)$q->fetchColumn();
    if(!$teacherId || $teacherId!==(int)$lessonRow['teacher_id']) respond_json(true,'Можно отмечать посещаемость только своих занятий',403);
}

$validStudents=$pdo->prepare("SELECT id FROM students WHERE group_id=? AND status='active'");
$validStudents->execute([(int)$lessonRow['group_id']]);
$allowed=array_fill_keys(array_map('intval',$validStudents->fetchAll(PDO::FETCH_COLUMN)),true);
$stmt=$pdo->prepare("INSERT INTO attendance(lesson_id,student_id,status,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),comment=VALUES(comment),updated_at=CURRENT_TIMESTAMP");
$saved=0;
try{
    $pdo->beginTransaction();
    foreach($statuses as $sid=>$status){
        $sid=(int)$sid;
        if(!isset($allowed[$sid])||!in_array($status,['PRESENT','ABSENT','LATE','EXCUSED'],true)) continue;
        $comment=trim((string)($comments[$sid]??''));
        if(function_exists('mb_substr')) $comment=mb_substr($comment,0,255,'UTF-8'); else $comment=substr($comment,0,255);
        $stmt->execute([$lesson,$sid,$status,$comment]);
        $saved++;
    }
    $pdo->commit();
}catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    respond_json(true,'Не удалось сохранить посещаемость',500);
}
respond_json(false,'Посещаемость сохранена: '.$saved);
