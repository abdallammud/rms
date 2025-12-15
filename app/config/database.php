<?php
/**
 * Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rms_db');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

/**
 * Execute query and return result
 */
function db_query($sql) {
    global $conn;
    return mysqli_query($conn, $sql);
}

/**
 * Execute query and return single row
 */
function db_query_row($sql) {
    $result = db_query($sql);
    return $result ? mysqli_fetch_assoc($result) : null;
}

/**
 * Execute query and return all rows
 */
function db_query_all($sql) {
    $result = db_query($sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Get last insert ID
 */
function db_insert_id() {
    global $conn;
    return mysqli_insert_id($conn);
}

/**
 * Escape string for SQL
 */
function db_escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}
