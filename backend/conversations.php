<?php
require_once 'db.php';

$user_id = $_GET['user_id'] ?? null;
$current_user_id = 9; // "You" user from our database setup

if (!$user_id || !is_numeric($user_id)) {
    jsonResponse(['error' => 'Valid user ID required'], 400);
}

try {
    // Find existing conversation between current user and selected user
    $stmt = $db->prepare("
        SELECT c.id, c.is_pinned, c.created_at
        FROM conversations c
        JOIN participants p1 ON c.id = p1.conversation_id AND p1.user_id = :current_user_id
        JOIN participants p2 ON c.id = p2.conversation_id AND p2.user_id = :user_id
        LIMIT 1
    ");
    $stmt->bindValue(':current_user_id', $current_user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $conversation = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$conversation) {
        // Create new conversation if none exists
        $db->exec("BEGIN TRANSACTION");
        
        $stmt = $db->prepare("INSERT INTO conversations (is_pinned) VALUES (0)");
        $stmt->execute();
        $conversation_id = $db->lastInsertRowID();
        
        // Add both users as participants
        $stmt = $db->prepare("INSERT INTO participants (conversation_id, user_id) VALUES (:conversation_id, :user_id)");
        
        // Add current user
        $stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $current_user_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Add other user
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        $db->exec("COMMIT");
        
        $conversation = [
            'id' => $conversation_id,
            'is_pinned' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Get messages with sender info
    $stmt = $db->prepare("
        SELECT m.*, u.name as sender_name, u.profile_picture as sender_avatar
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = :conversation_id
        ORDER BY m.timestamp ASC
    ");
    $stmt->bindValue(':conversation_id', $conversation['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $messages = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $messages[] = $row;
    }
    
    // Mark messages as read for the current user
    $stmt = $db->prepare("
        UPDATE messages 
        SET read_status = 1 
        WHERE conversation_id = :conversation_id 
        AND sender_id != :current_user_id 
        AND read_status = 0
    ");
    $stmt->bindValue(':conversation_id', $conversation['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':current_user_id', $current_user_id, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Get participant info (excluding current user)
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.status, u.last_seen, u.profile_picture
        FROM participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.conversation_id = :conversation_id AND p.user_id != :current_user_id
    ");
    $stmt->bindValue(':conversation_id', $conversation['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':current_user_id', $current_user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $participants = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $participants[] = $row;
    }
    
    jsonResponse([
        'conversation' => $conversation,
        'messages' => $messages,
        'participants' => $participants
    ]);
    
} catch (Exception $e) {
    $db->exec("ROLLBACK");
    jsonResponse([
        'error' => 'Database operation failed',
        'details' => $e->getMessage()
    ], 500);
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
}
?>