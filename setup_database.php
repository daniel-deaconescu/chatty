<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Chatty Database Setup ===\n\n";

// Check if required files exist
$requiredFiles = [
    'backend/db.php',
    'backend/users.php',
    'backend/conversations.php',
    'backend/send_messages.php'
];

echo "Checking required files...\n";
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
        die("ERROR: Required file $file is missing. Please make sure all files are present.\n");
    }
}

// Check PHP version
echo "\nPHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
    echo "✓ PHP version is compatible\n";
} else {
    echo "✗ PHP version should be 7.0 or higher\n";
}

// Check SQLite extension
echo "\nChecking SQLite extension...\n";
if (extension_loaded('sqlite3')) {
    echo "✓ SQLite3 extension is loaded\n";
} else {
    echo "✗ SQLite3 extension is not loaded\n";
    die("ERROR: SQLite3 extension is required. Please enable it in your PHP configuration.\n");
}

// Check database directory
echo "\nChecking database directory...\n";
$dbDir = __DIR__ . '/database';
if (!is_dir($dbDir)) {
    echo "Creating database directory...\n";
    if (!mkdir($dbDir, 0755, true)) {
        die("ERROR: Cannot create database directory. Check permissions.\n");
    }
}
echo "✓ Database directory exists\n";

try {
    echo "\nInitializing database...\n";
    
    // Include database connection
    require_once 'backend/db.php';
    echo "✓ Database connection successful\n";
    
    // Check if read_status column exists in messages table
    echo "Checking database schema...\n";
    $result = $db->query("PRAGMA table_info(messages)");
    $columns = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }
    
    if (!in_array('read_status', $columns)) {
        echo "Adding read_status column to messages table...\n";
        $db->exec("ALTER TABLE messages ADD COLUMN read_status INTEGER DEFAULT 0");
        echo "✓ read_status column added\n";
    } else {
        echo "✓ read_status column already exists\n";
    }
    
    // Disable foreign key constraints temporarily
    $db->exec('PRAGMA foreign_keys = OFF');
    
    // Clear all data
    echo "Clearing existing data...\n";
    $db->exec('DELETE FROM messages');
    $db->exec('DELETE FROM participants');
    $db->exec('DELETE FROM conversations');
    $db->exec('DELETE FROM users');
    
    // Reset auto-increment counters
    $db->exec('DELETE FROM sqlite_sequence WHERE name IN ("users", "conversations", "messages")');
    
    // Re-enable foreign key constraints
    $db->exec('PRAGMA foreign_keys = ON');
    echo "✓ Database cleaned\n";
    
    // Create fresh users
    echo "Creating users...\n";
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
    
    foreach ($users as $user) {
        $stmt = $db->prepare("
            INSERT INTO users (name, status, profile_picture_url, last_seen) 
            VALUES (:name, :status, :profile_picture, datetime('now', '-' || :minutes || ' minutes'))
        ");
        $stmt->bindValue(':name', $user['name'], SQLITE3_TEXT);
        $stmt->bindValue(':status', $user['status'], SQLITE3_TEXT);
        $stmt->bindValue(':profile_picture', $user['profile_picture'], SQLITE3_TEXT);
        $stmt->bindValue(':minutes', rand(1, 120), SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // Create the current user (You)
    $stmt = $db->prepare("
        INSERT INTO users (id, name, status, profile_picture_url, last_seen) 
        VALUES (9, 'You', 'online', 'https://i.pravatar.cc/150?img=9', datetime('now'))
    ");
    $stmt->execute();
    
    echo "✓ Users created successfully\n";
    
    // Create conversations
    echo "Creating conversations...\n";
    $conversations = [];
    for ($i = 1; $i <= 5; $i++) {
        $db->exec("INSERT INTO conversations (is_pinned) VALUES (0)");
        $convId = $db->lastInsertRowID();
        
        $db->exec("INSERT INTO participants (conversation_id, user_id) VALUES ($convId, 9)");
        $db->exec("INSERT INTO participants (conversation_id, user_id) VALUES ($convId, $i)");
        
        $conversations[] = $convId;
    }
    
    // Add sample messages
    echo "Adding sample messages...\n";
    $sampleMessages = [
        [
            ['sender' => 1, 'content' => 'Hey! How are you doing today?', 'read' => 1, 'minutes_ago' => 60],
            ['sender' => 9, 'content' => 'I\'m doing great, thanks! How about you?', 'read' => 1, 'minutes_ago' => 55],
            ['sender' => 1, 'content' => 'Pretty good! Working on some new projects.', 'read' => 0, 'minutes_ago' => 30],
            ['sender' => 1, 'content' => 'Want to catch up later?', 'read' => 0, 'minutes_ago' => 25]
        ],
        [
            ['sender' => 2, 'content' => 'Hi there!', 'read' => 1, 'minutes_ago' => 45],
            ['sender' => 9, 'content' => 'Hello Bob!', 'read' => 1, 'minutes_ago' => 40],
            ['sender' => 2, 'content' => 'Did you see the new update?', 'read' => 0, 'minutes_ago' => 15]
        ],
        [
            ['sender' => 3, 'content' => 'Good morning!', 'read' => 1, 'minutes_ago' => 120],
            ['sender' => 9, 'content' => 'Morning Charlie!', 'read' => 1, 'minutes_ago' => 115],
            ['sender' => 3, 'content' => 'Have a great day!', 'read' => 1, 'minutes_ago' => 110]
        ],
        [
            ['sender' => 4, 'content' => 'Hey! Are you free for a call?', 'read' => 0, 'minutes_ago' => 20],
            ['sender' => 9, 'content' => 'Sure! When works for you?', 'read' => 1, 'minutes_ago' => 18],
            ['sender' => 4, 'content' => 'How about tomorrow at 2 PM?', 'read' => 0, 'minutes_ago' => 10]
        ],
        [
            ['sender' => 5, 'content' => 'Thanks for your help yesterday!', 'read' => 1, 'minutes_ago' => 90],
            ['sender' => 9, 'content' => 'No problem at all!', 'read' => 1, 'minutes_ago' => 85],
            ['sender' => 5, 'content' => 'You\'re the best!', 'read' => 1, 'minutes_ago' => 80]
        ]
    ];
    
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
                $stmt->bindValue(':minutes', $msg['minutes_ago'], SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }
    
    echo "✓ Messages added successfully\n";
    
    // Verify setup
    echo "\nVerifying setup...\n";
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    echo "✓ Found {$row['count']} users in database\n";
    
    $result = $db->query("SELECT COUNT(*) as count FROM messages");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    echo "✓ Found {$row['count']} messages in database\n";
    
    echo "\n=== Setup Complete! ===\n";
    echo "✅ Database initialized successfully\n";
    echo "✅ Sample data created\n";
    echo "✅ You can now access the application at: http://localhost/chatty/frontend/\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Please check:\n";
    echo "1. All files are present in the project directory\n";
    echo "2. XAMPP is running\n";
    echo "3. PHP version is 7.0 or higher\n";
    echo "4. SQLite3 extension is enabled\n";
    exit(1);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
?> 