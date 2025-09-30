<?php /** @var array $errors */ /** @var array $old */ ?>
<?php ob_start(); ?>
<h1>Đăng nhập</h1>

<?php if (!empty($errors['common'])): ?>
  <p style="color:red"><?= htmlspecialchars($errors['common']) ?></p>
<?php endif; ?>

<form method="post" action="/login">
  <div>
    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
    <?php if (!empty($errors['email'])): ?>
      <div style="color:red"><?= htmlspecialchars($errors['email']) ?></div>
    <?php endif; ?>
  </div>

  <div>
    <label>Mật khẩu</label><br>
    <input type="password" name="password">
    <?php if (!empty($errors['password'])): ?>
      <div style="color:red"><?= htmlspecialchars($errors['password']) ?></div>
    <?php endif; ?>
  </div>

  <button type="submit">Đăng nhập</button>
</form>

<?php $content = ob_get_clean(); ?>
<?php $title = 'Đăng nhập'; ?>
<?php require __DIR__ . '/../layouts/main.php'; ?>
