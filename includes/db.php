<?php
require_once __DIR__ . '/database_connection.php';

try {
    $pdo = ezoniCreateDatabaseConnection();
} catch (Throwable $error) {
    error_log('EZONI database connection failed: ' . $error->getMessage());
    http_response_code(503);
    header('Cache-Control: no-store');
    die('Database connection failed. Please try again later.');
}
