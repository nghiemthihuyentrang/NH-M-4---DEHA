<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để bình luận']);
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
    exit;
}

// Lấy dữ liệu từ form
$post_id = $_POST['post_id'] ?? 0;
$content = trim($_POST['content'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;

// Validate dữ liệu
if (!$post_id || $post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID bài viết không hợp lệ']);
    exit;
}

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung bình luận']);
    exit;
}

if (strlen($content) < 5) {
    echo json_encode(['success' => false, 'message' => 'Bình luận phải có ít nhất 5 ký tự']);
    exit;
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập không hợp lệ']);
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
    
    // Tạo bảng topic_comments nếu chưa có
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS topic_comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        topic_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_topic_id (topic_id),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (topic_id) REFERENCES forum_topics(topic_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_table_sql);
    
    // Kiểm tra bài thảo luận có tồn tại và đã được duyệt hay không
    $check_stmt = $pdo->prepare("
        SELECT ft.topic_id, ft.title, ft.status, u.full_name, u.email 
        FROM forum_topics ft 
        LEFT JOIN users u ON ft.user_id = u.user_id 
        WHERE ft.topic_id = ? AND ft.status = 'active'
    ");
    $check_stmt->execute([$post_id]);
    $topic = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại hoặc chưa được duyệt!']);
        exit;
    }
    
    // Kiểm tra user có tồn tại hay không
    $user_stmt = $pdo->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản không hợp lệ']);
        exit;
    }
    
    // Thêm bình luận vào database (sử dụng bảng topic_comments)
    $insert_stmt = $pdo->prepare("
        INSERT INTO topic_comments (topic_id, user_id, content, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $insert_stmt->execute([$post_id, $user_id, $content]);
    
    // Lấy ID của comment vừa tạo
    $comment_id = $pdo->lastInsertId();
    
    // Tạo thông tin comment để trả về
    $comment_data = [
        'id' => $comment_id,
        'content' => $content,
        'author' => $user['full_name'] ?? $user['email'] ?? 'Ẩn danh',
        'time_ago' => 'vừa xong',
        'likes' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Bình luận đã được đăng thành công!',
        'comment' => $comment_data
    ]);
    
} catch(PDOException $e) {
    error_log("Database error in post_comment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("General error in post_comment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>