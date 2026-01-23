<?php
// Production Database Connection
$host = "sql109.infinityfree.com";
$dbname = "if0_40917862_ezoni";
$username = "if0_40917862";
$password = "nyamosi2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    //Timezone Setting
    date_default_timezone_set('Africa/Nairobi'); // Fixes PHP time
    $pdo->exec("SET time_zone = '+03:00'");       // Fixes MySQL Database time
    
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