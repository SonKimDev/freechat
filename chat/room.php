
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$currentUser = (int) $_SESSION['user_id'];
$conversationId = (int) ($_GET['id'] ?? 0);

if ($conversationId <= 0) {
    header("Location: index.php");
    exit;
}


// Kiểm tra người dùng có thuộc cuộc trò chuyện không
$stmt = $conn->prepare("
    SELECT id
    FROM conversation_members
    WHERE conversation_id = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $conversationId, $currentUser);
$stmt->execute();

$membership = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$membership) {
    exit("Bạn không có quyền truy cập cuộc trò chuyện này.");
}


// Lấy thông tin người đang trò chuyện, bao gồm avatar
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.fullname, u.avatar
    FROM conversation_members cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.conversation_id = ?
      AND cm.user_id != ?
    LIMIT 1
");

$stmt->bind_param("ii", $conversationId, $currentUser);
$stmt->execute();

$otherUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$otherUser) {
    $otherUser = [
        'id' => 0,
        'username' => 'Cuộc trò chuyện',
        'fullname' => 'Cuộc trò chuyện',
        'avatar' => null
    ];
}

$displayName = $otherUser['fullname'] ?: $otherUser['username'];

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="chat-room">

    <div class="chat-room-header">
        <a href="index.php" class="back-btn">←</a>


<div class="chat-user-info">

    <div class="chat-user-avatar">
        <?php if (!empty($otherUser['avatar'])): ?>
            <img
                src="../<?= htmlspecialchars($otherUser['avatar']) ?>"
                alt="Avatar"
            >
        <?php else: ?>
            <div class="chat-default-avatar">
                <?= strtoupper(substr($otherUser['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="chat-user-details">
        <h3>
            <?= htmlspecialchars($otherUser['fullname'] ?: $otherUser['username']) ?>
        </h3>
        <p>Đang trò chuyện</p>
    </div>

</div>

    </div>

    <div class="chat-messages" id="chatMessages">
        <div class="empty-chat">
            <h3>Bắt đầu cuộc trò chuyện</h3>
            <p>Hãy gửi tin nhắn đầu tiên!</p>
        </div>
    </div>

    <form class="chat-input-area" id="messageForm">
        <input
            type="text"
            id="messageInput"
            placeholder="Nhập tin nhắn..."
            autocomplete="off"
            required
        >

        <button type="submit">Gửi</button>
    </form>

</div>

<script>
const conversationId = <?= $conversationId ?>;
const currentUserId = <?= $currentUser ?>;

const messageForm = document.getElementById("messageForm");
const messageInput = document.getElementById("messageInput");
const chatMessages = document.getElementById("chatMessages");

let oldestMessageId = null;
let loadingOldMessages = false;
let hasMoreMessages = true;
let latestMessageId = 0;
// Tạo bong bóng tin nhắn

function createMessageElement(msg) {
    const message = document.createElement("div");

    const isMine = Number(msg.sender_id) === currentUserId;

    message.className = "message-bubble " +
        (isMine ? "message-mine" : "message-theirs");

    message.textContent = msg.content;
    message.dataset.id = msg.id;

    // Định dạng ngày giờ gửi
    const date = new Date(String(msg.sent_at).replace(" ", "T"));

    const formattedTime = date.toLocaleString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    });

    // Di chuột vào tin nhắn để xem thời gian
    message.title = "Gửi lúc: " + formattedTime;

    return message;
}


// Tải tin nhắn từ database
async function loadMessages() {
    try {
        const response = await fetch(
            `fetch.php?conversation_id=${conversationId}`
        );

        const result = await response.json();

        if (!result.success) return;

        chatMessages.innerHTML = "";

        if (result.messages.length === 0) {
            oldestMessageId = null;
            latestMessageId = 0;
            chatMessages.innerHTML = `
                <div class="empty-chat">
                    <h3>Bắt đầu cuộc trò chuyện</h3>
                    <p>Hãy gửi tin nhắn đầu tiên!</p>
                </div>
            `;
            hasMoreMessages = false;
            return;
        }

        result.messages.forEach(msg => {
            chatMessages.appendChild(createMessageElement(msg));
        });

        oldestMessageId = Number(result.messages[0].id);
        latestMessageId = Number(
            result.messages[result.messages.length - 1].id
        );
        hasMoreMessages = result.messages.length === 30;

        chatMessages.scrollTop = chatMessages.scrollHeight;

    } catch (error) {
        console.error("Lỗi tải tin nhắn:", error);
    }
}


// Tự động kiểm tra tin nhắn mới
async function checkNewMessages() {
    if (latestMessageId <= 0) return;

    try {
        const response = await fetch(
            `fetch.php?conversation_id=${conversationId}&after_id=${latestMessageId}`
        );

        const result = await response.json();

        if (!result.success || result.messages.length === 0) return;

        // Kiểm tra người dùng có đang xem cuối cuộc trò chuyện không
        const isNearBottom =
            chatMessages.scrollHeight -
            chatMessages.scrollTop -
            chatMessages.clientHeight < 120;

        const emptyChat = chatMessages.querySelector(".empty-chat");
        if (emptyChat) emptyChat.remove();

        result.messages.forEach(msg => {
            // Tránh hiển thị trùng tin nhắn
            if (chatMessages.querySelector(`[data-id="${msg.id}"]`)) {
                return;
            }

            chatMessages.appendChild(createMessageElement(msg));

            latestMessageId = Math.max(
                latestMessageId,
                Number(msg.id)
            );
        });

        // Chỉ tự cuộn xuống nếu người dùng đang xem tin mới nhất
        if (isNearBottom) {
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: "smooth"
            });
        }

    } catch (error) {
        console.error("Lỗi nhận tin nhắn mới:", error);
    }
}


// Gửi tin nhắn
messageForm.addEventListener("submit", async function(e) {
    e.preventDefault();

    const content = messageInput.value.trim();

    if (!content) return;

    const formData = new FormData();
    formData.append("conversation_id", conversationId);
    formData.append("content", content);

    try {
        const response = await fetch("send.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            messageInput.value = "";

            // Tải lại tin nhắn từ database
            await loadMessages();
            messageInput.focus();
        } else {
            alert(result.message || "Không thể gửi tin nhắn!");
        }

    } catch (error) {
        console.error(error);
        alert("Có lỗi khi gửi tin nhắn!");
    }
});

// Tải tin nhắn cũ khi cuộn lên đầu
chatMessages.addEventListener("scroll", async function() {
    if (
        chatMessages.scrollTop <= 10 &&
        !loadingOldMessages &&
        hasMoreMessages &&
        oldestMessageId !== null
    ) {
        loadingOldMessages = true;

        const oldHeight = chatMessages.scrollHeight;

        try {
            const response = await fetch(
                `fetch.php?conversation_id=${conversationId}&before_id=${oldestMessageId}`
            );

            const result = await response.json();

            if (result.success && result.messages.length > 0) {
                result.messages.forEach(msg => {
                    chatMessages.prepend(createMessageElement(msg));
                });

                oldestMessageId = result.messages[0].id;

                // Giữ nguyên vị trí đang đọc
                chatMessages.scrollTop =
                    chatMessages.scrollHeight - oldHeight;

                hasMoreMessages = result.messages.length === 30;
            } else {
                hasMoreMessages = false;
            }

        } catch (error) {
            console.error("Lỗi tải tin nhắn cũ:", error);
        }

        loadingOldMessages = false;
    }
});

// Khởi động phòng chat
loadMessages().then(() => {
    setInterval(checkNewMessages, 1500);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
