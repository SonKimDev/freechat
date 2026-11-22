
<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: chat/index.php");
    exit;
}

require_once __DIR__ . '/layouts/header.php';

?>

<section class="hero">

    <h1>Chào mừng đến với FreeChat!</h1>

    <p>
        Kết nối với mọi người và trò chuyện
        dễ dàng ngay trên nền tảng của chúng tôi.
    </p>

    <div class="hero-actions">

        <a href="auth/register.php" class="btn btn-primary">
            Bắt đầu ngay
        </a>

        <a href="auth/login.php" class="btn btn-outline">
            Đăng nhập
        </a>

    </div>

</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
