<?php require 'includes/layout.php'; track($pdo, 'about');
$edu = $pdo->query("SELECT * FROM education ORDER BY sort_order, start_year DESC")->fetchAll();
$skills = $pdo->query("SELECT * FROM skills ORDER BY category, sort_order")->fetchAll();
page_start('About', 'about'); ?>
<section class="wrap"><h1 class="sec">About me</h1>
<div class="card about"><img class="photo sm" src="<?= e($profile['photo_path'] ?: 'assets/img/myself.jpg') ?>" alt="Portrait of <?= e($profile['first_name']) ?>"><div><p><?= nl2br(e($profile['about_me'])) ?></p>
<p class="muted">&#128205; <?= e(trim($profile['city'] . ', ' . $profile['country'], ', ')) ?> &middot; <?= e($profile['email']) ?></p></div></div>
<h2 class="sec">What I do</h2>
<div class="grid"><div class="card"><h3>&#128187; Full-stack development</h3><p class="muted">Building complete web apps with PHP, MySQL, HTML, CSS and JavaScript.</p></div>
<div class="card"><h3>&#128451; Database design</h3><p class="muted">Clean, normalized schemas with proper keys, indexes and secure queries.</p></div>
<div class="card"><h3>&#127912; Responsive UI/UX</h3><p class="muted">Interfaces that look good and work well on phones, tablets and desktops.</p></div>
<div class="card"><h3>&#128274; Secure coding</h3><p class="muted">Prepared statements, hashed passwords, input validation and CSRF protection.</p></div></div>
<h2 class="sec">Education</h2>
<div class="timeline"><?php foreach ($edu as $x): ?><div class="card"><h3><?= e($x['degree']) ?> <small class="muted">&mdash; <?= e($x['field_of_study']) ?></small></h3>
<p><?= e($x['institution']) ?> <span class="badge"><?= e($x['start_year']) ?> - <?= $x['end_year'] ? e($x['end_year']) : 'Present' ?></span></p><p class="muted"><?= e($x['description']) ?></p></div><?php endforeach; ?></div>
<h2 class="sec">Skills</h2>
<div class="grid"><?php foreach ($skills as $s): ?><div class="card"><div class="between"><b><?php if ($s['icon_class']) echo '<i class="' . e($s['icon_class']) . '"></i> '; ?><?= e($s['name']) ?></b><small class="muted"><?= e($s['category']) ?></small></div>
<div class="bar"><i style="width:<?= (int)$s['proficiency'] ?>%"></i></div></div><?php endforeach; ?></div></section>
<?php page_end();
