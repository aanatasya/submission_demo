<?php
session_start();
$conn = new mysqli("localhost", "root", "", "submission_system");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>