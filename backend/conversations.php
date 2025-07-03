<?php

require_once 'db.php';

// Fetching conversations from db

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    jsonResponse(['error' => 'User ID required'], 400);
}

// Getting conversation for this user
$stmt = $db->prepare("SELECT * FROM conversations WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$conversation = $result->fetchArray(SQLITE3_ASSOC);

if (!$conversation) {
    jsonResponse(['error' => 'Conversation not found'], 404);
}

// Get messages for this conversation
$stmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = :conversation_id");
$stmt->bindValue(':conversation_id', $conversation['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$messages = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $messages[] = $row;
}

jsonResponse([
    'conversation' => $conversation,
    'messages' => $messages
]);

?>