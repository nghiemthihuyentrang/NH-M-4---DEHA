<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra user đã đăng nhập chưa
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để tạo bài thảo luận!']);
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Lấy dữ liệu từ form
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $tags = trim($_POST['tags'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        // Validate dữ liệu
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tiêu đề!']);
            exit;
        }
        
        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung!']);
            exit;
        }
        
        if (strlen($title) < 10) {
            echo json_encode(['success' => false, 'message' => 'Tiêu đề phải có ít nhất 10 ký tự!']);
            exit;
        }
        
        if (strlen($content) < 20) {
            echo json_encode(['success' => false, 'message' => 'Nội dung phải có ít nhất 20 ký tự!']);
            exit;
        }
        
        // Kiểm tra category_id có tồn tại không
        if ($category_id > 0) {
            $category_check = $pdo->prepare("SELECT category_id FROM categories WHERE category_id = ?");
            $category_check->execute([$category_id]);
            if (!$category_check->fetch()) {
                $category_id = null;
            }
        } else {
            $category_id = null;
        }
        
        // ✅ SỬA TẠI ĐÂY: Insert vào bảng forum_topics thay vì posts
        $stmt = $pdo->prepare("
            INSERT INTO forum_topics (user_id, title, content, category_id, status, tags, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $user_id,
            $title,
            $content,
            $category_id,
            $tags
        ]);
        
        if ($result) {
            $topic_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Bài thảo luận đã được tạo thành công và đang chờ admin duyệt!',
                'topic_id' => $topic_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi tạo bài thảo luận!']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    }
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống! Vui lòng thử lại sau.']);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>