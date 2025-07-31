<?php
ob_start();
session_start();
include "config.php";

$msg = "";
$username = "";
$role_selected = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $role = $_POST["role"] ?? '';
    $role_selected = $role;

    if (empty($username) || empty($password) || empty($role)) {
        $msg = "Vui lòng điền đầy đủ thông tin.";
    } else {
        $sql = "";
        if ($role == 'applicant') {
            $sql = "SELECT applicant_id, username, password FROM applicant_accounts WHERE username = ? LIMIT 1";
        } elseif ($role == 'company') {
            $sql = "SELECT account_id, company_id, username, password, role FROM company_accounts WHERE username = ? LIMIT 1";
        } else {
            $msg = "Vai trò không hợp lệ.";
        }

        if (!empty($sql)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $r = $stmt->get_result();
                
                if ($r->num_rows == 1) {
                    $user = $r->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        $_SESSION["is_logged_in"] = true;
                        $_SESSION["username"] = $username;
                        $_SESSION["role"] = $role;

                        if ($role == 'applicant') {
                            $_SESSION["applicant_id"] = $user['applicant_id'];
                            $conn->query("UPDATE applicant_accounts SET last_login = NOW() WHERE applicant_id = ".$user['applicant_id']);
                            header("Location: user_dashboard.php");
                        } elseif ($role == 'company') {
                            $_SESSION["company_id"] = $user['company_id'];
                            $_SESSION["account_id"] = $user['account_id'];
                            $conn->query("UPDATE company_accounts SET last_login = NOW() WHERE account_id = ".$user['account_id']);
                            header("Location: company/company_profile.php");
                        }
                        
                        exit; 
                    } else {
                        $msg = "Tên đăng nhập hoặc mật khẩu sai!";
                    }
                } else {
                    $msg = "Tên đăng nhập hoặc mật khẩu sai!";
                }
                $stmt->close();
            } else {
                $msg = "Lỗi truy vấn cơ sở dữ liệu: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <style>
        body { background: #f3f6fb; font-family: "Segoe UI", Arial, sans-serif; }
        .login-box { width: 370px; margin: 60px auto; background: #fff; border-radius: 12px; box-shadow: 0 6px 18px rgba(51,51,51,0.10); padding: 38px 32px 28px 32px; }
        .login-box h2 { text-align: center; color: #1867c0; margin-bottom: 18px; letter-spacing: 0.5px; }
        .form-group { margin-bottom: 19px; }
        .form-group label { display: block; font-size: 15px; margin-bottom: 5px; color: #454545; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1.2px solid #98acee; border-radius: 6px; font-size: 15px; background: #f7f9fc; transition: border 0.2s; }
        .form-group input:focus, .form-group select:focus { border-color: #2979ff; background: #fff; outline: none; }
        .submit-btn { width: 100%; padding: 11px 0; background: linear-gradient(90deg, #1976d2, #42a5f5); color: #fff; font-weight: 500; border: none; border-radius: 6px; font-size: 16px; box-shadow: 0 2px 8px rgba(66,165,245,0.09); cursor: pointer; transition: background 0.3s; }
        .submit-btn:hover { background: linear-gradient(90deg, #1565c0, #2196f3); }
        .error { color: #e53935; text-align: center; margin-bottom: 14px; }
        .toggle-signup { margin-top: 18px; text-align: center; }
        .toggle-signup a { color: #1565c0; text-decoration: none; font-weight: 500; }
        .toggle-signup a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Đăng nhập tài khoản</h2>
        <?php if($msg) echo "<div class='error'>$msg</div>"; ?>
        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="role">Vai trò:</label>
                <select name="role" id="role" required>
                    <option value="">Chọn vai trò</option>
                    <option value="applicant" <?= $role_selected == 'applicant' ? 'selected' : '' ?>>Ứng viên</option>
                    <option value="company" <?= $role_selected == 'company' ? 'selected' : '' ?>>Doanh nghiệp</option>
                </select>
            </div>
            <div class="form-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" name="username" id="username" required autocomplete="username" value="<?= htmlspecialchars($username) ?>">
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" name="password" id="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="submit-btn">Đăng nhập</button>
        </form>
        <div class="toggle-signup">
            <span>Chưa có tài khoản? </span><a href="register.php">Đăng ký</a>
        </div>
    </div>
</body>
</html>