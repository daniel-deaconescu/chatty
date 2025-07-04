<?php
header("Content-Type: application/json");

// Database configuration
$db_path = __DIR__ . '/../database/chatty.db';

try {
    $db = new SQLite3($db_path);
    $db->busyTimeout(5000); // 5 second timeout
    $db->exec('PRAGMA foreign_keys = ON;');
    $db->exec('PRAGMA journal_mode = WAL;'); // Better concurrency
} catch (Exception $e) {
    die(json_encode([
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ]));
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
?>