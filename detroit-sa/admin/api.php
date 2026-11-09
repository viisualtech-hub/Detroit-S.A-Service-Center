<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/sanitize.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail($msg, $code = 400) {
    out(array('ok' => false, 'error' => $msg), $code);
}
function need_owner() {
    if (!is_owner()) { fail('Your account can view this but cannot change it.', 403); }
}
function public_user($u, $selfId) {
    return array('id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'],
        'created' => isset($u['created']) ? $u['created'] : '', 'changed' => isset($u['changed']) ? $u['changed'] : '',
        'default' => !empty($u['default']), 'self' => $u['id'] === $selfId);
}

/** Things on the website that need someone's attention. */
function attention_items($c) {
    $items = array();
    $add = function ($level, $text, $go) use (&$items) { $items[] = array('level' => $level, 'text' => $text, 'go' => $go); };

    $msgs = data_read('messages', array());
    $new = 0; $old = 0; $cut = strtotime('-1 day');
    foreach ($msgs as $m) {
        if (empty($m['read'])) {
            $new++;
            $t = isset($m['time']) ? strtotime($m['time']) : 0;
            if ($t && $t < $cut) { $old++; }
        }
    }
    if ($old > 0) { $add('danger', $old . ' request(s) have been waiting for a reply for more than 24 hours.', 'messages'); }
    if ($new > 0) { $add('warn', $new . ' new request(s) have not been read yet.', 'messages'); }

    foreach (users_all() as $u) {
        if (!empty($u['default'])) { $add('danger', 'The temporary login (' . $u['email'] . ') has not been changed yet.', 'account'); break; }
    }

    $today = date('Y-m-d');
    foreach ((array)$c['notices'] as $n) {
        if (!empty($n['active']) && !empty($n['expires']) && $n['expires'] < $today) {
            $add('warn', 'The notice "' . ($n['title'] !== '' ? $n['title'] : substr($n['message'], 0, 40)) . '" has expired but is still switched on.', 'notices');
        }
    }

    $slides = 0; $noalt = 0;
    foreach ((array)$c['hero']['slides'] as $s) { if (!empty($s['active'])) { $slides++; if (trim((string)$s['alt']) === '') { $noalt++; } } }
    if ($slides === 0) { $add('danger', 'The Home page has no active hero slides.', 'hero'); }
    if ($noalt > 0) { $add('info', $noalt . ' hero slide(s) have no image description (bad for accessibility and Google).', 'hero'); }

    $noimg = 0; $noshort = 0;
    foreach ((array)$c['services'] as $s) { if (asset($s['image']) === '') { $noimg++; } if (trim((string)$s['short']) === '') { $noshort++; } }
    if ($noimg > 0) { $add('info', $noimg . ' service(s) have no photo.', 'services'); }
    if ($noshort > 0) { $add('info', $noshort . ' service(s) have no short description.', 'services'); }

    $nocap = 0;
    foreach ((array)$c['gallery'] as $g) { if (trim((string)$g['caption']) === '') { $nocap++; } }
    if ($nocap > 0) { $add('info', $nocap . ' gallery photo(s) have no caption.', 'gallery'); }

    if (trim((string)$c['site']['email']) === '') { $add('danger', 'The business email is empty, so booking emails cannot be sent.', 'settings'); }
    if (trim((string)$c['site']['phone']) === '' && trim((string)$c['site']['cell']) === '') { $add('warn', 'No phone number is set on the website.', 'settings'); }

    $nologo = 0;
    foreach (array('banks', 'insurers') as $k) { foreach ((array)$c['partners'][$k] as $b) { if (asset($b['logo']) === '') { $nologo++; } } }
    if ($nologo > 0) { $add('info', $nologo . ' bank/insurer name(s) are shown as text because no logo has been uploaded.', 'partners'); }

    if (count((array)$c['testimonials']) === 0) { $add('info', 'There are no customer testimonials yet, so that section is hidden.', 'testimonials'); }

    if (!is_dir(DATA_DIR) || !is_writable(DATA_DIR)) { $add('danger', 'The "data" folder is not writable, so changes cannot be saved.', ''); }
    if (!is_dir(UPLOAD_DIR) || !is_writable(UPLOAD_DIR)) { $add('warn', 'The "uploads" folder is not writable, so images cannot be uploaded.', ''); }
    if (!function_exists('imagecreatetruecolor')) { $add('info', 'The PHP image extension (GD) is not enabled, so uploaded photos are not resized.', ''); }

    $stats = data_read('stats', array('days' => array()));
    $week = 0;
    for ($i = 0; $i < 7; $i++) {
        $d = date('Y-m-d', strtotime('-' . $i . ' days'));
        if (isset($stats['days'][$d]['total'])) { $week += (int)$stats['days'][$d]['total']; }
    }
    if ($week === 0) { $add('info', 'The website has had no recorded visits in the last 7 days.', 'dashboard'); }

    return $items;
}

admin_session();
if (!is_logged()) { fail('Please sign in again.', 401); }

$me = current_user();
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];
$body = array();
if ($method === 'POST') {
    $tok = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    if (!csrf_check($tok)) { fail('Security check failed. Refresh the page and try again.', 403); }
    $ct = isset($_SERVER['CONTENT_TYPE']) ? (string)$_SERVER['CONTENT_TYPE'] : '';
    if (stripos($ct, 'application/json') !== false) {
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        if (is_array($decoded)) { $body = $decoded; }
    }
}

switch ($action) {

    case 'content':
        $info = array('email' => $me['email'], 'name' => $me['name'], 'role' => $me['role'], 'default' => !empty($me['default']));
        out(array('ok' => true, 'content' => is_owner() ? C() : null, 'admin' => $info));
        break;

    case 'save':
        need_owner();
        if ($method !== 'POST') { fail('POST required.', 405); }
        $section = (string)gv($body, 'section', '');
        if (!in_array($section, section_names(), true)) { fail('Unknown section.'); }
        $clean = clean_section($section, gv($body, 'data', array()));
        if ($clean === null) { fail('Could not read that section.'); }
        if ($section === 'site' && $clean['email'] === '') { fail('Please enter a valid business email address.'); }
        if ($section === 'site' && $clean['name'] === '') { fail('Please enter the business name.'); }
        $content = data_read('content', array());
        $content[$section] = $clean;
        if (!isset($content['_meta'])) { $content['_meta'] = array('version' => 2); }
        if (!data_write('content', $content)) { fail('Could not save. Check that the "data" folder is writable.', 500); }
        out(array('ok' => true, 'data' => $clean));
        break;

    case 'media':
        need_owner();
        out(array('ok' => true, 'files' => media_list()));
        break;

    case 'upload':
        need_owner();
        if ($method !== 'POST') { fail('POST required.', 405); }
        if (!isset($_FILES['file'])) { fail('No file received. The image may be larger than the server allows.'); }
        $r = media_upload($_FILES['file']);
        if (!$r[0]) { fail($r[1]); }
        out(array('ok' => true, 'path' => $r[1]));
        break;

    case 'delete_media':
        need_owner();
        if ($method !== 'POST') { fail('POST required.', 405); }
        $p = safe_path((string)gv($body, 'path', ''));
        if ($p === '' || strpos($p, 'uploads/') !== 0 || !is_file(ROOT . '/' . $p)) { fail('That file cannot be deleted.'); }
        $used = json_encode(C(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strpos($used, $p) !== false) { fail('This image is still used on the website. Remove it from the page first.'); }
        if (!@unlink(ROOT . '/' . $p)) { fail('Could not delete the file.'); }
        out(array('ok' => true));
        break;

    case 'dashboard':
        $c = C();
        $msgs = data_read('messages', array());
        $new = 0;
        foreach ($msgs as $m) { if (empty($m['read'])) { $new++; } }
        $stats = data_read('stats', array('days' => array()));
        $days = array();
        $pages = array();
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $t = isset($stats['days'][$d]['total']) ? (int)$stats['days'][$d]['total'] : 0;
            $days[] = array('date' => $d, 'total' => $t);
            if (isset($stats['days'][$d]['pages']) && is_array($stats['days'][$d]['pages'])) {
                foreach ($stats['days'][$d]['pages'] as $pg => $n) {
                    $pages[$pg] = (isset($pages[$pg]) ? $pages[$pg] : 0) + (int)$n;
                }
            }
        }
        arsort($pages);
        out(array('ok' => true,
            'counts' => array(
                'services' => count($c['services']), 'gallery' => count($c['gallery']),
                'slides' => count($c['hero']['slides']), 'notices' => count(active_notices($c)),
                'testimonials' => count($c['testimonials']), 'messages' => count($msgs), 'new_messages' => $new),
            'days' => $days, 'pages' => $pages,
            'recent' => array_slice(array_values($msgs), 0, 5),
            'attention' => attention_items($c)));
        break;

    case 'messages':
        out(array('ok' => true, 'messages' => array_values(data_read('messages', array()))));
        break;

    case 'message_update':
        if ($method !== 'POST') { fail('POST required.', 405); }
        $id = (string)gv($body, 'id', '');
        $op = (string)gv($body, 'op', '');
        if ($op === 'delete') { need_owner(); }
        $msgs = array_values(data_read('messages', array()));
        $out_list = array();
        foreach ($msgs as $m) {
            if (isset($m['id']) && $m['id'] === $id) {
                if ($op === 'delete') { continue; }
                if ($op === 'read') { $m['read'] = true; }
                if ($op === 'unread') { $m['read'] = false; }
            }
            $out_list[] = $m;
        }
        if (!data_write('messages', $out_list)) { fail('Could not update messages.', 500); }
        out(array('ok' => true));
        break;

    case 'account': // change your own login details
        if ($method !== 'POST') { fail('POST required.', 405); }
        $current = (string)gv($body, 'current', '');
        $email = strtolower(trim((string)gv($body, 'email', '')));
        $name = s_clean(gv($body, 'name', ''), 60);
        $new = (string)gv($body, 'password', '');
        $confirm = (string)gv($body, 'confirm', '');
        if (!password_verify($current, $me['hash'])) { fail('Your current password is not correct.'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { fail('Please enter a valid login email address.'); }
        $other = user_by_email($email);
        if ($other !== null && $other['id'] !== $me['id']) { fail('Another user already uses that email address.'); }
        if ($new !== '') {
            if (strlen($new) < 8) { fail('The new password must be at least 8 characters long.'); }
            if ($new !== $confirm) { fail('The new password and the confirmation do not match.'); }
        }
        $list = users_all();
        foreach ($list as $i => $u) {
            if ($u['id'] === $me['id']) {
                $list[$i]['email'] = $email;
                if ($name !== '') { $list[$i]['name'] = $name; }
                if ($new !== '') { $list[$i]['hash'] = password_hash($new, PASSWORD_DEFAULT); }
                $list[$i]['default'] = false;
                $list[$i]['changed'] = date('Y-m-d H:i');
            }
        }
        if (!users_save($list)) { fail('Could not save the new login details.', 500); }
        session_regenerate_id(true);
        out(array('ok' => true, 'email' => $email, 'name' => $name !== '' ? $name : $me['name']));
        break;

    case 'users':
        need_owner();
        $list = array();
        foreach (users_all() as $u) { $list[] = public_user($u, $me['id']); }
        out(array('ok' => true, 'users' => $list));
        break;

    case 'user_add':
        need_owner();
        if ($method !== 'POST') { fail('POST required.', 405); }
        $name = s_clean(gv($body, 'name', ''), 60);
        $email = strtolower(trim((string)gv($body, 'email', '')));
        $pass = (string)gv($body, 'password', '');
        $role = (string)gv($body, 'role', 'viewer');
        if ($name === '') { fail('Please enter the person\'s name.'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { fail('Please enter a valid email address.'); }
        if (user_by_email($email) !== null) { fail('A user with that email already exists.'); }
        if (strlen($pass) < 8) { fail('The password must be at least 8 characters long.'); }
        if (!in_array($role, array('viewer', 'owner'), true)) { $role = 'viewer'; }
        $list = users_all();
        $list[] = array('id' => 'u' . bin2hex(random_bytes(4)), 'name' => $name, 'email' => $email, 'hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => $role, 'default' => false, 'created' => date('Y-m-d H:i'), 'changed' => '');
        if (!users_save($list)) { fail('Could not save the new user.', 500); }
        out(array('ok' => true));
        break;

    case 'user_update':
        need_owner();
        if ($method !== 'POST') { fail('POST required.', 405); }
        $id = (string)gv($body, 'id', '');
        $role = (string)gv($body, 'role', '');
        $pass = (string)gv($body, 'password', '');
        $list = users_all();
        $owners = 0;
        foreach ($list as $u) { if ($u['role'] === 'owner') { $owners++; } }
        $found = false;
        foreach ($list as $i => $u) {
            if ($u['id'] !== $id) { continue; }
            $found = true;
            if ($role !== '') {
                if (!in_array($role, array('viewer', 'owner'), true)) { fail('Unknown role.'); }
                if ($u['role'] === 'owner' && $role !== 'owner' && $owners <= 1) { fail('There must always be at least one full admin.'); }
                $list[$i]['role'] = $role;
            }
            if ($pass !== '') {
                if (strlen($pass) < 8) { fail('The password must be at least 8 characters long.'); }
                $list[$i]['hash'] = password_hash($pass, PASSWORD_DEFAULT);
                $list[$i]['default'] = false;
                $list[$i]['changed'] = date('Y-m-d H:i');
            }
        }
        if (!$found) { fail('User not found.'); }
        if (!users_save($list)) { fail('Could not save.', 500); }
        out(array('ok' => true));
        break;

    case 'user_delete':
        need_owner();
        if ($method !== 'POST') { fail('POST required.', 405); }
        $id = (string)gv($body, 'id', '');
        if ($id === $me['id']) { fail('You cannot delete your own account.'); }
        $list = users_all();
        $keep = array();
        $owners = 0;
        foreach ($list as $u) {
            if ($u['id'] !== $id) { $keep[] = $u; if ($u['role'] === 'owner') { $owners++; } }
        }
        if (count($keep) === count($list)) { fail('User not found.'); }
        if ($owners < 1) { fail('There must always be at least one full admin.'); }
        if (!users_save($keep)) { fail('Could not save.', 500); }
        out(array('ok' => true));
        break;

    default:
        fail('Unknown action.', 404);
}
