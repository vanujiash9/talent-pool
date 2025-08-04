<?php
// Bật hiển thị lỗi và bộ đệm đầu ra để gỡ rối
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();

// 1. KIỂM TRA XÁC THỰC
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit;
}

include "config.php";

$aid = $_SESSION['applicant_id'] ?? null;
if (!$aid) {
    die("Lỗi nghiêm trọng: Không thể xác định thông tin người dùng. Vui lòng đăng nhập lại.");
}

$message = '';

// Xử lý khi người dùng gửi form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $place_of_birth = trim($_POST['place_of_birth'] ?? '');
    
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $cv_url = trim($_POST['cv_url'] ?? '');
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    
    $experience_description = trim($_POST['experience_description'] ?? '');
    $education_details = trim($_POST['education_details'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $projects = trim($_POST['projects'] ?? '');
    
    // Lấy thông tin hiện tại để xử lý avatar
    $sql_current_info = "SELECT profile_picture_url FROM applicant_profiles WHERE applicant_id = ?";
    $stmt = $conn->prepare($sql_current_info);
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    $current_profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $profile_picture_url = $current_profile['profile_picture_url'];

    // Xử lý upload avatar mới nếu có
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "uploads/avatars/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["avatar"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Kiểm tra loại file và kích thước
        $check = getimagesize($_FILES["avatar"]["tmp_name"]);
        if($check !== false && $_FILES["avatar"]["size"] < 500000) { // 500KB
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $profile_picture_url = $target_file;
            } else {
                $message = "Lỗi khi tải lên file.";
            }
        } else {
            $message = "File không hợp lệ hoặc quá lớn.";
        }
    }
    
    if (empty($message)) {
        // Cập nhật bảng applicants
        $sql_applicants = "
            UPDATE applicants 
            SET full_name = ?, date_of_birth = ?, phone_number = ?, email = ?, gender = ?, address = ?, place_of_birth = ? 
            WHERE applicant_id = ?
        ";
        $stmt = $conn->prepare($sql_applicants);
        $stmt->bind_param("sssssssi", $full_name, $date_of_birth, $phone_number, $email, $gender, $address, $place_of_birth, $aid);
        $stmt->execute();
        $stmt->close();

        // Cập nhật bảng applicant_profiles
        $sql_profile = "
            UPDATE applicant_profiles 
            SET meta_title = ?, meta_description = ?, cv_url = ?, portfolio_url = ?, profile_picture_url = ?, 
            experience_description = ?, education_details = ?, skills = ?, projects = ?
            WHERE applicant_id = ?
        ";
        $stmt = $conn->prepare($sql_profile);
        $stmt->bind_param("sssssssssi", $meta_title, $meta_description, $cv_url, $portfolio_url, $profile_picture_url, 
                                          $experience_description, $education_details, $skills, $projects, $aid);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Hồ sơ của bạn đã được cập nhật thành công!";
        header("Location: user_dashboard.php");
        exit;
    }
}

// Lấy dữ liệu hiện tại để hiển thị trong form
$sql_main = "
    SELECT a.*, ap.*
    FROM applicants a
    LEFT JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    WHERE a.applicant_id = ? LIMIT 1
";
$stmt = $conn->prepare($sql_main);
$stmt->bind_param("i", $aid);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$default_avatar = "https://ui-avatars.com/api/?name=" . urlencode($user_data['full_name'] ?? 'U') . "&size=256&background=4f46e5&color=fff";
$avatar_url = !empty($user_data['profile_picture_url']) ? htmlspecialchars($user_data['profile_picture_url']) : $default_avatar;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa Hồ sơ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
    </style>
</head>
<body>

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <a href="user_dashboard.php" class="text-indigo-600 font-bold text-lg"><i class="fas fa-arrow-left mr-2"></i>Trở về Dashboard</a>
                <a href="logout.php" class="text-gray-500 hover:text-red-500 transition-colors" title="Đăng xuất"><i class="fas fa-sign-out-alt fa-lg"></i></a>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8 mt-6">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-6">Chỉnh sửa Hồ sơ cá nhân</h1>
            
            <?php if ($message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
            <?php endif; ?>

            <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                
                <div class="border-b pb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Thông tin cá nhân</h2>
                    <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-8">
                        <div class="flex-shrink-0 text-center">
                            <img id="avatar-preview" src="<?= $avatar_url ?>" alt="Ảnh đại diện" class="w-32 h-32 rounded-full object-cover border-4 border-indigo-200 shadow-md mx-auto">
                            <label class="mt-4 inline-block px-4 py-2 bg-indigo-500 text-white rounded-lg font-semibold cursor-pointer hover:bg-indigo-600 transition-colors text-sm">
                                <i class="fas fa-upload mr-2"></i>Tải ảnh lên
                                <input type="file" name="avatar" id="avatar" class="hidden" onchange="previewAvatar(event)">
                            </label>
                        </div>
                        <div class="flex-grow w-full space-y-4">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Họ và Tên</label>
                                <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($user_data['full_name'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" id="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Ngày sinh</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" value="<?= htmlspecialchars($user_data['date_of_birth'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                    <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($user_data['phone_number'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Giới tính</label>
                                    <select name="gender" id="gender" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="male" <?= ($user_data['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Nam</option>
                                        <option value="female" <?= ($user_data['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Nữ</option>
                                        <option value="other" <?= ($user_data['gender'] ?? '') == 'other' ? 'selected' : '' ?>>Khác</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="place_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Nơi sinh</label>
                                    <input type="text" name="place_of_birth" id="place_of_birth" value="<?= htmlspecialchars($user_data['place_of_birth'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                                <textarea name="address" id="address" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-b pb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Hồ sơ & Kỹ năng</h2>
                    <div class="space-y-4">
                         <div>
                            <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1">Vị trí/Chức danh</label>
                            <input type="text" name="meta_title" id="meta_title" value="<?= htmlspecialchars($user_data['meta_title'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Giới thiệu bản thân</label>
                            <textarea name="meta_description" id="meta_description" rows="5" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($user_data['meta_description'] ?? '') ?></textarea>
                            <p class="mt-2 text-xs text-gray-500">Viết một đoạn tóm tắt về bản thân và mục tiêu nghề nghiệp của bạn.</p>
                        </div>
                         <div>
                            <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Kỹ năng</label>
                            <input type="text" name="skills" id="skills" value="<?= htmlspecialchars($user_data['skills'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <p class="mt-2 text-xs text-gray-500">Liệt kê các kỹ năng của bạn, cách nhau bởi dấu phẩy (ví dụ: PHP, JavaScript, SQL, Tailwind CSS).</p>
                        </div>
                    </div>
                </div>

                <div class="border-b pb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Tài liệu & Portfolio</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="cv_url" class="block text-sm font-medium text-gray-700 mb-1">Link CV</label>
                            <input type="url" name="cv_url" id="cv_url" value="<?= htmlspecialchars($user_data['cv_url'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="portfolio_url" class="block text-sm font-medium text-gray-700 mb-1">Link Portfolio</label>
                            <input type="url" name="portfolio_url" id="portfolio_url" value="<?= htmlspecialchars($user_data['portfolio_url'] ?? '') ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                 <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Mô tả chi tiết</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="experience_description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả Kinh nghiệm</label>
                            <textarea name="experience_description" id="experience_description" rows="5" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($user_data['experience_description'] ?? '') ?></textarea>
                            <p class="mt-2 text-xs text-gray-500">Mô tả chi tiết về các kinh nghiệm làm việc hoặc dự án đã thực hiện.</p>
                        </div>
                        <div>
                            <label for="education_details" class="block text-sm font-medium text-gray-700 mb-1">Mô tả Học vấn</label>
                            <textarea name="education_details" id="education_details" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($user_data['education_details'] ?? '') ?></textarea>
                            <p class="mt-2 text-xs text-gray-500">Mô tả thêm về quá trình học tập, thành tích, hoặc các khóa học.</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save mr-2"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
    function previewAvatar(event) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById('avatar-preview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
    </script>
</body>
</html>