<?php
require '../config.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$enableFile=__DIR__.'/setup.enable';
$lockFile=__DIR__.'/setup.lock';
if (!is_file($enableFile) || is_file($lockFile) || admin_password_uses_environment()) { http_response_code(404); exit('Setup is disabled.'); }
$csrf=csrf_token(); $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_valid((string)($_POST['csrf_token']??''))) $error='Security validation failed. Please refresh and try again.';
    else {
        $new=(string)($_POST['new_password']??''); $confirm=(string)($_POST['confirm_password']??'');
        if (strlen($new)<8) $error='Password must be at least 8 characters.';
        elseif ($new!==$confirm) $error='Password and confirmation do not match.';
        else {
            try {
                admin_set_password_hash($new);
                @file_put_contents($lockFile, date('c')."\n", LOCK_EX);
                @unlink($enableFile);
                session_regenerate_id(true);
                $_SESSION['admin']=1;
                $_SESSION['admin_csrf_token']=bin2hex(random_bytes(32));
                header('Location:index.php?password_set=1'); exit;
            } catch (Throwable $e) {
                error_log('Admin initial password setup error: '.$e->getMessage());
                $error='Unable to save the password. Make sure the admin folder is writable by PHP.';
            }
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Set Admin Password | Laal Mejhe Adda</title><link rel="stylesheet" href="../assets/style-modern-v3.css?v=4.0.0"></head><body>
<section class="admin-login"><form method="post" autocomplete="off"><span class="eyebrow">LAAL MEJHE ADDA</span><h2>Set Admin Password</h2><p>Create your admin password. This setup page locks itself after a successful setup.</p><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="password" name="new_password" placeholder="New Admin Password (8+ characters)" required minlength="8" autocomplete="new-password"><input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8" autocomplete="new-password"><button class="btn" type="submit">SET PASSWORD</button><small>Password is stored only as a secure bcrypt hash in a web-blocked server file.</small></form></section>
<link rel="stylesheet" href="../assets/admin-setup.css?v=1"></body></html>
