<?php
session_start();
if (!isset($_SESSION["is_logged_in"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Trang chính</title>
</head>
<body>
    <h2>Chào mừng, bạn đã đăng nhập thành công!</h2>
    <p>Xin chào: <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b></p>
    <p><a href="logout.php">Đăng xuất</a></p>
</body>
</html>
