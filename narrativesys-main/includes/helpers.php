<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/helpers.php';   // login/register module: sessions, current_user(), role guards

/** Start the session only when needed (CSRF/flash). Uses the same session as the login module. */
function ensure_session(): void {
    start_auth_session();
}

/** The logged-in user (or null). Never breaks the public page if the users table is missing. */
function viewer(): ?array {
    static $u = false;
    if ($u === false) {
        try { $u = current_user(); } catch (Throwable $t) { error_log('viewer: ' . $t->getMessage()); $u = null; }
    }
    return $u;
}

/** Book Now*/
function book_url(?int $packageId = null): string {
    $target = 'booking.php' . ($packageId ? '?package_id=' . $packageId : '');
    return viewer() ? $target : 'login.php?next=' . rawurlencode($target);
}

const UPLOAD_DIR = __DIR__ . '/../uploads/packages/';
const UPLOAD_URL = BASE_URL . '/uploads/packages/';

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

function peso($n): string {
    $n = (float)$n;
    return '₱' . number_format($n, $n == floor($n) ? 0 : 2);
}

function banner_url(?string $file): string {
    return $file ? UPLOAD_URL . rawurlencode($file) : '';
}

/* ---------- CSRF + flash ---------- */
function csrf_token(): string {
    ensure_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    ensure_session();
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid session token. Please go back and retry.');
    }
}
function flash(?string $msg = null, string $type = 'success') {
    ensure_session();
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

/* ---------- Read ---------- */
function attach_inclusions(array $rows): array {
    if (!$rows) return [];
    $ids = array_column($rows, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = db()->prepare("SELECT package_id, inclusion FROM package_inclusions
                          WHERE package_id IN ($in) ORDER BY sort_order, id");
    $st->execute($ids);
    $map = [];
    foreach ($st as $r) $map[$r['package_id']][] = $r['inclusion'];
    foreach ($rows as &$r) $r['inclusions'] = $map[$r['id']] ?? [];
    return $rows;
}

function get_packages(bool $onlyAvailable = false): array {
    $sql = 'SELECT * FROM packages' . ($onlyAvailable ? " WHERE status='available'" : '') . ' ORDER BY sort_order, id';
    return attach_inclusions(db()->query($sql)->fetchAll());
}

function get_package(int $id): ?array {
    $st = db()->prepare('SELECT * FROM packages WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ? attach_inclusions([$row])[0] : null;
}

/* ---------- Validate / save ---------- */
function package_from_post(array $post): array {
    $incl = array_values(array_filter(array_map('trim', array_filter((array)($post['inclusions'] ?? []), 'is_string')), fn($s) => $s !== ''));
    return [
        'name'        => trim($post['name'] ?? ''),
        'description' => trim($post['description'] ?? ''),
        'price'       => $post['price'] ?? '',
        'duration'    => trim($post['duration'] ?? ''),
        'status'      => ($post['status'] ?? '') === 'unavailable' ? 'unavailable' : 'available',
        'is_popular'  => !empty($post['is_popular']) ? 1 : 0,
        'inclusions'  => $incl,
    ];
}

function validate_package(array $d, array $file, bool $bannerRequired, ?int $ignoreId = null): array {
    $err = [];

    // name
    $len = mb_strlen($d['name']);
    if ($len === 0)        $err['name'] = 'Package name is required.';
    elseif ($len < 2)      $err['name'] = 'Package name must be at least 2 characters.';
    elseif ($len > 120)    $err['name'] = 'Package name must be 120 characters or fewer.';
    else {
        $st = db()->prepare('SELECT COUNT(*) FROM packages WHERE LOWER(name) = LOWER(?) AND id <> ?');
        $st->execute([$d['name'], $ignoreId ?? 0]);
        if ($st->fetchColumn() > 0) $err['name'] = 'A package with this name already exists.';
    }

    // description
    $len = mb_strlen($d['description']);
    if ($len === 0)        $err['description'] = 'Description is required.';
    elseif ($len < 3)      $err['description'] = 'Description must be at least 3 characters.';
    elseif ($len > 255)    $err['description'] = 'Description must be 255 characters or fewer.';

    // price
    $price = (string)$d['price'];
    if ($price === '')                               $err['price'] = 'Price is required.';
    elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $price)) $err['price'] = 'Enter a valid price (max 2 decimal places).';
    elseif ((float)$price <= 0)                      $err['price'] = 'Price must be greater than 0.';
    elseif ((float)$price > 999999.99)               $err['price'] = 'Price is too high.';

    if (mb_strlen($d['duration']) > 60) $err['duration'] = 'Duration must be 60 characters or fewer.';

    // inclusions
    $seen = [];
    if (!$d['inclusions'])                  $err['inclusions'] = 'Add at least one inclusion.';
    elseif (count($d['inclusions']) > 20)   $err['inclusions'] = 'A package can have at most 20 inclusions.';
    else foreach ($d['inclusions'] as $inc) {
        $k = mb_strtolower($inc);
        if (mb_strlen($inc) > 255)  { $err['inclusions'] = 'Each inclusion must be 255 characters or fewer.'; break; }
        if (isset($seen[$k]))       { $err['inclusions'] = 'Duplicate inclusion: “' . $inc . '”.'; break; }
        $seen[$k] = true;
    }

    // banner
    $hasFile = ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($bannerRequired && !$hasFile) $err['banner'] = 'Banner photo is required.';
    if ($hasFile) {
        if ($file['error'] !== UPLOAD_ERR_OK)                $err['banner'] = 'Upload failed. Try again.';
        elseif ($file['size'] > 4 * 1024 * 1024)             $err['banner'] = 'Banner must be 4 MB or smaller.';
        elseif (!in_array((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), ['image/jpeg', 'image/png', 'image/webp'], true))
                                                             $err['banner'] = 'Banner must be JPG, PNG or WEBP.';
        elseif ((@getimagesize($file['tmp_name'])[0] ?? 0) < 600) $err['banner'] = 'Banner is too small. Minimum width is 600px.';
    }
    return $err;
}

function store_banner(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])];
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    return move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name) ? $name : null;
}

function delete_banner(?string $file): void {
    if ($file && is_file(UPLOAD_DIR . $file)) @unlink(UPLOAD_DIR . $file);
}

function save_package(array $d, ?string $banner, ?int $id = null): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($id === null) {
            $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM packages')->fetchColumn();
            $pdo->prepare('INSERT INTO packages (name, description, price, duration, banner_photo, status, is_popular, sort_order)
                           VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$d['name'], $d['description'], $d['price'], $d['duration'], $banner, $d['status'], $d['is_popular'], $next]);
            $id = (int)$pdo->lastInsertId();
        } else {
            $sql = 'UPDATE packages SET name=?, description=?, price=?, duration=?, status=?, is_popular=?'
                 . ($banner ? ', banner_photo=?' : '') . ' WHERE id=?';
            $args = [$d['name'], $d['description'], $d['price'], $d['duration'], $d['status'], $d['is_popular']];
            if ($banner) $args[] = $banner;
            $args[] = $id;
            $pdo->prepare($sql)->execute($args);
            $pdo->prepare('DELETE FROM package_inclusions WHERE package_id = ?')->execute([$id]);
        }
        $ins = $pdo->prepare('INSERT INTO package_inclusions (package_id, inclusion, sort_order) VALUES (?,?,?)');
        foreach ($d['inclusions'] as $i => $text) $ins->execute([$id, $text, $i + 1]);
        $pdo->commit();
        return $id;
    } catch (Throwable $t) { $pdo->rollBack(); throw $t; }
}
