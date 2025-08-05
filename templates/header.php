<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$base_path = '/talent_pool'; // Sửa lại nếu bạn đổi tên thư mục dự án
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#E30417',
          }
        }
      }
    }
  </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .scrolled {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-white antialiased">

<header id="main-header" class="bg-white/80 sticky top-0 z-50 transition-all duration-300">
  <nav aria-label="Global" class="mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8">
    <div class="flex lg:flex-1">
      <a href="<?= $base_path ?>/index.php" class="-m-1.5 p-1.5 flex items-center gap-x-2">
        <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600" alt="Talent Pool Logo" class="h-8 w-auto" />
        <span class="font-bold text-xl text-gray-800">Talent Pool</span>
      </a>
    </div>
    <div class="hidden lg:flex lg:gap-x-12">
      <a href="#" class="text-sm font-semibold leading-6 text-gray-900 hover:text-primary">Dành cho Ứng viên</a>
      <a href="#" class="text-sm font-semibold leading-6 text-gray-900 hover:text-primary">Dành cho Doanh nghiệp</a>
      <a href="#" class="text-sm font-semibold leading-6 text-gray-900 hover:text-primary">Việc làm</a>
    </div>
    <div class="flex flex-1 justify-end items-center gap-x-6">
      <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
        <a href="<?= $_SESSION['role'] === 'user' ? $base_path.'/user/edit_profile.php' : $base_path.'/pages/company_profile.php' ?>" class="hidden lg:block text-sm font-semibold leading-6 text-gray-900 hover:text-primary">Hồ sơ cá nhân</a>
        <a href="<?= $base_path ?>/logout.php" class="rounded-md bg-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Đăng xuất</a>
      <?php else: ?>
        <a href="auth.php?action=signin" class="hidden lg:block text-sm font-semibold leading-6 text-gray-900 hover:text-primary">Đăng nhập</a>
        <a href="auth.php?action=signup" class="rounded-md bg-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Đăng ký</a>
      <?php endif; ?>
       <div class="flex lg:hidden">
         <button type="button" id="mobile-menu-open-button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700">
           <i class="fas fa-bars text-xl"></i>
         </button>
       </div>
    </div>
  </nav>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="lg:hidden fixed inset-0 z-50 hidden">
      <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-25 opacity-0 transition-opacity duration-300"></div>
      <div id="mobile-menu-content" class="fixed inset-y-0 right-0 w-full max-w-sm bg-white p-6 transform translate-x-full transition-transform duration-300 ease-in-out">
        <div class="flex items-center justify-between">
            <a href="<?= $base_path ?>/index.php" class="flex items-center gap-x-2">
                <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600" alt="Talent Pool Logo" class="h-8 w-auto" />
                <span class="font-bold text-xl text-gray-800">Talent Pool</span>
            </a>
            <button type="button" id="mobile-menu-close-button" class="-m-2.5 rounded-md p-2.5 text-gray-700">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="mt-6 flow-root">
            <div class="divide-y divide-gray-500/10">
                <div class="space-y-2 py-6">
                    <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Dành cho Ứng viên</a>
                    <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Dành cho Doanh nghiệp</a>
                    <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Việc làm</a>
                </div>
                <div class="py-6">
                    <?php if (isset($_SESSION['is_logged_in'])): ?>
                        <a href="<?= $_SESSION['role'] === 'user' ? $base_path.'/user/edit_profile.php' : $base_path.'/pages/company_profile.php' ?>" class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Hồ sơ cá nhân</a>
                        <a href="<?= $base_path ?>/logout.php" class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-red-600 hover:bg-gray-50">Đăng xuất</a>
                    <?php else: ?>
                        <a href="auth.php?action=signin" class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Đăng nhập</a>
                        <a href="auth.php?action=signup" class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
      </div>
  </div>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.getElementById('main-header');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuContent = document.getElementById('mobile-menu-content');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const openBtn = document.getElementById('mobile-menu-open-button');
        const closeBtn = document.getElementById('mobile-menu-close-button');

        window.addEventListener('scroll', function() {
            header.classList.toggle('scrolled', window.scrollY > 10);
        });

        function openMobileMenu() {
            mobileMenu.classList.remove('hidden');
            setTimeout(() => {
                mobileMenuContent.classList.remove('translate-x-full');
                mobileMenuOverlay.classList.remove('opacity-0');
            }, 10);
        }
        function closeMobileMenu() {
            mobileMenuContent.classList.add('translate-x-full');
            mobileMenuOverlay.classList.add('opacity-0');
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
            }, 300);
        }
        
        if (openBtn) openBtn.addEventListener('click', openMobileMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMobileMenu);
        if (mobileMenuOverlay) mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    });
</script>