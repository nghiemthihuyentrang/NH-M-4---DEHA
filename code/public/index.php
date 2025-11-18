<?php
// Khởi tạo session để quản lý trạng thái đăng nhập của người dùng
session_start();

// Kiểm tra trạng thái đăng nhập của người dùng từ session
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'];
// Lấy tên người dùng từ session nếu đã đăng nhập
$user_name = $is_logged_in ? $_SESSION['full_name'] : '';
// Kiểm tra xem người dùng có phải admin hay không
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tiêu đề trang web hiển thị trên tab browser -->
    <title>ElectroReview - Nền tảng Review & Trao đổi Điện tử Cũ</title>
    <!-- Link CDN cho Font Awesome icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Link file CSS chính của website -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header - Phần đầu trang chứa logo, menu điều hướng và nút đăng nhập/đăng ký -->
    <header class="header">
        <div class="nav-container">
            <!-- Logo của website -->
            <div class="logo">
                <i class="fas fa-laptop"></i>
                ElectroReview
            </div>
            
            <!-- Menu điều hướng chính -->
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="#about">Giới thiệu</a></li>                    
                    <li><a href="mua-ban.php">Mua bán, trao đổi</a></li>
                    <li><a href="thao-luan.php">Thảo luận</a></li>
                    <li><a href="lien-he.php">Liên hệ</a></li>
                </ul>
            </nav>
            
            <!-- Phần nút đăng nhập/đăng ký hoặc thông tin người dùng -->
            <div class="auth-buttons">
                <?php if ($is_logged_in): ?>
                    <!-- Hiển thị khi người dùng đã đăng nhập -->
                    <span class="user-welcome">Xin chào, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                    
                    <?php if ($is_admin): ?>
                        <!-- Nút Admin Panel chỉ hiển thị cho admin -->
                        <a href="admin.php" class="btn btn-warning">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    <?php endif; ?>
                    
                    <!-- Nút đăng xuất -->
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                <?php else: ?>
                    <!-- Hiển thị khi người dùng chưa đăng nhập -->
                    <button class="btn btn-outline" onclick="openLoginModal()">Đăng nhập</button>
                    <button class="btn btn-primary" onclick="openRegisterModal()">Đăng ký</button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section - Phần banner chính của trang chủ -->
    <section class="hero" id="home">
        <div class="hero-container">
            <!-- Tiêu đề chính của website -->
            <h1>Nền tảng Review & Trao đổi Điện tử Cũ</h1>
            <!-- Mô tả ngắn về website -->
            <p>Chia sẻ kinh nghiệm, đánh giá và thảo luận về các sản phẩm điện tử cũ<br>
            Tìm kiếm địa điểm có uy tín</p>
            
            <!-- Các nút hành động trong hero section -->
            <div class="hero-buttons">
                <?php if ($is_logged_in): ?>
                    <!-- Nút cho người dùng đã đăng nhập -->
                    <a href="mua-ban.php" class="btn btn-primary btn-hero">Đăng bài ngay</a>
                    <a href="thao-luan.php" class="btn btn-outline btn-hero">Tham gia thảo luận</a>
                <?php else: ?>
                    <!-- Nút cho người dùng chưa đăng nhập -->
                    <button class="btn btn-primary btn-hero" onclick="openRegisterModal()">Tham gia ngay</button>
                    <a href="#about" class="btn btn-outline btn-hero">Khám phá thêm</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section - Phần giới thiệu về website -->
    <section class="about" id="about">
        <div class="container">
            <h2 class="section-title">Giới thiệu về ElectroReview</h2>
            
            <!-- Mô tả chi tiết về website -->
            <p class="about-text">
                ElectroReview là cộng đồng trực tuyến dành riêng cho việc trao đổi, đánh giá và thảo luận về các sản phẩm điện tử cũ. 
                Chúng tôi tạo ra một không gian tin cậy để cộng đồng có thể chia sẻ kinh nghiệm và tìm kiếm những sản phẩm công nghệ phù hợp.
            </p>

            <!-- Grid hiển thị các tính năng chính của website -->
            <div class="features">
                <!-- Tính năng 1: Review chất lượng -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Review chất lượng</h3>
                    <p>Chia sẻ trải nghiệm thực tế về sản phẩm điện tử cũ, từ tình trạng sản phẩm, hiệu suất cho đến hình ảnh chi tiết để cộng đồng tham khảo.</p>
                </div>

                <!-- Tính năng 2: Thảo luận cộng đồng -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Thảo luận cộng đồng</h3>
                    <p>Tham gia các cuộc thảo luận sôi nổi, chia sẻ kinh nghiệm mua bán, xử lý sự cố và tìm hiểu về công nghệ mới.</p>
                </div>

                <!-- Tính năng 3: Tìm kiếm dịch vụ -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Tìm kiếm dịch vụ</h3>
                    <p>Khám phá thông tin các địa điểm bán laptop, điện thoại, gaming gear và thiết bị điện tử uy tín gần bạn.</p>
                </div>

                <!-- Tính năng 4: Mua bán an toàn -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Mua bán an toàn</h3>
                    <p>Hệ thống hỗ trợ giao dịch minh bạch với thông tin uy tín, đảm bảo quyền lợi cho người mua và người bán.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section - Phần hiển thị thống kê của website -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <!-- Thống kê số lượng dịch vụ được đánh giá -->
                <div class="stat-item">
                    <h3>1,250+</h3>
                    <p>Dịch vụ được đánh giá</p>
                </div>
                <!-- Thống kê số lượng thành viên -->
                <div class="stat-item">
                    <h3>5,600+</h3>
                    <p>Thành viên cộng đồng</p>
                </div>
                <!-- Thống kê số lượng bài review -->
                <div class="stat-item">
                    <h3>8,900+</h3>
                    <p>Bài review</p>
                </div>
                <!-- Thống kê số lượng thảo luận -->
                <div class="stat-item">
                    <h3>15,300+</h3>
                    <p>Thảo luận</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal - Popup đăng nhập -->
    <div id="loginModal" class="auth-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Đăng nhập</h2>
                <!-- Nút đóng modal -->
                <span class="close" onclick="closeLoginModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Form đăng nhập -->
                <form id="loginForm">
                    <!-- Trường nhập email hoặc username -->
                    <div class="form-group">
                        <label>Email hoặc tên đăng nhập</label>
                        <input type="text" name="login_input" class="form-control" required>
                    </div>
                    <!-- Trường nhập mật khẩu -->
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <!-- Checkbox ghi nhớ đăng nhập -->
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox"> Ghi nhớ đăng nhập
                        </label>
                    </div>
                    <!-- Nút submit form đăng nhập -->
                    <button type="submit" class="btn btn-primary btn-full">Đăng nhập</button>
                </form>
                <!-- Footer của modal với link chuyển sang đăng ký và quên mật khẩu -->
                <div class="auth-footer">
                    <p>Chưa có tài khoản? <a href="#" onclick="switchToRegister()">Đăng ký ngay</a></p>
                    <p><a href="#" onclick="forgotPassword()">Quên mật khẩu?</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal - Popup đăng ký -->
    <div id="registerModal" class="auth-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Đăng ký tài khoản</h2>
                <!-- Nút đóng modal -->
                <span class="close" onclick="closeRegisterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Form đăng ký -->
                <form id="registerForm">
                    <!-- Trường nhập họ tên -->
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <!-- Trường nhập email -->
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <!-- Trường nhập username -->
                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <!-- Trường nhập mật khẩu -->
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <!-- Trường xác nhận mật khẩu -->
                    <div class="form-group">
                        <label>Xác nhận mật khẩu</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <!-- Trường nhập số điện thoại (không bắt buộc) -->
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <!-- Checkbox đồng ý điều khoản -->
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="agree_terms" required> Tôi đồng ý với <a href="#">Điều khoản sử dụng</a>
                        </label>
                    </div>
                    <!-- Nút submit form đăng ký -->
                    <button type="submit" class="btn btn-primary btn-full">Đăng ký</button>
                </form>
                <!-- Footer của modal với link chuyển sang đăng nhập -->
                <div class="auth-footer">
                    <p>Đã có tài khoản? <a href="#" onclick="switchToLogin()">Đăng nhập ngay</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification - Phần hiển thị thông báo cho người dùng -->
    <div id="notification" class="notification"></div>

    <!-- Link file JavaScript chính -->
    <script src="js/script.js"></script>
    <script>
        // ===== CÁC FUNCTION QUẢN LÝ MODAL ĐĂNG NHẬP/ĐĂNG KÝ =====
        
        // Mở modal đăng nhập
        function openLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
        }

        // Đóng modal đăng nhập
        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }

        // Mở modal đăng ký
        function openRegisterModal() {
            document.getElementById('registerModal').style.display = 'block';
        }

        // Đóng modal đăng ký
        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
        }

        // Chuyển từ modal đăng nhập sang modal đăng ký
        function switchToRegister() {
            closeLoginModal();
            openRegisterModal();
        }

        // Chuyển từ modal đăng ký sang modal đăng nhập
        function switchToLogin() {
            closeRegisterModal();
            openLoginModal();
        }

        // Function xử lý khi người dùng click "Quên mật khẩu"
        function forgotPassword() {
            showNotification('Tính năng quên mật khẩu sẽ được phát triển sau!', 'warning');
        }

        // ===== FUNCTION HIỂN THỊ THÔNG BÁO =====
        
        // Hiển thị thông báo với loại và nội dung tùy chỉnh
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;
            
            // Tự động ẩn thông báo sau 3 giây
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // ===== EVENT LISTENER ĐỂ ĐÓNG MODAL KHI CLICK BÊN NGOÀI =====
        
        // Đóng modal khi người dùng click vào vùng tối bên ngoài modal
        window.onclick = function(event) {
            const loginModal = document.getElementById('loginModal');
            const registerModal = document.getElementById('registerModal');
            
            if (event.target === loginModal) {
                closeLoginModal();
            }
            if (event.target === registerModal) {
                closeRegisterModal();
            }
        }

        // ===== XỬ LÝ FORM ĐĂNG NHẬP =====
        
        // Event listener cho form đăng nhập
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Ngăn form submit theo cách thông thường
            e.preventDefault();
            
            // Lấy dữ liệu từ form
            const formData = new FormData(this);
            
            // Gửi request AJAX đến server để xử lý đăng nhập
            fetch('login_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Chuyển response thành JSON
            .then(data => {
                if (data.success) {
                    // Đăng nhập thành công
                    showNotification(data.message, 'success');
                    closeLoginModal();
                    
                    // Chuyển hướng sau 1 giây
                    setTimeout(() => {
                        if (data.is_admin) {
                            // Nếu là admin thì chuyển về admin panel
                            window.location.href = 'admin.php';
                        } else {
                            // Nếu là user thường thì reload trang
                            window.location.reload();
                        }
                    }, 1000);
                } else {
                    // Đăng nhập thất bại
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                // Xử lý lỗi kết nối hoặc lỗi khác
                showNotification('Có lỗi xảy ra khi đăng nhập', 'error');
                console.error('Error:', error);
            });
        });

        // ===== XỬ LÝ FORM ĐĂNG KÝ =====
        
        // Event listener cho form đăng ký
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            // Ngăn form submit theo cách thông thường
            e.preventDefault();
            
            // Lấy dữ liệu từ form
            const formData = new FormData(this);
            
            // Gửi request AJAX đến server để xử lý đăng ký
            fetch('register_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Chuyển response thành JSON
            .then(data => {
                if (data.success) {
                    // Đăng ký thành công
                    showNotification(data.message, 'success');
                    closeRegisterModal();
                    this.reset(); // Reset form về trạng thái ban đầu
                    
                    // Tự động mở modal đăng nhập sau 2 giây
                    setTimeout(() => {
                        openLoginModal();
                    }, 2000);
                } else {
                    // Đăng ký thất bại
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                // Xử lý lỗi kết nối hoặc lỗi khác
                showNotification('Có lỗi xảy ra khi đăng ký', 'error');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>