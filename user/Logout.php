<?php
session_start();
if(empty($_SESSION['username'])){
    header("Refresh:1, url=Login.php");
    exit;
}
$_SESSION = array();
session_unset();

if(ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(),'',time()-42000,$params['path'],$params['domain'],$params['secure'],$params['httponly']);
}
session_destroy();
header("Location:Login.php");
?>
