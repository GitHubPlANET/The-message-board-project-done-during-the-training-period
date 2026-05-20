<?php

session_start();
include('../connect.php');
include 'Check_username.php';
include 'Check_password.php';

$sc = '';
$user_er = '';
$pwd_er = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rem = $_POST['rem'] ?? '0';

    $check_user = check_username($username);
    $check_pwd = check_password($password);

    if ($check_user === true) {
        if (user_exist($username)) {
            $row = get_user_info($username);
            $hashed_password = $row['password'];
            $role = $row['role'];
            $id = $row['id'];
            if ($check_pwd === true) {
                if (password_verify($password, $hashed_password)) {
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                    $_SESSION['id'] = $id;
                    if ($rem == 1) {
                        setcookie('username', $username, time() + (86400 * 7), "/");
                    } else {
                        setcookie('username', '', time() - 3600, "/");
                    }
                    $sc = '登陆成功,2s后跳转首页';
                    header('Refresh: 2; URL=Index.php');
                    exit;
                } else {
                    $pwd_er = '密码错误';
                }
            } else {
                $pwd_er = $check_pwd;
            }
        } else {
            $user_er = '用户不存在';
        }
    } else {
        $user_er = $check_user;
    }

}
?>
<!DOCTYPE html>
<link rel="stylesheet" href="common.css">
<html>
<head>
    <meta charset="UTF-8">
    <title>登录</title>
</head>
<body>
<form method="post" action="">
    <h1 class="page-title">用户登录</h1>
    <label for="username">用户名</label>
    <input id="username" name="username" type="text" placeholder="字母/数字/下划线 4~8位"
            <?php echo isset($_COOKIE['username']) ? "value='{$_COOKIE['username']}'" : ''; ?>>
    <?php if (!empty($user_er)): ?>
        <span style="color: red"><?php echo htmlspecialchars($user_er) ?></span>
    <?php endif; ?>
    <label for="password">密码</label>
    <input id="password" name="password" type="password" placeholder="字母/数字/下划线 4~10位">
    <?php if (!empty($pwd_er)): ?>
        <span style="color: red"><?php echo htmlspecialchars($pwd_er) ?></span>
    <?php endif; ?>

    <label for="rem">
        <input id='rem' name='rem' type="checkbox" value="1">
        记住账号
    </label>
    <?php if (!empty($sc)): ?>
        <span style="color: green"><?php echo htmlspecialchars($sc) ?></span>
    <?php endif; ?>
    <div class="button-group">
        <button type="submit">登录</button>
        <button type="button" onclick="window.location.href='Register.php'">注册</button>
        <button type="button" onclick="window.location.href='Reset.php'">重置密码</button>
    </div>

</form>

</body>
</html>