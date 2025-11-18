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
    
    // Tìm bài viết theo tiêu đề
    $search_title = "Tư vấn mua Laptop Dell Latitude E7450 cũ có nên không?";
    
    // Tìm bài viết
    $find_stmt = $pdo->prepare("SELECT post_id, title, images FROM posts WHERE title = ?");
    $find_stmt->execute([$search_title]);
    $post = $find_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài viết với tiêu đề này']);
        exit;
    }
    
    $post_id = $post['post_id'];
    
    // Xóa hình ảnh liên quan nếu có
    if (!empty($post['images'])) {
        $images = json_decode($post['images'], true);
        if (is_array($images)) {
            foreach ($images as $image) {
                if (file_exists($image)) {
                    unlink($image);
                    error_log("Deleted image: $image");
                }
            }
        }
    }
    
    // Xóa comments liên quan
    $delete_comments = $pdo->prepare("DELETE FROM post_comments WHERE post_id = ?");
    $delete_comments->execute([$post_id]);
    
    // Xóa likes liên quan
    $delete_likes = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ?");
    $delete_likes->execute([$post_id]);
    
    // Xóa bài viết
    $delete_post = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
    $result = $delete_post->execute([$post_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => "Đã xóa thành công bài viết: '{$post['title']}'",
            'post_id' => $post_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa bài viết']);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi database: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>