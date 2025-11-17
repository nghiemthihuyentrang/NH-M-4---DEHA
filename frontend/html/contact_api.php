<?php
session_start();

// Kiểm tra xem user đã đăng nhập và có quyền admin không
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in'] || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
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
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

// Tạo bảng contact_messages nếu chưa có
try {
    $createTable = "
        CREATE TABLE IF NOT EXISTS contact_messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
            admin_reply TEXT,
            replied_at DATETIME,
            replied_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($createTable);
} catch(PDOException $e) {
    error_log("Contact messages table setup error: " . $e->getMessage());
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_messages':
        getMessages();
        break;
    case 'mark_read':
        markAsRead();
        break;
    case 'mark_multiple_read':
        markMultipleAsRead();
        break;
    case 'mark_all_unread_as_read':
        markAllUnreadAsRead();
        break;
    case 'reply':
        replyMessage();
        break;
    case 'delete':
        deleteMessage();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

function getMessages() {
    global $pdo;
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $status = $_GET['status'] ?? 'all';
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    try {
        // Build WHERE clause
        $whereClause = '';
        $params = [];
        
        if ($status !== 'all') {
            $whereClause = 'WHERE status = :status';
            $params['status'] = $status;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM contact_messages $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalMessages = $countStmt->fetchColumn();
        
        // Get messages
        $sql = "SELECT message_id, name, email, phone, subject, message, status, 
                       created_at, admin_reply, replied_at,
                       CASE 
                           WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 
                           THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' phút trước')
                           WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 24 
                           THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' giờ trước')
                           WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) < 7 
                           THEN CONCAT(TIMESTAMPDIFF(DAY, created_at, NOW()), ' ngày trước')
                           ELSE DATE_FORMAT(created_at, '%d/%m/%Y %H:%i')
                       END as time_ago
                FROM contact_messages 
                $whereClause 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as `read`,
                SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied
            FROM contact_messages
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Pagination info
        $totalPages = ceil($totalMessages / $limit);
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_messages' => $totalMessages,
            'per_page' => $limit
        ];
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'stats' => $stats,
            'pagination' => $pagination
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
    }
}

function markAsRead() {
    global $pdo;
    
    $messageId = intval($_POST['message_id'] ?? 0);
    
    if (!$messageId) {
        echo json_encode(['success' => false, 'message' => 'ID tin nhắn không hợp lệ']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE message_id = ?");
        $result = $stmt->execute([$messageId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Đã đánh dấu tin nhắn là đã đọc']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật tin nhắn']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
    }
}

function markMultipleAsRead() {
    global $pdo;
    
    $messageIds = $_POST['message_ids'] ?? '';
    
    if (empty($messageIds)) {
        echo json_encode(['success' => false, 'message' => 'Không có tin nhắn nào được chọn']);
        return;
    }
    
    try {
        $messageIds = json_decode($messageIds, true);
        if (!is_array($messageIds)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }
        
        // Validate all IDs are integers
        $messageIds = array_filter(array_map('intval', $messageIds));
        
        if (empty($messageIds)) {
            echo json_encode(['success' => false, 'message' => 'Không có ID tin nhắn hợp lệ']);
            return;
        }
        
        $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE message_id IN ($placeholders) AND status = 'unread'");
        $result = $stmt->execute($messageIds);
        
        $updatedCount = $stmt->rowCount();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => "Đã đánh dấu $updatedCount tin nhắn là đã đọc"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật tin nhắn']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
    }
}

function markAllUnreadAsRead() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE status = 'unread'");
        $result = $stmt->execute();
        
        $updatedCount = $stmt->rowCount();
        
        if ($result) {
            if ($updatedCount > 0) {
                echo json_encode(['success' => true, 'message' => "Đã đánh dấu tất cả $updatedCount tin nhắn chưa đọc là đã đọc"]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Không có tin nhắn chưa đọc nào để cập nhật']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật tin nhắn']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
    }
}

function replyMessage() {
    global $pdo;
    
    $messageId = intval($_POST['message_id'] ?? 0);
    $replyContent = trim($_POST['reply_content'] ?? '');
    $adminId = $_SESSION['user_id'] ?? 0;
    
    if (!$messageId || !$replyContent) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE contact_messages 
            SET status = 'replied', 
                admin_reply = ?, 
                replied_at = NOW(), 
                replied_by = ?,
                updated_at = NOW()
            WHERE message_id = ?
        ");
        $result = $stmt->execute([$replyContent, $adminId, $messageId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Đã gửi phản hồi thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể gửi phản hồi']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
    }
}

function deleteMessage() {
    global $pdo;
    
    $messageId = intval($_POST['message_id'] ?? 0);
    
    if (!$messageId) {
        echo json_encode(['success' => false, 'message' => 'ID tin nhắn không hợp lệ']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE message_id = ?");
        $result = $stmt->execute([$messageId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa tin nhắn thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tin nhắn để xóa']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
    }
}
?>