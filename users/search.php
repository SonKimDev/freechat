<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$keyword = "";
$users = [];

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["keyword"])) {

    $keyword = trim($_GET["keyword"]);

    if ($keyword !== "") {

        $search = "%" . $keyword . "%";

        $stmt = $conn->prepare(
            "SELECT id, username, fullname
             FROM users
             WHERE (username LIKE ? OR fullname LIKE ?)
             AND id != ?
             LIMIT 20"
        );

        $stmt->bind_param(
            "ssi",
            $search,
            $search,
            $_SESSION['user_id']
        );

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $stmt->close();
    }
}

require_once __DIR__ . '/../layouts/header.php';

?>

<div class="search-page">

    <h2>Tìm kiếm người dùng</h2>

    <form method="GET" class="search-form">

        <input
            type="text"
            name="keyword"
            placeholder="Nhập tên hoặc username..."
            value="<?= htmlspecialchars($keyword) ?>"
            required
        >

        <button type="submit" class="btn btn-primary">
            Tìm kiếm
        </button>

    </form>

    <div class="user-results">

        <?php if ($keyword !== ""): ?>

            <?php if (count($users) > 0): ?>

                <?php foreach ($users as $user): ?>

                    <div class="user-card">

                        <div class="user-info">
                            <h3>
                                <?= htmlspecialchars($user['fullname'] ?: $user['username']) ?>
                            </h3>

                            <p>
                                @<?= htmlspecialchars($user['username']) ?>
                            </p>
                        </div>

                        <a href="../chat/create.php?user_id=<?= $user['id'] ?>"
                           class="btn btn-primary">
                            Nhắn tin
                        </a>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p>Không tìm thấy người dùng nào.</p>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
