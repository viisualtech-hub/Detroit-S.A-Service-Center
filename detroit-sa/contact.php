<?php
require __DIR__ . '/includes/bootstrap.php';
$page_key = 'contact';
$page_title = 'Contact & Booking';
$page_desc = '';
include __DIR__ . '/includes/header.php';
$P = $C['pages']['contact'];
$makes = array();
foreach ($C['home']['specialists'] as $b) { $makes[] = $b['name']; }
foreach ($C['home']['other_makes'] as $m) { $makes[] = $m; }
$map_q = urlencode($S['map_query'] !== '' ? $S['map_query'] : $S['address']);
?>
<?= page_hero($P['title'], $P['sub']) ?>
<?= render_blocks($C, 'contact', 'top') ?>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5" data-aos="fade-right">
        <div class="info-card"><h3><i class="bi bi-geo-alt-fill"></i> Find us</h3><p><?= nl2br(e(str_replace(', ', ",\n", $S['address']))) ?></p>
          <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e($map_q) ?>" target="_blank" rel="noopener">Get directions &rarr;</a></div>
        <div class="info-card"><h3><i class="bi bi-telephone-fill"></i> Get in touch</h3>
          <ul class="list-unstyled mb-0">
            <li>Tel: <a href="tel:<?= e($S['phone_intl']) ?>"><?= e($S['phone']) ?></a></li>
            <?php if (trim((string)$S['cell']) !== ''): ?><li>Cell: <a href="tel:<?= e($S['cell_intl']) ?>"><?= e($S['cell']) ?></a></li><?php endif; ?>
            <?php if (trim((string)$S['cell2']) !== ''): ?><li>Cell: <a href="tel:<?= e($S['cell2_intl']) ?>"><?= e($S['cell2']) ?></a></li><?php endif; ?>
            <li>Email: <a href="mailto:<?= e($S['email']) ?>"><?= e($S['email']) ?></a></li>
          </ul></div>
        <div class="info-card"><h3><i class="bi bi-clock-fill"></i> Opening hours</h3>
          <table class="table table-sm mb-0"><?php foreach ($S['hours'] as $h): ?><tr><td><?= e($h['days']) ?></td><td class="text-end"><?= e($h['time']) ?></td></tr><?php endforeach; ?></table></div>
      </div>
      <div class="col-lg-7" data-aos="fade-left">
        <div class="form-card">
          <h3><?= e($P['form_title']) ?></h3>
          <p class="text-muted"><?= e($P['form_text']) ?></p>
          <div id="formAlert" class="form-alert alert d-none" role="alert"></div>
          <form id="bookingForm" data-ajax action="php/send-mail.php" method="POST" novalidate>
            <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" name="name" maxlength="100" required></div>
              <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" type="tel" maxlength="30" required></div>
              <div class="col-md-6"><label class="form-label" for="email">Email (optional)</label><input class="form-control" id="email" name="email" type="email" maxlength="120"></div>
              <div class="col-md-6"><label class="form-label" for="brand">Vehicle make</label>
                <select class="form-select" id="brand" name="brand" required><option value="">Choose&hellip;</option><?php foreach ($makes as $m): ?><option><?= e($m) ?></option><?php endforeach; ?><option>Other</option></select></div>
              <div class="col-12"><label class="form-label" for="service">Service needed</label>
                <select class="form-select" id="service" name="service" required><option value="">Choose&hellip;</option><?php foreach ($C['services'] as $sv): ?><option><?= e($sv['title']) ?></option><?php endforeach; ?><option>Not sure</option></select></div>
              <div class="col-12"><label class="form-label" for="message">Describe the problem</label><textarea class="form-control" id="message" name="message" rows="4" maxlength="2000" required></textarea></div>
              <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-brand btn-lg" type="submit">Send request</button>
                <a class="btn btn-whatsapp btn-lg" href="https://wa.me/<?= e($S['whatsapp']) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp us</a></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?= render_blocks($C, 'contact', 'bottom') ?>
<section class="map-wrap"><iframe title="Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=<?= e($map_q) ?>&amp;output=embed"></iframe></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
