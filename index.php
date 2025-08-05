<?php
session_start();
require 'config.php';

// 1. TỰ ĐỘNG CHUYỂN HƯỚNG NẾU ĐÃ ĐĂNG NHẬP
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    if ($_SESSION['role'] === 'user') {
        header("Location: user/user_dashboard.php");
    } elseif ($_SESSION['role'] === 'company') {
        header("Location: company/company_dashboard.php");
    }
    exit();
}

// 2. LẤY NGẪU NHIÊN MỘT SỐ CÔNG TY ĐỂ HIỂN THỊ
$featured_companies = [];
$sql = "SELECT company_name, logo_url FROM companies WHERE logo_url IS NOT NULL AND logo_url != '' ORDER BY RAND() LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featured_companies[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng kết nối Doanh nghiệp & Sinh viên</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .cta-gradient { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .feature-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .feature-card:hover { transform: translateY(-10px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
        .company-logo { transition: all 0.3s ease; filter: grayscale(100%); }
        .company-logo:hover { filter: grayscale(0%); transform: scale(1.1); }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-extrabold text-indigo-600">Talent Pool</a>
            <div class="space-x-4">
                <a href="auth.php" class="text-gray-600 font-semibold hover:text-indigo-600 transition-colors">Đăng nhập</a>
                <a href="auth.php" class="bg-indigo-600 text-white font-semibold px-5 py-2 rounded-full hover:bg-indigo-700 transition-colors">Đăng ký</a>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero-gradient text-white">
            <div class="container mx-auto px-6 py-24 text-center">
                <h1 class="text-4xl md:text-6xl font-extrabold leading-tight">Kết Nối Tài Năng, Mở Lối Tương Lai</h1>
                <p class="mt-4 text-lg md:text-xl text-indigo-100 max-w-3xl mx-auto">
                    Nền tảng hàng đầu giúp sinh viên tìm kiếm cơ hội thực tập và việc làm mơ ước từ các doanh nghiệp uy tín hàng đầu.
                </p>
                <div class="mt-8 flex justify-center gap-4">
                    <a href="auth.php" class="bg-white text-indigo-600 font-bold px-8 py-4 rounded-full text-lg hover:bg-gray-100 transition-transform transform hover:scale-105">Tìm việc ngay</a>
                    <a href="auth.php" class="border-2 border-white text-white font-bold px-8 py-4 rounded-full text-lg hover:bg-white hover:text-indigo-600 transition-all">Đăng tin tuyển dụng</a>
                </div>
            </div>
        </section>

        <!-- Featured Companies -->
        <?php if (!empty($featured_companies)): ?>
        <section class="py-16 bg-gray-50">
            <div class="container mx-auto px-6 text-center">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-widest">Được tin tưởng bởi các doanh nghiệp hàng đầu</h3>
                <div class="mt-8 flex justify-center items-center flex-wrap gap-x-12 gap-y-8">
                    <?php foreach ($featured_companies as $company): ?>
                        <div class="tooltip">
                            <img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>" class="h-12 company-logo">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- How It Works Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">Quy trình hoạt động</h2>
                    <p class="mt-4 text-lg text-gray-600">Chỉ với vài bước đơn giản để bắt đầu hành trình của bạn.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-16">
                    <!-- For Applicants -->
                    <div class="text-center">
                        <i class="fas fa-user-graduate text-5xl text-indigo-500"></i>
                        <h3 class="mt-6 text-2xl font-bold text-gray-800">Dành cho Ứng viên</h3>
                        <div class="mt-8 space-y-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold text-lg">1</div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold">Tạo Hồ Sơ Chuyên Nghiệp</h4>
                                    <p class="mt-1 text-gray-500">Xây dựng profile ấn tượng, cập nhật kỹ năng và kinh nghiệm của bạn.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold text-lg">2</div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold">Tìm Kiếm & Ứng Tuyển</h4>
                                    <p class="mt-1 text-gray-500">Khám phá hàng ngàn cơ hội việc làm, thực tập phù hợp với chuyên ngành.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold text-lg">3</div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold">Kết Nối & Phát Triển</h4>
                                    <p class="mt-1 text-gray-500">Tương tác với nhà tuyển dụng, tham gia các sự kiện và phát triển sự nghiệp.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- For Companies -->
                    <div class="text-center">
                        <i class="fas fa-building text-5xl text-blue-500"></i>
                        <h3 class="mt-6 text-2xl font-bold text-gray-800">Dành cho Doanh nghiệp</h3>
                        <div class="mt-8 space-y-6">
                             <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-lg">1</div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold">Đăng Tin Tuyển Dụng</h4>
                                    <p class="mt-1 text-gray-500">Dễ dàng tạo và quản lý các tin đăng tuyển dụng, tiếp cận hàng ngàn ứng viên tiềm năng.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-lg">2</div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold">Tìm Kiếm & Sàng Lọc</h4>
                                    <p class="mt-1 text-gray-500">Sử dụng bộ lọc thông minh để tìm kiếm và sàng lọc hồ sơ ứng viên phù hợp nhất.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-lg">3</div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold">Quản Lý & Tương Tác</h4>
                                    <p class="mt-1 text-gray-500">Theo dõi quá trình ứng tuyển, lên lịch phỏng vấn và tương tác trực tiếp với ứng viên.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final Call to Action Section -->
        <section class="cta-gradient">
            <div class="container mx-auto px-6 py-20 text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold text-white">Sẵn sàng để bắt đầu hành trình của bạn?</h2>
                <p class="mt-4 text-lg text-blue-100 max-w-2xl mx-auto">
                    Tham gia cộng đồng của chúng tôi ngay hôm nay và đừng bỏ lỡ bất kỳ cơ hội nào.
                </p>
                <a href="auth.php" class="mt-8 inline-block bg-white text-blue-600 font-bold px-8 py-4 rounded-full text-lg hover:bg-gray-100 transition-transform transform hover:scale-105">
                    Đăng ký miễn phí
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="container mx-auto px-6 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center text-center md:text-left">
                <p class="text-sm">© <?= date("Y") ?> Talent Pool. All Rights Reserved.</p>
                <div class="mt-4 md:mt-0 flex space-x-6">
                    <a href="#" class="hover:text-indigo-400"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="hover:text-indigo-400"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="hover:text-indigo-400"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>