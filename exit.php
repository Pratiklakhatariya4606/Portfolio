<?php require 'includes/init.php';
if (role() === 'admin' || isset($_GET['logout'])) { session_destroy(); header('Location: gate.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!csrf_ok($_POST['csrf'] ?? '')) { http_response_code(400); echo '{"ok":false}'; exit; }
    $rating = ($_POST['rating'] ?? '') === 'dislike' ? 'dislike' : 'like';
    $wants = ($_POST['changes'] ?? '') === 'yes' ? 1 : 0;
    $c = $wants ? mb_substr(trim($_POST['comments'] ?? ''), 0, 1000) : null;
    $pdo->prepare("INSERT INTO feedback (visitor_id,rating,wants_changes,comments) VALUES (?,?,?,?)")->execute([$_SESSION['visitor_id'] ?? null, $rating, $wants, $c]);
    session_destroy(); echo '{"ok":true}'; exit;
}
header('Location: index.php');
