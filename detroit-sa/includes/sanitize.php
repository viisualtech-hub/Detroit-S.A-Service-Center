<?php
/** Cleans everything the admin panel sends before it is saved, and handles image uploads. */
if (defined('DSA_SANITIZE')) { return; }
define('DSA_SANITIZE', true);

/* ---------- primitive cleaners ---------- */

function gv($a, $k, $d = '') {
    return (is_array($a) && array_key_exists($k, $a)) ? $a[$k] : $d;
}

function gl($a, $k) {
    $v = gv($a, $k, array());
    return is_array($v) ? array_values($v) : array();
}

function s_clean($v, $max = 300) {
    if (!is_scalar($v)) { return ''; }
    $v = (string)$v;
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $v);
    $v = str_replace("\r\n", "\n", $v);
    $v = trim($v);
    if (function_exists('mb_substr')) { $v = mb_substr($v, 0, $max, 'UTF-8'); } else { $v = substr($v, 0, $max); }
    return $v;
}

function b_clean($v) {
    return $v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 'on';
}

function int_clean($v, $min, $max, $def) {
    if (!is_numeric($v)) { return $def; }
    return max($min, min($max, (int)$v));
}

function lines_clean($v, $max = 200, $count = 60) {
    if (is_string($v)) { $v = preg_split('/\R/', $v); }
    if (!is_array($v)) { return array(); }
    $out = array();
    foreach ($v as $line) {
        $line = s_clean($line, $max);
        if ($line !== '') { $out[] = $line; }
        if (count($out) >= $count) { break; }
    }
    return $out;
}

function date_clean($v) {
    $v = is_scalar($v) ? (string)$v : '';
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
}

function link_clean($v) {
    $v = s_clean($v, 400);
    if ($v === '') { return ''; }
    if (preg_match('#^https?://#i', $v)) { return filter_var($v, FILTER_VALIDATE_URL) ? $v : ''; }
    if (preg_match('#^(tel|mailto):[A-Za-z0-9@+._\-]+$#i', $v)) { return $v; }
    return preg_match('#^[A-Za-z0-9_\-./\#?=&]+$#', $v) ? $v : '';
}

function email_clean($v) {
    $v = s_clean($v, 120);
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : '';
}

function img_clean($v) {
    $p = safe_path(is_scalar($v) ? (string)$v : '');
    return ($p !== '' && is_file(ROOT . '/' . $p)) ? $p : '';
}

function imgs_clean($v, $count = 30) {
    $out = array();
    if (!is_array($v)) { return $out; }
    foreach ($v as $p) {
        $p = img_clean($p);
        if ($p !== '' && !in_array($p, $out, true)) { $out[] = $p; }
        if (count($out) >= $count) { break; }
    }
    return $out;
}

function icon_clean($v) {
    $v = is_scalar($v) ? (string)$v : '';
    return preg_match('/^bi-[a-z0-9-]{1,40}$/', $v) ? $v : 'bi-gear';
}

function id_clean($v, &$seen) {
    $v = is_scalar($v) ? (string)$v : '';
    if (!preg_match('/^[A-Za-z0-9_-]{1,40}$/', $v) || isset($seen[$v])) { $v = 'i' . bin2hex(random_bytes(4)); }
    $seen[$v] = true;
    return $v;
}

/** "031 577 0160" becomes "+27315770160". Returns '' if it does not look like a phone number. */
function intl_from($v) {
    $d = preg_replace('/\D/', '', (string)$v);
    if ($d === '') { return ''; }
    if (substr($d, 0, 1) === '0') { $d = '27' . substr($d, 1); }
    return (strlen($d) >= 9 && strlen($d) <= 15) ? '+' . $d : '';
}

/* ---------- list-of-records cleaner ---------- */

function clean_records($list, $spec, $max, $require = array()) {
    $out = array();
    if (!is_array($list)) { return $out; }
    $seen = array();
    foreach (array_slice(array_values($list), 0, $max) as $row) {
        if (!is_array($row)) { continue; }
        $r = array();
        foreach ($spec as $k => $t) {
            $v = gv($row, $k, '');
            switch ($t[0]) {
                case 'id':    $r[$k] = id_clean($v, $seen); break;
                case 's':     $r[$k] = s_clean($v, $t[1]); break;
                case 'b':     $r[$k] = b_clean($v); break;
                case 'img':   $r[$k] = img_clean($v); break;
                case 'imgs':  $r[$k] = imgs_clean($v, $t[1]); break;
                case 'icon':  $r[$k] = icon_clean($v); break;
                case 'date':  $r[$k] = date_clean($v); break;
                case 'link':  $r[$k] = link_clean($v); break;
                case 'lines': $r[$k] = lines_clean($v, $t[1], $t[2]); break;
                case 'enum':  $r[$k] = in_array($v, $t[1], true) ? $v : $t[1][0]; break;
                case 'int':   $r[$k] = int_clean($v, $t[1], $t[2], $t[3]); break;
            }
        }
        $keep = empty($require);
        foreach ($require as $rk) { if (!empty($r[$rk])) { $keep = true; } }
        if ($keep) { $out[] = $r; }
    }
    return $out;
}

/* ---------- one cleaner per content section ---------- */

function clean_section($section, $d) {
    switch ($section) {

        case 'site':
            $hours = clean_records(gv($d, 'hours', array()), array('days' => array('s', 60), 'time' => array('s', 40)), 10, array('days'));
            $phone = s_clean(gv($d, 'phone'), 30);
            $cell = s_clean(gv($d, 'cell'), 30);
            $wa = s_clean(gv($d, 'whatsapp_display'), 30);
            $wa_intl = intl_from($wa !== '' ? $wa : ($cell !== '' ? $cell : $phone));
            return array(
                'name' => s_clean(gv($d, 'name'), 100),
                'short_name' => s_clean(gv($d, 'short_name'), 60),
                'tagline' => s_clean(gv($d, 'tagline'), 150),
                'phone' => $phone,
                'phone_intl' => intl_from($phone),
                'cell' => $cell,
                'cell_intl' => intl_from($cell),
                'cell2' => s_clean(gv($d, 'cell2'), 30),
                'cell2_intl' => intl_from(s_clean(gv($d, 'cell2'), 30)),
                'whatsapp_display' => $wa,
                'whatsapp' => ltrim($wa_intl, '+'),
                'email' => email_clean(gv($d, 'email')),
                'address' => s_clean(gv($d, 'address'), 200),
                'map_query' => s_clean(gv($d, 'map_query'), 200),
                'hours_short' => s_clean(gv($d, 'hours_short'), 80),
                'hours' => $hours,
                'facebook' => link_clean(gv($d, 'facebook')),
                'tiktok' => link_clean(gv($d, 'tiktok')),
                'instagram' => link_clean(gv($d, 'instagram')),
                'nav' => array(
                    'home' => s_clean(gv(gv($d, 'nav', array()), 'home'), 30) ?: 'Home',
                    'about' => s_clean(gv(gv($d, 'nav', array()), 'about'), 30) ?: 'About',
                    'services' => s_clean(gv(gv($d, 'nav', array()), 'services'), 30) ?: 'Services',
                    'parts' => s_clean(gv(gv($d, 'nav', array()), 'parts'), 30) ?: 'Parts',
                    'gallery' => s_clean(gv(gv($d, 'nav', array()), 'gallery'), 30) ?: 'Gallery',
                    'contact' => s_clean(gv(gv($d, 'nav', array()), 'contact'), 30) ?: 'Contact',
                ),
                'logo' => img_clean(gv($d, 'logo')) !== '' ? img_clean(gv($d, 'logo')) : 'images/logo.png',
                'footer_text' => s_clean(gv($d, 'footer_text'), 400),
                'meta_description' => s_clean(gv($d, 'meta_description'), 300),
            );

        case 'cta':
            return array(
                'title' => s_clean(gv($d, 'title'), 120),
                'text' => s_clean(gv($d, 'text'), 250),
                'button_text' => s_clean(gv($d, 'button_text'), 40),
            );

        case 'notices':
            return clean_records($d, array(
                'id' => array('id'), 'title' => array('s', 120), 'message' => array('s', 600),
                'type' => array('enum', array('info', 'warning', 'promo', 'closed')),
                'link' => array('link'), 'link_text' => array('s', 60),
                'starts' => array('date'), 'expires' => array('date'), 'active' => array('b'),
            ), 20);

        case 'hero':
            return array(
                'interval' => int_clean(gv($d, 'interval'), 2, 15, 5),
                'slides' => clean_records(gv($d, 'slides', array()), array(
                    'id' => array('id'), 'image' => array('img'), 'alt' => array('s', 200), 'active' => array('b'),
                ), 15, array('image')),
            );

        case 'home':
            return array(
                'seo_title' => s_clean(gv($d, 'seo_title'), 120),
                'hero_lines' => clean_records(gv($d, 'hero_lines', array()), array('text' => array('s', 80), 'color' => array('enum', array('white', 'red'))), 6, array('text')),
                'hero_lead' => s_clean(gv($d, 'hero_lead'), 800),
                'hero_buttons' => clean_records(gv($d, 'hero_buttons', array()), array('text' => array('s', 40), 'link' => array('link'), 'style' => array('enum', array('primary', 'outline'))), 4, array('text')),
                'info_title' => s_clean(gv($d, 'info_title'), 120),
                'info_items' => clean_records(gv($d, 'info_items', array()), array('icon' => array('icon'), 'title' => array('s', 60), 'text' => array('s', 400)), 12, array('title')),
                'intro_eyebrow' => s_clean(gv($d, 'intro_eyebrow'), 80),
                'logos_title' => s_clean(gv($d, 'logos_title'), 100),
                'brand_logos' => clean_records(gv($d, 'brand_logos', array()), array('name' => array('s', 40), 'logo' => array('img')), 40, array('name', 'logo')),
                'eyebrow' => s_clean(gv($d, 'eyebrow'), 120),
                'title_1' => s_clean(gv($d, 'title_1'), 80),
                'title_2' => s_clean(gv($d, 'title_2'), 80),
                'lead' => s_clean(gv($d, 'lead'), 800),
                'btn1' => s_clean(gv($d, 'btn1'), 40),
                'btn2' => s_clean(gv($d, 'btn2'), 40),
                'hero_photo' => img_clean(gv($d, 'hero_photo')),
                'hero_photo_alt' => s_clean(gv($d, 'hero_photo_alt'), 200),
                'hero_tag' => s_clean(gv($d, 'hero_tag'), 80),
                'stats' => clean_records(gv($d, 'stats', array()), array('value' => array('s', 20), 'label' => array('s', 60)), 6, array('value', 'label')),
                'ticker' => lines_clean(gv($d, 'ticker', array()), 40, 30),
                'makes_title' => s_clean(gv($d, 'makes_title'), 120),
                'makes_text' => s_clean(gv($d, 'makes_text'), 300),
                'specialists' => clean_records(gv($d, 'specialists', array()), array('name' => array('s', 40), 'text' => array('s', 200)), 6, array('name')),
                'other_makes_label' => s_clean(gv($d, 'other_makes_label'), 60),
                'other_makes' => lines_clean(gv($d, 'other_makes', array()), 40, 30),
                'services_title' => s_clean(gv($d, 'services_title'), 120),
                'services_text' => s_clean(gv($d, 'services_text'), 300),
                'why_title' => s_clean(gv($d, 'why_title'), 120),
                'why' => clean_records(gv($d, 'why', array()), array('title' => array('s', 60), 'text' => array('s', 250)), 8, array('title')),
                'steps_title' => s_clean(gv($d, 'steps_title'), 120),
                'steps' => clean_records(gv($d, 'steps', array()), array('title' => array('s', 60), 'text' => array('s', 250)), 8, array('title')),
                'reviews_title' => s_clean(gv($d, 'reviews_title'), 120),
                'reviews_text' => s_clean(gv($d, 'reviews_text'), 300),
            );

        case 'services':
            return clean_records($d, array(
                'id' => array('id'), 'title' => array('s', 100), 'icon' => array('icon'),
                'short' => array('s', 300), 'long' => array('s', 4000), 'image' => array('img'),
                'gallery' => array('imgs', 30), 'detail' => array('b'), 'home' => array('b'),
            ), 40, array('title'));

        case 'gallery':
            return clean_records($d, array(
                'id' => array('id'), 'image' => array('img'), 'caption' => array('s', 150), 'category' => array('s', 40),
            ), 300, array('image'));

        case 'testimonials':
            return clean_records($d, array(
                'id' => array('id'), 'name' => array('s', 80), 'vehicle' => array('s', 120),
                'text' => array('s', 1200), 'rating' => array('int', 1, 5, 5), 'active' => array('b'),
            ), 100, array('text'));

        case 'about':
            return array(
                'title' => s_clean(gv($d, 'title'), 120),
                'sub' => s_clean(gv($d, 'sub'), 250),
                'heading' => s_clean(gv($d, 'heading'), 160),
                'story' => s_clean(gv($d, 'story'), 6000),
                'image' => img_clean(gv($d, 'image')),
                'badge_title' => s_clean(gv($d, 'badge_title'), 60),
                'badge_text' => s_clean(gv($d, 'badge_text'), 80),
                'highlights' => clean_records(gv($d, 'highlights', array()), array('icon' => array('icon'), 'title' => array('s', 60), 'text' => array('s', 250)), 8, array('title')),
                'partners_title' => s_clean(gv($d, 'partners_title'), 120),
                'partners_text' => s_clean(gv($d, 'partners_text'), 300),
                'rmi_text' => s_clean(gv($d, 'rmi_text'), 300),
                'fleet_label' => s_clean(gv($d, 'fleet_label'), 80),
                'fleet' => lines_clean(gv($d, 'fleet', array()), 60, 30),
                'insurers_label' => s_clean(gv($d, 'insurers_label'), 80),
                'insurers' => lines_clean(gv($d, 'insurers', array()), 60, 30),
                'faq_title' => s_clean(gv($d, 'faq_title'), 120),
                'faq' => clean_records(gv($d, 'faq', array()), array('q' => array('s', 200), 'a' => array('s', 1500)), 30, array('q')),
            );

        case 'partners':
            $rec = array('name' => array('s', 60), 'logo' => array('img'));
            return array(
                'show_home' => b_clean(gv($d, 'show_home')),
                'show_services' => b_clean(gv($d, 'show_services')),
                'title' => s_clean(gv($d, 'title'), 120),
                'text' => s_clean(gv($d, 'text'), 500),
                'banks_title' => s_clean(gv($d, 'banks_title'), 120),
                'banks_text' => s_clean(gv($d, 'banks_text'), 300),
                'banks' => clean_records(gv($d, 'banks', array()), $rec, 40, array('name', 'logo')),
                'insurers_title' => s_clean(gv($d, 'insurers_title'), 120),
                'insurers_text' => s_clean(gv($d, 'insurers_text'), 300),
                'insurers' => clean_records(gv($d, 'insurers', array()), $rec, 40, array('name', 'logo')),
                'warranty_title' => s_clean(gv($d, 'warranty_title'), 120),
                'warranty_text' => s_clean(gv($d, 'warranty_text'), 500),
                'warranty_button' => s_clean(gv($d, 'warranty_button'), 40),
            );

        case 'parts':
            return array(
                'title' => s_clean(gv($d, 'title'), 120),
                'sub' => s_clean(gv($d, 'sub'), 250),
                'intro' => s_clean(gv($d, 'intro'), 2000),
                'categories_title' => s_clean(gv($d, 'categories_title'), 120),
                'categories' => clean_records(gv($d, 'categories', array()), array('id' => array('id'), 'icon' => array('icon'), 'title' => array('s', 80), 'text' => array('s', 300), 'image' => array('img')), 24, array('title')),
                'logos_title' => s_clean(gv($d, 'logos_title'), 120),
                'steps_title' => s_clean(gv($d, 'steps_title'), 120),
                'steps' => clean_records(gv($d, 'steps', array()), array('title' => array('s', 60), 'text' => array('s', 300)), 8, array('title')),
                'form_title' => s_clean(gv($d, 'form_title'), 120),
                'form_text' => s_clean(gv($d, 'form_text'), 300),
                'faq_title' => s_clean(gv($d, 'faq_title'), 120),
                'faq' => clean_records(gv($d, 'faq', array()), array('q' => array('s', 200), 'a' => array('s', 1500)), 30, array('q')),
            );

        case 'blocks':
            $out = array();
            foreach (array('home', 'about', 'services', 'parts', 'gallery', 'contact') as $pg) {
                $out[$pg] = clean_records(gv($d, $pg, array()), array(
                    'id' => array('id'), 'title' => array('s', 150), 'text' => array('s', 3000), 'image' => array('img'),
                    'layout' => array('enum', array('image-right', 'image-left', 'text-only')),
                    'position' => array('enum', array('bottom', 'top')),
                    'button_text' => array('s', 40), 'button_link' => array('link'), 'active' => array('b'),
                ), 20, array('title', 'text', 'image'));
            }
            return $out;

        case 'pages':
            $sv = gv($d, 'services', array());
            $ga = gv($d, 'gallery', array());
            $co = gv($d, 'contact', array());
            return array(
                'services' => array(
                    'title' => s_clean(gv($sv, 'title'), 120), 'sub' => s_clean(gv($sv, 'sub'), 250), 'intro' => s_clean(gv($sv, 'intro'), 500),
                    'signs_title' => s_clean(gv($sv, 'signs_title'), 120),
                    'signs' => clean_records(gv($sv, 'signs', array()), array('title' => array('s', 60), 'items' => array('lines', 100, 12)), 8, array('title')),
                ),
                'gallery' => array('title' => s_clean(gv($ga, 'title'), 120), 'sub' => s_clean(gv($ga, 'sub'), 250)),
                'contact' => array(
                    'title' => s_clean(gv($co, 'title'), 120), 'sub' => s_clean(gv($co, 'sub'), 250),
                    'form_title' => s_clean(gv($co, 'form_title'), 120), 'form_text' => s_clean(gv($co, 'form_text'), 300),
                ),
            );
    }
    return null;
}

function section_names() {
    return array('site', 'cta', 'notices', 'hero', 'home', 'services', 'gallery', 'testimonials', 'about', 'pages', 'partners', 'parts', 'blocks');
}

/* ---------- media library & uploads (JPG and PNG only) ---------- */

function media_list() {
    $out = array();
    foreach (array('uploads', 'images') as $dir) {
        $base = ROOT . '/' . $dir;
        if (!is_dir($base)) { continue; }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile()) { continue; }
            $ext = strtolower($f->getExtension());
            if (!in_array($ext, array('jpg', 'jpeg', 'png'), true)) { continue; }
            $rel = $dir . '/' . str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
            if ($rel === 'images/favicon.png') { continue; }
            $out[] = array('path' => $rel, 'name' => $f->getFilename(), 'mtime' => $f->getMTime(), 'deletable' => $dir === 'uploads');
        }
    }
    usort($out, function ($a, $b) {
        if ($a['deletable'] !== $b['deletable']) { return $a['deletable'] ? -1 : 1; }
        if ($a['deletable']) { return $b['mtime'] - $a['mtime']; }
        return strcmp($a['path'], $b['path']);
    });
    return $out;
}

function upload_error_text($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: return 'That image is too large for the server. Try a smaller photo.';
        case UPLOAD_ERR_NO_FILE: return 'No file was selected.';
        default: return 'The upload failed. Please try again.';
    }
}

/** Returns array(true, 'uploads/name.jpg') or array(false, 'error message'). */
function media_upload($file) {
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) { return array(false, 'Invalid upload.'); }
    if ($file['error'] !== UPLOAD_ERR_OK) { return array(false, upload_error_text($file['error'])); }
    if ($file['size'] > 12 * 1024 * 1024) { return array(false, 'That image is larger than 12 MB.'); }
    $info = @getimagesize($file['tmp_name']);
    if (!$info) { return array(false, 'That file is not a valid image. Please use a JPG or PNG.'); }
    $type = $info[2];
    if ($type === IMAGETYPE_JPEG) { $ext = 'jpg'; }
    elseif ($type === IMAGETYPE_PNG) { $ext = 'png'; }
    else { return array(false, 'Only JPG and PNG images are allowed.'); }

    if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0755, true); }
    if (!is_dir(UPLOAD_DIR) || !is_writable(UPLOAD_DIR)) {
        return array(false, 'The uploads folder is not writable. Give the "uploads" folder write permission (755 or 775).');
    }
    $name = 'u' . date('ymd') . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;
    $tmp = $file['tmp_name'];
    $done = false;

    // Re-encode with GD when available: strips hidden data, fixes phone-photo rotation, limits size to 1800px wide.
    if (function_exists('imagecreatetruecolor')) {
        $src = false;
        if ($ext === 'jpg' && function_exists('imagecreatefromjpeg')) { $src = @imagecreatefromjpeg($tmp); }
        if ($ext === 'png' && function_exists('imagecreatefrompng')) { $src = @imagecreatefrompng($tmp); }
        if ($src) {
            if ($ext === 'jpg' && function_exists('exif_read_data') && function_exists('imagerotate')) {
                $ex = @exif_read_data($tmp);
                $o = (is_array($ex) && isset($ex['Orientation'])) ? (int)$ex['Orientation'] : 1;
                $rot = false;
                if ($o === 3) { $rot = @imagerotate($src, 180, 0); }
                elseif ($o === 6) { $rot = @imagerotate($src, -90, 0); }
                elseif ($o === 8) { $rot = @imagerotate($src, 90, 0); }
                if ($rot) { $src = $rot; }
            }
            $w = imagesx($src);
            $h = imagesy($src);
            $max = 1800;
            if ($w > $max) {
                $nw = $max;
                $nh = (int)round($h * $max / $w);
                $dst = imagecreatetruecolor($nw, $nh);
                if ($ext === 'png') {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }
            if ($ext === 'png') { imagesavealpha($src, true); $done = @imagepng($src, $dest, 6); }
            else { $done = @imagejpeg($src, $dest, 84); }
            imagedestroy($src);
        }
    }
    if (!$done) { $done = @move_uploaded_file($tmp, $dest); }
    if (!$done) { return array(false, 'Could not save the image on the server.'); }
    @chmod($dest, 0644);
    return array(true, 'uploads/' . $name);
}
