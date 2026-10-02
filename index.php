<?php require 'includes/layout.php'; track($pdo, 'home');
$feat = $pdo->query("SELECT * FROM projects WHERE is_published=1 AND is_featured=1 ORDER BY completed_on DESC LIMIT 3")->fetchAll();
$skills = $pdo->query("SELECT name, icon_class FROM skills ORDER BY sort_order LIMIT 12")->fetchAll();
page_start('Home', 'index'); ?>
<section class="hero wrap">
  <div>
    <p class="tag">&gt; hello, world</p>
    <h1>I'm <span class="grad"><?= e($profile['first_name'] . ' ' . $profile['last_name']) ?></span></h1>
    <h2><?= e($profile['headline']) ?></h2>
    <p class="muted"><?= e($profile['short_intro']) ?></p>
    <div class="row"><a class="btn" href="work.php">View my work</a><a class="btn ghost" href="contact.php">Contact me</a>
    <?php if ($profile['resume_path']): ?><a class="btn ghost" href="<?= e($profile['resume_path']) ?>" download>Resume</a><?php endif; ?></div>
    <div class="row"><?php social_icons($pdo); ?></div>
  </div>
  <div class="photo-wrap"><img class="photo" src="<?= e($profile['photo_path'] ?: 'assets/img/myself.jpg') ?>" alt="Portrait of <?= e($profile['first_name']) ?>">
  <div class="term card"><div class="dots"><i></i><i></i><i></i></div>
<pre><span class="c">// profile.js</span>
const me = {
  name: "<?= e($profile['first_name']) ?>",
  role: "<?= e($profile['headline']) ?>",
  location: "<?= e(trim($profile['city'] . ', ' . $profile['country'], ', ')) ?>",
  skills: [<?= implode(', ', array_map(fn($s) => '"' . e($s['name']) . '"', array_slice($skills, 0, 5))) ?>],
  openToWork: true
};</pre></div></div>
</section>
<section class="wrap"><h2 class="sec">Featured projects</h2>
<div class="grid"><?php foreach ($feat as $p): ?>
  <a class="card proj" href="project.php?slug=<?= e($p['slug']) ?>"><h3><?= e($p['title']) ?></h3><p class="muted"><?= e($p['summary']) ?></p><span class="badge"><?= e($p['status']) ?></span></a>
<?php endforeach; if (!$feat) echo '<p class="muted">Add projects (is_featured = 1) to show them here.</p>'; ?></div></section>
<section class="wrap"><h2 class="sec">Tech stack</h2><div class="chips"><?php foreach ($skills as $s) echo tech_chip($s['name'], $s['icon_class']); ?></div></section>
<?php page_end();
