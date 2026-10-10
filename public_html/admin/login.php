<?php
require_once __DIR__ . '/../config.php';
if (!empty($_SESSION['admin'])) { header('Location:index.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_valid((string)($_POST['csrf_token']??''))) {
        $error='Security validation failed. Please refresh and try again.';
    } else {
        $ip=substr((string)($_SERVER['REMOTE_ADDR']??''),0,45);
        try {
            $st=$pdo->prepare('SELECT failed_attempts,locked_until FROM admin_login_attempts WHERE ip_address=? LIMIT 1');
            $st->execute([$ip]); $row=$st->fetch();
            if ($row && !empty($row['locked_until']) && strtotime($row['locked_until'])>time()) {
                $error='Too many failed login attempts. Please try again later.';
            } else {
                $password=(string)($_POST['password']??'');
                if (password_verify($password, admin_get_password_hash())) {
                    $pdo->prepare('DELETE FROM admin_login_attempts WHERE ip_address=?')->execute([$ip]);
                    session_regenerate_id(true);
                    $_SESSION['admin']=1;
                    $_SESSION['admin_csrf_token']=bin2hex(random_bytes(32));
                    header('Location:index.php'); exit;
                }
                $attempts=(int)($row['failed_attempts']??0)+1;
                $lockedUntil=$attempts>=5 ? date('Y-m-d H:i:s',time()+900) : null;
                $pdo->prepare("INSERT INTO admin_login_attempts(ip_address,failed_attempts,first_failed_at,locked_until,last_attempt_at) VALUES(?,?,NOW(),?,NOW()) ON DUPLICATE KEY UPDATE failed_attempts=?, locked_until=?, last_attempt_at=NOW()")
                    ->execute([$ip,$attempts,$lockedUntil,$attempts,$lockedUntil]);
                $error=$attempts>=5?'Too many failed login attempts. Please try again in 15 minutes.':'Invalid admin password.';
            }
        } catch(Throwable $e) { error_log('Admin login error: '.$e->getMessage()); $error='Unable to process login right now.'; }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Admin Login | Laal Mejhe Adda</title><link rel="stylesheet" href="../assets/style-modern-v3.css?v=4.0.0"></head><body><section class="admin-login"><form method="post" autocomplete="off"><span class="eyebrow">LAAL MEJHE ADDA</span><h2>Admin Login</h2><p>Manage locations, seats, slots, bookings and registrations.</p><?php if($error):?><div class="login-error"><?=e($error)?></div><?php endif;?><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="password" name="password" placeholder="Admin Password" required autocomplete="current-password"><button class="btn" type="submit">LOGIN →</button><small>Admin access is protected by password hashing, CSRF validation and login throttling.</small></form></section><link rel="stylesheet" href="../assets/admin-login.css?v=1"></body></html>