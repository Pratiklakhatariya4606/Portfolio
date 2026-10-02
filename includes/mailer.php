<?php
function cfg($k, $d = null) { static $c = null; if ($c === null) $c = require __DIR__ . '/config.local.php'; return $c[$k] ?? $d; }

// "Real" check: valid format, not a throwaway domain, and the domain has a mail server.
function email_deliverable($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $d = strtolower(substr(strrchr($email, '@'), 1));
    if (in_array($d, ['mailinator.com','tempmail.com','10minutemail.com','guerrillamail.com','yopmail.com','example.com','test.com'], true)) return false;
    return checkdnsrr($d, 'MX') || checkdnsrr($d, 'A');
}
// Returns +E164 number or null. 10-digit numbers are treated as Indian when country is India.
function normalize_phone($raw, $country = '') {
    $raw = trim($raw); $digits = preg_replace('/\D+/', '', $raw);
    if ($raw !== '' && $raw[0] === '+') $num = '+' . $digits;
    elseif (strncmp($raw, '00', 2) === 0) $num = '+' . substr($digits, 2);
    elseif (strlen($digits) === 10 && strtolower(trim($country)) === 'india') $num = '+91' . $digits;
    else return null;
    if (!preg_match('/^\+[1-9]\d{7,14}$/', $num)) return null;
    if (strncmp($num, '+91', 3) === 0 && !preg_match('/^\+91[6-9]\d{9}$/', $num)) return null;
    return $num;
}
// Minimal SMTP client (Gmail, STARTTLS). Returns true or an error string.
function send_mail($to, $subject, $body, $replyTo = null) {
    $user = cfg('smtp_user'); $pass = str_replace(' ', '', (string)cfg('smtp_pass')); $host = cfg('smtp_host', 'smtp.gmail.com'); $port = (int)cfg('smtp_port', 587);
    if (!$user || !$pass || strpos($pass, 'PASTE') !== false) return 'SMTP not set up (edit includes/config.local.php)';
    $clean = fn($s) => str_replace(["\r", "\n"], '', (string)$s);
    $to = $clean($to); $replyTo = $replyTo ? $clean($replyTo) : null;
    $verify = (bool)cfg('verify_tls', true);
    $ssl = ['verify_peer' => $verify, 'verify_peer_name' => $verify];
    $ca = 'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt'; if ($verify && is_file($ca)) $ssl['cafile'] = $ca;
    $fp = @stream_socket_client("tcp://$host:$port", $en, $es, 15, STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => $ssl]));
    if (!$fp) return "Cannot connect to $host:$port ($es)";
    stream_set_timeout($fp, 15);
    $rd = function () use ($fp) { $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
    $cmd = function ($c, $ok) use ($fp, $rd) { if ($c !== null) fwrite($fp, $c . "\r\n"); $r = $rd(); return strncmp($r, $ok, 3) === 0 ? true : 'SMTP: ' . trim(substr($r, 0, 160)); };
    $fail = function ($r) use ($fp) { fclose($fp); return $r; };
    if (($r = $cmd(null, '220')) !== true) return $fail($r);
    if (($r = $cmd('EHLO localhost', '250')) !== true) return $fail($r);
    if (($r = $cmd('STARTTLS', '220')) !== true) return $fail($r);
    if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return $fail('TLS failed (try verify_tls => false in config.local.php)');
    foreach ([['EHLO localhost', '250'], ['AUTH LOGIN', '334'], [base64_encode($user), '334'], [base64_encode($pass), '235'],
              ["MAIL FROM:<$user>", '250'], ["RCPT TO:<$to>", '250'], ['DATA', '354']] as [$c, $ok])
        if (($r = $cmd($c, $ok)) !== true) return $fail($r);
    $h = 'Date: ' . date('r') . "\r\nFrom: =?UTF-8?B?" . base64_encode(cfg('from_name', 'Portfolio')) . "?= <$user>\r\nTo: <$to>\r\n"
       . ($replyTo ? "Reply-To: <$replyTo>\r\n" : '') . 'Message-ID: <' . bin2hex(random_bytes(8)) . "@portfolio.local>\r\n"
       . 'Subject: =?UTF-8?B?' . base64_encode($clean($subject)) . "?=\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
       . chunk_split(base64_encode($body));
    fwrite($fp, $h . "\r\n.\r\n");
    $r = $rd(); fwrite($fp, "QUIT\r\n"); fclose($fp);
    return strncmp($r, '250', 3) === 0 ? true : 'SMTP: ' . trim(substr($r, 0, 160));
}
// Real SMS via Twilio. Returns true or an error string.
function sms_send($to, $body) {
    $sid = cfg('twilio_sid'); $tok = cfg('twilio_token'); $from = cfg('twilio_from');
    if (!$sid || !$tok || strpos($sid, 'PASTE') !== false) return 'SMS not set up (Twilio keys missing in config.local.php)';
    if (!function_exists('curl_init')) return 'PHP cURL extension is disabled';
    $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
    $o = [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['To' => $to, 'From' => $from, 'Body' => mb_substr($body, 0, 300)]),
          CURLOPT_USERPWD => "$sid:$tok", CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15];
    $ca = 'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt'; if (is_file($ca)) $o[CURLOPT_CAINFO] = $ca;
    curl_setopt_array($ch, $o);
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return ($code >= 200 && $code < 300) ? true : ($err ?: 'Twilio ' . $code . ': ' . substr((string)$r, 0, 150));
}
function notify($pdo, $mid, $channel, $to, $subject, $fn) {
    $r = $fn(); $ok = $r === true;
    $pdo->prepare("INSERT INTO notification_log (message_id,channel,to_addr,subject,status,error) VALUES (?,?,?,?,?,?)")
        ->execute([$mid, $channel, $to, mb_substr($subject, 0, 150), $ok ? 'sent' : 'failed', $ok ? null : mb_substr((string)$r, 0, 250)]);
    return $ok;
}
function note_skip($pdo, $mid, $channel, $to, $why) {
    $pdo->prepare("INSERT INTO notification_log (message_id,channel,to_addr,subject,status,error) VALUES (?,?,?,?, 'skipped', ?)")->execute([$mid, $channel, $to, 'not sent', $why]);
}
// Runs after a visitor sends a message: forward to owner + auto-reply to visitor.
function notify_new_message($pdo, $mid, $f) {
    @set_time_limit(60);
    $owner = cfg('owner_email'); $on = cfg('owner_name', 'Pratik');
    $mailOk = email_deliverable($f['email']); $phone = normalize_phone($f['mobile'], $f['country']);
    $full = trim($f['first_name'] . ' ' . $f['surname']);
    if ($mailOk || $phone) {
        $s = "New portfolio message from $full";
        $b = "Name: $full\nField / Year: {$f['field']} / {$f['study_year']}\nLocation: {$f['city']}, {$f['country']}\nEmail: {$f['email']}\nMobile: {$f['mobile']}\n\nMessage:\n{$f['message']}\n\nReply from your inbox on the portfolio site.";
        notify($pdo, $mid, 'email', $owner, $s, fn() => send_mail($owner, $s, $b, $mailOk ? $f['email'] : null));
    } else note_skip($pdo, $mid, 'email', $owner, 'Visitor email/phone do not look real, so not forwarded');
    if ($mailOk) {
        $s = "Thanks for visiting $on's portfolio";
        $b = "Hi {$f['first_name']},\n\nThanks for visiting $on's portfolio and for your message. $on will reply to you soon.\n\n- $on's Portfolio";
        notify($pdo, $mid, 'email', $f['email'], $s, fn() => send_mail($f['email'], $s, $b, $owner));
    } else note_skip($pdo, $mid, 'email', $f['email'], 'Email domain has no mail server; auto-reply skipped');
    if ($phone && cfg('auto_sms', false))
        notify($pdo, $mid, 'sms', $phone, 'auto reply', fn() => sms_send($phone, "Thanks for visiting $on's portfolio. $on will reply to you soon."));
}
