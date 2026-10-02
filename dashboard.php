<?php require 'includes/layout.php'; track($pdo, 'dashboard');
$n = fn($q) => (float)$pdo->query($q)->fetchColumn();
$stats = ['Projects'=>$n("SELECT COUNT(*) FROM projects WHERE is_published=1"),
  'Hours logged'=>$n("SELECT COALESCE(SUM(hours_spent),0) FROM activity_log"),
  'Visits (30d)'=>$n("SELECT COUNT(*) FROM page_visits WHERE visited_at >= NOW() - INTERVAL 30 DAY"),
  'Unique visitors'=>$n("SELECT COUNT(DISTINCT session_id) FROM page_visits WHERE visited_at >= NOW() - INTERVAL 30 DAY")];
$days = []; for ($i = 6; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
foreach ($pdo->query("SELECT DATE(visited_at) d, COUNT(*) c FROM page_visits WHERE visited_at >= CURDATE() - INTERVAL 6 DAY GROUP BY d") as $r) $days[$r['d']] = (int)$r['c'];
$max = max(1, max($days));
$cats = $pdo->query("SELECT category, SUM(hours_spent) h FROM activity_log GROUP BY category ORDER BY h DESC")->fetchAll();
$cmax = max(1, ...array_map(fn($c) => (float)$c['h'], $cats ?: [['h'=>1]]));
$recent = $pdo->query("SELECT * FROM activity_log ORDER BY activity_date DESC, id DESC LIMIT 6")->fetchAll();
$top = $pdo->query("SELECT title, view_count FROM projects ORDER BY view_count DESC LIMIT 5")->fetchAll();
page_start('Dashboard', 'dashboard'); ?>
<section class="wrap"><h1 class="sec">Dashboard</h1>
<div class="grid4"><?php foreach ($stats as $k => $v): ?><div class="card stat"><b><?= number_format($v, $v == floor($v) ? 0 : 1) ?></b><span class="muted"><?= $k ?></span></div><?php endforeach; ?></div>
<div class="grid2">
<div class="card"><h3>Visits &mdash; last 7 days</h3><div class="chart"><?php foreach ($days as $d => $c): ?>
<div class="col"><span><?= $c ?></span><i style="height:<?= round($c / $max * 100) ?>%"></i><small><?= date('D', strtotime($d)) ?></small></div><?php endforeach; ?></div></div>
<div class="card"><h3>Hours by category</h3><?php foreach ($cats as $c): ?><div class="between"><span><?= e($c['category']) ?></span><small class="muted"><?= (float)$c['h'] ?>h</small></div>
<div class="bar"><i style="width:<?= round($c['h'] / $cmax * 100) ?>%"></i></div><?php endforeach; if (!$cats) echo '<p class="muted">Add rows to activity_log.</p>'; ?></div>
<div class="card"><h3>Recent activity</h3><?php foreach ($recent as $a): ?><p><span class="badge"><?= e($a['activity_date']) ?></span> <?= e($a['title']) ?> <small class="muted">(<?= (float)$a['hours_spent'] ?>h)</small></p><?php endforeach; ?></div>
<div class="card"><h3>Most viewed projects</h3><?php foreach ($top as $t) echo '<div class="between"><span>' . e($t['title']) . '</span><b>' . (int)$t['view_count'] . '</b></div>'; ?></div>
</div></section>
<?php page_end();
