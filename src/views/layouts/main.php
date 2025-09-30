<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title><?= $title ?? 'Mini Market' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>body{font-family:system-ui,Arial,sans-serif;margin:20px}header,footer{margin:10px 0}</style>
</head>
<body>
<header>
  <a href="/">Trang chủ</a> | <a href="/products">Sản phẩm</a> | <a href="/login">Đăng nhập</a>
</header>

<main>
  <?php /* chỗ này view con sẽ được include */ ?>
  <?= $content ?? '' ?>
</main>

<footer>© <?= date('Y') ?> Mini Market</footer>
</body>
</html>
