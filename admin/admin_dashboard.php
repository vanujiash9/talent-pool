<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include "../config.php";

// Thống kê tổng
$user_count = $conn->query("SELECT COUNT(*) FROM applicant_accounts")->fetch_row()[0];
$company_count = $conn->query("SELECT COUNT(*) FROM company_accounts")->fetch_row()[0];
$job_count = $conn->query("SELECT COUNT(*) FROM jobs")->fetch_row()[0];
$pending_count = $conn->query("SELECT COUNT(*) FROM applicants WHERE applicant_status='pending'")->fetch_row()[0];

// 5 ứng viên mới nhất
$new_users = $conn->query(
    "SELECT aa.username, a.full_name, aa.created_at
     FROM applicant_accounts aa
     LEFT JOIN applicants a ON aa.applicant_id = a.applicant_id
     ORDER BY aa.created_at DESC LIMIT 5"
);

// 5 doanh nghiệp mới nhất + company_name
$new_companies = $conn->query(
    "SELECT ca.username, ca.created_at, c.company_name
     FROM company_accounts ca
     LEFT JOIN companies c ON ca.company_id = c.company_id
     ORDER BY ca.created_at DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Talent Pool</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,500,600&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
body {
    margin: 0;
    font-family: 'Inter', Arial, sans-serif;
    background: #f8fbff;
}
.container {
    display: flex;
}
.sidebar {
    width: 230px;
    background: #1976d2;
    color: #fff;
    min-height: 100vh;
    box-shadow: 2px 0 14px #0001;
    display: flex;
    flex-direction: column;
}
.sidebar h1 {
    font-size: 22px;
    margin: 36px 0 24px 34px;
    letter-spacing: 1.1px;
    font-weight: bold;
}
.sidebar ul {
    list-style: none;
    padding: 0;
}
.sidebar a {
    display: flex;
    align-items: center;
    padding: 13px 40px 13px 34px;
    color: #fff;
    text-decoration: none;
    font-size: 17px;
    border-radius: 0 18px 18px 0;
    margin: 0 0 6px 0;
    transition: .15s;
    font-weight: 500;
}
.sidebar a i {
    margin-right: 13px;
    font-size: 16.5px;
    width: 19px;
}
.sidebar a.active,
.sidebar a:hover {
    background: #f2f6fb;
    color: #1976d2;
    font-weight: 600;
}
.sidebar .logout {
    margin: 38px 0 25px 0;
            color: #E30417;
    font-weight: 600;
}
.main {
    flex: 1;
    padding: 43px 45px 20px 45px;
}
.panel-row {
    display: flex;
    gap: 32px;
    margin-bottom: 37px;
}
.panel {
    background: #fff;
    border-radius: 14px;
    flex: 1;
    box-shadow: 0 3px 11px #1976d21a;
    padding: 30px 24px 24px 28px;
    min-width: 170px;
    display: flex;
    align-items: center;
    gap: 19px;
}
.panel i {
    font-size: 37px;
    background: linear-gradient(79deg,#36d1c4 60%,#1976d2 120%);
    color: #fff;
    padding: 15px;
    border-radius: 99px;
    min-width: 37px;
    min-height: 37px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.panel .panel-content {
    flex: 1;
}
.panel .panel-count {
    font-weight: 700;
    font-size: 29px;
    margin-bottom: 4px;
    color: #263a53;
}
.panel .panel-label {
    font-size: 15.5px;
    color: #39a3d7;
}
h2 {
    margin: 18px 0 15px 0;
    font-size: 24px;
    font-weight: 500;
}
.table-box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px #144f9f0f;
    padding: 23px 22px;
    margin-bottom: 32px;
    overflow-x: auto;
}
.table-box table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15.2px;
    min-width: 400px;
}
.table-box th, .table-box td {
    padding: 7px 7px;
    text-align: left;
    border-bottom: 1px solid #f1f5fd;
    vertical-align: middle;
}
.table-box th {
    color: #1976d2;
    background: #f2f8fd;
    font-weight: 600;
}
.table-box tr:last-child td {
    border-bottom: none;
}
.avatar {
    display: inline-block;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #e2e8f0;
    text-align: center;
    color: #1976d2;
    font-size: 16.5px;
    font-weight: 600;
    line-height: 2.3;
    vertical-align: middle;
}
.welcome {
    font-weight: 600;
    color: #264e7d;
    margin-bottom: 12px;
    font-size: 18px;
    letter-spacing: 0.1px;
}
@media (max-width: 900px) {
    .panel-row {
        flex-direction: column;
    }
    .main {
        padding: 14px 3vw;
    }
    .sidebar {
        width: 60px;
        min-width: 60px;
    }
    .sidebar a span, .sidebar h1 {
        display: none;
    }
    .sidebar a i {
        margin: 0;
    }
}
@media (max-width: 600px) {
    .main {
        padding: 12px 2vw;
    }
    .table-box {
        padding: 10px 3px;
        font-size: 13.2px;
    }
    .sidebar {
        width: 48px;
        min-width: 48px;
    }
}
::-webkit-scrollbar {
    width: 8px;
    background: #eee;
    border-radius: 8px;
}
::-webkit-scrollbar-thumb {
    background: #bad6f8;
    border-radius: 8px;
}
    </style>
</head>
<body>
<div class="container">
    <nav class="sidebar">
        <h1>Talent Pool</h1>
        <ul>
            <li><a class="active" href="admin_dashboard.php"><i class="fa fa-gauge"></i><span> Dashboard</span></a></li>
            <li><a href="admin_users.php"><i class="fa fa-users"></i><span> Ứng viên </span></a></li>
            <li><a href="admin_companies.php"><i class="fa fa-building"></i><span> Doanh nghiệp </span></a></li>
            <li><a href="admin_jobs.php"><i class="fa fa-briefcase"></i><span> Tuyển dụng </span></a></li>
        </ul>
        <div style="flex:1"></div>
        <a href="../logout.php" class="logout"><i class="fa fa-sign-out-alt"></i><span> Đăng xuất</span></a>
    </nav>
    <main class="main">
        <div class="welcome">Chào mừng, <span style="color:#1976d2"><?= htmlspecialchars($_SESSION['username']) ?></span>! Bạn đang ở trang quản trị hệ thống.</div>
        <div class="panel-row">
            <div class="panel">
                <i class="fa fa-users"></i>
                <div class="panel-content">
                    <div class="panel-count"><?= $user_count ?></div>
                    <div class="panel-label">Tài khoản ứng viên</div>
                </div>
            </div>
            <div class="panel">
                <i class="fa fa-building"></i>
                <div class="panel-content">
                    <div class="panel-count"><?= $company_count ?></div>
                    <div class="panel-label">Tài khoản doanh nghiệp</div>
                </div>
            </div>
            <div class="panel">
                <i class="fa fa-briefcase"></i>
                <div class="panel-content">
                    <div class="panel-count"><?= $job_count ?></div>
                    <div class="panel-label">Tin tuyển dụng</div>
                </div>
            </div>
            <div class="panel">
                <i class="fa fa-user-clock"></i>
                <div class="panel-content">
                    <div class="panel-count"><?= $pending_count ?></div>
                    <div class="panel-label">Ứng viên chờ duyệt</div>
                </div>
            </div>
        </div>
        <div class="table-box">
            <h3 style="margin-bottom:8px;color:#303b54;">Ứng viên đăng ký mới nhất</h3>
            <table>
                <tr><th>Avatar</th><th>Username</th><th>Họ tên</th><th>Ngày tạo</th></tr>
                <?php while($row = $new_users->fetch_assoc()): ?>
                <tr>
                    <td>
                        <span class="avatar"><?= strtoupper(substr($row['username'],0,1)); ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?: "<span style='color:#bcc'>Chưa có</span>" ?></td>
                    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <div class="table-box">
            <h3 style="margin-bottom:8px;color:#303b54;">Doanh nghiệp mới tạo</h3>
            <table>
                <tr><th>Avatar</th><th>Username</th><th>Tên công ty</th><th>Ngày tạo</th></tr>
                <?php while($row = $new_companies->fetch_assoc()): ?>
                <tr>
                    <td>
                        <span class="avatar"><?= strtoupper(substr($row['username'],0,1)); ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['company_name']) ?: "<span style='color:#bcc'>Chưa có</span>" ?></td>
                    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </main>
</div>
</body>
</html>
