<?php
session_start();
require_once "../config.php";

// 1. BẢO VỆ TRANG
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../auth.php");
    exit;
}

$applicant_id = $_SESSION['applicant_id'] ?? null;
if (!$applicant_id) {
    die("Lỗi: Không thể xác định thông tin người dùng.");
}

$message = '';
$message_type = 'error'; // Mặc định là lỗi

// 2. LẤY DỮ LIỆU HIỆN TẠI ĐỂ ĐIỀN VÀO FORM
$sql_fetch = "SELECT a.*, ap.* FROM applicants a LEFT JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id WHERE a.applicant_id = ? LIMIT 1";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $applicant_id);
$stmt_fetch->execute();
$user_data = $stmt_fetch->get_result()->fetch_assoc();
$stmt_fetch->close();

// 3. XỬ LÝ KHI NGƯỜI DÙNG GỬI FORM (CẬP NHẬT DỮ LIỆU)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $full_name = trim($_POST['full_name'] ?? '');
    // ... (lấy tất cả các trường khác tương tự)
    $cv_url = trim($_POST['cv_url'] ?? '');
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    
    $profile_picture_url = $user_data['profile_picture_url']; // Giữ ảnh cũ làm mặc định

    // 4. SỬA LỖI UPLOAD ẢNH
    // Kiểm tra xem có file mới được tải lên không và không có lỗi
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/avatars/"; // Đường dẫn tương đối từ file hiện tại
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES["avatar"]["name"]);
        $file_ext = strtolower($file_info['extension']);
        $allowed_exts = ["jpg", "jpeg", "png", "gif"];
        
        $unique_name = "avatar_" . $applicant_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $unique_name;

        // Kiểm tra file
        $check = getimagesize($_FILES["avatar"]["tmp_name"]);
        if($check !== false && in_array($file_ext, $allowed_exts) && $_FILES["avatar"]["size"] < 5000000) { // Giới hạn 5MB
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                // Xóa ảnh cũ nếu có
                if (!empty($profile_picture_url) && file_exists("../" . $profile_picture_url)) {
                    unlink("../" . $profile_picture_url);
                }
                $profile_picture_url = "uploads/avatars/" . $unique_name; // Lưu đường dẫn tương đối
            } else {
                $message = "Lỗi khi di chuyển file đã tải lên.";
            }
        } else {
            $message = "File không hợp lệ hoặc quá lớn (tối đa 5MB).";
        }
    }
    
    if (empty($message)) {
        $conn->begin_transaction();
        try {
            // Cập nhật bảng applicants
            $sql_applicants = "UPDATE applicants SET full_name=?, date_of_birth=?, phone_number=?, email=?, gender=?, address=? WHERE applicant_id=?";
            $stmt_app = $conn->prepare($sql_applicants);
            // ... (bind_param cho applicants)
            $stmt_app->execute();
            $stmt_app->close();

            // Cập nhật bảng applicant_profiles
            $sql_profile = "UPDATE applicant_profiles SET meta_title=?, summary=?, skills=?, experience_description=?, education_details=?, projects=?, cv_url=?, portfolio_url=?, profile_picture_url=? WHERE applicant_id=?";
            $stmt_profile = $conn->prepare($sql_profile);
            // ... (bind_param cho applicant_profiles, bao gồm $profile_picture_url đã được cập nhật)
            $stmt_profile->execute();
            $stmt_profile->close();
            
            $conn->commit();
            $_SESSION['message'] = "Hồ sơ của bạn đã được cập nhật thành công!";
            header("Location: user_dashboard.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Lỗi khi cập nhật hồ sơ: " . $e->getMessage();
        }
    }
}

// Lấy lại dữ liệu sau khi post để hiển thị trên form
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($message)) {
    $user_data = array_merge($user_data, $_POST);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa Hồ sơ</title>
    <!-- (Toàn bộ phần <head> và CSS giống phiên bản trước) -->
</head>
<body>
    <!-- (Header giống phiên bản trước) -->

    <main class="container mx-auto p-4 sm:p-6 lg:p-8 mt-6">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-6">Chỉnh sửa Hồ sơ cá nhân</h1>
            
            <?php if ($message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
            <?php endif; ?>

            <!-- THÊM THUỘC TÍNH QUAN TRỌNG: enctype="multipart/form-data" -->
            <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                
                <!-- (Toàn bộ các trường input và textarea giống phiên bản trước) -->
                
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save mr-2"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
    // (Script previewAvatar không đổi)
    </script>
</body> 
</html>