<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$expectedUser = $_GET['user'] ?? 'testuser';
$expectedPass = $_GET['pass'] ?? 'testpass';

$user = $_SERVER['PHP_AUTH_USER'] ?? null;
$pass = $_SERVER['PHP_AUTH_PW'] ?? null;

if ($user !== $expectedUser || $pass !== $expectedPass) {
    \header('WWW-Authenticate: Basic realm="Test"');
    \http_response_code(401);
    echo 'Unauthorized';
    exit;
}

echo 'Authenticated';
