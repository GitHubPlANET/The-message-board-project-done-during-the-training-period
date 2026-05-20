<?php

$host = "localhost";
$dbname = "wm";
$username = "root";
$password = "root";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try{
    $pdo = new PDO($dsn, $username, $password, $options);
}catch (PDOException $e){
    die('数据库连接失败'.$e->getMessage());
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
$stmt->execute(['admin']);
$admin = $stmt->fetch();

if(!$admin){
    $insert = $pdo->prepare("INSERT INTO user (username, password,age,email,role) VALUES (?, ?, ?, ?, ?)");
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert ->execute(['admin',$password,'11','1605401297@qq.com','1']);
}

?>
