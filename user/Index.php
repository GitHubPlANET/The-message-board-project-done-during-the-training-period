<?php
include('../connect.php');
session_start();
if(empty($_SESSION['username'])){
    header("location:Login.php");
    exit;
}
$username = $_SESSION['username'];
$id = $_SESSION['id'];
$role = $_SESSION['role'];
$sc ='';
$eor = '';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $content = $_POST['content']??'';
    if(!empty($content)){
        $content = trim(htmlspecialchars($content));
        $sql = "INSERT INTO msg (user_id,username,content) VALUES (:user_id,:username,:content)";
        $stmt = $pdo->prepare($sql);
        $result= $stmt->execute([
                ':user_id' => $id,
                ':username' => $username,
                ':content' => $content
        ]);
        if($result){
            $sc ='留言成功';
        }else{
            $error_msg= $stmt->errorInfo();
            $error = $error_msg[2];
        }
    }else{
        $eor = '留言内容不能为空';
    }
}

// 模糊查询参数
$keyword = isset($_GET['keyword']) ? trim(htmlspecialchars($_GET['keyword'])) : '';

// 分页参数
$pagesize = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $pagesize;

// 获取总记录数
$user_id = $_SESSION['id'];
$keyword_param = '%'.$keyword.'%';
if($_SESSION['role']==0||$_SESSION['role']==2){
    if(!empty($keyword)){
        $count_sql = "SELECT COUNT(*) FROM msg WHERE user_id = :user_id AND (username LIKE :keyword1 OR content LIKE :keyword2)";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([':user_id' => $user_id, ':keyword1' => $keyword_param, ':keyword2' => $keyword_param]);
    }else{
        $count_sql = "SELECT COUNT(*) FROM msg WHERE user_id = :user_id";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([':user_id' => $user_id]);
    }
} else {
    if(!empty($keyword)){
        $count_sql = "SELECT COUNT(*) FROM msg WHERE username LIKE :keyword1 OR content LIKE :keyword2";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([':keyword1' => $keyword_param, ':keyword2' => $keyword_param]);
    }else{
        $count_sql = "SELECT COUNT(*) FROM msg";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute();
    }
}
$total = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total / $pagesize));

// 修正页码超出范围
if($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $pagesize;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>首页</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
<!-- 顶部导航栏 -->
<nav class="navbar">
    <div class="navbar-brand"><?php echo htmlspecialchars($username); ?>的留言板</div>
    <form class="search-form" method="get" action="">
        <input type="text" name="keyword" class="search-input" placeholder="搜索留言内容或用户名..." value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit" class="search-btn">搜索</button>
        <?php if(!empty($keyword)): ?>
            <button type="button" class="search-reset" onclick="window.location.href='Index.php'">清除</button>
        <?php endif; ?>
    </form>
    <div class="navbar-menu">
        <?php if ($role != 0 ):?>
            <a href="Admin.php" class="nav-link">进入后台</a>
        <?php endif;?>
        <a href="Revise.php" class="nav-link">修改密码</a>
        <a href="Logout.php" class="nav-link">退出登录</a>
    </div>
</nav>

<div class="container">
    <form method="post" class="msg-form">
        <label for="content">发布留言</label>
        <textarea id="content" name="content" placeholder="说点什么吧..."></textarea>
        <div class="form-actions">
            <button class="btn-primary" type="submit">提交留言</button>
        </div>

        <?php if (!empty($sc)):?>
            <span class="success"><?php echo htmlspecialchars($sc);?></span>
        <?php endif;?>
        <?php if (!empty($eor)):?>
            <span class="error"><?php echo htmlspecialchars($eor);?></span>
        <?php endif;?>
    </form>

    <?php
    if($_SESSION['role']==0||$_SESSION['role']==2){
        if(!empty($keyword)){
            $sql = "SELECT * FROM msg WHERE user_id = :user_id AND (username LIKE :keyword1 OR content LIKE :keyword2) ORDER BY create_time DESC LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':keyword1', $keyword_param, PDO::PARAM_STR);
            $stmt->bindValue(':keyword2', $keyword_param, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }else{
            $sql = "SELECT * FROM msg WHERE user_id = :user_id ORDER BY create_time DESC LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }
    }else{
        if(!empty($keyword)){
            $sql = "SELECT * FROM msg WHERE username LIKE :keyword1 OR content LIKE :keyword2 ORDER BY create_time DESC LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':keyword1', $keyword_param, PDO::PARAM_STR);
            $stmt->bindValue(':keyword2', $keyword_param, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }else{
            $sql = "SELECT * FROM msg ORDER BY create_time DESC LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    if($stmt->rowCount() > 0){
        $rows = $stmt->fetchAll();
        foreach($rows as $row){
            $msg_id = $row['id'];
            echo "<div class='msg-card'>";
            echo "<div class='msg-header'>";
            echo "<span class='msg-author'>" . htmlspecialchars($row['username']) . "</span>";
            echo "<span class='msg-time'>" . htmlspecialchars($row['create_time']) . "</span>";
            echo "</div>";
            echo "<div class='msg-content'>" . htmlspecialchars($row['content']) . "</div>";
            echo "<div class='msg-actions'><a href='Delete_msg.php?id=" . (int)$msg_id . "' class='btn-delete' onclick='return confirm(\"确认删除吗？\")'>删除</a></div>";
            echo "</div>";
        }
    } else {
        echo "<div class='empty-msg'>暂无留言，快来发布第一条吧！</div>";
    }
    ?>

    <!-- 分页导航 -->
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=1<?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">首页</a>
            <a href="?page=<?php echo $page-1; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">上一页</a>
        <?php endif; ?>
        
        <span class="page-info">第 <?php echo $page; ?> / <?php echo $total_pages; ?> 页<?php if(!empty($keyword)): ?> (搜索: <?php echo htmlspecialchars($keyword); ?>)<?php endif; ?></span>
        
        <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">下一页</a>
            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">末页</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>