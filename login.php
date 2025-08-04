<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include "config.php";

if (isset($_SESSION["is_logged_in"]) && $_SESSION["is_logged_in"] === true) {
    if ($_SESSION["role"] === 'user') {
        header("Location: user_dashboard.php");
        exit;
    } elseif ($_SESSION["role"] === 'company') {
        header("Location: company/company_profile.php");
        exit;
    }
}

$error_msg = "";
$username_val = "";
$role_val = "";
$registration_success = isset($_GET['registration']) && $_GET['registration'] == 'success';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $role = $_POST["role"] ?? '';

    $username_val = $username;
    $role_val = $role;

    if (empty($username) || empty($password) || empty($role)) {
        $error_msg = "Vui lòng điền đầy đủ thông tin.";
    } else {
        try {
            $stmt = null;
            if ($role == 'applicant') {
                $stmt = $conn->prepare("SELECT applicant_id, username, password FROM applicant_accounts WHERE username = ? LIMIT 1");
            } elseif ($role == 'company') {
                $stmt = $conn->prepare("SELECT account_id, company_id, username, password FROM company_accounts WHERE username = ? LIMIT 1");
            } else {
                $error_msg = "Vai trò không hợp lệ.";
            }

            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $user_data = $result->fetch_assoc();
                    if ($password === $user_data['password']) {
                        $_SESSION["is_logged_in"] = true;
                        $_SESSION["username"] = $user_data['username'];
                        $_SESSION["role"] = $role == 'applicant' ? 'user' : 'company';

                        if ($role == 'applicant') {
                            $_SESSION["applicant_id"] = $user_data['applicant_id'];
                            $update_stmt = $conn->prepare("UPDATE applicant_accounts SET last_login = NOW() WHERE applicant_id = ?");
                            $update_stmt->bind_param("i", $user_data['applicant_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                            header("Location: user_dashboard.php");
                            exit;
                        } elseif ($role == 'company') {
                            $_SESSION["company_id"] = $user_data['company_id'];
                            $_SESSION["account_id"] = $user_data['account_id'];
                            $update_stmt = $conn->prepare("UPDATE company_accounts SET last_login = NOW() WHERE account_id = ?");
                            $update_stmt->bind_param("i", $user_data['account_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                            header("Location: company/company_profile.php");
                            exit;
                        }
                    } else {
                        $error_msg = "Tên đăng nhập hoặc mật khẩu không chính xác.";
                    }
                } else {
                    $error_msg = "Tên đăng nhập hoặc mật khẩu không chính xác.";
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $error_msg = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .input-error { border-color: #ef4444; }
        .error-message { color: #ef4444; font-size: 0.875rem; }
        .password-container { position: relative; }
        .toggle-password {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">Đăng nhập</h2>

            <?php if ($registration_success): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                    Đăng ký thành công! Vui lòng đăng nhập.
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-4">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Vai trò</label>
                    <select name="role" id="role" required class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="" disabled <?= !$role_val ? 'selected' : '' ?>>Chọn vai trò</option>
                        <option value="applicant" <?= $role_val == 'applicant' ? 'selected' : '' ?>>Ứng viên</option>
                        <option value="company" <?= $role_val == 'company' ? 'selected' : '' ?>>Doanh nghiệp</option>
                    </select>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Tên đăng nhập</label>
                    <input id="username" name="username" type="text" value="<?= htmlspecialchars($username_val) ?>" required class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="password-container">
                    <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                    <input id="password" name="password" type="password" required class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <span class="toggle-password" onclick="togglePasswordVisibility('password')"><i class="fa-solid fa-eye-slash"></i></span>
                </div>

                <button type="submit" class="w-full p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Đăng nhập</button>
            </form>

            <p class="mt-4 text-center text-sm text-gray-600">
                Chưa có tài khoản? <a href="register.php" class="text-blue-600 hover:underline">Đăng ký</a>
            </p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>
</html>