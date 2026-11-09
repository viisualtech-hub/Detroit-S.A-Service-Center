<?php
// Expects header.php to have run first ($C, $S, $logo are defined there).
$socials = array(
    array('facebook', 'bi-facebook', 'Facebook'),
    array('tiktok', 'bi-tiktok', 'TikTok'),
    array('instagram', 'bi-instagram', 'Instagram'),
);
?>
<footer class="site-footer">
<div class="container">
<div class="row g-4">
  <div class="col-lg-4">
    <img class="foot-logo" src="<?= e($logo) ?>" alt="<?= e($S['name']) ?>" loading="lazy">
    <p><?= e($S['footer_text']) ?></p>
    <div class="social">
      <?php foreach ($socials as $so): if (trim((string)$S[$so[0]]) === '') { continue; } ?>
      <a href="<?= e($S[$so[0]]) ?>" target="_blank" rel="noopener" aria-label="<?= e($S['short_name']) ?> on <?= e($so[2]) ?>"><i class="bi <?= e($so[1]) ?>"></i></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <h6>Pages</h6>
    <ul class="list-unstyled">
      <?php foreach ($nav as $file => $label): ?><li><a href="<?= e($file) ?>"><?= e($label) ?></a></li><?php endforeach; ?>
    </ul>
  </div>
  <div class="col-6 col-lg-3">
    <h6>Hours</h6>
    <ul class="list-unstyled">
      <?php foreach ($S['hours'] as $h): ?><li><?= e($h['days']) ?>: <?= e($h['time']) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <div class="col-lg-3">
    <h6>Contact</h6>
    <ul class="list-unstyled">
      <li><i class="bi bi-telephone"></i> <a href="tel:<?= e($S['phone_intl']) ?>"><?= e($S['phone']) ?></a></li>
      <?php if (trim((string)$S['cell']) !== ''): ?><li><i class="bi bi-phone"></i> <a href="tel:<?= e($S['cell_intl']) ?>"><?= e($S['cell']) ?></a></li><?php endif; ?>
      <?php if (trim((string)$S['cell2']) !== ''): ?><li><i class="bi bi-phone"></i> <a href="tel:<?= e($S['cell2_intl']) ?>"><?= e($S['cell2']) ?></a></li><?php endif; ?>
      <li><i class="bi bi-envelope"></i> <a href="mailto:<?= e($S['email']) ?>"><?= e($S['email']) ?></a></li>
      <li><i class="bi bi-geo-alt"></i> <?= e($S['address']) ?></li>
    </ul>
  </div>
</div>
<div class="foot-bottom">&copy; <span id="year"><?= date('Y') ?></span> <?= e($S['name']) ?> &middot; All rights reserved</div>
</div>
</footer>

<div class="modal fade" id="lightbox" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content bg-transparent border-0">
  <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
  <img id="lbImg" class="img-fluid" alt="">
  <div class="lb-bar"><button id="lbPrev" type="button" aria-label="Previous"><i class="bi bi-chevron-left"></i></button><span id="lbCap"></span><button id="lbNext" type="button" aria-label="Next"><i class="bi bi-chevron-right"></i></button></div>
</div></div></div>

<div class="mobile-bar">
  <a href="tel:<?= e($S['phone_intl']) ?>"><i class="bi bi-telephone-fill"></i>Call</a>
  <a href="https://wa.me/<?= e($S['whatsapp']) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i>WhatsApp</a>
  <a href="contact.php" class="book"><i class="bi bi-calendar-check"></i>Book</a>
</div>
<a class="wa-float" href="https://wa.me/<?= e($S['whatsapp']) ?>" target="_blank" rel="noopener" aria-label="WhatsApp us"><i class="bi bi-whatsapp"></i></a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="js/main.js"></script>
</body>
</html>
