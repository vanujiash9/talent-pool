<?php
// Bật hiển thị lỗi và bộ đệm đầu ra
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();

// Kết nối đến cơ sở dữ liệu
include "config.php";

// ==== 1. KHAI THÁC DỮ LIỆU TỔNG HỢP CHO TRANG CHỦ ====

// Thống kê số liệu
$stats = [
    'applicants' => 0,
    'jobs' => 0,
    'companies' => 0,
    'projects' => 0
];
$sql_stats = "SELECT (SELECT COUNT(*) FROM applicants) AS applicants, (SELECT COUNT(*) FROM jobs) AS jobs, (SELECT COUNT(*) FROM companies) AS companies, (SELECT COUNT(*) FROM projects) AS projects";
$result_stats = $conn->query($sql_stats);
if ($result_stats) {
    $stats = $result_stats->fetch_assoc();
}

// Công việc nổi bật (Giả định bảng `jobs` có `company_id` để liên kết với `companies`)
$featured_jobs = [];
$sql_jobs = "
    SELECT
        j.job_name, j.average_salary, j.field, j.job_description,
        c.company_name, c.logo_url
    FROM jobs j
    LEFT JOIN companies c ON j.company_id = c.company_id
    ORDER BY j.created_at DESC
    LIMIT 6
";
$result_jobs = $conn->query($sql_jobs);
if ($result_jobs) {
    $featured_jobs = $result_jobs->fetch_all(MYSQLI_ASSOC);
}

// Talent nổi bật (có xếp hạng)
$featured_talents = [];
$sql_talents = "
    SELECT
        a.full_name, ap.profile_picture_url, ap.meta_title, t.rating
    FROM talents t
    JOIN applicants a ON t.applicant_id = a.applicant_id
    LEFT JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    ORDER BY t.rating DESC
    LIMIT 4
";
$result_talents = $conn->query($sql_talents);
if ($result_talents) {
    $featured_talents = $result_talents->fetch_all(MYSQLI_ASSOC);
}

// Công ty nổi bật
$featured_companies = [];
// Dòng code lỗi ở đây: WHERE is_VIP = TRUE OR active_jobs_count > 0
// Đã thay đổi để sử dụng các trường có sẵn trong database của bạn
$sql_companies = "
    SELECT
        company_name, industry, logo_url
    FROM companies
    WHERE is_VIP = TRUE
    ORDER BY company_id DESC
    LIMIT 8
";
$result_companies = $conn->query($sql_companies);
if ($result_companies) {
    $featured_companies = $result_companies->fetch_all(MYSQLI_ASSOC);
}

// Bài viết mới nhất
$latest_posts = [];
$sql_posts = "
    SELECT
        p.content, p.created_at, p.post_type,
        COALESCE(c.company_name, a.full_name) AS author_name,
        COALESCE(c.logo_url, ap.profile_picture_url) AS author_avatar
    FROM posts p
    LEFT JOIN companies c ON p.company_id = c.company_id
    LEFT JOIN applicants a ON p.applicant_id = a.applicant_id
    LEFT JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    ORDER BY p.created_at DESC
    LIMIT 4
";
$result_posts = $conn->query($sql_posts);
if ($result_posts) {
    $latest_posts = $result_posts->fetch_all(MYSQLI_ASSOC);
}

// Sự kiện sắp tới
$upcoming_events = [];
$sql_events = "
    SELECT
        event_name, event_date, location, event_format, notes
    FROM connect_events
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC
    LIMIT 3
";
$result_events = $conn->query($sql_events);
if ($result_events) {
    $upcoming_events = $result_events->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>Talent Pool - Kết nối Nhân tài & Doanh nghiệp</title>
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
                <a href="index.php" class="text-indigo-600 font-bold text-2xl">Talent Pool</a>
                <nav class="hidden md:flex space-x-8">
                    <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors">Trang chủ</a>
                    <a href="#featured-jobs" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors">Việc làm</a>
                    <a href="#featured-companies" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors">Công ty</a>
                    <a href="#featured-talents" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors">Talent</a>
                    <a href="#events" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors">Sự kiện</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['is_logged_in'])): ?>
                        <a href="user_dashboard.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition-colors">
                            Hồ sơ của tôi
                        </a>
                        <a href="logout.php" class="text-gray-500 hover:text-red-500" title="Đăng xuất"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    <?php else: ?>
                        <a href="login.php" class="text-indigo-600 hover:bg-indigo-50 font-semibold px-4 py-2 rounded-lg transition-colors">Đăng nhập</a>
                        <a href="register.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition-colors">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8 mt-6">

        <section class="bg-white rounded-xl shadow-lg p-8 sm:p-12 lg:p-16 mb-8 text-center" style="background-image: url('https://source.unsplash.com/1600x900/?office,teamwork'); background-size: cover; background-position: center;">
            <div class="relative z-10 p-8 rounded-xl bg-white bg-opacity-80 backdrop-blur-sm">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight">
                    Tìm kiếm <span class="text-indigo-600">Công việc mơ ước</span> <br class="hidden lg:block"> hoặc <span class="text-indigo-600">Nhân tài xuất sắc</span>
                </h1>
                <p class="mt-4 text-base sm:text-lg text-gray-700">Nền tảng kết nối nhân tài và doanh nghiệp hàng đầu Việt Nam.</p>

                <form action="search.php" method="GET" class="mt-8 max-w-2xl mx-auto flex items-center bg-white rounded-full shadow-lg p-2">
                    <i class="fas fa-search text-gray-400 ml-4"></i>
                    <input type="text" name="query" placeholder="Tìm kiếm công việc, công ty, kỹ năng..." class="flex-grow p-3 bg-transparent outline-none text-gray-800">
                    <button type="submit" class="bg-indigo-600 text-white font-bold py-3 px-8 rounded-full hover:bg-indigo-700 transition-colors">Tìm kiếm</button>
                </form>
            </div>
        </section>

        <section class="mb-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <i class="fas fa-users text-4xl text-indigo-500 mb-2"></i>
                    <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['applicants']) ?></p>
                    <p class="text-gray-500">Ứng viên</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <i class="fas fa-briefcase text-4xl text-green-500 mb-2"></i>
                    <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['jobs']) ?></p>
                    <p class="text-gray-500">Công việc</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <i class="fas fa-building text-4xl text-amber-500 mb-2"></i>
                    <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['companies']) ?></p>
                    <p class="text-gray-500">Công ty</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <i class="fas fa-project-diagram text-4xl text-red-500 mb-2"></i>
                    <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['projects']) ?></p>
                    <p class="text-gray-500">Dự án</p>
                </div>
            </div>
        </section>

        <section id="featured-jobs" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-gray-900">Công việc nổi bật</h2>
                <a href="#" class="text-indigo-600 font-semibold hover:underline">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (!empty($featured_jobs)): foreach($featured_jobs as $job): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <img src="<?= htmlspecialchars($job['logo_url'] ?? 'https://via.placeholder.com/40') ?>" alt="<?= htmlspecialchars($job['company_name'] ?? 'Công ty') ?>" class="w-10 h-10 rounded-full object-cover mr-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($job['job_name']) ?></h3>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($job['company_name'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 leading-relaxed line-clamp-3"><?= htmlspecialchars($job['job_description']) ?></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-1 rounded-full"><i class="fas fa-money-bill-wave mr-1"></i> <?= number_format($job['average_salary']) ?> VNĐ</span>
                        <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-1 rounded-full"><i class="fas fa-tags mr-1"></i> <?= htmlspecialchars($job['field']) ?></span>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <p class="text-gray-500 italic">Hiện chưa có công việc nào.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="featured-talents" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-gray-900">Talent nổi bật</h2>
                <a href="#" class="text-indigo-600 font-semibold hover:underline">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if (!empty($featured_talents)): foreach($featured_talents as $talent): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center hover:shadow-xl transition-shadow duration-300">
                    <img src="<?= htmlspecialchars($talent['profile_picture_url'] ?? 'https://via.placeholder.com/100') ?>" alt="<?= htmlspecialchars($talent['full_name']) ?>" class="w-24 h-24 rounded-full object-cover mx-auto mb-3 border-4 border-amber-200">
                    <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($talent['full_name']) ?></h3>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-1"><?= htmlspecialchars($talent['meta_title']) ?></p>
                    <div class="mt-3">
                        <span class="text-amber-500 font-bold"><?= number_format($talent['rating'] ?? 0.00, 2) ?></span>
                        <i class="fas fa-star text-amber-500"></i>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <p class="text-gray-500 italic">Chưa có talent nào nổi bật.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="featured-companies" class="mb-8 bg-white rounded-xl shadow-lg p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-gray-900">Công ty hàng đầu</h2>
                <a href="#" class="text-indigo-600 font-semibold hover:underline">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 items-center">
                <?php if (!empty($featured_companies)): foreach($featured_companies as $company): ?>
                <div class="p-3 text-center transition-transform transform hover:scale-105 duration-300">
                    <img src="<?= htmlspecialchars($company['logo_url'] ?? 'https://via.placeholder.com/64') ?>" alt="<?= htmlspecialchars($company['company_name']) ?>" class="w-16 h-16 object-contain mx-auto">
                    <p class="mt-2 text-xs font-semibold text-gray-700 line-clamp-2"><?= htmlspecialchars($company['company_name']) ?></p>
                </div>
                <?php endforeach; else: ?>
                    <p class="text-gray-500 italic">Chưa có công ty nào.</p>
                <?php endif; ?>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900">Bài viết mới</h2>
                    <a href="#" class="text-indigo-600 font-semibold hover:underline">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
                </div>
                <div class="space-y-4">
                    <?php if (!empty($latest_posts)): foreach($latest_posts as $post): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center mb-3">
                            <img src="<?= htmlspecialchars($post['author_avatar'] ?? 'https://via.placeholder.com/40') ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover mr-4">
                            <div>
                                <p class="font-bold text-gray-900"><?= htmlspecialchars($post['author_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= date('d M, Y', strtotime($post['created_at'])) ?></p>
                            </div>
                        </div>
                        <p class="text-gray-700 text-sm leading-relaxed line-clamp-3"><?= htmlspecialchars($post['content']) ?></p>
                    </div>
                    <?php endforeach; else: ?>
                        <p class="text-gray-500 italic">Chưa có bài viết nào.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section id="events">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900">Sự kiện sắp tới</h2>
                    <a href="#" class="text-indigo-600 font-semibold hover:underline">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
                </div>
                <div class="space-y-4">
                    <?php if (!empty($upcoming_events)): foreach($upcoming_events as $event): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-12 h-12 bg-indigo-500 text-white rounded-md flex flex-col items-center justify-center">
                                <span class="text-lg font-bold"><?= date('d', strtotime($event['event_date'])) ?></span>
                                <span class="text-xs -mt-1"><?= date('M', strtotime($event['event_date'])) ?></span>
                            </div>
                            <div class="ml-4 flex-grow">
                                <h3 class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($event['event_name']) ?></h3>
                                <p class="text-sm text-gray-600 mt-1"><i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars($event['location']) ?></p>
                                <p class="text-xs text-gray-500 mt-2 line-clamp-2"><?= htmlspecialchars($event['notes']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                        <p class="text-gray-500 italic">Chưa có sự kiện nào sắp diễn ra.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="bg-indigo-600 text-white rounded-xl shadow-lg p-8 sm:p-12 lg:p-16 text-center">
            <h2 class="text-3xl sm:text-4xl font-extrabold mb-4">Bạn là nhà tuyển dụng?</h2>
            <p class="text-lg opacity-80 mb-6">Hãy tìm kiếm nhân tài chất lượng cao và đăng tin tuyển dụng miễn phí ngay hôm nay!</p>
            <a href="register.php?type=company" class="bg-white text-indigo-600 font-bold px-8 py-4 rounded-full shadow-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-building mr-2"></i>Đăng ký cho doanh nghiệp
            </a>
        </section>

    </main>

    <footer class="bg-gray-800 text-gray-300 py-12 mt-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-white text-2xl font-bold mb-4">Talent Pool</h3>
                    <p class="text-sm">Nền tảng kết nối nhân tài và cơ hội việc làm tốt nhất.</p>
                    <div class="flex space-x-4 mt-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook fa-2x"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin fa-2x"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter fa-2x"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-bold text-lg mb-4">Menu</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition-colors">Trang chủ</a></li>
                        <li><a href="#featured-jobs" class="hover:text-white transition-colors">Việc làm</a></li>
                        <li><a href="#featured-companies" class="hover:text-white transition-colors">Công ty</a></li>
                        <li><a href="#featured-talents" class="hover:text-white transition-colors">Talent</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-lg mb-4">Liên hệ</h4>
                    <p class="text-sm">Email: contact@talentpool.vn</p>
                    <p class="text-sm">Phone: 028 123 4567</p>
                    <p class="text-sm mt-2">Địa chỉ: 123 Đường ABC, Quận 1, TP. Hồ Chí Minh</p>
                </div>
            </div>
            <div class="text-center text-sm mt-8 border-t border-gray-700 pt-6">
                &copy; 2025 Talent Pool. All Rights Reserved.
            </div>
        </div>
    </footer>

</body>
</html>