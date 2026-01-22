<?php
/**
 * Study is Funny - Local Router for PHP Built-in Server
 * Mimics Hostinger/Apache .htaccess behavior
 */

// Basic setup
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$path = rawurldecode($path);

// Security: Prevent access to sensitive files
$sensitiveFiles = [
    '/config/config.php',
    '/HOSTINGER_CONFIG.php',
    '/.env',
    '/database.yml',
    '/.git',
    '/run.ps1',
    '/router.php'
];

foreach ($sensitiveFiles as $sensitive) {
    if (strpos($path, $sensitive) !== false) {
        header("HTTP/1.1 403 Forbidden");
        echo "403 Forbidden - Sensitive file access denied.";
        exit;
    }
}

// Normalize path for Windows
$fullPath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

// 1. Handle PHP files (Force execution to avoid raw code issues)
if (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
    $_SERVER['SCRIPT_FILENAME'] = $fullPath;
    $_SERVER['SCRIPT_NAME'] = $path;
    $_SERVER['PHP_SELF'] = $path;
    include $fullPath;
    return true;
}

// 2. If it's a directory, look for index.php first, then index.html
if (is_dir($fullPath)) {
    $fullPath = rtrim($fullPath, DIRECTORY_SEPARATOR);
    if (is_file($fullPath . DIRECTORY_SEPARATOR . 'index.php')) {
        return false;
    }
    if (is_file($fullPath . DIRECTORY_SEPARATOR . 'index.html')) {
        // We serve HTML manually to preserve the clean URL
        header('Content-Type: text/html');
        readfile($fullPath . DIRECTORY_SEPARATOR . 'index.html');
        return true;
    }
}

// 3. Handle Clean URLs (e.g., /login -> /login.html)
if (!pathinfo($path, PATHINFO_EXTENSION)) {
    if (is_file($fullPath . '.html')) {
        header('Content-Type: text/html');
        readfile($fullPath . '.html');
        return true;
    }
}

// 4. For everything else (CSS, JS, Images, etc.), let the server handle it
return false;
