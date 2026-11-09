<?php
/** Admin login, users, roles, session and CSRF helpers. */
if (defined('DSA_AUTH')) { return; }
define('DSA_AUTH', true);

// Temporary first-time login. It is only used until the owner changes it in Admin > Account.
define('DEFAULT_ADMIN_EMAIL', 'vmabanga0@gmail.com');
define('DEFAULT_ADMIN_PASSWORD', 'Vuyani2026');

function admin_session() {
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    session_name('dsa_admin');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array('lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax'));
    } else {
        session_set_cookie_params(0, '/', '', $https, true);
    }
    session_start();
    if (isset($_SESSION['last']) && (time() - (int)$_SESSION['last']) > 7200) { // 2 hours idle
        $_SESSION = array();
    }
    $_SESSION['last'] = time();
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
    return $_SESSION['csrf'];
}

function csrf_check($t) {
    return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}

/* ---------- users ---------- */

/** All users. Creates the first owner (from the old single login, or the temporary login) when needed. */
function users_all() {
    $u = data_read('users', array());
    if (is_array($u) && count($u) > 0) { return array_values($u); }
    $legacy = data_read('admin', array());
    if (!empty($legacy['email']) && !empty($legacy['hash'])) {
        $u = array(array(
            'id' => 'u1', 'name' => 'Owner', 'email' => $legacy['email'], 'hash' => $legacy['hash'], 'role' => 'owner',
            'default' => !empty($legacy['default']), 'created' => date('Y-m-d H:i'), 'changed' => isset($legacy['changed']) ? $legacy['changed'] : '',
        ));
    } else {
        $u = array(array(
            'id' => 'u1', 'name' => 'Owner', 'email' => DEFAULT_ADMIN_EMAIL, 'hash' => password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT),
            'role' => 'owner', 'default' => true, 'created' => date('Y-m-d H:i'), 'changed' => '',
        ));
    }
    data_write('users', $u);
    return $u;
}

function users_save($list) {
    return data_write('users', array_values($list));
}

function user_by_email($email) {
    $email = strtolower(trim((string)$email));
    foreach (users_all() as $u) { if (strtolower($u['email']) === $email) { return $u; } }
    return null;
}

function user_by_id($id) {
    foreach (users_all() as $u) { if ($u['id'] === $id) { return $u; } }
    return null;
}

function current_user() {
    if (empty($_SESSION['dsa_uid'])) { return null; }
    return user_by_id($_SESSION['dsa_uid']);
}

function is_logged() {
    return !empty($_SESSION['dsa_ok']) && current_user() !== null;
}

function is_owner() {
    $u = current_user();
    return $u !== null && $u['role'] === 'owner';
}

/* ---- simple brute-force protection: 5 wrong attempts = 15 minute lock ---- */

function login_wait() {
    $k = md5(client_ip());
    $l = data_read('login', array());
    if (isset($l[$k]['until']) && (int)$l[$k]['until'] > time()) { return (int)$l[$k]['until'] - time(); }
    return 0;
}

function login_failed() {
    $k = md5(client_ip());
    $l = data_read('login', array());
    $now = time();
    foreach ($l as $key => $v) { // prune old entries
        if (!isset($v['last']) || $now - (int)$v['last'] > 86400) { unset($l[$key]); }
    }
    if (!isset($l[$k]) || $now - (int)$l[$k]['last'] > 900) { $l[$k] = array('count' => 0, 'last' => $now, 'until' => 0); }
    $l[$k]['count'] = (int)$l[$k]['count'] + 1;
    $l[$k]['last'] = $now;
    if ($l[$k]['count'] >= 5) { $l[$k]['until'] = $now + 900; $l[$k]['count'] = 0; }
    data_write('login', $l);
}

function login_succeeded() {
    $k = md5(client_ip());
    $l = data_read('login', array());
    if (isset($l[$k])) { unset($l[$k]); data_write('login', $l); }
}
