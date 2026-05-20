<?php
include('../connect.php');
session_start();
if (empty($_SESSION['username'])) {
    header("location:Login.php");
    exit;
}
if ($_SESSION['role'] == 0) {
    header("location:Index.php");
    exit;
}

$type = $_GET['type'] ?? ($_POST['type'] ?? 'user');
$role = $_SESSION['role'];
$file_path = [];
$sc = '';
$er = '';

// 模糊查询参数
$keyword = isset($_GET['keyword']) ? trim(htmlspecialchars($_GET['keyword'])) : '';

// 新建用户处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_email = trim($_POST['new_email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $new_age = (int)($_POST['new_age'] ?? 0);
    $new_role = (int)($_POST['new_role'] ?? 0);
    
    if (empty($new_username)) {
        $er = '用户名不能为空';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,10}$/', $new_username)) {
        $er = '用户名需为4-10位字母、数字或下划线';
    } elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $er = '请输入有效的邮箱地址';
    } elseif (empty($new_password) || !preg_match('/^[a-zA-Z0-9_]{4,10}$/', $new_password)) {
        $er = '密码需为4-10位字母、数字或下划线';
    } else {
        // 检查用户名是否存在
        $check_sql = "SELECT id FROM user WHERE username = :username";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute(['username' => $new_username]);
        if ($check_stmt->rowCount() > 0) {
            $er = '用户名已存在';
        } else {
            // 检查邮箱是否存在
            $check_sql = "SELECT id FROM user WHERE email = :email";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute(['email' => $new_email]);
            if ($check_stmt->rowCount() > 0) {
                $er = '邮箱已存在';
            } else {
                // 插入新用户
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $insert_sql = "INSERT INTO user (username, email, password, age, role) VALUES (:username, :email, :password, :age, :role)";
                $insert_stmt = $pdo->prepare($insert_sql);
                $result = $insert_stmt->execute([
                    'username' => $new_username,
                    'email' => $new_email,
                    'password' => $hashed_password,
                    'age' => $new_age,
                    'role' => $new_role
                ]);
                if ($result) {
                    $sc = '用户创建成功';
                } else {
                    $er = '用户创建失败';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['upload_btn'])) {
        $files = $_FILES['file'];

        if ($files['name'][0] == '') {
            $er = '请选择文件!';
        } else {
            $allowed_ext = ['jpg', 'png', 'jpeg', 'gif'];
            $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
            $allowed_size = 1024 * 1024 * 20;

            foreach ($files['name'] as $key => $name) {
                if(empty($files['name'][$key])) {
                    continue;
                }
                $file_name = $files['name'][$key];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $temp_name = $files['tmp_name'][$key];
                $file_size = $files['size'][$key];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $file_mime = finfo_file($finfo, $temp_name);
                finfo_close($finfo);

                if($file_size > $allowed_size) {
                    $er = $file_name.'文件超出最大限制20M';
                    break;
                }

                if (in_array($ext, $allowed_ext)&&in_array($file_mime, $allowed_mime)) {
                    if(!is_dir('upload/')){
                        mkdir('upload/', 0777, true);
                    }

                    $New_File_Name = 'upload/'.bin2hex(random_bytes(6)).".".$ext;
                    if(move_uploaded_file($temp_name, $New_File_Name)) {
                        $file_path[] = $New_File_Name;
                    }else{
                        $er = $file_name.'文件上传失败!';
                        $sc = 'False';
                        break;
                    }
                }else{
                    $er = '文件类型不符,仅支持上传图片';
                    break;
                }
            }
        }
    }

    if(empty($er)&&!empty($file_path)) {
        $id = $_SESSION['id'];
        $str_path = implode(',', $file_path);

        $stmt_old = $pdo->prepare("SELECT file_path FROM user WHERE id=:id");
        $stmt_old->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_old->execute();
        $old_path = $stmt_old->fetchColumn();

        if($old_path) {
            $str_path = $old_path . ',' . $str_path;
        }

        $sql = "UPDATE user SET file_path=:str_path WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':str_path', $str_path, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $result=$stmt->execute();
        if($result){
            $sc = '文件上传成功!';
        }else{
            $er='文件上传失败!';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>管理后台</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
<!-- 顶部导航栏 -->
<nav class="navbar">
    <div class="navbar-brand">管理后台</div>
    <form class="search-form" method="get" action="">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
        <input type="text" name="keyword" class="search-input" placeholder="搜索用户名..." value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit" class="search-btn">搜索</button>
        <?php if(!empty($keyword)): ?>
            <button type="button" class="search-reset-btn" onclick="window.location.href='Admin.php?type=<?php echo htmlspecialchars($type); ?>'">清除</button>
        <?php endif; ?>
    </form>
    <div class="navbar-menu">
        <a href="Index.php" class="nav-link">返回首页</a>
        <a href="Logout.php" class="nav-link">退出登录</a>
    </div>
</nav>

<div class="container">
    <h1 class="page-title admin-title">欢迎管理员<?php echo htmlspecialchars($_SESSION['username']) ?>进入后台!</h1>

    <form method="post" enctype="multipart/form-data" class="upload-form">
        <div class="upload-container" onclick="document.getElementById('file').click()">
            <div class="upload-btn">选择文件</div>
            <div class="upload-tips">支持多图上传，仅支持 jpg、png、gif，最大20M</div>
            <input type="file" name="file[]" id="file" multiple>
        </div>

        <div class="upload-submit">
            <button type="submit" name="upload_btn" class="btn-primary">确认上传</button>
        </div>

        <?php if(!empty($sc)): ?>
            <span class="success"><?php echo htmlspecialchars($sc)?></span>
        <?php endif; ?>
        <?php if(!empty($er)): ?>
            <span class="error"><?php echo htmlspecialchars($er)?></span>
        <?php endif; ?>
    </form>

    <div class="tab-buttons">
        <a href="?type=user" class="tab-btn <?php echo $type == 'user' ? 'active' : '' ?>">普通用户</a>
        <a href="?type=admin" class="tab-btn <?php echo $type == 'admin' ? 'active' : '' ?>">管理员</a>
    </div>

    <!-- 新建用户表单 -->
    <div class="add-user-form">
        <h3>新建用户</h3>
        <form method="post">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="new_username" placeholder="4-10位字母数字下划线" required>
                </div>
                <div class="form-group">
                    <label>邮箱</label>
                    <input type="email" name="new_email" placeholder="请输入邮箱" required>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="new_password" placeholder="4-10位字母数字下划线" required>
                </div>
                <div class="form-group">
                    <label>年龄</label>
                    <input type="number" name="new_age" placeholder="请输入年龄" min="0" max="150">
                </div>
                <div class="form-group">
                    <label>角色</label>
                    <select name="new_role">
                        <option value="0">普通用户</option>
                        <option value="2">管理员</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_user" class="btn-primary">创建用户</button>
        </form>
    </div>

    <?php
    // 分页参数
    $pagesize = 5;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $pagesize;
    $keyword_param = '%'.$keyword.'%';
    
    if ($type == "user") {
        // 获取总记录数
        if(!empty($keyword)){
            $count_sql = "SELECT COUNT(*) FROM user WHERE role = 0 AND username LIKE :keyword";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute([':keyword' => $keyword_param]);
        }else{
            $count_sql = "SELECT COUNT(*) FROM user WHERE role = 0";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute();
        }
        $total = $count_stmt->fetchColumn();
        $total_pages = max(1, ceil($total / $pagesize));
        
        if($page > $total_pages) $page = $total_pages;
        $offset = ($page - 1) * $pagesize;
        
        if(!empty($keyword)){
            $sql = "SELECT * FROM user WHERE role = 0 AND username LIKE :keyword LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':keyword', $keyword_param, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }else{
            $sql = "SELECT * FROM user WHERE role = 0 LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }
        $rows = $stmt->fetchAll();
        ?>

        <table class="table-box">
            <tr>
                <th>普通用户</th>
                <th>邮箱</th>
                <th>年龄</th>
                <th>操作</th>
            </tr>
            <?php if (count($rows) > 0): ?>
                <?php foreach ($rows as $row) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['age']); ?></td>
                        <td>
                                <a class="edit" href="Edit.php?id=<?php echo (int)$row['id']; ?>&role=2" onclick="return confirm('确认修改吗')">设为管理员</a>
                                <a class="del" href="Delete_user.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('确认删除吗')">删除用户</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center; color: #999;">暂无普通用户</td></tr>
            <?php endif; ?>
        </table>

        <!-- 分页导航 -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?type=user&page=1<?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">首页</a>
                <a href="?type=user&page=<?php echo $page-1; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">上一页</a>
            <?php endif; ?>
            
            <span class="page-info">第 <?php echo $page; ?> / <?php echo $total_pages; ?> 页 (共 <?php echo $total; ?> 条)</span>
            
            <?php if($page < $total_pages): ?>
                <a href="?type=user&page=<?php echo $page+1; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">下一页</a>
                <a href="?type=user&page=<?php echo $total_pages; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">末页</a>
            <?php endif; ?>
        </div>

        <?php
    } elseif ($type == "admin") {
        // 获取总记录数
        if(!empty($keyword)){
            $count_sql = "SELECT COUNT(*) FROM user WHERE role = 2 AND username LIKE :keyword";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute([':keyword' => $keyword_param]);
        }else{
            $count_sql = "SELECT COUNT(*) FROM user WHERE role = 2";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute();
        }
        $total = $count_stmt->fetchColumn();
        $total_pages = max(1, ceil($total / $pagesize));
        
        if($page > $total_pages) $page = $total_pages;
        $offset = ($page - 1) * $pagesize;
        
        if(!empty($keyword)){
            $sql = "SELECT * FROM user WHERE role = 2 AND username LIKE :keyword LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':keyword', $keyword_param, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }else{
            $sql = "SELECT * FROM user WHERE role = 2 LIMIT :offset, :pagesize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':pagesize', $pagesize, PDO::PARAM_INT);
            $stmt->execute();
        }
        $rows = $stmt->fetchAll();
        ?>

        <table class="table-box">
            <tr>
                <th>管理员</th>
                <th>邮箱</th>
                <th>年龄</th>
                <?php if ($role == 1) { ?>
                <th>操作</th>
                <?php } ?>
            </tr>
            <?php if (count($rows) > 0): ?>
                <?php foreach ($rows as $row) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['age']); ?></td>
                        <?php if ($role == 1) { ?>
                        <td>
                                <a class="edit" href="Edit.php?id=<?php echo (int)$row['id']; ?>&role=0" onclick="return confirm('确认修改吗')">取消管理员</a>
                                <a class="del" href="Delete_user.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('确认删除吗')">删除用户</a>
                        </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            <?php else: ?>
                <tr><td colspan="<?php echo $role == 1 ? '4' : '3'; ?>" style="text-align: center; color: #999;">暂无管理员</td></tr>
            <?php endif; ?>
        </table>

        <!-- 分页导航 -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?type=admin&page=1<?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">首页</a>
                <a href="?type=admin&page=<?php echo $page-1; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">上一页</a>
            <?php endif; ?>
            
            <span class="page-info">第 <?php echo $page; ?> / <?php echo $total_pages; ?> 页 (共 <?php echo $total; ?> 条)</span>
            
            <?php if($page < $total_pages): ?>
                <a href="?type=admin&page=<?php echo $page+1; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">下一页</a>
                <a href="?type=admin&page=<?php echo $total_pages; ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?>" class="page-btn">末页</a>
            <?php endif; ?>
        </div>
    <?php } ?>
</div>
</body>
</html>