
<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$currentUser = (int) $_SESSION['user_id'];
$targetUser = (int) ($_GET['user_id'] ?? 0);

if ($targetUser <= 0 || $targetUser === $currentUser) {
    header("Location: ../users/search.php");
    exit;
}

// Kiểm tra người dùng tồn tại
$stmt = $conn->prepare(
    "SELECT id FROM users WHERE id = ?"
);

$stmt->bind_param("i", $targetUser);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    $stmt->close();
    exit("Người dùng không tồn tại.");
}

$stmt->close();

// Kiểm tra cuộc trò chuyện đã tồn tại
$sql = "
    SELECT c.id
    FROM conversations c
    JOIN conversation_members cm1
        ON cm1.conversation_id = c.id
    JOIN conversation_members cm2
        ON cm2.conversation_id = c.id
    WHERE c.type = 'private'
      AND cm1.user_id = ?
      AND cm2.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $currentUser, $targetUser);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $conversation = $result->fetch_assoc();
    $conversationId = $conversation['id'];

    $stmt->close();

} else {

    $stmt->close();

    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare(
            "INSERT INTO conversations (type)
             VALUES ('private')"
        );

        $stmt->execute();

        $conversationId = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO conversation_members
             (conversation_id, user_id)
             VALUES (?, ?), (?, ?)"
        );

        $stmt->bind_param(
            "iiii",
            $conversationId,
            $currentUser,
            $conversationId,
            $targetUser
        );

        $stmt->execute();
        $stmt->close();

        $conn->commit();

    } catch (Throwable $e) {

        $conn->rollback();
        exit("Không thể tạo cuộc trò chuyện: " . $e->getMessage());

    }
}

header("Location: room.php?id=" . $conversationId);
exit;

?>
