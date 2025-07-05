<?php
require_once 'backend/db.php';

echo "Adding realistic mock data for testing...\n";

// First, let's make sure we have some users
$users = [
    ['name' => 'Alice Johnson', 'status' => 'online', 'profile_picture' => 'https://i.pravatar.cc/150?img=1'],
    ['name' => 'Bob Smith', 'status' => 'online', 'profile_picture' => 'https://i.pravatar.cc/150?img=2'],
    ['name' => 'Charlie Brown', 'status' => 'offline', 'profile_picture' => 'https://i.pravatar.cc/150?img=3'],
    ['name' => 'Diana Prince', 'status' => 'online', 'profile_picture' => 'https://i.pravatar.cc/150?img=4'],
    ['name' => 'Eva Garcia', 'status' => 'offline', 'profile_picture' => 'https://i.pravatar.cc/150?img=5'],
    ['name' => 'Frank Wilson', 'status' => 'online', 'profile_picture' => 'https://i.pravatar.cc/150?img=6'],
    ['name' => 'Grace Lee', 'status' => 'offline', 'profile_picture' => 'https://i.pravatar.cc/150?img=7'],
    ['name' => 'Henry Davis', 'status' => 'online', 'profile_picture' => 'https://i.pravatar.cc/150?img=8']
];

// Update users with profile pictures
foreach ($users as $index => $user) {
    $stmt = $db->prepare("UPDATE users SET profile_picture_url = :url WHERE id = :id");
    $stmt->bindValue(':url', $user['profile_picture'], SQLITE3_TEXT);
    $stmt->bindValue(':id', $index + 1, SQLITE3_INTEGER);
    $stmt->execute();
}

// Create conversations between current user (id=9) and other users
$conversations = [];
for ($i = 1; $i <= 5; $i++) {
    $db->exec("INSERT INTO conversations (is_pinned) VALUES (0)");
    $convId = $db->lastInsertRowID();
    
    // Add current user and user i as participants
    $db->exec("INSERT INTO participants (conversation_id, user_id) VALUES ($convId, 9)");
    $db->exec("INSERT INTO participants (conversation_id, user_id) VALUES ($convId, $i)");
    
    $conversations[] = $convId;
}

// Sample messages for each conversation
$sampleMessages = [
    [
        ['sender' => 1, 'content' => 'Hey! How are you doing today?', 'read' => 0],
        ['sender' => 9, 'content' => 'I\'m doing great, thanks! How about you?', 'read' => 1],
        ['sender' => 1, 'content' => 'Pretty good! Working on some new projects.', 'read' => 0],
        ['sender' => 1, 'content' => 'Want to catch up later?', 'read' => 0]
    ],
    [
        ['sender' => 2, 'content' => 'Hi there!', 'read' => 1],
        ['sender' => 9, 'content' => 'Hello Bob!', 'read' => 1],
        ['sender' => 2, 'content' => 'Did you see the new update?', 'read' => 0]
    ],
    [
        ['sender' => 3, 'content' => 'Good morning!', 'read' => 1],
        ['sender' => 9, 'content' => 'Morning Charlie!', 'read' => 1],
        ['sender' => 3, 'content' => 'Have a great day!', 'read' => 1]
    ],
    [
        ['sender' => 4, 'content' => 'Hey! Are you free for a call?', 'read' => 0],
        ['sender' => 9, 'content' => 'Sure! When works for you?', 'read' => 1],
        ['sender' => 4, 'content' => 'How about tomorrow at 2 PM?', 'read' => 0]
    ],
    [
        ['sender' => 5, 'content' => 'Thanks for your help yesterday!', 'read' => 1],
        ['sender' => 9, 'content' => 'No problem at all!', 'read' => 1],
        ['sender' => 5, 'content' => 'You\'re the best!', 'read' => 1]
    ]
];

// Add messages to conversations
foreach ($conversations as $index => $convId) {
    if (isset($sampleMessages[$index])) {
        foreach ($sampleMessages[$index] as $msg) {
            $stmt = $db->prepare("
                INSERT INTO messages (conversation_id, sender_id, content, read_status, timestamp) 
                VALUES (:conv_id, :sender_id, :content, :read_status, datetime('now', '-' || :minutes || ' minutes'))
            ");
            
            $stmt->bindValue(':conv_id', $convId, SQLITE3_INTEGER);
            $stmt->bindValue(':sender_id', $msg['sender'], SQLITE3_INTEGER);
            $stmt->bindValue(':content', $msg['content'], SQLITE3_TEXT);
            $stmt->bindValue(':read_status', $msg['read'], SQLITE3_INTEGER);
            $stmt->bindValue(':minutes', rand(1, 60), SQLITE3_INTEGER);
            $stmt->execute();
        }
    }
}

echo "✓ Added mock conversations and messages\n";

// Test the users endpoint
echo "\nTesting users endpoint with new data...\n";
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
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "User: {$row['name']} - Unread: {$row['unread_count']} - Last: " . 
         substr($row['last_message'] ?? 'No messages', 0, 30) . "...\n";
}

echo "\n✓ Mock data added successfully!\n";
$db->close();
?> 