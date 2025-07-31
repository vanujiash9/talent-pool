<?php
session_start();

if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] != 'company') {
    header("Location: login.php");
    exit;
}

include "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_id = $_SESSION['company_id'];
    $job_name = trim($_POST['job_name']);
    $job_description = trim($_POST['job_description']);
    $requirements = trim($_POST['requirements']);
    $salary = trim($_POST['salary']);
    $field = trim($_POST['field']);
    $location = trim($_POST['location']);
    $position = trim($_POST['position']);
    $experience = trim($_POST['experience']);
    $status = 'active'; // Mặc định là active khi đăng

    $sql = "INSERT INTO jobs (company_id, job_name, job_description, requirements, salary, field, location, position, experience, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("isssssssss", $company_id, $job_name, $job_description, $requirements, $salary, $field, $location, $position, $experience, $status);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Tin tuyển dụng mới đã được đăng thành công!";
            header("Location: company_dashboard.php");
            exit;
        } else {
            $message = "Đã có lỗi xảy ra. Vui lòng thử lại.";
        }
        $stmt->close();
    } else {
        $message = "Lỗi chuẩn bị truy vấn.";
    }
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng tin tuyển dụng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 antialiased">
    <div class="w-full text-white bg-blue-800 shadow-md">
        <div class="flex flex-col max-w-screen-xl px-4 mx-auto md:items-center md:justify-between md:flex-row md:px-6 lg:px-8">
            <div class="p-4 flex flex-row items-center justify-between">
                <a href="company_dashboard.php" class="text-lg font-semibold tracking-widest uppercase rounded-lg focus:outline-none focus:shadow-outline">Dashboard Công ty</a>
            </div>
            <nav class="flex-col flex-grow pb-4 md:pb-0 hidden md:flex md:justify-end md:flex-row">
                <a class="px-4 py-2 mt-2 text-sm font-semibold bg-transparent rounded-lg hover:bg-blue-600 md:mt-0 md:ml-4" href="logout.php">Đăng xuất</a>
            </nav>
        </div>
    </div>

    <div class="container mx-auto my-5 p-5">
        <div class="md:flex no-wrap md:-mx-2">
            <div class="w-full md:w-3/4 mx-auto">
                <div class="bg-white p-3 shadow-md rounded-lg">
                    <div class="flex justify-between items-center space-x-2 font-semibold text-gray-900 leading-8 mb-4 border-b pb-2">
                        <span class="text-blue-800 text-xl tracking-wide"><i class="fas fa-plus-circle"></i> Đăng tin tuyển dụng mới</span>
                    </div>
                    <?php if (!empty($message)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                            <p><?= htmlspecialchars($message) ?></p>
                        </div>
                    <?php endif; ?>
                    <form action="create_job.php" method="POST" class="mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="job_name">Tên công việc</label>
                                <input type="text" name="job_name" id="job_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="form-group">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="field">Lĩnh vực</label>
                                <input type="text" name="field" id="field" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="form-group">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="location">Địa điểm</label>
                                <input type="text" name="location" id="location" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="form-group">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="position">Vị trí</label>
                                <input type="text" name="position" id="position" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="form-group">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="salary">Mức lương</label>
                                <input type="text" name="salary" id="salary" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="form-group">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="experience">Kinh nghiệm</label>
                                <input type="text" name="experience" id="experience" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="job_description">Mô tả công việc</label>
                            <textarea name="job_description" id="job_description" rows="6" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
                        </div>
                        <div class="form-group mt-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="requirements">Yêu cầu công việc</label>
                            <textarea name="requirements" id="requirements" rows="6" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
                        </div>
                        <div class="flex items-center justify-between mt-6">
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                                Đăng tin
                            </button>
                            <a href="company_dashboard.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                                Hủy bỏ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>