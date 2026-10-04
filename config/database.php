<?php
/** XAMPP defaults are root with an empty password. Override with environment variables if needed. */
function db(): mysqli
{
    static $connection = null;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $host = getenv('CAMPUSBITE_DB_HOST') ?: '127.0.0.1';
    $user = getenv('CAMPUSBITE_DB_USER') ?: 'root';
    $password = getenv('CAMPUSBITE_DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }
    $database = getenv('CAMPUSBITE_DB_NAME') ?: 'campusbite';
    $port = (int) (getenv('CAMPUSBITE_DB_PORT') ?: 3306);

    $connection = new mysqli($host, $user, $password, $database, $port);
    $connection->set_charset('utf8mb4');
    $connection->query("SET time_zone = '+08:00'");
    return $connection;
}
