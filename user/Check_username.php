<?php
include('../connect.php');
function check_username($username)
{
    $username = trim($username);
    if(empty($username)){
        return '用户名不能为空';
    }

    $pat = '/^[A-Za-z0-9_]{4,8}$/';
    if(!preg_match($pat, $username)){
        return '用户名格式不正确';
    }
    return true;
}

function user_exist($username){
    global $pdo;
    $username = trim($username);
    $sql = "SELECT * FROM user WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt -> execute([
        'username' => $username
    ]);
    return $stmt -> rowCount() > 0;
}

function get_user_info($username){
    global $pdo;
    $username = trim($username);
    $sql = "SELECT * FROM user WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt -> execute([
        'username' => $username
    ]);
    return $stmt -> fetch()??['password'=>'','role'=>0];
}
?>
