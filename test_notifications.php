<?php
require_once 'backend/db.php';

echo "Testing notification functionality...\n";

// Check if read_status column exists
$result = $db->query("PRAGMA table_info(messages)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (in_array('read_status', $columns)) {
    echo "✓ read_status column exists\n";
} else {
    echo "✗ read_status column missing\n";
    exit(1);
}

// Add some test unread messages
echo "\nAdding test unread messages...\n";

// Get conversation IDs
$conversations = $db->query("SELECT id FROM conversations LIMIT 3");
$convIds = [];
while ($row = $conversations->fetchArray(SQLITE3_ASSOC)) {
    $convIds[] = $row['id'];
}

if (empty($convIds)) {
    echo "No conversations found. Creating test conversation...\n";
    
    // Create a test conversation
    $db->exec("INSERT INTO conversations (is_pinned) VALUES (0)");
    $convId = $db->lastInsertRowID();
    
    // Add participants
    $db->exec("INSERT INTO participants (conversation_id, user_id) VALUES ($convId, 9)");
    $db->exec("INSERT INTO participants (conversation_id, user_id) VALUES ($convId, 1)");
    
    $convIds = [$convId];
}

// Add unread messages from other users to current user
foreach ($convIds as $convId) {
    $stmt = $db->prepare("
        INSERT INTO messages (conversation_id, sender_id, content, read_status) 
        VALUES (:conv_id, :sender_id, :content, 0)
    ");
    
    // Add message from user 1
    $stmt->bindValue(':conv_id', $convId, SQLITE3_INTEGER);
    $stmt->bindValue(':sender_id', 1, SQLITE3_INTEGER);
    $stmt->bindValue(':content', 'Test unread message ' . date('H:i:s'), SQLITE3_TEXT);
    $stmt->execute();
    
    echo "Added unread message to conversation $convId\n";
}

// Test the users endpoint
echo "\nTesting users endpoint...\n";
$stmt = $db->prepare("
    SELECT 
        u.id, 
        u.name, 
        u.status, 
        u.last_seen, 
        u.profile_picture_url as profile_picture,
        COALESCE(unread.unread_count, 0) as unread_count
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
    WHERE u.id != 9  -- Exclude the 'You' user
    ORDER BY unread_count DESC, name ASC
");

$result = $stmt->execute();
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "User: {$row['name']} - Unread: {$row['unread_count']}\n";
}

echo "\nTest completed successfully!\n";
$db->close();
?> 