<?php require 'includes/layout.php'; require_once 'includes/mailer.php';
if (role() !== 'admin') { header('Location: gate.php?mode=admin'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok($_POST['csrf'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0); $act = $_POST['act'] ?? '';
    if ($act === 'reply') {
        $b = trim($_POST['body'] ?? '');
        if ($b !== '' && mb_strlen($b) <= 3000) {
            $pdo->prepare("INSERT INTO message_replies (message_id,admin_id,body) VALUES (?,?,?)")->execute([$id, $_SESSION['admin_id'] ?? null, $b]);
            $pdo->prepare("UPDATE contact_messages SET status='replied' WHERE id=?")->execute([$id]);
            $q = $pdo->prepare("SELECT first_name,email,mobile,country FROM contact_messages WHERE id=?"); $q->execute([$id]);
            if ($m = $q->fetch()) {
                $on = cfg('owner_name', 'Pratik');
                if (!empty($_POST['by_email'])) {
                    if (email_deliverable($m['email'])) { $s = "Reply from $on"; notify($pdo, $id, 'email', $m['email'], $s, fn() => send_mail($m['email'], $s, "Hi {$m['first_name']},\n\n$b\n\n- $on", cfg('owner_email'))); }
                    else note_skip($pdo, $id, 'email', $m['email'], 'Email not deliverable; reply saved in sent box only');
                }
                if (!empty($_POST['by_sms'])) {
                    $ph = normalize_phone($m['mobile'], $m['country']);
                    if ($ph) notify($pdo, $id, 'sms', $ph, 'reply', fn() => sms_send($ph, "$on: $b"));
                    else note_skip($pdo, $id, 'sms', $m['mobile'], 'Number is not a valid mobile number');
                }
            }
        }
    } elseif (in_array($act, ['read', 'archived'], true)) {
        $pdo->prepare("UPDATE contact_messages SET status=? WHERE id=?")->execute([$act, $id]);
    }
    header('Location: inbox.php#m' . $id); exit;
}
$msgs = $pdo->query("SELECT m.*, v.age FROM contact_messages m LEFT JOIN visitors v ON v.id=m.visitor_id WHERE m.status<>'archived' ORDER BY (m.status='new') DESC, m.submitted_at DESC LIMIT 100")->fetchAll();
$rep = [];
if ($msgs) { $in = implode(',', array_map('intval', array_column($msgs, 'id')));
    foreach ($pdo->query("SELECT * FROM message_replies WHERE message_id IN ($in) ORDER BY created_at") as $r) $rep[$r['message_id']][] = $r; }
$pdo->exec("UPDATE contact_messages SET status='read' WHERE status='new'");
$fb = $pdo->query("SELECT COUNT(*) t, COALESCE(SUM(rating='like'),0) l, COALESCE(SUM(rating='dislike'),0) d FROM feedback")->fetch();
$cm = $pdo->query("SELECT rating, comments, created_at FROM feedback WHERE comments<>'' ORDER BY created_at DESC LIMIT 10")->fetchAll();
page_start('Inbox', 'inbox'); ?>
<section class="wrap"><h1 class="sec">Inbox <small class="muted">(owner &amp; team only)</small></h1>
<div class="stack"><?php foreach ($msgs as $m): ?>
<div class="card msg" id="m<?= (int)$m['id'] ?>"><div class="meta"><b><?= e($m['first_name'] . ' ' . $m['surname']) ?></b><span class="badge"><?= e($m['status']) ?></span></div>
<small class="muted"><?= e($m['field']) ?> &middot; <?= e($m['study_year']) ?><?= $m['age'] ? ' &middot; age ' . (int)$m['age'] : '' ?> &middot; <?= e($m['city'] . ', ' . $m['country']) ?><br>
<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a> &middot; <a href="tel:<?= e($m['mobile']) ?>"><?= e($m['mobile']) ?></a> &middot; <?= e($m['submitted_at']) ?><?= $m['edited_at'] ? ' &middot; edited' : '' ?></small>
<p><?= nl2br(e($m['message'])) ?></p>
<?php foreach ($rep[$m['id']] ?? [] as $r) echo '<div class="reply"><small class="muted">Reply &middot; ' . e($r['created_at']) . '</small><br>' . nl2br(e($r['body'])) . '</div>'; ?>
<form method="post" class="form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
<textarea name="body" rows="3" maxlength="3000" placeholder="Write a reply..." required></textarea>
<div class="row" style="margin:0"><label class="chk"><input type="checkbox" name="by_email" value="1" checked> Email</label><label class="chk"><input type="checkbox" name="by_sms" value="1"> SMS</label><button class="btn sm" name="act" value="reply">Send reply</button><button class="btn sm ghost" name="act" value="archived" formnovalidate>Archive</button><?php if ($wa = normalize_phone($m['mobile'], $m['country'])) echo '<a class="btn sm ghost" target="_blank" rel="noopener" href="https://wa.me/' . ltrim($wa, '+') . '">WhatsApp</a>'; ?></div></form></div>
<?php endforeach; if (!$msgs) echo '<p class="muted">Inbox is empty.</p>'; ?></div>
<h2 class="sec">Visitor feedback</h2>
<div class="grid4"><div class="card stat"><b><?= (int)$fb['l'] ?></b><span class="muted">&#128077; Likes</span></div><div class="card stat"><b><?= (int)$fb['d'] ?></b><span class="muted">&#128078; Dislikes</span></div></div>
<div class="stack" style="margin-top:16px"><?php foreach ($cm as $c) echo '<div class="card"><span class="badge">' . e($c['rating']) . '</span> <small class="muted">' . e($c['created_at']) . '</small><p>' . nl2br(e($c['comments'])) . '</p></div>'; ?></div>
<h2 class="sec">Delivery log <small class="muted">(emails &amp; texts)</small></h2>
<div class="stack"><?php foreach ($pdo->query("SELECT * FROM notification_log ORDER BY id DESC LIMIT 15") as $n)
  echo '<div class="card"><span class="badge st-' . e($n['status']) . '">' . e($n['status']) . '</span> <b>' . e($n['channel']) . '</b> to ' . e($n['to_addr']) . ' <small class="muted">' . e($n['created_at']) . '</small>' . ($n['error'] ? '<br><small class="muted">' . e($n['error']) . '</small>' : '') . '</div>'; ?></div></section>
<?php page_end();
