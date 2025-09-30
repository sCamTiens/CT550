<?php
/** 
 * Biến truyền vào:
 *  - $products : mảng sản phẩm (id, name, slug, price, image_url,...)
 *  - $page     : số trang hiện tại (int) – optional
 */
$page = $page ?? 1;
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Danh sách sản phẩm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:24px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin:16px 0;padding:0;list-style:none}
    .card{border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#fff;display:flex;flex-direction:column}
    .card img{aspect-ratio:4/3;object-fit:cover;width:100%;background:#f3f4f6}
    .card .body{padding:12px;display:flex;flex-direction:column;gap:8px}
    .price{font-weight:700}
    .muted{color:#6b7280}
    nav.pager{display:flex;gap:8px;margin-top:16px}
    nav.pager a{padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;text-decoration:none}
  </style>
</head>
<body>
  <p><a href="/">Trang chủ</a> | <strong>Sản phẩm</strong> | <a href="/login">Đăng nhập</a></p>

  <h1>Danh sách sản phẩm</h1>

  <?php if (empty($products)): ?>
    <p class="muted">Chưa có sản phẩm.</p>
  <?php else: ?>
    <ul class="grid">
      <?php foreach ($products as $p): ?>
        <li class="card">
          <a href="/products/<?= e($p['slug'] ?? (string)$p['id']) ?>">
            <img src="<?= e($p['image_url'] ?? '/assets/images/placeholder.png') ?>" alt="<?= e($p['name'] ?? 'SP') ?>">
          </a>
          <div class="body">
            <div><a href="/products/<?= e($p['slug'] ?? (string)$p['id']) ?>"><?= e($p['name']) ?></a></div>
            <div class="price"><?= number_format((float)($p['price'] ?? 0), 0, ',', '.') ?> đ</div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <nav class="pager">
    <?php if ($page > 1): ?>
      <a href="/products?page=<?= $page-1 ?>">← Trang trước</a>
    <?php endif; ?>
    <a href="/products?page=<?= $page+1 ?>">Trang sau →</a>
  </nav>

  <p class="muted">© <?= date('Y') ?> Mini Market</p>
</body>
</html>
