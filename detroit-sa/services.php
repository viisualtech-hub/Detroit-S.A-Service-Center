<?php
require __DIR__ . '/includes/bootstrap.php';
$page_key = 'services';
$page_title = 'Services';
$page_desc = '';
include __DIR__ . '/includes/header.php';
$P = $C['pages']['services'];
?>
<?= page_hero($P['title'], $P['sub']) ?>
<?= render_blocks($C, 'services', 'top') ?>

<section class="section">
  <div class="container">
    <?php if (trim((string)$P['intro']) !== ''): ?>
    <div class="section-head" data-aos="fade-up"><p><?= e($P['intro']) ?></p></div>
    <?php endif; ?>
    <div class="row g-4">
      <?php foreach ($C['services'] as $i => $sv) { echo service_card($sv, $i + 1, ($i % 3) * 100); } ?>
    </div>
  </div>
</section>

<?php
$alt = false;
foreach ($C['services'] as $sv):
    if (empty($sv['detail'])) { continue; }
    $all = array();
    if (asset($sv['image']) !== '') { $all[] = $sv['image']; }
    foreach ($sv['gallery'] as $g) { if (asset($g) !== '') { $all[] = $g; } }
    $alt = !$alt;
    $grp = 'svc-' . $sv['id'];
?>
<section class="section svc-detail<?= $alt ? ' section-alt' : '' ?>" id="svc-<?= e($sv['id']) ?>">
  <div class="container">
    <div class="row g-5 align-items-center<?= $alt ? '' : ' flex-lg-row-reverse' ?>">
      <div class="col-lg-6" data-aos="fade-up">
        <span class="eyebrow dark"><i class="bi <?= e(icon_class($sv['icon'])) ?>"></i> <?= e($sv['title']) ?></span>
        <h2><?= e($sv['title']) ?></h2>
        <div class="svc-long"><?= paras($sv['long'] !== '' ? $sv['long'] : $sv['short']) ?></div>
        <a href="contact.php" class="btn btn-brand btn-lg mt-2">Enquire about this</a>
      </div>
      <div class="col-lg-6" data-aos="zoom-in">
        <?php if (count($all) > 0): ?>
        <a href="<?= e($all[0]) ?>" class="g-thumb svc-main" data-group="<?= e($grp) ?>" data-caption="<?= e($sv['title']) ?>"><img src="<?= e($all[0]) ?>" alt="<?= e($sv['title']) ?>" loading="lazy"></a>
        <?php if (count($all) > 1): ?>
        <div class="svc-thumbs">
          <?php foreach (array_slice($all, 1, 11) as $img): ?>
          <a href="<?= e($img) ?>" class="g-thumb sm" data-group="<?= e($grp) ?>" data-caption="<?= e($sv['title']) ?>"><img src="<?= e($img) ?>" alt="<?= e($sv['title']) ?>" loading="lazy"></a>
          <?php endforeach; ?>
        </div>
        <?php endif; endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endforeach; ?>

<?php if (count($P['signs']) > 0): ?>
<section class="section<?= $alt ? '' : ' section-alt' ?>">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">Warning signs</span><h2><?= e($P['signs_title']) ?></h2></div>
    <div class="row g-4">
      <?php foreach ($P['signs'] as $i => $sg): ?>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= (int)($i * 100) ?>"><div class="sign-card h-100"><h3><?= e($sg['title']) ?></h3><ul><?php foreach ($sg['items'] as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul></div></div>
      <?php endforeach; ?>
    </div>
    <p class="text-center mt-4 mb-0" data-aos="fade-up">Not sure what's wrong? Call us on <a href="tel:<?= e($S['phone_intl']) ?>"><b><?= e($S['phone']) ?></b></a> and describe the symptoms.</p>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($C['partners']['show_services'])) { echo render_partners($C); } ?>
<?= render_blocks($C, 'services', 'bottom') ?>
<?= cta_band($C) ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
