<?php
session_start();
header('Content-Type: application/json');

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kết nối database
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy dữ liệu từ form
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validate input
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email hoặc tên đăng nhập không được để trống']);
        exit;
    }
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu không được để trống']);
        exit;
    }    // Tìm user trong database (có thể login bằng email hoặc username)
    // Kiểm tra xem cột is_admin có tồn tại không
    $columnsCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'")->rowCount();
    $hasAdminColumn = $columnsCheck > 0;
    
    if ($hasAdminColumn) {
        $stmt = $pdo->prepare("
            SELECT user_id, username, email, password, full_name, is_admin, status 
            FROM users 
            WHERE (email = ? OR username = ?) 
            LIMIT 1
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT user_id, username, email, password, full_name, status 
            FROM users 
            WHERE (email = ? OR username = ?) 
            LIMIT 1
        ");
    }
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email/tên đăng nhập hoặc mật khẩu không đúng']);
        exit;
    }
    
    // Kiểm tra tài khoản có bị khóa không
    if ($user['status'] === 'banned') {
        echo json_encode(['success' => false, 'message' => 'Tài khoản của bạn đã bị khóa']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email/tên đăng nhập hoặc mật khẩu không đúng']);
        exit;
    }    // Đăng nhập thành công - lưu session
    $_SESSION['is_logged_in'] = true;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    
    // Kiểm tra admin status
    if ($hasAdminColumn && isset($user['is_admin'])) {
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
    } else {
        // Fallback: check theo username/email nếu chưa có cột is_admin
        $_SESSION['is_admin'] = ($user['username'] === 'admin' || $user['email'] === 'admin@electroreview.com');
    }
    
    // Cập nhật last login nếu cột tồn tại
    try {
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$user['user_id']]);
    } catch (PDOException $e) {
        // Ignore if last_login column doesn't exist
    }
      echo json_encode([
        'success' => true, 
        'message' => 'Đăng nhập thành công!',
        'user' => [
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'is_admin' => $_SESSION['is_admin']
        ],
        'redirect' => $_SESSION['is_admin'] ? 'admin.php' : null
    ]);
    
} catch(PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi đăng nhập. Vui lòng thử lại sau.']);
}
?>