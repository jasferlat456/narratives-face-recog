<?php
// ---- Site settings ----
$studio = [
    'name_main'  => 'Narrative',
    'name_accent'=> 'Studio PH',
];

$nav = [
    'About'        => 'index.php#about',
    'Packages'     => 'index.php#packages',
    'How It Works' => 'index.php#how-it-works',
];

// ---- Page content ----
// Packages now come from the database (managed in /admin). See sections/packages.php.
require_once __DIR__ . '/../includes/helpers.php';
try {
    $packages  = get_packages(true);   // only status = 'available'
    $loadError = false;
} catch (Throwable $t) {
    error_log('landing packages: ' . $t->getMessage());
    $packages  = [];
    $loadError = true;
}

$gallery = [
    ['images/gallery-1.jpg', 'Portrait against a pink wall'],
    ['images/gallery-2.jpg', 'Woman in a light blue sweater'],
    ['images/gallery-3.jpg', 'Couple walking beneath a palm tree'],
    ['images/gallery-4.jpg', 'Red rose on scattered book pages'],
    ['images/gallery-5.jpg', 'Hands holding a DSLR camera'],
    ['images/gallery-6.jpg', 'Portrait in a patterned collar'],
];

// Next available date: placeholder (next weekday). Replace with real data when booking is connected.
$next = new DateTime('today');
do { $next->modify('+1 day'); } while (in_array($next->format('N'), [6, 7]));
$nextDate  = $next->format('M j, Y');
$slotsLeft = 2;

$stats = [
    ['value' => '500+', 'label' => 'Happy Clients'],
    ['value' => '8',    'label' => 'Years of Stories'],
    ['value' => (string)count($packages), 'label' => 'Studio Packages'],   // live count from the database
];

$steps = [
    ['Register & Browse', 'Create your account and explore our photography packages at your own pace.'],
    ['Book a Session',    'Choose your package, pick an available date and time that works for you.'],
    ['Shoot Day',         'Arrive at the studio. We handle everything — direction, lighting, and the magic.'],
    ['Download & Keep',   'Receive your edited photos in your online gallery. Download anytime, forever.'],
];