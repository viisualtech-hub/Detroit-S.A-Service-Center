<?php
require __DIR__ . '/includes/bootstrap.php';
$page_key = 'parts';
$page_title = 'Parts';
$page_desc = '';
include __DIR__ . '/includes/header.php';
$X = $C['parts'];
$makes = array();
foreach ($C['home']['brand_logos'] as $b) { if (trim((string)$b['name']) !== '') { $makes[] = $b['name']; } }
?>
<?= page_hero($X['title'], $X['sub']) ?>
<?= render_blocks($C, 'parts', 'top') ?>

<section class="section">
  <div class="container">
    <?php if (trim((string)$X['intro']) !== ''): ?>
    <div class="section-head" data-aos="fade-up"><div class="parts-intro"><?= paras($X['intro']) ?></div></div>
    <?php endif; ?>
    <?php if (count($X['categories']) > 0): ?>
    <?php if (trim((string)$X['categories_title']) !== ''): ?><h2 class="text-center mb-4" data-aos="fade-up"><?= e($X['categories_title']) ?></h2><?php endif; ?>
    <div class="row g-4">
      <?php foreach ($X['categories'] as $i => $cat): $im = asset($cat['image']); ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= (int)(($i % 3) * 100) ?>">
        <div class="svc-card h-100">
          <?php if ($im !== ''): ?><div class="svc-img"><img src="<?= $im ?>" alt="<?= e($cat['title']) ?>" loading="lazy"></div><?php endif; ?>
          <i class="bi <?= e(icon_class($cat['icon'])) ?>"></i>
          <h3><?= e($cat['title']) ?></h3>
          <p><?= e($cat['text']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if (count($C['home']['brand_logos']) > 0): ?>
<section class="section section-alt">
  <div class="container">
    <div class="logos-block" data-aos="fade-up">
      <h3 class="logos-title"><?= e($X['logos_title']) ?></h3>
      <?= logo_marquee($C['home']['brand_logos']) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (count($X['steps']) > 0): ?>
<section class="section section-navy">
  <div class="container">
    <div class="section-head light" data-aos="fade-up"><span class="eyebrow">Simple</span><h2><?= e($X['steps_title']) ?></h2></div>
    <div class="row g-4 steps">
      <?php foreach ($X['steps'] as $i => $st): ?>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= (int)($i * 100) ?>"><div class="step"><i><?= (int)($i + 1) ?></i><h3><?= e($st['title']) ?></h3><p><?= e($st['text']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" id="request-part">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5" data-aos="fade-right">
        <div class="info-card"><h3><i class="bi bi-telephone-fill"></i> Prefer to call?</h3>
          <ul class="list-unstyled mb-0">
            <li>Tel: <a href="tel:<?= e($S['phone_intl']) ?>"><?= e($S['phone']) ?></a></li>
            <?php if (trim((string)$S['cell']) !== ''): ?><li>Cell: <a href="tel:<?= e($S['cell_intl']) ?>"><?= e($S['cell']) ?></a></li><?php endif; ?>
            <?php if (trim((string)$S['cell2']) !== ''): ?><li>Cell: <a href="tel:<?= e($S['cell2_intl']) ?>"><?= e($S['cell2']) ?></a></li><?php endif; ?>
            <li>Email: <a href="mailto:<?= e($S['email']) ?>"><?= e($S['email']) ?></a></li>
          </ul></div>
        <div class="info-card"><h3><i class="bi bi-clock-fill"></i> Opening hours</h3>
          <table class="table table-sm mb-0"><?php foreach ($S['hours'] as $h): ?><tr><td><?= e($h['days']) ?></td><td class="text-end"><?= e($h['time']) ?></td></tr><?php endforeach; ?></table></div>
        <div class="info-card"><h3><i class="bi bi-geo-alt-fill"></i> Collect from</h3><p class="mb-0"><?= e($S['address']) ?></p></div>
      </div>
      <div class="col-lg-7" data-aos="fade-left">
        <div class="form-card">
          <h3><?= e($X['form_title']) ?></h3>
          <p class="text-muted"><?= e($X['form_text']) ?></p>
          <div class="form-alert alert d-none" role="alert"></div>
          <form id="partsForm" data-ajax action="php/send-mail.php" method="POST" novalidate>
            <input type="hidden" name="form" value="parts">
            <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="pname">Full name</label><input class="form-control" id="pname" name="name" maxlength="100" required></div>
              <div class="col-md-6"><label class="form-label" for="pphone">Phone</label><input class="form-control" id="pphone" name="phone" type="tel" maxlength="30" required></div>
              <div class="col-md-6"><label class="form-label" for="pemail">Email (optional)</label><input class="form-control" id="pemail" name="email" type="email" maxlength="120"></div>
              <div class="col-md-6"><label class="form-label" for="pbrand">Vehicle make</label>
                <select class="form-select" id="pbrand" name="brand" required><option value="">Choose&hellip;</option><?php foreach ($makes as $m): ?><option><?= e($m) ?></option><?php endforeach; ?><option>Other</option></select></div>
              <div class="col-md-6"><label class="form-label" for="pmodel">Model and year</label><input class="form-control" id="pmodel" name="model" maxlength="80" placeholder="e.g. Jeep Wrangler 2018" required></div>
              <div class="col-md-6"><label class="form-label" for="ppart">Part needed</label><input class="form-control" id="ppart" name="part" maxlength="120" placeholder="e.g. clutch pack, water pump" required></div>
              <div class="col-12"><label class="form-label" for="pnotes">Chassis (VIN) number or extra details (optional)</label><textarea class="form-control" id="pnotes" name="message" rows="3" maxlength="1500"></textarea></div>
              <div class="col-12"><button class="btn btn-brand btn-lg" type="submit">Send request</button></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (count($X['faq']) > 0): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head" data-aos="fade-up"><span class="eyebrow dark">FAQ</span><h2><?= e($X['faq_title']) ?></h2></div>
    <div class="accordion faq mx-auto" id="pfaq" data-aos="fade-up">
      <?php foreach ($X['faq'] as $i => $f): ?>
      <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button<?= $i === 0 ? '' : ' collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#pf<?= (int)$i ?>"><?= e($f['q']) ?></button></h3>
        <div id="pf<?= (int)$i ?>" class="accordion-collapse collapse<?= $i === 0 ? ' show' : '' ?>" data-bs-parent="#pfaq"><div class="accordion-body"><?= nl2br(e($f['a'])) ?></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($C, 'parts', 'bottom') ?>
<?= cta_band($C) ?>
<?php include __DIR__ . '/includes/footer.php'; ?>