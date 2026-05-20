<?php
include($_SERVER['DOCUMENT_ROOT'] . '/New/connect.php');
include 'Check_username.php';
include 'Check_password.php';
include 'Check_email.php';
$sc = '';
$error = '';
$user_er = '';
$pwd_er = '';
$email_er = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';

    $check_username = check_username($username);
    $check_password = check_password($password);
    $check_email = check_email($email);

    if ($check_username!==true) {
        $user_er = $check_username;
    }

    if ($check_password!==true) {
        $pwd_er = $check_password;
    }

    if ($check_email!==true) {
        $email_er = $check_email;
    }
    if (!empty($user_er) || !empty($pwd_er) || !empty($email_er)) {

    } else {
        if (user_exist($username)) {
            if (email_exists($email)) {
                $row = get_user_info($username);
                if($row['email']===$email){
                    $hashed_pwd = $row['password'];
                    if (password_verify($password, $hashed_pwd)) {
                        $pwd_er ='新密码与旧密码重复';
                    }else{
                        $hash_pwd = password_hash($password, PASSWORD_DEFAULT);
                        $sql ="UPDATE user SET password = :hash_pwd WHERE username = :username";
                        $stmt = $pdo->prepare($sql);
                        $result = $stmt->execute([
                           ':hash_pwd' => $hash_pwd,
                           ':username' => $username
                        ]);
                        if ($result) {
                            $sc = '密码重置成功,2s后跳转登录页面';
                            header('Refresh:2;url=Login.php');
                            exit;
                        }else{
                            $error_msg = $stmt->errorInfo();
                            $error = "重置失败";
                        }
                    }
                }

            }
            else
            {
                $email_er = '该邮箱未注册';
            }
        }
        else
        {
            $user_er = '该用户名未注册';
        }
    }
}
?>
<!DOCTYPE html>
<link rel="stylesheet" href="common.css">
<html>
<head>
    <meta charset="UTF-8">
    <title>重置密码</title>
</head>
<body>
<form method="post" action="">
    <h1 class="page-title">重置密码</h1>
    <label for="username">用户名</label>
    <input id="username" name="username" type="text" placeholder="字母/数字/下划线 4~8位">
    <?php if (!empty($user_er)): ?>
        <span style="color: red"><?php echo htmlspecialchars($user_er); ?></span>
    <?php endif; ?>
    <label for="email">邮箱</label>
    <input id="email" name="email" type="text">
    <?php if (!empty($email_er)): ?>
        <span style="color: red"><?php echo htmlspecialchars($email_er); ?></span>
    <?php endif; ?>
    <label for="password">密码</label>
    <input id="password" name="password" type="password" placeholder="字母/数字/下划线 4~10位">
    <?php if (!empty($pwd_er)): ?>
        <span style="color: red"><?php echo htmlspecialchars($pwd_er); ?></span>
    <?php endif; ?>
    <?php if (!empty($sc)): ?>
        <span style="color: green"><?php echo htmlspecialchars($sc); ?></span>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <span style="color: red"><?php echo htmlspecialchars($error); ?></span>
    <?php endif; ?>
    <div class="button-group">
    <button type="submit">重置</button>
    <button type="button" onclick="window.location.href='Login.php'">返回首页</button>
    </div>
</form>
</body>
</html>