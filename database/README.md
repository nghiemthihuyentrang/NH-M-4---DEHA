# 🗄️ Database ElectroReview - Hướng dẫn cài đặt

## 📋 Cấu trúc Database

### 🔧 **Yêu cầu hệ thống:**
- XAMPP (Apache + MySQL + PHP)
- phpMyAdmin
- MySQL 5.7+ hoặc MariaDB 10.3+

---

## 🚀 **Cách cài đặt Database:**

### **Bước 1: Khởi động XAMPP**
1. Mở XAMPP Control Panel
2. Start **Apache** và **MySQL**
3. Đảm bảo cả 2 service đang chạy (màu xanh)

### **Bước 2: Truy cập phpMyAdmin**
1. Mở trình duyệt
2. Gõ: `http://localhost/phpmyadmin`
3. Click tab **SQL**

### **Bước 3: Import Database**
1. Copy toàn bộ nội dung file `electroreview_database.sql`
2. Paste vào ô SQL trong phpMyAdmin  
3. Click **Go** (Thực hiện)

### **Bước 4: Kiểm tra**
- Database `electroreview_db` đã được tạo
- 6 bảng đã được tạo với dữ liệu mẫu

---

## 📊 **Cấu trúc các bảng:**

### **1. 👥 users** - Quản lý tài khoản
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| user_id | INT | ID người dùng (PK) |
| username | VARCHAR(50) | Tên đăng nhập (unique) |
| email | VARCHAR(100) | Email (unique) |
| password | VARCHAR(255) | Mật khẩu đã mã hóa |
| full_name | VARCHAR(100) | Họ tên đầy đủ |
| phone | VARCHAR(15) | Số điện thoại |
| created_at | TIMESTAMP | Ngày tạo tài khoản |
| status | ENUM | Trạng thái: active/inactive/banned |

### **2. 📁 categories** - Danh mục sản phẩm
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| category_id | INT | ID danh mục (PK) |
| category_name | VARCHAR(100) | Tên danh mục |
| category_slug | VARCHAR(100) | Slug URL |
| description | TEXT | Mô tả danh mục |

### **3. 📝 posts** - Bài đăng mua bán/trao đổi
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| post_id | INT | ID bài đăng (PK) |
| user_id | INT | ID người đăng (FK) |
| category_id | INT | ID danh mục (FK) |
| title | VARCHAR(255) | Tiêu đề bài đăng |
| content | TEXT | Nội dung chi tiết |
| price | DECIMAL(15,0) | Giá (NULL cho trao đổi) |
| post_type | ENUM | Loại: sell/buy/exchange |
| condition_item | ENUM | Tình trạng: like_new/good/fair/poor |
| location | VARCHAR(100) | Địa điểm |
| images | TEXT | JSON array ảnh |
| status | ENUM | Trạng thái: active/sold/expired/hidden |
| views | INT | Lượt xem |

### **4. 💬 forum_topics** - Chủ đề thảo luận
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| topic_id | INT | ID chủ đề (PK) |
| user_id | INT | ID người tạo (FK) |
| title | VARCHAR(255) | Tiêu đề chủ đề |
| content | TEXT | Nội dung |
| tags | VARCHAR(255) | Tags (phân cách bằng dấu phẩy) |
| views | INT | Lượt xem |
| replies_count | INT | Số lượt trả lời |

### **5. 💭 forum_replies** - Trả lời thảo luận
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| reply_id | INT | ID trả lời (PK) |
| topic_id | INT | ID chủ đề (FK) |
| user_id | INT | ID người trả lời (FK) |
| content | TEXT | Nội dung trả lời |
| parent_reply_id | INT | ID reply cha (để trả lời reply) |

### **6. ✉️ contact_messages** - Tin nhắn liên hệ
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| message_id | INT | ID tin nhắn (PK) |
| name | VARCHAR(100) | Tên người gửi |
| email | VARCHAR(100) | Email người gửi |
| subject | VARCHAR(255) | Chủ đề |
| message | TEXT | Nội dung tin nhắn |
| status | ENUM | Trạng thái: new/read/replied |

---

## 🔐 **Tài khoản mẫu:**

| Username | Email | Password | Vai trò |
|----------|--------|----------|---------|
| admin | admin@electroreview.vn | 123456 | Quản trị viên |
| user1 | user1@gmail.com | 123456 | Người dùng |
| user2 | user2@gmail.com | 123456 | Người dùng |

---

## 📂 **Dữ liệu mẫu có sẵn:**

### **✅ Danh mục:**
- Laptop
- Điện thoại  
- Gaming Gear
- Phụ kiện
- Sửa chữa
- Tổng hợp

### **✅ Bài đăng mẫu:**
- Bán Laptop Dell Latitude E7450
- Cần mua iPhone 12 Pro Max
- Trao đổi Gaming Chair với Màn hình

### **✅ Chủ đề thảo luận:**
- Tư vấn mua Laptop Dell
- Chia sẻ kinh nghiệm mua iPhone

---

## 🔗 **Kết nối Database trong PHP:**

```php
<?php
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Kết nối database thành công!";
} catch(PDOException $e) {
    echo "Lỗi kết nối: " . $e->getMessage();
}
?>
```

---

## ⚠️ **Lưu ý:**
- Database này phù hợp cho **bài tập học tập**
- Mật khẩu đã được **mã hóa bằng bcrypt**
- Có **index** để tối ưu hiệu suất
- **Foreign key** đảm bảo tính toàn vẹn dữ liệu
- Sử dụng **charset utf8mb4** hỗ trợ tiếng Việt và emoji

---

## 🎯 **Tính năng hỗ trợ:**
- ✅ Đăng ký/đăng nhập tài khoản
- ✅ Đăng bài mua/bán/trao đổi
- ✅ Thảo luận trong forum
- ✅ Liên hệ qua form
- ✅ Quản lý danh mục sản phẩm
- ✅ Upload và quản lí hình ảnh