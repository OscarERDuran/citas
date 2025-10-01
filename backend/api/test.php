<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'API is working',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
?>
