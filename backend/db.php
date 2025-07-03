<?php
header("Content-Type: application/json");

$db = new SQLite3(__DIR__ . '/../database/chatty.db');

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

?>