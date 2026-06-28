<?php

$expectedUser = 'testuser';
$expectedPass = 'testpass';

$user = $_SERVER['PHP_AUTH_USER'] ?? null;
$pass = $_SERVER['PHP_AUTH_PW'] ?? null;

if ($user !== $expectedUser || $pass !== $expectedPass) {
    \header('WWW-Authenticate: Basic realm="Test"');
    \http_response_code(401);
    echo 'Unauthorized';
    exit;
}

echo 'Authenticated';
