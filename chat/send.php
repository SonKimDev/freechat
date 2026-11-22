
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Chưa đăng nhập"]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$currentUser = (int) $_SESSION['user_id'];
$conversationId = (int) ($_POST['conversation_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($conversationId <= 0 || $content === '') {
    echo json_encode(["success" => false, "message" => "Nội dung không hợp lệ"]);
    exit;
}

// Kiểm tra thành viên cuộc trò chuyện
$stmt = $conn->prepare("
    SELECT id FROM conversation_members
    WHERE conversation_id = ? AND user_id = ?
");
$stmt->bind_param("ii", $conversationId, $currentUser);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Không có quyền gửi tin nhắn"]);
    exit;
}
$stmt->close();

// Lưu tin nhắn
$stmt = $conn->prepare("
    INSERT INTO messages (conversation_id, sender_id, content)
    VALUES (?, ?, ?)
");

$stmt->bind_param("iis", $conversationId, $currentUser, $content);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message_id" => $conn->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Không thể gửi tin nhắn"]);
}

$stmt->close();
?>
