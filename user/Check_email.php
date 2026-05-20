<?php
include($_SERVER['DOCUMENT_ROOT'].'/New/connect.php');

function check_email($email)
{
    $email = trim($email);
    if(empty($email)){
        return '邮箱不能为空';
    }
    $pat ='/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+\.[a-zA-Z]{2,}$/';
    if(!preg_match($pat, $email)){
        return '邮箱格式不正确';
    }
    return true;
}

function email_exists($email){
    global $pdo;
    $email = trim($email);
    $sql = "SELECT * FROM user WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt -> execute(['email' => $email]);
    return $stmt -> rowCount() > 0;
}