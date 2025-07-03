<?php
require_once 'db.php'; // Ensure this path is correct

// Fetch all users
$result = $db->query("SELECT id, name, status, last_seen, profile_picture FROM users");

if ($result === false) {
    die("Query failed: " . $db->lastErrorMsg());
}

// Check if any rows were returned
$users = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode($users);
?>