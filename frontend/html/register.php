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
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate input
    if (empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Họ và tên không được để trống']);
        exit;
    }
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email không được để trống']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
        exit;
    }
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không được để trống']);
        exit;
    }
    
    if (strlen($username) < 3) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập phải có ít nhất 3 ký tự']);
        exit;
    }
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu không được để trống']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
        exit;
    }
    
    // Kiểm tra email đã tồn tại
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
        exit;
    }
    
    // Kiểm tra username đã tồn tại
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã được sử dụng']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Thêm user mới vào database
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, phone, created_at, status) 
        VALUES (?, ?, ?, ?, ?, NOW(), 'active')
    ");
    
    $result = $stmt->execute([$username, $email, $hashed_password, $full_name, $phone]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Đăng ký thành công! Vui lòng đăng nhập.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi tạo tài khoản']);
    }
    
} catch(PDOException $e) {
    error_log("Register error: " . $e->getMessage());
    
    // Xử lý lỗi duplicate entry
    if ($e->getCode() == 23000) {
        if (strpos($e->getMessage(), 'email') !== false) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
        } elseif (strpos($e->getMessage(), 'username') !== false) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã được sử dụng']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Thông tin đã tồn tại trong hệ thống']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi đăng ký. Vui lòng thử lại sau.']);
    }
}
?>