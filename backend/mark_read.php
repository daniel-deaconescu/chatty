<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$conversation_id = $data['conversation_id'] ?? null;
$user_id = $data['user_id'] ?? null;

if (!$conversation_id || !$user_id) {
    jsonResponse(['error' => 'Conversation ID and User ID required'], 400);
}

try {
    // Mark all messages in this conversation as read for the current user
    // Only mark messages that were sent by other users (not by the current user)
    $stmt = $db->prepare("
        UPDATE messages 
        SET read_status = 1 
        WHERE conversation_id = :conversation_id 
        AND sender_id != :user_id 
        AND read_status = 0
    ");
    $stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    
    if (!$stmt->execute()) {
        throw new Exception($db->lastErrorMsg());
    }
    
    $affectedRows = $db->changes();
    
    jsonResponse([
        'success' => true,
        'messages_marked_read' => $affectedRows
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'error' => 'Failed to mark messages as read',
        'details' => $e->getMessage()
    ], 500);
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
}
?> 