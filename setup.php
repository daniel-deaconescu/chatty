<?php
echo "=== Chatty Setup Verification ===\n\n";

// Check PHP version
echo "1. PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
    echo "   ✓ PHP version is compatible\n";
} else {
    echo "   ✗ PHP version should be 7.0 or higher\n";
}

// Check SQLite extension
echo "\n2. SQLite Extension:\n";
if (extension_loaded('sqlite3')) {
    echo "   ✓ SQLite3 extension is loaded\n";
} else {
    echo "   ✗ SQLite3 extension is not loaded\n";
    echo "   Please enable SQLite3 in your PHP configuration\n";
}

// Check if database directory exists
echo "\n3. Database Directory:\n";
$dbDir = __DIR__ . '/database';
if (is_dir($dbDir)) {
    echo "   ✓ Database directory exists\n";
} else {
    echo "   ✗ Database directory missing\n";
    echo "   Creating database directory...\n";
    mkdir($dbDir, 0755, true);
}

// Check database file
echo "\n4. Database File:\n";
$dbFile = $dbDir . '/chatty.db';
if (file_exists($dbFile)) {
    echo "   ✓ Database file exists\n";
} else {
    echo "   ✗ Database file missing\n";
    echo "   Please run: php clean_and_setup_database.php\n";
}

// Check if we can connect to database
echo "\n5. Database Connection:\n";
try {
    require_once 'backend/db.php';
    echo "   ✓ Database connection successful\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Check if users exist
echo "\n6. Sample Data:\n";
try {
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $userCount = $row['count'];
    
    if ($userCount > 0) {
        echo "   ✓ Found $userCount users in database\n";
    } else {
        echo "   ✗ No users found in database\n";
        echo "   Please run: php clean_and_setup_database.php\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking users: " . $e->getMessage() . "\n";
}

// Check file permissions
echo "\n7. File Permissions:\n";
$files = [
    'backend/db.php',
    'backend/users.php',
    'backend/conversations.php',
    'backend/send_messages.php',
    'frontend/index.html',
    'frontend/css/style.css',
    'frontend/js/app.js'
];

$allFilesExist = true;
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✓ $file exists\n";
    } else {
        echo "   ✗ $file missing\n";
        $allFilesExist = false;
    }
}

echo "\n=== Setup Summary ===\n";
if ($allFilesExist && $userCount > 0) {
    echo "✅ Setup looks good! You can now access the application at:\n";
    echo "   http://localhost/chatty/frontend/\n\n";
    echo "Make sure XAMPP Apache is running.\n";
} else {
    echo "❌ Some issues found. Please fix them before running the application.\n";
    echo "If you haven't set up the database yet, run:\n";
    echo "   php clean_and_setup_database.php\n";
}

if (isset($db)) {
    $db->close();
}
?> 