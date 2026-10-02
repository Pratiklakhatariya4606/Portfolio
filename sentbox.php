<?php require 'includes/layout.php';
if (role() === 'admin') { header('Location: inbox.php'); exit; }
track($pdo, 'sentbox');
$vid = (int)($_SESSION['visitor_id'] ?? 0); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0); $txt = trim($_POST['message'] ?? '');
    if (!csrf_ok($_POST['csrf'] ?? '')) $err = 'Session expired. Reload and try again.';
    elseif ($txt === '' || mb_strlen($txt) > 3000) $err = 'Message must be 1-3000 characters.';
    else {
        $pdo->prepare("UPDATE contact_messages SET message=?, edited_at=NOW() WHERE id=? AND visitor_id=?")->execute([$txt, $id, $vid]);
        header('Location: sentbox.php?edited=1#m' . $id); exit;
    }
}
$st = $pdo->prepare("SELECT * FROM contact_messages WHERE visitor_id=? ORDER BY submitted_at DESC"); $st->execute([$vid]);
$msgs = $st->fetchAll(); $rep = [];
if ($msgs) { $in = implode(',', array_map('intval', array_column($msgs, 'id')));
    foreach ($pdo->query("SELECT * FROM message_replies WHERE message_id IN ($in) ORDER BY created_at") as $r) $rep[$r['message_id']][] = $r; }
page_start('Sent box', 'sentbox'); ?>
<section class="wrap narrow"><h1 class="sec">Sent box</h1>
<?php if (isset($_GET['sent'])) echo '<div class="alert good">Message sent! I will reply soon.</div>';
if (isset($_GET['edited'])) echo '<div class="alert good">Message updated.</div>';
if ($err) echo '<div class="alert bad">' . e($err) . '</div>'; ?>
<div class="stack"><?php foreach ($msgs as $m): ?>
<div class="card msg" id="m<?= (int)$m['id'] ?>"><div class="meta"><small class="muted"><?= e($m['submitted_at']) ?><?= $m['edited_at'] ? ' &middot; edited' : '' ?></small><span class="badge"><?= e($m['status']) ?></span></div>
<p><?= nl2br(e($m['message'])) ?></p>
<?php foreach ($rep[$m['id']] ?? [] as $r) echo '<div class="reply"><small class="muted">Reply &middot; ' . e($r['created_at']) . '</small><br>' . nl2br(e($r['body'])) . '</div>'; ?>
<details><summary class="btn sm ghost">Edit message</summary>
<form method="post" class="form" style="margin-top:12px"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
<textarea name="message" rows="4" maxlength="3000" required><?= e($m['message']) ?></textarea><button class="btn sm">Save changes</button></form></details></div>
<?php endforeach; if (!$msgs) echo '<p class="muted">No messages yet. <a href="contact.php">Send me one</a>.</p>'; ?></div></section>
<?php page_end();
