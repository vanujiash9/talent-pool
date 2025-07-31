<?php
// Bật hiển thị lỗi PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Thêm ob_start() để tránh lỗi liên quan đến header
session_start();

// Sửa lỗi: Thay 'user' bằng 'applicant' để khớp với giá trị session
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] != 'applicant') {
    header("Location: login.php");
    exit;
}

include "config.php";

$username = $_SESSION['username'];
$aid = null;
$user = null;
$applied_jobs = [];
$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Lấy applicant_id
if ($stmt = $conn->prepare("SELECT applicant_id FROM applicant_accounts WHERE username = ? LIMIT 1")) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $aid = $result->fetch_assoc()['applicant_id'];
    }
    $stmt->close();
}

if (!$aid) {
    echo "Không tìm thấy thông tin người dùng.";
    exit;
}

// Lấy thông tin ứng viên và profile, bao gồm các trường mới
$sql_user_profile = "
    SELECT 
        a.*, ap.meta_title, ap.meta_description, ap.cv_url, ap.portfolio_url, 
        ap.profile_views, ap.application_count, ap.experience_description, 
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
    echo "Lỗi truy vấn SQL user profile: " . $conn->error;
    exit;
}

// Lấy các job đã ứng tuyển
if ($stmt = $conn->prepare("SELECT j.job_name, j.field, j.average_salary, ja.priority
                                     FROM job_applicant ja
                                     INNER JOIN jobs j ON ja.job_id = j.job_id
                                     WHERE ja.applicant_id = ?
                                     ORDER BY ja.priority ASC, j.job_id DESC LIMIT 5")) {
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    $applied_jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    echo "Lỗi truy vấn SQL applied jobs: " . $conn->error;
    exit;
}
$conn->close();

if (!$user) {
    echo "Không tìm thấy thông tin người dùng.";
    exit;
}

// Xử lý các trường dữ liệu JSON
$skills = json_decode($user['skills'] ?? '[]', true);
$projects = json_decode($user['projects'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Ứng viên</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <style>
        :root { --main-color: #4a76a8; }
        .bg-main-color { background-color: var(--main-color); }
        .text-main-color { color: var(--main-color); }
        .border-main-color { border-color: var(--main-color); }
        .user-avatar { font-size: 4rem; color: #fff; background-color: var(--main-color); display: flex; align-items: center; justify-content: center; }
        .text-teal-600 { color: #2c7a7b; }
        .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .card { transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gray-100 antialiased">
<div class="w-full text-white bg-main-color shadow-md">
    <div x-data="{ open: false }" class="flex flex-col max-w-screen-xl px-4 mx-auto md:items-center md:justify-between md:flex-row md:px-6 lg:px-8">
        <div class="p-4 flex flex-row items-center justify-between">
            <a href="user_dashboard.php" class="text-lg font-semibold tracking-widest uppercase rounded-lg focus:outline-none focus:shadow-outline">Talent Pool</a>
            <button class="md:hidden rounded-lg focus:outline-none focus:shadow-outline" @click="open = !open">
                <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6"><path x-show="!open" fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z" clip-rule="evenodd"></path><path x-show="open" fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
        <nav :class="{'flex': open, 'hidden': !open}" class="flex-col flex-grow pb-4 md:pb-0 hidden md:flex md:justify-end md:flex-row">
            <div @click.away="open = false" class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex flex-row items-center space-x-2 w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent hover:bg-blue-800 md:w-auto md:inline md:mt-0 md:ml-4 focus:bg-blue-800 focus:outline-none focus:shadow-outline">
                    <span><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></span>
                    <img class="inline h-6 w-6 rounded-full object-cover" src="<?= htmlspecialchars($user['avatar_url'] ?? "https://via.placeholder.com/60/4a76a8/ffffff?text=" . strtoupper(substr($user['username'] ?? '', 0, 1))) ?>" alt="Avatar">
                    <svg fill="currentColor" viewBox="0 0 20 20" :class="{'rotate-180': open, 'rotate-0': !open}" class="inline w-4 h-4 transition-transform duration-200 transform"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 w-full mt-2 origin-top-right rounded-md shadow-lg md:w-48">
                    <div class="py-2 bg-white text-blue-800 text-sm rounded-sm border border-main-color shadow-sm">
                        <a class="block px-4 py-2 mt-2 text-sm bg-white md:mt-0 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:shadow-outline" href="edit_profile.php">Settings</a>
                        <a class="block px-4 py-2 mt-2 text-sm bg-white md:mt-0 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:shadow-outline" href="#">Help</a>
                        <div class="border-b"></div>
                        <a class="block px-4 py-2 mt-2 text-sm bg-white md:mt-0 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:shadow-outline" href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>

<div class="container mx-auto my-5 p-5">
    <?php if (!empty($message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md" role="alert">
            <p class="font-bold">Thành công!</p>
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    <div class="md:flex no-wrap md:-mx-2 ">
        <div class="w-full md:w-3/12 md:mx-2">
            <div class="bg-white p-3 border-t-4 border-green-400 card shadow-md rounded-lg">
                <div class="image overflow-hidden text-center">
                    <img class="h-48 w-48 mx-auto rounded-full object-cover" src="<?= htmlspecialchars($user['avatar_url'] ?? "https://via.placeholder.com/200/4a76a8/ffffff?text=" . strtoupper(substr($user['username'] ?? '', 0, 1))) ?>" alt="User Avatar">
                </div>
                <h1 class="text-gray-900 font-bold text-2xl leading-8 my-2 text-center">
                    <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>
                </h1>
                <h3 class="text-gray-600 font-lg text-semibold leading-6 text-center">
                    <?= htmlspecialchars($user['meta_title'] ?? 'Chưa có vị trí nổi bật') ?>
                </h3>
                <p class="text-sm text-gray-500 hover:text-gray-600 leading-6 mt-2 text-center px-4">
                    <?= htmlspecialchars($user['meta_description'] ?? 'Chưa có mô tả.') ?>
                </p>
                <ul class="bg-gray-100 text-gray-600 py-2 px-3 mt-4 divide-y rounded shadow-sm">
                    <li class="flex items-center py-3">
                        <span><i class="fas fa-signal text-green-500 mr-2"></i> Trạng thái</span>
                        <span class="ml-auto">
                            <span class="bg-green-500 py-1 px-2 rounded text-white text-sm">
                                <?= htmlspecialchars(ucfirst($user['applicant_status'] ?? 'inactive')) ?>
                            </span>
                        </span>
                    </li>
                    <li class="flex items-center py-3">
                        <span><i class="fas fa-calendar-alt text-green-500 mr-2"></i> Ngày tham gia</span>
                        <span class="ml-auto">
                            <?= isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A' ?>
                        </span>
                    </li>
                </ul>
            </div>
            <div class="my-4"></div>
            <div class="bg-white p-3 card shadow-md rounded-lg">
                <div class="flex items-center space-x-3 font-semibold text-gray-900 text-xl leading-8 mb-4">
                    <span class="text-green-500">
                        <i class="fas fa-link"></i>
                    </span>
                    <span>Liên kết & Kỹ năng</span>
                </div>
                <ul class="list-inside space-y-2 text-sm text-gray-600 my-4">
                    <?php if (!empty($user['cv_url'])): ?>
                        <li><div class="text-teal-600 font-medium"><i class="fas fa-file-alt mr-2"></i> CV trực tuyến</div><div class="text-gray-500 text-xs truncate ml-6"><a href="<?= htmlspecialchars($user['cv_url']) ?>" target="_blank" class="text-blue-500 hover:underline"><?= htmlspecialchars($user['cv_url']) ?></a></div></li>
                    <?php endif; ?>
                    <?php if (!empty($user['portfolio_url'])): ?>
                        <li><div class="text-teal-600 font-medium"><i class="fas fa-globe mr-2"></i> Portfolio</div><div class="text-gray-500 text-xs truncate ml-6"><a href="<?= htmlspecialchars($user['portfolio_url']) ?>" target="_blank" class="text-blue-500 hover:underline"><?= htmlspecialchars($user['portfolio_url']) ?></a></div></li>
                    <?php endif; ?>
                    <?php if (empty($user['cv_url']) && empty($user['portfolio_url'])): ?>
                        <li class="text-gray-400">Chưa có liên kết nào được cung cấp.</li>
                    <?php endif; ?>
                </ul>
                <div class="flex flex-wrap gap-2 mt-4">
                    <?php if (!empty($skills)): ?>
                        <?php foreach($skills as $skill): ?>
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full card"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-gray-400 text-sm">Chưa có kỹ năng nào.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="w-full md:w-9/12 mx-2">
            <div class="bg-white p-3 shadow-md rounded-lg mb-4 card">
                <div class="flex items-center space-x-2 font-semibold text-gray-900 leading-8 mb-4 border-b pb-2">
                    <span class="text-green-500"><i class="fas fa-info-circle"></i></span>
                    <span class="tracking-wide text-xl">Thông tin cá nhân</span>
                </div>
                <div class="text-gray-700">
                    <div class="grid md:grid-cols-2 text-sm">
                        <div class="grid grid-cols-2">
                            <div class="px-4 py-2 font-semibold">Họ và Tên</div>
                            <div class="px-4 py-2"><?= htmlspecialchars($user['full_name'] ?? '-') ?></div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="px-4 py-2 font-semibold">Ngày sinh</div>
                            <div class="px-4 py-2"><?= ($user['date_of_birth'] ? date('d/m/Y', strtotime($user['date_of_birth'])) : '-') ?></div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="px-4 py-2 font-semibold">Giới tính</div>
                            <div class="px-4 py-2">
                                <?php
                                if(isset($user['gender'])) {
                                    if($user['gender'] == 'male') echo "Nam";
                                    elseif($user['gender'] == 'female') echo "Nữ";
                                    else echo "Khác";
                                } else {
                                    echo "-";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="px-4 py-2 font-semibold">Số điện thoại</div>
                            <div class="px-4 py-2"><?= htmlspecialchars($user['phone_number'] ?? '-') ?></div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="px-4 py-2 font-semibold">Địa chỉ</div>
                            <div class="px-4 py-2"><?= htmlspecialchars($user['address'] ?? '-') ?></div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="px-4 py-2 font-semibold">Email</div>
                            <div class="px-4 py-2"><a class="text-blue-800 hover:underline" href="mailto:<?= htmlspecialchars($user['email'] ?? '') ?>"><?= htmlspecialchars($user['email'] ?? '-') ?></a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white p-3 shadow-md rounded-lg mb-4 card">
                <div class="flex items-center space-x-2 font-semibold text-gray-900 leading-8 mb-4 border-b pb-2">
                    <span class="text-green-500"><i class="fas fa-file-alt"></i></span>
                    <span class="tracking-wide text-xl">Tóm tắt & Kinh nghiệm</span>
                </div>
                <div class="p-4 bg-gray-50 rounded-md mb-4">
                    <h4 class="font-bold text-gray-700 mb-2">Tóm tắt về bản thân</h4>
                    <p class="text-gray-700 text-sm">
                        <?= nl2br(htmlspecialchars($user['summary'] ?? 'Chưa có tóm tắt.')) ?>
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-gray-50 rounded-md">
                        <div class="flex items-center space-x-2 font-semibold text-gray-900 leading-8 mb-3">
                            <span class="text-green-500"><i class="fas fa-briefcase"></i></span>
                            <span class="tracking-wide">Kinh nghiệm làm việc</span>
                        </div>
                        <p class="text-gray-700 text-sm">
                            <?= nl2br(htmlspecialchars($user['experience_description'] ?? 'Chưa có kinh nghiệm được mô tả.')) ?>
                        </p>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-md">
                        <div class="flex items-center space-x-2 font-semibold text-gray-900 leading-8 mb-3">
                            <span class="text-green-500"><i class="fas fa-graduation-cap"></i></span>
                            <span class="tracking-wide">Học vấn</span>
                        </div>
                        <p class="text-gray-700 text-sm">
                            <?= nl2br(htmlspecialchars($user['education_details'] ?? 'Chưa có thông tin học vấn chi tiết.')) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-3 shadow-md rounded-lg mb-4 card">
                <div class="flex items-center space-x-2 font-semibold text-gray-900 leading-8 mb-4 border-b pb-2">
                    <span class="text-green-500"><i class="fas fa-project-diagram"></i></span>
                    <span class="tracking-wide text-xl">Dự án đã thực hiện</span>
                </div>
                <ul class="list-disc list-inside space-y-4 text-sm text-gray-600 p-4 bg-gray-50 rounded-md">
                    <?php if (!empty($projects)): ?>
                        <?php foreach($projects as $project): ?>
                            <li>
                                <div class="text-teal-600 font-semibold text-lg"><?= htmlspecialchars($project['name'] ?? 'N/A') ?></div>
                                <div class="text-gray-500 text-xs mt-1">
                                    <i class="fas fa-user-tag text-gray-400 mr-1"></i> Vai trò: <?= htmlspecialchars($project['role'] ?? 'N/A') ?>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-laptop-code text-gray-400 mr-1"></i> Công nghệ: <?= htmlspecialchars($project['tech'] ?? 'N/A') ?>
                                </div>
                                <p class="text-gray-600 mt-2">
                                    <i class="fas fa-comment-alt text-gray-400 mr-1"></i> Mô tả: <?= htmlspecialchars($project['description'] ?? 'N/A') ?>
                                </p>
                                <?php if (!empty($project['link'])): ?>
                                    <div class="text-xs mt-2"><a href="<?= htmlspecialchars($project['link']) ?>" target="_blank" class="text-blue-500 hover:underline"><i class="fas fa-external-link-alt mr-1"></i> Xem dự án</a></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-gray-400">Chưa có dự án nào được thêm.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="flex justify-end mt-6">
                <a href="edit_profile.php" class="btn bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    <i class="fas fa-edit mr-2"></i> Chỉnh sửa toàn bộ hồ sơ
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>