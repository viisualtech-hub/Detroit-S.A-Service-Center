<?php
require __DIR__ . '/includes/bootstrap.php';
$page_key = 'home';
$page_title = '';
$page_desc = '';
$__c = C();
$page_title = $__c['home']['seo_title'];
include __DIR__ . '/includes/header.php';
$H = $C['home'];

$slides = array();
foreach ($C['hero']['slides'] as $sl) {
    if (!empty($sl['active']) && asset($sl['image']) !== '') { $slides[] = $sl; }
}
$interval = max(2, min(15, (int)$C['hero']['interval'])) * 1000;

$home_services = array();
$n = 0;
foreach ($C['services'] as $sv) {
    $n++;
    if (!empty($sv['home'])) { $home_services[] = array($sv, $n); }
}

$reviews = array();
foreach ($C['testimonials'] as $t) {
    if (!empty($t['active']) && trim((string)$t['text']) !== '') { $reviews[] = $t; }
}
?>

<?php if (count($slides) > 0): ?>
<section class="hero-carousel">
  <div id="heroSlider" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="<?= (int)$interval ?>">
    <?php if (count($slides) > 1): ?>
    <div class="carousel-indicators">
      <?php foreach ($slides as $i => $sl): ?>
      <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="<?= (int)$i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Slide <?= (int)($i + 1) ?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="carousel-inner">
      <?php foreach ($slides as $i => $sl): ?>
      <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>"><img src="<?= asset($sl['image']) ?>" alt="<?= e($sl['alt']) ?>" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>></div>
      <?php endforeach; ?>
    </div>
    <?php if (count($slides) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
    <?php endif; ?>
  </div>
  <div class="hazard"></div>
</section>
<?php endif; ?>

<?php if (count($H['ticker']) > 0): ?>
<div class="ticker" aria-hidden="true"><div class="ticker-track">
  <?php for ($r = 0; $r < 2; $r++): foreach ($H['ticker'] as $w): ?><span><?= e($w) ?></span><?php endforeach; endfor; ?>
</div></div>
<?php endif; ?>

<section class="hero hero-intro">
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-7" data-aos="fade-right">
        <?php if (trim((string)$H['eyebrow']) !== ''): ?><span class="eyebrow"><i class="bi bi-geo-alt-fill"></i> <?= e($H['eyebrow']) ?></span><?php endif; ?>
        <h1><?php foreach ($H['hero_lines'] as $ln): ?><span class="hl-<?= $ln['color'] === 'red' ? 'red' : 'white' ?>"><?= e($ln['text']) ?></span><br><?php endforeach; ?></h1>
        <div class="lead"><?= paras($H['hero_lead']) ?></div>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($H['hero_buttons'] as $bt): if (trim((string)$bt['text']) === '') { continue; } ?>
          <a href="<?= e(safe_link($bt['link'])) ?>" class="btn btn-lg <?= $bt['style'] === 'outline' ? 'btn-outline-light' : 'btn-brand' ?>"><?= e($bt['text']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if (asset($H['hero_photo']) !== ''): ?>
      <div class="col-lg-5 mt-5 mt-lg-0" data-aos="fade-left" data-aos-delay="150">
        <div class="hero-photo">
          <img src="<?= asset($H['hero_photo']) ?>" alt="<?= e($H['hero_photo_alt']) ?>" loading="lazy">
          <?php if (trim((string)$H['hero_tag']) !== ''): ?><div class="hero-tag"><i class="bi bi-patch-check-fill"></i> <?= e($H['hero_tag']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if (count($H['info_items']) > 0): ?>
    <div class="info-wrap" data-aos="fade-up">
      <?php if (trim((string)$H['info_title']) !== ''): ?><h2 class="info-title"><?= e($H['info_title']) ?></h2><?php endif; ?>
      <div class="row g-3">
        <?php foreach ($H['info_items'] as $it): ?>
        <div class="col-md-6 col-xl-4"><div class="info-tile h-100"><i class="bi <?= e(icon_class($it['icon'])) ?>"></i><div><h3><?= e($it['title']) ?></h3><p><?= e($it['text']) ?></p></div></div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (count($H['stats']) > 0): ?>
    <div class="row g-3 hero-stats mt-4" data-aos="fade-up" data-aos-delay="200">
      <?php foreach ($H['stats'] as $st): ?>
      <div class="col-6 col-md"><b><?= e($st['value']) ?></b><span><?= e($st['label']) ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="hazard"></div>
</section>

<?= render_blocks($C, 'home', 'top') ?>

<?php if (trim((string)$H['title_1'] . (string)$H['title_2'] . (string)$H['lead']) !== ''): ?>
<section class="section intro-section">
  <div class="container">
    <div class="row justify-content-center"><div class="col-lg-10 text-center" data-aos="fade-up">
      <?php if (trim((string)$H['intro_eyebrow']) !== ''): ?><span class="eyebrow dark"><?= e($H['intro_eyebrow']) ?></span><?php endif; ?>
      <h2 class="intro-title"><?= e($H['title_1']) ?> <span><?= e($H['title_2']) ?></span></h2>
      <div class="intro-lead"><?= paras($H['lead']) ?></div>
      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <?php if (trim((string)$H['btn1']) !== ''): ?><a href="contact.php" class="btn btn-brand btn-lg"><?= e($H['btn1']) ?></a><?php endif; ?>
        <?php if (trim((string)$H['btn2']) !== ''): ?><a href="services.php" class="btn btn-navy btn-lg"><?= e($H['btn2']) ?></a><?php endif; ?>
      </div>
    </div></div>
  </div>
</section>
<?php endif; ?>

<section class="section section-alt">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">Makes we work on</span><h2><?= e($H['makes_title']) ?></h2>
      <?php if (trim((string)$H['makes_text']) !== ''): ?><p><?= e($H['makes_text']) ?></p><?php endif; ?></div>
    <div class="row g-4">
      <?php foreach ($H['specialists'] as $i => $b): ?>
      <div class="col-md-4" data-aos="zoom-in" data-aos-delay="<?= (int)($i * 100) ?>"><div class="brand-card"><h3><?= e($b['name']) ?></h3><p><?= e($b['text']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
    <?php if (count($H['brand_logos']) > 0): ?>
    <div class="logos-block" data-aos="fade-up">
      <?php if (trim((string)$H['logos_title']) !== ''): ?><h3 class="logos-title"><?= e($H['logos_title']) ?></h3><?php endif; ?>
      <?= logo_marquee($H['brand_logos']) ?>
      <?php if (count($H['other_makes']) > 0): ?><p class="logos-more"><?php foreach ($H['other_makes'] as $m): ?><span class="chip"><?= e($m) ?></span> <?php endforeach; ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">What we do</span><h2><?= e($H['services_title']) ?></h2>
      <?php if (trim((string)$H['services_text']) !== ''): ?><p><?= e($H['services_text']) ?></p><?php endif; ?></div>
    <div class="row g-4">
      <?php foreach ($home_services as $k => $pair) { echo service_card($pair[0], $pair[1], ($k % 3) * 100); } ?>
    </div>
    <div class="text-center mt-5" data-aos="fade-up"><a href="services.php" class="btn btn-navy btn-lg">See all services</a></div>
  </div>
</section>

<?php if (!empty($C['partners']['show_home'])) { echo render_partners($C); } ?>

<?php if (count($H['why']) > 0): ?>
<section class="section">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">Why us</span><h2><?= e($H['why_title']) ?></h2></div>
    <div class="row g-4">
      <?php foreach ($H['why'] as $i => $w): ?>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= (int)($i * 100) ?>"><div class="why"><b><?= sprintf('%02d', $i + 1) ?></b><h3><?= e($w['title']) ?></h3><p><?= e($w['text']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (count($H['steps']) > 0): ?>
<section class="section section-navy">
  <div class="container">
    <div class="section-head light" data-aos="fade-up"><span class="eyebrow">Process</span><h2><?= e($H['steps_title']) ?></h2></div>
    <div class="row g-4 steps">
      <?php foreach ($H['steps'] as $i => $st): ?>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= (int)($i * 100) ?>"><div class="step"><i><?= (int)($i + 1) ?></i><h3><?= e($st['title']) ?></h3><p><?= e($st['text']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (count($reviews) > 0): ?>
<section class="section section-alt testimonials">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">Testimonials</span><h2><?= e($H['reviews_title']) ?></h2>
      <?php if (trim((string)$H['reviews_text']) !== ''): ?><p><?= e($H['reviews_text']) ?></p><?php endif; ?></div>
    <div class="row g-4">
      <?php foreach ($reviews as $i => $t): $stars = max(1, min(5, (int)$t['rating'])); ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= (int)(($i % 3) * 100) ?>"><figure class="testi h-100">
        <div class="stars" aria-label="<?= (int)$stars ?> out of 5 stars"><?php for ($s = 0; $s < $stars; $s++) { echo '<i class="bi bi-star-fill"></i>'; } ?></div>
        <blockquote><?= nl2br(e($t['text'])) ?></blockquote>
        <figcaption><span class="avatar"><?= e(initial($t['name'])) ?></span><div><b><?= e($t['name']) ?></b><small><?= e($t['vehicle']) ?></small></div></figcaption>
      </figure></div>
      <?php endforeach; ?>
    </div>
    <?php if (trim((string)$S['facebook']) !== ''): ?>
    <div class="text-center mt-5" data-aos="fade-up"><a class="btn btn-navy btn-lg" href="<?= e($S['facebook']) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i> See more on Facebook</a></div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($C, 'home', 'bottom') ?>
<?= cta_band($C) ?>
<?php include __DIR__ . '/includes/footer.php'; ?>