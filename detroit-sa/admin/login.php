<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';
admin_session();
users_all();

if (is_logged()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wait = login_wait();
    $tok = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if ($wait > 0) {
        $error = 'Too many attempts. Please try again in ' . ceil($wait / 60) . ' minute(s).';
    } elseif (!csrf_check($tok)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = strtolower(trim(isset($_POST['email']) ? (string)$_POST['email'] : ''));
        $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';
        $u = $email !== '' ? user_by_email($email) : null;
        if ($u !== null && password_verify($pass, $u['hash'])) {
            login_succeeded();
            session_regenerate_id(true);
            $_SESSION['dsa_ok'] = true;
            $_SESSION['dsa_uid'] = $u['id'];
            $_SESSION['last'] = time();
            unset($_SESSION['csrf']);
            header('Location: index.php');
            exit;
        }
        login_failed();
        $error = 'Invalid email or password.';
    }
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Admin login | Detroit S.A</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="../images/favicon.png">
<link href="assets/admin.css" rel="stylesheet">
</head>
<body class="login-body">
<main class="login-card">
  <img src="../images/logo.png" alt="Detroit S.A Service Centre" class="login-logo">
  <h1>Admin panel</h1>
  <p class="text-muted">Sign in to manage the website.</p>
  <?php if ($error !== ''): ?><div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="login.php" autocomplete="on">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input class="form-control form-control-lg" id="email" name="email" type="email" autocomplete="username" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label" for="password">Password</label>
      <div class="input-group">
        <input class="form-control form-control-lg" id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="btn btn-outline-secondary" type="button" id="togglePw" aria-label="Show password"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <button class="btn btn-brand btn-lg w-100" type="submit">Sign in</button>
  </form>
  <p class="text-center mt-4 mb-0"><a href="../index.php" class="small">&larr; Back to website</a></p>
</main>
<script>
document.getElementById('togglePw').addEventListener('click', function () {
  var i = document.getElementById('password');
  i.type = i.type === 'password' ? 'text' : 'password';
});
</script>
</body>
</html>
