<?php
require_once 'backend/db.php';

echo "Fixing database structure...\n";

// Check current table structure
$result = $db->query("PRAGMA table_info(messages)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
    echo "Column: {$row['name']} - Type: {$row['type']}\n";
}

if (!in_array('read_status', $columns)) {
    echo "\nAdding read_status column...\n";
    
    // Add the read_status column
    $db->exec("ALTER TABLE messages ADD COLUMN read_status INTEGER DEFAULT 0");
    
    // Create index for better performance
    $db->exec("CREATE INDEX IF NOT EXISTS idx_messages_read_status ON messages(read_status, conversation_id, sender_id)");
    
    // Update existing messages to be marked as read
    $db->exec("UPDATE messages SET read_status = 1 WHERE read_status IS NULL OR read_status = 0");
    
    echo "✓ read_status column added successfully\n";
} else {
    echo "✓ read_status column already exists\n";
}

// Verify the column was added
$result = $db->query("PRAGMA table_info(messages)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (in_array('read_status', $columns)) {
    echo "✓ Verification successful - read_status column exists\n";
} else {
    echo "✗ Verification failed - read_status column still missing\n";
}

$db->close();
?> 