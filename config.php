<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'jualkode_mng_finance';
const DB_USER = 'jualkode_dikaputrarahmawan009';
const DB_PASS = 'G@m@techn0';

/**
 * Returns a shared PDO connection instance.
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
