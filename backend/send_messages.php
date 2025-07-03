<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$conversation_id = $data['conversation_id'] ?? null;
$sender_id = $data['sender_id'] ?? null;
$content = $data['content'] ?? null;

if (!$conversation_id || !$sender_id || !$content) {
    jsonResponse(['error' => 'Missing data'], 400);
}

$stmt = $db->prepare("
    INSERT INTO messages (conversation_id, sender_id, content)
    VALUES (:conversation_id, :sender_id, :content)
");

$stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
$stmt->bindValue(':sender_id', $sender_id, SQLITE3_INTEGER);
$stmt->bindValue(':content', $content, SQLITE3_TEXT);

if ($stmt->execute()) {
    jsonResponse(['success' => true]);
} else {
    jsonResponse(['error' => 'Failed to send message'], 500);
}

?>