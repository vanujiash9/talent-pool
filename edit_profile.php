<?php
// Bật hiển thị lỗi PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();

// Kiểm tra session đăng nhập và vai trò
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] != 'applicant') {
    header("Location: login.php");
    exit;
}

include "config.php";

$username = $_SESSION['username'];
$aid = $_SESSION['applicant_id'] ?? null;

// Nếu applicant_id không có trong session, tìm từ database
if ($aid === null) {
    $stmt = $conn->prepare("SELECT applicant_id FROM applicant_accounts WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $aid = $result->fetch_assoc()['applicant_id'];
        $_SESSION['applicant_id'] = $aid;
    }
    $stmt->close();
}

if (!$aid) {
    die("Không tìm thấy thông tin người dùng.");
}

$user = [];

// Xử lý khi form được gửi đi (trước khi hiển thị HTML)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form, sử dụng hàm trim() để loại bỏ khoảng trắng thừa
    $full_name = trim($_POST['full_name'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $experience_description = trim($_POST['experience_description'] ?? '');
    $education_details = trim($_POST['education_details'] ?? '');
    $skills = trim($_POST['skills'] ?? '[]');
    $projects = trim($_POST['projects'] ?? '[]');
    $avatar_url = trim($_POST['avatar_url'] ?? '');

    // Xử lý upload ảnh đại diện
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] == 0) {
        $target_dir = "uploads/avatars/";
        $file_extension = pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION);
        $file_name = "avatar_" . $aid . "." . $file_extension;
        $target_file = $target_dir . $file_name;

        // Di chuyển file đã upload
        if (move_uploaded_file($_FILES["avatar_file"]["tmp_name"], $target_file)) {
            $avatar_url = $target_file;
        } else {
            $_SESSION['message'] = 'Có lỗi xảy ra khi tải ảnh lên.';
            header("Location: edit_profile.php");
            exit;
        }
    }
    
    // Cập nhật bảng `applicants`
    $sql_applicant_update = "UPDATE applicants SET full_name = ?, date_of_birth = ?, gender = ?, phone_number = ?, address = ?, avatar_url = ? WHERE applicant_id = ?";
    if ($stmt = $conn->prepare($sql_applicant_update)) {
        $stmt->bind_param("ssssssi", $full_name, $date_of_birth, $gender, $phone_number, $address, $avatar_url, $aid);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Lỗi cập nhật bảng applicants: " . $conn->error);
    }

    // Cập nhật hoặc chèn dữ liệu vào bảng `applicant_profiles`
    $sql_profile_update = "
        INSERT INTO applicant_profiles (applicant_id, meta_title, meta_description, summary, experience_description, education_details, skills, projects)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            meta_title = VALUES(meta_title), 
            meta_description = VALUES(meta_description),
            summary = VALUES(summary),
            experience_description = VALUES(experience_description),
            education_details = VALUES(education_details),
            skills = VALUES(skills),
            projects = VALUES(projects)
    ";

    if ($stmt = $conn->prepare($sql_profile_update)) {
        $stmt->bind_param("isssssss", $aid, $meta_title, $meta_description, $summary, $experience_description, $education_details, $skills, $projects);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Lỗi cập nhật bảng applicant_profiles: " . $conn->error);
    }
    
    // Gán thông báo thành công vào session và chuyển hướng về dashboard
    $_SESSION['message'] = 'Thông tin của bạn đã được cập nhật thành công!';
    header("Location: user_dashboard.php");
    exit;
}

// Lấy thông tin người dùng từ database để điền vào form (khi form chưa được POST)
$sql_user_profile = "
    SELECT 
        a.full_name, a.date_of_birth, a.gender, a.phone_number, a.address, a.avatar_url,
        ap.meta_title, ap.meta_description, ap.summary, ap.experience_description, 
        ap.education_details, ap.skills, ap.projects
    FROM applicants a
    LEFT JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    WHERE a.applicant_id = ? LIMIT 1
";

if ($stmt = $conn->prepare($sql_user_profile)) {
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    die("Lỗi truy vấn SQL user profile: " . $conn->error);
}

$conn->close();

// Tạo URL ảnh đại diện mặc định nếu không có
$default_avatar_url = "https://via.placeholder.com/200/4a76a8/ffffff?text=" . strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$current_avatar_url = htmlspecialchars($user['avatar_url'] ?? $default_avatar_url);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa hồ sơ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <style>
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }
        .custom-tooltip .tooltiptext {
            visibility: hidden;
            width: 300px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -150px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .custom-tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #555 transparent transparent transparent;
        }
        .custom-tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-100 antialiased">
    <div class="container mx-auto my-5 p-5">
        <div class="bg-white p-8 rounded-lg shadow-xl">
            <div class="flex justify-between items-center mb-8 border-b pb-4">
                <h1 class="text-3xl font-bold text-gray-800">Chỉnh sửa hồ sơ</h1>
                <a href="user_dashboard.php" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại Dashboard
                </a>
            </div>
            
            <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <div class="md:col-span-1 flex flex-col items-center p-4 bg-gray-50 rounded-lg shadow-inner">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">Ảnh đại diện</h2>
                        <img src="<?= $current_avatar_url ?>" alt="Avatar" class="h-48 w-48 rounded-full object-cover border-4 border-gray-200 mb-6 shadow-md">
                        
                        <div class="w-full space-y-4">
                            <div>
                                <label for="avatar_file" class="block text-sm font-medium text-gray-700">Tải lên từ máy tính</label>
                                <input type="file" name="avatar_file" id="avatar_file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div class="relative flex py-3 items-center">
                                <div class="flex-grow border-t border-gray-300"></div>
                                <span class="flex-shrink mx-4 text-gray-400 text-sm">HOẶC</span>
                                <div class="flex-grow border-t border-gray-300"></div>
                            </div>
                            
                            <div>
                                <label for="avatar_url" class="block text-sm font-medium text-gray-700">Dán URL ảnh</label>
                                <input type="text" name="avatar_url" id="avatar_url" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2 space-y-8">

                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Thông tin cá nhân</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Họ và Tên</label>
                                    <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Ngày sinh</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700">Giới tính</label>
                                    <select name="gender" id="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="male" <?= ($user['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Nam</option>
                                        <option value="female" <?= ($user['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Nữ</option>
                                        <option value="other" <?= ($user['gender'] ?? '') == 'other' ? 'selected' : '' ?>>Khác</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                    <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>
                                <div class="col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700">Địa chỉ</label>
                                    <input type="text" name="address" id="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Thông tin nghề nghiệp</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="meta_title" class="block text-sm font-medium text-gray-700">Vị trí nổi bật</label>
                                    <input type="text" name="meta_title" id="meta_title" value="<?= htmlspecialchars($user['meta_title'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>
                                <div class="col-span-2">
                                    <label for="meta_description" class="block text-sm font-medium text-gray-700">Mô tả ngắn gọn</label>
                                    <textarea name="meta_description" id="meta_description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"><?= htmlspecialchars($user['meta_description'] ?? '') ?></textarea>
                                </div>
                                <div class="col-span-2">
                                    <label for="summary" class="block text-sm font-medium text-gray-700">Tóm tắt về bản thân</label>
                                    <textarea name="summary" id="summary" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"><?= htmlspecialchars($user['summary'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Kinh nghiệm & Học vấn</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <label for="experience_description" class="block text-sm font-medium text-gray-700">Kinh nghiệm làm việc</label>
                                    <textarea name="experience_description" id="experience_description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"><?= htmlspecialchars($user['experience_description'] ?? '') ?></textarea>
                                </div>
                                <div class="col-span-2">
                                    <label for="education_details" class="block text-sm font-medium text-gray-700">Thông tin học vấn</label>
                                    <textarea name="education_details" id="education_details" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"><?= htmlspecialchars($user['education_details'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Kỹ năng & Dự án</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <label for="skills" class="block text-sm font-medium text-gray-700">
                                        Kỹ năng (JSON Array)
                                        <span class="custom-tooltip text-gray-400 cursor-pointer">
                                            <i class="fas fa-question-circle"></i>
                                            <span class="tooltiptext text-xs">Sử dụng định dạng JSON Array. Ví dụ: ["PHP", "Laravel", "JavaScript"].</span>
                                        </span>
                                    </label>
                                    <textarea name="skills" id="skills" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"><?= htmlspecialchars($user['skills'] ?? '[]') ?></textarea>
                                </div>
                                <div class="col-span-2">
                                    <label for="projects" class="block text-sm font-medium text-gray-700">
                                        Dự án (JSON Array)
                                        <span class="custom-tooltip text-gray-400 cursor-pointer">
                                            <i class="fas fa-question-circle"></i>
                                            <span class="tooltiptext text-xs">Sử dụng định dạng JSON Array. Ví dụ: [{"name":"Project A","role":"Dev","tech":"PHP","description":"...","link":"..."}]</span>
                                        </span>
                                    </label>
                                    <textarea name="projects" id="projects" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"><?= htmlspecialchars($user['projects'] ?? '[]') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-8 border-t pt-4">
                    <a href="user_dashboard.php" class="mr-4 px-6 py-2 text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 transition-colors">
                        Hủy
                    </a>
                    <button type="submit" class="px-6 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>