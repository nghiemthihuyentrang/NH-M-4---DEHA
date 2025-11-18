<?php
session_start();

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'];
$user_name = $is_logged_in ? $_SESSION['full_name'] : '';
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Kết nối database
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Xử lý đánh giá bài đăng
    if (isset($_GET['action']) && $_GET['action'] === 'rate' && $is_logged_in) {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];

        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = $input['post_id'] ?? null;
        $rating = $input['rating'] ?? null;
        $user_id = $_SESSION['user_id'];

        if (!$post_id || !$rating || $rating < 1 || $rating > 5) {
            $response['message'] = 'Dữ liệu không hợp lệ!';
            echo json_encode($response);
            exit;
        }

        // Kiểm tra bài đăng tồn tại
        $stmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = ?");
        $stmt->execute([$post_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Bài đăng không tồn tại!';
            echo json_encode($response);
            exit;
        }

        // Thêm hoặc cập nhật đánh giá
        $stmt = $pdo->prepare("
            INSERT INTO post_ratings (post_id, user_id, rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?, created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$post_id, $user_id, $rating, $rating]);

        // Lấy đánh giá trung bình và số lượng đánh giá
        $stmt = $pdo->prepare("
            SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(rating_id) as rating_count
            FROM post_ratings
            WHERE post_id = ?
        ");
        $stmt->execute([$post_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['message'] = 'Đánh giá thành công!';
        $response['avg_rating'] = floatval($result['avg_rating']);
        $response['rating_count'] = intval($result['rating_count']);

        echo json_encode($response);
        exit;
    }

    // Debug: Kiểm tra tổng số bài đăng
    $total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $total_listings = $pdo->query("SELECT COUNT(*) FROM posts WHERE post_type = 'listing'")->fetchColumn();
    $active_listings = $pdo->query("SELECT COUNT(*) FROM posts WHERE post_type = 'listing' AND status = 'active'")->fetchColumn();
    
    // Lấy danh sách bài đăng với thông tin đánh giá trung bình
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            u.full_name, 
            u.username, 
            c.category_name,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.rating_id) as rating_count,
            (SELECT r2.rating FROM post_ratings r2 WHERE r2.post_id = p.post_id AND r2.user_id = :user_id) as user_rating
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN post_ratings r ON p.post_id = r.post_id
        WHERE p.status IS NOT NULL
        GROUP BY p.post_id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute(['user_id' => $is_logged_in ? $_SESSION['user_id'] : 0]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh mục
    $categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $db_error = null;
    
} catch(PDOException $e) {
    $posts = [];
    $categories = [];
    $total_posts = 0;
    $total_listings = 0;
    $active_listings = 0;
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mua Bán - ElectroReview</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Marketplace specific styles */
        .marketplace-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .marketplace-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 120px 0 40px;
            text-align: center;
            margin-top: 0;
        }
        .marketplace-nav {
            background: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .nav-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #495057;
            font-weight: 500;
        }
        .nav-tab:hover, .nav-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .post-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            font-size: 14px;
        }
        .image-upload {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .image-upload:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .btn-post {
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
        .btn-post:hover {
            transform: translateY(-2px);
        }
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .post-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .post-card:hover {
            transform: translateY(-5px);
        }
        .post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8f9fa;
        }
        .post-content {
            padding: 20px;
        }
        .post-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .post-price {
            font-size: 20px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .post-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        .post-actions {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            flex: 1;
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 14px;
        }
        .btn-action:hover {
            background: #f8f9fa;
        }
        .btn-contact {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .btn-contact:hover {
            background: #218838;
        }
        .no-results {
            grid-column: 1 / -1;
            background: #f9f9f9;
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
        }
        .no-results-content {
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .no-results i {
            font-size: 48px;
            color: #aaa;
            margin-bottom: 15px;
        }
        .no-results h3 {
            color: #555;
            margin-bottom: 10px;
        }
        .no-results p {
            color: #777;
            margin-bottom: 20px;
        }
        .comments-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .comment {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
        }
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        .comment-content {
            flex: 1;
        }
        .comment-author {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .comment-text {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }
        .comment-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .comment-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-comment {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .search-filter {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1.5fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .second-row {
            margin-top: 5px;
            align-items: end;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
        .price-range-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .price-separator {
            color: #999;
            font-weight: bold;
        }
        .search-buttons {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        .search-buttons button {
            flex: 1;
            padding: 10px;
            font-size: 14px;
        }
        .filter-results {
            display: flex;
            flex-direction: column;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .filter-results p {
            margin: 0 0 10px 0;
        }
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .filter-tag {
            background: #f0f4ff;
            border: 1px solid #d0d7f5;
            color: #4a5568;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .filter-tag i {
            cursor: pointer;
            font-size: 10px;
        }
        .filter-tag i:hover {
            color: #e53e3e;
        }
        .auth-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .close {
            color: #aaa;
            cursor: pointer;
            font-size: 18px;
        }
        .close:hover {
            color: black;
        }
        .auth-footer {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        /* Rating Styles */
        .post-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }
        .rating-stars i {
            margin-right: 4px;
        }
        .user-rating {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .rating-input {
            display: inline-flex;
            gap: 4px;
        }
        .rating-star {
            transition: color 0.2s ease;
        }
        .rating-star:hover,
        .rating-star.rated {
            color: #f1c40f !important;
        }
        .rating-star:hover ~ .rating-star {
            color: #ccc !important;
        }
        @media (max-width: 768px) {
            .form-row, .search-row {
                grid-template-columns: 1fr;
            }
            .posts-grid {
                grid-template-columns: 1fr;
            }
            .nav-tabs {
                flex-wrap: wrap;
                gap: 10px;
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
                    <li><a href="mua-ban.php" class="active">Mua bán, trao đổi</a></li>
                    <li><a href="thao-luan.php">Thảo luận</a></li>
                    <li><a href="lien-he.php">Liên hệ</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if ($is_logged_in): ?>
                    <span class="user-welcome">Xin chào, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                    <?php if ($is_admin): ?>
                        <a href="admin.php" class="btn btn-warning">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline" onclick="openLoginModal()">Đăng nhập</button>
                    <button class="btn btn-primary" onclick="openRegisterModal()">Đăng ký</button>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <!-- Marketplace Header -->
    <section class="marketplace-header">
        <div class="container">
            <h1>Chợ Điện Tử Cũ</h1>
            <p>Mua bán, trao đổi thiết bị điện tử cũ an toàn và tin cậy</p>
        </div>
    </section>

    <!-- Navigation Tabs -->
    <section class="marketplace-nav">
        <div class="container">
            <ul class="nav-tabs">
                <li class="nav-tab active" onclick="showTab('browse')">
                    <i class="fas fa-search"></i> Tìm kiếm bài đăng
                </li>
                <li class="nav-tab" onclick="showTab('post')">
                    <i class="fas fa-plus"></i> Đăng bài
                </li>
                <li class="nav-tab" onclick="showTab('my-posts')">
                    <i class="fas fa-user"></i> Bài của tôi
                </li>
            </ul>
        </div>
    </section>

    <div class="marketplace-container">
        <!-- Browse Posts Tab -->
        <div id="browse" class="tab-content active">
            <!-- Search and Filter -->
            <div class="search-filter">
                <h3 class="filter-title"><i class="fas fa-search"></i> Tìm kiếm bài đăng</h3>
                <div class="search-row">
                    <div class="form-group">
                        <label for="searchInput">Từ khóa</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm..." onkeyup="filterPosts()">
                    </div>
                    <div class="form-group">
                        <label for="searchType">Loại tìm kiếm</label>
                        <select id="searchType" class="form-select" onchange="updateSearchPlaceholder()">
                            <option value="all">Tất cả</option>
                            <option value="author">Người đăng</option>
                            <option value="topic">Chủ đề</option>
                            <option value="product">Sản phẩm</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="categoryFilter">Danh mục</label>
                        <select id="categoryFilter" class="form-select" onchange="filterPosts()">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priceRange">Khoảng giá</label>
                        <div class="price-range-inputs">
                            <input type="number" id="minPrice" class="form-control" placeholder="Từ" min="0" onchange="filterPosts()">
                            <span class="price-separator">-</span>
                            <input type="number" id="maxPrice" class="form-control" placeholder="Đến" min="0" onchange="filterPosts()">
                        </div>
                    </div>
                </div>
                <div class="search-row second-row">
                    <div class="form-group">
                        <label for="sortFilter">Sắp xếp theo</label>
                        <select id="sortFilter" class="form-select" onchange="filterPosts()">
                            <option value="">Mặc định</option>
                            <option value="newest">Mới nhất</option>
                            <option value="price-low">Giá thấp đến cao</option>
                            <option value="price-high">Giá cao đến thấp</option>
                            <option value="title-az">Tên A-Z</option>
                            <option value="title-za">Tên Z-A</option>
                            <option value="rating-high">Đánh giá cao nhất</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="postTypeFilter">Loại bài đăng</label>
                        <select id="postTypeFilter" class="form-select" onchange="filterPosts()">
                            <option value="">Tất cả loại</option>
                            <option value="sell">Bán</option>
                            <option value="buy">Mua</option>
                            <option value="exchange">Trao đổi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="conditionFilter">Tình trạng</label>
                        <select id="conditionFilter" class="form-select" onchange="filterPosts()">
                            <option value="">Tất cả tình trạng</option>
                            <option value="like_new">Như mới (95-99%)</option>
                            <option value="good">Tốt (85-94%)</option>
                            <option value="fair">Khá (70-84%)</option>
                            <option value="poor">Cần sửa chữa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="minRatingFilter">Đánh giá tối thiểu</label>
                        <select id="minRatingFilter" class="form-select" onchange="filterPosts()">
                            <option value=""> Tất cả</option>
                            <option value="1">3 sao trở lên</option>
                            <option value="2">4 sao trở lên</option>
                            
                        </select>
                    </div>
                    <div class="form-group search-buttons">
                        <button class="btn btn-primary" onclick="filterPosts()">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        <button class="btn btn-outline" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Xóa lọc
                        </button>
                    </div>
                </div>
                <div id="filterResults" class="filter-results">
                    <p>Hiển thị <strong><?php echo count($posts); ?></strong> bài đăng</p>
                    <div id="activeFilters" class="active-filters"></div>
                </div>
            </div>
            <!-- Posts Grid -->
            <div class="posts-grid">
                <?php if (empty($posts)): ?>
                    <div class="post-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                        <h3>Chưa có bài đăng nào</h3>
                        <p>Hãy là người đầu tiên đăng bài mua bán trên ElectroReview!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card" 
                             data-post-id="<?php echo $post['post_id']; ?>"
                             data-post-type="<?php echo htmlspecialchars($post['post_type'] ?? ''); ?>"
                             data-condition="<?php echo htmlspecialchars($post['condition_item'] ?? ''); ?>"
                             data-avg-rating="<?php echo round($post['avg_rating'], 1); ?>">
                            <?php 
                            $images = json_decode($post['images'] ?? '[]', true);
                            $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                            $placeholderColors = [
                                '#667eea', '#764ba2', '#f093fb', '#f5576c', 
                                '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'
                            ];
                            $colorIndex = $post['post_id'] % count($placeholderColors);
                            $placeholderColor = $placeholderColors[$colorIndex];
                            ?>
                            <div class="post-image-container" style="position: relative; width: 100%; height: 200px; overflow: hidden;">
                                <?php if ($firstImage): ?>
                                    <?php 
                                    $imageSrc = $firstImage;
                                    if (!filter_var($firstImage, FILTER_VALIDATE_URL)) {
                                        if (str_starts_with($firstImage, 'uploads/')) {
                                            $imageSrc = '../../' . $firstImage;
                                        } elseif (!str_starts_with($firstImage, '/') && !str_starts_with($firstImage, '../../')) {
                                            $imageSrc = '../../' . $firstImage;
                                        }
                                    }
                                    ?>
                                    <img class="post-image" 
                                         src="<?php echo htmlspecialchars($imageSrc); ?>" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="post-image-placeholder" 
                                         style="background: linear-gradient(135deg, <?php echo $placeholderColor; ?> 0%, <?php echo $placeholderColor; ?>aa 100%); 
                                                width: 100%; height: 100%; 
                                                display: none; align-items: center; justify-content: center; 
                                                color: white; font-size: 48px; position: absolute; top: 0; left: 0;">
                                        <div style="text-align: center;">
                                            <i class="fas fa-image"></i>
                                            <div style="font-size: 14px; margin-top: 10px; font-weight: 500;">
                                                <?php echo htmlspecialchars(substr($post['title'], 0, 20)); ?>...
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="post-image-placeholder" 
                                         style="background: linear-gradient(135deg, <?php echo $placeholderColor; ?> 0%, <?php echo $placeholderColor; ?>aa 100%); 
                                                width: 100%; height: 100%; 
                                                display: flex; align-items: center; justify-content: center; 
                                                color: white; font-size: 48px;">
                                        <div style="text-align: center;">
                                            <i class="fas fa-laptop"></i>
                                            <div style="font-size: 14px; margin-top: 10px; font-weight: 500;">
                                                <?php echo htmlspecialchars(substr($post['title'], 0, 20)); ?>...
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <?php if ($post['price']): ?>
                                    <div class="post-price"><?php echo number_format($post['price'], 0, ',', '.'); ?> VNĐ</div>
                                <?php else: ?>
                                    <div class="post-price" style="color: #28a745;">Trao đổi</div>
                                <?php endif; ?>
                                <p class="post-description"><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . '...'; ?></p>
                                <div class="post-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['full_name'] ?? $post['username'] ?? 'Ẩn danh'); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo date('d/m H:i', strtotime($post['created_at'])); ?></span>
                                </div>
                                <div class="post-meta" style="margin-bottom: 15px;">
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($post['category_name'] ?? 'Khác'); ?></span>
                                    <span class="badge <?php echo $post['status'] == 'pending' ? 'badge-warning' : 'badge-success'; ?>">
                                        <?php echo $post['status'] == 'pending' ? 'Chờ duyệt' : 'Hoạt động'; ?>
                                    </span>
                                    <div class="post-rating">
                                        <span class="rating-stars">
                                            <?php
                                            $avg_rating = round($post['avg_rating'], 1);
                                            $full_stars = floor($avg_rating);
                                            $half_star = $avg_rating - $full_stars >= 0.5 ? 1 : 0;
                                            $empty_stars = 5 - $full_stars - $half_star;
                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star" style="color: #f1c40f;"></i>';
                                            }
                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt" style="color: #f1c40f;"></i>';
                                            }
                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star" style="color: #ccc;"></i>';
                                            }
                                            ?>
                                        </span>
                                        <span>(<?php echo $avg_rating; ?> / <?php echo $post['rating_count']; ?> đánh giá)</span>
                                    </div>
                                </div>
                                <?php if ($is_logged_in): ?>
                                    <div class="user-rating" data-post-id="<?php echo $post['post_id']; ?>">
                                        <span>Đánh giá của bạn:</span>
                                        <div class="rating-input" data-current-rating="<?php echo $post['user_rating'] ?? 0; ?>">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star rating-star <?php echo ($post['user_rating'] >= $i) ? 'rated' : ''; ?>" data-value="<?php echo $i; ?>" style="color: #ccc; cursor: pointer;"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="post-actions">
                                    <button class="btn-action">
                                        <i class="fas fa-heart"></i> Yêu thích
                                    </button>
                                    <button class="btn-action">
                                        <i class="fas fa-share"></i> Chia sẻ
                                    </button>
                                    <button class="btn-action btn-contact">
                                        <i class="fas fa-phone"></i> Liên hệ
                                    </button>
                                </div>
                                <!-- Comments Section -->
                                <div class="comments-section">
                                    <div class="comment-form">
                                        <input type="text" class="comment-input" placeholder="Viết bình luận...">
                                        <button class="btn-comment">Gửi</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- Create Post Tab -->
        <div id="post" class="tab-content">
            <?php if ($is_logged_in): ?>
            <div class="post-form">
                <h2>Đăng bài bán/trao đổi</h2>
                <form id="postForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Tiêu đề bài đăng *</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="VD: Laptop Gaming ASUS ROG Strix G15" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Danh mục *</label>
                            <select id="category" name="category_id" class="form-select" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="post_type">Loại bài đăng *</label>
                            <select id="post_type" name="post_type" class="form-select" required>
                                <option value="">Chọn loại</option>
                                <option value="sell">Bán</option>
                                <option value="buy">Mua</option>
                                <option value="exchange">Trao đổi</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Giá (VNĐ)</label>
                            <input type="number" id="price" name="price" class="form-control" placeholder="15000000">
                            <small style="color: #666;">Để trống nếu trao đổi</small>
                        </div>
                        <div class="form-group">
                            <label for="condition">Tình trạng *</label>
                            <select id="condition" name="condition_item" class="form-select" required>
                                <option value="">Chọn tình trạng</option>
                                <option value="like_new">Như mới (95-99%)</option>
                                <option value="good">Tốt (85-94%)</option>
                                <option value="fair">Khá (70-84%)</option>
                                <option value="poor">Cần sửa chữa</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="location">Địa điểm *</label>
                        <input type="text" id="location" name="location" class="form-control" placeholder="VD: TP. Hồ Chí Minh" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Mô tả chi tiết *</label>
                        <textarea id="description" name="content" class="form-control" rows="6" placeholder="Mô tả chi tiết về sản phẩm: cấu hình, tình trạng, lý do bán, thời gian bảo hành còn lại..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Hình ảnh sản phẩm</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                        <div class="image-upload" onclick="document.getElementById('images').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                            <p>Kéo thả hoặc click để chọn hình ảnh</p>
                            <p style="font-size: 12px; color: #999;">Tối đa 5 hình, mỗi hình không quá 2MB</p>
                        </div>
                        <div id="imagePreview" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;"></div>
                    </div>
                    <button type="submit" class="btn-post">
                        <i class="fas fa-paper-plane"></i> Đăng bài
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="post-form">
                <h2>Bạn cần đăng nhập để đăng bài</h2>
                <p style="text-align: center; margin: 40px 0;">
                    <button class="btn btn-primary" onclick="openLoginModal()">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                    </button>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <!-- My Posts Tab -->
        <div id="my-posts" class="tab-content">
            <div class="post-form">
                <h2>Bài đăng của tôi</h2>
                <p>Quản lý các bài đăng mua bán của bạn</p>
                <div class="posts-grid">
                    <div class="post-card">
                        <div class="post-image" style="background: url('https://via.placeholder.com/350x200/dc3545/ffffff?text=My+Post') center/cover;"></div>
                        <div class="post-content">
                            <h3 class="post-title">MacBook Pro M1 13 inch</h3>
                            <div class="post-price">28,000,000 VNĐ</div>
                            <p class="post-description">MacBook Pro M1 chip, RAM 8GB, SSD 256GB. Tình trạng 98%, còn bảo hành 1 năm.</p>
                            <div class="post-meta">
                                <span><i class="fas fa-eye"></i> 234 lượt xem</span>
                                <span><i class="fas fa-heart"></i> 12 yêu thích</span>
                            </div>
                            <div class="post-actions">
                                <button class="btn-action">
                                    <i class="fas fa-edit"></i> Chỉnh sửa
                                </button>
                                <button class="btn-action">
                                    <i class="fas fa-pause"></i> Ẩn bài
                                </button>
                                <button class="btn-action" style="color: #dc3545;">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="auth-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Đăng nhập</h2>
                <span class="close" onclick="closeLoginModal()">×</span>
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
                <span class="close" onclick="closeRegisterModal()">×</span>
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

    <script>
        // Handle rating submission
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('rating-star')) {
                const ratingInput = e.target.parentElement;
                const postId = ratingInput.parentElement.getAttribute('data-post-id');
                const ratingValue = e.target.getAttribute('data-value');
                
                if (!postId || !ratingValue) return;
                
                // Show loading state
                const stars = ratingInput.querySelectorAll('.rating-star');
                stars.forEach(star => star.style.opacity = '0.5');
                
                // Send rating request
                fetch('mua-ban.php?action=rate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        rating: ratingValue
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update current rating
                        ratingInput.setAttribute('data-current-rating', ratingValue);
                        stars.forEach(star => {
                            star.style.opacity = '1';
                            const value = star.getAttribute('data-value');
                            star.classList.toggle('rated', value <= ratingValue);
                        });
                        
                        // Update average rating display
                        const postCard = ratingInput.closest('.post-card');
                        const ratingDisplay = postCard.querySelector('.post-rating');
                        if (ratingDisplay) {
                            const avgRating = data.avg_rating.toFixed(1);
                            const ratingCount = data.rating_count;
                            
                            // Update stars
                            const starsContainer = ratingDisplay.querySelector('.rating-stars');
                            starsContainer.innerHTML = '';
                            const fullStars = Math.floor(avgRating);
                            const halfStar = avgRating - fullStars >= 0.5 ? 1 : 0;
                            const emptyStars = 5 - fullStars - halfStar;
                            
                            for (let i = 0; i < fullStars; i++) {
                                starsContainer.innerHTML += '<i class="fas fa-star" style="color: #f1c40f;"></i>';
                            }
                            if (halfStar) {
                                starsContainer.innerHTML += '<i class="fas fa-star-half-alt" style="color: #f1c40f;"></i>';
                            }
                            for (let i = 0; i < emptyStars; i++) {
                                starsContainer.innerHTML += '<i class="far fa-star" style="color: #ccc;"></i>';
                            }
                            
                            // Update text
                            ratingDisplay.querySelector('span:last-child').textContent = `(${avgRating} / ${ratingCount} đánh giá)`;
                        }
                        
                        showNotification('Đánh giá thành công!', 'success');
                    } else {
                        showNotification('Lỗi: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Rating error:', error);
                    showNotification('Có lỗi xảy ra khi gửi đánh giá!', 'error');
                })
                .finally(() => {
                    stars.forEach(star => star.style.opacity = '1');
                });
            }
        });

        function showTab(tabName) {
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => tab.classList.remove('active'));
            const navTabs = document.querySelectorAll('.nav-tab');
            navTabs.forEach(tab => tab.classList.remove('active'));
            const targetTab = document.getElementById(tabName);
            if (targetTab) {
                targetTab.classList.add('active');
            }
            if (event && event.target) {
                const clickedTab = event.target.closest('.nav-tab');
                if (clickedTab) {
                    clickedTab.classList.add('active');
                }
            }
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-comment')) {
                e.preventDefault();
                const commentInput = e.target.previousElementSibling;
                const commentText = commentInput.value.trim();
                if (commentText) {
                    const commentSection = e.target.closest('.comments-section');
                    const commentForm = e.target.closest('.comment-form');
                    const newComment = document.createElement('div');
                    newComment.className = 'comment';
                    newComment.innerHTML = `
                        <div class="comment-avatar">U</div>
                        <div class="comment-content">
                            <div class="comment-author">Người dùng</div>
                            <div class="comment-text">${commentText}</div>
                        </div>
                    `;
                    commentSection.insertBefore(newComment, commentForm);
                    commentInput.value = '';
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const imagesInput = document.getElementById('images');
            if (imagesInput) {
                imagesInput.addEventListener('change', function(e) {
                    const files = e.target.files;
                    const preview = document.getElementById('imagePreview');
                    if (preview) {
                        preview.innerHTML = '';
                        if (files.length > 5) {
                            showNotification('Chỉ được chọn tối đa 5 hình ảnh!', 'warning');
                            return;
                        }
                        for (let i = 0; i < files.length; i++) {
                            const file = files[i];
                            if (file.size > 2 * 1024 * 1024) {
                                showNotification('Mỗi hình không được quá 2MB!', 'warning');
                                continue;
                            }
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const imgDiv = document.createElement('div');
                                imgDiv.style.cssText = 'position: relative; display: inline-block;';
                                imgDiv.innerHTML = `
                                    <img src="${e.target.result}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">
                                    <span onclick="this.parentElement.remove()" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px;">×</span>
                                `;
                                preview.appendChild(imgDiv);
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                });
            }

            const postForm = document.getElementById('postForm');
            if (postForm) {
                postForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('.btn-post');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng...';
                    submitBtn.disabled = true;
                    fetch('post_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            this.reset();
                            document.getElementById('imagePreview').innerHTML = '';
                            setTimeout(() => {
                                showTab('browse');
                                location.reload();
                            }, 2000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Có lỗi xảy ra khi đăng bài', 'error');
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }

            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData();
                    const inputs = this.querySelectorAll('input');
                    formData.append('email', inputs[0].value);
                    formData.append('password', inputs[1].value);
                    if (!formData.get('email').trim()) {
                        showNotification('Vui lòng nhập email hoặc tên đăng nhập!', 'error');
                        return;
                    }
                    if (!formData.get('password').trim()) {
                        showNotification('Vui lòng nhập mật khẩu!', 'error');
                        return;
                    }
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...';
                    submitBtn.disabled = true;
                    fetch('login.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Đăng nhập thành công!', 'success');
                            closeLoginModal();
                            if (data.redirect && data.redirect === 'admin.php') {
                                setTimeout(() => {
                                    window.location.href = 'admin.php';
                                }, 1000);
                            } else {
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
            }

            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const inputs = this.querySelectorAll('input');
                    const fullName = inputs[0].value;
                    const email = inputs[1].value;
                    const username = inputs[2].value;
                    const password = inputs[3].value;
                    const confirmPassword = inputs[4].value;
                    const phone = inputs[5].value;
                    const agreeTerms = inputs[6].checked;
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
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng ký...';
                    submitBtn.disabled = true;
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
                });
            }
        });

        function showNotification(message, type = 'success') {
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 2001;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                min-width: 300px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            `;
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            } else if (type === 'warning') {
                notification.style.background = 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)';
            } else if (type === 'error') {
                notification.style.background = 'linear-gradient(135deg, #dc3545 0%, #e83e8c 100%)';
            }
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
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

        window.onclick = function(event) {
            const loginModal = document.getElementById('loginModal');
            const registerModal = document.getElementById('registerModal');
            if (event.target === loginModal) {
                loginModal.style.display = 'none';
            }
            if (event.target === registerModal) {
                registerModal.style.display = 'none';
            }
        }

        function updateSearchPlaceholder() {
            const searchType = document.getElementById('searchType').value;
            const searchInput = document.getElementById('searchInput');
            switch(searchType) {
                case 'author':
                    searchInput.placeholder = 'Nhập tên người đăng...';
                    break;
                case 'topic':
                    searchInput.placeholder = 'Nhập chủ đề...';
                    break;
                case 'product':
                    searchInput.placeholder = 'Nhập tên sản phẩm...';
                    break;
                default:
                    searchInput.placeholder = 'Tìm kiếm...';
            }
        }

        function filterPosts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const searchType = document.getElementById('searchType').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const sortFilter = document.getElementById('sortFilter').value;
            const postTypeFilter = document.getElementById('postTypeFilter').value;
            const conditionFilter = document.getElementById('conditionFilter').value;
            const minPrice = document.getElementById('minPrice').value ? parseInt(document.getElementById('minPrice').value) : 0;
            const maxPrice = document.getElementById('maxPrice').value ? parseInt(document.getElementById('maxPrice').value) : Number.MAX_SAFE_INTEGER;
            const minRating = document.getElementById('minRatingFilter').value ? parseInt(document.getElementById('minRatingFilter').value) : 0;
            
            const postsGrid = document.querySelector('.posts-grid');
            const posts = Array.from(postsGrid.querySelectorAll('.post-card[data-post-id]'));
            
            let filteredPosts = posts.filter(post => {
                const title = post.querySelector('.post-title').textContent.toLowerCase();
                const description = post.querySelector('.post-description').textContent.toLowerCase();
                const author = post.querySelector('.fas.fa-user') ? 
                    post.querySelector('.fas.fa-user').parentElement.textContent.toLowerCase() : '';
                const price = extractPrice(post.querySelector('.post-price').textContent);
                const avgRating = parseFloat(post.getAttribute('data-avg-rating') || 0);
                
                let matchesSearch = true;
                if (searchTerm) {
                    switch(searchType) {
                        case 'author':
                            matchesSearch = author.includes(searchTerm);
                            break;
                        case 'topic':
                        case 'product':
                            matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
                            break;
                        default:
                            matchesSearch = title.includes(searchTerm) || 
                                           description.includes(searchTerm) || 
                                           author.includes(searchTerm);
                    }
                }
                
                let matchesCategory = true;
                if (categoryFilter) {
                    const categoryElement = post.querySelector('.fas.fa-tag');
                    if (categoryElement) {
                        const postCategory = categoryElement.parentElement.textContent.replace('🏷️', '').trim().toLowerCase();
                        matchesCategory = postCategory.includes(categoryFilter.toLowerCase());
                    }
                }
                
                let matchesPriceRange = true;
                if (minPrice > 0 || maxPrice < Number.MAX_SAFE_INTEGER) {
                    matchesPriceRange = price >= minPrice && price <= maxPrice;
                }
                
                let matchesPostType = true;
                if (postTypeFilter) {
                    const postType = post.getAttribute('data-post-type') || '';
                    matchesPostType = postType.includes(postTypeFilter);
                }
                
                let matchesCondition = true;
                if (conditionFilter) {
                    const condition = post.getAttribute('data-condition') || '';
                    matchesCondition = condition.includes(conditionFilter);
                }
                
                let matchesRating = true;
                if (minRating) {
                    matchesRating = avgRating >= minRating;
                }
                
                return matchesSearch && 
                       matchesCategory && 
                       matchesPriceRange && 
                       matchesPostType && 
                       matchesCondition && 
                       matchesRating;
            });
            
            if (sortFilter) {
                filteredPosts.sort((a, b) => {
                    switch (sortFilter) {
                        case 'newest':
                            const idA = parseInt(a.getAttribute('data-post-id'));
                            const idB = parseInt(b.getAttribute('data-post-id'));
                            return idB - idA;
                        case 'price-low':
                        case 'price-high':
                            const priceA = extractPrice(a.querySelector('.post-price').textContent);
                            const priceB = extractPrice(b.querySelector('.post-price').textContent);
                            return sortFilter === 'price-low' ? priceA - priceB : priceB - priceA;
                        case 'title-az':
                        case 'title-za':
                            const titleA = a.querySelector('.post-title').textContent;
                            const titleB = b.querySelector('.post-title').textContent;
                            return sortFilter === 'title-az' 
                                ? titleA.localeCompare(titleB) 
                                : titleB.localeCompare(titleA);
                        case 'rating-high':
                            const ratingA = parseFloat(a.getAttribute('data-avg-rating') || 0);
                            const ratingB = parseFloat(b.getAttribute('data-avg-rating') || 0);
                            return ratingB - ratingA;
                    }
                    return 0;
                });
            }
            
            posts.forEach(post => post.style.display = 'none');
            if (filteredPosts.length === 0) {
                let noResultsElement = document.getElementById('noResults');
                if (!noResultsElement) {
                    noResultsElement = document.createElement('div');
                    noResultsElement.id = 'noResults';
                    noResultsElement.className = 'no-results';
                    noResultsElement.innerHTML = `
                        <div class="no-results-content">
                            <i class="fas fa-search"></i>
                            <h3>Không tìm thấy kết quả phù hợp</h3>
                            <p>Vui lòng thử lại với các bộ lọc khác</p>
                            <button class="btn btn-outline" onclick="clearFilters()">
                                Xóa bộ lọc
                            </button>
                        </div>
                    `;
                    postsGrid.appendChild(noResultsElement);
                }
                noResultsElement.style.display = 'block';
            } else {
                filteredPosts.forEach(post => post.style.display = 'block');
                const noResultsElement = document.getElementById('noResults');
                if (noResultsElement) {
                    noResultsElement.style.display = 'none';
                }
            }
            
            updateFilterResults(filteredPosts.length, posts.length);
            updateActiveFilters();
        }
        
        function extractPrice(priceText) {
            const numbers = priceText.replace(/[^\d]/g, '');
            return numbers ? parseInt(numbers) : 0;
        }
        
        function updateFilterResults(filtered, total) {
            const resultsDiv = document.querySelector('#filterResults p');
            if (filtered === total) {
                resultsDiv.innerHTML = `Hiển thị <strong>${filtered}</strong> bài đăng`;
            } else {
                resultsDiv.innerHTML = `Hiển thị <strong>${filtered}</strong> / ${total} bài đăng`;
            }
        }
        
        function updateActiveFilters() {
            const activeFiltersDiv = document.getElementById('activeFilters');
            activeFiltersDiv.innerHTML = '';
            const searchTerm = document.getElementById('searchInput').value;
            if (searchTerm) {
                const searchType = document.getElementById('searchType').value;
                let prefix = 'Tìm kiếm';
                switch(searchType) {
                    case 'author': prefix = 'Người đăng'; break;
                    case 'topic': prefix = 'Chủ đề'; break;
                    case 'product': prefix = 'Sản phẩm'; break;
                }
                addFilterTag(activeFiltersDiv, `${prefix}: ${searchTerm}`, () => {
                    document.getElementById('searchInput').value = '';
                    filterPosts();
                });
            }
            const categoryFilter = document.getElementById('categoryFilter').value;
            if (categoryFilter) {
                addFilterTag(activeFiltersDiv, `Danh mục: ${categoryFilter}`, () => {
                    document.getElementById('categoryFilter').value = '';
                    filterPosts();
                });
            }
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            if (minPrice && maxPrice) {
                addFilterTag(activeFiltersDiv, `Giá: ${formatPrice(minPrice)} - ${formatPrice(maxPrice)} VNĐ`, () => {
                    document.getElementById('minPrice').value = '';
                    document.getElementById('maxPrice').value = '';
                    filterPosts();
                });
            } else if (minPrice) {
                addFilterTag(activeFiltersDiv, `Giá từ: ${formatPrice(minPrice)} VNĐ`, () => {
                    document.getElementById('minPrice').value = '';
                    filterPosts();
                });
            } else if (maxPrice) {
                addFilterTag(activeFiltersDiv, `Giá đến: ${formatPrice(maxPrice)} VNĐ`, () => {
                    document.getElementById('maxPrice').value = '';
                    filterPosts();
                });
            }
            const postTypeFilter = document.getElementById('postTypeFilter').value;
            if (postTypeFilter) {
                let postTypeLabel = '';
                switch(postTypeFilter) {
                    case 'sell': postTypeLabel = 'Bán'; break;
                    case 'buy': postTypeLabel = 'Mua'; break;
                    case 'exchange': postTypeLabel = 'Trao đổi'; break;
                }
                addFilterTag(activeFiltersDiv, `Loại: ${postTypeLabel}`, () => {
                    document.getElementById('postTypeFilter').value = '';
                    filterPosts();
                });
            }
            const conditionFilter = document.getElementById('conditionFilter').value;
            if (conditionFilter) {
                let conditionLabel = '';
                switch(conditionFilter) {
                    case 'like_new': conditionLabel = 'Như mới'; break;
                    case 'good': conditionLabel = 'Tốt'; break;
                    case 'fair': conditionLabel = 'Khá'; break;
                    case 'poor': conditionLabel = 'Cần sửa chữa'; break;
                }
                addFilterTag(activeFiltersDiv, `Tình trạng: ${conditionLabel}`, () => {
                    document.getElementById('conditionFilter').value = '';
                    filterPosts();
                });
            }
            const minRatingFilter = document.getElementById('minRatingFilter').value;
            if (minRatingFilter) {
                addFilterTag(activeFiltersDiv, `Đánh giá từ: ${minRatingFilter} sao`, () => {
                    document.getElementById('minRatingFilter').value = '';
                    filterPosts();
                });
            }
        }
        
        function addFilterTag(container, text, removeAction) {
            const tagElement = document.createElement('span');
            tagElement.className = 'filter-tag';
            tagElement.innerHTML = `${text} <i class="fas fa-times"></i>`;
            tagElement.querySelector('i').addEventListener('click', removeAction);
            container.appendChild(tagElement);
        }
        
        function formatPrice(price) {
            return parseInt(price).toLocaleString('vi-VN');
        }
        
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('searchType').value = 'all';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('sortFilter').value = '';
            document.getElementById('postTypeFilter').value = '';
            document.getElementById('conditionFilter').value = '';
            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            document.getElementById('minRatingFilter').value = '';
            
            // Show all posts
            const posts = document.querySelectorAll('.post-card[data-post-id]');
            posts.forEach(post => post.style.display = 'block');
            
            // Hide no results message
            const noResultsElement = document.getElementById('noResults');
            if (noResultsElement) {
                noResultsElement.style.display = 'none';
            }
            
            // Update results
            updateFilterResults(posts.length, posts.length);
            
            // Clear active filters
            document.getElementById('activeFilters').innerHTML = '';
            
            // Update search input placeholder
            updateSearchPlaceholder();
            
            // Show notification            
            showNotification('Đã xóa tất cả bộ lọc', 'success');
        }
          // Real-time search as user types
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(filterPosts, 300); // Debounce search
                });
                
                // Initialize search placeholder
                updateSearchPlaceholder();
                
                // Add event listeners for the search type dropdown
                document.getElementById('searchType').addEventListener('change', updateSearchPlaceholder);
            }
        });</script>
    
    <!-- Share Modal Script -->
    <script src="js/share-modal.js"></script>
</body>
</html>