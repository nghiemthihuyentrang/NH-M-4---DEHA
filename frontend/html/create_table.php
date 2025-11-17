<?php
// File tạo bảng topic_comments tự động
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 Tạo bảng topic_comments</h1>";
    
    // Kiểm tra bảng đã tồn tại chưa
    $check_table = $pdo->query("SHOW TABLES LIKE 'topic_comments'");
    if ($check_table->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ Bảng 'topic_comments' đã tồn tại!</p>";
    } else {
        echo "<p>📋 Đang tạo bảng 'topic_comments'...</p>";
        
        // Tạo bảng topic_comments
        $create_sql = "
        CREATE TABLE topic_comments (
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
        
        $pdo->exec($create_sql);
        echo "<p style='color: green;'>✅ Bảng 'topic_comments' đã được tạo thành công!</p>";
    }
    
    // Kiểm tra cấu trúc bảng
    echo "<h2>📊 Cấu trúc bảng topic_comments:</h2>";
    $structure = $pdo->query("DESCRIBE topic_comments");
    echo "<table style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Field</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Type</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Null</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Key</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Default</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Extra</th>";
    echo "</tr>";
    
    while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Field'] . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Type'] . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Null'] . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Key'] . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Default'] . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Thêm comment demo nếu bảng trống
    $count = $pdo->query("SELECT COUNT(*) FROM topic_comments")->fetchColumn();
    if ($count == 0) {
        echo "<h2>🎬 Thêm dữ liệu demo:</h2>";
        
        // Kiểm tra có topic nào không
        $topic_count = $pdo->query("SELECT COUNT(*) FROM forum_topics WHERE status = 'active'")->fetchColumn();
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        if ($topic_count > 0 && $user_count > 0) {
            // Lấy topic_id và user_id đầu tiên
            $first_topic = $pdo->query("SELECT topic_id FROM forum_topics WHERE status = 'active' LIMIT 1")->fetchColumn();
            $first_user = $pdo->query("SELECT user_id FROM users LIMIT 1")->fetchColumn();
            
            $demo_comments = [
                "Bài viết rất hay! Cảm ơn bạn đã chia sẻ kinh nghiệm.",
                "Mình cũng có trải nghiệm tương tự. Thông tin rất hữu ích!",
                "Chất lượng bài viết tốt, hy vọng có thêm nhiều bài như thế này."
            ];
            
            foreach ($demo_comments as $comment) {
                $pdo->prepare("INSERT INTO topic_comments (topic_id, user_id, content) VALUES (?, ?, ?)")
                    ->execute([$first_topic, $first_user, $comment]);
            }
            
            echo "<p style='color: green;'>✅ Đã thêm " . count($demo_comments) . " comment demo!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Không có topic hoặc user để tạo comment demo.</p>";
        }
    } else {
        echo "<p>📊 Bảng đã có $count comments.</p>";
    }
    
    echo "<br><h2>🎉 Hoàn thành!</h2>";
    echo "<p>Bây giờ bạn có thể:</p>";
    echo "<ul>";
    echo "<li>✅ Bình luận trên các bài thảo luận</li>";
    echo "<li>✅ Xem số lượng comment chính xác</li>";
    echo "<li>✅ Hệ thống comment hoạt động đầy đủ</li>";
    echo "</ul>";
    
    echo "<p><a href='test_query.php' style='color: blue;'>🔍 Test query lại</a> | ";
    echo "<a href='thao-luan.php' style='color: blue;'>💬 Về trang thảo luận</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>