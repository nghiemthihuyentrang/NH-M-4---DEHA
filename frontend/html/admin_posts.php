<?php
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in'] || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit;
}

// Kết nối database
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);    // Xử lý các action
    if (isset($_POST['action'])) {
        $postId = $_POST['post_id'];
        
        switch ($_POST['action']) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE post_id = ?");
                $stmt->execute([$postId]);
                $message = "Đã duyệt bài đăng mua bán #$postId thành công!";
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'rejected' WHERE post_id = ?");
                $stmt->execute([$postId]);
                $message = "Đã từ chối bài đăng mua bán #$postId";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
                $stmt->execute([$postId]);
                $message = "Đã xóa bài đăng mua bán #$postId";
                break;
                
            case 'approve_all_pending':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE status = 'pending' AND post_type = 'listing'");
                $result = $stmt->execute();
                $count = $stmt->rowCount();
                $message = "Đã duyệt $count bài đăng mua bán đang chờ duyệt!";
                break;
        }
        
        header("Location: admin_posts.php?msg=" . urlencode($message));
        exit;
    }
      // Lấy danh sách bài đăng mua bán
    $filter = $_GET['filter'] ?? 'all';
    $sql = "
        SELECT p.*, u.full_name, u.email, c.category_name 
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.post_type = 'listing'
    ";
    
    if ($filter !== 'all') {
        $sql .= " AND p.status = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY p.created_at DESC");
        $stmt->execute([$filter]);
    } else {
        $stmt = $pdo->prepare($sql . " ORDER BY p.created_at DESC");
        $stmt->execute();
    }
    
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $posts = [];
    $error = "Lỗi kết nối database: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài đăng - Admin ElectroReview</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 12px;
        }

        .header h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        .header-nav {
            text-align: center;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .header-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
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

        .post-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .post-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .post-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 13px;
            color: #666;
        }

        .post-content {
            padding: 20px;
        }
        .post-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .post-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-sold {
            background: #d1ecf1;
            color: #0c5460;
        }

        .post-actions {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .price {
            font-size: 18px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 10px;
        }

        .price.exchange {
            color: #28a745;
        }

        .notification {
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
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                justify-content: center;
            }

            .posts-grid {
                grid-template-columns: 1fr;
            }

            .post-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-newspaper"></i> Quản lý bài đăng</h1>
            <div class="header-nav">
                <a href="admin.php"><i class="fas fa-arrow-left"></i> Quay lại Dashboard</a>
                <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="notification success show">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($posts); ?></div>
                <div class="stat-label">Tổng bài đăng</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($posts, fn($p) => $p['status'] === 'pending')); ?></div>
                <div class="stat-label">Chờ duyệt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($posts, fn($p) => $p['status'] === 'active')); ?></div>
                <div class="stat-label">Đã duyệt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($posts, fn($p) => $p['status'] === 'rejected')); ?></div>
                <div class="stat-label">Đã từ chối</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label>Lọc theo trạng thái:</label>
                <select class="filter-select" onchange="filterPosts(this.value)">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Đã duyệt</option>
                    <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Đã từ chối</option>
                </select>
            </div>            <div class="filter-group">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="approve_all_pending">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Duyệt tất cả bài đăng đang chờ duyệt?')">
                        <i class="fas fa-check-double"></i> Duyệt tất cả chờ
                    </button>
                </form>
            </div>
        </div>

        <!-- Posts Grid -->
        <div class="posts-grid">
            <?php if (empty($posts)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 12px;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Không có bài đăng nào</h3>
                    <p>Chưa có bài đăng nào trong hệ thống.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card" data-status="<?php echo $post['status']; ?>">
                        <div class="post-header">
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <div class="post-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['full_name'] ?? $post['email'] ?? 'Ẩn danh'); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($post['category_name'] ?? 'Khác'); ?></span>
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
                            </div>
                        </div>

                        <div class="post-content">
                            <?php if ($post['price']): ?>
                                <div class="price"><?php echo number_format($post['price'], 0, ',', '.'); ?> VNĐ</div>
                            <?php else: ?>
                                <div class="price exchange">Trao đổi</div>
                            <?php endif; ?>

                            <p class="post-description"><?php echo htmlspecialchars($post['content']); ?></p>                            <div class="post-details">
                                <div class="detail-item">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Bài đăng mua bán</span>
                                </div>
                                <?php if (!empty($post['condition_item'])): ?>
                                <div class="detail-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?php echo ucfirst($post['condition_item']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($post['location'])): ?>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($post['location']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <i class="fas fa-hashtag"></i>
                                    <span>ID: #<?php echo $post['post_id']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="post-actions">
                            <?php if ($post['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Duyệt bài đăng này?')">
                                        <i class="fas fa-check"></i> Duyệt
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Từ chối bài đăng này?')">
                                        <i class="fas fa-times"></i> Từ chối
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <button class="btn btn-info" onclick="viewPostDetail(<?php echo $post['post_id']; ?>)">
                                <i class="fas fa-eye"></i> Xem
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Xóa bài đăng này? Hành động này không thể hoàn tác!')">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>    <script>
        function filterPosts(status) {
            window.location.href = 'admin_posts.php?filter=' + status;
        }

        function viewPostDetail(postId) {
            // Tạo modal hoặc redirect đến trang xem chi tiết
            window.open('view_post.php?id=' + postId, '_blank', 'width=800,height=600');
        }

        // Auto-hide notification
        setTimeout(function() {
            const notification = document.querySelector('.notification.show');
            if (notification) {
                notification.classList.remove('show');
            }
        }, 5000);
    </script>
</body>
</html>