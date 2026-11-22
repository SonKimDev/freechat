
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreeChat</title>

    <link rel="stylesheet" href="/freechat/assets/css/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-content">

        <a href="/freechat/" class="logo">FreeChat</a>

        <nav class="nav-links">

            <?php if ($loggedIn): ?>

                <a href="/freechat/chat/index.php">Tin nhắn</a>

                <a href="/freechat/users/search.php">
                    Tìm bạn bè
                </a>

                <a href="/freechat/users/profile.php" class="nav-user">
                    <?= htmlspecialchars(
                        $_SESSION['fullname'] ?: $_SESSION['username']
                    ) ?>
                </a>

                <a href="/freechat/auth/logout.php"
                   class="btn btn-primary">
                    Đăng xuất
                </a>

            <?php else: ?>

                <a href="/freechat/">Trang chủ</a>

                <a href="/freechat/auth/login.php">
                    Đăng nhập
                </a>

                <a href="/freechat/auth/register.php"
                   class="btn btn-primary">
                    Đăng ký
                </a>

            <?php endif; ?>

        </nav>

    </div>
</header>

<main class="container main-content">
