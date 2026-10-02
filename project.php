<?php require 'includes/layout.php';
$st = $pdo->prepare("SELECT * FROM projects WHERE slug=? AND is_published=1"); $st->execute([$_GET['slug'] ?? '']);
$p = $st->fetch(); if (!$p) { http_response_code(404); exit('Project not found'); }
track($pdo, 'project', $p['id']);
$pdo->prepare("UPDATE projects SET view_count=view_count+1 WHERE id=?")->execute([$p['id']]);
page_start($p['title'], 'work'); ?>
<section class="wrap"><a href="work.php" class="muted">&larr; Back</a><h1 class="sec"><?= e($p['title']) ?></h1>
<div class="card"><p><?= nl2br(e($p['description'] ?: $p['summary'])) ?></p>
<div class="row"><?php if ($p['live_url']) echo '<a class="btn" target="_blank" href="' . e($p['live_url']) . '">Live demo</a>';
if ($p['github_url']) echo '<a class="btn ghost" target="_blank" href="' . e($p['github_url']) . '">Source code</a>'; ?></div></div></section>
<?php page_end();
