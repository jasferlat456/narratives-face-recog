<?php
// Adjust to your environment (XAMPP defaults shown)
const DB_HOST = 'localhost';
const DB_NAME = 'narrativestudios';
const DB_USER = 'root';
const DB_PASS = '';

// URL path where the project lives (auto-detected, e.g. "/narrativestudios"; "" if in web root)
$__docRoot = rtrim(str_replace(chr(92), '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$__root    = str_replace(chr(92), '/', (string)realpath(__DIR__ . '/..'));
define('BASE_URL', rtrim(str_replace($__docRoot, '', $__root), '/'));

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
