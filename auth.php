<?php
session_start();
require 'config.php';

if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    if ($_SESSION['role'] === 'user') {
        header("Location: user/user_dashboard.php");
    } elseif ($_SESSION['role'] === 'company') {
        header("Location: company/company_dashboard.php");
    }
    exit();
}

$universities = [];
$result_uni = $conn->query(query: "SELECT university_id, university_name FROM universities ORDER BY university_name ASC");
while ($row = $result_uni->fetch_assoc()) {
    $universities[] = $row;
}
$majors = [];
$result_majors = $conn->query("SELECT major_name FROM majors GROUP BY major_name ORDER BY major_name ASC");
while ($row = $result_majors->fetch_assoc()) {
    $majors[] = $row;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'signUp') {
        $user_type = $_POST['user_type_choice'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $phone = trim($_POST['phone']);

        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Mật khẩu không hợp lệ. Vui lòng kiểm tra lại.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($user_type === 'applicant') {
                $dob = $_POST['dob'];
                $university_id = $_POST['university'];
                $major_name = trim($_POST['major']);
                if (empty($name) || empty($email) || empty($phone) || empty($dob) || empty($university_id) || empty($major_name)) {
                    $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
                } else {
                    $stmt_check = $conn->prepare("SELECT applicant_id FROM applicants WHERE email = ?");
                    $stmt_check->bind_param("s", $email);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $error = 'Email này đã được sử dụng.';
                    } else {
                        $conn->begin_transaction();
                        try {
                            $stmt1 = $conn->prepare("INSERT INTO applicants (full_name, email, phone_number, date_of_birth) VALUES (?, ?, ?, ?)");
                            $stmt1->bind_param("ssss", $name, $email, $phone, $dob);
                            $stmt1->execute();
                            $applicant_id = $stmt1->insert_id;
                            $major_id = null;
                            $stmt_major = $conn->prepare("SELECT major_id FROM majors WHERE major_name = ? AND university_id = ?");
                            $stmt_major->bind_param("si", $major_name, $university_id);
                            $stmt_major->execute();
                            $result_major = $stmt_major->get_result();
                            if ($result_major->num_rows > 0) {
                                $major_id = $result_major->fetch_assoc()['major_id'];
                            } else {
                                $stmt_new_major = $conn->prepare("INSERT INTO majors (major_name, university_id) VALUES (?, ?)");
                                $stmt_new_major->bind_param("si", $major_name, $university_id);
                                $stmt_new_major->execute();
                                $major_id = $stmt_new_major->insert_id;
                            }
                            $stmt2 = $conn->prepare("INSERT INTO applicant_accounts (applicant_id, username, password, role) VALUES (?, ?, ?, 'user')");
                            $username = "user_" . $applicant_id;
                            $stmt2->bind_param("iss", $applicant_id, $username, $hashed_password);
                            $stmt2->execute();
                            $stmt3 = $conn->prepare("INSERT INTO learn (applicant_id, major_id) VALUES (?, ?)");
                            $stmt3->bind_param("ii", $applicant_id, $major_id);
                            $stmt3->execute();
                            $conn->commit();
                            $success = 'Tạo tài khoản thành công! Vui lòng đăng nhập.';
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = 'Lỗi hệ thống khi đăng ký: ' . $e->getMessage();
                        }
                    }
                }
            } elseif ($user_type === 'company') {
                $address = trim($_POST['address']);
                if (empty($name) || empty($email) || empty($phone) || empty($address)) {
                    $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
                } else {
                    $stmt_check = $conn->prepare("SELECT company_id FROM company_contact WHERE contact_email = ?");
                    $stmt_check->bind_param("s", $email);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $error = 'Email này đã được sử dụng.';
                    } else {
                        $conn->begin_transaction();
                        try {
                            $stmt1 = $conn->prepare("INSERT INTO companies (company_name, brand_name) VALUES (?, ?)");
                            $stmt1->bind_param("ss", $name, $name);
                            $stmt1->execute();
                            $company_id = $stmt1->insert_id;
                            $stmt2 = $conn->prepare("INSERT INTO company_contact (company_id, contact_email, contact_phone) VALUES (?, ?, ?)");
                            $stmt2->bind_param("iss", $company_id, $email, $phone);
                            $stmt2->execute();
                            $stmt3 = $conn->prepare("INSERT INTO company_locations (company_id, contact_address) VALUES (?, ?)");
                            $stmt3->bind_param("is", $company_id, $address);
                            $stmt3->execute();
                            $stmt4 = $conn->prepare("INSERT INTO company_accounts (company_id, username, password, role) VALUES (?, ?, ?, 'company_admin')");
                            $username = "company_" . $company_id;
                            $stmt4->bind_param("iss", $company_id, $username, $hashed_password);
                            $stmt4->execute();
                            $conn->commit();
                            $success = 'Tạo tài khoản thành công! Vui lòng đăng nhập.';
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = 'Lỗi hệ thống khi đăng ký: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    } 
    elseif ($_POST['action'] == 'signIn') {
        $user_type = $_POST['user_type_choice'];
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        if (empty($email) || empty($password)) {
            $error = 'Vui lòng nhập email và mật khẩu.';
        } else {
            if ($user_type === 'applicant') {
                $stmt = $conn->prepare("SELECT a.applicant_id, a.full_name, aa.password, aa.username FROM applicants a JOIN applicant_accounts aa ON a.applicant_id = aa.applicant_id WHERE a.email = ? AND aa.account_status = 'active'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['is_logged_in'] = true;
                        $_SESSION['role'] = 'user';
                        $_SESSION['applicant_id'] = $user['applicant_id'];
                        $_SESSION['username'] = $user['username'];
                        header("Location: user/user_dashboard.php");
                        exit();
                    }
                }
            } elseif ($user_type === 'company') {
                $stmt = $conn->prepare("SELECT c.company_id, c.company_name, ca.password, ca.username FROM companies c JOIN company_contact cc ON c.company_id = cc.company_id JOIN company_accounts ca ON c.company_id = ca.company_id WHERE cc.contact_email = ? AND ca.account_status = 'active'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $company = $result->fetch_assoc();
                    if (password_verify($password, $company['password'])) {
                        $_SESSION['is_logged_in'] = true;
                        $_SESSION['role'] = 'company';
                        $_SESSION['company_id'] = $company['company_id'];
                        $_SESSION['username'] = $company['username'];
                        header("Location: company/company_dashboard.php");
                        exit();
                    }
                }
            }
            $error = 'Email hoặc mật khẩu không chính xác.';
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập & Đăng ký</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,700,800');
        * { box-sizing: border-box; }
        :root { --primary-color: #3498db; --gradient-start: #2980b9; --error-color: #e74c3c; }
        body { background: #f6f5f7; display: flex; justify-content: center; align-items: center; flex-direction: column; font-family: 'Montserrat', sans-serif; min-height: 100vh; margin: 20px 0; }
        #role-selection-screen { text-align: center; }
        .role-choice-box { background: white; padding: 40px 60px; border-radius: 10px; box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22); }
        .role-choice-box h1 { font-weight: 800; margin-bottom: 30px; }
        .role-buttons { display: flex; gap: 20px; }
        .role-btn { background-color: var(--primary-color); color: white; border: none; padding: 20px 40px; border-radius: 10px; font-size: 1.2em; font-weight: bold; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .role-btn:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .role-btn i { margin-right: 10px; }
        #main-container { display: none; }
        h1.form-title { font-weight: 800; font-size: 2em; margin: 0 0 20px 0; }
        h2.main-title { font-weight: 700; margin-bottom: 20px; font-size: 1.8em; color: #333; }
        p { font-size: 16px; font-weight: 100; line-height: 24px; letter-spacing: 0.5px; margin: 20px 0 30px; }
        a { color: #333; font-size: 14px; text-decoration: none; margin: 15px 0; }
        button { border-radius: 25px; border: 1px solid var(--primary-color); background-color: var(--primary-color); color: #FFFFFF; font-size: 14px; font-weight: bold; padding: 16px 50px; letter-spacing: 1px; text-transform: uppercase; transition: transform 80ms ease-in; cursor: pointer; }
        button:active { transform: scale(0.95); }
        button:focus { outline: none; }
        button.ghost { background-color: transparent; border-color: #FFFFFF; }
        form { background-color: #FFFFFF; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 50px; height: 100%; text-align: center; }
        input, select { background-color: #f0f0f0; border: 1px solid transparent; padding: 15px; margin: 8px 0; width: 100%; border-radius: 8px; font-size: 14px; font-family: 'Montserrat', sans-serif; transition: border-color 0.3s; }
        input:focus, select:focus { border-color: var(--primary-color); outline: none; }
        .input-error { border-color: var(--error-color) !important; }
        .input-wrapper { position: relative; width: 100%; }
        .input-wrapper i.toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #777; }
        select { appearance: none; -webkit-appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23333%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 15px top 50%; background-size: 12px; }
        .container { background-color: #fff; border-radius: 10px; box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22); position: relative; overflow: hidden; width: 1024px; max-width: 100%; min-height: 720px; }
        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; }
        .sign-in-container { left: 0; width: 50%; z-index: 2; }
        .container.right-panel-active .sign-in-container { transform: translateX(100%); }
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; overflow-y: auto; padding: 20px 0; scrollbar-width: thin; }
        .container.right-panel-active .sign-up-container { transform: translateX(100%); opacity: 1; z-index: 5; animation: show 0.6s; }
        @keyframes show { from { opacity: 0; z-index: 1; } to { opacity: 1; z-index: 5; } }
        .form-field-animated { opacity: 0; transform: translateY(20px); transition: all 0.4s ease; }
        .form-field-animated.visible { opacity: 1; transform: translateY(0); }
        .form-field-animated:nth-child(1) { transition-delay: 0.1s; }
        .form-field-animated:nth-child(2) { transition-delay: 0.2s; }
        .form-field-animated:nth-child(3) { transition-delay: 0.3s; }
        .form-field-animated:nth-child(4) { transition-delay: 0.4s; }
        .form-field-animated:nth-child(5) { transition-delay: 0.5s; }
        .form-field-animated:nth-child(6) { transition-delay: 0.6s; }
        .form-field-animated:nth-child(7) { transition-delay: 0.7s; }
        .form-field-animated:nth-child(8) { transition-delay: 0.8s; }
        .overlay-container { position: absolute; top: 0; left: 50%; width: 50%; height: 100%; overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; }
        .container.right-panel-active .overlay-container{ transform: translateX(-100%); }
        .overlay { background: var(--primary-color); background: -webkit-linear-gradient(to right, var(--gradient-start), var(--primary-color)); background: linear-gradient(to right, var(--gradient-start), var(--primary-color)); background-repeat: no-repeat; background-size: cover; background-position: 0 0; color: #FFFFFF; position: relative; left: -100%; height: 100%; width: 200%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .container.right-panel-active .overlay { transform: translateX(50%); }
        .overlay-panel { position: absolute; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 50px; text-align: center; top: 0; height: 100%; width: 50%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
        .overlay-left { transform: translateX(-20%); }
        .container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }
        .message-box { min-height: 24px; font-size: 14px; font-weight: bold; margin: 5px 0 10px 0; width: 100%; text-align: center; }
        .error { color: var(--error-color); }
        .success { color: #1a7431; }
        .password-error-msg { display: none; text-align: left; font-size: 13px; font-weight: normal; margin-top: -5px; color: var(--error-color); }
        .form-fields { width: 100%; }
        .applicant-field, .company-field { display: none; width: 100%; }
        .applicant-field .form-field-animated, .company-field .form-field-animated { 
            opacity: 0; 
            transform: translateY(20px); 
            transition: all 0.4s ease; 
        }
        .applicant-field .form-field-animated.visible, .company-field .form-field-animated.visible { 
            opacity: 1; 
            transform: translateY(0); 
        }
        .form-container button { transition: all 0.3s ease; }
        .form-container button.animated { transform: translateY(20px); opacity: 0; }
        .form-container button.animated.visible { transform: translateY(0); opacity: 1; transition-delay: 0.9s; }
    </style>
</head>
<body>
    <h2 class="main-title">Cổng kết nối Doanh nghiệp & Sinh viên</h2>
    <div id="role-selection-screen">
        <div class="role-choice-box">
            <h1>Bạn là ai?</h1>
            <div class="role-buttons">
                <button class="role-btn" data-role="applicant"><i class="fas fa-user-graduate"></i>Tôi là Ứng viên</button>
                <button class="role-btn" data-role="company"><i class="fas fa-building"></i>Tôi là Doanh nghiệp</button>
            </div>
        </div>
    </div>
    <div class="container" id="main-container">
        <div class="form-container sign-up-container">
            <form action="auth.php" method="post" id="signUpForm" novalidate>
                <input type="hidden" name="action" value="signUp">
                <input type="hidden" name="user_type_choice" id="signup-role">
                <h1 class="form-title" id="signup-title">Tạo Tài Khoản</h1>
                <div class="message-box">
                    <?php if (isset($_POST['action']) && $_POST['action'] == 'signUp' && $error && (strpos($error, 'Mật khẩu') === false)) echo "<span class='error'>$error</span>"; ?>
                    <?php if ($success) echo "<span class='success'>$success</span>"; ?>
                </div>
                <div class="form-fields">
                    <input type="text" name="name" id="signup-name-placeholder" required class="validate-required form-field-animated" />
                    <input type="email" name="email" placeholder="Email" required class="validate-required form-field-animated"/>
                    <div class="input-wrapper form-field-animated">
                        <input type="password" id="password-signup" name="password" placeholder="Mật khẩu" required class="validate-required"/>
                        <i class="fa fa-eye toggle-password" id="togglePasswordSignUp"></i>
                    </div>
                    <div class="password-error-msg" id="password-error-msg">Mật khẩu phải dài ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số.</div>
                    <input type="tel" name="phone" placeholder="Số điện thoại" required class="validate-required form-field-animated"/>
                    <div class="applicant-field">
                        <input type="text" onfocus="(this.type='date')" onblur="(this.type='text')" name="dob" placeholder="Ngày sinh" class="validate-required form-field-animated"/>
                        <select name="university" class="validate-required form-field-animated">
                            <option value="" disabled selected>-- Chọn trường đại học --</option>
                            <?php foreach($universities as $uni): ?>
                                <option value="<?php echo $uni['university_id']; ?>"><?php echo htmlspecialchars($uni['university_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input list="majors-list" name="major" placeholder="Nhập hoặc chọn chuyên ngành" class="validate-required form-field-animated">
                        <datalist id="majors-list">
                             <?php foreach($majors as $major): ?>
                                <option value="<?php echo htmlspecialchars($major['major_name']); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="company-field">
                        <input type="text" name="address" placeholder="Địa chỉ công ty" class="validate-required form-field-animated"/>
                    </div>
                </div>
                <button type="submit" class="form-field-animated">Đăng Ký</button>
            </form>
        </div>
        <div class="form-container sign-in-container">
            <form action="auth.php" method="post">
                <input type="hidden" name="action" value="signIn">
                <input type="hidden" name="user_type_choice" id="signin-role">
                <h1 class="form-title" id="signin-title">Đăng Nhập</h1>
                <div class="message-box">
                    <?php if (isset($_POST['action']) && $_POST['action'] == 'signIn' && $error) { echo "<span class='error'>$error</span>"; } ?>
                    <?php if ($success) { echo "<span class='success'>$success</span>"; } ?>
                </div>
                <span>Sử dụng tài khoản của bạn</span>
                <input type="email" name="email" placeholder="Email" required />
                <div class="input-wrapper">
                    <input type="password" name="password" id="password-signin" placeholder="Mật khẩu" required />
                    <i class="fa fa-eye toggle-password" id="togglePasswordSignIn"></i>
                </div>
                <a href="#">Quên mật khẩu?</a>
                <button type="submit">Đăng Nhập</button>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1 id="overlay-left-title">Đã có tài khoản?</h1>
                    <p id="overlay-left-text">Đăng nhập để kết nối với các cơ hội tuyệt vời!</p>
                    <button class="ghost" id="signIn">Đăng Nhập</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1 id="overlay-right-title">Thành viên mới?</h1>
                    <p id="overlay-right-text">Tạo tài khoản ngay để không bỏ lỡ các cơ hội việc làm tốt nhất!</p>
                    <button class="ghost" id="signUp">Đăng Ký</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelectionScreen = document.getElementById('role-selection-screen');
            const mainContainer = document.getElementById('main-container');
            const container = document.getElementById('main-container');
            document.querySelectorAll('.role-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const role = this.getAttribute('data-role');
                    document.getElementById('signin-role').value = role;
                    document.getElementById('signup-role').value = role;
                    const applicantFields = document.querySelector('.applicant-field');
                    const companyFields = document.querySelector('.company-field');
                    if (role === 'applicant') {
                        document.getElementById('signup-name-placeholder').placeholder = 'Họ và Tên';
                        applicantFields.style.display = 'block';
                        companyFields.style.display = 'none';
                        applicantFields.querySelectorAll('.validate-required').forEach(el => el.required = true);
                        companyFields.querySelectorAll('.validate-required').forEach(el => el.required = false);
                    } else {
                        document.getElementById('signup-name-placeholder').placeholder = 'Tên công ty';
                        applicantFields.style.display = 'none';
                        companyFields.style.display = 'block';
                        applicantFields.querySelectorAll('.validate-required').forEach(el => el.required = false);
                        companyFields.querySelectorAll('.validate-required').forEach(el => el.required = true);
                    }
                    roleSelectionScreen.style.display = 'none';
                    mainContainer.style.display = 'block';
                });
            });
            document.getElementById('signUp').addEventListener('click', () => {
                container.classList.add("right-panel-active");
                // Delay để hiện các trường form mượt mà
                setTimeout(() => {
                    const animatedFields = document.querySelectorAll('.form-field-animated');
                    animatedFields.forEach(field => {
                        field.classList.add('visible');
                    });
                }, 300);
            });
            document.getElementById('signIn').addEventListener('click', () => {
                const animatedFields = document.querySelectorAll('.form-field-animated');
                animatedFields.forEach(field => {
                    field.classList.remove('visible');
                });
                container.classList.remove("right-panel-active");
            });
            document.querySelectorAll('.toggle-password').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const passwordField = this.previousElementSibling;
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.classList.toggle('fa-eye-slash');
                });
            });
            const passwordInput = document.getElementById('password-signup');
            const passwordErrorMsg = document.getElementById('password-error-msg');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const pass = this.value;
                    const isValid = pass.length >= 8 && /[A-Z]/.test(pass) && /[a-z]/.test(pass) && /[0-9]/.test(pass);
                    if (pass.length > 0 && !isValid) {
                        passwordErrorMsg.style.display = 'block';
                        this.classList.add('input-error');
                    } else {
                        passwordErrorMsg.style.display = 'none';
                        if (this.classList.contains('input-error') && pass.length > 0) {
                           this.classList.remove('input-error');
                        }
                    }
                });
            }
            document.querySelectorAll('.validate-required').forEach(input => {
                input.addEventListener('blur', function() { if (!this.value.trim()) this.classList.add('input-error'); });
                input.addEventListener('input', function() { if (this.value.trim()) this.classList.remove('input-error'); });
            });
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                echo "roleSelectionScreen.style.display = 'none'; mainContainer.style.display = 'block';\n";
                $role_from_post = $_POST['user_type_choice'] ?? 'applicant';
                echo "document.querySelector('.role-btn[data-role=\'$role_from_post\']').click();\n";
                if (isset($_POST['action']) && $_POST['action'] == 'signUp' && !empty($error)) {
                    echo 'container.classList.add("right-panel-active");';
                    echo 'setTimeout(() => {';
                    echo '    const animatedFields = document.querySelectorAll(".form-field-animated");';
                    echo '    animatedFields.forEach(field => field.classList.add("visible"));';
                    echo '}, 300);';
                } elseif (!empty($success)) {
                    echo 'container.classList.remove("right-panel-active");';
                }
            }
            ?>
        });
    </script>
</body>
</html>