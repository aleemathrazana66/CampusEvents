<?php
/**
 * CampusEvents - Database Connection & Initialization
 * 
 * Provides a reliable PDO connection singleton with auto-installation
 * fallback for seamless out-of-the-box local development.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'campusevents');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If database doesn't exist, attempt auto-creation
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Execute SQL schema file
            $sqlFile = dirname(__DIR__) . '/database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }
        } catch (PDOException $inner) {
            die("Database connection failed: " . htmlspecialchars($inner->getMessage()));
        }
    }

    return $pdo;
}
