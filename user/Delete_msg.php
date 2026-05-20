<?php
session_start();
if(empty($_SESSION['username'])){
    header("location:Login.php");
    exit;
}
include($_SERVER['DOCUMENT_ROOT'] . '/New/connect.php');
$id = (int)$_GET['id'];
$user_id = $_SESSION['id'];
$role = $_SESSION['role'];

// 权限验证：只有管理员或留言本人才能删除
if ($role == 0 || $role == 2) {
    // 普通用户或普通管理员只能删除自己的留言
    $sql = "DELETE FROM msg WHERE id=:id AND user_id=:user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
} else {
    // 超级管理员可以删除所有留言
    $sql = "DELETE FROM msg WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
}
$result = $stmt->execute();
if($result){
    header("Location:Index.php");
}
?>

