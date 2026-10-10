<?php
require '../config.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if (empty($_SESSION['admin'])) { header('Location:login.php'); exit; }
if (empty($_SESSION['admin_csrf_token'])) {
  $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$adminCsrfToken = $_SESSION['admin_csrf_token'];
$pdo->exec("CREATE TABLE IF NOT EXISTS registrations (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, mobile VARCHAR(20) NULL, whatsapp VARCHAR(30) NULL, verified_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $postedCsrf = (string)($_POST['csrf_token'] ?? '');
  if ($postedCsrf === '' || !hash_equals($adminCsrfToken, $postedCsrf)) {
    http_response_code(403);
    exit('Security validation failed. Please refresh the admin page and try again.');
  }
  if (($_POST['action']??'')==='delete') {
  $id=(int)($_POST['id']??0); $pdo->prepare('DELETE FROM registrations WHERE id=?')->execute([$id]);
  header('Location:registrations.php?deleted=1'); exit;
  }
}
$q=trim($_GET['q']??'');
$sql="SELECT r.*, COUNT(DISTINCT b.id) booking_count, MAX(b.created_at) last_booking FROM registrations r LEFT JOIN bookings b ON REPLACE(REPLACE(b.mobile,' ',''),'+','')=REPLACE(REPLACE(r.mobile,' ',''),'+','')";
$args=[];
if($q!==''){ $sql.=" WHERE r.name LIKE ? OR r.mobile LIKE ?"; $args=['%'.$q.'%','%'.$q.'%']; }
$sql.=" GROUP BY r.id ORDER BY r.created_at DESC LIMIT 500";
$st=$pdo->prepare($sql); $st->execute($args); $rows=$st->fetchAll(PDO::FETCH_ASSOC);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Registration Data</title><link rel="stylesheet" href="../assets/admin-registrations.css?v=1"><link rel="stylesheet" href="../assets/style-modern-v3.css?v=4.0.0"><script src="../assets/admin-confirm.js?v=1" defer></script></head><body><header class="top"><div><b>REGISTRATION DATA</b><small>Name & SMS-verified mobile visitor records</small></div><div class="lm-registrations-1"><a href="index.php">← Admin Dashboard</a><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?=h($adminCsrfToken)?>"><button class="lm-registrations-2">Logout</button></form></div></header><main class="wrap"><section class="card"><div class="head"><div><h1>Registered Visitors</h1><p class="sub">Total records: <b><?php echo count($rows);?></b></p></div></div><?php if(isset($_GET['deleted'])):?><div class="notice">Registration record removed successfully.</div><?php endif;?><form class="search" method="get"><input name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8');?>" placeholder="Search name or mobile number"><button>Search</button><a class="lm-registrations-3" href="registrations.php">Clear</a></form><div class="table"><table><tr><th>#</th><th>Name</th><th>Mobile Number</th><th>Email</th><th>Registered On</th><th>Bookings</th><th>Last Booking</th><th>Action</th></tr><?php if(!$rows):?><tr><td colspan="8" class="empty">No registration records found yet.</td></tr><?php else: foreach($rows as $r): $mobile=preg_replace('/\D/','',$r['mobile']);?><tr><td><?php echo $r['id'];?></td><td><b><?php echo h($r['name']);?></b></td><td>+<?php echo h($mobile);?></td><td><?php echo h($r['email']??'—');?></td><td><?php echo h($r['created_at']);?></td><td><?php echo (int)$r['booking_count'];?></td><td><?php echo h($r['last_booking']?:'—');?></td><td><form method="post" data-confirm="Remove this registration record?"><input type="hidden" name="csrf_token" value="<?php echo h($adminCsrfToken);?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $r['id'];?>"><button class="del">Remove</button></form></td></tr><?php endforeach; endif;?></table></div></section></main></body></html>