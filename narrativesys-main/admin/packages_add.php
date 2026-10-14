<?php
require_once __DIR__ . '/../includes/helpers.php';

// Admin portal: admins only (add 'staff' here to let staff in too)
$authUser = require_login_page('admin/packages_add.php', ['admin']);

$pkg = ['name' => '', 'description' => '', 'price' => '', 'duration' => '', 'banner_photo' => null,
        'status' => 'available', 'is_popular' => 0, 'inclusions' => []];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pkg    = array_merge($pkg, package_from_post($_POST));
    $errors = validate_package($pkg, $_FILES['banner'] ?? [], true, null);

    if (!$errors) {
        $banner = store_banner($_FILES['banner']);
        if (!$banner) {
            $errors['banner'] = 'Could not save the uploaded file. Check folder permissions on /uploads/packages.';
        } else {
            save_package($pkg, $banner);
            flash('Package “' . $pkg['name'] . '” created.');
            header('Location: packages_manage.php'); exit;
        }
    }
}

$pageTitle = 'Add Package'; $isEdit = false; $submitLabel = 'Save Package';
require __DIR__ . '/partials/header.php';
?>
<div class="adm-head"><h1>Add Package</h1></div>
<?php if ($errors): ?><div class="alert alert-error">Please fix the highlighted fields.</div><?php endif; ?>
<?php require __DIR__ . '/partials/package_form.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
