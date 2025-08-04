<?php
include "config.php";

$errors = [];
$role = isset($_GET['role']) ? $_GET['role'] : null;

function getPostValue($key) {
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : '';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? null;

    if ($role == 'applicant') {
        $fullname = trim($_POST["fullname"] ?? '');
        $email = trim($_POST["email"] ?? '');
        $username = trim($_POST["username"] ?? '');
        $password = $_POST["password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';

        if (empty($fullname)) $errors['fullname'] = 'Họ tên không được để trống.';
        if (empty($email)) {
            $errors['email'] = 'Email không được để trống.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        }
        if (empty($username)) $errors['username'] = 'Tên đăng nhập không được để trống.';
        if (empty($password)) {
            $errors['password'] = 'Mật khẩu không được để trống.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Mật khẩu phải ít nhất 8 ký tự.';
        }
        if ($password !== $confirm_password) $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT 1 FROM applicant_accounts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors['username'] = 'Tên đăng nhập này đã được sử dụng.';
            $stmt->close();

            $stmt = $conn->prepare("SELECT 1 FROM applicants WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors['email'] = 'Email này đã được sử dụng.';
            $stmt->close();
        }

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO applicants (full_name, email, applicant_status) VALUES (?, ?, 'active')");
                $stmt->bind_param("ss", $fullname, $email);
                $stmt->execute();
                $new_applicant_id = $stmt->insert_id;
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO applicant_accounts (applicant_id, username, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param("iss", $new_applicant_id, $username, $password);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                header("Location: login.php?registration=success");
                exit;
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $errors['db'] = "Lỗi hệ thống, vui lòng thử lại.";
            }
        }
    } elseif ($role == 'company') {
        $company_name = trim($_POST["company_name"] ?? '');
        $email = trim($_POST["email"] ?? '');
        $username = trim($_POST["username"] ?? '');
        $password = $_POST["password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';

        if (empty($company_name)) $errors['company_name'] = 'Tên công ty không được để trống.';
        if (empty($email)) {
            $errors['email'] = 'Email không được để trống.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        }
        if (empty($username)) $errors['username'] = 'Tên đăng nhập không được để trống.';
        if (empty($password)) {
            $errors['password'] = 'Mật khẩu không được để trống.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Mật khẩu phải ít nhất 8 ký tự.';
        }
        if ($password !== $confirm_password) $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT 1 FROM company_accounts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors['username'] = 'Tên đăng nhập này đã được sử dụng.';
            $stmt->close();

            $stmt = $conn->prepare("SELECT 1 FROM companies WHERE company_name = ?");
            $stmt->bind_param("s", $company_name);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors['company_name'] = 'Tên công ty này đã được sử dụng.';
            $stmt->close();
        }

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO companies (company_name) VALUES (?)");
                $stmt->bind_param("s", $company_name);
                $stmt->execute();
                $new_company_id = $stmt->insert_id;
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO company_accounts (company_id, username, password, role) VALUES (?, ?, ?, 'company_user')");
                $stmt->bind_param("iss", $new_company_id, $username, $password);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO company_contact (company_id, contact_email) VALUES (?, ?)");
                $stmt->bind_param("is", $new_company_id, $email);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                header("Location: login.php?registration=success");
                exit;
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $errors['db'] = "Lỗi hệ thống, vui lòng thử lại.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
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
            <?php if (!$role): ?>
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">Đăng ký tài khoản</h2>
                <p class="text-center text-gray-600 mb-6">Bạn là ứng viên hay doanh nghiệp?</p>
                <div class="space-y-3">
                    <a href="?role=applicant" class="block w-full p-2 bg-blue-600 text-white rounded-lg text-center hover:bg-blue-700">Tôi là Ứng viên</a>
                    <a href="?role=company" class="block w-full p-2 bg-blue-100 text-blue-600 rounded-lg text-center hover:bg-blue-200">Tôi là Doanh nghiệp</a>
                </div>

            <?php elseif ($role == 'applicant'): ?>
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">Đăng ký Ứng viên</h2>
                <p class="text-center text-gray-600 mb-6">Tạo tài khoản để tìm việc làm.</p>

                <?php if (isset($errors['db'])): ?>
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm"><?= $errors['db'] ?></div>
                <?php endif; ?>

                <form method="POST" action="?role=applicant" class="space-y-4">
                    <input type="hidden" name="role" value="applicant">
                    <div>
                        <label for="fullname" class="block text-sm font-medium text-gray-700">Họ và tên</label>
                        <input id="fullname" name="fullname" type="text" value="<?= getPostValue('fullname') ?>" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['fullname']) ? 'input-error' : 'border-gray-300' ?>">
                        <?php if (isset($errors['fullname'])): ?><p class="error-message mt-1"><?= $errors['fullname'] ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="<?= getPostValue('email') ?>" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['email']) ? 'input-error' : 'border-gray-300' ?>">
                        <?php if (isset($errors['email'])): ?><p class="error-message mt-1"><?= $errors['email'] ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Tên đăng nhập</label>
                        <input id="username" name="username" type="text" value="<?= getPostValue('username') ?>" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['username']) ? 'input-error' : 'border-gray-300' ?>">
                        <?php if (isset($errors['username'])): ?><p class="error-message mt-1"><?= $errors['username'] ?></p><?php endif; ?>
                    </div>
                    <div class="password-container">
                        <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                        <input id="password" name="password" type="password" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['password']) ? 'input-error' : 'border-gray-300' ?>">
                        <span class="toggle-password" onclick="togglePasswordVisibility('password')"><i class="fa-solid fa-eye-slash"></i></span>
                        <?php if (isset($errors['password'])): ?><p class="error-message mt-1"><?= $errors['password'] ?></p><?php endif; ?>
                    </div>
                    <div class="password-container">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Nhập lại mật khẩu</label>
                        <input id="confirm_password" name="confirm_password" type="password" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['confirm_password']) ? 'input-error' : 'border-gray-300' ?>">
                        <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')"><i class="fa-solid fa-eye-slash"></i></span>
                        <?php if (isset($errors['confirm_password'])): ?><p class="error-message mt-1"><?= $errors['confirm_password'] ?></p><?php endif; ?>
                    </div>
                    <button type="submit" class="w-full p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Đăng ký</button>
                </form>

            <?php elseif ($role == 'company'): ?>
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">Đăng ký Doanh nghiệp</h2>
                <p class="text-center text-gray-600 mb-6">Tạo tài khoản để tìm nhân tài.</p>

                <?php if (isset($errors['db'])): ?>
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm"><?= $errors['db'] ?></div>
                <?php endif; ?>

                <form method="POST" action="?role=company" class="space-y-4">
                    <input type="hidden" name="role" value="company">
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">Tên công ty</label>
                        <input id="company_name" name="company_name" type="text" value="<?= getPostValue('company_name') ?>" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['company_name']) ? 'input-error' : 'border-gray-300' ?>">
                        <?php if (isset($errors['company_name'])): ?><p class="error-message mt-1"><?= $errors['company_name'] ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="<?= getPostValue('email') ?>" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['email']) ? 'input-error' : 'border-gray-300' ?>">
                        <?php if (isset($errors['email'])): ?><p class="error-message mt-1"><?= $errors['email'] ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Tên đăng nhập</label>
                        <input id="username" name="username" type="text" value="<?= getPostValue('username') ?>" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['username']) ? 'input-error' : 'border-gray-300' ?>">
                        <?php if (isset($errors['username'])): ?><p class="error-message mt-1"><?= $errors['username'] ?></p><?php endif; ?>
                    </div>
                    <div class="password-container">
                        <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                        <input id="password" name="password" type="password" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['password']) ? 'input-error' : 'border-gray-300' ?>">
                        <span class="toggle-password" onclick="togglePasswordVisibility('password')"><i class="fa-solid fa-eye-slash"></i></span>
                        <?php if (isset($errors['password'])): ?><p class="error-message mt-1"><?= $errors['password'] ?></p><?php endif; ?>
                    </div>
                    <div class="password-container">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Nhập lại mật khẩu</label>
                        <input id="confirm_password" name="confirm_password" type="password" required class="mt-1 w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 <?= isset($errors['confirm_password']) ? 'input-error' : 'border-gray-300' ?>">
                        <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')"><i class="fa-solid fa-eye-slash"></i></span>
                        <?php if (isset($errors['confirm_password'])): ?><p class="error-message mt-1"><?= $errors['confirm_password'] ?></p><?php endif; ?>
                    </div>
                    <button type="submit" class="w-full p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Đăng ký</button>
                </form>
            <?php endif; ?>

            <p class="mt-4 text-center text-sm text-gray-600">
                Đã có tài khoản? <a href="login.php" class="text-blue-600 hover:underline">Đăng nhập</a>
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