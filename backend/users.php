<?php
require_once 'db.php';

try {
    $stmt = $db->prepare("
        SELECT 
            u.id, 
            u.name, 
            u.status, 
            u.last_seen, 
            COALESCE(u.profile_picture_url, u.profile_picture) as profile_picture,
            COALESCE(unread.unread_count, 0) as unread_count,
            last_msg.content as last_message,
            last_msg.timestamp as last_message_time
        FROM users u
        LEFT JOIN (
            SELECT 
                p2.user_id,
                COUNT(m.id) as unread_count
            FROM participants p1
            JOIN participants p2 ON p1.conversation_id = p2.conversation_id
            JOIN messages m ON p1.conversation_id = m.conversation_id
            WHERE p1.user_id = 9  -- Current user
            AND p2.user_id != 9   -- Other users
            AND m.sender_id = p2.user_id  -- Messages from other users
            AND m.read_status = 0  -- Unread messages
            GROUP BY p2.user_id
        ) unread ON u.id = unread.user_id
        LEFT JOIN (
            SELECT 
                p2.user_id,
                m.content,
                m.timestamp
            FROM participants p1
            JOIN participants p2 ON p1.conversation_id = p2.conversation_id
            JOIN messages m ON p1.conversation_id = m.conversation_id
            WHERE p1.user_id = 9  -- Current user
            AND p2.user_id != 9   -- Other users
            AND m.timestamp = (
                SELECT MAX(m2.timestamp)
                FROM participants p3
                JOIN participants p4 ON p3.conversation_id = p4.conversation_id
                JOIN messages m2 ON p3.conversation_id = m2.conversation_id
                WHERE p3.user_id = 9
                AND p4.user_id = p2.user_id
            )
        ) last_msg ON u.id = last_msg.user_id
        WHERE u.id != 9  -- Exclude the 'You' user
        ORDER BY 
            CASE WHEN status = 'online' THEN 0 ELSE 1 END,
            unread_count DESC,
            COALESCE(last_msg.timestamp, '1970-01-01') DESC,
            name ASC
    ");
    
    $result = $stmt->execute();
    if (!$result) {
        throw new Exception($db->lastErrorMsg());
    }
    
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    
    jsonResponse($users);
    
} catch (Exception $e) {
    jsonResponse([
        'error' => 'Failed to fetch users',
        'details' => $e->getMessage()
    ], 500);
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
}
?>