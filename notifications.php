<?php
require_once 'config.php';
require_login();
$u=$_SESSION['user'];

$isAjax = isset($_GET['ajax']) || (isset($_POST['ajax']) && $_POST['ajax']==='1') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest');

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_read'])){
    if($_POST['mark_read']==='all'){
        $q=$pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?');
        $q->execute([$u['id']]);
    }else{
        $q=$pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');
        $q->execute([(int)$_POST['mark_read'],$u['id']]);
    }
    if($isAjax){header('Content-Type: application/json');echo json_encode(['ok'=>true,'unread'=>unread_notifications($pdo,(int)$u['id'])]);exit;}
    $dest=($u['role']??'')==='admin'?'admin.php':(($u['role']??'')==='faculty'?'faculty.php':'dashboard.php');
    header('Location: '.$dest);exit;
}

if($isAjax){
    header('Content-Type: application/json');
    if(isset($_GET['ajax']) && $_GET['ajax']==='count'){
        echo json_encode(['unread'=>unread_notifications($pdo,(int)$u['id'])]);
        exit;
    }
    $q=$pdo->prepare('SELECT id,title,message,type,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20');
    $q->execute([$u['id']]);
    echo json_encode(['notifications'=>$q->fetchAll(PDO::FETCH_ASSOC),'unread'=>unread_notifications($pdo,(int)$u['id'])]);
    exit;
}

$dest=($u['role']??'')==='admin'?'admin.php':(($u['role']??'')==='faculty'?'faculty.php':'dashboard.php');
header('Location: '.$dest);exit;
