<?php
session_start();
include($_SERVER['DOCUMENT_ROOT'] . '/New/connect.php');
if(empty($_SESSION['username'])){
    header("location:Login.php");
    exit;
}
if($_SESSION['role']==0){
    header("location:Index.php");
    exit;
}
$id = (int)$_GET['id'];

// 防止删除超级管理员
if ($id == 1) {
    echo "<script>alert('无法删除超级管理员！');location.href='Admin.php';</script>";
    exit;
}
$sql = "DELETE FROM user WHERE id=:id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$sql = "DELETE FROM msg WHERE user_id=:user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
$stmt->execute();
header("location:Admin.php");