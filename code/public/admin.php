<?php
// Khởi tạo session để quản lý trạng thái đăng nhập
session_start();

// Kiểm tra quyền truy cập: user phải đăng nhập và có quyền admin
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in'] || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // Nếu không có quyền, chuyển hướng về trang chủ
    header('Location: index.php');
    exit;
}

// Cấu hình kết nối database
$host = 'localhost';           // Địa chỉ server database
$dbname = 'electroreview_db';  // Tên database
$username = 'root';            // Username database
$password = '';                // Password database (trống vs XAMPP)

try {
    // Tạo kết nối PDO với database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);    // Thiết lập chế độ lỗi cho PDO để debug dễ dàng hơn
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== LẤY THỐNG KÊ TỔNG QUAN CHO DASHBOARD =====
    $stats = [];
    try {
        // Đếm tổng số bài đăng mua bán từ bảng posts
        $stats['total_posts'] = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        
        // Đếm tổng số bài thảo luận từ bảng forum_topics
        $stats['total_discussions'] = $pdo->query("SELECT COUNT(*) FROM forum_topics")->fetchColumn();
        
        // Đếm tổng số người dùng từ bảng users
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        // Đếm số bài đăng chờ duyệt (status = 'pending')
        $stats['pending_posts'] = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'")->fetchColumn();
        
        // Đếm số bài thảo luận chờ duyệt
        $stats['pending_discussions'] = $pdo->query("SELECT COUNT(*) FROM forum_topics WHERE status = 'pending'")->fetchColumn();
        
        // Kiểm tra xem bảng contact_messages có tồn tại không trước khi query
        $tableExists = $pdo->query("SHOW TABLES LIKE 'contact_messages'")->rowCount();
        if ($tableExists > 0) {
            // Đếm số tin nhắn liên hệ chưa đọc
            $stats['total_messages'] = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
        } else {
            $stats['total_messages'] = 0;
        }
    } catch(PDOException $e) {
        // Nếu có lỗi trong việc query, sử dụng giá trị mặc định
        $stats = [
            'total_posts' => 0,
            'total_discussions' => 0,
            'total_users' => 0,
            'pending_posts' => 0,
            'pending_discussions' => 0,
            'total_messages' => 0
        ];
    }    
    // ===== LẤY DỮ LIỆU CHO CÁC BẢNG QUẢN LÝ =====
    
    // Lấy bài đăng gần đây từ database để hiển thị trên dashboard
    $recent_posts_stmt = $pdo->query("
        SELECT p.*, u.full_name, u.email 
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $recent_posts = $recent_posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy tất cả bài đăng cho phần quản lý bài đăng
    $all_posts_stmt = $pdo->query("
        SELECT p.*, u.full_name, u.email, c.category_name
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.created_at DESC
    ");
    $all_posts = $all_posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy các bài thảo luận từ bảng forum_topics
    try {
        $discussions_stmt = $pdo->query("
            SELECT ft.*, u.full_name, u.email, c.category_name,
                   ft.topic_id as post_id,
                   0 as comment_count,
                   0 as like_count
            FROM forum_topics ft 
            LEFT JOIN users u ON ft.user_id = u.user_id 
            LEFT JOIN categories c ON ft.category_id = c.category_id
            ORDER BY ft.created_at DESC
        ");
        $discussions = $discussions_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Fallback nếu không kết nối được database
        $discussions = [];
    }      // Lấy các bài đăng mua bán cho phần quản lý (chỉ listing)  
    try {
        $listings_stmt = $pdo->query("
            SELECT p.*, u.full_name, u.email, c.category_name,
                   COALESCE((SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id), 0) as comment_count,
                   COALESCE((SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id), 0) as like_count
            FROM posts p 
            LEFT JOIN users u ON p.user_id = u.user_id 
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.post_type = 'listing'
            ORDER BY p.created_at DESC
        ");
        $listings = $listings_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Fallback query without comment/like counts if tables don't exist
        try {
            $listings_stmt = $pdo->query("
                SELECT p.*, u.full_name, u.email, c.category_name,
                       0 as comment_count, 0 as like_count
                FROM posts p 
                LEFT JOIN users u ON p.user_id = u.user_id 
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.post_type = 'listing'
                ORDER BY p.created_at DESC
            ");
            $listings = $listings_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $listings = [];
        }
    }
    
    // Lấy danh sách người dùng từ database
    try {
        $users_stmt = $pdo->query("
            SELECT u.*, 
                   COALESCE((SELECT COUNT(*) FROM posts p WHERE p.user_id = u.user_id), 0) as post_count,
                   COALESCE((SELECT COUNT(*) FROM posts p WHERE p.user_id = u.user_id AND p.status = 'active'), 0) as active_post_count
            FROM users u 
            ORDER BY u.created_at DESC
        ");
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Fallback query without post counts if issues occur
        try {
            $users_stmt = $pdo->query("
                SELECT u.*, 0 as post_count, 0 as active_post_count
                FROM users u 
                ORDER BY u.created_at DESC
            ");
            $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $users = [];
        }
    }
    
} catch(PDOException $e) {
    // Nếu không kết nối được database, dùng số liệu mặc định
    $stats = [
        'total_posts' => 156,
        'total_users' => 89,
        'pending_posts' => 23,
        'total_messages' => 12
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ElectroReview</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* ===== LAYOUT CHÍNH ===== */
        
        /* Sidebar - Thanh điều hướng bên trái */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            padding: 20px 0;
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 0 20px 30px;
            border-bottom: 1px solid #455a64;
            margin-bottom: 30px;
        }

        .sidebar-brand h2 {
            color: white;
            font-size: 18px;
            margin: 0;
        }

        /* Menu chính trong sidebar */
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 2px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(52, 152, 219, 0.2);
            color: white;
            border-right: 3px solid #3498db;
        }

        .sidebar-menu i {
            width: 20px;
            margin-right: 12px;
        }

        /* Main Content - Nội dung chính bên phải sidebar */
        .main-content {
            margin-left: 260px; /* Để trống space cho sidebar */
            min-height: 100vh;
        }

        /* ===== HEADER - THANH ĐIỀU HƯỚNG TRÊN CÙNG ===== */
        
        /* Header chứa title và thông tin user */
        .admin-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
        }

        /* Thông tin người dùng ở góc phải header */
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Nút đăng xuất */
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* ===== DASHBOARD CARDS - CÁC THẺ THỐNG KÊ ===== */
        
        .dashboard-content {
            padding: 30px;
        }

        /* Grid layout cho các card thống kê */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Card thống kê individual */
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease; /* Hiệu ứng hover */
        }

        .card:hover {
            transform: translateY(-5px); /* Nâng card lên khi hover */
        }

        /* Icon trong card */
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        /* Màu sắc cho các loại card khác nhau */
        .card.blue .card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card.green .card-icon {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .card.orange .card-icon {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }

        .card.red .card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-info {
            flex: 1;
        }

        /* Số liệu thống kê */
        .card-number {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        /* Tiêu đề card */
        .card-title {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
        }

        /* ===== BẢNG DỮ LIỆU - DATA TABLES ===== */
        
        /* Container cho bảng gần đây */
        .recent-section {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Header của section */
        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #ecf0f1;
            background: #f8f9fa;
        }

        .section-title {
            margin: 0;
            color: #2c3e50;
            font-size: 18px;
        }

        /* Wrapper cho table responsive */
        .table-responsive {
            overflow-x: auto;
        }

        /* Styling cho bảng dữ liệu */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px 25px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        /* Header của bảng */
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        /* Nội dung bảng */
        .data-table td {
            color: #5a6c7d;
            font-size: 14px;
        }

        /* Hiệu ứng hover cho hàng */
        .data-table tr:hover {
            background: #f8f9fa;
        }

        /* ===== STATUS BADGES - NHÃN TRẠNG THÁI ===== */
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Các màu sắc cho trạng thái khác nhau */
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-sold {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* ===== BUTTONS - CÁC NÚT BẤM ===== */
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        /* Các loại button với màu sắc khác nhau */
        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        /* ===== HIỂN THỊ SECTIONS ===== */
        
        /* Ẩn tất cả sections mặc định */
        .content-section {
            display: none;
        }

        /* Hiện section đang active */
        .content-section.active {
            display: block;
        }

        /* ===== RESPONSIVE DESIGN - THIẾT KẾ ĐÁP ỨNG ===== */
        
        @media (max-width: 768px) {
            /* Ẩn sidebar trên mobile */
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            /* Hiện sidebar khi có class mobile-open */
            .sidebar.mobile-open {
                transform: translateX(0);
            }

            /* Main content chiếm full width trên mobile */
            .main-content {
                margin-left: 0;
            }

            /* Cards thành 1 cột trên mobile */
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #2c3e50;
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            background: #34495e;
            text-align: center;
            border-bottom: 1px solid #3d566e;
        }

        .sidebar-header h3 {
            margin-bottom: 5px;
            color: #3498db;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #3498db;
            color: white;
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .content {
            padding: 30px;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .card-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-title {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .card.blue { color: #3498db; }
        .card.green { color: #2ecc71; }
        .card.orange { color: #f39c12; }
        .card.red { color: #e74c3c; }

        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #3498db;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            margin: 0;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }

        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }

        .btn:hover {
            opacity: 0.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }        .status-active { background: #d5f4e6; color: #27ae60; }        .status-pending { background: #fef9e7; color: #f39c12; }
        .status-sold { background: #fadbd8; color: #e74c3c; }
        .status-rejected { background: #fadbd8; color: #e74c3c; }

        .badge-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            background: #f8f9fa;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .badge-count i {
            font-size: 0.7rem;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        /* Section Navigation */
        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.8rem;
            }

            .actions {
                flex-direction: column;
            }
        }

        .mobile-menu-btn {
            display: none;
            background: #3498db;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }

        /* Modal styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }        /* Badge for unread messages */
        #unread-count {
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
            display: none;
        }

        /* Unread message styling */
        tr[data-status="unread"] {
            border-left: 4px solid #ffc107 !important;
        }

        tr[data-status="unread"] td {
            font-weight: 600 !important;
        }

        .unread-indicator {
            color: #ffc107;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-laptop" style="font-size: 1.5rem; margin-bottom: 10px;"></i>
            <h3>ElectroReview</h3>
            <p>Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" onclick="showSection('dashboard')" class="active" id="dashboard-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>            <li><a href="#" onclick="showSection('posts')" id="posts-link">
                <i class="fas fa-newspaper"></i> Quản lý bài đăng
            </a></li>
            <li><a href="#" onclick="showSection('discussions')" id="discussions-link">
                <i class="fas fa-comments"></i> Quản lý thảo luận
            </a></li>            <li><a href="#" onclick="showSection('users')" id="users-link">
                <i class="fas fa-users"></i> Quản lý người dùng
            </a></li>            <li><a href="#" onclick="showSection('messages')" id="messages-link">
                <i class="fas fa-envelope"></i> Tin nhắn liên hệ
            </a></li>
            <li><a href="#" onclick="showSection('categories')" id="categories-link">
                <i class="fas fa-tags"></i> Quản lý danh mục
            </a></li>
            <li><a href="#" onclick="showSection('settings')" id="settings-link">
                <i class="fas fa-cog"></i> Cài đặt
            </a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 id="page-title">Dashboard</h1>
            </div>            <div class="user-info">
                <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></strong></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <div class="content">
            <!-- Dashboard Section -->
            <div id="dashboard" class="section active">                <div class="dashboard-cards">
                    <div class="card blue">
                        <div class="card-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="card-number"><?php echo $stats['total_posts']; ?></div>
                        <div class="card-title">Bài đăng mua bán</div>
                    </div>
                    <div class="card green">
                        <div class="card-icon"><i class="fas fa-comments"></i></div>
                        <div class="card-number"><?php echo $stats['total_discussions']; ?></div>
                        <div class="card-title">Bài thảo luận</div>
                    </div>
                    <div class="card orange">
                        <div class="card-icon"><i class="fas fa-users"></i></div>
                        <div class="card-number"><?php echo $stats['total_users']; ?></div>
                        <div class="card-title">Người dùng</div>
                    </div>
                    <div class="card red">
                        <div class="card-icon"><i class="fas fa-clock"></i></div>
                        <div class="card-number"><?php echo ($stats['pending_posts'] + $stats['pending_discussions']); ?></div>
                        <div class="card-title">Chờ duyệt</div>
                    </div>
                </div>

                <div class="table-container">                    <div class="table-header">
                        <h3>Bài đăng gần đây</h3>
                        <a href="admin_posts.php" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Quản lý bài đăng
                        </a>
                    </div>                    <table>
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th>Người đăng</th>
                                <th>Loại</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_posts)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
                                        Chưa có bài đăng nào
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_posts as $post): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($post['title'], 0, 50)) . (strlen($post['title']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($post['full_name'] ?? $post['email'] ?? 'Ẩn danh'); ?></td>
                                        <td><?php echo ucfirst($post['post_type']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                                <?php 
                                                $statuses = [
                                                    'pending' => 'Chờ duyệt',
                                                    'active' => 'Đã duyệt', 
                                                    'rejected' => 'Đã từ chối'
                                                ];
                                                echo $statuses[$post['status']] ?? 'Không xác định';
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Posts Management Section -->
            <div id="posts" class="section">
                <div class="table-container">
                    <div class="table-header">
                        <h3>Quản lý bài đăng</h3>
                        <div>
                            <select onchange="filterPosts(this.value)" style="padding: 8px; margin-right: 10px; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="all">Tất cả</option>
                                <option value="pending">Chờ duyệt</option>
                                <option value="active">Đã duyệt</option>
                                <option value="sold">Đã bán</option>
                            </select>
                            <button class="btn btn-success" onclick="approveSelected()">
                                <i class="fas fa-check"></i> Duyệt đã chọn
                            </button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" onclick="selectAll(this)"></th>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Người đăng</th>
                                <th>Loại</th>
                                <th>Giá</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>                        <tbody id="posts-table">                            <?php if (empty($all_posts)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px; color: #999;">
                                        Chưa có bài đăng nào
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_posts as $post): ?>
                                    <tr data-status="<?php echo $post['status']; ?>">
                                        <td><input type="checkbox" class="post-checkbox" data-id="<?php echo $post['post_id']; ?>"></td>
                                        <td>#<?php echo str_pad($post['post_id'], 3, '0', STR_PAD_LEFT); ?></td>                                        <td>                                            <div style="max-width: 300px;">
                                                <strong><?php echo htmlspecialchars(substr($post['title'], 0, 50)) . (strlen($post['title']) > 50 ? '...' : ''); ?></strong>
                                                <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                                                    <span style="color: <?php echo $post['post_type'] === 'listing' ? '#007bff' : '#28a745'; ?>; font-weight: 500;">
                                                        <?php echo $post['post_type'] === 'listing' ? 'Bài đăng mua bán' : 'Bài thảo luận'; ?>
                                                    </span> • <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 80)) . (strlen(strip_tags($post['content'])) > 80 ? '...' : ''); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['full_name'] ?? $post['email'] ?? 'Ẩn danh'); ?></td>
                                        <td>
                                            <span class="status-badge" style="background: #e3f2fd; color: #1976d2;">
                                                <?php echo ucfirst($post['post_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($post['price']): ?>
                                                <?php echo number_format($post['price'], 0, ',', '.'); ?> VNĐ
                                            <?php else: ?>
                                                Trao đổi
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                                <?php 
                                                $statuses = [
                                                    'pending' => 'Chờ duyệt',
                                                    'active' => 'Đã duyệt', 
                                                    'rejected' => 'Đã từ chối',
                                                    'sold' => 'Đã bán'
                                                ];
                                                echo $statuses[$post['status']] ?? 'Không xác định';
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></td>                                        <td class="actions">
                                            <?php if ($post['status'] === 'pending'): ?>
                                                <button class="btn btn-success" onclick="approvePost(<?php echo intval($post['post_id']); ?>)" title="Duyệt" data-post-id="<?php echo intval($post['post_id']); ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-warning" onclick="rejectPost(<?php echo intval($post['post_id']); ?>)" title="Từ chối" data-post-id="<?php echo intval($post['post_id']); ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-primary" onclick="viewPost(<?php echo intval($post['post_id']); ?>)" title="Xem" data-post-id="<?php echo intval($post['post_id']); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-danger" onclick="deletePost(<?php echo intval($post['post_id']); ?>)" title="Xóa" data-post-id="<?php echo intval($post['post_id']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>            </div>

            <!-- Discussions Management Section -->
            <div id="discussions" class="section">
                <div class="table-container">
                    <div class="table-header">
                        <h3>Quản lý thảo luận</h3>
                        <div>
                            <select onchange="filterDiscussions(this.value)" style="padding: 8px; margin-right: 10px; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="all">Tất cả</option>
                                <option value="pending">Chờ duyệt</option>
                                <option value="active">Đã duyệt</option>
                                <option value="rejected">Đã từ chối</option>
                            </select>
                            <button class="btn btn-success" onclick="approveSelectedDiscussions()">
                                <i class="fas fa-check"></i> Duyệt đã chọn
                            </button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" onclick="selectAllDiscussions(this)"></th>
                                <th>ID</th>
                                <th>Tiêu đề thảo luận</th>
                                <th>Người đăng</th>
                                <th>Danh mục</th>
                                <th>Bình luận</th>
                                <th>Lượt thích</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>                        <tbody id="discussions-table">
                            <?php if (empty($discussions)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 20px; color: #999;">
                                        Chưa có bài thảo luận nào
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($discussions as $discussion): ?>
                                    <tr data-status="<?php echo $discussion['status']; ?>">
                                        <td><input type="checkbox" class="discussion-checkbox" data-id="<?php echo $discussion['post_id']; ?>"></td>
                                        <td>#<?php echo str_pad($discussion['post_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <div style="max-width: 300px;">
                                                <strong><?php echo htmlspecialchars(substr($discussion['title'], 0, 50)) . (strlen($discussion['title']) > 50 ? '...' : ''); ?></strong>
                                                <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                                                    <?php echo htmlspecialchars(substr(strip_tags($discussion['content']), 0, 100)) . (strlen(strip_tags($discussion['content'])) > 100 ? '...' : ''); ?>
                                                </div>
                                                <?php if (!empty($discussion['tags'])): ?>
                                                    <div style="margin-top: 5px;">
                                                        <?php 
                                                        $tags = explode(',', $discussion['tags']);
                                                        foreach($tags as $tag): 
                                                            $tag = trim($tag);
                                                            if (!empty($tag)):
                                                        ?>
                                                            <span style="background: #e9ecef; color: #495057; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-right: 3px;">
                                                                <?php echo htmlspecialchars($tag); ?>
                                                            </span>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="width: 30px; height: 30px; border-radius: 50%; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                                    <?php echo strtoupper(substr($discussion['full_name'] ?? $discussion['email'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500; font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($discussion['full_name'] ?? 'Chưa cập nhật'); ?>
                                                    </div>
                                                    <div style="font-size: 0.7rem; color: #666;">
                                                        <?php echo htmlspecialchars($discussion['email'] ?? 'Không có email'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge" style="background: #e9ecef; color: #495057;">
                                                <?php echo htmlspecialchars($discussion['category_name'] ?? 'Tổng hợp'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-count">
                                                <i class="fas fa-comments"></i> <?php echo $discussion['comment_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-count">
                                                <i class="fas fa-heart"></i> <?php echo $discussion['like_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $discussion['status']; ?>">
                                                <?php 
                                                $statuses = [
                                                    'pending' => 'Chờ duyệt',
                                                    'active' => 'Đã duyệt', 
                                                    'rejected' => 'Đã từ chối'
                                                ];
                                                echo $statuses[$discussion['status']] ?? 'Không xác định';
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.9rem;">
                                                <?php echo date('d/m/Y', strtotime($discussion['created_at'])); ?>
                                                <div style="font-size: 0.7rem; color: #666;">
                                                    <?php echo date('H:i', strtotime($discussion['created_at'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="actions">
                                            <?php if ($discussion['status'] === 'pending'): ?>
                                                <button class="btn btn-success" onclick="approveDiscussion(<?php echo $discussion['post_id']; ?>)" title="Duyệt" data-post-id="<?php echo $discussion['post_id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-warning" onclick="rejectDiscussion(<?php echo $discussion['post_id']; ?>)" title="Từ chối" data-post-id="<?php echo $discussion['post_id']; ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-primary" onclick="viewDiscussion(<?php echo $discussion['post_id']; ?>)" title="Xem" data-post-id="<?php echo $discussion['post_id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-danger" onclick="deleteDiscussion(<?php echo $discussion['post_id']; ?>)" title="Xóa" data-post-id="<?php echo $discussion['post_id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>            <!-- Users Management Section -->
            <div id="users" class="section">
                <div class="table-container">
                    <div class="table-header">
                        <h3>Quản lý người dùng</h3>
                        <div>
                            <select onchange="filterUsers(this.value)" style="padding: 8px; margin-right: 10px; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="all">Tất cả</option>
                                <option value="active">Hoạt động</option>
                                <option value="inactive">Không hoạt động</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button class="btn btn-primary" onclick="addUser()">
                                <i class="fas fa-plus"></i> Thêm người dùng
                            </button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" onclick="selectAllUsers(this)"></th>
                                <th>ID</th>
                                <th>Thông tin người dùng</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Số bài đăng</th>
                                <th>Ngày tham gia</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="users-table">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px; color: #999;">
                                        Chưa có người dùng nào
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr data-status="<?php echo $user['is_admin'] ? 'admin' : 'active'; ?>">
                                        <td><input type="checkbox" class="user-checkbox" data-id="<?php echo $user['user_id']; ?>"></td>
                                        <td>#<?php echo str_pad($user['user_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <div style="max-width: 250px;">
                                                <strong><?php echo htmlspecialchars($user['full_name'] ?? 'Chưa cập nhật'); ?></strong>
                                                <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                                                    <i class="fas fa-user"></i> @<?php echo htmlspecialchars($user['username']); ?>
                                                    <?php if ($user['is_admin']): ?>
                                                        <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 5px;">
                                                            <i class="fas fa-crown"></i> ADMIN
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" style="color: #007bff; text-decoration: none;">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($user['phone']): ?>
                                                <a href="tel:<?php echo htmlspecialchars($user['phone']); ?>" style="color: #28a745; text-decoration: none;">
                                                    <?php echo htmlspecialchars($user['phone']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #999; font-style: italic;">Chưa cập nhật</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                                <span class="badge-count">
                                                    <i class="fas fa-newspaper"></i> <?php echo $user['post_count']; ?> tổng
                                                </span>
                                                <span class="badge-count" style="background: #d5f4e6; color: #27ae60;">
                                                    <i class="fas fa-check"></i> <?php echo $user['active_post_count']; ?> hoạt động
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.9rem;">
                                                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                                <div style="font-size: 0.7rem; color: #666;">
                                                    <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="status-badge" style="background: #dc3545; color: white;">
                                                    <i class="fas fa-crown"></i> Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-active">
                                                    <i class="fas fa-check-circle"></i> Hoạt động
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <button class="btn btn-primary" onclick="viewUser(<?php echo $user['user_id']; ?>)" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-info" onclick="editUser(<?php echo $user['user_id']; ?>)" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (!$user['is_admin']): ?>
                                                <button class="btn btn-warning" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>)" title="Khóa/Mở khóa">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                                <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['user_id']; ?>)" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 0.8rem; padding: 5px;">Bảo vệ</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>            </div>

            <!-- Messages Section -->
            <div id="messages" class="section">
                <div class="table-container">                    <div class="table-header">
                        <h3>Tin nhắn liên hệ</h3>                        <div>
                            <select id="message-filter" onchange="filterMessages(this.value)" style="padding: 8px; margin-right: 10px; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="all">Tất cả</option>
                                <option value="unread">Chưa đọc</option>
                                <option value="read">Đã đọc</option>
                                <option value="replied">Đã trả lời</option>
                            </select>
                            <button class="btn btn-secondary" onclick="markSelectedAsRead()">
                                <i class="fas fa-check"></i> Đánh dấu đã chọn
                            </button>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div style="padding: 20px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #dc3545;" id="unread-count">0</div>
                                <div style="font-size: 0.8rem; color: #666;">Chưa đọc</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #17a2b8;" id="read-count">0</div>
                                <div style="font-size: 0.8rem; color: #666;">Đã đọc</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;" id="replied-count">0</div>
                                <div style="font-size: 0.8rem; color: #666;">Đã trả lời</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #6c757d;" id="total-count">0</div>
                                <div style="font-size: 0.8rem; color: #666;">Tổng cộng</div>
                            </div>
                        </div>
                    </div>
                      <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" onclick="selectAllMessages(this)"></th>
                                <th>ID</th>
                                <th>Thông tin liên hệ</th>
                                <th>Chủ đề</th>
                                <th>Tin nhắn</th>
                                <th>Ngày gửi</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="messages-table">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                                    <i class="fas fa-spinner fa-spin"></i> Đang tải tin nhắn...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div id="messages-pagination" style="padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
                        <!-- Pagination will be loaded here -->
                    </div>
                </div>
                
                <!-- Message Detail Modal -->
                <div id="messageModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 800px;">
                        <div class="modal-header">
                            <h3><i class="fas fa-envelope"></i> Chi tiết tin nhắn</h3>
                            <span class="close" onclick="closeMessageModal()">&times;</span>
                        </div>
                        <div class="modal-body" id="messageModalBody">
                            <!-- Message content will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <!-- Reply Modal -->
                <div id="replyModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 600px;">
                        <div class="modal-header">
                            <h3><i class="fas fa-reply"></i> Trả lời tin nhắn</h3>
                            <span class="close" onclick="closeReplyModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="replyForm">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Trả lời đến:</label>
                                    <div id="reply-to-info" style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6;">
                                        <!-- Reply info will be loaded here -->
                                    </div>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nội dung phản hồi:</label>
                                    <textarea id="reply-content" style="width: 100%; min-height: 120px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Nhập nội dung phản hồi..."></textarea>
                                </div>
                                <div style="text-align: right;">
                                    <button type="button" class="btn btn-secondary" onclick="closeReplyModal()">Hủy</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Gửi phản hồi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>            </div>

            <!-- Categories Section -->
            <div id="categories" class="section">
                <div class="table-container">
                    <div class="table-header">
                        <h3>Quản lý danh mục</h3>
                        <button class="btn btn-primary" onclick="addCategory()">
                            <i class="fas fa-plus"></i> Thêm danh mục
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên danh mục</th>
                                <th>Mô tả</th>
                                <th>Số bài đăng</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#001</td>
                                <td>Laptop</td>
                                <td>Laptop các loại</td>
                                <td>25</td>
                                <td>10/11/2025</td>
                                <td class="actions">
                                    <button class="btn btn-primary" onclick="editCategory(1)" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteCategory(1)" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#002</td>
                                <td>Điện thoại</td>
                                <td>Smartphone và điện thoại</td>
                                <td>40</td>
                                <td>10/11/2025</td>
                                <td class="actions">
                                    <button class="btn btn-primary" onclick="editCategory(2)" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteCategory(2)" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#003</td>
                                <td>Phụ kiện</td>
                                <td>Phụ kiện điện tử</td>
                                <td>18</td>
                                <td>10/11/2025</td>
                                <td class="actions">
                                    <button class="btn btn-primary" onclick="editCategory(3)" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteCategory(3)" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Settings Section -->
            <div id="settings" class="section">
                <div class="table-container">
                    <div class="table-header">
                        <h3>Cài đặt hệ thống</h3>
                    </div>
                    <div style="padding: 30px;">
                        <h4>Cài đặt chung</h4>
                        <div style="margin: 20px 0;">
                            <label style="display: block; margin-bottom: 10px;">Tên website:</label>
                            <input type="text" value="ElectroReview" style="width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin: 20px 0;">
                            <label style="display: block; margin-bottom: 10px;">Email liên hệ:</label>
                            <input type="email" value="admin@electroreview.vn" style="width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div style="margin: 20px 0;">
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" checked> Tự động duyệt bài đăng
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox"> Cho phép đăng ký tài khoản mới
                            </label>
                        </div>
                        <button class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu cài đặt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>    <script>
        // ===== KHAI BÁO HÀM CHÍNH ===== 
        
        /* 
         * Hàm chuyển đổi giữa các section khác nhau trong admin panel
         * Tham số: sectionName - tên của section cần hiển thị (dashboard, posts, users, etc.)
         */
        function showSection(sectionName) {
            console.log('Chuyển đổi sang section:', sectionName);
            
            try {
                // Ẩn tất cả các section
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Xóa class active khỏi tất cả menu items
                document.querySelectorAll('.sidebar-menu a').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Hiển thị section được chọn
                const targetSection = document.getElementById(sectionName);
                if (targetSection) {
                    targetSection.classList.add('active');
                    console.log('Section activated:', sectionName);
                } else {
                    console.error('Section not found:', sectionName);
                }
                
                // Kích hoạt menu link tương ứng
                const linkElement = document.getElementById(sectionName + '-link');
                if (linkElement) {
                    linkElement.classList.add('active');
                    console.log('Menu link activated:', sectionName + '-link');
                } else {
                    console.error('Menu link not found:', sectionName + '-link');
                }
                
                // Cập nhật tiêu đề trang
                const titles = {
                    'dashboard': 'Dashboard',
                    'posts': 'Quản lý bài đăng',
                    'discussions': 'Quản lý thảo luận',
                    'users': 'Quản lý người dùng',
                    'messages': 'Tin nhắn liên hệ',
                    'categories': 'Quản lý danh mục',
                    'settings': 'Cài đặt'
                };
                const pageTitle = document.getElementById('page-title');
                if (pageTitle) {
                    pageTitle.textContent = titles[sectionName] || 'Admin Panel';
                    console.log('Page title updated to:', titles[sectionName]);
                }
                
                // Tải dữ liệu cho các section cụ thể
                if (sectionName === 'messages') {
                    setTimeout(() => loadMessages(1, 'all'), 100);
                }
                
            } catch (error) {
                console.error('Error in showSection:', error);
            }
        }        // ===== XỬ LÝ LỖI VÀ GIAO DIỆN MOBILE =====
        
        // Hàm toggle sidebar cho mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.toggle('open');
        }        // Xử lý lỗi toàn cục để ngăn crash
        window.addEventListener('error', function(e) {
            console.warn('JavaScript error caught:', e.error);
            // Log chi tiết lỗi để debug
            console.warn('Error details:', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno
            });
            return true; // Ngăn xử lý lỗi mặc định
        });

        // Xử lý promise rejection toàn cục
        window.addEventListener('unhandledrejection', function(e) {
            console.warn('Unhandled promise rejection:', e.reason);
            e.preventDefault();
        });

        // ===== HÀM THỰC THI AN TOÀN =====
        
        // Hàm thực thi an toàn để ngăn crash
        function safelyExecute(fn) {
            try {
                return fn();
            } catch (error) {
                console.warn('Function execution error:', error);
                return null;
            }
        }

        // Override console.error để ngăn script crash
        const originalError = console.error;
        console.error = function(...args) {
            originalError.apply(console, args);
            // Không throw errors, chỉ log
        };

        // ===== QUẢN LÝ BÀI ĐĂNG =====
        
        // Hàm chọn tất cả checkbox bài đăng
        function selectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.post-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }        // Hàm duyệt bài đăng
        function approvePost(postId) {
            console.log('approvePost called with:', postId, typeof postId);
            if (confirm('Duyệt bài đăng này?')) {
                performAction('approve', postId);
            }
        }

        // Hàm từ chối bài đăng
        function rejectPost(postId) {
            console.log('rejectPost called with:', postId, typeof postId);
            if (confirm('Từ chối bài đăng này?')) {
                performAction('reject', postId);
            }
        }

        // Hàm xóa bài đăng
        function deletePost(postId) {
            console.log('deletePost called with:', postId, typeof postId);
            if (confirm('Xóa bài đăng này? Hành động này không thể hoàn tác!')) {
                performAction('delete', postId);
            }
        }

        // Hàm thực hiện các hành động trên bài đăng
        function performAction(action, postId) {
            console.log('Performing action:', action, 'for post ID:', postId);
            
            if (!postId || postId <= 0) {
                showNotification('ID bài đăng không hợp lệ: ' + postId, 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('post_id', postId);
            formData.append('type', 'all'); // Gửi type = 'all' cho tất cả loại bài đăng
            
            console.log('Sending FormData:', {
                action: formData.get('action'),
                post_id: formData.get('post_id'),
                type: formData.get('type')
            });

            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showNotification('Có lỗi xảy ra khi xử lý: ' + error.message, 'error');
            });
        }        // Hàm duyệt nhiều bài đăng đã chọn
        function approveSelected() {
            const selected = document.querySelectorAll('.post-checkbox:checked');
            if (selected.length === 0) {
                showNotification('Vui lòng chọn ít nhất một bài đăng!', 'error');
                return;
            }
              
            const postIds = Array.from(selected).map(cb => cb.dataset.id);
            
            if (confirm(`Duyệt ${selected.length} bài đăng đã chọn?`)) {
                const formData = new FormData();
                formData.append('action', 'approve_selected');
                formData.append('post_ids', JSON.stringify(postIds));
                formData.append('type', 'all'); // Gửi type = 'all' cho tất cả loại bài đăng

                fetch('admin_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Có lỗi xảy ra khi xử lý', 'error');
                });
            }
        }

        // Hàm lọc bài đăng theo trạng thái
        function filterPosts(status) {
            const rows = document.querySelectorAll('#posts-table tr[data-status]');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Hàm xem chi tiết bài đăng
        function viewPost(postId) {
            // Chuyển hướng đến trang chi tiết bài đăng hoặc mở modal
            window.open(`mua-ban.php?id=${postId}`, '_blank');
        }        // ===== HIỂN THỊ THÔNG BÁO =====
        
        // Hàm hiển thị thông báo cho người dùng
        function showNotification(message, type = 'success') {
            // Tạo element thông báo
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                transform: translateX(400px);
                transition: transform 0.3s ease;
            `;
            
            // Thiết lập màu sắc theo loại thông báo
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            } else {
                notification.style.background = 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)';
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Hiển thị thông báo
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Ẩn thông báo sau 3 giây
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // ===== QUẢN LÝ THẢO LUẬN =====
        
        // Hàm chọn tất cả checkbox thảo luận
        function selectAllDiscussions(checkbox) {
            const checkboxes = document.querySelectorAll('.discussion-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }        // Hàm duyệt thảo luận
        function approveDiscussion(postId) {
            console.log('approveDiscussion called with:', postId, typeof postId);
            if (confirm('Duyệt bài thảo luận này?')) {
                performDiscussionAction('approve', postId);
            }
        }

        // Hàm từ chối thảo luận
        function rejectDiscussion(postId) {
            console.log('rejectDiscussion called with:', postId, typeof postId);
            if (confirm('Từ chối bài thảo luận này?')) {
                performDiscussionAction('reject', postId);
            }
        }

        // Hàm xóa thảo luận
        function deleteDiscussion(postId) {
            console.log('deleteDiscussion called with:', postId, typeof postId);
            if (confirm('Xóa bài thảo luận này? Hành động này không thể hoàn tác!')) {
                performDiscussionAction('delete', postId);
            }
        }

        // Hàm xem chi tiết thảo luận
        function viewDiscussion(postId) {
            // Chuyển hướng đến trang chi tiết thảo luận
            window.open(`thao-luan.php?topic=${postId}`, '_blank');
        }

        // Hàm thực hiện các hành động trên thảo luận
        function performDiscussionAction(action, postId) {
            console.log('Performing action:', action, 'for discussion ID:', postId);
            
            if (!postId || postId <= 0) {
                showNotification('ID bài thảo luận không hợp lệ: ' + postId, 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('post_id', postId);
            formData.append('type', 'discussion');
            
            console.log('Sending FormData:', {
                action: formData.get('action'),
                post_id: formData.get('post_id'),
                type: formData.get('type')
            });

            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showNotification('Có lỗi xảy ra khi xử lý: ' + error.message, 'error');
            });
        }

        // Hàm duyệt nhiều thảo luận đã chọn
        function approveSelectedDiscussions() {
            const selected = document.querySelectorAll('.discussion-checkbox:checked');
            if (selected.length === 0) {
                showNotification('Vui lòng chọn ít nhất một bài thảo luận!', 'error');
                return;
            }
            
            const postIds = Array.from(selected).map(cb => cb.dataset.id);
            
            if (confirm(`Duyệt ${selected.length} bài thảo luận đã chọn?`)) {
                const formData = new FormData();
                formData.append('action', 'approve_selected');
                formData.append('post_ids', JSON.stringify(postIds));
                formData.append('type', 'discussion');

                fetch('admin_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Có lỗi xảy ra khi xử lý', 'error');
                });
            }
        }

        // Hàm lọc thảo luận theo trạng thái
        function filterDiscussions(status) {
            const rows = document.querySelectorAll('#discussions-table tr[data-status]');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }        // ===== QUẢN LÝ NGƯỜI DÙNG =====
        
        // Hàm chọn tất cả checkbox người dùng
        function selectAllUsers(checkbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        // Hàm lọc người dùng theo trạng thái
        function filterUsers(status) {
            const rows = document.querySelectorAll('#users-table tr[data-status]');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Hàm xem chi tiết người dùng
        function viewUser(userId) {
            // Hiển thị thông tin chi tiết người dùng
            showNotification(`Xem chi tiết người dùng #${userId}`, 'info');
            // TODO: Implement user detail modal or redirect
        }

        // Hàm chỉnh sửa người dùng
        function editUser(userId) {
            // Chỉnh sửa thông tin người dùng
            showNotification(`Chỉnh sửa người dùng #${userId}`, 'info');
            // TODO: Implement user edit functionality
        }

        // Hàm thay đổi trạng thái người dùng (khóa/mở khóa)
        function toggleUserStatus(userId) {
            if (confirm('Thay đổi trạng thái tài khoản người dùng này?')) {
                const formData = new FormData();
                formData.append('action', 'toggle_user_status');
                formData.append('user_id', userId);

                fetch('admin_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Có lỗi xảy ra khi xử lý', 'error');
                });
            }
        }

        // Hàm xóa người dùng
        function deleteUser(userId) {
            if (confirm('Xóa tài khoản người dùng này? Hành động này không thể hoàn tác!')) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);

                fetch('admin_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Có lỗi xảy ra khi xử lý', 'error');
                });
            }
        }

        // Hàm thêm người dùng mới
        function addUser() {
            // Thêm người dùng mới
            showNotification('Chức năng thêm người dùng sẽ được triển khai sớm!', 'info');
            // TODO: Implement add user functionality
        }        // Hàm ban người dùng (alias của toggleUserStatus)
        function banUser(userId) {
            toggleUserStatus(userId);
        }

        // ===== QUẢN LÝ DANH MỤC =====
        
        // Hàm thêm danh mục mới
        function addCategory() {
            const name = prompt('Nhập tên danh mục mới:');
            if (name) {
                showNotification('Đã thêm danh mục: ' + name, 'success');
                // TODO: Implement actual add category functionality
                setTimeout(() => location.reload(), 1500);
            }
        }

        // Hàm chỉnh sửa danh mục
        function editCategory(categoryId) {
            const newName = prompt('Nhập tên danh mục mới:');
            if (newName) {
                showNotification('Đã cập nhật danh mục #' + categoryId, 'success');
                // TODO: Implement actual edit category functionality
                setTimeout(() => location.reload(), 1500);
            }
        }

        // Hàm xóa danh mục
        function deleteCategory(categoryId) {
            if (confirm('Xóa danh mục này? Hành động này có thể ảnh hưởng đến các bài đăng liên quan!')) {
                showNotification('Đã xóa danh mục #' + categoryId, 'success');
                // TODO: Implement actual delete category functionality
                setTimeout(() => location.reload(), 1500);
            }
        }

        // ===== QUẢN LÝ TIN NHẮN =====
        
        // Biến global cho quản lý tin nhắn
        let currentMessagePage = 1;
        let currentMessageFilter = 'all';
        let selectedMessageId = null;

        // Hàm tải tin nhắn từ server
        function loadMessages(page = 1, status = 'all') {
            currentMessagePage = page;
            currentMessageFilter = status;
            
            fetch(`contact_api.php?action=get_messages&page=${page}&status=${status}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                        updateMessageStats(data.stats);
                        updateMessagePagination(data.pagination);
                        updateUnreadBadge(data.stats.unread);
                    } else {
                        showNotification('Không thể tải tin nhắn: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    showNotification('Có lỗi xảy ra khi tải tin nhắn', 'error');
                });
        }        // Hàm hiển thị danh sách tin nhắn trong bảng
        function displayMessages(messages) {
            const tbody = document.getElementById('messages-table');
            
            // Nếu không có tin nhắn nào, hiển thị thông báo trống
            if (messages.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                            <i class="fas fa-inbox"></i><br>
                            Không có tin nhắn nào
                        </td>
                    </tr>
                `;
                return;
            }
              tbody.innerHTML = messages.map(message => {
                // Mapping cho các nhãn chủ đề tin nhắn
                const subjectLabels = {
                    'support': 'Hỗ trợ kỹ thuật',
                    'partnership': 'Hợp tác kinh doanh',
                    'feedback': 'Góp ý / Phản hồi',
                    'report': 'Báo cáo vấn đề',
                    'other': 'Khác'
                };
                
                // Mapping cho các trạng thái tin nhắn  
                const statusLabels = {
                    'unread': { text: 'Chưa đọc', class: 'status-pending' },
                    'read': { text: 'Đã đọc', class: 'status-active' },
                    'replied': { text: 'Đã trả lời', class: 'status-sold' }
                };
                
                const status = statusLabels[message.status] || { text: 'Chưa đọc', class: 'status-pending' };return `
                    <tr data-status="${message.status}" style="${message.status === 'unread' ? 'background: #fff3cd; font-weight: 600;' : ''}" onclick="readMessage(${message.message_id})" style="cursor: pointer;">
                        <td onclick="event.stopPropagation();"><input type="checkbox" class="message-checkbox" data-id="${message.message_id}"></td>
                        <td>${message.status === 'unread' ? '<i class="fas fa-circle unread-indicator" style="color: #ffc107; margin-right: 5px;"></i>' : ''}#${String(message.message_id).padStart(3, '0')}</td>
                        <td>
                            <div style="max-width: 200px;">
                                <strong>${message.name}</strong>
                                <div style="font-size: 0.8rem; color: #666; margin-top: 2px;">
                                    <i class="fas fa-envelope"></i> ${message.email}
                                </div>
                                ${message.phone ? `
                                    <div style="font-size: 0.8rem; color: #666;">
                                        <i class="fas fa-phone"></i> ${message.phone}
                                    </div>
                                ` : ''}
                            </div>
                        </td>
                        <td>
                            <span class="status-badge" style="background: #e3f2fd; color: #1976d2;">
                                ${subjectLabels[message.subject] || message.subject}
                            </span>
                        </td>
                        <td>
                            <div style="max-width: 300px;">
                                <div class="message-preview" style="cursor: pointer;" onclick="event.stopPropagation(); readMessage(${message.message_id})">
                                    ${message.message.substring(0, 100)}${message.message.length > 100 ? '...' : ''}
                                    <div style="font-size: 0.7rem; color: #007bff; margin-top: 5px;">
                                        <i class="fas fa-eye"></i> Click để xem chi tiết
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.9rem;">
                                ${message.time_ago}
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${status.class}">
                                ${status.text}
                            </span>
                            <div style="margin-top: 5px;">
                                <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); readMessage(${message.message_id})" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${message.status !== 'replied' ? `
                                    <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); replyMessage(${message.message_id})" title="Trả lời">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                ` : ''}
                                <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); deleteMessage(${message.message_id})" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Hàm cập nhật thống kê tin nhắn
        function updateMessageStats(stats) {
            document.getElementById('unread-count').textContent = stats.unread || 0;
            document.getElementById('read-count').textContent = stats.read || 0;
            document.getElementById('replied-count').textContent = stats.replied || 0;
            document.getElementById('total-count').textContent = stats.total || 0;
        }

        // Hàm cập nhật phân trang tin nhắn
        function updateMessagePagination(pagination) {
            const container = document.getElementById('messages-pagination');
            
            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '<div style="display: flex; gap: 5px; justify-content: center; align-items: center;">';
            
            // Nút Previous
            if (pagination.current_page > 1) {
                html += `<button class="btn btn-secondary" onclick="loadMessages(${pagination.current_page - 1}, '${currentMessageFilter}')">« Trước</button>`;
            }
            
            // Số trang
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += `<button class="btn btn-primary">${i}</button>`;
                } else {
                    html += `<button class="btn btn-secondary" onclick="loadMessages(${i}, '${currentMessageFilter}')">${i}</button>`;
                }
            }
            
            // Nút Next
            if (pagination.current_page < pagination.total_pages) {
                html += `<button class="btn btn-secondary" onclick="loadMessages(${pagination.current_page + 1}, '${currentMessageFilter}')">Sau »</button>`;
            }
            
            html += `</div>
                     <div style="margin-top: 10px; font-size: 0.9rem; color: #666;">
                         Hiển thị trang ${pagination.current_page} / ${pagination.total_pages} 
                         (${pagination.total_messages} tin nhắn)
                     </div>`;
            
            container.innerHTML = html;
        }

        // Hàm cập nhật badge số tin nhắn chưa đọc
        function updateUnreadBadge(unreadCount) {
            const badge = document.getElementById('unread-count');
            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        // Hàm lọc tin nhắn theo trạng thái
        function filterMessages(status) {
            loadMessages(1, status);
        }

        // Hàm chọn tất cả tin nhắn
        function selectAllMessages(checkbox) {
            const checkboxes = document.querySelectorAll('.message-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }        function readMessage(messageId) {
            console.log('Reading message:', messageId);
            
            // Đánh dấu là đã đọc trước
            fetch('contact_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=mark_read&message_id=${messageId}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Mark read response:', data);
                if (data.success) {
                    // Tải chi tiết tin nhắn và hiển thị modal
                    showMessageDetail(messageId);
                } else {
                    console.warn('Mark read failed:', data.message);
                    // Hiển thị chi tiết tin nhắn ngay cả khi đánh dấu đọc thất bại
                    showMessageDetail(messageId);
                }
            })
            .catch(error => {
                console.error('Error marking message as read:', error);
                // Hiển thị chi tiết tin nhắn ngay cả khi có lỗi
                showMessageDetail(messageId);
            });
        }

        // Hàm hiển thị chi tiết tin nhắn trong modal
        function showMessageDetail(messageId) {
            // Tìm tin nhắn trong dữ liệu hiện tại hoặc tải nó
            fetch(`contact_api.php?action=get_messages&page=1&status=all`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = data.messages.find(m => m.message_id == messageId);
                        if (message) {
                            displayMessageModal(message);
                        } else {
                            showNotification('Không tìm thấy tin nhắn', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading message detail:', error);
                    showNotification('Có lỗi xảy ra khi tải tin nhắn', 'error');
                });
        }

        // Hàm hiển thị modal chi tiết tin nhắn
        function displayMessageModal(message) {
            const modal = document.getElementById('messageModal');
            const body = document.getElementById('messageModalBody');
            
            const subjectLabels = {
                'support': 'Hỗ trợ kỹ thuật',
                'partnership': 'Hợp tác kinh doanh', 
                'feedback': 'Góp ý / Phản hồi',
                'report': 'Báo cáo vấn đề',
                'other': 'Khác'
            };
            
            body.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h4 style="margin-bottom: 10px; color: #333;">Thông tin liên hệ</h4>
                            <p><strong>Họ tên:</strong> ${message.name}</p>
                            <p><strong>Email:</strong> <a href="mailto:${message.email}">${message.email}</a></p>
                            ${message.phone ? `<p><strong>Số điện thoại:</strong> <a href="tel:${message.phone}">${message.phone}</a></p>` : ''}
                        </div>
                        <div>
                            <h4 style="margin-bottom: 10px; color: #333;">Thông tin tin nhắn</h4>
                            <p><strong>Chủ đề:</strong> ${subjectLabels[message.subject] || message.subject}</p>
                            <p><strong>Ngày gửi:</strong> ${message.time_ago}</p>
                            <p><strong>Trạng thái:</strong> 
                                <span class="status-badge ${message.status === 'unread' ? 'status-pending' : message.status === 'read' ? 'status-active' : 'status-sold'}">
                                    ${message.status === 'unread' ? 'Chưa đọc' : message.status === 'read' ? 'Đã đọc' : 'Đã trả lời'}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin-bottom: 10px; color: #333;">Nội dung tin nhắn</h4>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; line-height: 1.6;">
                            ${message.message.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    
                    ${message.admin_reply ? `
                        <div style="margin-top: 20px;">
                            <h4 style="margin-bottom: 10px; color: #333;">Phản hồi của admin</h4>
                            <div style="background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; line-height: 1.6;">
                                ${message.admin_reply.replace(/\n/g, '<br>')}
                            </div>
                            <small style="color: #666;">Trả lời lúc: ${message.replied_at}</small>
                        </div>
                    ` : ''}
                    
                    <div style="margin-top: 20px; text-align: right;">
                        ${message.status !== 'replied' ? `
                            <button class="btn btn-success" onclick="replyMessage(${message.message_id}); closeMessageModal();">
                                <i class="fas fa-reply"></i> Trả lời
                            </button>
                        ` : ''}
                        <button class="btn btn-danger" onclick="deleteMessage(${message.message_id}); closeMessageModal();">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                        <button class="btn btn-secondary" onclick="closeMessageModal()">Đóng</button>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
            
            // Refresh messages list to update read status
            setTimeout(() => loadMessages(currentMessagePage, currentMessageFilter), 500);
        }

        // Hàm đóng modal chi tiết tin nhắn
        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        // Hàm trả lời tin nhắn
        function replyMessage(messageId) {
            selectedMessageId = messageId;
            
            // Tải thông tin tin nhắn để phản hồi
            fetch(`contact_api.php?action=get_messages&page=1&status=all`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = data.messages.find(m => m.message_id == messageId);
                        if (message) {
                            showReplyModal(message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading message for reply:', error);
                });
        }

        // Hàm hiển thị modal trả lời tin nhắn
        function showReplyModal(message) {
            const modal = document.getElementById('replyModal');
            const replyInfo = document.getElementById('reply-to-info');
            
            replyInfo.innerHTML = `
                <strong>${message.name}</strong> &lt;${message.email}&gt;<br>
                <small>Chủ đề: ${message.subject}</small>
            `;
            
            document.getElementById('reply-content').value = '';
            modal.style.display = 'block';
        }

        // Hàm đóng modal trả lời tin nhắn
        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
            selectedMessageId = null;
        }

        // Hàm xóa tin nhắn
        function deleteMessage(messageId) {
            if (confirm('Xóa tin nhắn này? Hành động này không thể hoàn tác!')) {
                fetch('contact_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&message_id=${messageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Đã xóa tin nhắn', 'success');
                        loadMessages(currentMessagePage, currentMessageFilter);
                    } else {
                        showNotification('Không thể xóa tin nhắn: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting message:', error);
                    showNotification('Có lỗi xảy ra khi xóa tin nhắn', 'error');
                });
            }
        }        // Hàm đánh dấu tin nhắn đã chọn là đã đọc
        function markSelectedAsRead() {
            const selected = document.querySelectorAll('.message-checkbox:checked');
            if (selected.length === 0) {
                showNotification('Vui lòng chọn ít nhất một tin nhắn!', 'error');
                return;
            }
            
            // Lọc chỉ những tin nhắn chưa đọc
            const unreadMessages = Array.from(selected).filter(checkbox => {
                const row = checkbox.closest('tr');
                return row && row.dataset.status === 'unread';
            });
            
            if (unreadMessages.length === 0) {
                showNotification('Không có tin nhắn chưa đọc nào được chọn!', 'error');
                return;
            }
            
            if (confirm(`Đánh dấu ${unreadMessages.length} tin nhắn chưa đọc là đã đọc?`)) {
                const messageIds = unreadMessages.map(checkbox => checkbox.dataset.id);
                
                // Vô hiệu hóa button trong quá trình xử lý
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                button.disabled = true;
                
                fetch('contact_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=mark_multiple_read&message_ids=${encodeURIComponent(JSON.stringify(messageIds))}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        // Bỏ chọn tất cả checkbox
                        document.querySelectorAll('.message-checkbox').forEach(cb => cb.checked = false);
                        document.querySelector('input[onclick="selectAllMessages(this)"]').checked = false;
                        // Tải lại danh sách tin nhắn
                        loadMessages(currentMessagePage, currentMessageFilter);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error marking messages as read:', error);
                    showNotification('Có lỗi xảy ra khi cập nhật tin nhắn', 'error');
                })
                .finally(() => {
                    // Khôi phục trạng thái button
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }

        // ===== KHỞI TẠO HỆ THỐNG VÀ XỬ LÝ SỰ KIỆN =====
        
        /*
         * Khởi tạo admin panel khi DOM đã sẵn sàng
         * Kiểm tra tính toàn vẹn của các element và thêm event listener
         */
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Admin Panel - Bắt đầu khởi tạo...');
            
            // Kiểm tra FontAwesome đã load chưa
            if (document.querySelector('link[href*="font-awesome"]')) {
                console.log('✓ FontAwesome CSS đã được tải');
            } else {
                console.warn('⚠ Không tìm thấy FontAwesome CSS');
            }
            
            // Kiểm tra các element quan trọng
            const criticalElements = ['dashboard', 'posts', 'sidebar', 'page-title'];
            criticalElements.forEach(id => {
                if (document.getElementById(id)) {
                    console.log(`✓ Element #${id} đã tồn tại`);
                } else {
                    console.warn(`⚠ Element quan trọng #${id} không tìm thấy`);
                }
            });
            
            // Kiểm tra các script external có thể gây xung đột
            const scripts = document.querySelectorAll('script[src]');
            scripts.forEach(script => {
                if (script.src) {
                    console.log(`📝 Script ngoài: ${script.src}`);
                }
            });
            
            console.log('✓ Kiểm tra dependency hoàn tất');
            
            // Khởi tạo admin panel một cách an toàn
            safelyExecute(() => {
                console.log('DOM Content Loaded - Admin Panel');
                
                // Đảm bảo tất cả element cần thiết tồn tại trước khi thêm listener
                const dashboard = document.getElementById('dashboard');
                const postsTable = document.getElementById('posts-table');
                
                // Thêm event listener cho tất cả các button hành động
                document.addEventListener('click', function(e) {
                    safelyExecute(() => {
                        if (e.target && e.target.closest && e.target.closest('.btn')) {
                            const button = e.target.closest('.btn');
                            const postId = button.getAttribute('data-post-id');
                            
                            if (!postId) return;
                            
                            console.log('Button được click, post ID từ data attribute:', postId);
                            
                            // Log loại button được click
                            if (button.onclick && button.onclick.toString().includes('approvePost')) {
                                console.log('Button duyệt đã được phát hiện');
                            } else if (button.onclick && button.onclick.toString().includes('rejectPost')) {
                                console.log('Button từ chối đã được phát hiện');
                            } else if (button.onclick && button.onclick.toString().includes('deletePost')) {
                                console.log('Button xóa đã được phát hiện');
                            }
                        }
                    });
                });
                
                // Event listener an toàn cho modal nếu tồn tại
                const shareModal = document.getElementById('shareModal');
                if (shareModal) {
                    shareModal.addEventListener('click', function(e) {
                        safelyExecute(() => {
                            if (e.target === shareModal) {
                                shareModal.style.display = 'none';
                            }
                        });
                    });
                }
                
                // Event listener an toàn cho các modal khác
                const modals = document.querySelectorAll('.modal');
                if (modals) {
                    modals.forEach(modal => {
                        if (modal) {
                            modal.addEventListener('click', function(e) {
                                safelyExecute(() => {
                                    if (e.target === modal) {
                                        modal.style.display = 'none';
                                    }
                                });
                            });
                        }
                    });
                }
            });
        });

        // ===== TỰ ĐỘNG LÀM MỚI DỮ LIỆU =====
        
        // Tự động làm mới dashboard mỗi 30 giây
        setInterval(function() {
            if (document.getElementById('dashboard').classList.contains('active')) {
                // Làm mới dữ liệu dashboard
                console.log('Đang làm mới dữ liệu dashboard...');
            }
        }, 30000);
    </script>
</body>
</html>