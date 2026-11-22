
<?php

session_start();

require_once __DIR__ . '/../config/database.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($username === "" || $fullname === "" ||
        $email === "" || $password === "") {

        $error = "Vui lòng nhập đầy đủ thông tin.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Email không hợp lệ.";

    } elseif (strlen($username) < 3) {

        $error = "Tên đăng nhập phải có ít nhất 3 ký tự.";

    } elseif (strlen($password) < 6) {

        $error = "Mật khẩu phải có ít nhất 6 ký tự.";

    } elseif ($password !== $confirm_password) {

        $error = "Mật khẩu xác nhận không khớp.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );

        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "Tên đăng nhập hoặc email đã tồn tại.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users (username, password, fullname, email)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $username,
                $hashed_password,
                $fullname,
                $email
            );

            if ($stmt->execute()) {
                $success = "Đăng ký thành công! Bạn có thể đăng nhập.";
            } else {
                $error = "Đăng ký thất bại. Vui lòng thử lại.";
            }
        }

        $stmt->close();
    }
}

require_once __DIR__ . '/../layouts/header.php';

?>

<div class="auth-container">

    <div class="auth-card">

        <h2>Tạo tài khoản</h2>
        <p class="auth-subtitle">Tham gia cộng đồng FreeChat</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
                <br>
                <a href="login.php">Đăng nhập ngay</a>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Họ và tên</label>
                <input type="text" name="fullname"
                       placeholder="Nhập họ và tên"
                       required>
            </div>

            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username"
                       placeholder="Nhập tên đăng nhập"
                       required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       placeholder="Nhập email"
                       required>
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password"
                       placeholder="Nhập mật khẩu"
                       required>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password"
                       placeholder="Nhập lại mật khẩu"
                       required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Đăng ký
            </button>

        </form>

        <p class="auth-bottom">
            Đã có tài khoản?
            <a href="login.php">Đăng nhập</a>
        </p>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
