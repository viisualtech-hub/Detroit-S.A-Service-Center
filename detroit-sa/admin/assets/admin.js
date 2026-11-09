/* Detroit S.A admin panel. Plain JavaScript, no build step. Talks to api.php. */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var CSRF = ($('meta[name=csrf]') || {}).content || '';
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var uid = function () { return 'i' + Math.random().toString(36).slice(2, 10); };
  var pub = function (p) { return p ? '../' + p : ''; };

  var S = { c: null, admin: {}, screen: 'dashboard', dirty: false, open: new Set(), dash: null, msgs: [], users: [], loading: false };
  var isOwner = function () { return S.admin.role === 'owner'; };

  /* ---------- network ---------- */
  async function api(action, o) {
    o = o || {};
    var opt = { method: o.method || 'GET', credentials: 'same-origin', headers: {} };
    if (opt.method === 'POST') {
      opt.headers['X-CSRF-Token'] = CSRF;
      if (o.form) { opt.body = o.form; }
      else { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(o.body || {}); }
    }
    var r;
    try { r = await fetch('api.php?action=' + encodeURIComponent(action), opt); }
    catch (e) { throw new Error('Network error. Please check your connection.'); }
    var j = null;
    try { j = await r.json(); }
    catch (e) { throw new Error('Server error (' + r.status + '). Check that PHP is running on this server.'); }
    if (r.status === 401) { location.href = 'login.php'; throw new Error('Signed out'); }
    if (!j.ok) { throw new Error(j.error || 'Something went wrong'); }
    return j;
  }

  function toast(msg, type) {
    var t = document.createElement('div');
    t.className = 'toast-msg ' + (type === 'err' ? 'err' : '');
    t.setAttribute('role', 'status');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () { t.classList.add('show'); }, 10);
    setTimeout(function () { t.classList.remove('show'); setTimeout(function () { t.remove(); }, 300); }, type === 'err' ? 5000 : 3000);
  }

  /* ---------- state helpers ---------- */
  function get(path) { return path.split('.').reduce(function (a, k) { return a == null ? undefined : a[k]; }, S.c); }
  function set(path, v) {
    var ks = path.split('.'), a = S.c;
    for (var i = 0; i < ks.length - 1; i++) { if (a[ks[i]] == null) { a[ks[i]] = {}; } a = a[ks[i]]; }
    a[ks[ks.length - 1]] = v;
  }
  function setDirty(v) { S.dirty = v; document.body.classList.toggle('is-dirty', v); }

  /* ---------- field builders ---------- */
  function fText(path, label, o) {
    o = o || {};
    return '<div class="mb-3"><label class="form-label">' + label + '</label><input class="form-control" type="' + (o.type || 'text') + '" data-path="' + path + '" value="' + esc(get(path)) + '"' +
      (o.ph ? ' placeholder="' + esc(o.ph) + '"' : '') + (o.max ? ' maxlength="' + o.max + '"' : '') + (o.min != null ? ' min="' + o.min + '"' : '') + (o.maxn != null ? ' max="' + o.maxn + '"' : '') + '>' +
      (o.help ? '<div class="form-text">' + o.help + '</div>' : '') + '</div>';
  }
  function fArea(path, label, rows, o) {
    o = o || {};
    return '<div class="mb-3"><label class="form-label">' + label + '</label><textarea class="form-control" rows="' + (rows || 3) + '" data-path="' + path + '">' + esc(get(path)) + '</textarea>' +
      (o.help ? '<div class="form-text">' + o.help + '</div>' : '') + '</div>';
  }
  function fLines(path, label, rows, help) {
    var v = get(path); v = Array.isArray(v) ? v.join('\n') : '';
    return '<div class="mb-3"><label class="form-label">' + label + '</label><textarea class="form-control" rows="' + (rows || 4) + '" data-path="' + path + '" data-kind="lines">' + esc(v) + '</textarea>' +
      '<div class="form-text">' + (help || 'One per line.') + '</div></div>';
  }
  function fSwitch(path, label, help) {
    return '<div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="sw-' + path + '" data-path="' + path + '" data-kind="bool"' + (get(path) ? ' checked' : '') + '>' +
      '<label class="form-check-label" for="sw-' + path + '">' + label + '</label>' + (help ? '<div class="form-text">' + help + '</div>' : '') + '</div>';
  }
  function fSelect(path, label, opts) {
    var v = get(path);
    var list = opts.map(function (o) { return Array.isArray(o) ? o : [o, o]; });
    if (v && !list.some(function (o) { return String(o[0]) === String(v); })) { list.push([v, v]); }
    return '<div class="mb-3"><label class="form-label">' + label + '</label><select class="form-select" data-path="' + path + '">' +
      list.map(function (o) { return '<option value="' + esc(o[0]) + '"' + (String(o[0]) === String(v) ? ' selected' : '') + '>' + esc(o[1]) + '</option>'; }).join('') + '</select></div>';
  }
  var ICONS = [['bi-gear-wide-connected', 'Gearbox'], ['bi-gear', 'Gear'], ['bi-speedometer2', 'Engine / speed'], ['bi-nut', 'Nut / diff'], ['bi-pc-display', 'Diagnostics'],
    ['bi-wrench-adjustable', 'Wrench'], ['bi-tools', 'Tools'], ['bi-brush', 'Paint'], ['bi-truck', 'Truck'], ['bi-truck-front', 'Truck front'], ['bi-card-checklist', 'Fleet checklist'],
    ['bi-stars', 'Exotic'], ['bi-hourglass-split', 'Classic'], ['bi-car-front', 'Car'], ['bi-box-seam', 'Parts box'], ['bi-lightning-charge', 'Electrical'], ['bi-droplet', 'Oil / fluids'],
    ['bi-fuel-pump', 'Fuel'], ['bi-thermometer-half', 'Cooling'], ['bi-shield-check', 'Shield'], ['bi-patch-check', 'Approved'], ['bi-award', 'Award'], ['bi-people', 'Team'],
    ['bi-clock-history', 'Time'], ['bi-geo-alt', 'Location'], ['bi-telephone', 'Phone']];
  function fIcon(path, label) {
    var v = get(path) || 'bi-gear';
    var list = ICONS.slice();
    if (!list.some(function (o) { return o[0] === v; })) { list.push([v, v]); }
    return '<div class="mb-3"><label class="form-label">' + label + '</label><div class="d-flex align-items-center"><select class="form-select" data-path="' + path + '" data-kind="icon">' +
      list.map(function (o) { return '<option value="' + o[0] + '"' + (o[0] === v ? ' selected' : '') + '>' + o[1] + '</option>'; }).join('') + '</select><i class="bi ' + esc(v) + ' icon-prev"></i></div></div>';
  }
  function fImage(path, label, o) {
    o = o || {};
    var v = get(path);
    return '<div class="mb-3"><label class="form-label">' + label + '</label><div class="imgfield">' +
      (v ? '<img src="' + esc(pub(v)) + '" alt="">' : '<div class="imgfield-empty"><i class="bi bi-image"></i></div>') +
      '<div class="imgfield-btns"><button type="button" class="btn btn-sm btn-navy" data-act="pick" data-path="' + path + '"><i class="bi bi-images"></i> ' + (v ? 'Change' : 'Choose') + ' image</button>' +
      (v && !o.required ? '<button type="button" class="btn btn-sm btn-outline-danger" data-act="clear" data-path="' + path + '">Remove</button>' : '') + '</div></div>' +
      (o.help ? '<div class="form-text">' + o.help + '</div>' : '') + '</div>';
  }
  function fImages(path, label, help) {
    var arr = get(path) || [];
    return '<div class="mb-3"><label class="form-label">' + label + '</label><div class="imglist">' +
      arr.map(function (p, i) { return '<div class="t"><img src="' + esc(pub(p)) + '" alt=""><button type="button" data-act="rmimg" data-path="' + path + '" data-i="' + i + '" aria-label="Remove image">&times;</button></div>'; }).join('') +
      '</div><button type="button" class="btn btn-sm btn-navy" data-act="addimgs" data-path="' + path + '"><i class="bi bi-plus-lg"></i> Add images</button>' +
      (help ? '<div class="form-text">' + help + '</div>' : '') + '</div>';
  }
  function row2(a, b) { return '<div class="row"><div class="col-md-6">' + a + '</div><div class="col-md-6">' + b + '</div></div>'; }
  function panel(title, hint, body) {
    return '<section class="panel"><h2>' + title + '</h2>' + (hint ? '<p class="hint">' + hint + '</p>' : '') + body + '</section>';
  }
  function pill(on, yes, no) { return '<span class="pill ' + (on ? 'on' : 'off') + '">' + (on ? yes : no) + '</span>'; }
  function addBtn(path, label) { return '<button type="button" class="btn btn-brand btn-sm" data-act="add" data-path="' + path + '"><i class="bi bi-plus-lg"></i> ' + label + '</button>'; }

  function card(path, i, title, sub, body) {
    var key = path + '.' + i, arr = get(path) || [];
    return '<details class="item" data-key="' + key + '"' + (S.open.has(key) ? ' open' : '') + '><summary><span class="item-title">' + esc(title || '(untitled)') + '</span>' + (sub || '') +
      '<span class="item-btns">' +
      '<button type="button" class="btn btn-sm btn-light" data-act="up" data-path="' + path + '" data-i="' + i + '"' + (i === 0 ? ' disabled' : '') + ' aria-label="Move up"><i class="bi bi-arrow-up"></i></button>' +
      '<button type="button" class="btn btn-sm btn-light" data-act="down" data-path="' + path + '" data-i="' + i + '"' + (i === arr.length - 1 ? ' disabled' : '') + ' aria-label="Move down"><i class="bi bi-arrow-down"></i></button>' +
      '<button type="button" class="btn btn-sm btn-outline-danger" data-act="del" data-path="' + path + '" data-i="' + i + '" aria-label="Delete"><i class="bi bi-trash"></i></button>' +
      '</span></summary><div class="item-body">' + body + '</div></details>';
  }
  function simpleList(path, label, tplHint, fields) {
    var arr = get(path) || [];
    return arr.map(function (x, i) { return card(path, i, x[tplHint] || label + ' ' + (i + 1), '', fields(path + '.' + i)); }).join('') + addBtn(path, 'Add ' + label.toLowerCase());
  }

  var TPL = {
    'notices': function () { return { id: uid(), title: '', message: '', type: 'info', link: '', link_text: '', starts: '', expires: '', active: true }; },
    'hero.slides': function () { return { id: uid(), image: '', alt: '', active: true }; },
    'services': function () { return { id: uid(), title: 'New service', icon: 'bi-gear', short: '', long: '', image: '', gallery: [], detail: false, home: false }; },
    'testimonials': function () { return { id: uid(), name: '', vehicle: '', text: '', rating: 5, active: true }; },
    'home.stats': function () { return { value: '', label: '' }; },
    'home.specialists': function () { return { name: '', text: '' }; },
    'home.why': function () { return { title: '', text: '' }; },
    'home.steps': function () { return { title: '', text: '' }; },
    'about.highlights': function () { return { icon: 'bi-tools', title: '', text: '' }; },
    'about.faq': function () { return { q: '', a: '' }; },
    'pages.services.signs': function () { return { title: '', items: [] }; },
    'site.hours': function () { return { days: '', time: '' }; },
    'home.hero_lines': function () { return { text: '', color: 'white' }; },
    'home.hero_buttons': function () { return { text: 'New button', link: 'contact.php', style: 'primary' }; },
    'home.info_items': function () { return { icon: 'bi-info-circle', title: '', text: '' }; },
    'home.brand_logos': function () { return { name: '', logo: '' }; },
    'partners.banks': function () { return { name: '', logo: '' }; },
    'partners.insurers': function () { return { name: '', logo: '' }; },
    'parts.categories': function () { return { id: uid(), icon: 'bi-gear', title: '', text: '', image: '' }; },
    'parts.steps': function () { return { title: '', text: '' }; },
    'parts.faq': function () { return { q: '', a: '' }; }
  };
  var BLOCK_PAGES = ['home', 'about', 'services', 'parts', 'gallery', 'contact'];
  BLOCK_PAGES.forEach(function (pg) {
    TPL['blocks.' + pg] = function () { return { id: uid(), title: '', text: '', image: '', layout: 'image-right', position: 'bottom', button_text: '', button_link: '', active: true }; };
  });

  function listOp(op, path, i) {
    var arr = get(path);
    if (!Array.isArray(arr)) { return; }
    if (op === 'del') { if (!confirm('Delete this item? This cannot be undone after you save.')) { return; } arr.splice(i, 1); }
    if (op === 'up' && i > 0) { var a = arr[i - 1]; arr[i - 1] = arr[i]; arr[i] = a; }
    if (op === 'down' && i < arr.length - 1) { var b = arr[i + 1]; arr[i + 1] = arr[i]; arr[i] = b; }
    if (op === 'add') { arr.push(TPL[path]()); }
    S.open.forEach(function (k) { if (k.indexOf(path + '.') === 0) { S.open.delete(k); } });
    if (op === 'add') { S.open.add(path + '.' + (arr.length - 1)); }
    setDirty(true);
    render();
  }

  function blocksPanel(page, label) {
    var path = 'blocks.' + page, L = get(path) || [];
    return panel('Extra sections on the ' + label + ' page', 'Add your own heading, text, photo and button to this page. Each one becomes a new section that you can place near the top or near the bottom of the page.',
      addBtn(path, 'Add section') + (L.length ? '' : '<p class="text-muted mt-3 mb-0">No extra sections yet.</p>') +
      '<div class="mt-3">' + L.map(function (b, i) {
        var p = path + '.' + i;
        return card(path, i, b.title || '(untitled section)', pill(b.active, 'Shown', 'Hidden'),
          fText(p + '.title', 'Heading') + fArea(p + '.text', 'Text', 5, { help: 'Leave a blank line between paragraphs.' }) + fImage(p + '.image', 'Photo (optional)') +
          row2(fSelect(p + '.layout', 'Layout', [['image-right', 'Photo on the right'], ['image-left', 'Photo on the left'], ['text-only', 'Text only']]),
               fSelect(p + '.position', 'Where on the page', [['bottom', 'Near the bottom'], ['top', 'Near the top']])) +
          row2(fText(p + '.button_text', 'Button text (optional)'), fText(p + '.button_link', 'Button link', { ph: 'contact.php or https://...' })) +
          fSwitch(p + '.active', 'Show this section'));
      }).join('') + '</div>');
  }

  /* ---------- screens ---------- */
  function vNotices() {
    var L = S.c.notices || [];
    return panel('Notices', 'A notice appears as a coloured bar at the top of every page while it is switched on and today falls between its dates. Visitors can dismiss it.', addBtn('notices', 'Add notice')) +
      (L.length ? '' : '<p class="text-muted">No notices yet.</p>') +
      L.map(function (n, i) {
        var p = 'notices.' + i;
        return card('notices', i, n.title || n.message, pill(n.active, 'On', 'Off'),
          fSelect(p + '.type', 'Style', [['info', 'Information (blue)'], ['warning', 'Warning (amber)'], ['promo', 'Promotion (red)'], ['closed', 'Closed / holiday (grey)']]) +
          fText(p + '.title', 'Title (optional)', { max: 120 }) + fArea(p + '.message', 'Message', 3) +
          row2(fText(p + '.link', 'Link (optional)', { ph: 'contact.php or https://...' }), fText(p + '.link_text', 'Link text', { ph: 'Learn more' })) +
          row2(fText(p + '.starts', 'Show from (optional)', { type: 'date' }), fText(p + '.expires', 'Hide after (optional)', { type: 'date' })) +
          fSwitch(p + '.active', 'Show this notice on the website'));
      }).join('');
  }

  function vHero() {
    var L = S.c.hero.slides || [];
    return panel('Hero slider', 'The big banners at the top of the Home page. Use wide images (about 1800 x 600 pixels) because the full image is shown. Slides with the switch off are hidden.',
      fText('hero.interval', 'Seconds each slide is shown', { type: 'number', min: 2, maxn: 15 }) + addBtn('hero.slides', 'Add slide')) +
      L.map(function (s, i) {
        var p = 'hero.slides.' + i;
        return card('hero.slides', i, 'Slide ' + (i + 1) + (s.alt ? ': ' + s.alt : ''), pill(s.active, 'On', 'Off'),
          fImage(p + '.image', 'Banner image', { required: true }) + fText(p + '.alt', 'Describe the image (for accessibility and Google)', { max: 200 }) + fSwitch(p + '.active', 'Show this slide'));
      }).join('');
  }

  function vHome() {
    return panel('Search engine title', 'The title shown in browser tabs and Google for the Home page.', fText('home.seo_title', 'Page title', { max: 120 })) +
      panel('Main headline (top of the Home page)', 'The big heading, paragraph and buttons next to the photo. Add as many headline lines as you like and choose white or red for each.',
        fText('home.eyebrow', 'Small line above the headline') +
        simpleList('home.hero_lines', 'Headline line', 'text', function (p) { return row2(fText(p + '.text', 'Text'), fSelect(p + '.color', 'Colour', [['white', 'White'], ['red', 'Red']])); }) +
        '<div class="mt-3">' + fArea('home.hero_lead', 'Paragraph under the headline', 4, { help: 'Leave a blank line between paragraphs.' }) + '</div>' +
        '<h6 class="mt-3">Buttons</h6>' +
        simpleList('home.hero_buttons', 'Button', 'text', function (p) {
          return row2(fText(p + '.text', 'Button text'), fText(p + '.link', 'Link', { ph: 'contact.php, https://... or tel:+27...' })) + fSelect(p + '.style', 'Style', [['primary', 'Solid red'], ['outline', 'White outline']]);
        })) +
      panel('Hero photo', 'The photo beside the headline.', fImage('home.hero_photo', 'Photo') + row2(fText('home.hero_photo_alt', 'Photo description'), fText('home.hero_tag', 'Badge on the photo'))) +
      panel('Good to know (information tiles)', 'Useful information for customers, shown under the headline. Add, edit or remove tiles.',
        fText('home.info_title', 'Heading') + simpleList('home.info_items', 'Info tile', 'title', function (p) {
          return fIcon(p + '.icon', 'Icon') + fText(p + '.title', 'Title') + fArea(p + '.text', 'Text', 3);
        })) +
      panel('Highlights strip', 'The big figures under the information tiles.', simpleList('home.stats', 'Highlight', 'value', function (p) { return row2(fText(p + '.value', 'Figure', { max: 20 }), fText(p + '.label', 'Label', { max: 60 })); })) +
      panel('Scrolling strip', 'The red strip of words that scrolls across the page.', fLines('home.ticker', 'Words', 5)) +
      panel('Introduction section', 'The section below the highlights. It holds the headline that used to be at the very top.',
        fText('home.intro_eyebrow', 'Small line above the heading') + row2(fText('home.title_1', 'Heading, first part'), fText('home.title_2', 'Heading, second part (red)')) +
        fArea('home.lead', 'Paragraph', 4, { help: 'Leave a blank line between paragraphs.' }) + row2(fText('home.btn1', 'Main button text (goes to Contact)'), fText('home.btn2', 'Second button text (goes to Services)'))) +
      panel('Makes we work on', 'Specialist brands shown as cards, then a strip of brand logos.',
        fText('home.makes_title', 'Section title') + fText('home.makes_text', 'Short text (optional)') +
        simpleList('home.specialists', 'Specialist brand', 'name', function (p) { return fText(p + '.name', 'Brand name') + fText(p + '.text', 'Description'); }) +
        '<hr>' + fText('home.logos_title', 'Heading above the logos') +
        simpleList('home.brand_logos', 'Brand logo', 'name', function (p) { return fText(p + '.name', 'Brand name') + fImage(p + '.logo', 'Logo', { help: 'A logo on a white or transparent background looks best.' }); }) +
        fLines('home.other_makes', 'Extra words after the logos (for example: and more)', 2)) +
      panel('Services section', 'The cards come from the Services screen. Tick "Show on the Home page" on each service you want here.', fText('home.services_title', 'Section title') + fText('home.services_text', 'Short text')) +
      panel('Why choose us', '', fText('home.why_title', 'Section title') + simpleList('home.why', 'Reason', 'title', function (p) { return fText(p + '.title', 'Title') + fText(p + '.text', 'Text'); })) +
      panel('How it works', '', fText('home.steps_title', 'Section title') + simpleList('home.steps', 'Step', 'title', function (p) { return fText(p + '.title', 'Title') + fText(p + '.text', 'Text'); })) +
      panel('Customer reviews heading', 'The reviews section only appears when you have added reviews on the Testimonials screen.', fText('home.reviews_title', 'Section title') + fText('home.reviews_text', 'Short text')) +
      blocksPanel('home', 'Home');
  }

  function vServices() {
    var L = S.c.services || [];
    return panel('Services', 'Each service appears as a card. Turn on "Detailed section" to give it its own full section with photos on the Services page. Use the arrows to change the order.', addBtn('services', 'Add service')) +
      L.map(function (s, i) {
        var p = 'services.' + i;
        return card('services', i, s.title, pill(s.home, 'Home', 'Not on home') + ' ' + pill(s.detail, 'Detailed', 'Card only'),
          fText(p + '.title', 'Service name', { max: 100 }) + fIcon(p + '.icon', 'Icon') +
          fArea(p + '.short', 'Short description (shown on the card)', 2) +
          fArea(p + '.long', 'Full description (shown in the detailed section)', 7, { help: 'Leave a blank line between paragraphs.' }) +
          fImage(p + '.image', 'Main photo') + fImages(p + '.gallery', 'More photos (detailed section)', 'These open in a photo viewer when tapped.') +
          fSwitch(p + '.home', 'Show on the Home page') + fSwitch(p + '.detail', 'Show a detailed section on the Services page'));
      }).join('') + blocksPanel('services', 'Services');
  }

  function vGallery() {
    var L = S.c.gallery || [], seen = {}, cats = [];
    L.forEach(function (g) { var k = (g.category || '').trim(); if (k && !seen[k]) { seen[k] = 1; cats.push(k); } });
    return panel('Gallery', 'Photos shown on the Gallery page. The category becomes a filter button. New photos are added at the top.',
      '<button type="button" class="btn btn-brand btn-sm" data-act="addphotos"><i class="bi bi-plus-lg"></i> Add photos</button> <span class="text-muted small ms-2">' + L.length + ' photos</span>' +
      '<datalist id="cats">' + cats.map(function (c) { return '<option value="' + esc(c) + '">'; }).join('') + '</datalist>') +
      L.map(function (g, i) {
        var p = 'gallery.' + i;
        return '<div class="g-row"><img src="' + esc(pub(g.image)) + '" alt="">' +
          '<input class="form-control cap" placeholder="Caption" data-path="' + p + '.caption" value="' + esc(g.caption) + '" maxlength="150">' +
          '<input class="form-control cat" placeholder="Category" list="cats" data-path="' + p + '.category" value="' + esc(g.category) + '" maxlength="40">' +
          '<span class="item-btns"><button type="button" class="btn btn-sm btn-light" data-act="up" data-path="gallery" data-i="' + i + '"' + (i === 0 ? ' disabled' : '') + ' aria-label="Move up"><i class="bi bi-arrow-up"></i></button>' +
          '<button type="button" class="btn btn-sm btn-light" data-act="down" data-path="gallery" data-i="' + i + '"' + (i === L.length - 1 ? ' disabled' : '') + ' aria-label="Move down"><i class="bi bi-arrow-down"></i></button>' +
          '<button type="button" class="btn btn-sm btn-outline-danger" data-act="del" data-path="gallery" data-i="' + i + '" aria-label="Delete"><i class="bi bi-trash"></i></button></span></div>';
      }).join('') + blocksPanel('gallery', 'Gallery');
  }

  function vAbout() {
    return panel('Page heading', '', row2(fText('about.title', 'Page title'), fText('about.sub', 'Sub-heading'))) +
      panel('Our story', 'Leave a blank line between paragraphs.', fText('about.heading', 'Heading') + fArea('about.story', 'Story', 12) +
        fImage('about.image', 'Photo') + row2(fText('about.badge_title', 'Badge title'), fText('about.badge_text', 'Badge text'))) +
      panel('Highlight cards', '', simpleList('about.highlights', 'Highlight', 'title', function (p) { return fIcon(p + '.icon', 'Icon') + fText(p + '.title', 'Title') + fText(p + '.text', 'Text'); })) +
      panel('Approvals & partners', '', fText('about.partners_title', 'Section title') + fText('about.partners_text', 'Short text (optional)') +
        fText('about.rmi_text', 'RMI approval text (leave empty to hide the card)') +
        fText('about.fleet_label', 'Fleet card title') + fLines('about.fleet', 'Fleet companies', 4) +
        fText('about.insurers_label', 'Insurers card title') + fLines('about.insurers', 'Insurers', 4)) +
      panel('Frequently asked questions', '', fText('about.faq_title', 'Section title') + simpleList('about.faq', 'Question', 'q', function (p) { return fText(p + '.q', 'Question') + fArea(p + '.a', 'Answer', 3); })) +
      blocksPanel('about', 'About');
  }

  function vTestimonials() {
    var L = S.c.testimonials || [];
    return panel('Testimonials', 'Only add genuine reviews from real customers, and ask for their permission. The reviews section appears on the Home page once you add at least one active review.', addBtn('testimonials', 'Add review')) +
      (L.length ? '' : '<p class="text-muted">No reviews yet, so the section is hidden on the website.</p>') +
      L.map(function (t, i) {
        var p = 'testimonials.' + i;
        return card('testimonials', i, (t.name || 'Customer') + (t.vehicle ? ' (' + t.vehicle + ')' : ''), pill(t.active, 'Shown', 'Hidden'),
          row2(fText(p + '.name', 'Customer name'), fText(p + '.vehicle', 'Vehicle / service', { ph: 'Jeep Wrangler, gearbox overhaul' })) +
          fArea(p + '.text', 'Review', 4) + fSelect(p + '.rating', 'Stars', [['5', '5 stars'], ['4', '4 stars'], ['3', '3 stars'], ['2', '2 stars'], ['1', '1 star']]) +
          fSwitch(p + '.active', 'Show this review'));
      }).join('');
  }

  function vPages() {
    return panel('Services page', '', row2(fText('pages.services.title', 'Page title'), fText('pages.services.sub', 'Sub-heading')) + fText('pages.services.intro', 'Intro text (optional)')) +
      panel('Warning signs (Services page)', 'Shown near the bottom of the Services page. Put one sign per line.', fText('pages.services.signs_title', 'Section title') +
        (get('pages.services.signs') || []).map(function (s, i) { var p = 'pages.services.signs.' + i; return card('pages.services.signs', i, s.title, '', fText(p + '.title', 'Heading') + fLines(p + '.items', 'Signs', 5)); }).join('') +
        addBtn('pages.services.signs', 'Add group')) +
      panel('Gallery page', '', row2(fText('pages.gallery.title', 'Page title'), fText('pages.gallery.sub', 'Sub-heading'))) +
      panel('Contact page', '', row2(fText('pages.contact.title', 'Page title'), fText('pages.contact.sub', 'Sub-heading')) + row2(fText('pages.contact.form_title', 'Form title'), fText('pages.contact.form_text', 'Form text'))) +
      blocksPanel('contact', 'Contact');
  }

  function vSettings() {
    return panel('Business details', 'Used in the header, footer, contact page and booking emails. Enter phone numbers the way people write them, for example 031 577 0160.',
      row2(fText('site.name', 'Business name'), fText('site.short_name', 'Short name')) + fText('site.tagline', 'Tagline') +
      row2(fText('site.phone', 'Telephone'), fText('site.cell', 'Cell')) + row2(fText('site.cell2', 'Second cell'), '') +
      row2(fText('site.whatsapp_display', 'WhatsApp number', { help: 'Leave empty to use the cell number.' }), fText('site.email', 'Email (booking requests are sent here)', { type: 'email' })) +
      fText('site.address', 'Address') + fText('site.map_query', 'Address used for the map and directions')) +
      panel('Menu names', 'The words shown in the website menu.', row2(fText('site.nav.home', 'Home'), fText('site.nav.about', 'About')) + row2(fText('site.nav.services', 'Services'), fText('site.nav.parts', 'Parts')) + row2(fText('site.nav.gallery', 'Gallery'), fText('site.nav.contact', 'Contact'))) +
      panel('Opening hours', '', fText('site.hours_short', 'Short version (top bar)', { ph: 'Mon-Fri 07:30-17:00' }) +
        (get('site.hours') || []).map(function (h, i) { var p = 'site.hours.' + i; return card('site.hours', i, h.days, '', row2(fText(p + '.days', 'Days'), fText(p + '.time', 'Hours'))); }).join('') + addBtn('site.hours', 'Add line')) +
      panel('Social media', 'Leave a field empty to hide that icon.', fText('site.facebook', 'Facebook link') + fText('site.tiktok', 'TikTok link') + fText('site.instagram', 'Instagram link')) +
      panel('Logo & footer', '', fImage('site.logo', 'Logo', { required: true, help: 'Shown on a dark background. A PNG with a transparent background works best.' }) +
        fArea('site.footer_text', 'Footer text', 2) + fArea('site.meta_description', 'Google description (about 150 characters)', 2)) +
      panel('Call-to-action banner', 'The red banner near the bottom of most pages.', fText('cta.title', 'Title') + fText('cta.text', 'Text') + fText('cta.button_text', 'Button text'));
  }

  function vParts() {
    return panel('Parts page', 'The Parts page in the website menu.', row2(fText('parts.title', 'Page title'), fText('parts.sub', 'Sub-heading')) + fArea('parts.intro', 'Introduction', 4, { help: 'Leave a blank line between paragraphs.' })) +
      panel('What we can supply', 'Cards for the kinds of parts you sell.', fText('parts.categories_title', 'Heading') +
        simpleList('parts.categories', 'Parts category', 'title', function (p) { return fText(p + '.title', 'Title') + fIcon(p + '.icon', 'Icon') + fArea(p + '.text', 'Description', 2) + fImage(p + '.image', 'Photo (optional)'); })) +
      panel('Brand logos on the Parts page', 'The logos come from Home page > Makes we work on.', fText('parts.logos_title', 'Heading')) +
      panel('How to order', '', fText('parts.steps_title', 'Heading') + simpleList('parts.steps', 'Step', 'title', function (p) { return fText(p + '.title', 'Title') + fArea(p + '.text', 'Text', 2); })) +
      panel('Parts request form', 'Requests arrive in Booking requests, marked as Parts.', row2(fText('parts.form_title', 'Form title'), fText('parts.form_text', 'Form text'))) +
      panel('Parts questions', '', fText('parts.faq_title', 'Heading') + simpleList('parts.faq', 'Question', 'q', function (p) { return fText(p + '.q', 'Question') + fArea(p + '.a', 'Answer', 3); })) +
      blocksPanel('parts', 'Parts');
  }

  function vPartners() {
    var logoList = function (path, label) {
      return simpleList(path, label, 'name', function (p) { return row2(fText(p + '.name', 'Name'), '') + fImage(p + '.logo', 'Logo (optional)', { help: 'If you upload a logo it is shown instead of the name.' }); });
    };
    return panel('Banks, insurers & warranty', 'Shown on the Home page and the Services page. Only keep the banks and insurers the business really works with.',
        fSwitch('partners.show_home', 'Show on the Home page') + fSwitch('partners.show_services', 'Show on the Services page') +
        '<div class="mt-3">' + fText('partners.title', 'Section heading') + fArea('partners.text', 'Short text', 2) + '</div>') +
      panel('Banks', '', fText('partners.banks_title', 'Heading') + fText('partners.banks_text', 'Short text (optional)') + logoList('partners.banks', 'Bank')) +
      panel('Insurers', '', fText('partners.insurers_title', 'Heading') + fText('partners.insurers_text', 'Short text (optional)') + logoList('partners.insurers', 'Insurer')) +
      panel('Extended warranty', 'The dark banner under the logos. Leave the heading empty to hide it.', fText('partners.warranty_title', 'Heading') + fArea('partners.warranty_text', 'Text', 2) + fText('partners.warranty_button', 'Button text'));
  }

  function vUsers() {
    var roleName = function (r) { return r === 'owner' ? 'Full admin' : 'Viewer'; };
    return panel('Add a user', 'A Viewer can sign in to read booking requests and see what needs attention, but cannot change the website. A Full admin can change everything.',
        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Name</label><input class="form-control" id="nu-name"></div>' +
        '<div class="col-md-6 mb-3"><label class="form-label">Login email</label><input class="form-control" id="nu-email" type="email"></div>' +
        '<div class="col-md-6 mb-3"><label class="form-label">Password (at least 8 characters)</label><input class="form-control" id="nu-pass" type="text" autocomplete="off"></div>' +
        '<div class="col-md-6 mb-3"><label class="form-label">Access</label><select class="form-select" id="nu-role"><option value="viewer">Viewer (booking requests + monitoring)</option><option value="owner">Full admin (can edit everything)</option></select></div></div>' +
        '<button type="button" class="btn btn-brand" data-act="user-add"><i class="bi bi-person-plus"></i> Add user</button>') +
      panel('Users', 'Everyone who can sign in to this admin panel.',
        (S.users || []).map(function (u) {
          return '<div class="msg-card"><div class="d-flex justify-content-between flex-wrap gap-2"><b>' + esc(u.name) + (u.self ? ' (you)' : '') + '</b><span class="pill ' + (u.role === 'owner' ? 'on' : '') + '">' + roleName(u.role) + '</span></div>' +
            '<div class="meta">' + esc(u.email) + (u.created ? ' &middot; added ' + esc(u.created) : '') + '</div>' +
            '<div class="d-flex flex-wrap gap-2 mt-2"><button type="button" class="btn btn-sm btn-light" data-act="user-reset" data-id="' + esc(u.id) + '"><i class="bi bi-key"></i> Reset password</button>' +
            '<button type="button" class="btn btn-sm btn-light" data-act="user-role" data-id="' + esc(u.id) + '" data-role="' + (u.role === 'owner' ? 'viewer' : 'owner') + '">Make ' + (u.role === 'owner' ? 'Viewer' : 'Full admin') + '</button>' +
            (u.self ? '' : '<button type="button" class="btn btn-sm btn-outline-danger" data-act="user-del" data-id="' + esc(u.id) + '"><i class="bi bi-trash"></i> Delete</button>') + '</div></div>';
        }).join('') || '<p class="text-muted">No users.</p>');
  }

  var PAGE_NAMES = { home: 'Home', about: 'About', services: 'Services', parts: 'Parts', gallery: 'Gallery', contact: 'Contact' };

  function msgCard(m) {
    var wa = (m.phone || '').replace(/\D/g, '');
    return '<div class="msg-card' + (m.read ? '' : ' new') + '"><div class="d-flex justify-content-between flex-wrap gap-2"><b>' + esc(m.name) + (m.type === 'parts' ? ' <span class="pill on">Parts</span>' : '') + '</b><span class="meta">' + esc(m.time) + (m.read ? '' : ' &middot; <b class="text-danger">NEW</b>') + '</span></div>' +
      '<div class="meta mb-2">' + esc(m.brand) + ' &middot; ' + esc(m.service) + '</div><div style="white-space:pre-wrap">' + esc(m.message) + '</div>' +
      '<div class="d-flex flex-wrap gap-2 mt-3"><a class="btn btn-sm btn-navy" href="tel:' + esc(wa) + '"><i class="bi bi-telephone"></i> ' + esc(m.phone) + '</a>' +
      (m.email ? '<a class="btn btn-sm btn-outline-secondary" href="mailto:' + esc(m.email) + '"><i class="bi bi-envelope"></i> ' + esc(m.email) + '</a>' : '') +
      '<button type="button" class="btn btn-sm btn-light" data-act="msg" data-op="' + (m.read ? 'unread' : 'read') + '" data-id="' + esc(m.id) + '">' + (m.read ? 'Mark as new' : 'Mark as read') + '</button>' +
      (isOwner() ? '<button type="button" class="btn btn-sm btn-outline-danger" data-act="msg" data-op="delete" data-id="' + esc(m.id) + '"><i class="bi bi-trash"></i></button>' : '') + '</div></div>';
  }

  function vDashboard() {
    var d = S.dash || { counts: {}, days: [], pages: {}, recent: [], attention: [] }, c = d.counts || {};
    var days = d.days || [], max = Math.max.apply(null, [1].concat(days.map(function (x) { return x.total; })));
    var total = days.reduce(function (a, x) { return a + x.total; }, 0);
    var pages = Array.isArray(d.pages) ? [] : Object.keys(d.pages || {}).map(function (k) { return [k, d.pages[k]]; });
    var tile = function (go, n, label) { return '<button type="button" class="stat" data-go="' + go + '"><b>' + (n || 0) + '</b><span>' + label + '</span></button>'; };
    var att = d.attention || [];
    var lvl = { danger: ['alert-danger', 'bi-exclamation-octagon-fill'], warn: ['alert-warning', 'bi-exclamation-triangle-fill'], info: ['alert-info', 'bi-info-circle-fill'] };
    var order = { danger: 0, warn: 1, info: 2 };
    att = att.filter(function (a) { return a.level !== 'info'; }).sort(function (a, b) { return order[a.level] - order[b.level]; });
    var attHtml = att.length ? att.map(function (a) {
      var l = lvl[a.level] || lvl.info, canGo = a.go && allowed().indexOf(a.go) > -1;
      return '<div class="alert ' + l[0] + ' d-flex align-items-center gap-2 py-2 mb-2"><i class="bi ' + l[1] + '"></i><span class="flex-grow-1">' + esc(a.text) + '</span>' +
        (canGo ? '<button type="button" class="btn btn-sm btn-navy" data-go="' + esc(a.go) + '">Open</button>' : '') + '</div>';
    }).join('') : '<p class="text-success mb-0"><i class="bi bi-check-circle-fill"></i> Everything looks good. Nothing needs attention.</p>';
    return panel('Needs attention', 'Things on the website that someone should look at. Red is urgent.', attHtml) +
      '<div class="stat-grid">' + tile('messages', c.new_messages, 'New booking requests') + tile('notices', c.notices, 'Active notices') + tile('services', c.services, 'Services') + tile('gallery', c.gallery, 'Gallery photos') + tile('hero', c.slides, 'Hero slides') + '</div>' +
      panel('Page views, last 14 days', total + ' views in total. Visits from search-engine robots and from signed-in admins are not counted.',
        '<div class="chart">' + days.map(function (x) { return '<div class="col"><div class="bar" style="height:' + Math.round(x.total / max * 100) + '%"><span>' + (x.total || '') + '</span></div><small>' + esc(x.date.slice(8)) + '</small></div>'; }).join('') + '</div>' +
        (pages.length ? '<hr><div class="d-flex flex-wrap gap-3">' + pages.map(function (p) { return '<span><b>' + esc(PAGE_NAMES[p[0]] || p[0]) + '</b> ' + p[1] + '</span>'; }).join('') + '</div>' : '')) +
      panel('Latest requests', '', (d.recent && d.recent.length ? d.recent.map(msgCard).join('') : '<p class="text-muted mb-0">No requests yet.</p>'));
  }

  function vMessages() {
    return panel('Booking requests', 'Requests sent from the Contact page. A copy is also emailed to the business email.', '') +
      (S.msgs.length ? S.msgs.map(msgCard).join('') : '<p class="text-muted">No booking requests yet.</p>');
  }

  function vAccount() {
    return panel('Login details', 'Change the email and password used to sign in here. Your current password is required. Use at least 8 characters for the new password.',
      '<div class="mb-3"><label class="form-label">Current password</label><input class="form-control" id="acc-current" type="password" autocomplete="current-password"></div>' +
      '<div class="mb-3"><label class="form-label">Your name</label><input class="form-control" id="acc-name" value="' + esc(S.admin.name) + '"></div>' +
      '<div class="mb-3"><label class="form-label">Login email</label><input class="form-control" id="acc-email" type="email" autocomplete="username" value="' + esc(S.admin.email) + '"></div>' +
      '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">New password (leave empty to keep the current one)</label><input class="form-control" id="acc-new" type="password" autocomplete="new-password"></div>' +
      '<div class="col-md-6 mb-3"><label class="form-label">Confirm new password</label><input class="form-control" id="acc-confirm" type="password" autocomplete="new-password"></div></div>' +
      '<button type="button" class="btn btn-brand" data-act="account">Save login details</button>');
  }

  var SCREENS = {
    messages: { title: 'Booking requests', icon: 'bi-inbox', view: vMessages, load: function () { return loadMessages(); } },
    dashboard: { title: 'Dashboard & attention', icon: 'bi-speedometer2', view: vDashboard, load: function () { return refreshDash(); } },
    notices: { title: 'Notices', icon: 'bi-megaphone', view: vNotices, save: ['notices'] },
    hero: { title: 'Hero slider', icon: 'bi-images', view: vHero, save: ['hero'] },
    home: { title: 'Home page', icon: 'bi-house', view: vHome, save: ['home', 'blocks'] },
    services: { title: 'Services', icon: 'bi-tools', view: vServices, save: ['services', 'blocks'] },
    parts: { title: 'Parts page', icon: 'bi-box-seam', view: vParts, save: ['parts', 'blocks'] },
    partners: { title: 'Banks, insurers & warranty', icon: 'bi-bank', view: vPartners, save: ['partners'] },
    gallery: { title: 'Gallery', icon: 'bi-grid-3x3-gap', view: vGallery, save: ['gallery', 'blocks'] },
    about: { title: 'About & FAQ', icon: 'bi-info-circle', view: vAbout, save: ['about', 'blocks'] },
    testimonials: { title: 'Testimonials', icon: 'bi-chat-quote', view: vTestimonials, save: ['testimonials'] },
    pages: { title: 'Page text & Contact', icon: 'bi-card-text', view: vPages, save: ['pages', 'blocks'] },
    settings: { title: 'Business settings', icon: 'bi-gear', view: vSettings, save: ['site', 'cta'] },
    users: { title: 'Users', icon: 'bi-people', view: vUsers, load: function () { return loadUsers(); } },
    account: { title: 'My account & login', icon: 'bi-person-lock', view: vAccount }
  };
  var ORDER_OWNER = ['dashboard', 'messages', '-', 'notices', 'hero', 'home', 'services', 'parts', 'partners', 'gallery', 'about', 'testimonials', 'pages', 'settings', '-', 'users', 'account'];
  var ORDER_VIEWER = ['dashboard', 'messages', '-', 'account'];
  function order() { return isOwner() ? ORDER_OWNER : ORDER_VIEWER; }
  function allowed() { return order().filter(function (k) { return k !== '-'; }); }

  /* ---------- shell & render ---------- */
  function shell() {
    var sc = SCREENS[S.screen];
    var nm = S.dash && S.dash.counts ? S.dash.counts.new_messages : 0;
    var nav = order().map(function (k) {
      if (k === '-') { return '<hr>'; }
      var s = SCREENS[k];
      return '<button type="button" data-go="' + k + '" class="' + (k === S.screen ? 'active' : '') + '"><i class="bi ' + s.icon + '"></i> ' + s.title +
        (k === 'messages' && nm ? '<span class="badge rounded-pill">' + nm + '</span>' : '') + '</button>';
    }).join('');
    return '<div class="shell"><aside class="sidebar"><div class="side-brand"><img src="../images/logo.png" alt="Detroit S.A"></div><nav class="side-nav">' + nav + '</nav>' +
      '<div class="side-foot"><div class="mb-1 text-white-50 small">' + esc(S.admin.name || S.admin.email) + ' &middot; ' + (isOwner() ? 'Full admin' : 'Viewer') + '</div><a href="../index.php" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> View website</a><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a></div></aside>' +
      '<div class="backdrop" data-act="closenav"></div>' +
      '<div class="main"><header class="topbar"><button type="button" class="menu-btn" data-act="opennav" aria-label="Open menu"><i class="bi bi-list"></i></button><h1>' + esc(sc.title) + '</h1>' +
      '<span class="dirty-badge">Unsaved changes</span><a class="btn btn-sm btn-outline-secondary d-none d-sm-inline-block" href="../index.php" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> View site</a></header>' +
      '<main id="screen">' + (S.loading ? '<div class="boot">Loading&hellip;</div>' : sc.view()) + '</main>' +
      (sc.save && !S.loading ? '<div class="savebar"><span class="note">' + (S.dirty ? 'You have unsaved changes.' : 'All changes saved.') + '</span><button type="button" class="btn btn-brand" data-act="save"><i class="bi bi-check2-circle"></i> Save changes</button></div>' : '') +
      '</div></div>';
  }

  function render() {
    var y = window.scrollY, sb = $('.sidebar'), sy = sb ? sb.scrollTop : 0;
    $('#app').innerHTML = shell();
    document.body.classList.toggle('is-dirty', S.dirty);
    var nsb = $('.sidebar'); if (nsb) { nsb.scrollTop = sy; }
    window.scrollTo(0, y);
  }

  async function loadContent() { var r = await api('content'); S.c = r.content; S.admin = r.admin; }
  async function refreshDash() { S.dash = await api('dashboard'); }
  async function loadUsers() { var r = await api('users'); S.users = r.users || []; }
  async function loadMessages() { var r = await api('messages'); S.msgs = r.messages || []; await refreshDash(); }

  async function go(name) {
    if (!SCREENS[name] || allowed().indexOf(name) < 0) { return; }
    if (S.dirty) {
      if (!confirm('You have unsaved changes. Leave without saving?')) { return; }
      try { await loadContent(); } catch (e) { /* keep local state */ }
      setDirty(false);
    }
    S.screen = name;
    document.body.classList.remove('nav-open');
    var sc = SCREENS[name];
    if (sc.load) {
      S.loading = true; render();
      try { await sc.load(); } catch (e) { toast(e.message, 'err'); }
      S.loading = false;
    }
    render();
    window.scrollTo(0, 0);
  }

  async function saveScreen() {
    var sc = SCREENS[S.screen], btn = $('[data-act=save]');
    if (!sc || !sc.save) { return; }
    if (btn) { btn.disabled = true; }
    try {
      for (var i = 0; i < sc.save.length; i++) {
        var sec = sc.save[i];
        var r = await api('save', { method: 'POST', body: { section: sec, data: S.c[sec] } });
        S.c[sec] = r.data;
      }
      setDirty(false);
      render();
      toast('Saved. Your website is updated.');
    } catch (e) {
      toast(e.message, 'err');
      if (btn) { btn.disabled = false; }
    }
  }

  /* ---------- media library ---------- */
  async function openMedia(opts) {
    var ov = document.createElement('div');
    ov.className = 'media-ov';
    ov.innerHTML = '<div class="media-box" role="dialog" aria-modal="true"><div class="media-head"><h5>Choose ' + (opts.multiple ? 'images' : 'an image') + '</h5><button type="button" class="btn-close" data-m="cancel" aria-label="Close"></button></div>' +
      '<div class="media-tools"><label class="btn btn-brand btn-sm mb-0"><i class="bi bi-upload"></i> Upload from this device<input type="file" accept="image/jpeg,image/png" multiple hidden></label>' +
      '<span class="text-muted small">JPG or PNG. Big photos are resized automatically.</span></div>' +
      '<div class="media-grid"><div class="text-muted">Loading&hellip;</div></div>' +
      '<div class="media-foot"><span class="media-count"></span><button type="button" class="btn btn-light btn-sm" data-m="cancel">Cancel</button>' +
      (opts.multiple ? '<button type="button" class="btn btn-navy btn-sm" data-m="use">Use selected</button>' : '') + '</div></div>';
    document.body.appendChild(ov);
    var grid = $('.media-grid', ov), count = $('.media-count', ov), fileInput = $('input[type=file]', ov);
    var files = [], sel = [];
    function close() { ov.remove(); document.removeEventListener('keydown', onKey); }
    function onKey(e) { if (e.key === 'Escape') { close(); } }
    document.addEventListener('keydown', onKey);
    function tile(f) {
      return '<div class="m-wrap"><button type="button" class="m-tile' + (sel.indexOf(f.path) > -1 ? ' sel' : '') + '" data-p="' + esc(f.path) + '"><img src="' + esc(pub(f.path)) + '" loading="lazy" alt=""><span>' + esc(f.name) + '</span></button>' +
        (f.deletable ? '<button type="button" class="m-del" data-del="' + esc(f.path) + '" title="Delete this upload"><i class="bi bi-trash"></i></button>' : '') + '</div>';
    }
    function draw() {
      var up = files.filter(function (f) { return f.deletable; }), site = files.filter(function (f) { return !f.deletable; });
      grid.innerHTML = (up.length ? '<h6>Your uploads</h6><div class="m-list">' + up.map(tile).join('') + '</div>' : '') +
        (site.length ? '<h6>Website images</h6><div class="m-list">' + site.map(tile).join('') + '</div>' : '') || '<div class="text-muted">No images yet. Upload one.</div>';
      count.textContent = opts.multiple ? sel.length + ' selected' : '';
    }
    function choose(path) { opts.cb(opts.multiple ? sel.slice() : path); close(); }
    ov.addEventListener('click', async function (e) {
      if (e.target === ov) { close(); return; }
      var t = e.target.closest('[data-m],[data-p],[data-del]');
      if (!t) { return; }
      if (t.dataset.m === 'cancel') { close(); return; }
      if (t.dataset.m === 'use') { if (!sel.length) { toast('Select at least one image.', 'err'); return; } choose(); return; }
      if (t.dataset.del) {
        if (!confirm('Delete this uploaded image from the server?')) { return; }
        try {
          await api('delete_media', { method: 'POST', body: { path: t.dataset.del } });
          files = files.filter(function (f) { return f.path !== t.dataset.del; });
          sel = sel.filter(function (p) { return p !== t.dataset.del; });
          draw();
        } catch (err) { toast(err.message, 'err'); }
        return;
      }
      if (t.dataset.p) {
        if (!opts.multiple) { choose(t.dataset.p); return; }
        var i = sel.indexOf(t.dataset.p);
        if (i > -1) { sel.splice(i, 1); } else { sel.push(t.dataset.p); }
        draw();
      }
    });
    fileInput.addEventListener('change', async function () {
      var list = Array.prototype.slice.call(fileInput.files || []);
      var done = [];
      for (var i = 0; i < list.length; i++) {
        count.textContent = 'Uploading ' + (i + 1) + ' of ' + list.length + '…';
        var fd = new FormData(); fd.append('file', list[i]);
        try {
          var r = await api('upload', { method: 'POST', form: fd });
          files.unshift({ path: r.path, name: r.path.split('/').pop(), deletable: true });
          done.push(r.path);
        } catch (err) { toast(list[i].name + ': ' + err.message, 'err'); }
      }
      fileInput.value = '';
      if (done.length) {
        if (!opts.multiple) { choose(done[0]); return; }
        done.forEach(function (p) { if (sel.indexOf(p) < 0) { sel.push(p); } });
      }
      draw();
    });
    try { var r = await api('media'); files = r.files || []; } catch (err) { toast(err.message, 'err'); }
    draw();
  }

  /* ---------- events ---------- */
  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-act],[data-go]');
    if (!t || t.closest('.media-ov')) { return; }
    e.preventDefault();
    if (t.dataset.go) { go(t.dataset.go); return; }
    var a = t.dataset.act, path = t.dataset.path, i = parseInt(t.dataset.i, 10);
    if (a === 'opennav') { document.body.classList.add('nav-open'); }
    else if (a === 'closenav') { document.body.classList.remove('nav-open'); }
    else if (a === 'save') { saveScreen(); }
    else if (a === 'add' || a === 'del' || a === 'up' || a === 'down') { listOp(a, path, i); }
    else if (a === 'pick') { openMedia({ multiple: false, cb: function (p) { set(path, p); setDirty(true); render(); } }); }
    else if (a === 'clear') { set(path, ''); setDirty(true); render(); }
    else if (a === 'addimgs') {
      openMedia({ multiple: true, cb: function (ps) { var arr = get(path) || []; ps.forEach(function (p) { if (arr.indexOf(p) < 0) { arr.push(p); } }); set(path, arr); setDirty(true); render(); } });
    }
    else if (a === 'rmimg') { (get(path) || []).splice(i, 1); setDirty(true); render(); }
    else if (a === 'addphotos') {
      openMedia({ multiple: true, cb: function (ps) {
        var last = (S.c.gallery[0] && S.c.gallery[0].category) || 'Workshop';
        ps.slice().reverse().forEach(function (p) { S.c.gallery.unshift({ id: uid(), image: p, caption: '', category: last }); });
        setDirty(true); render();
      } });
    }
    else if (a === 'msg') {
      try {
        await api('message_update', { method: 'POST', body: { id: t.dataset.id, op: t.dataset.op } });
        if (S.screen === 'messages') { await loadMessages(); } else { await refreshDash(); }
        render();
      } catch (err) { toast(err.message, 'err'); }
    }
    else if (a === 'user-add') {
      try {
        await api('user_add', { method: 'POST', body: { name: $('#nu-name').value, email: $('#nu-email').value, password: $('#nu-pass').value, role: $('#nu-role').value } });
        await loadUsers(); render(); toast('User added.');
      } catch (err) { toast(err.message, 'err'); }
    }
    else if (a === 'user-del') {
      if (!confirm('Delete this user? They will no longer be able to sign in.')) { return; }
      try { await api('user_delete', { method: 'POST', body: { id: t.dataset.id } }); await loadUsers(); render(); toast('User deleted.'); } catch (err) { toast(err.message, 'err'); }
    }
    else if (a === 'user-reset') {
      var np = prompt('Type a new password for this user (at least 8 characters):');
      if (!np) { return; }
      try { await api('user_update', { method: 'POST', body: { id: t.dataset.id, password: np } }); toast('Password changed.'); } catch (err) { toast(err.message, 'err'); }
    }
    else if (a === 'user-role') {
      if (!confirm('Change this user\'s access?')) { return; }
      try { await api('user_update', { method: 'POST', body: { id: t.dataset.id, role: t.dataset.role } }); await loadUsers(); render(); toast('Access changed.'); } catch (err) { toast(err.message, 'err'); }
    }
    else if (a === 'account') {
      var body = { current: $('#acc-current').value, name: $('#acc-name').value, email: $('#acc-email').value, password: $('#acc-new').value, confirm: $('#acc-confirm').value };
      try {
        var r = await api('account', { method: 'POST', body: body });
        S.admin.email = r.email; S.admin.name = r.name; S.admin.default = false;
        toast('Login details saved. Use them next time you sign in.');
        render();
      } catch (err) { toast(err.message, 'err'); }
    }
  });

  function onInput(e) {
    var t = e.target, p = t.dataset && t.dataset.path;
    if (!p || t.dataset.act || !S.c) { return; }
    var v;
    if (t.type === 'checkbox') { v = t.checked; }
    else if (t.dataset.kind === 'lines') { v = t.value.split('\n'); }
    else { v = t.value; }
    set(p, v);
    if (t.dataset.kind === 'icon') {
      var prev = t.parentElement.querySelector('.icon-prev');
      if (prev) { prev.className = 'bi ' + v + ' icon-prev'; }
    }
    if (!S.dirty) { setDirty(true); var n = $('.savebar .note'); if (n) { n.textContent = 'You have unsaved changes.'; } }
  }
  document.addEventListener('input', onInput);
  document.addEventListener('change', onInput);

  document.addEventListener('toggle', function (e) {
    var d = e.target;
    if (d && d.matches && d.matches('details.item')) { if (d.open) { S.open.add(d.dataset.key); } else { S.open.delete(d.dataset.key); } }
  }, true);

  window.addEventListener('beforeunload', function (e) { if (S.dirty) { e.preventDefault(); e.returnValue = ''; } });

  /* ---------- start ---------- */
  (async function boot() {
    try {
      var res = await Promise.all([api('content'), api('dashboard')]);
      S.c = res[0].content; S.admin = res[0].admin; S.dash = res[1];
      var r = await api('messages'); S.msgs = r.messages || [];
      render();
    } catch (e) {
      $('#app').innerHTML = '<div class="boot text-danger">' + esc(e.message) + '</div>';
    }
  })();
})();