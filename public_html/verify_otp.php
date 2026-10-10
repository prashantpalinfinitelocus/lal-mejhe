<?php
require_once __DIR__ . '/sms.php';
header('Content-Type: application/json; charset=utf-8');
function otp_verify_error(string $message, int $status): void { http_response_code($status); echo json_encode(['ok'=>false,'message'=>$message]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') otp_verify_error('Invalid request.',405);
$csrf=(string)($_POST['csrf_token']??'');
if(!csrf_valid($csrf)) otp_verify_error('Security validation failed. Please refresh the page.',403);
$mobile=sms_normalize_mobile($_POST['mobile']??($_SESSION['otp_mobile']??''));
$otp=preg_replace('/\D+/','',(string)($_POST['otp']??''));
if(!preg_match('/^91[6-9]\d{9}$/',$mobile)||!preg_match('/^\d{6}$/',$otp)) otp_verify_error('Enter the 6-digit OTP sent to your mobile.',422);
try{
  $id=(int)($_SESSION['otp_request_id']??0);
  $q=$pdo->prepare("SELECT id,mobile,otp_hash,attempts,verified,expires_at FROM otp_requests WHERE id=? AND mobile=? LIMIT 1");
  $q->execute([$id,$mobile]); $req=$q->fetch();
  if(!$req || (int)$req['verified']===1) otp_verify_error('Please request a new OTP.',400);
  if(!$req['expires_at'] || strtotime($req['expires_at'])<time()) otp_verify_error('OTP expired. Please request a new OTP.',400);
  if((int)$req['attempts']>=OTP_MAX_ATTEMPTS) otp_verify_error('Too many incorrect attempts. Please request a new OTP.',429);
  $pdo->prepare("UPDATE otp_requests SET attempts=attempts+1 WHERE id=?")->execute([$id]);
  if(!hash_equals((string)$req['otp_hash'],otp_hash($otp))){$remaining=max(0,OTP_MAX_ATTEMPTS-((int)$req['attempts']+1));otp_verify_error('Incorrect OTP. '.$remaining.' attempt'.($remaining===1?'':'s').' remaining.',422);}
  $pdo->prepare("UPDATE otp_requests SET verified=1,verified_at=NOW() WHERE id=?")->execute([$id]);
  session_regenerate_id(true);
  $_SESSION['mobile_verified']=true;
  $_SESSION['verified_mobile']=$mobile;
  $_SESSION['otp_verified']=true;
  unset($_SESSION['otp_mobile'],$_SESSION['otp_request_id']);
  $reg=$pdo->prepare('SELECT id,name,email,terms_accepted_at,privacy_accepted_at FROM registrations WHERE mobile=? LIMIT 1');
  $reg->execute([$mobile]); $user=$reg->fetch(PDO::FETCH_ASSOC);
  if($user && !empty($user['terms_accepted_at']) && !empty($user['privacy_accepted_at'])) {
    $_SESSION['user_authenticated']=true; $_SESSION['user_mobile']=$mobile;
    $_SESSION['user_name']=$user['name']??''; $_SESSION['user_email']=$user['email']??''; $_SESSION['registration_id']=(int)$user['id'];
    unset($_SESSION['mobile_verified'],$_SESSION['verified_mobile'],$_SESSION['otp_verified']);
    // nosemgrep: php.lang.security.injection.echoed-request.echoed-request (JSON response, Content-Type is application/json, values are json_encode'd)
    echo json_encode(['ok'=>true,'returning_user'=>true,'name'=>$_SESSION['user_name'],'mobile'=>$mobile,'message'=>'Mobile number verified successfully.']); exit;
  }
  // nosemgrep: php.lang.security.injection.echoed-request.echoed-request (JSON response, Content-Type is application/json, values are json_encode'd)
  echo json_encode(['ok'=>true,'returning_user'=>false,'mobile'=>$mobile,'message'=>'Mobile number verified. Please accept the Terms & Conditions and Privacy Policy.']);
}catch(Throwable $e){error_log('OTP verification exception: '.$e->getMessage());otp_verify_error('Unable to verify OTP right now.',500);}
