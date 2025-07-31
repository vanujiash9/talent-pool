<?php
$db_server = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "talent_pool";

$conn = new mysqli($db_server, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}
// Không có thẻ đóng ?>