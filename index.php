<?php
// index.php
session_start();
require 'config.php';

// Lấy một số công ty để hiển thị
$featured_companies = [];
$sql_companies = "SELECT company_name, logo_url FROM companies WHERE logo_url IS NOT NULL AND logo_url != '' ORDER BY RAND() LIMIT 6";
$result_companies = $conn->query($sql_companies);
if ($result_companies) {
    while ($row = $result_companies->fetch_assoc()) {
        $featured_companies[] = $row;
    }
}

// Lấy các số liệu thống kê chi tiết hơn
$stats = [
    'jobs' => 0,
    'companies' => 0,
    'applicants' => 0,
    'universities' => 0,
    'active_jobs' => 0,
    'total_applications' => 0
];

// Thống kê chi tiết
$stats['jobs'] = $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'] ?? 0;
$stats['companies'] = $conn->query("SELECT COUNT(*) as count FROM companies")->fetch_assoc()['count'] ?? 0;
$stats['applicants'] = $conn->query("SELECT COUNT(*) as count FROM applicants")->fetch_assoc()['count'] ?? 0;
$stats['universities'] = $conn->query("SELECT COUNT(*) as count FROM universities")->fetch_assoc()['count'] ?? 0;
$stats['active_jobs'] = $conn->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;

// Lấy các việc làm mới nhất
$latest_jobs = [];
$sql_latest_jobs = "SELECT j.*, c.company_name, c.logo_url 
                    FROM jobs j 
                    JOIN companies c ON j.company_id = c.company_id 
                    WHERE j.status = 'active' 
                    ORDER BY j.created_at DESC 
                    LIMIT 6";
$result_latest_jobs = $conn->query($sql_latest_jobs);
if ($result_latest_jobs) {
    while ($row = $result_latest_jobs->fetch_assoc()) {
        $latest_jobs[] = $row;
    }
}

// Lấy các bài đăng nổi bật từ cộng đồng
$featured_posts = [];
$sql_featured_posts = "SELECT p.*, c.company_name, c.logo_url, a.full_name, a.profile_picture_url
                       FROM posts p
                       LEFT JOIN companies c ON p.company_id = c.company_id
                       LEFT JOIN applicants a ON p.applicant_id = a.applicant_id
                       WHERE p.post_type IN ('achievement', 'project_showcase', 'career_tip')
                       ORDER BY p.created_at DESC
                       LIMIT 3";
$result_featured_posts = $conn->query($sql_featured_posts);
if ($result_featured_posts) {
    while ($row = $result_featured_posts->fetch_assoc()) {
        $featured_posts[] = $row;
    }
}

// Lấy các kỹ năng phổ biến nhất
$top_skills = [];
$sql_top_skills = "SELECT s.skill_name, COUNT(*) as demand_count
                   FROM skills s
                   JOIN applicant_skills aps ON s.skill_id = aps.skill_id
                   GROUP BY s.skill_id, s.skill_name
                   ORDER BY demand_count DESC
                   LIMIT 8";
$result_top_skills = $conn->query($sql_top_skills);
if ($result_top_skills) {
    while ($row = $result_top_skills->fetch_assoc()) {
        $top_skills[] = $row;
    }
}

// Lấy các lĩnh vực việc làm phổ biến
$popular_fields = [];
$sql_popular_fields = "SELECT field, COUNT(*) as job_count
                       FROM jobs 
                       WHERE status = 'active'
                       GROUP BY field 
                       ORDER BY job_count DESC 
                       LIMIT 6";
$result_popular_fields = $conn->query($sql_popular_fields);
if ($result_popular_fields) {
    while ($row = $result_popular_fields->fetch_assoc()) {
        $popular_fields[] = $row;
    }
}

$conn->close();
?>
<?php include 'templates/header.php'; // Gọi file header chung ?>

<main>
    <!-- Hero Section -->
    <section class="relative bg-white overflow-hidden">
        <div class="container mx-auto px-6 lg:px-8 py-20 lg:py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl md:text-6xl font-black text-gray-900 leading-tight">
                        Nơi Tài Năng <span class="text-primary">Gặp Gỡ</span> Cơ Hội
                    </h1>
                    <p class="mt-6 text-lg text-gray-600 max-w-xl mx-auto lg:mx-0">
                        Khám phá hàng ngàn cơ hội việc làm và thực tập. Xây dựng hồ sơ chuyên nghiệp và để các nhà tuyển dụng hàng đầu tìm thấy bạn.
                    </p>
                    
                    <!-- Search Bar -->
                    <div class="mt-8 max-w-md mx-auto lg:mx-0">
                        <form action="pages/posts.php" method="GET" class="flex flex-col sm:flex-row gap-2">
                            <input type="text" name="search" placeholder="Tìm kiếm việc làm, kỹ năng..." 
                                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition-colors">
                                <i class="fas fa-search mr-2"></i>Tìm kiếm
                            </button>
                        </form>
                    </div>
                    
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="auth.php?action=signup" class="w-full sm:w-auto inline-block text-center bg-primary text-white font-bold px-8 py-4 rounded-lg text-lg hover:bg-primary/90 transition-transform transform hover:scale-105">
                            Bắt đầu ngay
                        </a>
                        <a href="#features" class="w-full sm:w-auto inline-block text-center text-gray-700 font-bold px-8 py-4 rounded-lg text-lg hover:text-primary transition-all">
                            Tìm hiểu thêm <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block relative">
                    <img src="https://meraki-agency.com/wp-content/uploads/2023/07/WEB-DESIGN-ILLUSTRATION-01-1.png" alt="Illustration of people connecting" class="rounded-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats-section" class="bg-gray-50 py-16">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8 text-center">
                <div class="p-4">
                    <h3 class="text-4xl font-extrabold text-primary" data-target="<?= $stats['active_jobs'] ?>">0</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">Việc làm đang tuyển</p>
                </div>
                <div class="p-4">
                    <h3 class="text-4xl font-extrabold text-primary" data-target="<?= $stats['companies'] ?>">0</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">Doanh nghiệp</p>
                </div>
                <div class="p-4">
                    <h3 class="text-4xl font-extrabold text-primary" data-target="<?= $stats['applicants'] ?>">0</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">Ứng viên</p>
                </div>
                <div class="p-4">
                    <h3 class="text-4xl font-extrabold text-primary" data-target="<?= $stats['universities'] ?>">0</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">Trường đại học</p>
                </div>
                <div class="p-4">
                    <h3 class="text-4xl font-extrabold text-primary" data-target="<?= $stats['jobs'] ?>">0</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">Tổng việc làm</p>
                </div>
                <div class="p-4">
                    <h3 class="text-4xl font-extrabold text-primary" data-target="<?= $stats['total_applications'] ?>">0</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">Đơn ứng tuyển</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Jobs Section -->
    <?php if (!empty($latest_jobs)): ?>
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900">Việc Làm Mới Nhất</h2>
                <p class="mt-4 text-lg text-gray-600">Khám phá những cơ hội việc làm hấp dẫn nhất</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($latest_jobs as $job): ?>
                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <?php if ($job['logo_url']): ?>
                            <img src="<?= htmlspecialchars($job['logo_url']) ?>" alt="<?= htmlspecialchars($job['company_name']) ?>" class="w-12 h-12 rounded-lg object-cover mr-4">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-building text-primary"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($job['company_name']) ?></h3>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($job['field']) ?></p>
                        </div>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($job['job_name']) ?></h4>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?= htmlspecialchars(substr($job['job_description'], 0, 120)) ?>...</p>
                    <?php if ($job['average_salary']): ?>
                        <p class="text-primary font-semibold mb-4"><?= number_format($job['average_salary'], 0, ',', '.') ?> VNĐ/tháng</p>
                    <?php endif; ?>
                    <div class="flex justify-between items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <?= $job['status'] === 'active' ? 'Đang tuyển' : 'Tạm dừng' ?>
                        </span>
                        <a href="auth.php?action=login" class="text-primary hover:text-primary/80 font-medium text-sm">
                            Xem chi tiết <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-12">
                <a href="auth.php?action=login" class="inline-flex items-center px-6 py-3 border border-primary text-primary font-medium rounded-lg hover:bg-primary hover:text-white transition-colors">
                    Xem tất cả việc làm <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular Fields Section -->
    <?php if (!empty($popular_fields)): ?>
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900">Lĩnh Vực Việc Làm Phổ Biến</h2>
                <p class="mt-4 text-lg text-gray-600">Khám phá các ngành nghề đang có nhu cầu cao</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($popular_fields as $field): ?>
                <div class="bg-white rounded-lg p-6 text-center hover:shadow-md transition-shadow cursor-pointer">
                    <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-briefcase text-primary text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($field['field']) ?></h3>
                    <p class="text-primary font-bold text-lg"><?= $field['job_count'] ?></p>
                    <p class="text-xs text-gray-500">việc làm</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Companies -->
    <?php if (!empty($featured_companies)): ?>
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">Đối Tác Hàng Đầu Của Chúng Tôi</h2>
            <p class="mt-4 text-lg text-gray-600">Những doanh nghiệp luôn tìm kiếm tài năng trẻ.</p>
            <div class="mt-12 flex justify-center items-center flex-wrap gap-x-16 gap-y-8">
                <?php foreach ($featured_companies as $company): ?>
                    <img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>" class="h-12 company-logo transition-all duration-300 filter grayscale hover:grayscale-0" title="<?= htmlspecialchars($company['company_name']) ?>">
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Top Skills Section -->
    <?php if (!empty($top_skills)): ?>
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900">Kỹ Năng Được Tìm Kiếm Nhiều Nhất</h2>
                <p class="mt-4 text-lg text-gray-600">Những kỹ năng đang được các nhà tuyển dụng quan tâm</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($top_skills as $skill): ?>
                <div class="bg-white rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                    <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-star text-primary"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($skill['skill_name']) ?></h3>
                    <p class="text-primary font-bold"><?= $skill['demand_count'] ?></p>
                    <p class="text-xs text-gray-500">ứng viên</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Posts Section -->
    <?php if (!empty($featured_posts)): ?>
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900">Cộng Đồng Talent Pool</h2>
                <p class="mt-4 text-lg text-gray-600">Những chia sẻ và thành tựu từ cộng đồng</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($featured_posts as $post): ?>
                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <?php if ($post['company_id'] && $post['logo_url']): ?>
                            <img src="<?= htmlspecialchars($post['logo_url']) ?>" alt="<?= htmlspecialchars($post['company_name']) ?>" class="w-10 h-10 rounded-lg object-cover mr-3">
                        <?php elseif ($post['applicant_id'] && $post['profile_picture_url']): ?>
                            <img src="<?= htmlspecialchars($post['profile_picture_url']) ?>" alt="<?= htmlspecialchars($post['full_name']) ?>" class="w-10 h-10 rounded-lg object-cover mr-3">
                        <?php else: ?>
                            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3 class="font-semibold text-gray-900 text-sm">
                                <?= htmlspecialchars($post['company_name'] ?? $post['full_name'] ?? 'Thành viên') ?>
                            </h3>
                            <p class="text-xs text-gray-500">
                                <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm line-clamp-4"><?= htmlspecialchars(substr($post['content'], 0, 150)) ?>...</p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?= ucfirst(str_replace('_', ' ', $post['post_type'])) ?>
                        </span>
                        <a href="auth.php?action=login" class="text-primary hover:text-primary/80 text-sm">
                            Đọc thêm
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">Tại sao nên chọn Talent Pool?</h2>
                <p class="mt-4 text-lg text-gray-600">Chúng tôi mang đến những công cụ tốt nhất để bạn thành công.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card bg-white p-8 rounded-lg text-center border border-gray-200">
                    <div class="h-16 w-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto text-3xl">
                        <i class="fas fa-search-plus"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-800">Tìm kiếm thông minh</h3>
                    <p class="mt-2 text-gray-500">Hệ thống gợi ý và bộ lọc mạnh mẽ giúp bạn tìm thấy cơ hội hoặc ứng viên phù hợp nhất.</p>
                </div>
                <div class="feature-card bg-white p-8 rounded-lg text-center border border-gray-200">
                    <div class="h-16 w-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto text-3xl">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-800">Hồ sơ chuyên nghiệp</h3>
                    <p class="mt-2 text-gray-500">Xây dựng hồ sơ năng lực ấn tượng, thể hiện toàn bộ kỹ năng và kinh nghiệm của bạn.</p>
                </div>
                <div class="feature-card bg-white p-8 rounded-lg text-center border border-gray-200">
                    <div class="h-16 w-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto text-3xl">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-800">Kết nối trực tiếp</h3>
                    <p class="mt-2 text-gray-500">Tương tác, trao đổi và lên lịch phỏng vấn với nhà tuyển dụng ngay trên nền tảng.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-20 bg-primary">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-6">Sẵn sàng bắt đầu hành trình mới?</h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Tham gia cộng đồng Talent Pool ngay hôm nay và khám phá những cơ hội việc làm tuyệt vời
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="auth.php?action=signup&role=applicant" class="w-full sm:w-auto inline-block text-center bg-white text-primary font-bold px-8 py-4 rounded-lg text-lg hover:bg-gray-100 transition-colors">
                    Đăng ký ứng viên
                </a>
                <a href="auth.php?action=signup&role=company" class="w-full sm:w-auto inline-block text-center border-2 border-white text-white font-bold px-8 py-4 rounded-lg text-lg hover:bg-white hover:text-primary transition-colors">
                    Đăng ký doanh nghiệp
                </a>
            </div>
        </div>
    </section>
</main>

<footer class="bg-gray-900 text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="font-bold text-lg mb-4">Talent Pool</h3>
                <p class="text-gray-400 text-sm">Nền tảng kết nối tài năng trẻ với các doanh nghiệp hàng đầu.</p>
            </div>
            <div>
                <h3 class="font-semibold mb-4">Dành cho Ứng viên</h3>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-white">Tìm việc làm</a></li>
                    <li><a href="#" class="hover:text-white">Tạo hồ sơ</a></li>
                    <li><a href="#" class="hover:text-white">Hướng dẫn</a></li>
                </ul>
            </div>
             <div>
                <h3 class="font-semibold mb-4">Dành cho Doanh nghiệp</h3>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-white">Đăng tin</a></li>
                    <li><a href="#" class="hover:text-white">Tìm ứng viên</a></li>
                    <li><a href="#" class="hover:text-white">Bảng giá</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-4">Kết nối với chúng tôi</h3>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white text-xl"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white text-xl"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white text-xl"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="mt-12 border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
            <p>© <?= date("Y") ?> Talent Pool. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Animated Counter Logic ---
    const statsSection = document.getElementById('stats-section');
    if (statsSection) {
        const counters = statsSection.querySelectorAll('h3[data-target]');
        const animateCounter = (element) => {
            const target = +element.getAttribute('data-target');
            if (target === 0) return; // Không cần chạy nếu giá trị là 0
            const duration = 2000;
            const stepTime = 20;
            const totalSteps = duration / stepTime;
            const increment = target / totalSteps;
            let current = 0;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.innerText = target.toLocaleString('vi-VN');
                    clearInterval(timer);
                } else {
                    element.innerText = Math.ceil(current).toLocaleString('vi-VN');
                }
            }, stepTime);
        };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    counters.forEach(counter => animateCounter(counter));
                    observer.unobserve(statsSection);
                }
            });
        }, { threshold: 0.5 });
        observer.observe(statsSection);
    }
});
</script>
<style>
    .feature-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .feature-card:hover { transform: translateY(-10px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-4 { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
</style>
</body>
</html>