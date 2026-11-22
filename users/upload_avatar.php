
<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = currentUserId();
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $error = "Vui lòng chọn ảnh hợp lệ.";
    } else {
        $file = $_FILES['avatar'];

        // Giới hạn dung lượng 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            $error = "Ảnh không được vượt quá 5MB.";
        } else {
            // Kiểm tra nội dung file thực tế
            $imageInfo = @getimagesize($file['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $mime = $imageInfo['mime'] ?? '';

            if (!$imageInfo || !isset($allowedTypes[$mime])) {
                $error = "Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.";
            } else {
                $uploadDir = __DIR__ . '/../uploads/avatars/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $extension = $allowedTypes[$mime];
                $filename = bin2hex(random_bytes(16)) . '.' . $extension;

                $targetPath = $uploadDir . $filename;
                $relativePath = 'uploads/avatars/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {

                    // Lấy ảnh đại diện cũ
                    $stmt = $conn->prepare("
                        SELECT avatar FROM users WHERE id = ?
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $oldUser = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    // Cập nhật ảnh mới
                    $stmt = $conn->prepare("
                        UPDATE users SET avatar = ? WHERE id = ?
                    ");
                    $stmt->bind_param("si", $relativePath, $userId);

                    if ($stmt->execute()) {
                        $stmt->close();

                        // Xóa ảnh cũ nếu nằm trong thư mục avatar
                        $oldAvatar = $oldUser['avatar'] ?? '';

                        if (
                            $oldAvatar &&
                            strpos($oldAvatar, 'uploads/avatars/') === 0 &&
                            basename($oldAvatar) === substr($oldAvatar, strlen('uploads/avatars/'))
                        ) {
                            $oldPath = __DIR__ . '/../' . $oldAvatar;

                            if (is_file($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        header("Location: profile.php?avatar_updated=1");
                        exit;
                    } else {
                        $stmt->close();
                        unlink($targetPath);
                        $error = "Không thể cập nhật ảnh đại diện.";
                    }
                } else {
                    $error = "Không thể tải ảnh lên máy chủ.";
                }
            }
        }
    }
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="profile-container">

    <h2>Thay đổi ảnh đại diện</h2>

    <div class="profile-card">

        <?php if ($error): ?>
            <p class="form-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST"
              enctype="multipart/form-data">

            <div class="avatar-preview">
                <img
                    id="avatarPreview"
                    src="../assets/images/default-avatar.png"
                    alt="Xem trước ảnh"
                >
            </div>

            <div class="profile-form-group">
                <label>Chọn ảnh đại diện</label>

                <input
                    type="file"
                    name="avatar"
                    id="avatarInput"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >
            </div>

            <p class="avatar-note">
                JPG, PNG hoặc WEBP. Dung lượng tối đa 5MB.
            </p>

            <div class="profile-actions">
                <button type="submit" class="profile-btn">
                    Tải ảnh lên
                </button>

                <a href="profile.php" class="profile-btn secondary">
                    Quay lại
                </a>
            </div>

        </form>

    </div>

</div>

<script>
const avatarInput = document.getElementById("avatarInput");
const avatarPreview = document.getElementById("avatarPreview");

avatarInput.addEventListener("change", function() {
    const file = this.files[0];

    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert("Ảnh không được vượt quá 5MB!");
        this.value = "";
        return;
    }

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!allowedTypes.includes(file.type)) {
        alert("Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP!");
        this.value = "";
        return;
    }

    avatarPreview.src = URL.createObjectURL(file);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
