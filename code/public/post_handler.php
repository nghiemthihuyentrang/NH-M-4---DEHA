<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đăng bài']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $post_type = $_POST['post_type'] ?? '';
    $price = $_POST['price'] ?? null;
    $condition_item = $_POST['condition_item'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Validate dữ liệu
    if (empty($title) || empty($content) || empty($category_id) || empty($post_type) || empty($condition_item) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        exit;
    }
    
    // Xử lý upload ảnh
    $uploadedImages = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadDir = '../../uploads/posts/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        $maxFiles = 5;
        
        $fileCount = count($_FILES['images']['name']);
        if ($fileCount > $maxFiles) {
            echo json_encode(['success' => false, 'message' => 'Chỉ được upload tối đa 5 hình ảnh']);
            exit;
        }
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['images']['tmp_name'][$i];
                $fileName = $_FILES['images']['name'][$i];
                $fileSize = $_FILES['images']['size'][$i];
                $fileType = $_FILES['images']['type'][$i];
                
                // Kiểm tra loại file
                if (!in_array($fileType, $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ file ảnh (JPEG, PNG, GIF)']);
                    exit;
                }
                
                // Kiểm tra kích thước file
                if ($fileSize > $maxFileSize) {
                    echo json_encode(['success' => false, 'message' => 'Mỗi file không được quá 2MB']);
                    exit;
                }
                
                // Tạo tên file unique
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $uniqueFileName;
                
                // Upload file
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    // Lưu đường dẫn relative (không có ../../)
                    $uploadedImages[] = 'uploads/posts/' . $uniqueFileName;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi upload hình ảnh']);
                    exit;
                }
            }
        }
    }
    
    // Chuyển đổi mảng ảnh thành JSON
    $imagesJson = json_encode($uploadedImages);
    
    // Xử lý giá
    if (empty($price) || $price <= 0) {
        $price = null;
    }
    
    try {
        // Thêm bài đăng vào database
        $stmt = $pdo->prepare("
            INSERT INTO posts (user_id, title, content, category_id, post_type, price, condition_item, location, images, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $title,
            $content,
            $category_id,
            $post_type,
            $price,
            $condition_item,
            $location,
            $imagesJson
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Đăng bài thành công!']);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu bài đăng: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
}
?>