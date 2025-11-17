<?php
session_start();
header('Content-Type: application/json');

// Kết nối database
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tạo bảng contact_messages nếu chưa có
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS contact_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        subject VARCHAR(500) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        admin_reply TEXT,
        replied_at DATETIME,
        replied_by INT,
        
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_table_sql);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Lấy dữ liệu từ form
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validate dữ liệu
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập họ và tên!']);
            exit;
        }
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email!']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ!']);
            exit;
        }
        
        if (empty($subject)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn chủ đề!']);
            exit;
        }
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung tin nhắn!']);
            exit;
        }
        
        if (strlen($name) < 2) {
            echo json_encode(['success' => false, 'message' => 'Họ tên phải có ít nhất 2 ký tự!']);
            exit;
        }
        
        if (strlen($message) < 10) {
            echo json_encode(['success' => false, 'message' => 'Tin nhắn phải có ít nhất 10 ký tự!']);
            exit;
        }
        
        // Kiểm tra spam (không gửi quá 3 tin nhắn trong 1 giờ từ cùng email)
        $spam_check = $pdo->prepare("
            SELECT COUNT(*) FROM contact_messages 
            WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $spam_check->execute([$email]);
        $recent_messages = $spam_check->fetchColumn();
        
        if ($recent_messages >= 3) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã gửi quá nhiều tin nhắn trong giờ qua. Vui lòng thử lại sau!']);
            exit;
        }
        
        // Lưu tin nhắn vào database
        $insert_stmt = $pdo->prepare("
            INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $insert_stmt->execute([$name, $email, $phone, $subject, $message]);
        
        if ($result) {
            $message_id = $pdo->lastInsertId();
            
            // Log activity cho admin
            error_log("New contact message received: ID $message_id from $email");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cảm ơn bạn đã gửi tin nhắn! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.',
                'message_id' => $message_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại!']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    }
    
} catch(PDOException $e) {
    error_log("Database error in contact_submit.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống! Vui lòng thử lại sau.']);
} catch(Exception $e) {
    error_log("General error in contact_submit.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>