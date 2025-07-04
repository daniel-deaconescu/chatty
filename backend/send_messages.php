<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$conversation_id = $data['conversation_id'] ?? null;
$sender_id = $data['sender_id'] ?? null;
$content = trim($data['content'] ?? '');

if (!$conversation_id || !$sender_id || empty($content)) {
    jsonResponse(['error' => 'Missing required fields'], 400);
}

try {
    $stmt = $db->prepare("
        INSERT INTO messages (conversation_id, sender_id, content)
        VALUES (:conversation_id, :sender_id, :content)
    ");
    $stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
    $stmt->bindValue(':sender_id', $sender_id, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    
    if (!$stmt->execute()) {
        throw new Exception($db->lastErrorMsg());
    }
    
    // Return the newly created message with additional info
    $message_id = $db->lastInsertRowID();
    $stmt = $db->prepare("
        SELECT m.*, u.name as sender_name, u.profile_picture as sender_avatar
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = :message_id
    ");
    $stmt->bindValue(':message_id', $message_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $message = $result->fetchArray(SQLITE3_ASSOC);
    
    jsonResponse([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'error' => 'Failed to send message',
        'details' => $e->getMessage()
    ], 500);
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
}
?>