document.addEventListener('DOMContentLoaded', function () {
  // Scroll animations
  if (window.AOS) { AOS.init({ duration: 800, once: true, offset: 60 }); }

  // Dismissible notice bars (remembered for this browser session)
  document.querySelectorAll('.notice').forEach(function (n) {
    var id = n.getAttribute('data-notice');
    try { if (sessionStorage.getItem('dsa-notice-' + id)) { n.remove(); return; } } catch (e) { /* storage blocked */ }
    var btn = n.querySelector('.notice-close');
    if (btn) {
      btn.addEventListener('click', function () {
        try { sessionStorage.setItem('dsa-notice-' + id, '1'); } catch (e) { /* ignore */ }
        n.remove();
      });
    }
  });

  // Gallery filter buttons
  var btns = document.querySelectorAll('.filter-btn');
  btns.forEach(function (b) {
    b.addEventListener('click', function () {
      btns.forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');
      var f = b.dataset.filter;
      document.querySelectorAll('.g-item').forEach(function (i) {
        i.classList.toggle('hide', f !== 'all' && i.dataset.cat !== f);
      });
    });
  });

  // Photo viewer (gallery and service photos)
  var box = document.getElementById('lightbox');
  if (box && window.bootstrap) {
    var modal = new bootstrap.Modal(box);
    var img = document.getElementById('lbImg'), cap = document.getElementById('lbCap');
    var list = [], idx = 0;
    var show = function () {
      var a = list[idx];
      img.src = a.getAttribute('href');
      img.alt = a.dataset.caption || '';
      cap.textContent = a.dataset.caption || '';
    };
    document.querySelectorAll('.g-thumb').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var grp = a.dataset.group || 'all';
        list = Array.prototype.filter.call(document.querySelectorAll('.g-thumb'), function (x) {
          return (x.dataset.group || 'all') === grp && !x.closest('.g-item.hide');
        });
        idx = Math.max(0, list.indexOf(a));
        show();
        modal.show();
      });
    });
    var step = function (n) { if (list.length) { idx = (idx + n + list.length) % list.length; show(); } };
    document.getElementById('lbPrev').addEventListener('click', function () { step(-1); });
    document.getElementById('lbNext').addEventListener('click', function () { step(1); });
    box.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { step(-1); }
      if (e.key === 'ArrowRight') { step(1); }
    });
  }

  // Booking and parts forms (send to php/send-mail.php)
  document.querySelectorAll('form[data-ajax]').forEach(function (form) {
    var alertBox = form.parentElement.querySelector('.form-alert');
    var say = function (msg, ok) {
      if (!alertBox) { alert(msg); return; }
      alertBox.className = 'form-alert alert ' + (ok ? 'alert-success' : 'alert-danger');
      alertBox.textContent = msg;
    };
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      form.querySelectorAll('.is-invalid').forEach(function (f) { f.classList.remove('is-invalid'); });
      var bad = Array.prototype.filter.call(form.querySelectorAll('[required]'), function (f) { return !f.value.trim(); });
      if (bad.length) { bad.forEach(function (f) { f.classList.add('is-invalid'); }); return say('Please fill in the highlighted fields.', false); }
      var btn = form.querySelector('button[type=submit]');
      var label = btn.textContent;
      btn.disabled = true; btn.textContent = 'Sending…';
      fetch(form.action, { method: 'POST', body: new FormData(form) })
        .then(function (r) { return r.json(); })
        .then(function (data) { say(data.message, data.success); if (data.success) { form.reset(); } })
        .catch(function () { say('Could not send. Please call or WhatsApp us.', false); })
        .then(function () { btn.disabled = false; btn.textContent = label; });
    });
  });
});
