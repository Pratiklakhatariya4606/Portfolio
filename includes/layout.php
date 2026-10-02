<?php
require_once __DIR__ . '/init.php';

// Renders social media logo buttons from the social_links table.
function social_icons($pdo) {
    $svg = [
      'instagram' => '<svg viewBox="0 0 24 24" width="20" height="20"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/></svg>',
      'facebook'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M13.5 21v-7.5h2.5l.4-3h-2.9V8.7c0-.9.3-1.5 1.6-1.5h1.4V4.5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2.1H8v3h2.6V21z"/></svg>',
      'github'    => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
      'linkedin'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
    ];
    echo '<span class="socials">';
    foreach ($pdo->query("SELECT * FROM social_links WHERE is_active=1 ORDER BY sort_order, id")->fetchAll() as $s) {
        $key = strtolower($s['platform']);
        $key = str_contains($key, 'insta') ? 'instagram' : (str_contains($key, 'face') ? 'facebook' : (str_contains($key, 'git') ? 'github' : (str_contains($key, 'linked') ? 'linkedin' : '')));
        echo '<a class="soc s-' . ($key ?: 'other') . '" href="' . e($s['url']) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($s['platform']) . '" title="' . e($s['platform']) . '">'
           . ($key ? $svg[$key] : e(substr($s['platform'], 0, 2))) . '</a>';
    }
    echo '</span>';
}
function page_start($title, $page) {
    global $profile;
    $name = e($profile['first_name'] . ' ' . $profile['last_name']);
    $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    $url = rtrim($url, '/\\') . '/';
    $links = ['index'=>'Home','about'=>'About','work'=>'My Work','dashboard'=>'Dashboard'];
    if (role() === 'admin') $links['inbox'] = 'Inbox'; else { $links['contact'] = 'Contact'; $links['sentbox'] = 'Sent box'; }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . ' | ' . $name . '</title>';
    echo '<meta property="og:title" content="' . $name . ' - ' . e($profile['headline']) . '"><meta property="og:description" content="' . e($profile['short_intro']) . '">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/devicons/devicon@v2.15.1/devicon.min.css"><link rel="stylesheet" href="assets/css/style.css"></head><body data-share-url="' . e($url) . '" data-share-title="' . $name . ' - Portfolio" data-csrf="' . e(csrf()) . '">';
    echo '<header class="nav"><div class="wrap nav-in"><a class="logo" href="index.php">&lt;' . e($profile['first_name']) . ' /&gt;</a>';
    echo '<button class="burger" id="burger" aria-label="Menu">&#9776;</button><nav id="menu">';
    foreach ($links as $f => $l) echo '<a href="' . $f . '.php"' . ($page === $f ? ' class="on"' : '') . '>' . $l . '</a>';
    echo '</nav><button class="btn sm" id="shareBtn">Share &#10150;</button>';
    echo role() === 'admin' ? '<a class="btn sm ghost" href="exit.php">Exit</a>' : '<button class="btn sm ghost" id="exitBtn">Exit</button>';
    echo '</div></header><main>';
    if (!empty($_SESSION['welcome'])) { echo '<div class="toast" id="welcome">' . e($_SESSION['welcome']) . '</div>'; unset($_SESSION['welcome']); }
}
function page_end() {
    global $pdo, $profile;
    echo '</main><footer><div class="wrap foot"><span>&copy; ' . date('Y') . ' ' . e($profile['first_name'] . ' ' . $profile['last_name']) . ' &middot; Built with PHP &amp; MySQL</span><span>';
    social_icons($pdo);
    echo '</span></div></footer>';
    echo '<div class="modal" id="shareBox"><div class="card"><h3>Share my profile</h3><input id="shareLink" readonly>
    <div class="row"><button class="btn" id="copyBtn">Copy link</button><a class="btn ghost" id="wa" target="_blank">WhatsApp</a>
    <a class="btn ghost" id="li" target="_blank">LinkedIn</a><a class="btn ghost" id="tw" target="_blank">X</a><a class="btn ghost" id="ml">Email</a></div>
    <button class="close" id="closeShare">&times;</button></div></div>';
    if (role() !== 'admin') echo '<div class="modal" id="exitBox"><div class="card"><div id="st1"><h3>Before you go...</h3><p class="muted">Did you like my portfolio?</p>
    <div class="row"><button class="btn" data-rate="like">&#128077; Like</button><button class="btn ghost" data-rate="dislike">&#128078; Dislike</button></div></div>
    <div id="st2" hidden><h3>Any changes required?</h3><div class="row"><button class="btn" id="chYes">Yes</button><button class="btn ghost" id="chNo">No</button></div></div>
    <div id="st3" hidden><h3>What should I change?</h3><textarea id="chText" rows="4" maxlength="1000" placeholder="Write your suggestion..."></textarea><div class="row"><button class="btn" id="chSend">Submit &amp; exit</button></div></div>
    <button class="close" id="exitClose">&times;</button></div></div>
    <div class="bye" id="bye" hidden><div><h2 class="grad">Thanks for visiting!</h2><p class="muted">You can close this tab now.</p></div></div>';
    echo '<script src="assets/js/main.js"></script></body></html>';
}
