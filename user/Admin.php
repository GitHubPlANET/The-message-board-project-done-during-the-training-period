<?php
include('../connect.php');
session_start();

// 权限验证
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
$keyword = isset($_GET['keyword']) ? trim(htmlspecialchars($_GET['keyword'])) : '';

// 获取用户列表
function get_user_list($pdo, $role_filter, $keyword = '', $page = 1, $page_size = 5) {
    $params = ['role' => $role_filter];
    $where = "WHERE role = :role";
    
    if (!empty($keyword)) {
        $where .= " AND username LIKE :keyword";
        $params['keyword'] = '%' . $keyword . '%';
    }
    
    // 获取总数
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM user " . $where);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // 分页计算
    $total_pages = max(1, ceil($total / $page_size));
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $page_size;
    
    // 获取数据
    $sql = "SELECT * FROM user " . $where . " LIMIT :offset, :pagesize";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params + ['offset' => $offset, 'pagesize' => $page_size]);
    
    return [
        'data' => $stmt->fetchAll(),
        'total' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page
    ];
}

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
        $check_stmt = $pdo->prepare("SELECT id FROM user WHERE username = :username");
        $check_stmt->execute(['username' => $new_username]);
        if ($check_stmt->rowCount() > 0) {
            $er = '用户名已存在';
        } else {
            // 检查邮箱是否存在
            $check_stmt = $pdo->prepare("SELECT id FROM user WHERE email = :email");
            $check_stmt->execute(['email' => $new_email]);
            if ($check_stmt->rowCount() > 0) {
                $er = '邮箱已存在';
            } else {
                // 插入新用户
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $insert_stmt = $pdo->prepare("INSERT INTO user (username, email, password, age, role) VALUES (:username, :email, :password, :age, :role)");
                $result = $insert_stmt->execute([
                    'username' => $new_username,
                    'email' => $new_email,
                    'password' => $hashed_password,
                    'age' => $new_age,
                    'role' => $new_role
                ]);
                $sc = $result ? '用户创建成功' : '用户创建失败';
            }
        }
    }
}

// 文件上传处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_btn'])) {
    $files = $_FILES['file'];
    
    if (empty($files['name'][0])) {
        $er = '请选择文件!';
    } else {
        $allowed_ext = ['jpg', 'png', 'jpeg', 'gif'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
        $allowed_size = 1024 * 1024 * 20;
        
        foreach ($files['name'] as $key => $name) {
            if (empty($files['name'][$key])) continue;
            
            $file_name = $files['name'][$key];
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $temp_name = $files['tmp_name'][$key];
            $file_size = $files['size'][$key];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($finfo, $temp_name);
            finfo_close($finfo);
            
            if ($file_size > $allowed_size) {
                $er = $file_name . '文件超出最大限制20M';
                break;
            }
            
            if (in_array($ext, $allowed_ext) && in_array($file_mime, $allowed_mime)) {
                if (!is_dir('upload/')) mkdir('upload/', 0777, true);
                $new_file_name = 'upload/' . bin2hex(random_bytes(6)) . "." . $ext;
                if (!move_uploaded_file($temp_name, $new_file_name)) {
                    $er = $file_name . '文件上传失败!';
                    break;
                }
                $file_path[] = $new_file_name;
            } else {
                $er = '文件类型不符,仅支持上传图片';
                break;
            }
        }
    }
    
    if (empty($er) && !empty($file_path)) {
        $id = $_SESSION['id'];
        $str_path = implode(',', $file_path);
        
        $stmt_old = $pdo->prepare("SELECT file_path FROM user WHERE id=:id");
        $stmt_old->execute(['id' => $id]);
        $old_path = $stmt_old->fetchColumn();
        if ($old_path) $str_path = $old_path . ',' . $str_path;
        
        $stmt = $pdo->prepare("UPDATE user SET file_path=:str_path WHERE id=:id");
        $result = $stmt->execute(['str_path' => $str_path, 'id' => $id]);
        $sc = $result ? '文件上传成功!' : '文件上传失败!';
    }
}

// 获取用户列表数据
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$page_size = 5;
$role_filter = ($type == 'admin') ? 2 : 0;
$user_data = get_user_list($pdo, $role_filter, $keyword, $page, $page_size);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>管理后台</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">管理后台</div>
    <form class="search-form" method="get" action="">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
        <input type="text" name="keyword" class="search-input" placeholder="搜索用户名..." value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit" class="search-btn">搜索</button>
        <?php if (!empty($keyword)): ?>
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
        <?php if (!empty($sc)): ?><span class="success"><?php echo htmlspecialchars($sc) ?></span><?php endif; ?>
        <?php if (!empty($er)): ?><span class="error"><?php echo htmlspecialchars($er) ?></span><?php endif; ?>
    </form>

    <div class="tab-buttons">
        <a href="?type=user" class="tab-btn <?php echo $type == 'user' ? 'active' : '' ?>">普通用户</a>
        <a href="?type=admin" class="tab-btn <?php echo $type == 'admin' ? 'active' : '' ?>">管理员</a>
    </div>

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

    <table class="table-box">
        <tr>
            <th><?php echo $type == 'admin' ? '管理员' : '普通用户'; ?></th>
            <th>邮箱</th>
            <th>年龄</th>
            <?php if ($type == 'user' || $role == 1): ?><th>操作</th><?php endif; ?>
        </tr>
        <?php if (count($user_data['data']) > 0): ?>
            <?php foreach ($user_data['data'] as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                    <?php if ($type == 'user' || $role == 1): ?>
                        <td>
                            <?php if ($type == 'user'): ?>
                                <a class="edit" href="Edit.php?id=<?php echo (int)$row['id']; ?>&role=2" onclick="return confirm('确认修改吗')">设为管理员</a>
                            <?php else: ?>
                                <a class="edit" href="Edit.php?id=<?php echo (int)$row['id']; ?>&role=0" onclick="return confirm('确认修改吗')">取消管理员</a>
                            <?php endif; ?>
                            <a class="del" href="Delete_user.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('确认删除吗')">删除用户</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo ($type == 'user' || $role == 1) ? '4' : '3'; ?>" style="text-align: center; color: #999;">
                    暂无<?php echo $type == 'admin' ? '管理员' : '普通用户'; ?>
                </td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="pagination">
        <?php if ($user_data['current_page'] > 1): ?>
            <a href="?type=<?php echo $type; ?>&page=1<?php echo !empty($keyword) ? '&keyword=' . urlencode($keyword) : ''; ?>" class="page-btn">首页</a>
            <a href="?type=<?php echo $type; ?>&page=<?php echo $user_data['current_page'] - 1; ?><?php echo !empty($keyword) ? '&keyword=' . urlencode($keyword) : ''; ?>" class="page-btn">上一页</a>
        <?php endif; ?>
        <span class="page-info">第 <?php echo $user_data['current_page']; ?> / <?php echo $user_data['total_pages']; ?> 页 (共 <?php echo $user_data['total']; ?> 条)</span>
        <?php if ($user_data['current_page'] < $user_data['total_pages']): ?>
            <a href="?type=<?php echo $type; ?>&page=<?php echo $user_data['current_page'] + 1; ?><?php echo !empty($keyword) ? '&keyword=' . urlencode($keyword) : ''; ?>" class="page-btn">下一页</a>
            <a href="?type=<?php echo $type; ?>&page=<?php echo $user_data['total_pages']; ?><?php echo !empty($keyword) ? '&keyword=' . urlencode($keyword) : ''; ?>" class="page-btn">末页</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
