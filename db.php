<?php
// db.php - File cấu hình kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "talent_pool"; // << THAY TÊN DATABASE CỦA BẠN

// Bật báo lỗi để dễ dàng gỡ lỗi nếu có vấn đề
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>