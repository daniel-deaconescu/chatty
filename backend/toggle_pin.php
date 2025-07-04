<?php
require_once 'db.php';

$conversation_id = $_POST['conversation_id'] ?? null;
if (!$conversation_id) {
    jsonResponse(['error' => 'Conversation ID required'], 400);
}

try {
    $stmt = $db->prepare("
        UPDATE conversations
        SET is_pinned = NOT is_pinned
        WHERE id = :conversation_id
        RETURNING is_pinned
    ");
    $stmt->bindValue(':conversation_id', $conversation_id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    if (!$result) {
        throw new Exception($db->lastErrorMsg());
    }
    
    $updated = $result->fetchArray(SQLITE3_ASSOC);
    jsonResponse([
        'success' => true,
        'is_pinned' => (bool)$updated['is_pinned']
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'error' => 'Failed to toggle pin status',
        'details' => $e->getMessage()
    ], 500);
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
}
?>