<?php
require_once __DIR__ . '/../includes/helpers.php';

// Admin portal: admins only (add 'staff' here to let staff in too)
$authUser = require_login_page('admin/packages_manage.php', ['admin']);

// ---- Delete / toggle status (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && ($p = get_package($id))) {
        db()->prepare('DELETE FROM packages WHERE id = ?')->execute([$id]); // inclusions cascade
        delete_banner($p['banner_photo']);
        flash('Package deleted.');
    } elseif ($action === 'toggle') {
        db()->prepare("UPDATE packages SET status = IF(status='available','unavailable','available') WHERE id = ?")->execute([$id]);
        flash('Status updated.');
    }
    header('Location: packages_manage.php'); exit;
}

$packages  = get_packages();
$pageTitle = 'All Packages';
require __DIR__ . '/partials/header.php';
?>
<div class="adm-head">
  <h1>All Packages</h1>
  <a class="btn" href="packages_add.php">+ Add Package</a>
</div>

<div class="card tbl-wrap" style="padding:0">
<?php if (!$packages): ?>
  <p style="padding:24px;color:var(--muted)">No packages yet. Click “Add Package” to create one.</p>
<?php else: ?>
  <table>
    <thead><tr><th>Banner</th><th>Package</th><th>Price</th><th>Inclusions</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($packages as $p): ?>
      <tr>
        <td><div class="thumb" <?= $p['banner_photo'] ? 'style="background-image:url(\'' . e(banner_url($p['banner_photo'])) . '\')"' : '' ?>></div></td>
        <td>
          <strong><?= e($p['name']) ?></strong> <?php if ($p['is_popular']): ?><span class="star">★ Popular</span><?php endif; ?><br>
          <span class="hint"><?= e($p['description']) ?><?= $p['duration'] ? ' · ' . e($p['duration']) : '' ?></span>
        </td>
        <td><?= peso($p['price']) ?></td>
        <td><?= count($p['inclusions']) ?> items</td>
        <td><span class="pill pill-<?= e($p['status']) ?>"><?= ucfirst(e($p['status'])) ?></span></td>
        <td>
          <div class="acts">
            <a class="btn btn-ghost btn-sm" href="packages_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-ghost btn-sm" name="action" value="toggle">
                <?= $p['status'] === 'available' ? 'Set Unavailable' : 'Set Available' ?>
              </button>
            </form>
            <form method="post" onsubmit="return confirm('Delete “<?= e(addslashes($p['name'])) ?>”? This cannot be undone.')">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-danger btn-sm" name="action" value="delete">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
