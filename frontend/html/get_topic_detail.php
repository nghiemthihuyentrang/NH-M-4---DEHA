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
    
    $topic_id = $_GET['id'] ?? null;
    
    if (!$topic_id) {
        echo json_encode(['success' => false, 'message' => 'ID bài viết không hợp lệ']);
        exit;
    }
    
    // Lấy thông tin bài viết từ forum_topics
    $stmt = $pdo->prepare("
        SELECT ft.*, u.full_name, u.email, c.category_name
        FROM forum_topics ft
        LEFT JOIN users u ON ft.user_id = u.user_id
        LEFT JOIN categories c ON ft.category_id = c.category_id
        WHERE ft.topic_id = ? AND ft.status = 'active'
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại hoặc chưa được duyệt']);
        exit;
    }
    
    // Format thời gian
    function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'vừa xong';
        } elseif ($time < 3600) {
            return round($time/60) . ' phút trước';
        } elseif ($time < 86400) {
            return round($time/3600) . ' giờ trước';
        } elseif ($time < 2629746) {
            return round($time/86400) . ' ngày trước';
        } else {
            return date('d/m/Y', strtotime($datetime));
        }
    }    // Lấy comments của bài thảo luận từ bảng topic_comments
    $comments_stmt = $pdo->prepare("
        SELECT tc.*, u.full_name, u.email,
               0 as likes
        FROM topic_comments tc 
        LEFT JOIN users u ON tc.user_id = u.user_id 
        WHERE tc.topic_id = ? 
        ORDER BY tc.created_at ASC
    ");
    $comments_stmt->execute([$topic_id]);
    $comments_raw = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format comments
    $comments = [];
    foreach ($comments_raw as $comment) {
        $comments[] = [
            'id' => $comment['comment_id'],
            'content' => $comment['content'],
            'author' => $comment['full_name'] ?? $comment['email'] ?? 'Ẩn danh',
            'time_ago' => timeAgo($comment['created_at']),
            'likes' => $comment['likes'] ?? 0
        ];
    }
    
    // Tăng số lượt xem (có thể implement sau)
    
    $response = [
        'success' => true,
        'topic' => [
            'title' => $topic['title'],
            'content' => $topic['content'],
            'author' => $topic['full_name'] ?? $topic['email'] ?? 'Ẩn danh',
            'time_ago' => timeAgo($topic['created_at']),
            'views' => $topic['views'] ?? 0,
            'likes' => 0, // Tạm thời để 0, có thể implement sau
            'category' => $topic['category_name'] ?? 'Tổng hợp'
        ],
        'comments' => $comments
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>