<?php
session_start();
header('Content-Type: application/json');

// Debug log function
function debugLog($message) {
    error_log("[Admin Actions Debug] " . $message);
}

// Kiểm tra quyền adminn
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in'] || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
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
    
    // Debug: Log received data
    debugLog("Received POST data: " . print_r($_POST, true));
    
    $action = $_POST['action'] ?? '';
    debugLog("Action: $action");
    
    // Handle bulk actions first
    if ($action === 'approve_selected') {
        $post_ids_json = $_POST['post_ids'] ?? '';
        debugLog("Bulk action - post_ids: $post_ids_json");
        
        $post_ids = json_decode($post_ids_json, true);
        
        if (empty($post_ids) || !is_array($post_ids)) {
            echo json_encode(['success' => false, 'message' => 'Không có bài đăng nào được chọn']);
            exit;
        }
        
        // Sanitize IDs
        $post_ids = array_map('intval', $post_ids);
        $post_ids = array_filter($post_ids, function($id) { return $id > 0; });
        
        if (empty($post_ids)) {
            echo json_encode(['success' => false, 'message' => 'ID bài đăng không hợp lệ']);
            exit;
        }
        
        debugLog("Validated post IDs: " . implode(', ', $post_ids));
        
        // Execute bulk approve
        try {
            $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE post_id IN ($placeholders)");
            $result = $stmt->execute($post_ids);
            
            if ($result) {
                $count = count($post_ids);
                debugLog("Bulk approve successful - $count posts updated");
                echo json_encode(['success' => true, 'message' => "Đã duyệt $count bài đăng thành công"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi duyệt các bài đăng']);
            }
        } catch (Exception $e) {
            debugLog("Bulk approve error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi khi duyệt hàng loạt: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Handle single post actions
    $post_id = $_POST['post_id'] ?? '';
    debugLog("Single action - Post ID raw: $post_id");
    
    if (empty($post_id)) {
        echo json_encode(['success' => false, 'message' => 'Không có ID bài đăng']);
        exit;
    }
    
    $post_id = intval($post_id);
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => "ID bài đăng không hợp lệ: $post_id"]);
        exit;
    }
    
    debugLog("Validated Post ID: $post_id");
    
    // Kiểm tra xem bài đăng có tồn tại không
    $check_stmt = $pdo->prepare("SELECT post_id, status FROM posts WHERE post_id = ?");
    $check_stmt->execute([$post_id]);
    $existing_post = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing_post) {
        echo json_encode(['success' => false, 'message' => "Không tìm thấy bài đăng với ID: $post_id"]);
        exit;
    }
    
    debugLog("Found post: " . print_r($existing_post, true));
    
    // Execute single post actions
    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE post_id = ?");
            $result = $stmt->execute([$post_id]);
            
            debugLog("Approve result: " . ($result ? 'success' : 'failed'));
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => "Đã duyệt bài đăng #$post_id thành công"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi duyệt bài đăng']);
            }
            break;
            
        case 'reject':
            $stmt = $pdo->prepare("UPDATE posts SET status = 'rejected' WHERE post_id = ?");
            $result = $stmt->execute([$post_id]);
            
            debugLog("Reject result: " . ($result ? 'success' : 'failed'));
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => "Đã từ chối bài đăng #$post_id"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi từ chối bài đăng']);
            }
            break;
            
        case 'delete':
            // Xóa hình ảnh liên quan trước
            $stmt = $pdo->prepare("SELECT images FROM posts WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post && !empty($post['images'])) {
                $images = json_decode($post['images'], true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        if (file_exists($image)) {
                            unlink($image);
                            debugLog("Deleted image: $image");
                        }
                    }
                }
            }
            
            // Xóa bài đăng
            $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
            $result = $stmt->execute([$post_id]);
            
            debugLog("Delete result: " . ($result ? 'success' : 'failed'));
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => "Đã xóa bài đăng #$post_id thành công"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa bài đăng']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => "Hành động không hợp lệ: $action"]);
            break;
    }
    
} catch(PDOException $e) {
    debugLog("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
} catch(Exception $e) {
    debugLog("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>