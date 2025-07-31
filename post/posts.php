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
                // Redirect to avoid form resubmission
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
    <title>Bảng Tin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(to right, #4a90e2, #50e3c2); }
        .input-error { border-color: #ef4444; }
        .error-message { color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; }
        /* Custom styles for the feed */
        .post-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            background-color: #ffffff;
        }
        .post-header {
            display: flex;
            align-items: center;
        }
        .post-author-name {
            font-weight: 600;
            color: #1a202c;
        }
        .post-time {
            font-size: 0.875rem;
            color: #a0aec0;
        }
        .post-content {
            margin-top: 1rem;
            line-height: 1.625;
            color: #4a5568;
            white-space: pre-wrap;
        }
        .post-actions {
            display: flex;
            border-top: 1px solid #edf2f7;
            margin-top: 1rem;
            padding-top: 1rem;
        }
        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            padding: 0.5rem;
            font-size: 0.875rem;
            color: #718096;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border-radius: 8px;
        }
        .action-button:hover {
            background-color: #f7fafc;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen bg-gray-50">
        <div class="w-64 bg-gray-800 text-white flex flex-col">
            <div class="px-6 py-4 border-b border-gray-700 text-center">
                <h1 class="text-2xl font-bold">Quản lý</h1>
            </div>
            <nav class="flex-1 px-2 py-4 space-y-2">
                <?php if ($poster_type == 'company'): ?>
                    <a href="company_dashboard.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                        <i class="fas fa-home w-5 h-5 mr-3"></i>Bảng điều khiển
                    </a>
                    <a href="company_profile.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                        <i class="fas fa-building w-5 h-5 mr-3"></i>Hồ sơ Công ty
                    </a>
                    <a href="posts.php" class="flex items-center px-4 py-2 bg-gray-900 text-white rounded-md">
                        <i class="fas fa-bullhorn w-5 h-5 mr-3"></i>Bài đăng
                    </a>
                <?php else: ?>
                    <a href="user_dashboard.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                        <i class="fas fa-home w-5 h-5 mr-3"></i>Bảng điều khiển
                    </a>
                    <a href="posts.php" class="flex items-center px-4 py-2 bg-gray-900 text-white rounded-md">
                        <i class="fas fa-bullhorn w-5 h-5 mr-3"></i>Bài đăng
                    </a>
                <?php endif; ?>
            </nav>
            <div class="px-6 py-4 border-t border-gray-700">
                <a href="../logout.php" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-md">
                    <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>Đăng xuất
                </a>
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex items-center justify-between p-6 bg-white border-b-2 border-gray-100">
                <div class="text-2xl font-semibold text-gray-800">Bảng Tin</div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Xin chào, <?= htmlspecialchars($_SESSION['username'] ?? 'Bạn') ?></span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="max-w-xl mx-auto">
                    <div class="post-card p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Bạn đang nghĩ gì?</h2>
                        <?php if (!empty($msg)): ?>
                            <div class="p-4 rounded-md <?= empty($errors) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> mb-4">
                                <p><?= htmlspecialchars($msg) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <form action="posts.php" method="POST" class="space-y-4">
                            <div>
                                <textarea id="content" name="content" rows="3" placeholder="Viết gì đó..." class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-indigo-500 <?= isset($errors['content']) ? 'input-error' : '' ?>"></textarea>
                                <?php if(isset($errors['content'])) echo "<p class='error-message'>{$errors['content']}</p>"; ?>
                            </div>
                            <div>
                                <label for="post_type" class="block text-sm font-medium text-gray-700">Loại bài đăng</label>
                                <select id="post_type" name="post_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                    <?php if ($poster_type == 'company'): ?>
                                        <option value="job_announcement">Thông báo tuyển dụng</option>
                                        <option value="company_news">Tin tức công ty</option>
                                    <?php endif; ?>
                                    <?php if ($poster_type == 'applicant'): ?>
                                        <option value="personal_update">Cập nhật cá nhân</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white gradient-bg hover:opacity-90">
                                    Đăng
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="space-y-6">
                        <?php if (empty($posts)): ?>
                            <div class="post-card p-6 text-center text-gray-500">
                                Chưa có bài đăng nào.
                            </div>
                        <?php endif; ?>

                        <?php foreach ($posts as $post): 
                            $poster_name = ($post['company_id'] !== null) ? ($post['company_name'] ?? 'Công ty') : ($post['full_name'] ?? 'Ứng viên');
                            $poster_avatar = ($post['company_id'] !== null) ? ($post['logo_url'] ?? '../assets/images/placeholder_logo.png') : ($post['profile_picture_url'] ?? '../assets/images/placeholder_avatar.png');
                        ?>
                            <div class="post-card p-6">
                                <div class="post-header mb-4">
                                    <img class="w-12 h-12 rounded-full object-cover mr-4" src="<?= htmlspecialchars($poster_avatar) ?>" alt="Avatar">
                                    <div>
                                        <div class="post-author-name">
                                            <?= htmlspecialchars($poster_name) ?>
                                            <?php if ($post['company_id'] !== null): ?>
                                                <i class="fas fa-building text-blue-500 text-xs ml-1" title="Công ty"></i>
                                            <?php else: ?>
                                                <i class="fas fa-user-circle text-gray-500 text-xs ml-1" title="Ứng viên"></i>
                                            <?php endif; ?>
                                        </div>
                                        <p class="post-time"><?= htmlspecialchars($post['created_at']) ?></p>
                                    </div>
                                    <span class="ml-auto bg-gray-200 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                        <?= htmlspecialchars(str_replace('_', ' ', $post['post_type'])) ?>
                                    </span>
                                </div>
                                <p class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                <div class="post-actions">
                                    <button class="action-button">
                                        <i class="far fa-thumbs-up mr-2"></i>Thích
                                    </button>
                                    <button class="action-button">
                                        <i class="far fa-comment-alt mr-2"></i>Bình luận
                                    </button>
                                    <button class="action-button">
                                        <i class="fas fa-share mr-2"></i>Chia sẻ
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>