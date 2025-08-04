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

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// 2. KHAI THÁC DỮ LIỆU TỪ NHIỀU BẢNG

// === Query 1: Thông tin chính của ứng viên ===
$sql_main = "
    SELECT 
        a.full_name, a.date_of_birth, a.phone_number, a.email, a.gender, a.address, a.place_of_birth, a.is_talent,
        ap.meta_title, ap.meta_description, ap.profile_views, ap.application_count,
        ap.cv_url, ap.portfolio_url, ap.profile_picture_url,
        ap.experience_description, ap.education_details, ap.skills, ap.projects,
        t.nickname AS talent_nickname, t.rating AS talent_rating
    FROM applicants a
    LEFT JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    LEFT JOIN talents t ON a.applicant_id = t.applicant_id
    WHERE a.applicant_id = ? LIMIT 1
";
$stmt = $conn->prepare($sql_main);
$stmt->bind_param("i", $aid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Không thể tải dữ liệu hồ sơ.");
}

// === Query 2: Kinh nghiệm dự án thực tế ===
$sql_work_projects = "
    SELECT p.project_name, p.request_description, c.company_name, c.logo_url
    FROM project_talents pt
    JOIN projects p ON pt.project_id = p.project_id
    JOIN companies c ON p.company_id = c.company_id
    WHERE pt.applicant_id = ? ORDER BY p.project_time DESC
";
$stmt = $conn->prepare($sql_work_projects);
$stmt->bind_param("i", $aid);
$stmt->execute();
$work_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === Query 3: Đánh giá năng lực ===
$sql_evals = "
    SELECT e.evaluation_content, e.category, t.task_name
    FROM evaluations e
    LEFT JOIN tasks t ON e.task_id = t.task_id
    WHERE e.applicant_id = ? ORDER BY e.evaluation_id DESC
";
$stmt = $conn->prepare($sql_evals);
$stmt->bind_param("i", $aid);
$stmt->execute();
$evaluations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === Query 4: Học vấn ===
$sql_education = "
    SELECT u.university_name, m.major_name, m.certificate, m.gpa
    FROM learn l
    JOIN majors m ON l.major_id = m.major_id
    JOIN universities u ON m.university_id = u.university_id
    WHERE l.applicant_id = ?
";
$stmt = $conn->prepare($sql_education);
$stmt->bind_param("i", $aid);
$stmt->execute();
$education_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === Query 5: Công việc đã ứng tuyển ===
$sql_applied_jobs = "
    SELECT j.job_name, j.average_salary, ja.priority
    FROM job_applicant ja
    JOIN jobs j ON ja.job_id = j.job_id
    WHERE ja.applicant_id = ? ORDER BY ja.priority ASC, j.created_at DESC LIMIT 5
";
$stmt = $conn->prepare($sql_applied_jobs);
$stmt->bind_param("i", $aid);
$stmt->execute();
$applied_jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// 3. XỬ LÝ DỮ LIỆU TRƯỚC KHI HIỂN THỊ
$skills = explode(',', $user['skills'] ?? '');
$skills = array_map('trim', array_filter($skills));

$default_avatar = "https://ui-avatars.com/api/?name=" . urlencode($user['full_name'] ?? 'U') . "&size=256&background=4f46e5&color=fff";
$avatar_url = !empty($user['profile_picture_url']) ? htmlspecialchars($user['profile_picture_url']) : $default_avatar;
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ của <?= htmlspecialchars($user['full_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
    </style>
</head>
<body x-data="{}">

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <a href="user_dashboard.php" class="flex items-center space-x-3">
                    <img src="<?= $avatar_url ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border-2 border-indigo-600">
                    <span class="font-bold text-lg text-gray-800 hidden sm:block">Talent Pool</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="edit_profile.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa hồ sơ
                    </a>
                    <a href="logout.php" class="text-gray-500 hover:text-red-500 transition-colors" title="Đăng xuất"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8 mt-6">
        
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-md shadow-md" role="alert">
                <p class="font-medium"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <aside class="lg:col-span-1 space-y-8">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <img src="<?= $avatar_url ?>" alt="Avatar" class="w-28 h-28 rounded-full mx-auto object-cover border-4 border-indigo-200 shadow-lg mb-3">
                    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></h1>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($user['meta_title'] ?? 'Chưa có vị trí') ?></p>
                    
                    <?php if ($user['is_talent']): ?>
                    <div class="mt-4 inline-flex items-center bg-amber-100 text-amber-800 font-bold text-xs px-3 py-1.5 rounded-full shadow-md">
                        <i class="fas fa-star mr-2"></i>
                        THÀNH VIÊN TALENT POOL
                    </div>
                    <div class="mt-2 text-sm text-gray-600">Đánh giá: <span class="font-bold text-lg text-amber-500"><?= number_format($user['talent_rating'] ?? 0.00, 2) ?></span> / 5.0</div>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Tổng quan hồ sơ</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg p-4 text-center border border-gray-200 shadow-sm">
                            <p class="text-3xl font-bold text-indigo-600"><?= number_format($user['profile_views'] ?? 0) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Lượt xem</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 text-center border border-gray-200 shadow-sm">
                            <p class="text-3xl font-bold text-indigo-600"><?= number_format($user['application_count'] ?? 0) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Lượt ứng tuyển</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg p-4 text-center border border-gray-200 shadow-sm">
                            <p class="text-3xl font-bold text-indigo-600"><?= count($work_history) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Dự án đã làm</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 text-center border border-gray-200 shadow-sm">
                             <p class="text-3xl font-bold text-indigo-600"><?= count($evaluations) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Lượt đánh giá</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Việc đã ứng tuyển</h2>
                    <ul class="space-y-4">
                        <?php if (!empty($applied_jobs)): foreach($applied_jobs as $job): ?>
                        <li class="flex items-center space-x-3 text-sm">
                            <div class="flex-shrink-0 text-indigo-500"><i class="fas fa-briefcase"></i></div>
                            <div>
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($job['job_name']) ?></p>
                                <p class="text-xs text-gray-500">Mức lương: <?= number_format($job['average_salary']) ?> VNĐ</p>
                            </div>
                        </li>
                        <?php endforeach; else: ?>
                            <p class="text-sm text-gray-400 italic">Bạn chưa ứng tuyển công việc nào.</p>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Liên hệ</h2>
                    <div class="space-y-3 text-gray-600 text-sm">
                        <p><i class="fas fa-envelope mr-2 text-indigo-500"></i><?= htmlspecialchars($user['email']) ?></p>
                        <p><i class="fas fa-phone mr-2 text-indigo-500"></i><?= htmlspecialchars($user['phone_number'] ?? 'Chưa cập nhật') ?></p>
                        <p><i class="fas fa-map-marker-alt mr-2 text-indigo-500"></i><?= htmlspecialchars($user['address'] ?? 'Chưa cập nhật') ?></p>
                    </div>
                </div>
            </aside>

            <main class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Giới thiệu</h2>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line text-sm"><?= !empty($user['meta_description']) ? htmlspecialchars($user['meta_description']) : 'Hãy cập nhật phần giới thiệu bản thân để nhà tuyển dụng hiểu rõ hơn về bạn.' ?></p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Kỹ năng & Đánh giá</h2>
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-700 text-lg mb-2">Kỹ năng</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php if (!empty($skills)): foreach($skills as $skill): ?>
                                <span class="bg-sky-100 text-sky-800 text-xs font-semibold px-2.5 py-1 rounded-full shadow-sm"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; else: ?>
                                <p class="text-gray-500 text-sm italic">Chưa cập nhật kỹ năng.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($evaluations)): ?>
                    <div class="border-t pt-6">
                        <h3 class="font-semibold text-gray-700 text-lg mb-4">Phản hồi & Đánh giá</h3>
                        <div class="space-y-6">
                            <?php foreach($evaluations as $eval): ?>
                            <div class="flex items-start text-sm bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="flex-shrink-0 bg-green-100 text-green-600 rounded-full h-8 w-8 flex items-center justify-center mr-4 mt-0.5">
                                    <i class="fas fa-quote-left text-xs"></i>
                                </div>
                                <div>
                                    <p class="italic text-gray-800 leading-relaxed">"<?= htmlspecialchars($eval['evaluation_content']) ?>"</p>
                                    <p class="text-xs text-gray-500 mt-2">Cho task: <span class="font-medium text-gray-600"><?= htmlspecialchars($eval['task_name']) ?></span></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($work_history)): ?>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Kinh nghiệm dự án thực tế</h2>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line text-sm mb-4"><?= !empty($user['experience_description']) ? htmlspecialchars($user['experience_description']) : 'Chưa có mô tả kinh nghiệm.' ?></p>
                    <div class="relative border-l-2 border-indigo-200 pl-6 space-y-8">
                        <?php foreach($work_history as $work): ?>
                        <div class="relative">
                            <div class="absolute -left-[37px] top-0 w-10 h-10 rounded-full bg-white border-4 border-indigo-200 flex items-center justify-center shadow-md">
                                <img src="<?= htmlspecialchars($work['logo_url'] ?? 'https://via.placeholder.com/32') ?>" class="w-8 h-8 rounded-full object-contain" alt="logo">
                            </div>
                            <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($work['project_name']) ?></h3>
                            <p class="text-sm font-semibold text-indigo-700"><?= htmlspecialchars($work['company_name']) ?></p>
                            <p class="text-sm text-gray-600 mt-2 leading-relaxed"><?= htmlspecialchars($work['request_description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Học vấn</h2>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line text-sm mb-4"><?= !empty($user['education_details']) ? htmlspecialchars($user['education_details']) : 'Chưa có mô tả chi tiết học vấn.' ?></p>
                    <div class="space-y-6">
                        <?php if (!empty($education_history)): foreach($education_history as $edu): ?>
                        <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex-shrink-0 bg-indigo-100 rounded-full h-10 w-10 flex items-center justify-center mr-4">
                                <i class="fas fa-graduation-cap text-indigo-500 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($edu['university_name']) ?></h3>
                                <p class="text-sm text-gray-600 mt-1">Chuyên ngành: <span class="font-medium"><?= htmlspecialchars($edu['major_name']) ?></span></p>
                                <p class="text-xs text-gray-500">Bằng: <?= htmlspecialchars($edu['certificate']) ?> | GPA: <?= number_format($edu['gpa'], 2) ?></p>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <p class="text-gray-500 text-sm italic">Chưa cập nhật thông tin học vấn.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </main>

</body>
</html>