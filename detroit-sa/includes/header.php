<?php
// Expects $page_key, $page_title and $page_desc to be set by the page.
$C = C();
$S = $C['site'];
$nav = array(
    'index.php' => nav_label($S, 'home', 'Home'),
    'about.php' => nav_label($S, 'about', 'About'),
    'services.php' => nav_label($S, 'services', 'Services'),
    'parts.php' => nav_label($S, 'parts', 'Parts'),
    'gallery.php' => nav_label($S, 'gallery', 'Gallery'),
    'contact.php' => nav_label($S, 'contact', 'Contact'),
);
$nav_active = array('home' => 'index.php', 'about' => 'about.php', 'services' => 'services.php', 'parts' => 'parts.php', 'gallery' => 'gallery.php', 'contact' => 'contact.php');
$logo = safe_path($S['logo']);
if ($logo === '') { $logo = 'images/logo.png'; }
$full_title = trim((string)$page_title) === '' ? $S['name'] : $page_title . ' | ' . $S['name'] . ', Durban';
$desc = trim((string)$page_desc) !== '' ? $page_desc : $S['meta_description'];
$notices = active_notices($C);
$notice_icons = array('info' => 'bi-info-circle-fill', 'warning' => 'bi-exclamation-triangle-fill', 'promo' => 'bi-megaphone-fill', 'closed' => 'bi-calendar-x-fill');
record_view($page_key);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($full_title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta property="og:title" content="<?= e($full_title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:type" content="website">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="icon" type="image/png" href="images/favicon.png">
<link href="css/style.css" rel="stylesheet">
<link href="css/extra.css" rel="stylesheet">
</head>
<body>
<?php foreach ($notices as $n):
    $type = isset($notice_icons[$n['type']]) ? $n['type'] : 'info'; ?>
<div class="notice notice-<?= e($type) ?>" data-notice="<?= e($n['id']) ?>" role="status">
  <div class="container">
    <i class="bi <?= e($notice_icons[$type]) ?>"></i>
    <div class="notice-text">
      <?php if (trim((string)$n['title']) !== ''): ?><b><?= e($n['title']) ?></b> <?php endif; ?>
      <?= e($n['message']) ?>
      <?php if (!empty($n['link'])): ?> <a href="<?= e($n['link']) ?>"><?= e($n['link_text'] !== '' ? $n['link_text'] : 'Learn more') ?></a><?php endif; ?>
    </div>
    <button type="button" class="notice-close" aria-label="Dismiss notice">&times;</button>
  </div>
</div>
<?php endforeach; ?>
<div class="topbar d-none d-md-block"><div class="container d-flex justify-content-between">
  <span><i class="bi bi-geo-alt-fill"></i> <?= e($S['address']) ?></span>
  <span><i class="bi bi-clock"></i> <?= e($S['hours_short']) ?></span>
</div></div>
<nav class="navbar navbar-expand-lg sticky-top main-nav">
  <div class="container">
    <a class="navbar-brand" href="index.php" aria-label="<?= e($S['name']) ?> home"><img class="brand-logo" src="<?= e($logo) ?>" alt="<?= e($S['name']) ?>" width="210" height="62"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <?php foreach ($nav as $file => $label): ?>
        <li class="nav-item"><a class="nav-link<?= (isset($nav_active[$page_key]) && $nav_active[$page_key] === $file) ? ' active' : '' ?>" href="<?= e($file) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item ms-lg-3"><a class="btn btn-brand" href="tel:<?= e($S['phone_intl']) ?>"><i class="bi bi-telephone-fill"></i> <?= e($S['phone']) ?></a></li>
      </ul>
    </div>
  </div>
</nav>
