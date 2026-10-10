<?php
/* V120: OTP-verified visitor consent only. No visitor name/email collection. */
ob_start();
$signup_json_sent = false;
function signup_json(string $message, int $status = 200, array $extra = []): void {
    global $signup_json_sent; $signup_json_sent = true;
    while (ob_get_level() > 0) { @ob_end_clean(); }
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode(array_merge(['ok'=>$status>=200 && $status<300,'message'=>$message],$extra),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit;
}
function signup_error(string $message,int $status): void { signup_json($message,$status); }
register_shutdown_function(function() use (&$signup_json_sent){$err=error_get_last();if(!$signup_json_sent&&$err&&in_array($err['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR],true)){while(ob_get_level()>0){@ob_end_clean();}error_log('V120 consent fatal: '.$err['message']);http_response_code(500);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'message'=>'Unable to save consent right now. Please try again.']);}});
try {
 require_once __DIR__.'/config.php';
 header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
 if($_SERVER['REQUEST_METHOD']!=='POST') signup_error('Invalid request.',405);
 $csrf=(string)($_POST['csrf_token']??''); if(!csrf_valid($csrf)) signup_error('Security validation failed. Please refresh the page.',403);
 if(empty($_SESSION['mobile_verified'])||empty($_SESSION['verified_mobile'])) signup_error('Please verify your mobile number first.',401);
 if((string)($_POST['terms_privacy']??'')!=='1') signup_error('Please accept the Terms & Conditions and Privacy Policy to continue.',422);
 $mobile=function_exists('sms_normalize_mobile')?sms_normalize_mobile($_SESSION['verified_mobile']):preg_replace('/\D+/','',(string)$_SESSION['verified_mobile']);
 if(!preg_match('/^91[6-9]\d{9}$/',$mobile)) signup_error('Verified mobile number is invalid. Please start again.',422);
 $columns=$pdo->query('SHOW COLUMNS FROM registrations')->fetchAll(PDO::FETCH_COLUMN,0);
 $required=['name'=>'VARCHAR(100) NULL','mobile'=>'VARCHAR(20) NULL','email'=>'VARCHAR(190) NULL','terms_accepted_at'=>'DATETIME NULL','privacy_accepted_at'=>'DATETIME NULL','verified_at'=>'DATETIME NULL','created_at'=>'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP','updated_at'=>'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'];
 foreach($required as $col=>$def){if(!in_array($col,$columns,true)){$pdo->exec("ALTER TABLE registrations ADD COLUMN `{$col}` {$def}");}}
 $now=date('Y-m-d H:i:s'); $st=$pdo->prepare('SELECT id,name,email FROM registrations WHERE mobile=? LIMIT 1'); $st->execute([$mobile]); $row=$st->fetch(PDO::FETCH_ASSOC); $name=$row['name']??null; $email=$row['email']??null;
 if($row){$up=$pdo->prepare('UPDATE registrations SET terms_accepted_at=?,privacy_accepted_at=?,verified_at=COALESCE(verified_at,?) WHERE id=?');$up->execute([$now,$now,$now,(int)$row['id']]);$id=(int)$row['id'];}
 else {$in=$pdo->prepare('INSERT INTO registrations(name,mobile,email,terms_accepted_at,privacy_accepted_at,verified_at) VALUES(?,?,?,?,?,?)');$in->execute(['',$mobile,'',$now,$now,$now]);$id=(int)$pdo->lastInsertId();}
 session_regenerate_id(true); $_SESSION['user_authenticated']=true; $_SESSION['user_mobile']=$mobile; $_SESSION['user_name']=$name??''; $_SESSION['user_email']=$email??''; $_SESSION['registration_id']=$id; unset($_SESSION['mobile_verified'],$_SESSION['verified_mobile'],$_SESSION['otp_verified']);
 signup_json('Consent saved. You can continue booking.',200,['mobile'=>$mobile]);
} catch(Throwable $e){error_log('V120 consent save exception: '.$e->getMessage());signup_error('Unable to save consent right now. Please try again.',500);}
