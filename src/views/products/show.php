<?php
/**
 * Biến truyền vào:
 *  - $product : thông tin sản phẩm (id, name, price, description, image_url, ...)
 */
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title><?= e($product['name'] ?? 'Chi tiết sản phẩm') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:24px}
    .wrap{display:grid;grid-template-columns:1fr 1fr;gap:24px}
    img{width:100%;max-width:520px;aspect-ratio:4/3;object-fit:cover;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:10px}
    .price{font-size:20px;font-weight:700;margin:8px 0}
    .muted{color:#6b7280}
    form{display:flex;gap:8px;align-items:center;margin-top:12px}
    input[type="number"]{width:80px;padding:8px}
    button{padding:10px 16px;border:0;border-radius:8px;background:#111827;color:#fff;cursor:pointer}
  </style>
</head>
<body>
  <p><a href="/">Trang chủ</a> | <a href="/products">Sản phẩm</a> | <strong><?= e($product['name'] ?? '') ?></strong></p>

  <div class="wrap">
    <div>
      <img src="<?= e($product['image_url'] ?? '/assets/images/placeholder.png') ?>" alt="<?= e($product['name'] ?? '') ?>">
    </div>
    <div>
      <h1><?= e($product['name'] ?? '') ?></h1>
      <div class="price"><?= number_format((float)($product['price'] ?? 0), 0, ',', '.') ?> đ</div>
      <p class="muted"><?= nl2br(e($product['description'] ?? '')) ?></p>

      <!-- Form thêm vào giỏ -->
      <form method="post" action="/cart">
        <input type="hidden" name="product_id" value="<?= e((string)($product['id'] ?? '')) ?>">
        <label for="qty">Số lượng:</label>
        <input id="qty" type="number" name="qty" value="1" min="1">
        <button type="submit">Thêm vào giỏ</button>
      </form>
    </div>
  </div>

  <p class="muted">© <?= date('Y') ?> Mini Market</p>
</body>
</html>
