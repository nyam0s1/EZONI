<?php
// Production Database Connection
$host = 'localhost';
$dbname = 'ezoni_farm';
$username = 'root';      // CHANGE THIS on live server
$password = '';          // CHANGE THIS on live server

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set Error Mode to Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable emulation of prepared statements (Better Security)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // In production, NEVER echo the actual error to the screen!
    // Log it to a file instead.
    error_log($e->getMessage()); 
    die("Database connection failed. Please try again later.");
}
?>