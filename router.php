<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

$routes = [
    ''       => '/index.php',
    'login'  => '/auth/login.php',
    'register' => '/auth/register.php',
];

if (isset($routes[$uri])) {
    require __DIR__ . $routes[$uri];
    exit;
}

$static = __DIR__ . '/' . $uri;
if (file_exists($static) && is_file($static)) {
    return false;
}

http_response_code(404);
echo "404 - Page non trouvée";