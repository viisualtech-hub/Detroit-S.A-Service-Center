<?php
// Booking form handler. Saves each request in the admin panel inbox and emails a copy to the business.
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function reply($ok, $msg) {
    echo json_encode(array('success' => $ok, 'message' => $msg));
    exit;
}
function line($v, $max) {
    $v = trim(strip_tags((string)$v));
    $v = preg_replace('/[\r\n\t]+/', ' ', $v);
    return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { reply(false, 'Invalid request.'); }
if (!empty($_POST['website'])) { reply(true, 'Thank you!'); } // honeypot: bots fill this in

$C = C();
$is_parts = isset($_POST['form']) && $_POST['form'] === 'parts';
$name = line(isset($_POST['name']) ? $_POST['name'] : '', 100);
$phone = line(isset($_POST['phone']) ? $_POST['phone'] : '', 30);
$email = line(isset($_POST['email']) ? $_POST['email'] : '', 120);
$brand = line(isset($_POST['brand']) ? $_POST['brand'] : '', 60);
$message = trim(strip_tags(isset($_POST['message']) ? (string)$_POST['message'] : ''));
$message = function_exists('mb_substr') ? mb_substr($message, 0, 2000, 'UTF-8') : substr($message, 0, 2000);

if ($is_parts) {
    $model = line(isset($_POST['model']) ? $_POST['model'] : '', 80);
    $part = line(isset($_POST['part']) ? $_POST['part'] : '', 120);
    if ($name === '' || $phone === '' || $brand === '' || $model === '' || $part === '') {
        reply(false, 'Please fill in all required fields.');
    }
    $service = 'Parts: ' . $part;
    $message = 'Vehicle: ' . $model . "\nPart needed: " . $part . ($message !== '' ? "\nDetails: " . $message : '');
} else {
    $service = line(isset($_POST['service']) ? $_POST['service'] : '', 100);
    if ($name === '' || $phone === '' || $brand === '' || $service === '' || $message === '') {
        reply(false, 'Please fill in all required fields.');
    }
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }

// Allow at most 5 requests per hour from one visitor.
$k = md5(client_ip());
$rate = data_read('rate', array());
$now = time();
foreach ($rate as $key => $times) {
    $rate[$key] = array_values(array_filter((array)$times, function ($t) use ($now) { return $now - (int)$t < 3600; }));
    if (count($rate[$key]) === 0) { unset($rate[$key]); }
}
if (isset($rate[$k]) && count($rate[$k]) >= 5) { reply(false, 'Too many requests. Please call us on ' . $C['site']['phone'] . '.'); }
$rate[$k][] = $now;
data_write('rate', $rate);

// 1) Save it in the admin inbox.
$msgs = array_values(data_read('messages', array()));
array_unshift($msgs, array(
    'id' => 'm' . bin2hex(random_bytes(5)),
    'time' => date('Y-m-d H:i'),
    'name' => $name, 'phone' => $phone, 'email' => $email,
    'brand' => $brand, 'service' => $service, 'message' => $message,
    'type' => $is_parts ? 'parts' : 'booking',
    'read' => false,
));
$msgs = array_slice($msgs, 0, 500);
$saved = data_write('messages', $msgs);

// 2) Email a copy.
$to = $C['site']['email'];
$mailed = false;
if ($to !== '' && function_exists('mail')) {
    $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', (string)$_SERVER['HTTP_HOST']) : 'localhost';
    $host = preg_replace('/^www\./', '', $host);
    $from = 'no-reply@' . $host;
    $subject = ($is_parts ? 'New parts request: ' : 'New booking request: ') . $service . ' (' . $brand . ')';
    $body = "Name: $name\nPhone: $phone\nEmail: " . ($email !== '' ? $email : 'n/a') . "\nVehicle: $brand\nService: $service\n\nMessage:\n$message\n";
    $headers = 'From: ' . $from . "\r\n" . ($email !== '' ? 'Reply-To: ' . $email . "\r\n" : '') . "Content-Type: text/plain; charset=UTF-8\r\n";
    $mailed = @mail($to, $subject, $body, $headers);
}

if ($saved || $mailed) { reply(true, "Thank you $name, we'll get back to you shortly."); }
reply(false, 'Sorry, something went wrong. Please call ' . $C['site']['phone'] . '.');
