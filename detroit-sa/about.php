<?php
require __DIR__ . '/includes/bootstrap.php';
$page_key = 'about';
$page_title = 'About Us';
$page_desc = '';
include __DIR__ . '/includes/header.php';
$A = $C['about'];
?>
<?= page_hero($A['title'], $A['sub']) ?>
<?= render_blocks($C, 'about', 'top') ?>

<section class="section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6" data-aos="fade-right">
        <span class="eyebrow dark">Our story</span>
        <h2><?= e($A['heading']) ?></h2>
        <?= paras($A['story']) ?>
      </div>
      <?php if (asset($A['image']) !== ''): ?>
      <div class="col-lg-6" data-aos="fade-left">
        <div class="about-frame">
          <img class="about-img" src="<?= asset($A['image']) ?>" alt="<?= e($S['name']) ?> technicians at work" loading="lazy">
          <?php if (trim((string)$A['badge_title']) !== ''): ?><div class="about-badge"><b><?= e($A['badge_title']) ?></b><span><?= e($A['badge_text']) ?></span></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if (count($A['highlights']) > 0): ?>
<section class="section section-alt">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($A['highlights'] as $i => $h): ?>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= (int)($i * 100) ?>"><div class="svc-card plain h-100"><i class="bi <?= e(icon_class($h['icon'])) ?>"></i><h3><?= e($h['title']) ?></h3><p><?= e($h['text']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">Approvals &amp; partners</span><h2><?= e($A['partners_title']) ?></h2>
      <?php if (trim((string)$A['partners_text']) !== ''): ?><p><?= e($A['partners_text']) ?></p><?php endif; ?></div>
    <div class="row g-4">
      <?php if (trim((string)$A['rmi_text']) !== ''): ?>
      <div class="col-lg-4" data-aos="fade-up"><div class="partner-card h-100"><i class="bi bi-patch-check-fill"></i><h3>RMI Approved</h3><p><?= e($A['rmi_text']) ?></p></div></div>
      <?php endif; ?>
      <?php if (count($A['fleet']) > 0): ?>
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100"><div class="partner-card h-100"><i class="bi bi-truck"></i><h3><?= e($A['fleet_label']) ?></h3><div class="chips"><?php foreach ($A['fleet'] as $f): ?><span class="chip"><?= e($f) ?></span><?php endforeach; ?></div></div></div>
      <?php endif; ?>
      <?php if (count($A['insurers']) > 0): ?>
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200"><div class="partner-card h-100"><i class="bi bi-shield-check"></i><h3><?= e($A['insurers_label']) ?></h3><div class="chips"><?php foreach ($A['insurers'] as $f): ?><span class="chip"><?= e($f) ?></span><?php endforeach; ?></div></div></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if (count($A['faq']) > 0): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">FAQ</span><h2><?= e($A['faq_title']) ?></h2></div>
    <div class="accordion faq mx-auto" id="faq" data-aos="fade-up">
      <?php foreach ($A['faq'] as $i => $f): ?>
      <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button<?= $i === 0 ? '' : ' collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#f<?= (int)$i ?>"><?= e($f['q']) ?></button></h3>
        <div id="f<?= (int)$i ?>" class="accordion-collapse collapse<?= $i === 0 ? ' show' : '' ?>" data-bs-parent="#faq"><div class="accordion-body"><?= nl2br(e($f['a'])) ?></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($C, 'about', 'bottom') ?>
<?= cta_band($C) ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
