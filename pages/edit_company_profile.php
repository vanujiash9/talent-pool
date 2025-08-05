<?php
session_start();
require_once "../config.php"; 

// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["is_logged_in"]) || $_SESSION["is_logged_in"] !== true || $_SESSION["role"] !== 'company') {
    header("location: ../login.php");
    exit;
}

$company_id = $_SESSION["company_id"];
$msg = "";
$errors = [];

function getPostValue($key, $default_value = '') {
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : $default_value;
}

// -----------------------------------------------------------
// 1. Fetch existing data to pre-fill the form (GET request)
// -----------------------------------------------------------
$company_info = [];
$sql_fetch = "SELECT c.*, cl.website_url, cl.contact_address, cl.headquarter, cl.city, cl.country, cl.facebook_url, cl.linkedin_url, cc.contact_email, cc.contact_phone, cc.hr_email, ct.founded_year
              FROM companies c
              LEFT JOIN company_locations cl ON c.company_id = cl.company_id
              LEFT JOIN company_contact cc ON c.company_id = cc.company_id
              LEFT JOIN company_timeline ct ON c.company_id = ct.company_id
              WHERE c.company_id = ?";

if (isset($conn) && $conn) {
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $company_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows > 0) {
        $company_info = $result_fetch->fetch_assoc();
    }
    $stmt_fetch->close();
}


// -----------------------------------------------------------
// 2. Handle form submission (Update operation) (POST request)
// -----------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $company_name = getPostValue("company_name");
    $brand_name = getPostValue("brand_name");
    $short_name = getPostValue("short_name");
    $industry = getPostValue("industry");
    $description = $_POST["description"] ?? '';
    $company_overview = $_POST["company_overview"] ?? '';
    $founded_year = getPostValue("founded_year");

    $contact_email = getPostValue("contact_email");
    $contact_phone = getPostValue("contact_phone");
    $hr_email = getPostValue("hr_email");

    $website_url = getPostValue("website_url");
    $headquarter = getPostValue("headquarter");
    $city = getPostValue("city");
    $country = getPostValue("country");
    $contact_address = getPostValue("contact_address");
    $facebook_url = getPostValue("facebook_url");
    $linkedin_url = getPostValue("linkedin_url");
    $is_VIP = isset($_POST["is_VIP"]) ? 1 : 0;
    
    // Validation
    if (empty($company_name)) $errors['company_name'] = 'Tên công ty không được để trống.';
    if (empty($industry)) $errors['industry'] = 'Ngành nghề không được để trống.';
    if (empty($contact_email)) $errors['contact_email'] = 'Email liên hệ không được để trống.';
    if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors['contact_email'] = 'Email liên hệ không hợp lệ.';

    // Handle file upload for logo
    $logo_url = $company_info['logo_url'] ?? ''; // Default to existing logo
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
        $target_dir = "../uploads/company_logos/";
        // Check if directory exists, if not, create it
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["company_logo"]["name"], PATHINFO_EXTENSION);
        $new_filename = $company_id . "_" . uniqid() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["company_logo"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file)) {
                $logo_url = "uploads/company_logos/" . $new_filename;
            } else {
                $errors['company_logo'] = "Có lỗi khi tải ảnh lên.";
            }
        } else {
            $errors['company_logo'] = "File không phải là ảnh.";
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update companies table
            $sql_update_company = "UPDATE companies SET company_name=?, brand_name=?, short_name=?, company_overview=?, industry=?, description=?, logo_url=?, is_VIP=? WHERE company_id=?";
            $stmt_update_company = $conn->prepare($sql_update_company);
            if ($stmt_update_company === false) {
                throw new Exception("Lỗi prepare companies: " . $conn->error);
            }
            $stmt_update_company->bind_param("sssssssii", $company_name, $brand_name, $short_name, $company_overview, $industry, $description, $logo_url, $is_VIP, $company_id);
            if (!$stmt_update_company->execute()) {
                throw new Exception("Lỗi khi cập nhật thông tin công ty: " . $stmt_update_company->error);
            }
            $stmt_update_company->close();

            // Update company_contact table
            $sql_update_contact = "UPDATE company_contact SET contact_email=?, contact_phone=?, hr_email=? WHERE company_id=?";
            $stmt_update_contact = $conn->prepare($sql_update_contact);
            if ($stmt_update_contact === false) {
                throw new Exception("Lỗi prepare company_contact: " . $conn->error);
            }
            $stmt_update_contact->bind_param("sssi", $contact_email, $contact_phone, $hr_email, $company_id);
            if (!$stmt_update_contact->execute()) {
                throw new Exception("Lỗi khi cập nhật thông tin liên hệ: " . $stmt_update_contact->error);
            }
            $stmt_update_contact->close();

            // Update company_locations table
            $sql_update_location = "UPDATE company_locations SET headquarter=?, city=?, country=?, contact_address=?, website_url=?, facebook_url=?, linkedin_url=? WHERE company_id=?";
            $stmt_update_location = $conn->prepare($sql_update_location);
            if ($stmt_update_location === false) {
                throw new Exception("Lỗi prepare company_locations: " . $conn->error);
            }
            $stmt_update_location->bind_param("sssssssi", $headquarter, $city, $country, $contact_address, $website_url, $facebook_url, $linkedin_url, $company_id);
            if (!$stmt_update_location->execute()) {
                throw new Exception("Lỗi khi cập nhật địa chỉ: " . $stmt_update_location->error);
            }
            $stmt_update_location->close();
            
            // Update company_timeline table
            $sql_update_timeline = "UPDATE company_timeline SET founded_year=? WHERE company_id=?";
            $stmt_update_timeline = $conn->prepare($sql_update_timeline);
            if ($stmt_update_timeline === false) {
                throw new Exception("Lỗi prepare company_timeline: " . $conn->error);
            }
            $stmt_update_timeline->bind_param("si", $founded_year, $company_id);
            if (!$stmt_update_timeline->execute()) {
                throw new Exception("Lỗi khi cập nhật năm thành lập: " . $stmt_update_timeline->error);
            }
            $stmt_update_timeline->close();

            // All updates successful, commit the transaction
            $conn->commit();
            $msg = "Cập nhật hồ sơ thành công! Đang chuyển hướng...";
            header("refresh:2;url=company_profile.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Cập nhật thất bại: " . $e->getMessage();
        }
    } else {
        $msg = "Vui lòng sửa các lỗi trong form.";
    }
}

// Re-fetch data if a post request failed to re-populate the form with new values
if (!empty($errors)) {
    $company_info = array_merge($company_info, $_POST);
    $company_info['logo_url'] = $logo_url; // keep logo url in case of other errors
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa Hồ sơ Doanh nghiệp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(to right, #4a90e2, #50e3c2); }
        .input-error { border-color: #ef4444; }
        .error-message { color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-4xl p-8 space-y-8 bg-white rounded-xl shadow-lg">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Chỉnh sửa Hồ sơ Doanh nghiệp
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Cập nhật thông tin công ty của bạn
                </p>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="p-4 rounded-md <?= empty($errors) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                    <p><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="edit_company_profile.php" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin công ty</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700">Tên công ty<span class="text-red-500">*</span></label>
                                <input id="company_name" name="company_name" type="text" value="<?= getPostValue('company_name', $company_info['company_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['company_name']) ? 'input-error' : '' ?>">
                                <?php if(isset($errors['company_name'])) echo "<p class='error-message'>{$errors['company_name']}</p>"; ?>
                            </div>
                            <div>
                                <label for="brand_name" class="block text-sm font-medium text-gray-700">Tên thương hiệu</label>
                                <input id="brand_name" name="brand_name" type="text" value="<?= getPostValue('brand_name', $company_info['brand_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="short_name" class="block text-sm font-medium text-gray-700">Tên viết tắt</label>
                                <input id="short_name" name="short_name" type="text" value="<?= getPostValue('short_name', $company_info['short_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="industry" class="block text-sm font-medium text-gray-700">Ngành nghề<span class="text-red-500">*</span></label>
                                <input id="industry" name="industry" type="text" value="<?= getPostValue('industry', $company_info['industry'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['industry']) ? 'input-error' : '' ?>">
                                <?php if(isset($errors['industry'])) echo "<p class='error-message'>{$errors['industry']}</p>"; ?>
                            </div>
                            <div>
                                <label for="founded_year" class="block text-sm font-medium text-gray-700">Năm thành lập</label>
                                <input id="founded_year" name="founded_year" type="number" value="<?= getPostValue('founded_year', $company_info['founded_year'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="company_overview" class="block text-sm font-medium text-gray-700">Giới thiệu chung</label>
                                <textarea id="company_overview" name="company_overview" rows="5" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?= getPostValue('company_overview', $company_info['company_overview'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Mô tả chi tiết</label>
                                <textarea id="description" name="description" rows="5" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?= getPostValue('description', $company_info['description'] ?? '') ?></textarea>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input id="is_VIP" name="is_VIP" type="checkbox" <?= ($company_info['is_VIP'] ?? false) ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_VIP" class="text-sm font-medium text-gray-700">VIP Company</label>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Liên hệ & Vị trí</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="company_logo" class="block text-sm font-medium text-gray-700">Logo công ty</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-20m32-12V16a4 4 0 00-4-4H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="company_logo" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>Tải lên một file</span>
                                                <input id="company_logo" name="company_logo" type="file" class="sr-only" accept="image/*">
                                            </label>
                                            <p class="pl-1">hoặc kéo và thả</p>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            PNG, JPG, GIF tối đa 2MB
                                        </p>
                                    </div>
                                </div>
                                <?php if(isset($errors['company_logo'])) echo "<p class='error-message text-center'>{$errors['company_logo']}</p>"; ?>
                                <?php if(!empty($company_info['logo_url'])): ?>
                                    <div class="mt-4 text-center">
                                        <p class="text-sm font-medium text-gray-700">Logo hiện tại:</p>
                                        <img src="../<?= htmlspecialchars($company_info['logo_url']) ?>" alt="Current Logo" class="mx-auto mt-2 h-24 w-24 object-contain">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700">Email liên hệ<span class="text-red-500">*</span></label>
                                <input id="contact_email" name="contact_email" type="email" value="<?= getPostValue('contact_email', $company_info['contact_email'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm <?= isset($errors['contact_email']) ? 'input-error' : '' ?>">
                                <?php if(isset($errors['contact_email'])) echo "<p class='error-message'>{$errors['contact_email']}</p>"; ?>
                            </div>
                            <div>
                                <label for="hr_email" class="block text-sm font-medium text-gray-700">Email HR</label>
                                <input id="hr_email" name="hr_email" type="email" value="<?= getPostValue('hr_email', $company_info['hr_email'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                <input id="contact_phone" name="contact_phone" type="tel" value="<?= getPostValue('contact_phone', $company_info['contact_phone'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="website_url" class="block text-sm font-medium text-gray-700">Website</label>
                                <input id="website_url" name="website_url" type="url" value="<?= getPostValue('website_url', $company_info['website_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="facebook_url" class="block text-sm font-medium text-gray-700">Facebook URL</label>
                                <input id="facebook_url" name="facebook_url" type="url" value="<?= getPostValue('facebook_url', $company_info['facebook_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="linkedin_url" class="block text-sm font-medium text-gray-700">LinkedIn URL</label>
                                <input id="linkedin_url" name="linkedin_url" type="url" value="<?= getPostValue('linkedin_url', $company_info['linkedin_url'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="contact_address" class="block text-sm font-medium text-gray-700">Địa chỉ liên hệ</label>
                                <textarea id="contact_address" name="contact_address" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?= getPostValue('contact_address', $company_info['contact_address'] ?? '') ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="headquarter" class="block text-sm font-medium text-gray-700">Trụ sở chính</label>
                                    <input id="headquarter" name="headquarter" type="text" value="<?= getPostValue('headquarter', $company_info['headquarter'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700">Thành phố</label>
                                    <input id="city" name="city" type="text" value="<?= getPostValue('city', $company_info['city'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700">Quốc gia</label>
                                    <input id="country" name="country" type="text" value="<?= getPostValue('country', $company_info['country'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <a href="company_profile.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Hủy
                    </a>
                    <button type="submit" class="group relative py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white gradient-bg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save mr-2"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>