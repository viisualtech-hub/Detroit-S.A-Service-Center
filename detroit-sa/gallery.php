<?php
require __DIR__ . '/includes/bootstrap.php';
$page_key = 'gallery';
$page_title = 'Gallery';
$page_desc = '';
include __DIR__ . '/includes/header.php';
$P = $C['pages']['gallery'];

$cats = array();
foreach ($C['gallery'] as $g) {
    $c = trim((string)$g['category']);
    if ($c !== '' && !isset($cats[slug($c)])) { $cats[slug($c)] = $c; }
}
?>
<?= page_hero($P['title'], $P['sub']) ?>
<?= render_blocks($C, 'gallery', 'top') ?>

<section class="section">
  <div class="container">
    <?php if (count($cats) > 1): ?>
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4" data-aos="fade-up">
      <button type="button" class="btn btn-sm filter-btn active" data-filter="all">All</button>
      <?php foreach ($cats as $k => $label): ?><button type="button" class="btn btn-sm filter-btn" data-filter="<?= e($k) ?>"><?= e($label) ?></button><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="row g-3" id="gallery">
      <?php foreach ($C['gallery'] as $i => $g): if (asset($g['image']) === '') { continue; } ?>
      <div class="col-6 col-lg-3 g-item" data-cat="<?= e(slug($g['category'])) ?>">
        <a href="<?= asset($g['image']) ?>" class="g-thumb" data-group="gallery" data-caption="<?= e($g['caption']) ?>"><img src="<?= asset($g['image']) ?>" alt="<?= e($g['caption'] !== '' ? $g['caption'] : $S['name']) ?>" loading="lazy"><?php if ($g['caption'] !== ''): ?><span><?= e($g['caption']) ?></span><?php endif; ?></a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($C['gallery']) === 0): ?><p class="text-center text-muted">Photos coming soon.</p><?php else: ?>
    <p class="text-center text-muted mt-4 small">Tap a photo to view it larger.</p><?php endif; ?>
  </div>
</section>

<?= render_blocks($C, 'gallery', 'bottom') ?>
<?= cta_band($C) ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
