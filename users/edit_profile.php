
<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = currentUserId();
$error = "";

$stmt = $conn->prepare("
    SELECT fullname, email
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    exit("Không tìm thấy tài khoản.");
}

$fullname = $user['fullname'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($fullname === '' || $email === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } else {

        // Kiểm tra email đã được tài khoản khác sử dụng chưa
        $stmt = $conn->prepare("
            SELECT id FROM users
            WHERE email = ? AND id != ?
        ");

        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();

        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $error = "Email này đã được sử dụng.";
        } else {
            $stmt = $conn->prepare("
                UPDATE users
                SET fullname = ?, email = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ssi", $fullname, $email, $userId);

            if ($stmt->execute()) {
                $stmt->close();

                header("Location: profile.php?updated=1");
                exit;
            }

            $error = "Không thể cập nhật thông tin.";
            $stmt->close();
        }
    }
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="profile-container">

    <h2>Chỉnh sửa thông tin</h2>

    <div class="profile-card">

        <?php if ($error): ?>
            <p class="form-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <div class="profile-form-group">
                <label>Họ và tên</label>
                <input
                    type="text"
                    name="fullname"
                    value="<?= htmlspecialchars($fullname) ?>"
                    maxlength="100"
                    required
                >
            </div>

            <div class="profile-form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    maxlength="100"
                    required
                >
            </div>

            <div class="profile-actions">
                <button type="submit" class="profile-btn">
                    Lưu thay đổi
                </button>

                <a href="profile.php" class="profile-btn secondary">
                    Quay lại
                </a>
            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
