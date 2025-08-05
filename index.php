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

// Lấy các số liệu thống kê
$stats = [
    'jobs' => 0,
    'companies' => 0,
    'applicants' => 0
];

// DÒNG ĐÃ ĐƯỢC SỬA LỖI: 'trạng thái' -> 'status'
$stats['jobs'] = $conn->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
$stats['companies'] = $conn->query("SELECT COUNT(*) as count FROM companies")->fetch_assoc()['count'] ?? 0;
$stats['applicants'] = $conn->query("SELECT COUNT(*) as count FROM applicants")->fetch_assoc()['count'] ?? 0;

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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-4">
                    <h3 class="text-5xl font-extrabold text-primary" data-target="<?= $stats['jobs'] ?>">0</h3>
                    <p class="mt-2 text-lg font-medium text-gray-500">Việc làm đang tuyển</p>
                </div>
                <div class="p-4">
                    <h3 class="text-5xl font-extrabold text-primary" data-target="<?= $stats['companies'] ?>">0</h3>
                    <p class="mt-2 text-lg font-medium text-gray-500">Doanh nghiệp tham gia</p>
                </div>
                <div class="p-4">
                    <h3 class="text-5xl font-extrabold text-primary" data-target="<?= $stats['applicants'] ?>">0</h3>
                    <p class="mt-2 text-lg font-medium text-gray-500">Hồ sơ ứng viên</p>
                </div>
            </div>
        </div>
    </section>

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
</style>
</body>
</html>