<?php
$host ="sql109.infinityfree.com";
$user = "if0_42109132";
$password = "************";
$database = "if0_42109132_sisonke_marketplace";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>
