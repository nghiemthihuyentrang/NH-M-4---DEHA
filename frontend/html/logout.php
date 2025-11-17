<?php
session_start();

// Xóa tất cả session variables
$_SESSION = array();

// Xóa session cookie nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect về trang chủ hoặc trang trước đó
$redirect_url = $_GET['redirect'] ?? 'index.php';
header("Location: $redirect_url");
exit;
?>