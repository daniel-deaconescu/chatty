<?php
require_once 'db.php';

try {
    $stmt = $db->prepare("
        SELECT id, name, status, last_seen, profile_picture_url as profile_picture
        FROM users 
        WHERE id != 9  -- Exclude the 'You' user
        ORDER BY 
            CASE WHEN status = 'online' THEN 0 ELSE 1 END,
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