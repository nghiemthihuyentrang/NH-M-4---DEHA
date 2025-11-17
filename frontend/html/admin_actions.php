<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in'] || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
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
    
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? 'all'; // 'all', 'discussion', 'listing'
    
    // Log để debug
    error_log("Admin action: $action, type: $type");
    
    switch ($action) {
        case 'approve':
            $post_id = $_POST['post_id'] ?? 0;
            
            if ($type === 'discussion') {
                // Duyệt bài thảo luận từ forum_topics
                $stmt = $pdo->prepare("UPDATE forum_topics SET status = 'active' WHERE topic_id = ?");
                $stmt->execute([$post_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đã duyệt bài thảo luận']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài thảo luận']);
                }
            } else {
                // Duyệt bài đăng từ posts
                $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE post_id = ?");
                $stmt->execute([$post_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đã duyệt bài đăng']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài đăng']);
                }
            }
            break;
            
        case 'reject':
            $post_id = $_POST['post_id'] ?? 0;
            
            if ($type === 'discussion') {
                // Từ chối bài thảo luận từ forum_topics
                $stmt = $pdo->prepare("UPDATE forum_topics SET status = 'rejected' WHERE topic_id = ?");
                $stmt->execute([$post_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đã từ chối bài thảo luận']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài thảo luận']);
                }
            } else {
                // Từ chối bài đăng từ posts
                $stmt = $pdo->prepare("UPDATE posts SET status = 'rejected' WHERE post_id = ?");
                $stmt->execute([$post_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đã từ chối bài đăng']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài đăng']);
                }
            }
            break;
            
        case 'delete':
            $post_id = $_POST['post_id'] ?? 0;
            
            if ($type === 'discussion') {
                // Xóa bài thảo luận từ forum_topics
                $stmt = $pdo->prepare("DELETE FROM forum_topics WHERE topic_id = ?");
                $stmt->execute([$post_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đã xóa bài thảo luận']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài thảo luận']);
                }
            } else {
                // Xóa bài đăng từ posts
                $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
                $stmt->execute([$post_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đã xóa bài đăng']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài đăng']);
                }
            }
            break;
            
        case 'approve_selected':
            $post_ids = json_decode($_POST['post_ids'] ?? '[]', true);
            
            if (empty($post_ids)) {
                echo json_encode(['success' => false, 'message' => 'Không có bài đăng nào được chọn']);
                break;
            }
            
            $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
            
            if ($type === 'discussion') {
                // Duyệt nhiều bài thảo luận
                $stmt = $pdo->prepare("UPDATE forum_topics SET status = 'active' WHERE topic_id IN ($placeholders)");
                $stmt->execute($post_ids);
                
                $count = $stmt->rowCount();
                echo json_encode(['success' => true, 'message' => "Đã duyệt $count bài thảo luận"]);
            } else {
                // Duyệt nhiều bài đăng
                $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE post_id IN ($placeholders)");
                $stmt->execute($post_ids);
                
                $count = $stmt->rowCount();
                echo json_encode(['success' => true, 'message' => "Đã duyệt $count bài đăng"]);
            }
            break;
            
        case 'toggle_user_status':
            $user_id = $_POST['user_id'] ?? 0;
            
            // Kiểm tra trạng thái hiện tại
            $stmt = $pdo->prepare("SELECT is_active FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $new_status = $user['is_active'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
                $stmt->execute([$new_status, $user_id]);
                
                $status_text = $new_status ? 'kích hoạt' : 'khóa';
                echo json_encode(['success' => true, 'message' => "Đã $status_text tài khoản người dùng"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng']);
            }
            break;
            
        case 'delete_user':
            $user_id = $_POST['user_id'] ?? 0;
            
            // Kiểm tra không phải admin
            $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && !$user['is_admin']) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đã xóa tài khoản người dùng']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản admin']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch(PDOException $e) {
    error_log("Database error in admin_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
}
?>