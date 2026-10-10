<?php require_once __DIR__.'/config.php'; if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } ?>
<!doctype html>
<html lang="en" class="<?php echo in_array(basename($_SERVER['SCRIPT_NAME']), ['terms.php','privacy.php'], true) ? 'legal-scroll-page' : ''; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#e41e2b">
<meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
<link rel="stylesheet" href="/assets/page-inline-fix.css?v=1">
<title>Lal Mejhe Adda | Book Your Slot</title>
<link rel="stylesheet" href="/assets/style-modern-v3.css?v=100.0.0">
<link rel="stylesheet" href="/assets/campaign.css?v=100.0.0">
<link rel="stylesheet" href="/assets/single-page-v82.css?v=100.0.0">
<link rel="stylesheet" href="/assets/page-rebuild-v95.css?v=125.0.0">
<?php if (basename($_SERVER['SCRIPT_NAME']) !== 'index.php'): ?><script src="assets/app.js" defer></script><?php endif; ?>
</head>
<body class="<?php echo in_array(basename($_SERVER['SCRIPT_NAME']), ['terms.php','privacy.php'], true) ? 'legal-scroll-page terms-scroll-page' : (basename($_SERVER['SCRIPT_NAME'])==='location.php' ? 'page-location' : (basename($_SERVER['SCRIPT_NAME'])==='index.php' ? 'page-index-v94' : '')); ?>">
<header class="site-header-v94 global-header-v94">
  <a class="brandLogo" href="/index.php" aria-label="Lal Mejhe Adda"><picture><source srcset="/assets/logo.svg?v=100" type="image/svg+xml"><img src="/assets/logo.png?v=100" alt="Lal Mejhe Adda"></picture></a>
<?php if (!empty($_SESSION['user_authenticated'])): ?><form class="user-logout-form" method="post" action="/logout.php"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']??''); ?>"><button type="submit">LOGOUT</button></form><?php endif; ?>
</header>
