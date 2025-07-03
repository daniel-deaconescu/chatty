<?php 
require_once 'db.php';

// Pin/Unpin Conversations

$conversation_id = $_POST['conversation_id'] ?? null;

if (!$conversation_id) {
    jsonResponse(['error' => 'Conversation ID required'], 400);
}

// Toggle pin status
$stmt = $db->prepare("
    UPDATE conversations
    SET is_pinned = NOT is_pinned
    WHERE id = :conversation_id
");
$stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
$stmt->execute();

jsonResponse(['success' => true]);

?>