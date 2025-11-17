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
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
        exit;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
        exit;
    }
    
    // Kiểm tra username và email đã tồn tại
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại']);
        exit;
    }
    
    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Thêm user mới
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone])) {
        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công! Vui lòng đăng nhập.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi đăng ký']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
}
?>