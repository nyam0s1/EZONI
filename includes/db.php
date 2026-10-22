<?php
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$port = getenv('DB_PORT');

if ($port === false || $port === '') {
    $port = '3306';
}

$missingSettings = [];
foreach ([
    'DB_HOST' => $host,
    'DB_NAME' => $dbname,
    'DB_USER' => $username,
    'DB_PASSWORD' => $password,
] as $setting => $value) {
    if ($value === false) {
        $missingSettings[] = $setting;
    }
}

if ($missingSettings !== []) {
    error_log('Database configuration is missing: ' . implode(', ', $missingSettings));
    die('Database connection failed. Please try again later.');
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);

    date_default_timezone_set('Africa/Nairobi');
    $pdo->exec("SET time_zone = '+03:00'");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Database connection failed. Please try again later.');
}
?>
