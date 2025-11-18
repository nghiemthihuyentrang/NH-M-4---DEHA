<?php
// File setup database cho hệ thống tin nhắn liên hệ

// Kết nối database
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔗 Kết nối database thành công!\n";
    
    // Tạo bảng contact_messages với cấu trúc đầy đủ
    $createTable = "
        CREATE TABLE IF NOT EXISTS contact_messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            admin_reply TEXT,
            replied_at DATETIME,
            replied_by INT,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            INDEX idx_updated_at (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTable);
    echo "✅ Tạo bảng contact_messages thành công!\n";
    
    // Kiểm tra cấu trúc bảng hiện tại
    $checkTable = $pdo->query("DESCRIBE contact_messages");
    $columns = $checkTable->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Cấu trúc bảng contact_messages:\n";
    echo "+" . str_repeat("-", 60) . "+\n";
    printf("| %-20s | %-15s | %-10s | %-8s |\n", "Cột", "Kiểu dữ liệu", "Null", "Key");
    echo "+" . str_repeat("-", 60) . "+\n";
    
    foreach ($columns as $column) {
        printf("| %-20s | %-15s | %-10s | %-8s |\n", 
               $column['Field'], 
               $column['Type'], 
               $column['Null'], 
               $column['Key']);
    }
    echo "+" . str_repeat("-", 60) . "+\n";
    
    // Kiểm tra và thêm các cột thiếu (nếu bảng đã tồn tại trước đó)
    $existingColumns = array_column($columns, 'Field');
    
    $requiredColumns = [
        'admin_reply' => 'TEXT',
        'replied_at' => 'DATETIME',
        'replied_by' => 'INT'
    ];
    
    foreach ($requiredColumns as $colName => $colType) {
        if (!in_array($colName, $existingColumns)) {
            $alterQuery = "ALTER TABLE contact_messages ADD COLUMN $colName $colType";
            $pdo->exec($alterQuery);
            echo "✅ Đã thêm cột: $colName ($colType)\n";
        } else {
            echo "ℹ️  Cột $colName đã tồn tại\n";
        }
    }
    
    // Thêm một số dữ liệu mẫu (nếu bảng trống)
    $countStmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
    $messageCount = $countStmt->fetchColumn();
    
    if ($messageCount == 0) {
        echo "\n📝 Thêm dữ liệu mẫu...\n";
        
        $sampleMessages = [
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@email.com',
                'phone' => '0123456789',
                'subject' => 'support',
                'message' => 'Chào admin, tôi gặp vấn đề khi đăng bài bán laptop. Có thể hỗ trợ tôi được không?',
                'status' => 'unread'
            ],
            [
                'name' => 'Trần Thị B',
                'email' => 'tranthib@email.com',
                'phone' => '0987654321',
                'subject' => 'feedback',
                'message' => 'Website rất hữu ích! Tôi muốn góp ý thêm tính năng so sánh giá sản phẩm.',
                'status' => 'read'
            ],
            [
                'name' => 'Lê Văn C',
                'email' => 'levanc@email.com',
                'phone' => '',
                'subject' => 'partnership',
                'message' => 'Công ty chúng tôi muốn hợp tác quảng cáo sản phẩm điện tử. Vui lòng liên hệ.',
                'status' => 'replied',
                'admin_reply' => 'Cảm ơn bạn đã quan tâm. Chúng tôi sẽ liên hệ qua email trong 24h tới.',
                'replied_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        foreach ($sampleMessages as $msg) {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, status, admin_reply, replied_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $msg['name'],
                $msg['email'],
                $msg['phone'],
                $msg['subject'],
                $msg['message'],
                $msg['status'],
                $msg['admin_reply'] ?? null,
                $msg['replied_at'] ?? null
            ]);
        }
        
        echo "✅ Đã thêm " . count($sampleMessages) . " tin nhắn mẫu\n";
    } else {
        echo "\nℹ️  Bảng đã có $messageCount tin nhắn\n";
    }
    
    // Tạo index bổ sung nếu cần
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contact_status_created ON contact_messages(status, created_at)");
        echo "✅ Đã tạo index tối ưu hóa truy vấn\n";
    } catch (PDOException $e) {
        // Index có thể đã tồn tại
    }
    
    echo "\n🎉 Setup database hoàn tất!\n";
    echo "📊 Thống kê tin nhắn:\n";
      $stats = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM contact_messages 
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stats as $stat) {
        $statusLabels = [
            'unread' => 'Chưa đọc',
            'read' => 'Đã đọc',
            'replied' => 'Đã trả lời'
        ];
        echo "  - " . ($statusLabels[$stat['status']] ?? $stat['status']) . ": " . $stat['count'] . " tin nhắn\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Bạn có thể truy cập admin panel tại: http://localhost/uht3/frontend/html/admin.php\n";
?>