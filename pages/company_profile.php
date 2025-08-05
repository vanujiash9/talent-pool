<?php
session_start();
require_once "../config.php"; 

if (!isset($_SESSION["is_logged_in"]) || $_SESSION["is_logged_in"] !== true || $_SESSION["role"] !== 'company') {
    header("location: ../login.php");
    exit;
}

$company_id = $_SESSION["company_id"];
$company_info = [];

if (isset($conn) && $conn) {
    $sql = "SELECT c.*, cl.website_url, cl.contact_address, cl.headquarter, cl.city, cl.country, cl.facebook_url, cl.linkedin_url, cc.contact_email, cc.contact_phone, cc.hr_email, ct.founded_year
            FROM companies c
            LEFT JOIN company_locations cl ON c.company_id = cl.company_id
            LEFT JOIN company_contact cc ON c.company_id = cc.company_id
            LEFT JOIN company_timeline ct ON c.company_id = ct.company_id
            WHERE c.company_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $company_info = $result->fetch_assoc();
        }
        $stmt->close();
    }
    $conn->close();
} else {
    die("Lỗi: Không thể kết nối tới cơ sở dữ liệu.");
}

$logo_url = !empty($company_info['logo_url']) ? "../" . htmlspecialchars($company_info['logo_url']) : '../assets/images/placeholder_logo.png';
$is_vip = $company_info['is_VIP'] ?? false;
$brand_name = $company_info['brand_name'] ?? 'Tên thương hiệu';
$industry = $company_info['industry'] ?? 'Chưa cập nhật';

// Xử lý dòng tagline để hiển thị chính xác
$tagline = htmlspecialchars_decode($brand_name);
if (!empty($industry)) {
    $tagline .= " (" . htmlspecialchars_decode($industry) . ")";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ Doanh nghiệp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen bg-gray-50">
        <div class="w-64 bg-gray-800 text-white flex flex-col">
            <div class="px-6 py-4 border-b border-gray-700 text-center">
                <h1 class="text-2xl font-bold">Quản lý Công ty</h1>
            </div>
            <nav class="flex-1 px-2 py-4 space-y-2">
                <a href="company_dashboard.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                    <i class="fas fa-home w-5 h-5 mr-3"></i>
                    Bảng điều khiển
                </a>
                <a href="company_profile.php" class="flex items-center px-4 py-2 bg-gray-900 text-white rounded-md">
                    <i class="fas fa-building w-5 h-5 mr-3"></i>
                    Hồ sơ Công ty
                </a>
                <a href="company_post_job.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                    <i class="fas fa-plus-circle w-5 h-5 mr-3"></i>
                    Đăng tuyển dụng mới
                </a>
                <a href="company_manage_jobs.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                    <i class="fas fa-list-alt w-5 h-5 mr-3"></i>
                    Quản lý tin tuyển dụng
                </a>
            </nav>
            <div class="px-6 py-4 border-t border-gray-700">
                <a href="../logout.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                    <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                    Đăng xuất
                </a>
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex items-center justify-between p-6 bg-white border-b-2 border-gray-100">
                <div class="text-2xl font-semibold text-gray-800">
                    Hồ sơ Công ty
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Xin chào, <?= htmlspecialchars($_SESSION['username'] ?? 'Công ty') ?></span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden p-6 mb-8">
                        <div class="flex items-center space-x-6">
                            <img src="<?= $logo_url ?>" alt="Company Logo" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <h2 class="text-3xl font-extrabold text-gray-900">
                                        <?= htmlspecialchars($company_info['company_name'] ?? 'Tên công ty') ?>
                                    </h2>
                                    <?php if ($is_vip): ?>
                                        <i class="fas fa-medal text-yellow-500 text-xl" title="VIP Company"></i>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xl text-gray-600 mt-1">
                                    <?= htmlspecialchars_decode($brand_name) ?>
                                    <?php if (!empty($industry)): ?>
                                        (<?= htmlspecialchars_decode($industry) ?>)
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <a href="edit_company_profile.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition duration-300">
                                    <i class="fas fa-edit w-4 h-4 mr-2"></i>Chỉnh sửa hồ sơ
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
                                <i class="fas fa-building text-indigo-500 mr-2"></i>Giới thiệu
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Ngành nghề</p>
                                    <p class="text-md text-gray-900"><?= htmlspecialchars_decode($industry) ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Năm thành lập</p>
                                    <p class="text-md text-gray-900"><?= htmlspecialchars($company_info['founded_year'] ?? 'Chưa cập nhật') ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Giới thiệu chung</p>
                                    <p class="text-md text-gray-900 mt-1"><?= nl2br(htmlspecialchars($company_info['company_overview'] ?? 'Chưa có giới thiệu.')) ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Mô tả chi tiết</p>
                                    <p class="text-md text-gray-900 mt-1"><?= nl2br(htmlspecialchars($company_info['description'] ?? 'Chưa có mô tả.')) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
                                <i class="fas fa-phone-alt text-indigo-500 mr-2"></i>Liên hệ & Vị trí
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-envelope text-gray-500 w-5 h-5 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Email liên hệ</p>
                                        <p class="text-md text-gray-900"><?= htmlspecialchars($company_info['contact_email'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-envelope text-gray-500 w-5 h-5 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Email HR</p>
                                        <p class="text-md text-gray-900"><?= htmlspecialchars($company_info['hr_email'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-phone text-gray-500 w-5 h-5 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Số điện thoại</p>
                                        <p class="text-md text-gray-900"><?= htmlspecialchars($company_info['contact_phone'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-map-marker-alt text-gray-500 w-5 h-5 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Địa chỉ</p>
                                        <p class="text-md text-gray-900"><?= htmlspecialchars($company_info['contact_address'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-map-pin text-gray-500 w-5 h-5 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Trụ sở</p>
                                        <p class="text-md text-gray-900"><?= htmlspecialchars($company_info['headquarter'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-globe text-gray-500 w-5 h-5 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Vị trí</p>
                                        <p class="text-md text-gray-900"><?= htmlspecialchars($company_info['city'] ?? 'Chưa cập nhật') ?>, <?= htmlspecialchars($company_info['country'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                                <div class="pt-4 border-t border-gray-200">
                                    <p class="text-sm font-medium text-gray-600 mb-2">Social Media</p>
                                    <div class="flex space-x-4">
                                        <?php if (!empty($company_info['website_url'])): ?>
                                            <a href="<?= htmlspecialchars($company_info['website_url']) ?>" target="_blank" class="text-gray-500 hover:text-indigo-600 transition-colors">
                                                <i class="fas fa-link text-xl"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($company_info['facebook_url'])): ?>
                                            <a href="<?= htmlspecialchars($company_info['facebook_url']) ?>" target="_blank" class="text-gray-500 hover:text-blue-600 transition-colors">
                                                <i class="fab fa-facebook-square text-xl"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($company_info['linkedin_url'])): ?>
                                            <a href="<?= htmlspecialchars($company_info['linkedin_url']) ?>" target="_blank" class="text-gray-500 hover:text-blue-700 transition-colors">
                                                <i class="fab fa-linkedin text-xl"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>