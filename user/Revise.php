<?php
session_start();
include($_SERVER['DOCUMENT_ROOT'] . '/New/connect.php');
include 'Check_password.php';
if(empty($_SESSION['username'])){
    header("location:Login.php");
    exit;
}
$username = $_SESSION['username'];
$sc = '';
$er = '';
$pwd_er = '';
$re_pwd_er = '';
$old_pwd_er = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $old_password = $_POST['old_password'] ?? '';
    $password = $_POST['password'];
    $re_password = $_POST['re_password'];
    
    // 先验证旧密码
    $sql = "SELECT password FROM user WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $row = $stmt->fetch();
    
    if(!$row || !password_verify($old_password, $row['password'])){
        $old_pwd_er = '旧密码错误!';
    }else{
        $check_pwd = check_password($password);
        $check_re_pwd = check_password($re_password);
        if($check_pwd!==true){
            $pwd_er = $check_pwd;
        }
        if($check_re_pwd!==true){
            $re_pwd_er = $check_re_pwd;
        }
        if($check_pwd===true && $check_re_pwd===true){
            if($password == $re_password){
                if(password_verify($password, $row['password'])){
                    $er = "新密码和旧密码相同!";
                }else{
                    $hash_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE user SET password = :password WHERE username = :username";
                    $stmt = $pdo->prepare($sql);
                    $result = $stmt->execute([
                        ':password' => $hash_password,
                        ':username' => $username,
                    ]);
                    if($result){
                        $sc = '密码修改成功!,即将返回首页';
                        header('Refresh: 2; URL=Index.php');
                    }else{
                        $er = '密码修改失败!';
                    }
                }
            }else{
                $er = "两次密码不一致!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="common.css">
</head>
<body>
<form method="post">
    <label for="old_password">旧密码</label>
    <input id="old_password" name="old_password" type="password" placeholder="请输入旧密码" required>
    <?php if (!empty($old_pwd_er)):?>
        <span style="color: red"><?php echo htmlspecialchars($old_pwd_er);?></span>
    <?php endif;?>
    <label for="password">新密码</label>
    <input id="password" name="password" type="password" placeholder="字母/数字/下划线 4~10位">
    <?php if (!empty($pwd_er)):?>
        <span style="color: red"><?php echo htmlspecialchars($pwd_er);?></span>
    <?php endif;?>
    <label for="re_password">确认密码</label>
    <input id="re_password" name="re_password" type="password" placeholder="字母/数字/下划线 4~10位">
    <?php if (!empty($re_pwd_er)):?>
        <span style="color: red"><?php echo htmlspecialchars($re_pwd_er);?></span>
    <?php endif;?>
    <?php if (!empty($sc)):?>
        <span style="color: green"><?php echo htmlspecialchars($sc);?></span>
    <?php endif;?>
    <?php if (!empty($er)):?>
        <span style="color: red"><?php echo htmlspecialchars($er);?></span>
    <?php endif;?>
    <div style="text-align: center;">
        <button type="submit">修改密码</button>
        <button type="button" onclick="window.location.href='Index.php'">返回首页</button>
    </div>
</form>
</body>
</html>