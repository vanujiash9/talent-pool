<?php
session_start();
require_once "../config.php"; // Đi ngược ra 1 cấp để gọi config.php

// 1. BẢO VỆ TRANG: KIỂM TRA XÁC THỰC
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../auth.php");
    exit;
}

$applicant_id = $_SESSION['applicant_id'] ?? null;
if (!$applicant_id) {
    die("Lỗi: Không thể xác định thông tin người dùng. Vui lòng đăng nhập lại.");
}

$user_data = null;
$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// 2. LẤY DỮ LIỆU TỔNG HỢP CỦA ỨNG VIÊN TỪ DATABASE
$sql = "
    SELECT 
        a.full_name, a.date_of_birth, a.phone_number, a.email, a.gender, a.address,
        ap.profile_picture_url, ap.meta_title, ap.summary, ap.skills,
        ap.experience_description, ap.education_details, ap.projects,
        ap.cv_url, ap.portfolio_url,
        m.major_name,
        u.university_name
    FROM applicants a
    LEFT JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    LEFT JOIN learn l ON a.applicant_id = l.applicant_id
    LEFT JOIN majors m ON l.major_id = m.major_id
    LEFT JOIN universities u ON m.university_id = u.university_id
    WHERE a.applicant_id = ? LIMIT 1
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    }
    $stmt->close();
}
$conn->close();

if (!$user_data) {
    die("Không thể tải thông tin hồ sơ của bạn.");
}

// 3. XỬ LÝ DỮ LIỆU ĐỂ HIỂN THỊ
$default_avatar = "https://ui-avatars.com/api/?name=" . urlencode($user_data['full_name']) . "&size=256&background=4f46e5&color=fff";
$avatar_url = !empty($user_data['profile_picture_url']) ? "../" . htmlspecialchars($user_data['profile_picture_url']) : $default_avatar;
$skills_array = !empty($user_data['skills']) ? array_map('trim', explode(',', $user_data['skills'])) : [];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ của tôi - <?= htmlspecialchars($user_data['full_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .card { background-color: white; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); transition: all 0.3s ease-in-out; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1); }
        .tag { background-color: #e0e7ff; color: #3730a3; padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600; }
        .icon-label { display: flex; align-items: center; color: #4b5563; }
        .icon-label i { width: 1.25rem; text-align: center; margin-right: 0.75rem; color: #6366f1; }
        .prose { max-width: none; }
        .prose h3 { font-weight: 700; margin-bottom: 0.5em; }
        .prose p { margin-top: 0; margin-bottom: 1em; }
        .prose ul { list-style-type: disc; padding-left: 1.5em; }
        .prose li { margin-bottom: 0.5em; }
    </style>
</head>
<body class="antialiased">
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <a href="../index.php" class="text-xl font-bold text-indigo-600">Talent Pool</a>
                <div class="flex items-center space-x-4">
                     <span class="text-gray-600 hidden sm:block">Xin chào, <strong><?= htmlspecialchars($user_data['full_name']) ?></strong></span>
                    <a href="../logout.php" class="text-gray-500 hover:text-red-500 transition-colors" title="Đăng xuất"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8">
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-8">
                <div class="card p-6 text-center">
                    <img src="<?= $avatar_url ?>" alt="Ảnh đại diện" class="w-32 h-32 rounded-full object-cover mx-auto mb-4 border-4 border-indigo-200 shadow-lg">
                    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($user_data['full_name']) ?></h1>
                    <p class="text-indigo-600 font-semibold mt-1"><?= htmlspecialchars($user_data['meta_title'] ?? 'Chưa cập nhật vị trí') ?></p>
                    <div class="mt-6 flex justify-center space-x-4">
                        <?php if(!empty($user_data['cv_url'])): ?>
                            <a href="<?= htmlspecialchars($user_data['cv_url']) ?>" target="_blank" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors text-sm"><i class="fas fa-download mr-2"></i>Xem CV</a>
                        <?php endif; ?>
                        <?php if(!empty($user_data['portfolio_url'])): ?>
                             <a href="<?= htmlspecialchars($user_data['portfolio_url']) ?>" target="_blank" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition-colors text-sm"><i class="fas fa-briefcase mr-2"></i>Portfolio</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card p-6">
                    <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Thông tin liên hệ</h2>
                    <div class="space-y-3 text-sm">
                        <p class="icon-label"><i class="fas fa-envelope"></i> <?= htmlspecialchars($user_data['email']) ?></p>
                        <p class="icon-label"><i class="fas fa-phone"></i> <?= htmlspecialchars($user_data['phone_number'] ?? 'Chưa cập nhật') ?></p>
                        <p class="icon-label"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user_data['address'] ?? 'Chưa cập nhật') ?></p>
                        <p class="icon-label"><i class="fas fa-birthday-cake"></i> <?= !empty($user_data['date_of_birth']) ? date("d/m/Y", strtotime($user_data['date_of_birth'])) : 'Chưa cập nhật' ?></p>
                    </div>
                </div>

                <div class="card p-6">
                    <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Kỹ năng</h2>
                    <div class="flex flex-wrap gap-2">
                        <?php if (!empty($skills_array)): ?>
                            <?php foreach ($skills_array as $skill): ?>
                                <span class="tag"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Chưa cập nhật kỹ năng.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-8">
                <div class="card p-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-gray-800">Giới thiệu bản thân</h2>
                        <a href="edit_profile.php" class="inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-700 font-semibold rounded-lg hover:bg-indigo-200 transition-colors text-sm">
                            <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                        </a>
                    </div>
                    <p class="text-gray-600 leading-relaxed">
                        <?= !empty($user_data['summary']) ? nl2br(htmlspecialchars($user_data['summary'])) : 'Hãy viết một đoạn giới thiệu ngắn về bản thân, mục tiêu nghề nghiệp và những gì bạn đang tìm kiếm.' ?>
                    </p>
                </div>
                
                <div class="card p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-briefcase text-indigo-500 mr-3"></i>Kinh nghiệm làm việc</h2>
                    <div class="text-gray-600 leading-relaxed prose">
                        <?= !empty($user_data['experience_description']) ? nl2br(htmlspecialchars($user_data['experience_description'])) : '<p>Chưa có thông tin kinh nghiệm làm việc.</p>' ?>
                    </div>
                </div>

                <div class="card p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-graduation-cap text-indigo-500 mr-3"></i>Học vấn</h2>
                    <div class="border-l-4 border-indigo-200 pl-6 space-y-6">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($user_data['university_name'] ?? 'Chưa cập nhật') ?></h3>
                            <p class="text-indigo-600 font-semibold"><?= htmlspecialchars($user_data['major_name'] ?? 'Chưa cập nhật') ?></p>
                            <div class="text-gray-600 leading-relaxed mt-2 prose">
                                <?= !empty($user_data['education_details']) ? nl2br(htmlspecialchars($user_data['education_details'])) : '<p>Chưa có mô tả chi tiết về quá trình học tập.</p>' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-project-diagram text-indigo-500 mr-3"></i>Dự án đã thực hiện</h2>
                     <div class="text-gray-600 leading-relaxed prose">
                        <?= !empty($user_data['projects']) ? nl2br(htmlspecialchars($user_data['projects'])) : '<p>Chưa có thông tin dự án.</p>' ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>