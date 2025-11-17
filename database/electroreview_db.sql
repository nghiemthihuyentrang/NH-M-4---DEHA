-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th10 17, 2025 lúc 08:45 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `electroreview_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `category_slug`, `description`, `created_at`) VALUES
(1, 'Laptop', 'laptop', 'Laptop cũ các loại', '2025-11-10 07:03:38'),
(2, 'Điện thoại', 'dien-thoai', 'Smartphone và điện thoại cũ', '2025-11-10 07:03:38'),
(3, 'Gaming Gear', 'gaming-gear', 'Thiết bị gaming, PC gaming', '2025-11-10 07:03:38'),
(4, 'Phụ kiện', 'phu-kien', 'Phụ kiện điện tử, cáp sạc, tai nghe', '2025-11-10 07:03:38'),
(5, 'Sửa chữa', 'sua-chua', 'Hướng dẫn sửa chữa và bảo dưỡng', '2025-11-10 07:03:38'),
(6, 'Tổng hợp', 'tong-hop', 'Thảo luận tổng hợp', '2025-11-10 07:03:38');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `contact_messages`
--

INSERT INTO `contact_messages` (`message_id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`, `admin_reply`, `replied_at`, `replied_by`) VALUES
(1, 'Nguyễn Văn A', 'nguyenvana@gmail.com', NULL, 'Hỗ trợ kỹ thuật', 'Tôi cần hỗ trợ về việc đăng bài mua bán. Làm thế nào để upload nhiều ảnh?', 'new', '2025-11-14 12:03:15', NULL, NULL, NULL),
(3, 'Lê Văn C', 'levanc@gmail.com', NULL, 'Báo cáo lỗi', 'Tôi gặp lỗi khi đăng nhập bằng Facebook. Xin hướng dẫn khắc phục.', 'new', '2025-11-15 12:03:15', NULL, NULL, NULL),
(8, 'bvcx', 'gd@gmail.com', '0987654321', 'partnership', 'gfdgfdsgfds', 'new', '2025-11-16 20:45:42', NULL, NULL, NULL),
(9, 'Trần Dần', 'trandan@gmail.com', '0123456789', 'other', 'có trang titkok không', 'new', '2025-11-17 07:14:08', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `forum_replies`
--

CREATE TABLE `forum_replies` (
  `reply_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_reply_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `forum_topics`
--

CREATE TABLE `forum_topics` (
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `replies_count` int(11) DEFAULT 0,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','closed','pinned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `forum_topics`
--

INSERT INTO `forum_topics` (`topic_id`, `user_id`, `category_id`, `title`, `content`, `tags`, `views`, `replies_count`, `last_reply_at`, `status`, `created_at`, `updated_at`) VALUES
(6, 9, 1, 'Laptop lenovo yoga', 'Tôi muốn mua laptop phục vụ văn phòng, yêu cầu nhẹ để tiện mang đi, mong mọi người giúp tôi chọn', 'tư vấn', 0, 0, NULL, 'active', '2025-11-15 17:15:43', '2025-11-15 17:15:43'),
(7, 11, 2, 'iphone 17 cần tư vấnnnnnnnnnnnnnnnnnnnn', 'hiện tại nên mua điện thoại ip17 nữa kh ae', 'Điện thoại, Thảo luận', 0, 0, NULL, 'active', '2025-11-17 07:07:57', '2025-11-17 07:07:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` text DEFAULT NULL,
  `price` decimal(15,0) DEFAULT NULL,
  `post_type` enum('sell','buy','exchange') NOT NULL,
  `condition_item` enum('like_new','good','fair','poor') DEFAULT 'good',
  `location` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(15) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('active','sold','expired','hidden') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `category_id`, `title`, `content`, `tags`, `price`, `post_type`, `condition_item`, `location`, `contact_phone`, `contact_email`, `images`, `status`, `views`, `created_at`, `updated_at`) VALUES
(73, 9, 2, 'ip 7 plus', 'tốt', NULL, 100000, 'sell', 'good', 'Nghệ An', NULL, NULL, '[]', 'active', 0, '2025-11-13 15:33:45', '2025-11-13 15:33:45'),
(74, 9, 2, 'ip 16', 'hjk', NULL, 100000, 'sell', 'good', 'hn', NULL, NULL, '[\"uploads\\/posts\\/68472215564f8_1749492245.png\"]', 'active', 0, '2025-11-14 18:04:05', '2025-11-14 18:04:05'),
(76, 10, 1, 'máy tính HP', 'tôi muốn dùng laptop lenovo, ai cần trao đổi laptop hp liên hệ tôi', NULL, 15000000, 'exchange', 'like_new', 'hà nội', NULL, NULL, '[\"uploads\\/posts\\/68532e209e0f9_1750281760.png\"]', 'active', 0, '2025-11-16 21:22:40', '2025-11-16 21:22:40'),
(77, 10, 2, 'điện thoại iphone 13', 'Tôi không có nhu cầu nên cần bán', NULL, 9000000, 'sell', 'good', 'Nghệ An', NULL, NULL, '[\"uploads\\/posts\\/68532f29b87a6_1750282025.png\"]', 'active', 0, '2025-11-16 21:27:05', '2025-11-16 21:27:05'),
(78, 9, 2, 'ip 13 prm', 'tốt', NULL, 12000000, 'sell', 'good', 'HCM', NULL, NULL, '[\"uploads\\/posts\\/685330010d586_1750282241.png\"]', 'active', 0, '2025-11-16 21:30:41', '2025-11-16 21:30:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `post_comments`
--

CREATE TABLE `post_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('active','pending','hidden') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `post_likes`
--

CREATE TABLE `post_likes` (
  `like_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `post_ratings`
--

CREATE TABLE `post_ratings` (
  `rating_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `post_ratings`
--

INSERT INTO `post_ratings` (`rating_id`, `post_id`, `user_id`, `rating`, `created_at`) VALUES
(2, 74, 10, 5, '2025-11-15 21:20:09'),
(3, 77, 10, 5, '2025-11-16 21:27:12'),
(4, 73, 1, 5, '2025-11-16 21:28:12'),
(5, 77, 9, 2, '2025-11-16 21:41:38'),
(6, 78, 9, 3, '2025-11-16 22:18:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `topic_comments`
--

CREATE TABLE `topic_comments` (
  `comment_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `topic_comments`
--

INSERT INTO `topic_comments` (`comment_id`, `topic_id`, `user_id`, `content`, `created_at`, `updated_at`) VALUES
(8, 6, 9, 'ádfadf', '2025-11-16 00:28:19', '2025-11-16 00:28:19'),
(9, 7, 11, 'kh á!!!!!', '2025-11-17 14:08:23', '2025-11-17 14:08:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `phone`, `avatar`, `created_at`, `updated_at`, `status`, `is_admin`) VALUES
(1, 'admin', 'admin@electroreview.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên', '0901234567', 'default-avatar.png', '2025-11-10 07:03:38', '2025-11-10 11:44:56', 'active', 1),
(3, 'user2', 'user2@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', '0923456789', 'default-avatar.png', '2025-11-11 07:03:38', '2025-11-11 07:03:38', 'active', 0),
(9, 'uht', 'uiop@gmail.com', '$2y$10$p/4vem46hECqUXjukZW8LeJtF1Ow0vPPaaCRWxZppoig7NZx9ThS6', 'Uông Hoài Thương', '0987654321', 'default-avatar.png', '2025-11-12 20:15:47', '2025-11-12 20:15:47', 'active', 0),
(10, 'fghj@gmail.com', 'fghj@gmail.com', '$2y$10$QpM.lOA5xL43eWBUz6OrmOdrjFAWBwrcPTuCSWw5pC1FJrtW4lgO6', 'dsgf', '', 'default-avatar.png', '2025-11-14 21:15:41', '2025-11-14 21:15:41', 'active', 0),
(11, 'trang', 'admin@gmail.com', '$2y$10$knX3fRAekTDO.m4Raz3Nnuj/j/36HuWn3bR7wYO2qMDsMwlORvAuG', 'trang', '0955555555', 'default-avatar.png', '2025-11-16 09:17:08', '2025-11-16 09:17:08', 'active', 0);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_slug` (`category_slug`);

--
-- Chỉ mục cho bảng `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_contact_status_created` (`status`,`created_at`);

--
-- Chỉ mục cho bảng `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_reply_id` (`parent_reply_id`),
  ADD KEY `idx_forum_replies_topic_id` (`topic_id`);

--
-- Chỉ mục cho bảng `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`topic_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_forum_topics_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_posts_user_id` (`user_id`),
  ADD KEY `idx_posts_category_id` (`category_id`),
  ADD KEY `idx_posts_type_status` (`post_type`,`status`);

--
-- Chỉ mục cho bảng `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_parent_comment` (`parent_comment_id`);

--
-- Chỉ mục cho bảng `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_user_post` (`user_id`,`post_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `post_ratings`
--
ALTER TABLE `post_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_user_post_rating` (`user_id`,`post_id`),
  ADD KEY `idx_post_id` (`post_id`);

--
-- Chỉ mục cho bảng `topic_comments`
--
ALTER TABLE `topic_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_topic_id` (`topic_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `topic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT cho bảng `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `post_ratings`
--
ALTER TABLE `post_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `topic_comments`
--
ALTER TABLE `topic_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`topic_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_replies_ibfk_3` FOREIGN KEY (`parent_reply_id`) REFERENCES `forum_replies` (`reply_id`);

--
-- Các ràng buộc cho bảng `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD CONSTRAINT `forum_topics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_topics_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Các ràng buộc cho bảng `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Các ràng buộc cho bảng `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `post_comments` (`comment_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `post_ratings`
--
ALTER TABLE `post_ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `topic_comments`
--
ALTER TABLE `topic_comments`
  ADD CONSTRAINT `topic_comments_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`topic_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topic_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
