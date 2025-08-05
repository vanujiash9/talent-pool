<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include "../config.php";
$rs = $conn->query("SELECT aa.account_id, a.full_name, aa.username, aa.role, aa.account_status FROM applicant_accounts aa 
LEFT JOIN applicants a ON a.applicant_id=aa.applicant_id ORDER BY account_id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Tài khoản ứng viên</title>
    <style>
        body { background:#f3f6fd; font-family:Arial; }
        .adbox { max-width:750px; margin:60px auto;background:#fff;box-shadow:0 3px 20px #b5bbef25; padding:32px; border-radius:9px;}
        h2 { color:#1976d2;text-align:center; margin-bottom:18px;}
        table { width:100%;border-collapse:collapse;background:#f9fbff;}
        th,td { border-bottom:1px solid #dde3ee;padding:9px 6px; text-align:center;}
        th { background:#e3eefe;}
        tr:hover { background:#e9f5fe;}
        .status { font-size:12px;padding:2px 8px;border-radius:4px;}
        .active {background:#e8f7ee; color:#219150;}
        .suspended {background:#fbeaea; color:#E30417;}
        .admin {background:#e9f5fe; color:#1976d2;}
        .user {background:#eee; color:#727b87;}
        a {color:#2962ff;text-decoration:none;}
        a:hover { text-decoration:underline;}
    </style>
</head>
<body>
<div class="adbox">
    <h2>Danh sách tài khoản ứng viên</h2>
    <table>
        <tr><th>ID</th><th>Họ tên</th><th>Username</th><th>Vai trò</th><th>Trạng thái</th></tr>
        <?php while($row=$rs->fetch_assoc()): ?>
        <tr>
            <td><?= $row['account_id'] ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><span class="<?= $row['role'] ?> status"><?= $row['role'] ?></span></td>
            <td>
                <span class="status <?= $row['account_status'] ?>">
                    <?= $row['account_status']=='active' ? "Đang hoạt động" : "Bị khóa" ?>
                </span>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <div style="margin-top:26px;text-align:right">
        <a href="admin_dashboard.php">← Quay về trang quản trị</a>
    </div>
</div>
</body>
</html>
