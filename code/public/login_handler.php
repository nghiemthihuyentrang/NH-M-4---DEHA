<?php
session_start();

// Kết nối database
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = $_POST['login_input'] ?? '';
    $password_input = $_POST['password'] ?? '';
    
    if (empty($login_input) || empty($password_input)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
        exit;
    }
    
    // Tìm user bằng email hoặc username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password_input, $user['password'])) {
        // Đăng nhập thành công
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_logged_in'] = true;
        
        // Kiểm tra xem có phải admin không
        $is_admin = ($user['username'] == 'admin' || $user['email'] == 'admin@electroreview.vn');
        
        if ($is_admin) {
            $_SESSION['is_admin'] = true;
            echo json_encode([
                'success' => true, 
                'message' => 'Đăng nhập thành công!',
                'redirect' => 'admin.php',
                'is_admin' => true
            ]);
        } else {
            $_SESSION['is_admin'] = false;
            echo json_encode([
                'success' => true, 
                'message' => 'Đăng nhập thành công!',
                'redirect' => 'index.php',
                'is_admin' => false
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
}
?>