<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tiêu đề trang hiển thị trên tab browser -->
    <title>Liên hệ - ElectroReview</title>
    <!-- Link CDN cho Font Awesome icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Link file CSS chính của website -->
    <link href="css/style.css" rel="stylesheet">    <style>
        /* ===== CSS RIÊNG CHO TRANG LIÊN HỆ ===== */
        
        /* Container chính cho toàn bộ nội dung liên hệ */
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header của trang liên hệ với background gradient */
        .contact-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0 40px;
            text-align: center;
        }

        /* Grid layout cho form liên hệ và thông tin liên hệ */
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr; /* 2 cột bằng nhau */
            gap: 40px; /* Khoảng cách giữa 2 cột */
            margin-top: 40px;
        }

        /* Styling cho form liên hệ */
        .contact-form {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Styling cho phần thông tin liên hệ */
        .contact-info {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Styling cho từng nhóm input trong form */
        .form-group {
            margin-bottom: 20px;
        }

        /* Styling cho label của các input */
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        /* Styling cho tất cả input, select, textarea */
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        /* Hiệu ứng khi focus vào input */
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Styling riêng cho textarea */
        .form-textarea {
            min-height: 120px;
            resize: vertical; /* Chỉ cho phép resize theo chiều dọc */
        }

        /* Styling cho nút submit */
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

        /* Hiệu ứng hover cho nút submit */
        .btn-submit:hover {
            transform: translateY(-2px);
        }

        /* Container cho mỗi item thông tin liên hệ */
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        /* Icon tròn cho thông tin liên hệ */
        .info-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        /* Nội dung text cho thông tin liên hệ */
        .info-content h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-weight: 600;
        }

        .info-content p {
            margin: 0;
            color: #666;
        }

        /* Container cho các link mạng xã hội */
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        /* Styling cho từng link mạng xã hội */
        .social-link {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        /* Hiệu ứng hover cho social links */
        .social-link:hover {
            transform: translateY(-3px);
        }

        /* Màu riêng cho từng platform */
        .social-link.facebook { background: #3b5998; }
        .social-link.twitter { background: #1da1f2; }
        .social-link.youtube { background: #ff0000; }
        .social-link.instagram { background: #e4405f; }

        /* Responsive design cho mobile */
        @media (max-width: 768px) {
            .contact-content {
                grid-template-columns: 1fr; /* Chuyển về 1 cột trên mobile */
                gap: 20px;
            }
            
            .contact-form, .contact-info {
                padding: 20px; /* Giảm padding trên mobile */
            }
        }
    </style>
</head>
<body>    <!-- Header của trang với navigation menu -->
    <header class="header">
        <div class="nav-container">
            <!-- Logo của website với icon laptop -->
            <div class="logo">
                <i class="fas fa-laptop"></i>
                ElectroReview
            </div>
            <!-- Menu điều hướng chính -->
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="index.php#about">Giới thiệu</a></li>
                    <li><a href="mua-ban.php">Mua bán, trao đổi</a></li>
                    <li><a href="thao-luan.php">Thảo luận</a></li>
                    <li><a href="lien-he.php" class="active">Liên hệ</a></li>
                </ul>
            </nav>
            <!-- Các nút đăng nhập và đăng ký -->
            <div class="auth-buttons">
                <button class="btn btn-outline" onclick="openLoginModal()">Đăng nhập</button>
                <button class="btn btn-primary" onclick="openRegisterModal()">Đăng ký</button>
            </div>
        </div>
    </header>

    <!-- Header section của trang liên hệ -->
    <section class="contact-header">
        <div class="contact-container">
            <h1><i class="fas fa-envelope"></i> Liên hệ với chúng tôi</h1>
            <p>Chúng tôi luôn sẵn sàng hỗ trợ và lắng nghe ý kiến của bạn</p>
        </div>
    </section>

    <!-- Nội dung chính của trang liên hệ -->
    <div class="contact-container">
        <div class="contact-content">
            <!-- Form liên hệ - bên trái -->
            <div class="contact-form">
                <h2>Gửi tin nhắn cho chúng tôi</h2>
                <form id="contactForm">
                    <!-- Trường nhập họ tên (bắt buộc) -->
                    <div class="form-group">
                        <label for="name">Họ và tên *</label>
                        <input type="text" id="name" class="form-control" placeholder="Nhập họ và tên của bạn" required>
                    </div>

                    <!-- Trường nhập email (bắt buộc) -->
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" class="form-control" placeholder="your.email@example.com" required>
                    </div>

                    <!-- Trường nhập số điện thoại (không bắt buộc) -->
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" class="form-control" placeholder="0901234567">
                    </div>

                    <!-- Dropdown chọn chủ đề (bắt buộc) -->
                    <div class="form-group">
                        <label for="subject">Chủ đề *</label>
                        <select id="subject" class="form-control" required>
                            <option value="">Chọn chủ đề</option>
                            <option value="support">Hỗ trợ kỹ thuật</option>
                            <option value="partnership">Hợp tác kinh doanh</option>
                            <option value="feedback">Góp ý / Phản hồi</option>
                            <option value="report">Báo cáo vấn đề</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>

                    <!-- Textarea nhập nội dung tin nhắn (bắt buộc) -->
                    <div class="form-group">
                        <label for="message">Tin nhắn *</label>
                        <textarea id="message" class="form-control form-textarea" placeholder="Nhập nội dung tin nhắn của bạn..." required></textarea>
                    </div>

                    <!-- Nút submit gửi tin nhắn -->
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                    </button>
                </form>
            </div>

            <!-- Thông tin liên hệ - bên phải -->
            <div class="contact-info">
                <h2>Thông tin liên hệ</h2>
                
                <!-- Thông tin địa chỉ -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h4>Địa chỉ</h4>
                        <p>123 Đường ABC, Quận 1<br>TP. Hồ Chí Minh, Việt Nam</p>
                    </div>
                </div>

                <!-- Thông tin điện thoại -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h4>Điện thoại</h4>
                        <p>Hotline: (028) 1234-5678<br>Mobile: 0901-234-567</p>
                    </div>
                </div>

                <!-- Thông tin email -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h4>Email</h4>
                        <p>info@electroreview.vn<br>support@electroreview.vn</p>
                    </div>
                </div>

                <!-- Thông tin giờ làm việc -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h4>Giờ làm việc</h4>
                        <p>Thứ 2 - Thứ 6: 8:00 - 18:00<br>Thứ 7: 8:00 - 12:00</p>
                    </div>
                </div>

                <!-- Phần mạng xã hội -->
                <h3 style="margin-top: 40px; margin-bottom: 20px;">Kết nối với chúng tôi</h3>
                <div class="social-links">
                    <a href="#" class="social-link facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link youtube">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="#" class="social-link instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>    <!-- Modal đăng nhập -->
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
                        <input type="text" class="form-control" required>
                    </div>
                    <!-- Trường nhập mật khẩu -->
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <!-- Checkbox ghi nhớ đăng nhập -->
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox"> Ghi nhớ đăng nhập
                        </label>
                    </div>
                    <!-- Nút submit đăng nhập -->
                    <button type="submit" class="btn btn-primary btn-full">Đăng nhập</button>
                </form>
                <!-- Footer với link chuyển sang đăng ký và quên mật khẩu -->
                <div class="auth-footer">
                    <p>Chưa có tài khoản? <a href="#" onclick="switchToRegister()">Đăng ký ngay</a></p>
                    <p><a href="#" onclick="forgotPassword()">Quên mật khẩu?</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal đăng ký -->
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
                        <input type="text" class="form-control" required>
                    </div>
                    <!-- Trường nhập email -->
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" required>
                    </div>
                    <!-- Trường nhập username -->
                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <!-- Trường nhập mật khẩu -->
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <!-- Trường xác nhận mật khẩu -->
                    <div class="form-group">
                        <label>Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <!-- Trường nhập số điện thoại (không bắt buộc) -->
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" class="form-control">
                    </div>
                    <!-- Checkbox đồng ý điều khoản -->
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" required> Tôi đồng ý với <a href="#">Điều khoản sử dụng</a>
                        </label>
                    </div>
                    <!-- Nút submit đăng ký -->
                    <button type="submit" class="btn btn-primary btn-full">Đăng ký</button>
                </form>
                <!-- Footer với link chuyển sang đăng nhập -->
                <div class="auth-footer">
                    <p>Đã có tài khoản? <a href="#" onclick="switchToLogin()">Đăng nhập ngay</a></p>
                </div>
            </div>
        </div>
    </div>    <!-- Link file JavaScript chính -->
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
            alert('Chức năng quên mật khẩu sẽ được triển khai sớm!');
        }

        // ===== EVENT LISTENER ĐỂ ĐÓNG MODAL KHI CLICK BÊN NGOÀI =====
        
        // Đóng modal khi người dùng click vào vùng tối bên ngoài modal
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

        // ===== XỬ LÝ FORM LIÊN HỆ =====
        
        // Event listener cho form liên hệ
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            // Ngăn form submit theo cách thông thường
            e.preventDefault();
            
            // Lấy dữ liệu từ các trường input
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value.trim();

            // ===== VALIDATION CÁC TRƯỜNG FORM =====
            
            // Kiểm tra họ tên có được nhập không
            if (!name) {
                showNotification('Vui lòng nhập họ và tên!', 'error');
                return;
            }
            
            // Kiểm tra email có được nhập không
            if (!email) {
                showNotification('Vui lòng nhập email!', 'error');
                return;
            }
            
            // Kiểm tra chủ đề có được chọn không
            if (!subject) {
                showNotification('Vui lòng chọn chủ đề!', 'error');
                return;
            }
            
            // Kiểm tra tin nhắn có được nhập không
            if (!message) {
                showNotification('Vui lòng nhập nội dung tin nhắn!', 'error');
                return;
            }
            
            // Kiểm tra độ dài tối thiểu của họ tên
            if (name.length < 2) {
                showNotification('Họ tên phải có ít nhất 2 ký tự!', 'error');
                return;
            }
            
            // Kiểm tra độ dài tối thiểu của tin nhắn
            if (message.length < 10) {
                showNotification('Tin nhắn phải có ít nhất 10 ký tự!', 'error');
                return;
            }

            // ===== CHUẨN BỊ DỮ LIỆU GỬI ĐI =====
            
            // Tạo FormData object để gửi dữ liệu
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('subject', subject);
            formData.append('message', message);

            // ===== HIỂN THỊ TRẠNG THÁI LOADING =====
            
            // Lấy nút submit và lưu text gốc
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            // Thay đổi text và disable nút
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            submitBtn.disabled = true;

            // ===== GỬI DỮ LIỆU VỀ SERVER =====
            
            // Gửi request AJAX đến server
            fetch('contact_submit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Chuyển response thành JSON
            .then(data => {
                if (data.success) {
                    // Gửi thành công
                    showNotification('Cảm ơn bạn đã gửi tin nhắn! Chúng tôi sẽ phản hồi sớm nhất có thể.', 'success');
                    this.reset(); // Reset form về trạng thái ban đầu
                } else {
                    // Gửi thất bại
                    showNotification('Lỗi: ' + data.message, 'error');
                }
            })
            .catch(error => {
                // Xử lý lỗi kết nối hoặc lỗi khác
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra khi gửi tin nhắn!', 'error');
            })
            .finally(() => {
                // Khôi phục trạng thái ban đầu của nút submit
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // ===== FUNCTION HIỂN THỊ THÔNG BÁO =====
        
        // Function hiển thị thông báo với loại và nội dung tùy chỉnh
        function showNotification(message, type = 'success') {
            // Tạo element notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            // Styling cho notification
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
            
            // Thiết lập màu sắc theo loại thông báo
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            } else if (type === 'error') {
                notification.style.background = 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)';
            } else if (type === 'warning') {
                notification.style.background = 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)';
                notification.style.color = '#333';
            }
            
            // Thiết lập nội dung và thêm vào DOM
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Animation slide in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Tự động ẩn sau 4 giây
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                // Xóa khỏi DOM sau khi animation hoàn thành
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }
    </script>
</body>
</html>