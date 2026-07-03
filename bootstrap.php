<?php

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('This application requires PHP 8.0 or higher. Current version: ' . PHP_VERSION);
}

$requiredExtensions = ['pdo_mysql', 'curl', 'json'];
$missing = array_filter($requiredExtensions, fn(string $ext): bool => !extension_loaded($ext));

if ($missing !== []) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Missing required PHP extensions: ' . implode(', ', $missing));
}
