
<?php

// Chuyển hướng trang
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Lấy ID người dùng hiện tại
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Hiển thị thông báo
function setMessage($message, $type = "success") {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Lấy thông báo
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        return $message;
    }

    return null;
}

