<?php /** @var array $products */ ?>
<?php ob_start(); ?>
<h1>Sản phẩm mới</h1>

<?php if (empty($products)): ?>
  <p>Chưa có sản phẩm.</p>
<?php else: ?>
  <ul>
    <?php foreach ($products as $p): ?>
      <li>
        <a href="/products/<?= htmlspecialchars($p['slug'] ?? '') ?>">
          <?= htmlspecialchars($p['name'] ?? 'No name') ?>
        </a>
        — <?= number_format((float)($p['price'] ?? 0), 0, ',', '.') ?> đ
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php $title = 'Trang chủ'; ?>
<?php require __DIR__ . '/../layouts/main.php'; ?>
