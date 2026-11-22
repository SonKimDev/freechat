
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
$conversationId = (int) ($_GET['conversation_id'] ?? 0);
$beforeId = (int) ($_GET['before_id'] ?? 0);
$afterId = (int) ($_GET['after_id'] ?? 0);

if ($conversationId <= 0) {
    echo json_encode(["success" => false, "message" => "Cuộc trò chuyện không hợp lệ"]);
    exit;
}

// Kiểm tra quyền truy cập
$stmt = $conn->prepare("
    SELECT id FROM conversation_members
    WHERE conversation_id = ? AND user_id = ?
");
$stmt->bind_param("ii", $conversationId, $currentUser);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Không có quyền truy cập"]);
    exit;
}
$stmt->close();

// Lấy tin nhắn mới hơn
if ($afterId > 0) {
    $stmt = $conn->prepare("
        SELECT id, sender_id, content, sent_at
        FROM messages
        WHERE conversation_id = ? AND id > ?
        ORDER BY id ASC
        LIMIT 30
    ");
    $stmt->bind_param("ii", $conversationId, $afterId);
}
// Lấy tin nhắn cũ hơn
elseif ($beforeId > 0) {
    $stmt = $conn->prepare("
        SELECT id, sender_id, content, sent_at
        FROM messages
        WHERE conversation_id = ? AND id < ?
        ORDER BY id DESC
        LIMIT 30
    ");
    $stmt->bind_param("ii", $conversationId, $beforeId);
}
// Lấy 30 tin nhắn mới nhất
else {
    $stmt = $conn->prepare("
        SELECT id, sender_id, content, sent_at
        FROM messages
        WHERE conversation_id = ?
        ORDER BY id DESC
        LIMIT 30
    ");
    $stmt->bind_param("i", $conversationId);
}

$stmt->execute();
$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$stmt->close();

// Chỉ cần đảo thứ tự khi lấy tin nhắn cũ hoặc mới nhất
if ($afterId === 0) {
    $messages = array_reverse($messages);
}

echo json_encode([
    "success" => true,
    "messages" => $messages
], JSON_UNESCAPED_UNICODE);
?>
