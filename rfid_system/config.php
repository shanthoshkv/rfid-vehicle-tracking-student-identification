<?php
session_start();

$hostname = "localhost";
$username = "root";
$password = "";
$database = "rfid_system";

$conn = mysqli_connect($hostname, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Base URL
define('BASE_URL', 'http://localhost/rfid_system/');
?>
