<?php
/* Secrets are NOT stored in this file. Set DB_HOST, DB_NAME, DB_USER, DB_PASS, INFOBIP_API_KEY and APP_OTP_SECRET
 * as server environment variables (see config.sample.php and README.md). */

$dbHost = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Database is not configured. Please enter your exact Hostinger MySQL credentials in config.php.');
}

/* Database schema migrations are intentionally NOT executed during normal web requests.
 * Run the one-time protected admin/migrate.php maintenance workflow when a schema
 * migration is explicitly required. This keeps production requests from needing
 * database ALTER/CREATE privileges.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly'=>true,
        'secure'=>$secureCookie,
        'samesite'=>'Lax',
        'path'=>'/'
    ]);
    session_start();
}

/* V102.6 security headers. CSP allows the existing inline UI scripts/styles and Google Maps frames. */
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob: https:; font-src 'self' data: https:; style-src 'self'; script-src 'self'; connect-src 'self' https:; frame-src 'self' https://www.google.com https://www.google.co.in https://maps.google.com;");
}

/* V102.6.4: Simple secure admin password store.
 * The active bcrypt hash lives in admin/.admin_password, not in config.php
 * and never in plain text. The file is blocked from direct web access by
 * admin/.htaccess. This avoids runtime config.php rewrites and OPcache issues.
 */
function admin_password_file(): string {
    return __DIR__ . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . '.admin_password';
}
function admin_password_uses_environment(): bool {
    $v = getenv('ADMIN_PASSWORD_HASH');
    return is_string($v) && $v !== '';
}
function admin_get_password_hash(): string {
    if (admin_password_uses_environment()) return (string)getenv('ADMIN_PASSWORD_HASH');
    $file = admin_password_file();
    if (is_file($file)) {
        $hash = trim((string)@file_get_contents($file));
        if ($hash !== '' && password_get_info($hash)['algo'] !== 0) return $hash;
    }
    throw new RuntimeException('Admin password is not configured. Run the protected password setup.');
}
function admin_set_password_hash(string $password): void {
    if (admin_password_uses_environment()) throw new RuntimeException('ADMIN_PASSWORD_HASH environment variable is active.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) throw new RuntimeException('Unable to hash the password.');
    $file = admin_password_file();
    $bytes = @file_put_contents($file, $hash . "\n", LOCK_EX);
    if ($bytes === false) throw new RuntimeException('Unable to save the admin password file. Make sure the admin directory is writable by PHP.');
    @chmod($file, 0600);
}




define('SMS_PROVIDER', 'infobip');
define('INFOBIP_API_KEY', getenv('INFOBIP_API_KEY') ?: '');
define('INFOBIP_SMS_ENDPOINT', getenv('INFOBIP_SMS_ENDPOINT') ?: 'https://6jm24z.api.infobip.com/sms/3/messages');
define('INFOBIP_SENDER', 'COKETH');
define('INFOBIP_TEMPLATE_ID', '1177179084139887689');
define('INFOBIP_PRINCIPAL_ENTITY_ID', '1101393580000056521');
define('APP_OTP_SECRET', getenv('APP_OTP_SECRET') ?: '');
define('OTP_TTL_SECONDS', 300);
define('OTP_RESEND_SECONDS', 30);
define('OTP_MAX_ATTEMPTS', 5);
define('OTP_MAX_REQUESTS_PER_HOUR', 5);

define('OTP_REQUIRED_COLUMNS', [
    'id','mobile','otp_hash','ip_address','attempts','verified','expires_at','created_at',
    'verified_at','infobip_message_id','infobip_status','infobip_status_description',
    'infobip_sent_at','infobip_delivered_at','infobip_error'
]);

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function otp_schema_status(PDO $pdo): array {
    try {
        $table = $pdo->query("SHOW TABLES LIKE 'otp_requests'")->fetchColumn();
        if (!$table) return ['ok'=>false,'missing_table'=>true,'missing_columns'=>OTP_REQUIRED_COLUMNS];
        $rows = $pdo->query('SHOW COLUMNS FROM otp_requests')->fetchAll(PDO::FETCH_COLUMN, 0);
        $missing = array_values(array_diff(OTP_REQUIRED_COLUMNS, $rows));
        return ['ok'=>empty($missing),'missing_table'=>false,'missing_columns'=>$missing];
    } catch (Throwable $e) {
        return ['ok'=>false,'missing_table'=>false,'missing_columns'=>OTP_REQUIRED_COLUMNS,'error'=>$e->getMessage()];
    }
}


header_remove('X-Powered-By');

/* CSRF protection with a fresh token on every page. Each token is a random nonce plus an HMAC of it keyed with a
 * secret kept in the session, so tokens differ per request but all stay valid for the session (other tabs keep working). */
function csrf_key(): string {
    if (empty($_SESSION['csrf_key']) || !is_string($_SESSION['csrf_key'])) $_SESSION['csrf_key'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_key'];
}
function csrf_token(): string {
    $nonce = bin2hex(random_bytes(16));
    return $nonce . '.' . hash_hmac('sha256', $nonce, csrf_key());
}
function csrf_valid(string $token): bool {
    if (!preg_match('/^([a-f0-9]{32})\.([a-f0-9]{64})$/', $token, $m) || empty($_SESSION['csrf_key'])) return false;
    return hash_equals(hash_hmac('sha256', $m[1], (string)$_SESSION['csrf_key']), $m[2]);
}
