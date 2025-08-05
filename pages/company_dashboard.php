<?php
session_start();

if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] != 'company') { // Sửa lại vai trò là 'company'
    header("Location: login.php");
    exit;
}

include "config.php";

$company_id = $_SESSION['company_id'];
$company_info = null;
$jobs = [];
$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Lấy thông tin công ty từ nhiều bảng
$sql_company_info = "
    SELECT 
        c.company_name, c.company_overview, c.industry, c.logo_url,
        cl.website_url, cl.contact_address,
        cc.contact_phone
    FROM companies c
    LEFT JOIN company_locations cl ON c.company_id = cl.company_id
    LEFT JOIN company_contact cc ON c.company_id = cc.company_id
    WHERE c.company_id = ? LIMIT 1
";

if ($stmt = $conn->prepare($sql_company_info)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $company_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Lấy danh sách các công việc đã đăng bởi công ty này
$sql_jobs = "
    SELECT 
        j.job_id, j.job_name, j.field, j.status, 
        COUNT(ja.job_id) as application_count 
    FROM jobs j
    LEFT JOIN job_applicant ja ON j.job_id = ja.job_id
    WHERE j.company_id = ?
    GROUP BY j.job_id
    ORDER BY j.created_at DESC
    LIMIT 5
";

if ($stmt = $conn->prepare($sql_jobs)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

if (!$company_info) {
    echo "Không tìm thấy thông tin công ty.";
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Nhà tuyển dụng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root { --main-color: #2b6cb0; }
        .bg-main-color { background-color: var(--main-color); }
        .text-main-color { color: var(--main-color); }
        .border-main-color { border-color: var(--main-color); }
        .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .card { transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gray-100 antialiased">
    <div class="w-full text-white bg-main-color shadow-md">
        <div class="flex flex-col max-w-screen-xl px-4 mx-auto md:items-center md:justify-between md:flex-row md:px-6 lg:px-8">
            <div class="p-4 flex flex-row items-center justify-between">
                <a href="company_dashboard.php" class="text-lg font-semibold tracking-widest uppercase rounded-lg focus:outline-none focus:shadow-outline">Dashboard Công ty</a>
            </div>
            <nav class="flex-col flex-grow pb-4 md:pb-0 hidden md:flex md:justify-end md:flex-row">
                <div class="relative">
                    <a class="flex flex-row items-center space-x-2 w-full px-4 py-2 mt-2 text-sm font-semibold text-left bg-transparent hover:bg-blue-800 md:w-auto md:inline md:mt-0 md:ml-4 focus:bg-blue-800 focus:outline-none focus:shadow-outline" href="logout.php">
                        <span>Đăng xuất</span>
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
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
                <div class="bg-white p-3 border-t-4 border-main-color card shadow-md rounded-lg">
                    <div class="image overflow-hidden text-center">
                        <img class="h-32 w-32 mx-auto object-cover" src="<?= htmlspecialchars($company_info['logo_url'] ?? "https://via.placeholder.com/128/2b6cb0/ffffff?text=" . strtoupper(substr($company_info['company_name'] ?? '', 0, 1))) ?>" alt="Company Logo">
                    </div>
                    <h1 class="text-gray-900 font-bold text-2xl leading-8 my-2 text-center">
                        <?= htmlspecialchars($company_info['company_name'] ?? 'Công ty XYZ') ?>
                    </h1>
                    <ul class="bg-gray-100 text-gray-600 py-2 px-3 mt-4 divide-y rounded shadow-sm">
                        <li class="flex items-center py-3">
                            <span><i class="fas fa-briefcase text-main-color mr-2"></i> Ngành nghề</span>
                            <span class="ml-auto"><?= htmlspecialchars($company_info['industry'] ?? 'N/A') ?></span>
                        </li>
                        <li class="flex items-center py-3">
                            <span><i class="fas fa-phone text-main-color mr-2"></i> Điện thoại</span>
                            <span class="ml-auto"><?= htmlspecialchars($company_info['contact_phone'] ?? 'N/A') ?></span>
                        </li>
                        <li class="flex items-center py-3">
                            <span><i class="fas fa-map-marker-alt text-main-color mr-2"></i> Địa chỉ</span>
                            <span class="ml-auto truncate w-32"><?= htmlspecialchars($company_info['contact_address'] ?? 'N/A') ?></span>
                        </li>
                        <li class="flex items-center py-3">
                            <span><i class="fas fa-globe text-main-color mr-2"></i> Website</span>
                            <span class="ml-auto truncate w-32">
                                <?php if (!empty($company_info['website_url'])): ?>
                                    <a href="<?= htmlspecialchars($company_info['website_url']) ?>" target="_blank" class="text-blue-500 hover:underline"><?= htmlspecialchars($company_info['website_url']) ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </span>
                        </li>
                    </ul>
                    <a href="company/edit_profile.php" class="block w-full text-center text-blue-800 text-sm font-semibold rounded-lg hover:bg-gray-100 py-2 mt-4 transition-colors">
                        <i class="fas fa-edit mr-2"></i> Chỉnh sửa thông tin
                    </a>
                </div>
            </div>

            <div class="w-full md:w-9/12 mx-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <div class="bg-white p-4 rounded-lg shadow-md card flex items-center justify-between">
                        <div>
                            <span class="text-gray-500">Tin tuyển dụng</span>
                            <div class="text-3xl font-bold text-gray-800">
                                <?= count($jobs) ?>
                            </div>
                        </div>
                        <i class="fas fa-briefcase text-4xl text-blue-200"></i>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md card flex items-center justify-between">
                        <div>
                            <span class="text-gray-500">Hồ sơ đã nhận</span>
                            <div class="text-3xl font-bold text-gray-800">
                                <?php
                                $total_applications = 0;
                                foreach ($jobs as $job) {
                                    $total_applications += $job['application_count'];
                                }
                                echo $total_applications;
                                ?>
                            </div>
                        </div>
                        <i class="fas fa-file-alt text-4xl text-green-200"></i>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md card flex items-center justify-between">
                        <div>
                            <span class="text-gray-500">Tin đang hoạt động</span>
                            <div class="text-3xl font-bold text-gray-800">
                                <?php
                                $active_jobs = 0;
                                foreach ($jobs as $job) {
                                    if ($job['status'] == 'active') {
                                        $active_jobs++;
                                    }
                                }
                                echo $active_jobs;
                                ?>
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-4xl text-orange-200"></i>
                    </div>
                </div>

                <div class="bg-white p-3 shadow-md rounded-lg card">
                    <div class="flex justify-between items-center space-x-2 font-semibold text-gray-900 leading-8 mb-4 border-b pb-2">
                        <span class="text-main-color text-xl tracking-wide"><i class="fas fa-list-ul"></i> Các tin tuyển dụng của bạn</span>
                        <a href="create_job.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors text-sm">
                            <i class="fas fa-plus-circle mr-2"></i> Đăng tin mới
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên công việc</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lĩnh vực</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hồ sơ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($jobs)): ?>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($job['job_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($job['field']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo ($job['status'] == 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?= htmlspecialchars(ucfirst($job['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($job['application_count']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="view_applicants.php?job_id=<?= $job['job_id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2">
                                                    <i class="fas fa-eye"></i> Xem hồ sơ
                                                </a>
                                                <a href="edit_job.php?job_id=<?= $job['job_id'] ?>" class="text-yellow-600 hover:text-yellow-900">
                                                    <i class="fas fa-edit"></i> Sửa
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            Bạn chưa đăng tin tuyển dụng nào.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>