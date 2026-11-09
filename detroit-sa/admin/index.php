<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';
admin_session();
if (!is_logged()) { header('Location: login.php'); exit; }
$csrf = csrf_token();
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= e($csrf) ?>">
<title>Admin | Detroit S.A</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="../images/favicon.png">
<link href="assets/admin.css" rel="stylesheet">
</head>
<body>
<div id="app"><div class="boot">Loading admin&hellip;</div></div>
<script src="assets/admin.js"></script>
</body>
</html>
