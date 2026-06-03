<?php

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

$file = __DIR__ . $uri;
if (str_ends_with($uri, '.php') && file_exists($file)) {
    require $file;
    return true;
}

http_response_code(404);
echo '404 Not Found';
return true;
