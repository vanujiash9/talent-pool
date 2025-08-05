<?php
session_start();
require_once "../config.php"; 

if (!isset($_SESSION["is_logged_in"]) || $_SESSION["is_logged_in"] !== true) {
    header("location: ../login.php");
    exit;
}

$company_id = $_SESSION["role"] === 'company' ? $_SESSION["company_id"] : null;
$applicant_id = $_SESSION["role"] === 'applicant' ? $_SESSION["user_id"] : null;
$poster_type = $_SESSION["role"];

$msg = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = trim($_POST['content'] ?? '');
    $post_type = trim($_POST['post_type'] ?? 'personal_update');

    if (empty($content)) {
        $errors['content'] = 'Nội dung bài đăng không được để trống.';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO posts (company_id, applicant_id, content, post_type) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iiss", $company_id, $applicant_id, $content, $post_type);
            
            if ($stmt->execute()) {
                header("location: posts.php");
                exit;
            } else {
                $msg = "Có lỗi xảy ra, vui lòng thử lại: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $msg = "Lỗi khi chuẩn bị câu lệnh SQL: " . $conn->error;
        }
    } else {
        $msg = "Vui lòng sửa các lỗi trong form.";
    }
}

// Lấy tất cả các bài đăng để hiển thị
$posts = [];
$sql_posts = "SELECT p.*, c.company_name, c.logo_url, a.full_name, a.profile_picture_url
              FROM posts p
              LEFT JOIN companies c ON p.company_id = c.company_id
              LEFT JOIN applicants a ON p.applicant_id = a.applicant_id
              ORDER BY p.created_at DESC";

$result_posts = $conn->query($sql_posts);
if ($result_posts) {
    while ($row = $result_posts->fetch_assoc()) {
        $posts[] = $row;
    }
}
if (isset($conn) && $conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Tin - Talent Pool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .main-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .sidebar-gradient {
            background: linear-gradient(180deg, #1a202c 0%, #2d3748 100%);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        
        .post-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-radius: 24px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .post-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .create-post-gradient {
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.9) 0%, 
                rgba(255, 255, 255, 0.7) 100%);
        }
        
        .avatar-glow {
            position: relative;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 300% 300%;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-gradient:hover::before {
            left: 100%;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .sidebar-link {
            position: relative;
            transition: all 0.3s ease;
            border-radius: 16px;
            margin: 4px 0;
        }
        
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(8px);
        }
        
        .sidebar-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .floating-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .floating-btn:hover {
            transform: scale(1.1) rotate(360deg);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.6);
        }
        
        .input-modern {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 16px 20px;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .input-modern:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .post-type-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-button {
            background: transparent;
            border: none;
            color: #64748b;
            padding: 12px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }
        
        .action-button:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #334155;
            transform: translateY(-2px);
        }
        
        .scrollbar-hidden {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .scrollbar-hidden::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .company-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 4px;
            border-radius: 50%;
        }
        
        .applicant-icon {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: white;
            padding: 4px;
            border-radius: 50%;
        }
        
        .header-glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .notification-dot {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 12px;
            height: 12px;
            background: #E30417;
            border-radius: 50%;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="main-gradient min-h-screen">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-72 sidebar-gradient text-white flex flex-col">
            <div class="px-8 py-8 border-b border-gray-600">
                <div class="text-center">
                    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-400 mb-2">
                        Talent Pool
                    </h1>
                    <div class="h-1 w-20 mx-auto bg-gradient-to-r from-blue-400 to-purple-400 rounded-full"></div>
                </div>
            </div>
            
            <nav class="flex-1 px-6 py-8 space-y-2">
                <?php if ($poster_type == 'company'): ?>
                    <a href="company_dashboard.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                        <i class="fas fa-tachometer-alt w-5 h-5 mr-4"></i>
                        <span class="font-medium">Bảng điều khiển</span>
                    </a>
                    <a href="company_profile.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                        <i class="fas fa-building w-5 h-5 mr-4"></i>
                        <span class="font-medium">Hồ sơ Công ty</span>
                    </a>
                    <a href="posts.php" class="sidebar-link active flex items-center px-6 py-4 text-white">
                        <i class="fas fa-bullhorn w-5 h-5 mr-4"></i>
                        <span class="font-medium">Bài đăng</span>
                        <div class="notification-dot"></div>
                    </a>
                    <a href="job_management.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                        <i class="fas fa-briefcase w-5 h-5 mr-4"></i>
                        <span class="font-medium">Quản lý Việc làm</span>
                    </a>
                <?php else: ?>
                    <a href="user_dashboard.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                        <i class="fas fa-tachometer-alt w-5 h-5 mr-4"></i>
                        <span class="font-medium">Bảng điều khiển</span>
                    </a>
                    <a href="profile.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                        <i class="fas fa-user w-5 h-5 mr-4"></i>
                        <span class="font-medium">Hồ sơ cá nhân</span>
                    </a>
                    <a href="posts.php" class="sidebar-link active flex items-center px-6 py-4 text-white">
                        <i class="fas fa-bullhorn w-5 h-5 mr-4"></i>
                        <span class="font-medium">Bài đăng</span>
                        <div class="notification-dot"></div>
                    </a>
                    <a href="job_search.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                        <i class="fas fa-search w-5 h-5 mr-4"></i>
                        <span class="font-medium">Tìm việc làm</span>
                    </a>
                    <a href="applications.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                        <i class="fas fa-file-alt w-5 h-5 mr-4"></i>
                        <span class="font-medium">Đơn ứng tuyển</span>
                    </a>
                <?php endif; ?>
                
                <a href="events.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                    <i class="fas fa-calendar-alt w-5 h-5 mr-4"></i>
                    <span class="font-medium">Sự kiện</span>
                </a>
                <a href="messages.php" class="sidebar-link flex items-center px-6 py-4 text-gray-300 hover:text-white">
                    <i class="fas fa-comments w-5 h-5 mr-4"></i>
                    <span class="font-medium">Tin nhắn</span>
                </a>
            </nav>
            
            <div class="px-6 py-6 border-t border-gray-600">
                <div class="flex items-center mb-4">
                    <div class="avatar-glow mr-3">
                        <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="font-medium text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
                        <p class="text-xs text-gray-400"><?= $poster_type == 'company' ? 'Công ty' : 'Ứng viên' ?></p>
                    </div>
                </div>
                <a href="../logout.php" class="sidebar-link flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-red-600">
                    <i class="fas fa-sign-out-alt w-5 h-5 mr-4"></i>
                    <span class="font-medium">Đăng xuất</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="header-glass px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <h1 class="text-3xl font-bold text-white mr-4">Bảng Tin</h1>
                        <div class="h-8 w-1 bg-white/30 rounded-full"></div>
                        <span class="ml-4 text-white/80">Kết nối và chia sẻ</span>
                    </div>
                    <div class="flex items-center space-x-6">
                        <button class="relative p-2 text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-bell text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                        <button class="relative p-2 text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-search text-xl"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto scrollbar-hidden px-8 py-8">
                <div class="max-w-2xl mx-auto space-y-8">
                    <!-- Create Post Card -->
                    <div class="glass-card create-post-gradient p-8 rounded-3xl animate-fade-in">
                        <div class="flex items-center mb-6">
                            <div class="avatar-glow mr-4">
                                <div class="w-14 h-14 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600 text-lg"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Bạn đang nghĩ gì?</h2>
                                <p class="text-gray-600">Chia sẻ cập nhật mới nhất của bạn</p>
                            </div>
                        </div>
                        
                        <?php if (!empty($msg)): ?>
                            <div class="mb-6 p-4 rounded-2xl <?= empty($errors) ? 'bg-green-100 border border-green-200 text-green-700' : 'bg-red-100 border border-red-200 text-red-700' ?>">
                                <div class="flex items-center">
                                    <i class="fas <?= empty($errors) ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                                    <p class="font-medium"><?= htmlspecialchars($msg) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form action="posts.php" method="POST" class="space-y-6">
                            <div>
                                <textarea 
                                    id="content" 
                                    name="content" 
                                    rows="4" 
                                    placeholder="Chia sẻ những suy nghĩ, cập nhật hoặc thông tin thú vị..." 
                                    class="input-modern w-full resize-none <?= isset($errors['content']) ? 'border-red-400' : '' ?>"
                                    required
                                ></textarea>
                                <?php if(isset($errors['content'])): ?>
                                    <p class="mt-2 text-red-500 text-sm flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <?= $errors['content'] ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex-1 mr-6">
                                    <select id="post_type" name="post_type" class="input-modern w-full">
                                        <?php if ($poster_type == 'company'): ?>
                                            <option value="job_announcement">📢 Thông báo tuyển dụng</option>
                                            <option value="company_news">📰 Tin tức công ty</option>
                                        <?php else: ?>
                                            <option value="personal_update">💭 Cập nhật cá nhân</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn-gradient text-white px-8 py-4 rounded-2xl font-semibold">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Đăng bài
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Posts Feed -->
                    <div class="space-y-6">
                        <?php if (empty($posts)): ?>
                            <div class="glass-card p-12 rounded-3xl text-center animate-fade-in">
                                <div class="mb-6">
                                    <i class="fas fa-comments text-6xl text-gray-300"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-700 mb-2">Chưa có bài đăng nào</h3>
                                <p class="text-gray-500">Hãy là người đầu tiên chia sẻ điều gì đó thú vị!</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($posts as $index => $post): 
                            $poster_name = ($post['company_id'] !== null) ? 
                                ($post['company_name'] ?? 'Công ty') : 
                                ($post['full_name'] ?? 'Ứng viên');
                            $poster_avatar = ($post['company_id'] !== null) ? 
                                ($post['logo_url'] ?? '../assets/images/placeholder_logo.png') : 
                                ($post['profile_picture_url'] ?? '../assets/images/placeholder_avatar.png');
                            $is_company = ($post['company_id'] !== null);
                            
                            $post_type_labels = [
                                'job_announcement' => '📢 Thông báo tuyển dụng',
                                'company_news' => '📰 Tin tức công ty',
                                'personal_update' => '💭 Cập nhật cá nhân'
                            ];
                        ?>
                            <div class="post-card p-8 animate-fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                                <div class="flex items-start justify-between mb-6">
                                    <div class="flex items-center">
                                        <div class="avatar-glow mr-4">
                                            <?php if ($is_company && !empty($poster_avatar) && $poster_avatar !== '../assets/images/placeholder_logo.png'): ?>
                                                <img class="w-14 h-14 rounded-full object-cover" 
                                                     src="<?= htmlspecialchars($poster_avatar) ?>" 
                                                     alt="Company Logo">
                                            <?php elseif (!$is_company && !empty($poster_avatar) && $poster_avatar !== '../assets/images/placeholder_avatar.png'): ?>
                                                <img class="w-14 h-14 rounded-full object-cover" 
                                                     src="<?= htmlspecialchars($poster_avatar) ?>" 
                                                     alt="Avatar">
                                            <?php else: ?>
                                                <div class="w-14 h-14 bg-gray-300 rounded-full flex items-center justify-center">
                                                    <i class="fas <?= $is_company ? 'fa-building' : 'fa-user' ?> text-gray-600 text-lg"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center mb-1">
                                                <h4 class="font-bold text-gray-900 text-lg mr-3">
                                                    <?= htmlspecialchars($poster_name) ?>
                                                </h4>
                                                <?php if ($is_company): ?>
                                                    <i class="fas fa-building company-icon text-xs p-1" title="Công ty"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user-circle applicant-icon text-xs p-1" title="Ứng viên"></i>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-500">
                                                <i class="far fa-clock mr-1"></i>
                                                <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="post-type-badge">
                                        <?= $post_type_labels[$post['post_type']] ?? $post['post_type'] ?>
                                    </span>
                                </div>
                                
                                <div class="mb-6">
                                    <p class="text-gray-700 leading-relaxed text-lg">
                                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                                    </p>
                                </div>
                                
                                <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                                    <button class="action-button flex items-center" onclick="likePost(this)">
                                        <i class="far fa-thumbs-up mr-2 text-lg"></i>
                                        <span class="font-medium">Thích</span>
                                        <span class="ml-2 text-xs bg-gray-100 px-3 py-1 rounded-full">
                                            <?= $post['views_count'] ?? 0 ?>
                                        </span>
                                    </button>
                                    <button class="action-button flex items-center">
                                        <i class="far fa-comment-alt mr-2 text-lg"></i>
                                        <span class="font-medium">Bình luận</span>
                                    </button>
                                    <button class="action-button flex items-center">
                                        <i class="fas fa-share mr-2 text-lg"></i>
                                        <span class="font-medium">Chia sẻ</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="floating-btn" onclick="scrollToTop()" title="Lên đầu trang">
        <i class="fas fa-arrow-up"></i>
    </div>

    <script>
        // Auto-resize textarea
        document.getElementById('content').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Like post functionality
        function likePost(button) {
            const likeCount = button.querySelector('span:last-child');
            const icon = button.querySelector('i');
            let count = parseInt(likeCount.textContent);
            
            if (icon.classList.contains('far')) {
                // Like the post
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.style.color = '#3b82f6';
                likeCount.textContent = count + 1;
                
                // Add animation
                button.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                }, 200);
            } else {
                // Unlike the post
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.style.color = '#64748b';
                likeCount.textContent = Math.max(0, count - 1);
            }
        }

        // Scroll to top
        function scrollToTop() {
            document.querySelector('main').scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Add loading animation to form submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang đăng...';
            submitBtn.disabled = true;
            
            // Re-enable after form submission (if there are errors)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });

        // Smooth scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all post cards
        document.querySelectorAll('.post-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Search functionality (if needed)
        function initSearch() {
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Tìm kiếm bài đăng...';
            searchInput.className = 'input-modern w-full mb-4';
            
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const posts = document.querySelectorAll('.post-card');
                
                posts.forEach(post => {
                    const content = post.textContent.toLowerCase();
                    if (content.includes(searchTerm) || searchTerm === '') {
                        post.style.display = 'block';
                        post.style.opacity = '1';
                    } else {
                        post.style.display = 'none';
                    }
                });
            });
        }

        // Initialize smooth scrolling for sidebar links
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Add ripple effect
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(255, 255, 255, 0.3)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s linear';
                ripple.style.left = '50%';
                ripple.style.top = '50%';
                ripple.style.width = '100px';
                ripple.style.height = '100px';
                ripple.style.marginLeft = '-50px';
                ripple.style.marginTop = '-50px';
                
                this.style.position = 'relative';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            .post-card {
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            
            .notification-badge {
                animation: bounce 2s infinite;
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-10px);
                }
                60% {
                    transform: translateY(-5px);
                }
            }
        `;
        document.head.appendChild(style);

        // Auto-hide success/error messages
        setTimeout(() => {
            const messages = document.querySelectorAll('[class*="bg-green-100"], [class*="bg-red-100"]');
            messages.forEach(msg => {
                if (msg.parentElement) {
                    msg.style.opacity = '0';
                    msg.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        msg.remove();
                    }, 300);
                }
            });
        }, 5000);

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to submit form
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.querySelector('form');
                if (form && document.activeElement.tagName === 'TEXTAREA') {
                    form.submit();
                }
            }
            
            // Escape to clear form
            if (e.key === 'Escape') {
                const textarea = document.getElementById('content');
                if (textarea && textarea === document.activeElement) {
                    textarea.value = '';
                    textarea.style.height = 'auto';
                }
            }
        });

        // Add real-time character counter
        const textarea = document.getElementById('content');
        const maxLength = 1000;
        
        const counter = document.createElement('div');
        counter.className = 'text-sm text-gray-500 text-right mt-2';
        counter.textContent = `0/${maxLength}`;
        textarea.parentNode.appendChild(counter);
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length}/${maxLength}`;
            
            if (length > maxLength * 0.9) {
                counter.className = 'text-sm text-orange-500 text-right mt-2';
            } else if (length > maxLength) {
                counter.className = 'text-sm text-red-500 text-right mt-2';
            } else {
                counter.className = 'text-sm text-gray-500 text-right mt-2';
            }
        });

        // Initialize tooltips for better UX
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.position = 'relative';
            });
        });

        console.log('🚀 Talent Pool Posts Page loaded successfully!');
    </script>
</body>
</html>