<?php require 'includes/layout.php'; track($pdo, 'contact');
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$f = array_fill_keys(['first_name','surname','study_year','field','country','city','email','mobile','message'], '');
if (role() === 'admin') { header('Location: inbox.php'); exit; }
$vs = $pdo->prepare("SELECT first_name,surname,field FROM visitors WHERE id=?"); $vs->execute([(int)($_SESSION['visitor_id'] ?? 0)]);
if ($vr = $vs->fetch()) { $f['first_name'] = $vr['first_name']; $f['surname'] = $vr['surname']; $f['field'] = $vr['field']; }
$errors = []; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) $errors[] = 'Session expired. Reload and try again.';
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');
    foreach ($f as $k => $v) if ($v === '') $errors[] = ucwords(str_replace('_', ' ', $k)) . ' is required.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if ($f['mobile'] && !preg_match('/^\+?[0-9\s\-]{7,15}$/', $f['mobile'])) $errors[] = 'Enter a valid mobile number (7-15 digits).';
    if (mb_strlen($f['message']) > 3000) $errors[] = 'Message is too long.';
    if (!empty($_POST['website'])) $errors[] = 'Spam detected.'; // honeypot
    if (!$errors) {
        $pdo->prepare("INSERT INTO contact_messages (first_name,surname,study_year,field,country,city,email,mobile,message,ip_address,visitor_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([...array_values($f), $_SERVER['REMOTE_ADDR'] ?? null, (int)($_SESSION['visitor_id'] ?? 0) ?: null]);
        $mid = (int)$pdo->lastInsertId();
        try { require_once 'includes/mailer.php'; notify_new_message($pdo, $mid, $f); } catch (Throwable $t) { /* never block the visitor */ }
        header('Location: sentbox.php?sent=1'); exit;
    }
}
page_start('Contact', 'contact'); ?>
<section class="wrap narrow"><h1 class="sec">Contact me</h1>
<?php if ($ok): ?><div class="alert good">Thanks! Your message was sent. I'll get back to you soon.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert bad"><?php foreach ($errors as $x) echo '<div>' . e($x) . '</div>'; ?></div><?php endif; ?>
<form method="post" class="card form" novalidate>
<input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
<div class="two"><label>Name<input name="first_name" value="<?= e($f['first_name']) ?>" required></label><label>Surname<input name="surname" value="<?= e($f['surname']) ?>" required></label></div>
<div class="two"><label>Year<input name="study_year" placeholder="e.g. Final Year" value="<?= e($f['study_year']) ?>" required></label><label>Field<input name="field" placeholder="e.g. Computer Science" value="<?= e($f['field']) ?>" required></label></div>
<div class="two"><label>Country<input name="country" value="<?= e($f['country']) ?>" required></label><label>City<input name="city" value="<?= e($f['city']) ?>" required></label></div>
<div class="two"><label>Email<input type="email" name="email" value="<?= e($f['email']) ?>" required></label><label>Mobile number<input name="mobile" placeholder="+91 98765 43210" value="<?= e($f['mobile']) ?>" required></label></div>
<label>Message<textarea name="message" rows="5" required><?= e($f['message']) ?></textarea></label>
<button class="btn" type="submit">Send message</button></form></section>
<?php page_end();
