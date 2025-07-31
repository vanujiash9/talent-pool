<?php
include "config.php";
$msg = "";
$success = false;
$role = isset($_GET['role']) ? $_GET['role'] : null;
$errors = [];

// Giữ input user nếu lỗi khi submit
function getPostValue($key) {
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : '';
}

// Xử lý đăng ký
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? null;

    // ĐĂNG KÝ ỨNG VIÊN
    if ($role == 'applicant') {
        $fullname = getPostValue("fullname");
        $email = getPostValue("email");
        $username = getPostValue("username");
        $password = $_POST["password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';

        if (empty($fullname)) $errors['fullname'] = 'Họ tên không được để trống.';
        if (empty($email)) $errors['email'] = 'Email không được để trống.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email không hợp lệ.';
        if (empty($username)) $errors['username'] = 'Tên đăng nhập không được để trống.';
        if (empty($password)) {
            $errors['password'] = 'Mật khẩu không được để trống.';
        } else {
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                $errors['password'] = 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.';
            }
        }
        if ($password !== $confirm_password) $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $conn->begin_transaction();
            try {
                // Kiểm tra username trùng
                $query = $conn->prepare("SELECT 1 FROM applicant_accounts WHERE username=?");
                $query->bind_param("s", $username);
                $query->execute();
                if ($query->get_result()->num_rows > 0) {
                    $errors['username'] = "Tên đăng nhập đã tồn tại!";
                    throw new Exception("Tên đăng nhập đã tồn tại!");
                }
                $query->close();

                // Insert applicants
                $stmt = $conn->prepare("INSERT INTO applicants (full_name, email, applicant_status) VALUES (?, ?, 'active')");
                $stmt->bind_param("ss", $fullname, $email);
                $stmt->execute();
                $new_applicant_id = $stmt->insert_id;
                $stmt->close();

                // Insert tài khoản
                $stmt2 = $conn->prepare("INSERT INTO applicant_accounts (applicant_id, username, password, role) VALUES (?, ?, ?, 'user')");
                $stmt2->bind_param("iss", $new_applicant_id, $username, $hashed_password);
                $stmt2->execute();
                $stmt2->close();
                $conn->commit();
                $success = true;
                // Chuyển ngay sang login.php thay vì refresh:3
                header("Location: login.php?success=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $msg = $e->getMessage();
            }
            $conn->close();
        } else {
            $msg = "Vui lòng sửa các lỗi trong form.";
        }
    }

    // ĐĂNG KÝ DOANH NGHIỆP
    elseif ($role == 'company') {
        $company_name = getPostValue("company_name");
        $industry = getPostValue("industry");
        $contact_email = getPostValue("contact_email");
        $username = getPostValue("username");
        $password = $_POST["password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';

        if (empty($company_name)) $errors['company_name'] = 'Tên công ty không được để trống.';
        if (empty($industry)) $errors['industry'] = 'Ngành nghề không được để trống.';
        if (empty($contact_email)) $errors['contact_email'] = 'Email liên hệ không được để trống.';
        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors['contact_email'] = 'Email liên hệ không hợp lệ.';
        if (empty($username)) $errors['username'] = 'Tên đăng nhập không được để trống.';
        if (empty($password)) {
            $errors['password'] = 'Mật khẩu không được để trống.';
        } else {
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                $errors['password'] = 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.';
            }
        }
        if ($password !== $confirm_password) $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';

        if (empty($errors)) {
            $website_url = getPostValue("website_url");
            $contact_phone = getPostValue("contact_phone");
            $contact_address = getPostValue("contact_address");
            $logo_url = getPostValue("logo_url");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $conn->begin_transaction();
            try {
                // Kiểm tra tên công ty
                $sql_check_company = "SELECT 1 FROM companies WHERE company_name = ?";
                $stmt_check_company = $conn->prepare($sql_check_company);
                $stmt_check_company->bind_param("s", $company_name);
                $stmt_check_company->execute();
                if ($stmt_check_company->get_result()->num_rows > 0) {
                    $errors['company_name'] = "Tên công ty đã tồn tại.";
                    throw new Exception("Tên công ty đã tồn tại.");
                }
                $stmt_check_company->close();

                // Kiểm tra username trùng
                $sql_check_user = "SELECT 1 FROM company_accounts WHERE username = ?";
                $stmt_check_user = $conn->prepare($sql_check_user);
                $stmt_check_user->bind_param("s", $username);
                $stmt_check_user->execute();
                if ($stmt_check_user->get_result()->num_rows > 0) {
                    $errors['username'] = "Tên đăng nhập đã tồn tại.";
                    throw new Exception("Tên đăng nhập đã tồn tại.");
                }
                $stmt_check_user->close();

                // Thêm công ty
                $sql_insert_company = "INSERT INTO companies (company_name, industry, logo_url) VALUES (?, ?, ?)";
                $stmt_insert_company = $conn->prepare($sql_insert_company);
                $stmt_insert_company->bind_param("sss", $company_name, $industry, $logo_url);
                if (!$stmt_insert_company->execute()) throw new Exception("Lỗi khi thêm công ty: " . $stmt_insert_company->error);
                $company_id = $conn->insert_id;
                $stmt_insert_company->close();

                // Địa chỉ công ty
                $sql_insert_location = "INSERT INTO company_locations (company_id, website_url, contact_address) VALUES (?, ?, ?)";
                $stmt_insert_location = $conn->prepare($sql_insert_location);
                $stmt_insert_location->bind_param("iss", $company_id, $website_url, $contact_address);
                if (!$stmt_insert_location->execute()) throw new Exception("Lỗi khi thêm địa chỉ: " . $stmt_insert_location->error);
                $stmt_insert_location->close();

                // Liên hệ công ty
                $sql_insert_contact = "INSERT INTO company_contact (company_id, contact_email, contact_phone) VALUES (?, ?, ?)";
                $stmt_insert_contact = $conn->prepare($sql_insert_contact);
                $stmt_insert_contact->bind_param("iss", $company_id, $contact_email, $contact_phone);
                if (!$stmt_insert_contact->execute()) throw new Exception("Lỗi khi thêm thông tin liên hệ: " . $stmt_insert_contact->error);
                $stmt_insert_contact->close();

                // Năm thành lập
                $sql_insert_timeline = "INSERT INTO company_timeline (company_id, founded_year) VALUES (?, YEAR(CURRENT_DATE()))";
                $stmt_insert_timeline = $conn->prepare($sql_insert_timeline);
                $stmt_insert_timeline->bind_param("i", $company_id);
                if (!$stmt_insert_timeline->execute()) throw new Exception("Lỗi khi thêm mốc thời gian: " . $stmt_insert_timeline->error);
                $stmt_insert_timeline->close();

                // Tài khoản admin
                $sql_insert_account = "INSERT INTO company_accounts (company_id, username, password, role) VALUES (?, ?, ?, 'company_admin')";
                $stmt_insert_account = $conn->prepare($sql_insert_account);
                $stmt_insert_account->bind_param("iss", $company_id, $username, $hashed_password);
                if (!$stmt_insert_account->execute()) throw new Exception("Lỗi khi tạo tài khoản: " . $stmt_insert_account->error);
                $stmt_insert_account->close();

                $conn->commit();
                $success = true;
                // Chuyển ngay sang login.php
                header("Location: login.php?success=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $msg = $e->getMessage();
            }
            $conn->close();
        } else {
            $msg = "Vui lòng sửa các lỗi trong form.";
        }
    }
    else {
        $msg = "Vai trò không hợp lệ.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(to right, #4a90e2, #50e3c2); }
        .input-error { border-color: #ef4444; }
        .error-message { color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; }
        .password-container { position: relative; }
        .toggle-password { position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; color: #6b7280; }
    </style>
</head>
<body class="bg-gray-100 antialiased">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-2xl p-8 space-y-8 bg-white rounded-xl shadow-lg">
            <?php if (!empty($msg) || !empty($errors)): ?>
                <div class="p-4 rounded-md <?= $success && empty($errors) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                    <p><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!$role || ($success)): ?>
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Bạn muốn đăng ký với vai trò gì?</h2>
                    <div class="mt-8 space-y-4">
                        <a href="register.php?role=applicant" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-lg font-medium rounded-md text-white gradient-bg hover:bg-indigo-700">Ứng viên</a>
                        <a href="register.php?role=company" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-lg font-medium rounded-md text-white gradient-bg hover:bg-indigo-700">Doanh nghiệp</a>
                    </div>
                </div>
            <?php elseif ($role == 'applicant' && !$success): ?>
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Đăng ký tài khoản ứng viên</h2>
                    <p class="mt-2 text-sm text-gray-600">Nhanh chóng tạo hồ sơ để tìm kiếm công việc phù hợp</p>
                </div>
                <form class="mt-8 space-y-6" action="register.php?role=applicant" method="POST">
                    <input type="hidden" name="role" value="applicant">
                    <div class="space-y-4">
                        <div>
                            <label for="fullname" class="block text-sm font-medium text-gray-700">Họ tên<span class="text-red-500">*</span></label>
                            <input id="fullname" name="fullname" type="text" value="<?= getPostValue('fullname') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['fullname']) ? 'input-error' : '' ?>">
                            <?php if(isset($errors['fullname'])) echo "<p class='error-message'>{$errors['fullname']}</p>"; ?>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email<span class="text-red-500">*</span></label>
                            <input id="email" name="email" type="email" value="<?= getPostValue('email') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['email']) ? 'input-error' : '' ?>">
                            <?php if(isset($errors['email'])) echo "<p class='error-message'>{$errors['email']}</p>"; ?>
                        </div>
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Tên đăng nhập<span class="text-red-500">*</span></label>
                            <input id="username" name="username" type="text" autocomplete="username" value="<?= getPostValue('username') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['username']) ? 'input-error' : '' ?>">
                            <?php if(isset($errors['username'])) echo "<p class='error-message'>{$errors['username']}</p>"; ?>
                        </div>
                        <div class="password-container">
                            <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu<span class="text-red-500">*</span></label>
                            <input id="password" name="password" type="password" autocomplete="new-password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['password']) ? 'input-error' : '' ?>">
                            <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePasswordVisibility('password')"></i>
                            <?php if(isset($errors['password'])) echo "<p class='error-message'>{$errors['password']}</p>"; ?>
                        </div>
                        <div class="password-container">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Nhập lại mật khẩu<span class="text-red-500">*</span></label>
                            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['confirm_password']) ? 'input-error' : '' ?>">
                            <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePasswordVisibility('confirm_password')"></i>
                            <?php if(isset($errors['confirm_password'])) echo "<p class='error-message'>{$errors['confirm_password']}</p>"; ?>
                        </div>
                    </div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white gradient-bg hover:bg-indigo-700">Đăng ký</button>
                </form>
            <?php elseif ($role == 'company' && !$success): ?>
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Đăng ký tài khoản doanh nghiệp</h2>
                    <p class="mt-2 text-sm text-gray-600">Điền đầy đủ thông tin để tạo hồ sơ công ty</p>
                </div>
                <form class="mt-8 space-y-6" action="register.php?role=company" method="POST">
                    <input type="hidden" name="role" value="company">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin công ty</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700">Tên công ty<span class="text-red-500">*</span></label>
                                    <input id="company_name" name="company_name" type="text" value="<?= getPostValue('company_name') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['company_name']) ? 'input-error' : '' ?>">
                                    <?php if(isset($errors['company_name'])) echo "<p class='error-message'>{$errors['company_name']}</p>"; ?>
                                </div>
                                <div>
                                    <label for="industry" class="block text-sm font-medium text-gray-700">Ngành nghề<span class="text-red-500">*</span></label>
                                    <input id="industry" name="industry" type="text" value="<?= getPostValue('industry') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['industry']) ? 'input-error' : '' ?>">
                                    <?php if(isset($errors['industry'])) echo "<p class='error-message'>{$errors['industry']}</p>"; ?>
                                </div>
                                <div>
                                    <label for="website_url" class="block text-sm font-medium text-gray-700">Website</label>
                                    <input id="website_url" name="website_url" type="url" value="<?= getPostValue('website_url') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label for="logo_url" class="block text-sm font-medium text-gray-700">Logo (URL)</label>
                                    <input id="logo_url" name="logo_url" type="url" value="<?= getPostValue('logo_url') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-gray-700">Email liên hệ<span class="text-red-500">*</span></label>
                                    <input id="contact_email" name="contact_email" type="email" value="<?= getPostValue('contact_email') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['contact_email']) ? 'input-error' : '' ?>">
                                    <?php if(isset($errors['contact_email'])) echo "<p class='error-message'>{$errors['contact_email']}</p>"; ?>
                                </div>
                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                    <input id="contact_phone" name="contact_phone" type="tel" value="<?= getPostValue('contact_phone') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label for="contact_address" class="block text-sm font-medium text-gray-700">Địa chỉ</label>
                                    <textarea id="contact_address" name="contact_address" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?= getPostValue('contact_address') ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin tài khoản Admin</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700">Tên đăng nhập<span class="text-red-500">*</span></label>
                                    <input id="username" name="username" type="text" autocomplete="username" value="<?= getPostValue('username') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['username']) ? 'input-error' : '' ?>">
                                    <?php if(isset($errors['username'])) echo "<p class='error-message'>{$errors['username']}</p>"; ?>
                                </div>
                                <div class="password-container">
                                    <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu<span class="text-red-500">*</span></label>
                                    <input id="password" name="password" type="password" autocomplete="new-password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['password']) ? 'input-error' : '' ?>">
                                    <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePasswordVisibility('password')"></i>
                                    <?php if(isset($errors['password'])) echo "<p class='error-message'>{$errors['password']}</p>"; ?>
                                </div>
                                <div class="password-container">
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Nhập lại mật khẩu<span class="text-red-500">*</span></label>
                                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['confirm_password']) ? 'input-error' : '' ?>">
                                    <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePasswordVisibility('confirm_password')"></i>
                                    <?php if(isset($errors['confirm_password'])) echo "<p class='error-message'>{$errors['confirm_password']}</p>"; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white gradient-bg hover:bg-indigo-700">Đăng ký tài khoản</button>
                </form>
            <?php endif; ?>

            <?php if (!$success): ?>
            <div class="text-center text-sm text-gray-600 mt-6">
                Đã có tài khoản?
                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">Đăng nhập ngay</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function togglePasswordVisibility(id) {
        const input = document.getElementById(id);
        const toggleIcon = input.nextElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        }
    }
    </script>
</body>
</html>
