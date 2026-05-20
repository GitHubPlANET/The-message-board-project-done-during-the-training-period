<?php
session_start();
include($_SERVER['DOCUMENT_ROOT'] . '/New/connect.php');
if (empty($_SESSION['username'])) {
    header("location:Login.php");
    exit;
}
if ($_SESSION['role'] == 0) {
    header("location:Index.php");
    exit;
}

$id = (int)$_GET['id'];
$role = (int)$_GET['role'];

// 防止修改超级管理员权限
if ($id == 1) {
    echo "<script>alert('无法修改超级管理员权限！');location.href='Admin.php';</script>";
    exit;
}

$sql = "UPDATE user SET role =:role WHERE id=:id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':role', $role);
$stmt->bindParam(':id', $id);
$result = $stmt->execute();
if ($result) {
    echo "<script>alert('修改权限成功！');location.href='Admin.php';</script>";
    //header("location:Admin.php");
}

?>
