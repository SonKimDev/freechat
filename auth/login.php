<?php

session_start();

require_once __DIR__ . '/../config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Vui lòng nhập đầy đủ thông tin.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password, fullname
             FROM users
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["fullname"] = $user["fullname"];

                header("Location: ../chat/index.php");
                exit;

            } else {
                $error = "Mật khẩu không chính xác.";
            }

        } else {
            $error = "Tài khoản không tồn tại.";
        }

        $stmt->close();
    }
}

require_once __DIR__ . '/../layouts/header.php';

?>

<div class="auth-container">

    <div class="auth-card">

        <h2>Đăng nhập</h2>
        <p class="auth-subtitle">Chào mừng trở lại FreeChat</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username"
                       placeholder="Nhập tên đăng nhập" required>
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password"
                       placeholder="Nhập mật khẩu" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Đăng nhập
            </button>

        </form>

        <p class="auth-bottom">
            Chưa có tài khoản?
            <a href="register.php">Đăng ký ngay</a>
        </p>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
