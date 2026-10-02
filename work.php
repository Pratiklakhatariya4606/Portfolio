<?php require 'includes/layout.php'; track($pdo, 'work');
$admin = role() === 'admin';
$all = $pdo->query("SELECT id,name,icon_class FROM skills ORDER BY name")->fetchAll();
function slugify($t) { $s = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($t)), '-'); return $s ?: 'project'; }
function clean_url($u) { $u = trim($u); return ($u !== '' && filter_var($u, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $u)) ? $u : null; }
function project_form($act, $all, $p = [], $sel = []) {
    $v = fn($k) => e($p[$k] ?? '');
    echo '<form method="post" class="form"><input type="hidden" name="csrf" value="' . e(csrf()) . '"><input type="hidden" name="act" value="' . $act . '">';
    if (!empty($p['id'])) echo '<input type="hidden" name="id" value="' . (int)$p['id'] . '">';
    echo '<label>Title<input name="title" maxlength="150" required value="' . $v('title') . '"></label>';
    echo '<label>Short summary<input name="summary" maxlength="300" required value="' . $v('summary') . '"></label>';
    echo '<label>Description<textarea name="description" rows="4">' . $v('description') . '</textarea></label>';
    echo '<div class="two"><label>Live URL<input type="url" name="live_url" placeholder="https://..." value="' . $v('live_url') . '"></label><label>GitHub URL<input type="url" name="github_url" placeholder="https://github.com/..." value="' . $v('github_url') . '"></label></div>';
    echo '<label>Status<select name="status">';
    foreach (['completed' => 'Completed', 'in_progress' => 'In progress', 'planned' => 'Planned'] as $k => $l)
        echo '<option value="' . $k . '"' . (($p['status'] ?? 'completed') === $k ? ' selected' : '') . '>' . $l . '</option>';
    echo '</select></label><div class="chips">';
    foreach ($all as $s) echo '<label class="chk"><input type="checkbox" name="skills[]" value="' . (int)$s['id'] . '"' . (in_array($s['id'], $sel) ? ' checked' : '') . '> ' . e($s['name']) . '</label>';
    echo '</div><div class="row" style="margin:0"><label class="chk"><input type="checkbox" name="is_featured" value="1"' . (!empty($p['is_featured']) ? ' checked' : '') . '> Show on home page</label>';
    echo '<label class="chk"><input type="checkbox" name="is_published" value="1"' . (!isset($p['is_published']) || $p['is_published'] ? ' checked' : '') . '> Published</label></div>';
    echo '<button class="btn sm">' . ($act === 'add' ? 'Add project' : 'Save changes') . '</button></form>';
}
$err = '';
if ($admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if (!csrf_ok($_POST['csrf'] ?? '')) $err = 'Session expired. Reload and try again.';
    elseif ($act === 'delete' && $id) { $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]); header('Location: work.php?deleted=1'); exit; }
    elseif ($act === 'add' || $act === 'edit') {
        $title = mb_substr(trim($_POST['title'] ?? ''), 0, 150); $sum = mb_substr(trim($_POST['summary'] ?? ''), 0, 300);
        $desc = trim($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['planned', 'in_progress', 'completed'], true) ? $_POST['status'] : 'completed';
        $live = clean_url($_POST['live_url'] ?? ''); $gh = clean_url($_POST['github_url'] ?? '');
        $feat = empty($_POST['is_featured']) ? 0 : 1; $pub = empty($_POST['is_published']) ? 0 : 1;
        if ($title === '' || $sum === '') $err = 'Title and summary are required.';
        else {
            if ($act === 'add') {
                $base = slugify($title); $slug = $base; $n = 2; $chk = $pdo->prepare("SELECT 1 FROM projects WHERE slug=?");
                for (;;) { $chk->execute([$slug]); if (!$chk->fetch()) break; $slug = $base . '-' . $n++; }
                $pdo->prepare("INSERT INTO projects (title,slug,summary,description,live_url,github_url,status,is_featured,is_published,completed_on) VALUES (?,?,?,?,?,?,?,?,?,IF(?='completed',CURDATE(),NULL))")
                    ->execute([$title, $slug, $sum, $desc, $live, $gh, $status, $feat, $pub, $status]);
                $id = (int)$pdo->lastInsertId();
            } else {
                $pdo->prepare("UPDATE projects SET title=?,summary=?,description=?,live_url=?,github_url=?,status=?,is_featured=?,is_published=?,completed_on=IF(?='completed',COALESCE(completed_on,CURDATE()),NULL) WHERE id=?")
                    ->execute([$title, $sum, $desc, $live, $gh, $status, $feat, $pub, $status, $id]);
                $pdo->prepare("DELETE FROM project_skills WHERE project_id=?")->execute([$id]);
            }
            $ins = $pdo->prepare("INSERT IGNORE INTO project_skills (project_id,skill_id) SELECT ?, id FROM skills WHERE id=?");
            foreach (array_unique(array_map('intval', (array)($_POST['skills'] ?? []))) as $sid) $ins->execute([$id, $sid]);
            header('Location: work.php?saved=1'); exit;
        }
    }
}
$sel = []; foreach ($pdo->query("SELECT project_id, skill_id FROM project_skills") as $r) $sel[$r['project_id']][] = $r['skill_id'];
$rows = $pdo->query("SELECT p.*, GROUP_CONCAT(CONCAT(s.name,'|',COALESCE(s.icon_class,'')) ORDER BY s.name SEPARATOR ',') AS tech
  FROM projects p LEFT JOIN project_skills ps ON ps.project_id=p.id LEFT JOIN skills s ON s.id=ps.skill_id
  WHERE " . ($admin ? '1' : 'p.is_published=1') . " GROUP BY p.id ORDER BY p.id DESC")->fetchAll();
page_start('My Work', 'work'); ?>
<section class="wrap"><h1 class="sec">My work</h1>
<?php if (isset($_GET['saved'])) echo '<div class="alert good">Project saved.</div>'; if (isset($_GET['deleted'])) echo '<div class="alert good">Project deleted.</div>';
if ($err) echo '<div class="alert bad">' . e($err) . '</div>'; ?>
<?php if ($admin): ?><details class="card" style="margin-bottom:18px"><summary class="btn sm">&#65291; Add a project</summary><div style="margin-top:14px"><?php project_form('add', $all); ?></div></details><?php endif; ?>
<div class="grid"><?php foreach ($rows as $p): ?>
<div class="card proj"><h3><a href="project.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></h3>
<p class="muted"><?= e($p['summary']) ?></p>
<div class="chips"><?php foreach (array_filter(explode(',', (string)$p['tech'])) as $t) { $x = explode('|', $t, 2); echo tech_chip($x[0], $x[1] ?? ''); } ?></div>
<div class="row"><span class="badge"><?= e(str_replace('_', ' ', $p['status'])) ?></span><?php if (!$p['is_published']) echo '<span class="badge" style="color:var(--bad)">hidden</span>'; ?>
<?php if ($p['live_url']): ?><a href="<?= e($p['live_url']) ?>" target="_blank" rel="noopener">Live</a><?php endif; ?>
<?php if ($p['github_url']): ?><a href="<?= e($p['github_url']) ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?></div>
<?php if ($admin): ?><details style="margin-top:14px"><summary class="btn sm ghost">Edit</summary><div style="margin-top:12px"><?php project_form('edit', $all, $p, $sel[$p['id']] ?? []); ?></div></details>
<form method="post" onsubmit="return confirm('Delete this project?')" style="margin-top:10px"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn sm ghost">Delete</button></form><?php endif; ?></div>
<?php endforeach; if (!$rows) echo '<p class="muted">No projects yet.</p>'; ?></div></section>
<?php page_end();
