<?php
require_once __DIR__ . '/../includes/helpers.php';

// Admin portal: admins only (add 'staff' here to let staff in too)
$authUser = require_login_page('admin/packages_edit.php?id=' . (int)($_GET['id'] ?? 0), ['admin']);

$id  = (int)($_GET['id'] ?? 0);
$pkg = get_package($id);
if (!$pkg) { flash('Package not found.', 'error'); header('Location: packages_manage.php'); exit; }
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old    = $pkg;
    $pkg    = array_merge($pkg, package_from_post($_POST));
    $errors = validate_package($pkg, $_FILES['banner'] ?? [], false, $id);

    if (!$errors) {
        $new = store_banner($_FILES['banner']);          // null when no new file
        save_package($pkg, $new, $id);
        if ($new) delete_banner($old['banner_photo']);   // remove replaced banner
        flash('Package updated.');
        header('Location: packages_manage.php'); exit;
    }
    $pkg['banner_photo'] = $old['banner_photo'];
}

$pageTitle = 'Edit Package'; $isEdit = true; $submitLabel = 'Update Package';
require __DIR__ . '/partials/header.php';
?>
<div class="adm-head"><h1>Edit Package</h1></div>
<?php if ($errors): ?><div class="alert alert-error">Please fix the highlighted fields.</div><?php endif; ?>
<?php require __DIR__ . '/partials/package_form.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
