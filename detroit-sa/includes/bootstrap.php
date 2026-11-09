<?php
/**
 * Shared helpers for the public site, the admin panel and the booking form.
 * Needs PHP 7.0 or newer.
 */
if (defined('DSA_BOOT')) { return; }
define('DSA_BOOT', true);

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('UPLOAD_DIR', ROOT . '/uploads');
define('DATA_GUARD', "<?php http_response_code(404); exit; ?>\n");

date_default_timezone_set('Africa/Johannesburg');

// Show PHP errors only when running on your own computer / phone (localhost).
$dsa_host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
$dsa_local = in_array($dsa_host, array('localhost', '127.0.0.1', '[::1]', '::1'), true) || php_sapi_name() === 'cli-server';
error_reporting(E_ALL);
ini_set('display_errors', $dsa_local ? '1' : '0');
ini_set('log_errors', '1');

/* ---------- small helpers ---------- */

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Turn text with blank lines between paragraphs into <p> tags (escaped). */
function paras($text) {
    $out = '';
    $parts = preg_split('/\R{2,}/', trim((string)$text));
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') { $out .= '<p>' . nl2br(e($p)) . '</p>'; }
    }
    return $out;
}

function slug($s) {
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string)$s), '-'));
    return $s === '' ? 'x' : $s;
}

/** Only allow image paths that live in /images or /uploads. */
function safe_path($p) {
    $p = (string)$p;
    if (!preg_match('#^(images|uploads)/[A-Za-z0-9_\-./]+$#', $p) || strpos($p, '..') !== false) { return ''; }
    return $p;
}

/** Escaped image URL (relative to the site root) or empty string. */
function asset($p) {
    $p = safe_path($p);
    return $p === '' ? '' : e($p);
}

function icon_class($i) {
    $i = (string)$i;
    return preg_match('/^bi-[a-z0-9-]{1,40}$/', $i) ? $i : 'bi-gear';
}

function dsa_is_list($a) {
    return is_array($a) && ($a === array() || array_keys($a) === range(0, count($a) - 1));
}

function client_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/* ---------- data files (JSON stored behind a PHP guard line) ---------- */

function data_path($name) {
    return DATA_DIR . '/' . preg_replace('/[^a-z0-9_]/', '', (string)$name) . '.json.php';
}

function data_read($name, $default = array()) {
    $p = data_path($name);
    if (!is_file($p)) { return $default; }
    $raw = @file_get_contents($p);
    if ($raw === false) { return $default; }
    $nl = strpos($raw, "\n");
    if ($nl === false) { return $default; }
    $d = json_decode(substr($raw, $nl + 1), true);
    return is_array($d) ? $d : $default;
}

function data_write($name, $data) {
    if (!is_dir(DATA_DIR)) { @mkdir(DATA_DIR, 0755, true); }
    $p = data_path($name);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) { return false; }
    $tmp = $p . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, DATA_GUARD . $json, LOCK_EX) === false) { return false; }
    if (!@rename($tmp, $p)) {
        $ok = @copy($tmp, $p);
        @unlink($tmp);
        return $ok;
    }
    return true;
}

/** Fill in any keys missing from $cur using $def (lists are never merged). */
function merge_defaults($def, $cur) {
    if (!is_array($def) || !is_array($cur)) { return $cur; }
    if (dsa_is_list($def)) { return $cur; }          // lists are kept exactly as saved
    if ($cur === array()) { return $def; }           // nothing saved yet: use the defaults
    if (dsa_is_list($cur)) { return $cur; }
    foreach ($def as $k => $v) {
        if (!array_key_exists($k, $cur)) {
            $cur[$k] = $v;
        } elseif (is_array($v) && !dsa_is_list($v) && is_array($cur[$k])) {
            $cur[$k] = merge_defaults($v, $cur[$k]);
        }
    }
    return $cur;
}

/** One-time update for sites that already saved content before version 2 (adds the new services). */
function migrate_content($saved) {
    if (empty($saved)) { return $saved; }
    $v = isset($saved['_meta']['version']) ? (int)$saved['_meta']['version'] : 1;
    if ($v >= 2) { return $saved; }
    $def = data_read('content_default', array());
    if (isset($saved['services']) && is_array($saved['services']) && isset($def['services']) && is_array($def['services'])) {
        $have = array();
        foreach ($saved['services'] as $sv) { if (isset($sv['id'])) { $have[$sv['id']] = true; } }
        foreach ($def['services'] as $sv) {
            if (in_array($sv['id'], array('touchups', 'structural', 'respray', 'warranty'), true) && !isset($have[$sv['id']])) {
                $saved['services'][] = $sv;
            }
        }
    }
    $saved['_meta'] = array('version' => 2);
    data_write('content', $saved);
    return $saved;
}

/** All website content (saved content merged over the shipped defaults). */
function C() {
    static $c = null;
    if ($c === null) {
        $def = data_read('content_default', array());
        $saved = migrate_content(data_read('content', array()));
        $c = merge_defaults($def, $saved);
    }
    return $c;
}

/* ---------- notices ---------- */

function active_notices($C) {
    $out = array();
    $today = date('Y-m-d');
    $list = isset($C['notices']) && is_array($C['notices']) ? $C['notices'] : array();
    foreach ($list as $n) {
        if (empty($n['active'])) { continue; }
        if (!empty($n['expires']) && $n['expires'] < $today) { continue; }
        if (!empty($n['starts']) && $n['starts'] > $today) { continue; }
        if (trim((string)$n['title']) === '' && trim((string)$n['message']) === '') { continue; }
        $out[] = $n;
    }
    return $out;
}

/* ---------- page-view counter (for the admin dashboard) ---------- */

function record_view($page) {
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget/i', $ua)) { return; }
    if (!empty($_COOKIE['dsa_admin'])) { return; } // do not count the admin's own visits
    $s = data_read('stats', array('days' => array()));
    if (!isset($s['days']) || !is_array($s['days'])) { $s['days'] = array(); }
    $d = date('Y-m-d');
    if (!isset($s['days'][$d])) { $s['days'][$d] = array('total' => 0, 'pages' => array()); }
    $s['days'][$d]['total'] = (int)$s['days'][$d]['total'] + 1;
    if (!isset($s['days'][$d]['pages'][$page])) { $s['days'][$d]['pages'][$page] = 0; }
    $s['days'][$d]['pages'][$page]++;
    ksort($s['days']);
    while (count($s['days']) > 60) { array_shift($s['days']); }
    data_write('stats', $s);
}

/* ---------- reusable HTML blocks ---------- */

function service_card($s, $n, $delay) {
    $detail = !empty($s['detail']);
    $href = 'services.php' . ($detail ? '#svc-' . e($s['id']) : '');
    $img = asset($s['image']);
    $h = '<div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="' . (int)$delay . '">';
    $h .= '<div class="svc-card h-100">';
    $h .= '<div class="svc-img">';
    if ($img !== '') { $h .= '<img src="' . $img . '" alt="' . e($s['title']) . '" loading="lazy">'; }
    $h .= '<span class="svc-no">' . sprintf('%02d', $n) . '</span></div>';
    $h .= '<i class="bi ' . e(icon_class($s['icon'])) . '"></i>';
    $h .= '<h3>' . e($s['title']) . '</h3>';
    $h .= '<p>' . e($s['short']) . '</p>';
    if ($detail) { $h .= '<a class="svc-more" href="' . $href . '">Read more <i class="bi bi-arrow-right"></i></a>'; }
    $h .= '</div></div>';
    return $h;
}

function cta_band($C) {
    $S = $C['site'];
    $c = $C['cta'];
    $h = '<section class="cta-band"><div class="container d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" data-aos="fade-up">';
    $h .= '<div><h2>' . e($c['title']) . '</h2><p class="mb-0">' . e($c['text']) . '</p></div>';
    $h .= '<div class="d-flex gap-2 flex-wrap"><a href="contact.php" class="btn btn-light btn-lg">' . e($c['button_text']) . '</a>';
    $h .= '<a href="tel:' . e($S['phone_intl']) . '" class="btn btn-outline-light btn-lg">' . e($S['phone']) . '</a></div>';
    $h .= '</div></section>';
    return $h;
}

function page_hero($title, $sub) {
    return '<header class="page-hero"><div class="container" data-aos="fade-up"><h1>' . e($title) . '</h1><p>' . e($sub) . '</p></div><div class="hazard"></div></header>';
}

function initial($name) {
    $name = trim((string)$name);
    if ($name === '') { return 'C'; }
    $ch = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($ch, 'UTF-8') : strtoupper($ch);
}

/* ---------- v2 helpers: links, logos, extra sections, partners ---------- */

function nav_label($S, $key, $def) {
    return (isset($S['nav'][$key]) && trim((string)$S['nav'][$key]) !== '') ? $S['nav'][$key] : $def;
}

/** Links typed in the admin: only http(s), mailto, tel or a page on this site. */
function safe_link($l) {
    $l = trim((string)$l);
    if ($l === '') { return '#'; }
    if (preg_match('#^(https?://|mailto:|tel:)#i', $l)) { return $l; }
    if (preg_match('#^[A-Za-z0-9_\-./\#?=&]+$#', $l)) { return $l; }
    return '#';
}

/** A grid of logo tiles. A tile shows the logo if there is one, otherwise the name. */
function logo_tiles($list, $cls = '') {
    $h = '<div class="logo-grid ' . e($cls) . '">';
    foreach ((array)$list as $b) {
        $name = isset($b['name']) ? (string)$b['name'] : '';
        $logo = asset(isset($b['logo']) ? $b['logo'] : '');
        if ($name === '' && $logo === '') { continue; }
        $h .= '<div class="logo-tile" title="' . e($name) . '">';
        $h .= $logo !== '' ? '<img src="' . $logo . '" alt="' . e($name) . '" loading="lazy">' : '<span>' . e($name) . '</span>';
        $h .= '</div>';
    }
    return $h . '</div>';
}

/** Extra sections the admin added to a page. $pos is 'top' or 'bottom'. */
function render_blocks($C, $page, $pos) {
    $out = '';
    $list = (isset($C['blocks'][$page]) && is_array($C['blocks'][$page])) ? $C['blocks'][$page] : array();
    foreach ($list as $b) {
        if (empty($b['active'])) { continue; }
        if ((isset($b['position']) ? $b['position'] : 'bottom') !== $pos) { continue; }
        $title = trim((string)$b['title']);
        $text = trim((string)$b['text']);
        $img = asset($b['image']);
        if ($title === '' && $text === '' && $img === '') { continue; }
        $layout = in_array($b['layout'], array('image-left', 'image-right', 'text-only'), true) ? $b['layout'] : 'image-right';
        if ($img === '') { $layout = 'text-only'; }
        $btn = '';
        if (trim((string)$b['button_text']) !== '') {
            $btn = '<a class="btn btn-brand btn-lg mt-2" href="' . e(safe_link($b['button_link'])) . '">' . e($b['button_text']) . '</a>';
        }
        $tcls = $layout === 'text-only' ? 'col-lg-10 mx-auto text-center' : 'col-lg-6';
        $textcol = '<div class="' . $tcls . '" data-aos="fade-up">' . ($title !== '' ? '<h2>' . e($title) . '</h2>' : '') . '<div class="block-text">' . paras($text) . '</div>' . $btn . '</div>';
        $imgcol = ($layout !== 'text-only') ? '<div class="col-lg-6" data-aos="zoom-in"><img class="block-img" src="' . $img . '" alt="' . e($title) . '" loading="lazy"></div>' : '';
        $row = $layout === 'image-left' ? $imgcol . $textcol : $textcol . $imgcol;
        $out .= '<section class="section block-section"><div class="container"><div class="row g-5 align-items-center">' . $row . '</div></div></section>';
    }
    return $out;
}

/** Banks, insurers and extended warranty. */
function render_partners($C) {
    $P = $C['partners'];
    $h = '<section class="section section-alt partners-section" id="partners"><div class="container">';
    $h .= '<div class="section-head" data-aos="fade-up"><span class="eyebrow dark">Finance &amp; cover</span><h2>' . e($P['title']) . '</h2>';
    if (trim((string)$P['text']) !== '') { $h .= '<p>' . e($P['text']) . '</p>'; }
    $h .= '</div><div class="row g-4">';
    if (count($P['banks']) > 0) {
        $h .= '<div class="col-lg-6" data-aos="fade-up"><div class="partner-card h-100"><i class="bi bi-bank"></i><h3>' . e($P['banks_title']) . '</h3>';
        if (trim((string)$P['banks_text']) !== '') { $h .= '<p>' . e($P['banks_text']) . '</p>'; }
        $h .= logo_tiles($P['banks'], 'small') . '</div></div>';
    }
    if (count($P['insurers']) > 0) {
        $h .= '<div class="col-lg-6" data-aos="fade-up" data-aos-delay="100"><div class="partner-card h-100"><i class="bi bi-shield-check"></i><h3>' . e($P['insurers_title']) . '</h3>';
        if (trim((string)$P['insurers_text']) !== '') { $h .= '<p>' . e($P['insurers_text']) . '</p>'; }
        $h .= logo_tiles($P['insurers'], 'small') . '</div></div>';
    }
    $h .= '</div>';
    if (trim((string)$P['warranty_title']) !== '') {
        $h .= '<div class="warranty-band" data-aos="fade-up"><div><h3>' . e($P['warranty_title']) . '</h3><p>' . e($P['warranty_text']) . '</p></div>';
        if (trim((string)$P['warranty_button']) !== '') { $h .= '<a class="btn btn-brand btn-lg" href="contact.php">' . e($P['warranty_button']) . '</a>'; }
        $h .= '</div>';
    }
    return $h . '</div></section>';
}

/** The same logos as logo_tiles(), but scrolling slowly across the page like the red text strip. */
function logo_marquee($list) {
    $tiles = '';
    foreach ((array)$list as $b) {
        $name = isset($b['name']) ? (string)$b['name'] : '';
        $logo = asset(isset($b['logo']) ? $b['logo'] : '');
        if ($name === '' && $logo === '') { continue; }
        $tiles .= '<div class="logo-tile" title="' . e($name) . '">';
        $tiles .= $logo !== '' ? '<img src="' . $logo . '" alt="' . e($name) . '" loading="lazy">' : '<span>' . e($name) . '</span>';
        $tiles .= '</div>';
    }
    if ($tiles === '') { return ''; }
    return '<div class="logo-marquee"><div class="logo-track">'
        . '<div class="logo-set">' . $tiles . '</div>'
        . '<div class="logo-set" aria-hidden="true">' . $tiles . '</div>'
        . '<div class="logo-set" aria-hidden="true">' . $tiles . '</div>'
        . '<div class="logo-set" aria-hidden="true">' . $tiles . '</div>'
        . '</div></div>';
}