
<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];

$sql = "
    SELECT
        c.id,
        c.type,
        c.name,

        CASE
            WHEN c.type = 'private' THEN (
                SELECT COALESCE(u.fullname, u.username)
                FROM conversation_members cm2
                JOIN users u ON u.id = cm2.user_id
                WHERE cm2.conversation_id = c.id
                  AND cm2.user_id != ?
                LIMIT 1
            )
            ELSE COALESCE(c.name, 'Nhóm chat')
        END AS conversation_name,

        (
            SELECT u.avatar
            FROM conversation_members cm2
            JOIN users u ON u.id = cm2.user_id
            WHERE cm2.conversation_id = c.id
            AND cm2.user_id != ?
            LIMIT 1
        ) AS conversation_avatar,

        (
            SELECT u.username
            FROM conversation_members cm2
            JOIN users u ON u.id = cm2.user_id
            WHERE cm2.conversation_id = c.id
            AND cm2.user_id != ?
            LIMIT 1
        ) AS conversation_username,

        (
            SELECT m.content
            FROM messages m
            WHERE m.conversation_id = c.id
            ORDER BY m.sent_at DESC, m.id DESC
            LIMIT 1
        ) AS last_message,

        (
            SELECT m.sent_at
            FROM messages m
            WHERE m.conversation_id = c.id
            ORDER BY m.sent_at DESC, m.id DESC
            LIMIT 1
        ) AS last_time

    FROM conversations c
    JOIN conversation_members cm
        ON cm.conversation_id = c.id

    WHERE cm.user_id = ?

    ORDER BY COALESCE(
        (
            SELECT m.sent_at
            FROM messages m
            WHERE m.conversation_id = c.id
            ORDER BY m.sent_at DESC, m.id DESC
            LIMIT 1
        ),
        c.created_at
    ) DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
$stmt->execute();

$result = $stmt->get_result();

require_once __DIR__ . '/../layouts/header.php';

?>

<div class="inbox">

    <aside class="inbox-sidebar">

        <div class="inbox-heading">
            <h2>Đoạn chat</h2>
        </div>

        <a href="../users/search.php"
           class="btn btn-primary search-friends-btn">
            + Tìm bạn bè
        </a>

        <div class="conversation-list">

            <?php if ($result->num_rows > 0): ?>

                <?php while ($conversation = $result->fetch_assoc()): ?>

                    <a class="conversation-item"
                       href="room.php?id=<?= $conversation['id'] ?>">

                        
                        <div class="conversation-avatar">

                            <?php if (
                                $conversation['type'] === 'private'
                                && !empty($conversation['conversation_avatar'])
                            ): ?>

                                <img
                                    src="../<?= htmlspecialchars($conversation['conversation_avatar']) ?>"
                                    alt="Avatar"
                                >

                            <?php else: ?>

                                <?= strtoupper(substr(
                                    $conversation['conversation_name'] ?? '?',
                                    0,
                                    1
                                )) ?>

                            <?php endif; ?>

                        </div>


                        <div class="conversation-info">

                            <h3>
                                <?= htmlspecialchars(
                                    $conversation['conversation_name'] ?? 'Cuộc trò chuyện'
                                ) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $conversation['last_message'] ?? 'Chưa có tin nhắn'
                                ) ?>
                            </p>

                        </div>

                    </a>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-conversations">
                    <p>Chưa có cuộc trò chuyện nào.</p>
                    <p>Hãy tìm bạn bè để bắt đầu trò chuyện!</p>
                </div>

            <?php endif; ?>

        </div>

    </aside>

    <section class="inbox-content">

        <div class="welcome-chat">
            <div class="welcome-icon">💬</div>

            <h2>Chào mừng đến với FreeChat</h2>

            <p>
                Chọn một cuộc trò chuyện hoặc tìm bạn bè
                để bắt đầu nhắn tin.
            </p>

            <a href="../users/search.php"
               class="btn btn-primary">
                Tìm bạn bè
            </a>
        </div>

    </section>

</div>

<?php

$stmt->close();

require_once __DIR__ . '/../layouts/footer.php';

?>
