<?php
session_start();

// Function to format time as "X ago"
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
}

// Kết nối database
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lấy thống kê forum
    $total_topics = $pdo->query("SELECT COUNT(*) FROM forum_topics WHERE status = 'active'")->fetchColumn();
    $total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $online_users = rand(50, 200); // Tạm thời random, sau này có thể implement tracking thực    // Lấy tất cả các bài thảo luận từ bảng forum_topics (chỉ hiển thị đã duyệt)
    $all_discussions_stmt = $pdo->prepare("
        SELECT ft.*, u.full_name, u.email, c.category_name,
               COALESCE((SELECT COUNT(*) FROM topic_comments tc WHERE tc.topic_id = ft.topic_id), 0) as comment_count,
               0 as like_count,
               ft.topic_id as post_id
        FROM forum_topics ft 
        LEFT JOIN users u ON ft.user_id = u.user_id 
        LEFT JOIN categories c ON ft.category_id = c.category_id
        WHERE ft.status = 'active'
        ORDER BY ft.created_at DESC 
        LIMIT 50    ");
    $all_discussions_stmt->execute();
    $all_discussions = $all_discussions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy các bài thảo luận gần đây (để hiển thị trong tab "Chủ đề mới nhất")
    $recent_discussions = $all_discussions; // Sử dụng tất cả discussions thay vì chỉ 20 bài đầu
    
    // Lấy các danh mục và đếm số bài trong mỗi danh mục
    $categories_stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(p.post_id) as topic_count,
               (SELECT COUNT(*) FROM posts p2 WHERE p2.category_id = c.category_id AND p2.status = 'active') as post_count
        FROM categories c 
        LEFT JOIN posts p ON c.category_id = p.category_id AND p.post_type = 'discussion' AND p.status = 'active'
        GROUP BY c.category_id
        ORDER BY c.category_name
    ");
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Fallback data nếu không kết nối được database
    error_log("Database error in thao-luan.php: " . $e->getMessage());
    $total_topics = 0;
    $total_posts = 0;
    $total_users = 0;
    $online_users = 0;
    $all_discussions = [];
    $recent_discussions = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thảo luận - ElectroReview</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Forum specific styles */
        .forum-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .forum-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0 40px;
            text-align: center;
        }

        .forum-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            opacity: 0.9;
        }

        /* Navigation */
        .forum-nav {
            background: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .nav-btn {
            padding: 12px 24px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-btn:hover, .nav-btn.active {
            background: #667eea;
            color: white;
        }

        /* Categories */
        .categories-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .category-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .category-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .category-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }

        .category-desc {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #999;
        }

        /* Topics List */
        .topics-section {
            margin-bottom: 40px;
        }

        .topics-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-input {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
        }

        .search-btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-new-topic {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .btn-new-topic:hover {
            transform: translateY(-2px);
        }

        .topics-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .topic-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }

        .topic-item:hover {
            background: #f8f9fa;
        }

        .topic-item:last-child {
            border-bottom: none;
        }

        .topic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .topic-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .topic-title:hover {
            color: #667eea;
        }

        .topic-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .topic-content {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .topic-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topic-tags {
            display: flex;
            gap: 8px;
        }

        .tag {
            background: #e9ecef;
            color: #495057;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .topic-stats {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: #999;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-select {
            background: white;
            cursor: pointer;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        /* Topic Detail View */
        .topic-detail {
            display: none;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .topic-detail.active {
            display: block;
        }

        .detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }

        .detail-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .detail-meta {
            display: flex;
            gap: 20px;
            opacity: 0.9;
        }

        .detail-content {
            padding: 30px;
        }

        .original-post {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .post-content {
            line-height: 1.8;
            color: #333;
            margin-bottom: 20px;
        }

        .post-actions {
            display: flex;
            gap: 15px;
        }

        .btn-action {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-action:hover {
            background: #f8f9fa;
        }

        .btn-like.liked {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }

        /* Replies Section */
        .replies-section {
            margin-top: 30px;
        }

        .replies-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .reply-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reply-author {
            font-weight: 600;
            color: #333;
        }

        .reply-time {
            font-size: 0.9rem;
            color: #999;
        }

        .reply-content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .reply-actions {
            display: flex;
            gap: 10px;
        }

        /* Reply Form */
        .reply-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            margin-top: 20px;
        }

        .reply-form h4 {
            margin-bottom: 15px;
            color: #333;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-btn {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .page-btn:hover, .page-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Auth Modals */
        .auth-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }

        .auth-modal .modal-content {
            background-color: #f8f9fa;
            margin: 10% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }

        .auth-modal .modal-header {
            background: #667eea;
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .auth-modal h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .auth-modal .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .auth-modal .modal-body {
            padding: 30px;
        }

        .auth-modal .form-group {
            margin-bottom: 20px;
        }

        .auth-modal .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .auth-modal .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .auth-modal .form-control:focus {
            outline: none;
            border-color: #667eea;
        }        .auth-modal .btn-primary {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .auth-modal .btn-primary:hover {
            background: #5a6fba;
        }

        .auth-modal .btn-primary:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .auth-modal .auth-footer {
            margin-top: 15px;
            text-align: center;
            font-size: 0.9rem;
        }

        .auth-modal .auth-footer a {
            color: #667eea;
            text-decoration: none;
        }

        .auth-modal .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .forum-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .topics-controls {
                flex-direction: column;
                gap: 15px;
            }

            .search-box {
                width: 100%;
            }

            .search-input {
                width: 100%;
            }

            .topic-header {
                flex-direction: column;
                gap: 10px;
            }

            .topic-footer {
                flex-direction: column;
                gap: 15px;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
            }

            .auth-modal .modal-content {
                margin: 10% auto;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-laptop"></i>
                ElectroReview
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="index.php#about">Giới thiệu</a></li>
                    <li><a href="mua-ban.php">Mua bán, trao đổi</a></li>
                    <li><a href="thao-luan.php" class="active">Thảo luận</a></li>
                    <li><a href="lien-he.php">Liên hệ</a></li>
                </ul>
            </nav>            <div class="auth-buttons">
                <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                    <span style="color: #667eea; margin-right: 15px;">
                        <i class="fas fa-user-circle"></i> 
                        Xin chào, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                    </span>                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <a href="admin.php" class="btn btn-outline" style="margin-right: 10px;">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline" onclick="openLoginModal()">Đăng nhập</button>
                    <button class="btn btn-primary" onclick="openRegisterModal()">Đăng ký</button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Forum Header -->
    <section class="forum-header">
        <div class="forum-container">
            <h1><i class="fas fa-comments"></i> Diễn đàn thảo luận</h1>
            <p>Chia sẻ kinh nghiệm, hỏi đáp và thảo luận về các sản phẩm điện tử cũ</p>
              <div class="forum-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_topics); ?></div>
                    <div class="stat-label">Chủ đề</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_posts); ?></div>
                    <div class="stat-label">Bài viết</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Thành viên</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($online_users); ?></div>
                    <div class="stat-label">Online</div>
                </div>
            </div>
        </div>
    </section>    <!-- Forum Navigation -->
    <section class="forum-nav">
        <div class="forum-container">
            <div class="nav-buttons">
                <a href="#" class="nav-btn active" onclick="showSection('all')">
                    <i class="fas fa-list"></i> Tất cả thảo luận
                </a>
                <a href="#" class="nav-btn" onclick="showSection('recent')">
                    <i class="fas fa-clock"></i> Mới nhất
                </a>
                <a href="#" class="nav-btn" onclick="showSection('popular')">
                    <i class="fas fa-fire"></i> Phổ biến
                </a>
                <a href="#" class="nav-btn" onclick="showSection('trending')">
                    <i class="fas fa-trending-up"></i> Thịnh hành
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="forum-container">        <!-- Main Discussions Section -->
        <section id="categories-section" class="topics-section">
            <div class="topics-controls">
                <h2 class="section-title">
                    <i class="fas fa-comments"></i> Thảo luận gần đây
                </h2>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Tìm kiếm thảo luận...">
                        <button class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                    <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                        <button class="btn-new-topic" onclick="openNewTopicModal()">
                            <i class="fas fa-plus"></i> Tạo chủ đề mới
                        </button>
                    <?php else: ?>
                        <button class="btn-new-topic" onclick="showLoginRequiredMessage()">
                            <i class="fas fa-lock"></i> Đăng nhập để tạo chủ đề
                        </button>
                    <?php endif; ?>
                </div>
            </div>            <div class="topics-list">                <?php if (!empty($all_discussions)): ?>
                    <?php foreach ($all_discussions as $index => $discussion): ?>
                        <div class="topic-item" onclick="showTopicDetail(<?php echo $discussion['post_id']; ?>)">
                            <div class="topic-header">
                                <h3 class="topic-title"><?php echo htmlspecialchars($discussion['title']); ?></h3>
                                <div class="topic-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($discussion['full_name'] ?? $discussion['email'] ?? 'Ẩn danh'); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo timeAgo($discussion['created_at']); ?></span>
                                </div>
                            </div>                            <p class="topic-content"><?php echo htmlspecialchars(substr(strip_tags($discussion['content']), 0, 200)); ?><?php echo strlen(strip_tags($discussion['content'])) > 200 ? '...' : ''; ?></p>
                            <div class="topic-footer">
                                <div class="topic-tags">
                                    <span class="tag"><?php echo htmlspecialchars($discussion['category_name'] ?? 'Tổng hợp'); ?></span>
                                    <span class="tag">Thảo luận</span>
                                    <?php if (!empty($discussion['tags'])): ?>
                                        <?php 
                                        $tags = explode(',', $discussion['tags']);
                                        foreach($tags as $tag): 
                                            $tag = trim($tag);
                                            if (!empty($tag)):
                                        ?>
                                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    <?php endif; ?>
                                </div>
                                <div class="topic-stats">
                                    <span><i class="fas fa-eye"></i> <?php echo rand(50, 500); ?> lượt xem</span>
                                    <span><i class="fas fa-comments"></i> <?php echo $discussion['comment_count']; ?> trả lời</span>
                                    <span><i class="fas fa-heart"></i> <?php echo $discussion['like_count']; ?> thích</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Thông báo khi chưa có bài thảo luận nào -->
                    <div class="empty-state" style="text-align: center; padding: 60px 20px; color: #666;">
                        <div style="font-size: 4rem; margin-bottom: 20px; color: #ddd;">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 style="margin-bottom: 15px; color: #333;">Chưa có bài thảo luận nào</h3>
                        <p style="margin-bottom: 30px; line-height: 1.6;">
                            Hãy là người đầu tiên tạo chủ đề thảo luận mới trong cộng đồng ElectroReview!<br>
                            Chia sẻ kinh nghiệm, đặt câu hỏi hoặc thảo luận về các sản phẩm điện tử cũ.
                        </p>
                        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                            <button class="btn-new-topic" onclick="openNewTopicModal()" style="display: inline-block;">
                                <i class="fas fa-plus"></i> Tạo chủ đề đầu tiên
                            </button>
                        <?php else: ?>
                            <button class="btn-new-topic" onclick="showLoginRequiredMessage()" style="display: inline-block;">
                                <i class="fas fa-lock"></i> Đăng nhập để tạo chủ đề
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Topics Section -->
        <section id="topics-section" class="topics-section" style="display: none;">            <div class="topics-controls">
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Tìm kiếm chủ đề...">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </div>
                <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                    <button class="btn-new-topic" onclick="openNewTopicModal()">
                        <i class="fas fa-plus"></i> Tạo chủ đề mới
                    </button>
                <?php else: ?>
                    <button class="btn-new-topic" onclick="showLoginRequiredMessage()">
                        <i class="fas fa-lock"></i> Đăng nhập để tạo chủ đề
                    </button>
                <?php endif; ?>
            </div><div class="topics-list">
                <?php if (!empty($recent_discussions)): ?>
                    <?php foreach ($recent_discussions as $index => $discussion): ?>
                        <div class="topic-item" onclick="showTopicDetail(<?php echo $discussion['post_id']; ?>)">
                            <div class="topic-header">
                                <h3 class="topic-title"><?php echo htmlspecialchars($discussion['title']); ?></h3>
                                <div class="topic-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($discussion['full_name'] ?? $discussion['email'] ?? 'Ẩn danh'); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo timeAgo($discussion['created_at']); ?></span>
                                </div>
                            </div>                            <p class="topic-content"><?php echo htmlspecialchars(substr(strip_tags($discussion['content']), 0, 200)); ?><?php echo strlen(strip_tags($discussion['content'])) > 200 ? '...' : ''; ?></p>
                            <div class="topic-footer">
                                <div class="topic-tags">
                                    <span class="tag"><?php echo htmlspecialchars($discussion['category_name'] ?? 'Tổng hợp'); ?></span>
                                    <span class="tag">Thảo luận</span>
                                </div>
                                <div class="topic-stats">
                                    <span><i class="fas fa-eye"></i> <?php echo rand(50, 500); ?> lượt xem</span>
                                    <span><i class="fas fa-comments"></i> <?php echo $discussion['comment_count']; ?> trả lời</span>
                                    <span><i class="fas fa-heart"></i> <?php echo $discussion['like_count']; ?> thích</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>                <?php else: ?>
                    <!-- Thông báo khi chưa có bài thảo luận nào -->
                    <div class="empty-state" style="text-align: center; padding: 60px 20px; color: #666;">
                        <div style="font-size: 4rem; margin-bottom: 20px; color: #ddd;">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 style="margin-bottom: 15px; color: #333;">Chưa có bài thảo luận nào</h3>
                        <p style="margin-bottom: 30px; line-height: 1.6;">
                            Hãy là người đầu tiên tạo chủ đề thảo luận mới trong cộng đồng ElectroReview!<br>
                            Chia sẻ kinh nghiệm, đặt câu hỏi hoặc thảo luận về các sản phẩm điện tử cũ.
                        </p>                        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                            <button class="btn-new-topic" onclick="openNewTopicModal()" style="display: inline-block;">
                                <i class="fas fa-plus"></i> Tạo chủ đề đầu tiên
                            </button>
                        <?php else: ?>
                            <button class="btn-new-topic" onclick="showLoginRequiredMessage()" style="display: inline-block;">
                                <i class="fas fa-lock"></i> Đăng nhập để tạo chủ đề
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Topic Detail View -->
        <section id="topic-detail" class="topic-detail">            <div class="detail-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 class="detail-title" id="detail-title">Chào mừng đến với diễn đàn ElectroReview</h1>
                        <div class="detail-meta">
                            <span><i class="fas fa-user"></i> System</span>
                            <span><i class="fas fa-clock"></i> 1 ngày trước</span>
                            <span><i class="fas fa-eye"></i> 100 lượt xem</span>
                        </div>
                    </div>
                    <button class="btn-action" onclick="backToTopics()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </button>
                </div>
            </div>

            <div class="detail-content">
                <div class="original-post">
                    <div class="post-content" id="detail-content">
                        <p>Chào mừng bạn đến với cộng đồng thảo luận ElectroReview!</p>
                        <p>Đây là nơi để bạn:</p>
                        <ul>
                            <li>Chia sẻ kinh nghiệm mua bán thiết bị điện tử cũ</li>
                            <li>Hỏi đáp về các vấn đề kỹ thuật</li>
                            <li>Tìm hiểu giá cả thị trường</li>
                            <li>Kết nối với những người có cùng sở thích</li>
                        </ul>
                        <p>Hãy tạo chủ đề mới để bắt đầu thảo luận!</p>
                    </div>

                    <div class="post-actions">
                        <button class="btn-action btn-like" onclick="toggleLike(this)">
                            <i class="fas fa-heart"></i> Thích (8)
                        </button>
                        <button class="btn-action" onclick="sharePost()">
                            <i class="fas fa-share"></i> Chia sẻ
                        </button>
                        <button class="btn-action" onclick="reportPost()">
                            <i class="fas fa-flag"></i> Báo cáo
                        </button>
                    </div>
                </div>                <!-- Replies Section -->
                <div class="replies-section">
                    <h3 class="replies-title"><i class="fas fa-comments"></i> Trả lời (3)</h3>

                    <div class="reply-item">
                        <div class="reply-header">
                            <span class="reply-author"><i class="fas fa-user-circle"></i> ElectroExpert</span>
                            <span class="reply-time">1 giờ trước</span>
                        </div>
                        <div class="reply-content">
                            Cộng đồng ElectroReview thật tuyệt vời! Đây là nơi mình học được rất nhiều kinh nghiệm về việc mua bán thiết bị điện tử cũ. Các thành viên ở đây rất nhiệt tình chia sẻ và hỗ trợ nhau.
                        </div>
                        <div class="reply-actions">
                            <button class="btn-action" onclick="likeReply(this)">
                                <i class="fas fa-thumbs-up"></i> Thích (5)
                            </button>
                            <button class="btn-action" onclick="replyToReply(this)">
                                <i class="fas fa-reply"></i> Trả lời
                            </button>
                        </div>
                    </div>

                    <div class="reply-item">
                        <div class="reply-header">
                            <span class="reply-author"><i class="fas fa-user-circle"></i> TechReviewer</span>
                            <span class="reply-time">45 phút trước</span>
                        </div>
                        <div class="reply-content">
                            Rất cảm ơn admin đã tạo ra diễn đàn này. Mình đã tìm được nhiều thông tin hữu ích và kết nối với những người có cùng sở thích. Hy vọng cộng đồng sẽ ngày càng phát triển!
                        </div>
                        <div class="reply-actions">
                            <button class="btn-action" onclick="likeReply(this)">
                                <i class="fas fa-thumbs-up"></i> Thích (8)
                            </button>
                            <button class="btn-action" onclick="replyToReply(this)">
                                <i class="fas fa-reply"></i> Trả lời
                            </button>
                        </div>
                    </div>

                    <div class="reply-item">
                        <div class="reply-header">
                            <span class="reply-author"><i class="fas fa-user-circle"></i> NewbieLearner</span>
                            <span class="reply-time">30 phút trước</span>
                        </div>
                        <div class="reply-content">
                            Mình là người mới tham gia, cảm thấy rất ấn tượng với cách mọi người chia sẻ kinh nghiệm. Sẽ tích cực tham gia thảo luận và học hỏi từ các anh chị có kinh nghiệm. Cảm ơn tất cả mọi người!
                        </div>
                        <div class="reply-actions">
                            <button class="btn-action" onclick="likeReply(this)">
                                <i class="fas fa-thumbs-up"></i> Thích (3)
                            </button>
                            <button class="btn-action" onclick="replyToReply(this)">
                                <i class="fas fa-reply"></i> Trả lời
                            </button>
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <div class="reply-form">
                        <h4><i class="fas fa-pen"></i> Trả lời chủ đề</h4>
                        <div class="form-group">
                            <textarea class="form-control form-textarea" placeholder="Nhập nội dung trả lời..." rows="4"></textarea>
                        </div>
                        <button class="btn-submit" onclick="submitReply()">
                            <i class="fas fa-paper-plane"></i> Gửi trả lời
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- New Topic Modal -->
    <div id="newTopicModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-plus"></i> Tạo chủ đề mới</h2>
                <span class="close" onclick="closeNewTopicModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="newTopicForm">                    <div class="form-group">
                        <label class="form-label">Danh mục</label>
                        <select class="form-control form-select" required>
                            <option value="">Chọn danh mục...</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="1">Laptop</option>
                                <option value="2">Điện thoại</option>
                                <option value="3">Gaming Gear</option>
                                <option value="4">Phụ kiện</option>
                                <option value="5">Sửa chữa</option>
                                <option value="6">Tổng hợp</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tiêu đề chủ đề</label>
                        <input type="text" class="form-control" placeholder="Nhập tiêu đề chủ đề..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tags (phân cách bằng dấu phẩy)</label>
                        <input type="text" class="form-control" placeholder="VD: laptop, dell, tư vấn">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nội dung</label>
                        <textarea class="form-control form-textarea" rows="8" placeholder="Nhập nội dung chi tiết..." required></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Đăng chủ đề
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="auth-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Đăng nhập</h2>
                <span class="close" onclick="closeLoginModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div class="form-group">
                        <label>Email hoặc tên đăng nhập</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox"> Ghi nhớ đăng nhập
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Đăng nhập</button>
                </form>
                <div class="auth-footer">
                    <p>Chưa có tài khoản? <a href="#" onclick="switchToRegister()">Đăng ký ngay</a></p>
                    <p><a href="#" onclick="forgotPassword()">Quên mật khẩu?</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="auth-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Đăng ký tài khoản</h2>
                <span class="close" onclick="closeRegisterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="registerForm">
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" required> Tôi đồng ý với <a href="#">Điều khoản sử dụng</a>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Đăng ký</button>
                </form>
                <div class="auth-footer">
                    <p>Đã có tài khoản? <a href="#" onclick="switchToLogin()">Đăng nhập ngay</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>        // Forum functionality
        function showSection(section) {
            // Update navigation
            document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Hide all sections
            document.getElementById('categories-section').style.display = 'none';
            document.getElementById('topics-section').style.display = 'none';
            document.getElementById('topic-detail').classList.remove('active');

            // Show selected section
            if (section === 'all' || section === 'recent' || section === 'popular' || section === 'trending') {
                document.getElementById('categories-section').style.display = 'block';
                
                // Update section title based on selection
                const sectionTitle = document.querySelector('#categories-section .section-title');
                const icons = {
                    'all': '<i class="fas fa-list"></i> Tất cả thảo luận',
                    'recent': '<i class="fas fa-clock"></i> Thảo luận mới nhất',
                    'popular': '<i class="fas fa-fire"></i> Thảo luận phổ biến',
                    'trending': '<i class="fas fa-trending-up"></i> Thảo luận thịnh hành'
                };
                sectionTitle.innerHTML = icons[section] || icons['all'];
            } else if (section === 'topics') {
                document.getElementById('topics-section').style.display = 'block';
            }
        }        function showTopicDetail(topicId) {
            console.log('Showing topic detail for ID:', topicId);
            
            // Hide other sections
            document.getElementById('categories-section').style.display = 'none';
            document.getElementById('topics-section').style.display = 'none';
            
            // Show topic detail
            document.getElementById('topic-detail').classList.add('active');
            
            // Set current topic ID for comments - QUAN TRỌNG!
            window.currentTopicId = topicId;
            console.log('Set currentTopicId to:', window.currentTopicId);
            
            // Load topic content based on ID
            loadTopicContent(topicId);
        }function loadTopicContent(topicId) {
            // Load topic content from database via AJAX
            fetch(`get_topic_detail.php?id=${topicId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('detail-title').textContent = data.topic.title;
                        document.getElementById('detail-content').innerHTML = formatPostContent(data.topic.content);
                        
                        // Update meta information
                        const metaDiv = document.querySelector('.detail-meta');
                        if (metaDiv && data.topic.author) {
                            metaDiv.innerHTML = `
                                <span><i class="fas fa-user"></i> ${data.topic.author}</span>
                                <span><i class="fas fa-clock"></i> ${data.topic.time_ago}</span>
                                <span><i class="fas fa-eye"></i> ${data.topic.views} lượt xem</span>
                                <span><i class="fas fa-tag"></i> ${data.topic.category}</span>
                            `;
                        }
                        
                        // Update like count
                        const likeBtn = document.querySelector('.btn-like');
                        if (likeBtn) {
                            likeBtn.innerHTML = `<i class="fas fa-heart"></i> Thích (${data.topic.likes})`;
                        }
                        
                        // Load and display comments
                        loadComments(data.comments);
                    } else {
                        // Hiển thị thông báo lỗi và quay lại danh sách
                        showNotification(data.message || 'Bài viết không tồn tại hoặc chưa được duyệt', 'error');
                        backToTopics();
                    }
                })
                .catch(error => {
                    console.log('Error loading topic from database:', error);
                    showNotification('Không thể tải bài viết', 'error');
                    backToTopics();
                });
        }
        
        function formatPostContent(content) {
            // Convert line breaks to paragraphs
            return content.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>');
        }
        
        function loadComments(comments) {
            const repliesSection = document.querySelector('.replies-section');
            const repliesTitle = repliesSection.querySelector('.replies-title');
            
            // Update replies count
            repliesTitle.innerHTML = `<i class="fas fa-comments"></i> Trả lời (${comments.length})`;
            
            // Remove existing reply items (keep the reply form)
            const existingReplies = repliesSection.querySelectorAll('.reply-item');
            existingReplies.forEach(reply => reply.remove());
            
            // Add new comments
            comments.forEach(comment => {
                const replyItem = document.createElement('div');
                replyItem.className = 'reply-item';
                replyItem.innerHTML = `
                    <div class="reply-header">
                        <span class="reply-author"><i class="fas fa-user-circle"></i> ${comment.author}</span>
                        <span class="reply-time">${comment.time_ago}</span>
                    </div>
                    <div class="reply-content">${formatPostContent(comment.content)}</div>
                    <div class="reply-actions">
                        <button class="btn-action" onclick="likeReply(this)">
                            <i class="fas fa-thumbs-up"></i> Thích (${comment.likes})
                        </button>
                        <button class="btn-action" onclick="replyToReply(this)">
                            <i class="fas fa-reply"></i> Trả lời
                        </button>
                    </div>
                `;
                
                // Insert before reply form
                const replyForm = repliesSection.querySelector('.reply-form');
                repliesSection.insertBefore(replyItem, replyForm);
            });
        }        function backToTopics() {
            document.getElementById('topic-detail').classList.remove('active');
            document.getElementById('topics-section').style.display = 'block';
        }        function openNewTopicModal() {
            // Kiểm tra đăng nhập trước khi mở modal
            <?php if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']): ?>
                showNotification('Vui lòng đăng nhập để tạo chủ đề mới!', 'warning');
                openLoginModal();
                return;
            <?php endif; ?>
            
            document.getElementById('newTopicModal').style.display = 'block';
        }
        
        function showLoginRequiredMessage() {
            showNotification('Vui lòng đăng nhập để tạo chủ đề thảo luận!', 'warning');
            setTimeout(() => {
                openLoginModal();
            }, 1000);
        }

        function closeNewTopicModal() {
            document.getElementById('newTopicModal').style.display = 'none';
        }

        function openLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
        }

        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }

        function openRegisterModal() {
            document.getElementById('registerModal').style.display = 'block';
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
        }

        function switchToRegister() {
            closeLoginModal();
            openRegisterModal();
        }

        function switchToLogin() {
            closeRegisterModal();
            openLoginModal();
        }

        function forgotPassword() {
            alert('Chức năng quên mật khẩu sẽ được triển khai sớm!');
        }

        function toggleLike(button) {
            button.classList.toggle('liked');
            const count = button.textContent.match(/\d+/)[0];
            const newCount = button.classList.contains('liked') ? 
                parseInt(count) + 1 : parseInt(count) - 1;
            button.innerHTML = `<i class="fas fa-heart"></i> Thích (${newCount})`;
        }

        function likeReply(button) {
            const count = button.textContent.match(/\d+/)[0];
            const newCount = parseInt(count) + 1;
            button.innerHTML = `<i class="fas fa-thumbs-up"></i> Thích (${newCount})`;
        }

        function sharePost() {
            alert('Chức năng chia sẻ sẽ được triển khai sớm!');
        }

        function reportPost() {
            alert('Báo cáo đã được gửi đi. Cảm ơn bạn!');
        }

        function replyToReply(button) {
            alert('Chức năng trả lời comment sẽ được triển khai!');
        }        function submitReply() {
            const textarea = document.querySelector('.reply-form textarea');
            const content = textarea.value.trim();
            
            if (!content) {
                showNotification('Vui lòng nhập nội dung trả lời!', 'error');
                return;
            }
            
            if (content.length < 5) {
                showNotification('Bình luận phải có ít nhất 5 ký tự!', 'error');
                return;
            }
            
            // Kiểm tra đăng nhập
            <?php if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']): ?>
                showNotification('Vui lòng đăng nhập để bình luận!', 'error');
                openLoginModal();
                return;
            <?php endif; ?>
            
            // Lấy post_id từ URL hoặc global variable
            const currentTopicId = getCurrentTopicId();
            if (!currentTopicId) {
                showNotification('Không thể xác định bài viết!', 'error');
                return;
            }
            
            // Gửi comment
            const formData = new FormData();
            formData.append('post_id', currentTopicId);
            formData.append('content', content);
            
            // Show loading
            const originalValue = textarea.value;
            textarea.value = 'Đang gửi bình luận...';
            textarea.disabled = true;
            
            fetch('post_comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Bình luận đã được đăng thành công!', 'success');
                    textarea.value = '';
                    
                    // Thêm comment mới vào danh sách
                    addNewComment(data.comment);
                } else {
                    showNotification('Lỗi: ' + data.message, 'error');
                    textarea.value = originalValue;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra khi đăng bình luận!', 'error');
                textarea.value = originalValue;
            })
            .finally(() => {
                textarea.disabled = false;
            });
        }
          // Function để lấy topic ID hiện tại
        function getCurrentTopicId() {
            // Lấy từ global variable được set khi click vào topic
            if (window.currentTopicId) {
                console.log('getCurrentTopicId: Found currentTopicId =', window.currentTopicId);
                return window.currentTopicId;
            }
            
            // Có thể lấy từ URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const topicId = urlParams.get('topic');
            if (topicId) {
                console.log('getCurrentTopicId: Found from URL =', topicId);
                return topicId;
            }
            
            console.log('getCurrentTopicId: No topic ID found');
            return null;
        }
        
        // Function để thêm comment mới vào danh sách
        function addNewComment(comment) {
            const repliesSection = document.querySelector('.replies-section');
            const replyForm = repliesSection.querySelector('.reply-form');
            const repliesTitle = repliesSection.querySelector('.replies-title');
            
            // Update replies count
            const currentCount = parseInt(repliesTitle.textContent.match(/\d+/)[0]) || 0;
            repliesTitle.innerHTML = `<i class="fas fa-comments"></i> Trả lời (${currentCount + 1})`;
            
            // Create new reply element
            const replyItem = document.createElement('div');
            replyItem.className = 'reply-item';
            replyItem.innerHTML = `
                <div class="reply-header">
                    <span class="reply-author"><i class="fas fa-user-circle"></i> ${comment.author}</span>
                    <span class="reply-time">${comment.time_ago}</span>
                </div>
                <div class="reply-content">${formatPostContent(comment.content)}</div>
                <div class="reply-actions">
                    <button class="btn-action" onclick="likeReply(this)">
                        <i class="fas fa-thumbs-up"></i> Thích (0)
                    </button>
                    <button class="btn-action" onclick="replyToReply(this)">
                        <i class="fas fa-reply"></i> Trả lời
                    </button>
                </div>
            `;
            
            // Insert before reply form with animation
            repliesSection.insertBefore(replyItem, replyForm);
            
            // Scroll to new comment
            replyItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }// Notification function
        function showNotification(message, type = 'success') {
            // Create notification element
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
                max-width: 400px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            } else if (type === 'error') {
                notification.style.background = 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)';
            } else if (type === 'warning') {
                notification.style.background = 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)';
                notification.style.color = '#333';
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Hide notification after 4 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('email', this.querySelector('input[type="text"]').value);
            formData.append('password', this.querySelector('input[type="password"]').value);
            
            // Validate form
            if (!formData.get('email').trim()) {
                showNotification('Vui lòng nhập email hoặc tên đăng nhập!', 'error');
                return;
            }
            
            if (!formData.get('password').trim()) {
                showNotification('Vui lòng nhập mật khẩu!', 'error');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...';
            submitBtn.disabled = true;
            
            // Send login request
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())            .then(data => {
                if (data.success) {
                    showNotification('Đăng nhập thành công!', 'success');
                    closeLoginModal();
                    
                    // Kiểm tra nếu là admin thì chuyển hướng đến admin panel
                    if (data.redirect && data.redirect === 'admin.php') {
                        setTimeout(() => {
                            window.location.href = 'admin.php';
                        }, 1000);
                    } else {
                        // Refresh page to update login state
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    showNotification('Lỗi đăng nhập: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                showNotification('Có lỗi xảy ra khi đăng nhập!', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Handle register form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const inputs = this.querySelectorAll('input');
            const fullName = inputs[0].value;
            const email = inputs[1].value;
            const username = inputs[2].value;
            const password = inputs[3].value;
            const confirmPassword = inputs[4].value;
            const phone = inputs[5].value;
            const agreeTerms = inputs[6].checked;
            
            // Validate form
            if (!fullName.trim()) {
                showNotification('Vui lòng nhập họ và tên!', 'error');
                return;
            }
            
            if (!email.trim()) {
                showNotification('Vui lòng nhập email!', 'error');
                return;
            }
            
            if (!username.trim()) {
                showNotification('Vui lòng nhập tên đăng nhập!', 'error');
                return;
            }
            
            if (!password.trim()) {
                showNotification('Vui lòng nhập mật khẩu!', 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                showNotification('Mật khẩu xác nhận không khớp!', 'error');
                return;
            }
            
            if (password.length < 6) {
                showNotification('Mật khẩu phải có ít nhất 6 ký tự!', 'error');
                return;
            }
            
            if (!agreeTerms) {
                showNotification('Vui lòng đồng ý với điều khoản sử dụng!', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('full_name', fullName);
            formData.append('email', email);
            formData.append('username', username);
            formData.append('password', password);
            formData.append('phone', phone);
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng ký...';
            submitBtn.disabled = true;
            
            // Send register request
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
                    closeRegisterModal();
                    setTimeout(() => {
                        openLoginModal();
                    }, 1000);
                } else {
                    showNotification('Lỗi đăng ký: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Register error:', error);
                showNotification('Có lỗi xảy ra khi đăng ký!', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });        // Handle new topic form submission
        document.getElementById('newTopicForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Kiểm tra đăng nhập ngay từ đầu
            <?php if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']): ?>
                showNotification('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại!', 'warning');
                closeNewTopicModal();
                openLoginModal();
                return;
            <?php endif; ?>
            
            // Lấy dữ liệu từ form
            const formData = new FormData();
            formData.append('title', document.querySelector('input[placeholder="Nhập tiêu đề chủ đề..."]').value);
            formData.append('content', document.querySelector('textarea[placeholder="Nhập nội dung chi tiết..."]').value);
            formData.append('category_id', document.querySelector('select').value);
            formData.append('tags', document.querySelector('input[placeholder="VD: laptop, dell, tư vấn"]').value);
            
            // Validate form
            if (!formData.get('title').trim()) {
                showNotification('Vui lòng nhập tiêu đề!', 'error');
                return;
            }
            
            if (!formData.get('content').trim()) {
                showNotification('Vui lòng nhập nội dung!', 'error');
                return;
            }
            
            if (formData.get('title').length < 10) {
                showNotification('Tiêu đề phải có ít nhất 10 ký tự!', 'error');
                return;
            }
            
            if (formData.get('content').length < 20) {
                showNotification('Nội dung phải có ít nhất 20 ký tự!', 'error');
                return;
            }
            
            // Hiển thị loading
            const submitBtn = document.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng...';
            submitBtn.disabled = true;
            
            // Gửi request
            fetch('create_discussion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())            .then(data => {
                if (data.success) {
                    showNotification('Bài thảo luận đã được đăng thành công! Bài viết của bạn đã xuất hiện trên trang thảo luận.', 'success');
                    closeNewTopicModal();
                    this.reset();
                    
                    // Refresh trang ngay lập tức để hiển thị bài viết mới
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Lỗi: ' + data.message, 'error');
                    
                    // Nếu lỗi do chưa đăng nhập, mở modal login
                    if (data.message && data.message.includes('đăng nhập')) {
                        closeNewTopicModal();
                        setTimeout(() => {
                            openLoginModal();
                        }, 1500);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra khi tạo bài thảo luận!', 'error');
            })
            .finally(() => {
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
          // Function để refresh danh sách bài viết
        function refreshTopicsList() {
            // Reload trang để cập nhật danh sách bài viết mới (nhanh hơn vì bài đã được duyệt)
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('newTopicModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }

            const loginModal = document.getElementById('loginModal');
            if (event.target === loginModal) {
                loginModal.style.display = 'none';
            }

            const registerModal = document.getElementById('registerModal');
            if (event.target === registerModal) {
                registerModal.style.display = 'none';
            }
        }

        // Search functionality
        document.querySelector('.search-btn').addEventListener('click', function() {
            const searchTerm = document.querySelector('.search-input').value;
            if (searchTerm.trim()) {
                alert(`Tìm kiếm: "${searchTerm}"`);
            }
        });

        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.search-btn').click();
            }
        });
    </script>
</body>
</html>