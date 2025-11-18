# ElectroReview - Nền tảng Review & Trao đổi Điện tử Cũ

## Tổng Quan
ElectroReview là hệ thống quản lý cộng đồng mua bán và trao đổi thiết bị điện tử cũ toàn diện được thiết kế cho người dùng Việt Nam. Hệ thống cho phép người dùng đăng bài mua bán, tham gia thảo luận, đánh giá sản phẩm và kết nối với cộng đồng yêu thích công nghệ.

## Yêu Cầu Hệ Thống

### Yêu Cầu Kỹ Thuật
- PHP 7.4 hoặc cao hơn
- MySQL 5.7 hoặc cao hơn
- Máy chủ web (Apache/Nginx)
- PDO PHP Extension
- Trình duyệt web hiện đại có hỗ trợ JavaScript ES6+
- Font Awesome 6.0.0

### Cấu Hình Cơ Sở Dữ Liệu
- **Host**: localhost
- **Tên Database**: electroreview_db
- **Tên người dùng**: root
- **Mật khẩu**: (trống - có thể được cấu hình trong các file PHP)

## Vai Trò Người Dùng và Quyền Truy Cập

### Quản Trị Viên (Admin)
- Quản lý người dùng (thêm, sửa, xóa, khóa tài khoản)
- Quản lý bài đăng mua bán (duyệt, từ chối, xóa)
- Quản lý bài thảo luận (duyệt, từ chối, xóa)
- Quản lý danh mục sản phẩm
- Quản lý tin nhắn liên hệ từ người dùng
- Xem bảng điều khiển với thống kê hệ thống
- Xử lý báo cáo và phản hồi

### Người Dùng (User)
- Đăng ký và đăng nhập tài khoản
- Đăng bài mua bán/trao đổi thiết bị điện tử
- Tạo và tham gia thảo luận
- Đánh giá và bình luận sản phẩm
- Tìm kiếm và lọc bài đăng theo nhiều tiêu chí
- Quản lý bài đăng cá nhân
- Gửi tin nhắn liên hệ với admin

### Khách (Guest)
- Xem danh sách bài đăng mua bán
- Xem danh sách bài thảo luận
- Tìm kiếm sản phẩm
- Đăng ký tài khoản mới

## Use Cases (Trường Hợp Sử Dụng)

### Use Cases Xác Thực

#### 1. Đăng Nhập
**Tác nhân**: Quản trị viên, Người dùng  
**Mô tả**: Người dùng đăng nhập vào hệ thống bằng email/username và mật khẩu

**Luồng chính**:
1. Người dùng truy cập trang chủ
2. Click vào nút "Đăng nhập"
3. Điền email/username và mật khẩu
4. Hệ thống xác thực thông tin
5. Chuyển hướng đến trang chính (admin.php cho admin, index.php cho user)

**Luồng thay thế**:
- Nếu thông tin sai: Hiển thị thông báo lỗi "Email/tên đăng nhập hoặc mật khẩu không đúng"
- Nếu tài khoản bị khóa: Hiển thị "Tài khoản của bạn đã bị khóa"

#### 2. Đăng Ký
**Tác nhân**: Khách  
**Mô tả**: Khách tạo tài khoản mới trong hệ thống

**Luồng chính**:
1. Khách truy cập trang chủ
2. Click vào nút "Đăng ký"
3. Điền thông tin: Họ tên, Email, Username, Mật khẩu, Số điện thoại
4. Đồng ý điều khoản sử dụng
5. Hệ thống xác thực và lưu thông tin
6. Chuyển hướng đến trang đăng nhập

**Luồng thay thế**:
- Nếu email/username đã tồn tại: Hiển thị thông báo lỗi
- Nếu mật khẩu không khớp: Hiển thị "Mật khẩu xác nhận không khớp"
- Nếu mật khẩu quá ngắn: Hiển thị "Mật khẩu phải có ít nhất 6 ký tự"

#### 3. Đăng Xuất
**Tác nhân**: Người dùng đã đăng nhập  
**Mô tả**: Người dùng đăng xuất khỏi hệ thống

**Luồng chính**:
1. Người dùng nhấn nút "Đăng xuất"
2. Hệ thống xóa phiên đăng nhập
3. Chuyển hướng đến trang chủ

### Use Cases Quản Trị Viên

#### 1. Quản Lý Người Dùng
**Tác nhân**: Quản trị viên  
**Mô tả**: Quản lý thông tin người dùng trong hệ thống

**Luồng chính**:
1. Admin truy cập trang "Quản lý người dùng"
2. Xem danh sách người dùng với thông tin: ID, Họ tên, Email, Số bài đăng, Ngày tham gia, Trạng thái
3. Có thể lọc theo trạng thái (Hoạt động/Không hoạt động/Admin)
4. Thực hiện các thao tác:
   - Xem chi tiết người dùng
   - Chỉnh sửa thông tin
   - Khóa/Mở khóa tài khoản
   - Xóa tài khoản (không thể xóa admin)

#### 2. Quản Lý Bài Đăng Mua Bán
**Tác nhân**: Quản trị viên  
**Mô tả**: Duyệt và quản lý bài đăng mua bán

**Luồng chính**:
1. Admin truy cập trang "Quản lý bài đăng"
2. Xem danh sách bài đăng với thông tin: ID, Tiêu đề, Người đăng, Loại, Giá, Trạng thái, Ngày đăng
3. Lọc theo: Trạng thái (Chờ duyệt/Đã duyệt/Đã bán/Từ chối)
4. Thực hiện các thao tác:
   - Duyệt bài đăng (chuyển từ "Chờ duyệt" sang "Đã duyệt")
   - Từ chối bài đăng
   - Xem chi tiết bài đăng
   - Xóa bài đăng
   - Duyệt hàng loạt các bài đăng đã chọn

#### 3. Quản Lý Bài Thảo Luận
**Tác nhân**: Quản trị viên  
**Mô tả**: Duyệt và quản lý bài thảo luận trong forum

**Luồng chính**:
1. Admin truy cập trang "Quản lý thảo luận"
2. Xem danh sách bài thảo luận với: ID, Tiêu đề, Người đăng, Danh mục, Số bình luận, Trạng thái
3. Lọc theo trạng thái (Chờ duyệt/Đã duyệt/Từ chối)
4. Thực hiện các thao tác:
   - Duyệt bài thảo luận
   - Từ chối bài thảo luận
   - Xem chi tiết và bình luận
   - Xóa bài thảo luận
   - Ghim bài thảo luận quan trọng

#### 4. Quản Lý Tin Nhắn Liên Hệ
**Tác nhân**: Quản trị viên  
**Mô tả**: Xem và trả lời tin nhắn liên hệ từ người dùng

**Luồng chính**:
1. Admin truy cập trang "Tin nhắn liên hệ"
2. Xem danh sách tin nhắn với: ID, Tên, Email, Chủ đề, Trạng thái, Ngày gửi
3. Lọc theo trạng thái (Chưa đọc/Đã đọc/Đã trả lời)
4. Thực hiện các thao tác:
   - Đánh dấu đã đọc
   - Xem chi tiết tin nhắn
   - Trả lời tin nhắn qua email
   - Xóa tin nhắn
   - Đánh dấu nhiều tin nhắn cùng lúc

#### 5. Quản Lý Danh Mục
**Tác nhân**: Quản trị viên  
**Mô tả**: Quản lý các danh mục sản phẩm

**Luồng chính**:
1. Admin truy cập trang "Quản lý danh mục"
2. Xem danh sách danh mục: ID, Tên, Mô tả, Số bài đăng
3. Thực hiện các thao tác:
   - Thêm danh mục mới
   - Chỉnh sửa danh mục
   - Xóa danh mục (nếu không có bài đăng)

#### 6. Xem Thống Kê Hệ Thống
**Tác nhân**: Quản trị viên  
**Mô tả**: Xem các thống kê về hoạt động hệ thống

**Luồng chính**:
1. Admin truy cập Dashboard
2. Xem các thống kê:
   - Tổng số bài đăng mua bán
   - Tổng số bài thảo luận
   - Tổng số người dùng
   - Số bài đăng chờ duyệt
   - Số tin nhắn chưa đọc
3. Xem biểu đồ hoạt động theo thời gian
4. Xem bài đăng gần đây

### Use Cases Người Dùng

#### 1. Đăng Bài Mua Bán
**Tác nhân**: Người dùng đã đăng nhập  
**Mô tả**: Người dùng đăng bài bán/mua/trao đổi thiết bị điện tử

**Luồng chính**:
1. Người dùng truy cập trang "Mua bán, trao đổi"
2. Click vào tab "Đăng bài"
3. Điền thông tin:
   - Tiêu đề bài đăng
   - Danh mục (Laptop/Điện thoại/Gaming Gear/Phụ kiện)
   - Loại bài đăng (Bán/Mua/Trao đổi)
   - Giá (VNĐ) - có thể để trống nếu trao đổi
   - Tình trạng (Như mới/Tốt/Khá/Cần sửa chữa)
   - Địa điểm
   - Mô tả chi tiết
   - Upload hình ảnh (tối đa 5 ảnh, mỗi ảnh < 2MB)
4. Click "Đăng bài"
5. Hệ thống lưu bài đăng với trạng thái "Chờ duyệt"
6. Hiển thị thông báo "Bài đăng đã được tạo và đang chờ admin duyệt"

**Luồng thay thế**:
- Nếu chưa đăng nhập: Chuyển đến trang đăng nhập
- Nếu thiếu thông tin bắt buộc: Hiển thị thông báo lỗi
- Nếu hình ảnh quá lớn: Hiển thị "Mỗi hình không được quá 2MB"

#### 2. Tìm Kiếm và Lọc Bài Đăng
**Tác nhân**: Người dùng, Khách  
**Mô tả**: Tìm kiếm bài đăng mua bán theo nhiều tiêu chí

**Luồng chính**:
1. Người dùng truy cập trang "Mua bán, trao đổi"
2. Sử dụng bộ lọc tìm kiếm:
   - Từ khóa (tìm trong tiêu đề, mô tả, tên sản phẩm)
   - Loại tìm kiếm (Tất cả/Người đăng/Chủ đề/Sản phẩm)
   - Danh mục
   - Khoảng giá (Từ - Đến)
   - Loại bài đăng (Bán/Mua/Trao đổi)
   - Tình trạng sản phẩm
   - Đánh giá tối thiểu (3 sao trở lên/4 sao trở lên)
   - Sắp xếp (Mới nhất/Giá thấp-cao/Giá cao-thấp/Tên A-Z/Z-A/Đánh giá cao nhất)
3. Click "Tìm kiếm"
4. Hệ thống hiển thị kết quả phù hợp
5. Hiển thị số lượng kết quả tìm được

**Tính năng bổ sung**:
- Hiển thị các bộ lọc đang áp dụng dưới dạng tag
- Có thể xóa từng bộ lọc riêng lẻ
- Nút "Xóa lọc" để reset tất cả

#### 3. Xem Chi Tiết Bài Đăng
**Tác nhân**: Người dùng, Khách  
**Mô tả**: Xem thông tin chi tiết về bài đăng

**Luồng chính**:
1. Người dùng click vào một bài đăng
2. Hệ thống hiển thị:
   - Hình ảnh sản phẩm (slideshow nếu có nhiều ảnh)
   - Tiêu đề và giá
   - Thông tin người đăng
   - Mô tả chi tiết
   - Tình trạng sản phẩm
   - Địa điểm
   - Danh mục
   - Đánh giá trung bình và số lượt đánh giá
   - Ngày đăng
3. Các nút thao tác:
   - Yêu thích
   - Chia sẻ
   - Liên hệ (hiển thị số điện thoại/email)
4. Phần bình luận ở cuối trang

#### 4. Đánh Giá Bài Đăng
**Tác nhân**: Người dùng đã đăng nhập  
**Mô tả**: Người dùng đánh giá bài đăng từ 1-5 sao

**Luồng chính**:
1. Người dùng xem chi tiết bài đăng
2. Trong phần "Đánh giá của bạn", click vào số sao muốn đánh giá (1-5 sao)
3. Hệ thống gửi đánh giá đến server
4. Cập nhật đánh giá trung bình và số lượt đánh giá
5. Hiển thị thông báo "Đánh giá thành công!"

**Luồng thay thế**:
- Nếu chưa đăng nhập: Yêu cầu đăng nhập
- Nếu đã đánh giá trước đó: Cập nhật đánh giá mới

#### 5. Tạo Bài Thảo Luận
**Tác nhân**: Người dùng đã đăng nhập  
**Mô tả**: Người dùng tạo chủ đề thảo luận mới

**Luồng chính**:
1. Người dùng truy cập trang "Thảo luận"
2. Click "Tạo chủ đề mới"
3. Điền thông tin:
   - Danh mục
   - Tiêu đề chủ đề (tối thiểu 10 ký tự)
   - Tags (phân cách bằng dấu phẩy)
   - Nội dung (tối thiểu 20 ký tự)
4. Click "Đăng chủ đề"
5. Hệ thống lưu với trạng thái "active" (hiển thị ngay)
6. Hiển thị thông báo "Bài thảo luận đã được đăng thành công!"

**Luồng thay thế**:
- Nếu chưa đăng nhập: Hiển thị thông báo và chuyển đến trang đăng nhập
- Nếu nội dung quá ngắn: Hiển thị yêu cầu nhập đủ ký tự

#### 6. Tham Gia Thảo Luận
**Tác nhân**: Người dùng đã đăng nhập  
**Mô tả**: Người dùng bình luận vào bài thảo luận

**Luồng chính**:
1. Người dùng xem chi tiết bài thảo luận
2. Cuộn xuống phần "Trả lời chủ đề"
3. Nhập nội dung bình luận (tối thiểu 5 ký tự)
4. Click "Gửi trả lời"
5. Hệ thống lưu bình luận
6. Hiển thị bình luận mới trong danh sách
7. Cập nhật số lượng bình luận

**Luồng thay thế**:
- Nếu chưa đăng nhập: Yêu cầu đăng nhập
- Nếu nội dung quá ngắn: Hiển thị "Bình luận phải có ít nhất 5 ký tự"

#### 7. Quản Lý Bài Đăng Cá Nhân
**Tác nhân**: Người dùng đã đăng nhập  
**Mô tả**: Người dùng quản lý các bài đăng của mình

**Luồng chính**:
1. Người dùng truy cập tab "Bài của tôi"
2. Xem danh sách bài đăng cá nhân với thông tin:
   - Hình ảnh
   - Tiêu đề
   - Giá
   - Trạng thái
   - Lượt xem, yêu thích
3. Thực hiện các thao tác:
   - Chỉnh sửa bài đăng
   - Ẩn/Hiện bài đăng
   - Đánh dấu "Đã bán"
   - Xóa bài đăng

#### 8. Gửi Tin Nhắn Liên Hệ
**Tác nhân**: Người dùng, Khách  
**Mô tả**: Gửi tin nhắn liên hệ cho admin

**Luồng chính**:
1. Người dùng truy cập trang "Liên hệ"
2. Điền thông tin:
   - Họ và tên
   - Email
   - Số điện thoại (tùy chọn)
   - Chủ đề (Hỗ trợ kỹ thuật/Hợp tác kinh doanh/Góp ý/Báo cáo vấn đề/Khác)
   - Tin nhắn (tối thiểu 10 ký tự)
3. Click "Gửi tin nhắn"
4. Hệ thống lưu tin nhắn với trạng thái "unread"
5. Hiển thị "Cảm ơn bạn đã gửi tin nhắn! Chúng tôi sẽ phản hồi sớm nhất có thể."

**Luồng thay thế**:
- Nếu thiếu thông tin: Hiển thị thông báo lỗi
- Nếu spam (gửi quá 3 tin nhắn trong 1 giờ): Hiển thị "Bạn đã gửi quá nhiều tin nhắn"

## Mô Hình Dữ Liệu

### Bảng: users
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| user_id | INT (PK, AI) | ID người dùng |
| username | VARCHAR(50) | Tên đăng nhập (unique) |
| email | VARCHAR(100) | Email (unique) |
| password | VARCHAR(255) | Mật khẩu đã mã hóa |
| full_name | VARCHAR(100) | Họ tên đầy đủ |
| phone | VARCHAR(15) | Số điện thoại |
| avatar | VARCHAR(255) | Đường dẫn ảnh đại diện |
| created_at | TIMESTAMP | Ngày tạo tài khoản |
| updated_at | TIMESTAMP | Ngày cập nhật |
| status | ENUM | Trạng thái: active/inactive/banned |
| is_admin | TINYINT(1) | Có phải admin không |

### Bảng: categories
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| category_id | INT (PK, AI) | ID danh mục |
| category_name | VARCHAR(100) | Tên danh mục |
| category_slug | VARCHAR(100) | URL slug (unique) |
| description | TEXT | Mô tả danh mục |
| created_at | TIMESTAMP | Ngày tạo |

### Bảng: posts
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| post_id | INT (PK, AI) | ID bài đăng |
| user_id | INT (FK) | ID người đăng |
| category_id | INT (FK) | ID danh mục |
| title | VARCHAR(255) | Tiêu đề |
| content | TEXT | Nội dung |
| tags | TEXT | Tags |
| price | DECIMAL(15,0) | Giá (VNĐ) |
| post_type | ENUM | Loại: sell/buy/exchange |
| condition_item | ENUM | Tình trạng: like_new/good/fair/poor |
| location | VARCHAR(100) | Địa điểm |
| contact_phone | VARCHAR(15) | Số điện thoại liên hệ |
| contact_email | VARCHAR(100) | Email liên hệ |
| images | TEXT | JSON array đường dẫn ảnh |
| status | ENUM | Trạng thái: active/sold/expired/hidden |
| views | INT | Lượt xem |
| created_at | TIMESTAMP | Ngày đăng |
| updated_at | TIMESTAMP | Ngày cập nhật |

### Bảng: forum_topics
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| topic_id | INT (PK, AI) | ID chủ đề |
| user_id | INT (FK) | ID người tạo |
| category_id | INT (FK) | ID danh mục |
| title | VARCHAR(255) | Tiêu đề |
| content | TEXT | Nội dung |
| tags | VARCHAR(255) | Tags |
| views | INT | Lượt xem |
| replies_count | INT | Số bình luận |
| last_reply_at | TIMESTAMP | Thời gian bình luận cuối |
| status | ENUM | Trạng thái: active/closed/pinned |
| created_at | TIMESTAMP | Ngày tạo |
| updated_at | TIMESTAMP | Ngày cập nhật |

### Bảng: topic_comments
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| comment_id | INT (PK, AI) | ID bình luận |
| topic_id | INT (FK) | ID chủ đề |
| user_id | INT (FK) | ID người bình luận |
| content | TEXT | Nội dung |
| created_at | DATETIME | Ngày tạo |
| updated_at | DATETIME | Ngày cập nhật |

### Bảng: post_ratings
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| rating_id | INT (PK, AI) | ID đánh giá |
| post_id | INT (FK) | ID bài đăng |
| user_id | INT (FK) | ID người đánh giá |
| rating | INT(1) | Điểm (1-5) |
| created_at | TIMESTAMP | Ngày đánh giá |

**Ràng buộc**: UNIQUE(user_id, post_id) - Mỗi user chỉ đánh giá 1 lần/bài

### Bảng: post_comments
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| comment_id | INT (PK, AI) | ID bình luận |
| post_id | INT (FK) | ID bài đăng |
| user_id | INT (FK) | ID người bình luận |
| parent_comment_id | INT (FK) | ID bình luận cha (cho reply) |
| content | TEXT | Nội dung |
| status | ENUM | Trạng thái: active/pending/hidden |
| created_at | TIMESTAMP | Ngày tạo |
| updated_at | TIMESTAMP | Ngày cập nhật |

### Bảng: post_likes
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| like_id | INT (PK, AI) | ID like |
| post_id | INT (FK) | ID bài đăng |
| user_id | INT (FK) | ID người like |
| created_at | TIMESTAMP | Ngày like |

**Ràng buộc**: UNIQUE(user_id, post_id)

### Bảng: contact_messages
| Cột | Kiểu dữ liệu | Mô tả |
|-----|-------------|-------|
| message_id | INT (PK, AI) | ID tin nhắn |
| name | VARCHAR(100) | Họ tên |
| email | VARCHAR(100) | Email |
| phone | VARCHAR(20) | Số điện thoại |
| subject | VARCHAR(50) | Chủ đề |
| message | TEXT | Nội dung |
| status | ENUM | Trạng thái: unread/read/replied |
| admin_reply | TEXT | Phản hồi của admin |
| replied_at | DATETIME | Thời gian phản hồi |
| replied_by | INT (FK) | ID admin phản hồi |
| created_at | DATETIME | Ngày gửi |
| updated_at | DATETIME | Ngày cập nhật |

## Yêu Cầu Giao Diện Người Dùng

### Thiết Kế Chung
- ✅ Thiết kế đáp ứng (responsive) tương thích máy tính và mobile
- ✅ Điều hướng trực quan với menu sticky
- ✅ Giao diện thân thiện, hiện đại với gradient colors
- ✅ Font Awesome icons cho tất cả actions
- ✅ Hỗ trợ đầy đủ tiếng Việt

### Màu Sắc Chủ Đạo
- **Primary**: `#667eea` (Gradient với `#764ba2`)
- **Success**: `#28a745`
- **Warning**: `#ffc107`
- **Danger**: `#dc3545`
- **Info**: `#17a2b8`


## Tính Năng Chính

### 1. Hệ Thống Xác Thực
- ✅ Đăng nhập với email/username và mật khẩu
- ✅ Đăng ký tài khoản với validation đầy đủ
- ✅ Quản lý phiên làm việc an toàn
- ✅ Mã hóa mật khẩu bằng `password_hash()` của PHP
- ✅ Kiểm tra trạng thái tài khoản (active/banned)
- ✅ Session timeout tự động

### 2. Quản Lý Bài Đăng Mua Bán
- ✅ Đăng bài với nhiều loại: Bán/Mua/Trao đổi
- ✅ Upload nhiều hình ảnh (tối đa 5 ảnh, mỗi ảnh < 2MB)
- ✅ Chỉnh sửa và xóa bài đăng
- ✅ Theo dõi trạng thái (Chờ duyệt/Đã duyệt/Đã bán/Từ chối)
- ✅ Đánh giá bài đăng (1-5 sao)
- ✅ Bình luận và thảo luận
- ✅ Yêu thích và chia sẻ bài đăng

### 3. Tìm Kiếm và Lọc Nâng Cao
- ✅ Tìm kiếm theo từ khóa (tiêu đề, mô tả, tên sản phẩm)
- ✅ Lọc theo danh mục (Laptop/Điện thoại/Gaming Gear/Phụ kiện)
- ✅ Lọc theo khoảng giá (min-max)
- ✅ Lọc theo loại bài đăng (Bán/Mua/Trao đổi)
- ✅ Lọc theo tình trạng sản phẩm (Như mới/Tốt/Khá/Cần sửa)
- ✅ Lọc theo đánh giá tối thiểu (3-5 sao)
- ✅ Sắp xếp đa dạng (Mới nhất/Giá/Tên/Đánh giá)
- ✅ Hiển thị số kết quả và active filters
- ✅ Xóa filter dễ dàng

### 4. Diễn Đàn Thảo Luận
- ✅ Tạo chủ đề thảo luận mới
- ✅ Phân loại theo danh mục
- ✅ Hệ thống tags cho bài viết
- ✅ Bình luận và trả lời bình luận
- ✅ Like/Unlike bài viết và bình luận
- ✅ Theo dõi số lượt xem
- ✅ Sắp xếp theo: Mới nhất/Phổ biến/Thịnh hành
- ✅ Tìm kiếm chủ đề

### 5. Hệ Thống Đánh Giá
- ✅ Đánh giá sản phẩm từ 1-5 sao
- ✅ Tính đánh giá trung bình tự động
- ✅ Hiển thị số lượt đánh giá
- ✅ Mỗi user chỉ đánh giá 1 lần/sản phẩm
- ✅ Cập nhật đánh giá real-time
- ✅ Lọc theo đánh giá tối thiểu

### 6. Quản Lý Tin Nhắn Liên Hệ
- ✅ Form liên hệ đa dạng chủ đề
- ✅ Phân loại tin nhắn (Hỗ trợ/Hợp tác/Góp ý/Báo cáo)
- ✅ Trạng thái tin nhắn (Chưa đọc/Đã đọc/Đã trả lời)
- ✅ Admin trả lời qua email
- ✅ Thống kê tin nhắn
- ✅ Chống spam (giới hạn 3 tin nhắn/giờ)

### 7. Admin Dashboard
- ✅ Thống kê tổng quan (Bài đăng/Thảo luận/Users)
- ✅ Biểu đồ hoạt động
- ✅ Bài đăng gần đây
- ✅ Quản lý users với phân trang
- ✅ Duyệt/Từ chối bài đăng hàng loạt
- ✅ Quản lý danh mục
- ✅ Xử lý tin nhắn liên hệ

## Cấu Trúc Dự Án

```
electroreview/
│
├── public/
│   ├── index.php                    # Trang chủ
│   ├── mua-ban.php                  # Trang mua bán
│   ├── thao-luan.php                # Diễn đàn thảo luận
│   ├── lien-he.php                  # Trang liên hệ
│   ├── admin.php                    # Admin dashboard
│   ├── admin_posts.php              # Quản lý bài đăng
│   │
│   ├── login.php                    # Xử lý đăng nhập
│   ├── login_handler.php            # Handler đăng nhập
│   ├── register.php                 # Xử lý đăng ký
│   ├── register_handler.php         # Handler đăng ký
│   ├── logout.php                   # Đăng xuất
│   │
│   ├── post_handler.php             # Xử lý đăng bài mua bán
│   ├── create_discussion.php        # Tạo bài thảo luận
│   ├── post_comment.php             # Đăng bình luận
│   ├── get_topic_detail.php         # Lấy chi tiết chủ đề
│   │
│   ├── admin_actions.php            # Xử lý actions admin
│   ├── contact_api.php              # API quản lý tin nhắn
│   ├── contact_submit.php           # Gửi tin nhắn liên hệ
│   │
│   ├── css/
│   │   └── style.css                # CSS chính
│   │
│   ├── js/
│   │   ├── script.js                # JavaScript chính
│   │   └── share-modal.js           # Modal chia sẻ
│   │
│   └── uploads/
│       └── posts/                   # Thư mục lưu ảnh bài đăng
│
├── database/
│   └── electroreview_db.sql         # Database schema & sample data
│
├── docs/
│   └── README.md                    # File này

```

## Các Tuyến Đường (Routes) Chính

### Public Routes
- `/index.php` - Trang chủ
- `/mua-ban.php` - Trang mua bán, trao đổi
- `/thao-luan.php` - Diễn đàn thảo luận
- `/lien-he.php` - Trang liên hệ

### User Routes (Yêu cầu đăng nhập)
- `/mua-ban.php?action=post` - Đăng bài mua bán
- `/mua-ban.php?action=my-posts` - Quản lý bài đăng cá nhân
- `/thao-luan.php?action=new-topic` - Tạo chủ đề mới

### Admin Routes (Yêu cầu quyền admin)
- `/admin.php` - Dashboard admin
- `/admin.php?section=posts` - Quản lý bài đăng
- `/admin.php?section=discussions` - Quản lý thảo luận
- `/admin.php?section=users` - Quản lý người dùng
- `/admin.php?section=messages` - Quản lý tin nhắn
- `/admin.php?section=categories` - Quản lý danh mục
- `/admin.php?section=settings` - Cài đặt hệ thống

### API Endpoints
- `POST /login.php` - Đăng nhập
- `POST /register.php` - Đăng ký
- `POST /post_handler.php` - Tạo/Cập nhật bài đăng
- `POST /create_discussion.php` - Tạo bài thảo luận
- `POST /post_comment.php` - Đăng bình luận
- `GET /get_topic_detail.php?id={topic_id}` - Lấy chi tiết chủ đề
- `POST /admin_actions.php` - Các actions admin
- `GET /contact_api.php` - Lấy tin nhắn liên hệ
- `POST /contact_submit.php` - Gửi tin nhắn liên hệ
- `GET/POST /mua-ban.php?action=rate` - Đánh giá bài đăng

## Hướng Dẫn Cài Đặt

### Bước 1: Yêu Cầu Hệ Thống
Đảm bảo máy chủ của bạn đáp ứng các yêu cầu:
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- PDO Extension

### Bước 2: Clone/Download Project
```bash
# Clone từ repository 
git clone https://github.com/nghiemthihuyentrang/NH-M-4---DEHA.git

# Hoặc download và giải nén file ZIP
```

### Bước 3: Cấu Hình Database

1. Tạo database mới:
```sql
CREATE DATABASE electroreview_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Sử dụng phpMyAdmin:
- Mở phpMyAdmin
- Chọn database `electroreview_db`
- Click tab "Import"
- Chọn file `electroreview_db.sql`
- Click "Go"

### Bước 4: Cấu Hình Kết Nối Database

Mở các file PHP và kiểm tra cấu hình database:

```php
// Trong các file: login.php, register.php, admin.php, etc.
$host = 'localhost';
$dbname = 'electroreview_db';
$username = 'root';
$password = ''; // Thay đổi nếu cần
```

### Bước 5: Truy Cập Hệ Thống

Mở trình duyệt và truy cập:
```
http://localhost/nhom4dehaphp/code/public/
```

## Tài Khoản Mặc Định

### Admin Account
- **Username**: admin
- **Email**: admin@electroreview.vn
- **Password**: password 
### Test User Accounts
Database đã có sẵn một số user test:
- **User 1**:  username: ntthanh, mk: ntthanh

