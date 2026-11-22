
<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = currentUserId();
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Mật khẩu mới phải có ít nhất 8 ký tự.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {

        // Lấy mật khẩu hiện tại
        $stmt = $conn->prepare("
            SELECT password FROM users WHERE id = ?
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($oldPassword, $user['password'])) {
            $error = "Mật khẩu hiện tại không chính xác.";
        } else {

            // Mã hóa mật khẩu mới
            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare("
                UPDATE users SET password = ? WHERE id = ?
            ");

            $stmt->bind_param("si", $hashedPassword, $userId);

            if ($stmt->execute()) {
                $success = "Đổi mật khẩu thành công!";
            } else {
                $error = "Không thể đổi mật khẩu.";
            }

            $stmt->close();
        }
    }
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="profile-container">

    <h2>Đổi mật khẩu</h2>

    <div class="profile-card">

        <?php if ($error): ?>
            <p class="form-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="form-success">
                <?= htmlspecialchars($success) ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <div class="profile-form-group">
                <label>Mật khẩu hiện tại</label>
                <input
                    type="password"
                    name="old_password"
                    required
                >
            </div>

            <div class="profile-form-group">
                <label>Mật khẩu mới</label>
                <input
                    type="password"
                    name="new_password"
                    minlength="8"
                    required
                >
            </div>

            <div class="profile-form-group">
                <label>Xác nhận mật khẩu mới</label>
                <input
                    type="password"
                    name="confirm_password"
                    minlength="8"
                    required
                >
            </div>

            <div class="profile-actions">
                <button type="submit" class="profile-btn">
                    Đổi mật khẩu
                </button>

                <a href="profile.php" class="profile-btn secondary">
                    Quay lại
                </a>
            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
