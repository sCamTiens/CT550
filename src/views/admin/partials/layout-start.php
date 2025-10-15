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
</style>

<body class="bg-gray-50 text-gray-800">
     <div class="min-h-screen flex"
          x-data="{ openSidebar: true, openAdd:false, groups: {catalog:true, inventory:false, promo:false} }">
          <?php require __DIR__ . '/sidebar.php'; ?>
          <main class="flex-1 p-6">