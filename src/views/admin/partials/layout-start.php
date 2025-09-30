<!doctype html>
<html lang="vi">
<head><?php require __DIR__.'/head.php'; ?></head>
<?php require __DIR__.'/header.php'; ?>
<body class="bg-gray-50 text-gray-800">
<div class="min-h-screen flex"
     x-data="{ openSidebar: true, openAdd:false, groups: {catalog:true, inventory:false, promo:false} }">
<?php require __DIR__.'/sidebar.php'; ?>
<main class="flex-1 p-6">
