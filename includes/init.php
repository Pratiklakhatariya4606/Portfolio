<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=portfolio_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    exit('Database connection failed. Start MySQL in XAMPP and import the .sql files from the database folder.');
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function role() { return $_SESSION['role'] ?? null; }
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_ok($t) { return hash_equals($_SESSION['csrf'] ?? '', (string)$t); }
function tech_chip($name, $icon = '') { return '<span class="chip">' . ($icon ? '<i class="' . e($icon) . '"></i>' : '') . e($name) . '</span>'; }
// Every page except gate.php needs a verified viewer or admin.
if (!defined('NO_GATE') && !role()) { header('Location: gate.php'); exit; }
$profile = $pdo->query("SELECT * FROM profile LIMIT 1")->fetch() ?: [
    'first_name'=>'Pratik','last_name'=>'Lakhatariya','headline'=>'IT Student','short_intro'=>'','about_me'=>'',
    'email'=>'','phone'=>'','city'=>'','country'=>'','photo_path'=>'','resume_path'=>''];
function track($pdo, $page, $pid = null) {
    $pdo->prepare("INSERT INTO page_visits (page,project_id,session_id,ip_address,referrer,user_agent) VALUES (?,?,?,?,?,?)")
        ->execute([$page, $pid, session_id(), $_SERVER['REMOTE_ADDR'] ?? null,
                   substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
}
