<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
include 'header.php';
$slug=$_GET['slug']??'';
$st=$pdo->prepare("SELECT * FROM locations WHERE slug=? AND active=1");$st->execute([$slug]);$loc=$st->fetch(PDO::FETCH_ASSOC);if(!$loc)die('Location not found');
$slots=$pdo->prepare("SELECT * FROM slots WHERE location_id=? AND active=1 AND slot_date>=CURDATE() ORDER BY slot_date, STR_TO_DATE(SUBSTRING_INDEX(slot_time, ' - ', 1), '%h:%i %p')");$slots->execute([$loc['id']]);$slots=$slots->fetchAll(PDO::FETCH_ASSOC);
$banner=$loc['banner']?:'assets/kolkata-hero.png';
$mapSrc=$loc['map_embed']?:'https://www.google.com/maps?q='.urlencode($loc['name'].', Kolkata').'&z=15&output=embed';
$dir=$loc['direction_url']?:'https://www.google.com/maps/dir/?api=1&destination='.urlencode($loc['name'].', Kolkata');
?>
<link rel="stylesheet" href="/assets/location-page-1.css?v=1">
<link rel="stylesheet" href="/assets/location-page-2.css?v=1">

<link rel="stylesheet" href="/assets/location-page-3.css?v=1">

<section class="kx-location-hero">
  <img class="kx-hero-image" src="<?php echo htmlspecialchars($banner); ?>" alt="<?php echo htmlspecialchars($loc['name']); ?>">
  <div class="kx-location-hero-inner">
    <div class="kx-location-brand" aria-label="Coca-Cola">
      <img src="/assets/coca-cola-white.png?v=34" width="220" height="72" alt="Coca-Cola" loading="eager" decoding="async">
      <p class="kx-location-brand-note">Experience Lal Mejhe This Durga Puja*</p>
    </div>
    <span class="kx-location-kicker">Laal Mejhe Adda</span>
    <h1><?php echo htmlspecialchars($loc['name']); ?></h1>
    <p><?php echo htmlspecialchars($loc['description'] ?: 'Discover an unforgettable Kolkata experience at this iconic destination.'); ?></p>
    <div class="kx-location-meta">
      <span>📍 <?php echo htmlspecialchars($loc['address'] ?: ($loc['name'].', Kolkata')); ?></span>
      <span>⏱ 1-Hour Experience</span>
      <span>🎟 Select Your Seats</span>
    </div>
  </div>
</section>

<section class="kx-location-mapbar">
  <div class="kx-map-layout">
    <div class="kx-map-copy">
      <span class="mini">Plan Your Visit</span>
      <h2><?php echo htmlspecialchars($loc['name']); ?><br>Map & Direction</h2>
      <p>Find the experience location easily and get directions directly from Google Maps.</p>
      <div class="kx-address">📍 <?php echo htmlspecialchars($loc['address'] ?: ($loc['name'].', Kolkata')); ?></div>
      <a class="kx-direction" href="<?php echo htmlspecialchars($dir); ?>" target="_blank" rel="noopener">GET GOOGLE MAP DIRECTIONS →</a>
    </div>
    <div class="kx-map-frame">
      <iframe src="<?php echo htmlspecialchars($mapSrc); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?php echo htmlspecialchars($loc['name']); ?> Google Map"></iframe>
    </div>
  </div>
</section>

<section class="location-booking-v10">
<div class="lb-wrap">
<div class="lb-head"><span class="lb-kicker">Reserve Your Visit</span><h2>Choose Your Experience</h2><p>Select a date first, then choose your 1-hour time slot and seats.</p></div>
<?php if(!$slots): ?><div class="lb-summary">No booking slots are available yet. Please check back soon.</div><?php else: ?>
<form method="post" action="book.php" id="bookingForm">
<input type="hidden" name="location_id" value="<?php echo (int)$loc['id']; ?>">
<input type="hidden" name="guest_name" id="guest_name"><input type="hidden" name="guest_mobile" id="guest_mobile"><input type="hidden" name="seats" id="seatsInput"><input type="hidden" name="slot_date" id="slotDateInput">
<div class="lb-flow">
<div class="lb-step active"><div class="lb-step-top"><div class="lb-num">01</div><div><div class="lb-title">Select Date</div><div class="lb-sub">Choose your preferred visit date</div></div></div>
<div class="lb-date-grid" id="dateButtons">
<?php $dates=[];foreach($slots as $s){$d=$s['slot_date'];if(!isset($dates[$d]))$dates[$d]=true;}foreach(array_keys($dates) as $d): ?><button type="button" class="lb-date" data-date="<?php echo e($d); ?>"><span class="dow"><?php echo e(date('D',strtotime($d))); ?></span><span class="day"><?php echo e(date('d',strtotime($d))); ?></span><span class="mon"><?php echo e(date('M',strtotime($d))); ?></span></button><?php endforeach; ?>
</div></div>
<div class="lb-step" id="slotStep"><div class="lb-step-top"><div class="lb-num">02</div><div><div class="lb-title">Select 1-Hour Slot</div><div class="lb-sub">Choose a time between 11 AM and 10 PM</div></div></div>
<div class="lb-select-wrap"><span class="lb-clock">◷</span><select class="lb-select" id="slotSelect" name="slot_id" required disabled><option value="">Select date first</option></select><span class="lb-arrow">⌄</span></div></div>
</div>
<div class="lb-summary" id="selectionSummary">Select a date to see available time slots.</div>
<div class="lb-seats"><div class="lb-seat-head"><h3>03. Select Seats <small id="seatMaxLabel">Choose a slot first</small></h3><div class="lb-legend"><i></i>Available <i class="sel"></i>Selected <i class="book"></i>Booked</div></div><div class="lb-screen">EXPERIENCE AREA</div><div class="lb-seat-grid" id="seatGrid"></div><p class="lb-seat-note" id="seatInfo">Select a date and slot to see available seats.</p><button class="lb-submit" type="submit">CONFIRM MY BOOKING →</button></div>
</form>
<?php endif; ?></div></section>
<script type="application/json" id="lm-slots"><?php echo json_encode(array_map(function($s){return ['id'=>(int)$s['id'],'date'=>$s['slot_date'],'time'=>$s['slot_time'],'max'=>(int)$s['max_seats']];},$slots),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
<script src="/assets/location-page.js?v=1" defer></script>
<?php include 'footer.php'; ?>
