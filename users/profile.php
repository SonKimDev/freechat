
<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = currentUserId();

$stmt = $conn->prepare("
    SELECT id, username, fullname, email, avatar, created_at
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

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="profile-container">

    <h2>Thông tin cá nhân</h2>

    <div class="profile-card">


    <div class="profile-avatar">

        <?php if (!empty($user['avatar'])): ?>
            <img
                src="../<?= htmlspecialchars($user['avatar']) ?>"
                alt="Avatar"
            >
        <?php else: ?>
            <div class="default-avatar">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>

        <a href="upload_avatar.php"
        class="avatar-edit-btn"
        title="Thay đổi ảnh đại diện"
        aria-label="Thay đổi ảnh đại diện">
            ✎
        </a>

    </div>


        <h3>
            <?= htmlspecialchars($user['fullname'] ?: $user['username']) ?>
        </h3>

        <p class="profile-username">
            @<?= htmlspecialchars($user['username']) ?>
        </p>

        <hr>

        <div class="profile-info">
            <label>Họ và tên</label>
            <p><?= htmlspecialchars($user['fullname'] ?? '') ?: 'Chưa cập nhật' ?></p>

            <label>Email</label>
            <p><?= htmlspecialchars($user['email'] ?? '') ?: 'Chưa cập nhật' ?></p>

            <label>Ngày tham gia</label>
            <p><?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
        </div>

        <div class="profile-actions">
            <a href="edit_profile.php" class="profile-btn">
                Chỉnh sửa thông tin
            </a>

            <a href="change_password.php" class="profile-btn secondary">
                Đổi mật khẩu
            </a>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
