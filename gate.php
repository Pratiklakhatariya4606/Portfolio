<?php
define('NO_GATE', 1);
require 'includes/init.php';
if (role()) { header('Location: ' . (role() === 'admin' ? 'inbox.php' : 'index.php')); exit; }
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
function fails($pdo, $ip) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip=? AND success=0 AND attempted_at > NOW() - INTERVAL 15 MINUTE");
    $s->execute([$ip]); return (int)$s->fetchColumn();
}
$mode = $_GET['mode'] ?? ''; $err = []; $note = '';
$locked = fails($pdo, $ip) >= 3;
if ($mode === 'admin' && $locked) { $mode = 'viewer'; $note = 'Owner login is locked for 15 minutes after 3 wrong attempts. Please continue as a viewer.'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = $_POST['do'] ?? '';
    if (!csrf_ok($_POST['csrf'] ?? '')) { $err[] = 'Session expired. Please try again.'; }
    elseif ($do === 'admin' && !$locked) {
        $mode = 'admin'; $aid = null;
        foreach ($pdo->query("SELECT id,password_hash FROM admin_users")->fetchAll() as $r)
            if (password_verify((string)($_POST['password'] ?? ''), $r['password_hash'])) { $aid = (int)$r['id']; break; }
        $pdo->prepare("INSERT INTO login_attempts (ip,success) VALUES (?,?)")->execute([$ip, $aid ? 1 : 0]);
        if ($aid) {
            $pdo->prepare("DELETE FROM login_attempts WHERE ip=? AND success=0")->execute([$ip]);
            $pdo->prepare("UPDATE admin_users SET last_login_at=NOW() WHERE id=?")->execute([$aid]);
            session_regenerate_id(true);
            $_SESSION['role'] = 'admin'; $_SESSION['admin_id'] = $aid; $_SESSION['welcome'] = 'Welcome back! Inbox access granted.';
            header('Location: inbox.php'); exit;
        }
        $left = 3 - fails($pdo, $ip);
        if ($left <= 0) { $mode = 'viewer'; $note = 'Three wrong attempts. You are continuing as a normal viewer.'; }
        else $err[] = "Wrong password. $left attempt(s) left.";
    }
    elseif ($do === 'viewer') {
        $mode = 'viewer';
        $fn = trim($_POST['first_name'] ?? ''); $sn = trim($_POST['surname'] ?? '');
        $age = (int)($_POST['age'] ?? 0); $fd = trim($_POST['field'] ?? '');
        if (!empty($_POST['website'])) $err[] = 'Verification failed.';
        if (empty($_POST['human'])) $err[] = 'Please tick "I\'m not a robot".';
        if ((int)($_POST['answer'] ?? -1) !== (int)($_SESSION['cap_a'] ?? 0) + (int)($_SESSION['cap_b'] ?? 0)) $err[] = 'Wrong answer to the verification question.';
        if (time() - (int)($_SESSION['cap_t'] ?? time()) < 2) $err[] = 'That was too fast. Please try again.';
        foreach ([$fn, $sn] as $n) if (mb_strlen($n) < 1 || mb_strlen($n) > 60 || !preg_match('/^[\p{L} .\'-]+$/u', $n)) { $err[] = 'Enter a valid name and surname (letters only).'; break; }
        if ($age < 5 || $age > 100) $err[] = 'Enter a valid age.';
        if (mb_strlen($fd) < 2 || mb_strlen($fd) > 100) $err[] = 'Enter your field (e.g. Computer Science).';
        if (!$err) {
            $pdo->prepare("INSERT INTO visitors (first_name,surname,age,field,ip_address) VALUES (?,?,?,?,?)")->execute([$fn, $sn, $age, $fd, $ip]);
            $vid = (int)$pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['role'] = 'viewer'; $_SESSION['visitor_id'] = $vid; $_SESSION['welcome'] = "Welcome, $fn! Enjoy exploring my portfolio.";
            header('Location: index.php'); exit;
        }
    }
}
$_SESSION['cap_a'] = random_int(1, 9); $_SESSION['cap_b'] = random_int(1, 9); $_SESSION['cap_t'] = time();
$P = fn($k) => e($_POST[$k] ?? '');
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Welcome | <?= e($profile['first_name']) ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="gate-body"><main class="gate card">
<p class="tag" style="text-align:center">&gt; access check</p>
<h1>Welcome to <span class="grad"><?= e($profile['first_name'] . ' ' . $profile['last_name']) ?></span>'s portfolio</h1>
<?php if ($note) echo '<div class="alert bad">' . e($note) . '</div>'; foreach ($err as $x) echo '<div class="alert bad">' . e($x) . '</div>'; ?>
<?php if ($mode === 'admin'): ?>
<form method="post" class="form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="admin">
<label>Owner / team password<input type="password" name="password" autocomplete="current-password" required autofocus></label>
<button class="btn">Open inbox</button><a href="gate.php" class="muted">&larr; Back</a></form>
<?php elseif ($mode === 'viewer'): ?>
<p class="muted" style="text-align:center">Tell me a little about you, then confirm you are human.</p>
<form method="post" class="form" novalidate><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="do" value="viewer">
<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
<div class="two"><label>Name<input name="first_name" value="<?= $P('first_name') ?>" required></label><label>Surname<input name="surname" value="<?= $P('surname') ?>" required></label></div>
<div class="two"><label>Age<input type="number" name="age" min="5" max="100" value="<?= $P('age') ?>" required></label><label>Field<input name="field" placeholder="e.g. Computer Science" value="<?= $P('field') ?>" required></label></div>
<label class="human"><input type="checkbox" name="human" value="1"> I'm not a robot</label>
<label>Verify: what is <?= (int)$_SESSION['cap_a'] ?> + <?= (int)$_SESSION['cap_b'] ?> ?<input type="number" name="answer" required></label>
<button class="btn">Enter portfolio</button><a href="gate.php" class="muted">&larr; Back</a></form>
<?php else: ?>
<p class="muted" style="text-align:center">Who is visiting?</p>
<div class="choice"><a href="gate.php?mode=viewer"><b>&#128100; I'm a viewer</b><small>Browse the portfolio and send me a message</small></a>
<a href="gate.php?mode=admin"><b>&#128274; I'm the owner / team</b><small>Password required to open the inbox</small></a></div>
<?php endif; ?></main></body></html>
