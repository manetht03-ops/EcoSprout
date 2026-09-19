<?php
// db.php - centralized DB connection
// Edit these settings if your MySQL root has a password
$DB_HOST = '127.0.0.1';
$DB_USER = 'root'; 
$DB_PASS = 'mysql';
$DB_NAME = 'ecosprout_db';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    // In a production app, do not reveal sensitive errors. Keep it simple here for debugging.
    die('Database connection failed: ' . $mysqli->connect_error);
}
// Use $mysqli in other scripts by include 'db.php';
?>