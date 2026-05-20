<?php
include('../connect.php');

function  check_password($password)
{
    $password = trim($password);
    if(empty($password)){
        return '密码不能为空';
    }
    $pat = '/^[A-Za-z0-9]{4,10}$/';
    if(!preg_match($pat, $password)){
        return '密码格式不正确';
    }
    return true;
}

