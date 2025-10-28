<!doctype html>
<html lang="vi">

<head><?php require __DIR__ . '/head.php'; ?></head>
<?php require __DIR__ . '/header.php'; ?>

<style>
     table th,
     table td {
          white-space: nowrap;
          /* luôn 1 dòng */
     }

     .overflow-x-auto {
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
          display: block;
     }

     table {
          width: 100%;
          border-collapse: collapse;
          table-layout: fixed;
          /* ép cột chia % */
     }

     table th,
     table td {
          width: 10%;
          /* Ví dụ 10 cột thì mỗi cột 10% */
     }

     #content {
    transition: opacity 0.3s ease-in-out;
}
</style>

<body class="bg-gray-50 text-gray-800">
     <div class="min-h-screen flex"
          x-data="{ openSidebar: true, openAdd:false, groups: {catalog:true, inventory:false, promo:false} }">
          <?php require __DIR__ . '/sidebar.php'; ?>
          <main class="flex-1 p-6" id="content">
               <!-- Flash Messages (Toast Style) -->
               <?php if (isset($_SESSION['flash_error'])): ?>
                    <div class="fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold text-red-700 border-red-400 bg-white rounded-xl shadow-lg border-2"
                         x-data="{ show: true }" 
                         x-show="show" 
                         x-init="setTimeout(() => show = false, 5000)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-full"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 transform translate-x-0"
                         x-transition:leave-end="opacity-0 transform translate-x-full">
                         <svg class="flex-shrink-0 w-6 h-6 text-red-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
                         </svg>
                         <div class="flex-1"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
                         <button @click="show = false" class="ml-3 text-red-700 hover:text-red-900">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>
                         </button>
                    </div>
                    <?php unset($_SESSION['flash_error']); ?>
               <?php endif; ?>
               
               <?php if (isset($_SESSION['flash_success'])): ?>
                    <div class="fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold text-green-700 border-green-400 bg-white rounded-xl shadow-lg border-2"
                         x-data="{ show: true }" 
                         x-show="show" 
                         x-init="setTimeout(() => show = false, 5000)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-full"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 transform translate-x-0"
                         x-transition:leave-end="opacity-0 transform translate-x-full">
                         <svg class="flex-shrink-0 w-6 h-6 text-green-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                         </svg>
                         <div class="flex-1"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
                         <button @click="show = false" class="ml-3 text-green-700 hover:text-green-900">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>
                         </button>
                    </div>
                    <?php unset($_SESSION['flash_success']); ?>
               <?php endif; ?>