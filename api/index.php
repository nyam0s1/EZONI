<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$route = $_GET['__ezoni_route'] ?? '';
unset($_GET['__ezoni_route']);

if (!is_string($route)) {
    $route = '';
}

$route = '/' . ltrim(rawurldecode($route), '/');
$route = preg_replace('#/+#', '/', $route) ?: '/';

$publicAssets = [
    'CSS/style.css' => ['type' => 'text/css; charset=utf-8', 'cache' => 'public, max-age=0, must-revalidate'],
    'images/logo.png' => ['type' => 'image/png', 'cache' => 'public, max-age=86400'],
    'manifest.json' => ['type' => 'application/manifest+json; charset=utf-8', 'cache' => 'public, max-age=0, must-revalidate'],
];

$assetPath = ltrim($route, '/');
if (isset($publicAssets[$assetPath])) {
    $assetFile = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $assetPath);
    if (!is_file($assetFile)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $publicAssets[$assetPath]['type']);
    header('Cache-Control: ' . $publicAssets[$assetPath]['cache']);
    readfile($assetFile);
    exit;
}

if ($route === '/') {
    $route = '/index.php';
}

$entrypoints = [
    '/index.php' => 'index.php',
    '/register.php' => 'register.php',
    '/auth_handler.php' => 'auth_handler.php',
    '/admin_dashboard.php' => 'admin_dashboard.php',
    '/user_dashboard.php' => 'user_dashboard.php',
    '/logout.php' => 'logout.php',
];

if (!isset($entrypoints[$route])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

require_once $projectRoot . '/includes/db.php';
require_once $projectRoot . '/includes/PdoSessionHandler.php';

$forwardedProtocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$isSecureRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (is_string($forwardedProtocol) && strpos($forwardedProtocol, 'https') === 0)
    || getenv('VERCEL') === '1';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isSecureRequest,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_set_save_handler(new EzoniPdoSessionHandler($pdo), true);

chdir($projectRoot);
require $projectRoot . '/' . $entrypoints[$route];
?>
