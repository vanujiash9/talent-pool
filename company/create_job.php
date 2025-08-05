<?php
session_start();
require_once "../config.php"; // Đi ngược ra 1 cấp để gọi config.php

// 1. BẢO VỆ TRANG: CHỈ DOANH NGHIỆP ĐÃ ĐĂNG NHẬP MỚI ĐƯỢC TRUY CẬP
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'company') {
    header("location: ../auth.php"); // Chuyển về trang đăng nhập nếu không hợp lệ
    exit;
}

// Lấy company_id từ session để biết ai là người đăng tin
$company_id = $_SESSION['company_id'] ?? null;
if (!$company_id) {
    // Xử lý trường hợp không tìm thấy company_id trong session, có thể đăng xuất và báo lỗi
    session_unset();
    session_destroy();
    header("location: ../auth.php?error=session_expired");
    exit;
}

// Khởi tạo các biến
$errors = [];
$message = '';
$job_name = $job_description = $average_salary = $field = $status = '';

// 2. XỬ LÝ KHI FORM ĐƯỢC GỬI ĐI
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy và làm sạch dữ liệu
    $job_name = trim($_POST['job_name']);
    $job_description = trim($_POST['job_description']);
    $average_salary = trim($_POST['average_salary']);
    $field = trim($_POST['field']);
    $status = trim($_POST['status']);

    // --- Validation ---
    if (empty($job_name)) { $errors['job_name'] = 'Tên công việc không được để trống.'; }
    if (empty($job_description)) { $errors['job_description'] = 'Mô tả công việc không được để trống.'; }
    if (empty($field)) { $errors['field'] = 'Lĩnh vực không được để trống.'; }
    if (!empty($average_salary) && !is_numeric($average_salary)) { $errors['average_salary'] = 'Mức lương phải là một con số.'; }

    // Nếu không có lỗi, tiến hành lưu vào database
    if (empty($errors)) {
        $sql = "INSERT INTO jobs (company_id, job_name, job_description, average_salary, field, status) VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $salary_to_db = !empty($average_salary) ? $average_salary : NULL;
            $stmt->bind_param("isssis", $company_id, $job_name, $job_description, $salary_to_db, $field, $status);
            
            if ($stmt->execute()) {
                // Đặt thông báo thành công vào session và chuyển hướng về trang dashboard
                $_SESSION['message'] = "Đăng tin tuyển dụng '" . htmlspecialchars($job_name) . "' thành công!";
                header("Location: company_dashboard.php"); 
                exit;
            } else {
                $message = "Có lỗi xảy ra, vui lòng thử lại: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Lỗi khi chuẩn bị câu lệnh SQL: " . $conn->error;
        }
    } else {
        $message = "Vui lòng sửa các lỗi trong form.";
    }
}

$conn->close();
?>
<?php include '../templates/header.php'; // Gọi file header chung, chú ý đường dẫn ../ ?>

<main class="container mx-auto p-4 sm:p-6 lg:p-8 pt-24 lg:pt-8"> <!-- Thêm padding top để không bị header che -->
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="border-b pb-6 mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900">Đăng tin tuyển dụng mới</h1>
                <p class="mt-2 text-gray-600">Điền các thông tin dưới đây để tiếp cận hàng ngàn ứng viên tiềm năng.</p>
            </div>
            
            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-md bg-red-100 text-red-700">
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
            <?php endif; ?>

            <form action="create_job.php" method="POST" class="space-y-6">
                
                <div>
                    <label for="job_name" class="block text-sm font-medium text-gray-700 mb-1">Tên công việc <span class="text-red-500">*</span></label>
                    <input type="text" name="job_name" id="job_name" value="<?= htmlspecialchars($job_name) ?>" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 <?= isset($errors['job_name']) ? 'border-red-500' : '' ?>" 
                           required>
                    <?php if(isset($errors['job_name'])) echo "<p class='text-red-500 text-xs mt-1'>{$errors['job_name']}</p>"; ?>
                </div>

                <div>
                    <label for="job_description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả chi tiết công việc <span class="text-red-500">*</span></label>
                    <textarea name="job_description" id="job_description" rows="10" 
                              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 <?= isset($errors['job_description']) ? 'border-red-500' : '' ?>" 
                              required><?= htmlspecialchars($job_description) ?></textarea>
                    <p class="mt-2 text-xs text-gray-500">Mô tả yêu cầu, quyền lợi, và trách nhiệm của vị trí này. Bạn có thể sử dụng các công cụ định dạng.</p>
                    <?php if(isset($errors['job_description'])) echo "<p class='text-red-500 text-xs mt-1'>{$errors['job_description']}</p>"; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="average_salary" class="block text-sm font-medium text-gray-700 mb-1">Mức lương (VNĐ/tháng)</label>
                        <input type="number" name="average_salary" id="average_salary" placeholder="VD: 15000000 (để trống nếu thỏa thuận)" value="<?= htmlspecialchars($average_salary) ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 <?= isset($errors['average_salary']) ? 'border-red-500' : '' ?>">
                        <?php if(isset($errors['average_salary'])) echo "<p class='text-red-500 text-xs mt-1'>{$errors['average_salary']}</p>"; ?>
                    </div>
                    <div>
                        <label for="field" class="block text-sm font-medium text-gray-700 mb-1">Lĩnh vực <span class="text-red-500">*</span></label>
                        <input type="text" name="field" id="field" placeholder="VD: Công nghệ thông tin, Marketing" value="<?= htmlspecialchars($field) ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 <?= isset($errors['field']) ? 'border-red-500' : '' ?>" 
                               required>
                        <?php if(isset($errors['field'])) echo "<p class='text-red-500 text-xs mt-1'>{$errors['field']}</p>"; ?>
                    </div>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái tin</label>
                    <select name="status" id="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="active" selected>Công khai (Đang tuyển)</option>
                        <option value="inactive">Tạm ẩn</option>
                    </select>
                </div>

                <div class="flex justify-end pt-6 border-t mt-8 space-x-4">
                    <a href="company_dashboard.php" class="py-2 px-6 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Hủy
                    </a>
                    <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-paper-plane mr-2"></i>Đăng tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
<script>
    try {
         CKEDITOR.replace('job_description', {
            height: 250,
            toolbar: [
                { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', '-', 'RemoveFormat' ] },
                { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ] },
                { name: 'links', items: [ 'Link', 'Unlink' ] },
                { name: 'styles', items: [ 'Format' ] },
            ]
        });
    } catch (e) {
        console.error("CKEditor failed to initialize:", e);
    }
</script>
</body> 
</html>