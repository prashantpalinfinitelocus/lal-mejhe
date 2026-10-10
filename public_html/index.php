<?php
include 'header.php';
$locations = $pdo->query("SELECT * FROM locations WHERE active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$slotsStmt = $pdo->query("SELECT id,location_id,slot_date,slot_time,max_seats FROM slots WHERE active=1 AND slot_date>=CURDATE() ORDER BY location_id,slot_date,id");
$allSlots = $slotsStmt->fetchAll(PDO::FETCH_ASSOC);
$slotByLocation=[];
foreach($allSlots as $s){ $slotByLocation[$s['location_id']][]=$s; }
$locationData=[];
$hotspotPositions=[[42,61],[29,52],[45,57],[48,55],[38,68],[52,42],[59,64],[72,35],[44,24],[48,71],[44,53],[72,46],[49,45]];
function autoHotspotPosition($i){$cols=4;$row=(int)floor($i/$cols);$col=$i%$cols;$rows=max(1,(int)ceil(($i+1)/$cols));return [round(12+$col*(76/($cols-1)),2),round(12+$row*(76/max(1,$rows-1)),2)];}
foreach($locations as $i=>$l){
  $map=$l['map_embed'] ?: 'https://www.google.com/maps?q='.urlencode(($l['address']?:$l['name']).', Kolkata').'&z=15&output=embed';
  $dir=$l['direction_url'] ?: 'https://www.google.com/maps/dir/?api=1&destination='.urlencode(($l['address']?:$l['name']).', Kolkata');
  if(isset($l['hotspot_left']) && $l['hotspot_left']!==null && $l['hotspot_left']!=='') $hotLeft=(float)$l['hotspot_left'];
  elseif(isset($hotspotPositions[$i])) $hotLeft=(float)$hotspotPositions[$i][0];
  else $hotLeft=(float)autoHotspotPosition($i)[0];
  if(isset($l['hotspot_top']) && $l['hotspot_top']!==null && $l['hotspot_top']!=='') $hotTop=(float)$l['hotspot_top'];
  elseif(isset($hotspotPositions[$i])) $hotTop=(float)$hotspotPositions[$i][1];
  else $hotTop=(float)autoHotspotPosition($i)[1];
  $locationData[]=[
    'id'=>(int)$l['id'],'name'=>$l['name'],'slug'=>$l['slug'],'description'=>$l['description']?:'A memorable Laal Mejhe experience in Kolkata.',
    'address'=>$l['address']?:($l['name'].', Kolkata'),'map'=>$map,'direction'=>$dir,'seat_quantity'=>(int)($l['seat_quantity']??15),
    'hotspot_left'=>$hotLeft,'hotspot_top'=>$hotTop,
    'slots'=>$slotByLocation[$l['id']]??[]
  ];
}
$first=$locationData[0]??null;
function hs($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$bookingAccessCode = $_SESSION['last_booking_code'] ?? '';
?>
<div id="userModal" class="modal"><div class="modalbox sms-auth-box signup-auth-box">
  <div id="mobileStep">
    <span class="sms-eyebrow">SECURE LOGIN</span><h2>Enter Your Mobile Number</h2>
    <p>We’ll send a 6-digit OTP by SMS to verify your mobile number.</p>
    <div class="mobile-input-wrap"><span>+91</span><input id="guestMobile" placeholder="10-digit mobile number" inputmode="numeric" maxlength="10" autocomplete="tel-national"></div>
    <button type="button" id="sendOtpBtn">SEND OTP →</button>
  </div>
  <div id="otpVerifyStep" class="hidden">
    <span class="sms-eyebrow">VERIFY MOBILE</span><h2>Enter OTP</h2>
    <p id="otpMessage">Enter the 6-digit OTP sent to your mobile.</p>
    <input id="guestOtp" class="otp-input" placeholder="6-digit OTP" inputmode="numeric" maxlength="6" autocomplete="one-time-code">
    <button type="button" id="verifyOtpBtn">VERIFY OTP →</button>
    <button type="button" id="resendOtpBtn" class="otp-secondary">RESEND OTP</button>
    <button type="button" id="changeMobileBtn" class="otp-link-btn">← CHANGE MOBILE NUMBER</button>
  </div>
  <div id="signupStep" class="hidden">
    <span class="sms-eyebrow">ONE LAST STEP</span><h2>Accept Our Policies</h2>
    <div class="signup-mark" aria-hidden="true">✓</div>
    <p>Your mobile number is verified. Please accept the Terms &amp; Conditions and Privacy Policy to continue booking.</p>
    <div class="consent-check"><input type="checkbox" id="termsPrivacyConsent" aria-label="Accept Terms and Privacy Policy"><label for="termsPrivacyConsent">I agree to the <a href="terms.php" target="_blank" rel="noopener">Terms &amp; Conditions</a> and <a href="privacy.php" target="_blank" rel="noopener">Privacy Policy</a>.</label></div>
    <button type="button" id="signupSubmitBtn">CONTINUE</button>
  </div>
</div></div>
<main class="lal-page-v94" id="experiencePages" data-booking-access="0" data-server-booking-code="<?=hs($bookingAccessCode)?>" data-authenticated="<?=!empty($_SESSION['user_authenticated']) ? '1' : '0'?>" data-user-name="<?=hs($_SESSION['user_name']??'')?>" data-user-mobile="<?=hs($_SESSION['user_mobile']??'')?>" data-location-data="<?=hs(json_encode($locationData,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))?>">
  <article class="experience-page-v94" data-page="0" id="page-section-2">
    <?php include 'page-header.php'; ?>
    <div class="artwork-page-v94 artwork-s2-v94"><picture><source media="(max-width: 767px)" srcset="/assets/section2-mobile.jpg?v=121.0.0" data-fallback="/assets/section2-mobile.jpg"><img src="/assets/section2-desktop.jpg" data-fallback="/assets/section2.webp" alt="Lal Mejhe Section 2 artwork"></picture></div>
    <div class="floating-nav-v94"><button type="button" class="page-nav-v94 page-back-v94" data-prev disabled aria-label="Previous page">← <span>BACK</span></button><button type="button" class="page-nav-v94 page-next-v94" data-next aria-label="Next page"><span>NEXT</span> →</button></div>
    <?php include 'page-footer.php'; ?>
  </article>

  <article class="experience-page-v94" data-page="1" id="page-section-3">
    <?php include 'page-header.php'; ?>
    <div class="artwork-page-v94 artwork-s3-v94"><picture><source media="(max-width: 767px)" srcset="/assets/section3-mobile.jpg?v=121.0.0" data-fallback="/assets/section3-mobile.jpg"><img src="/assets/section3-desktop.jpg" data-fallback="/assets/section3.webp" alt="Lal Mejhe Section 3 artwork"></picture></div>
    <div class="floating-nav-v94"><button type="button" class="page-nav-v94 page-back-v94" data-prev aria-label="Previous page">← <span>BACK</span></button><button type="button" class="page-nav-v94 page-next-v94" data-next aria-label="Next page"><span>NEXT</span> →</button></div>
    <?php include 'page-footer.php'; ?>
  </article>

  <article class="experience-page-v94 page-booking-v94" data-page="2" id="page-section-4">
    <?php include 'page-header.php'; ?>
    <div class="page-content-v94 booking-page-content-v94">
      <section class="booking-section design-s4" id="plan">
        <div class="section4-shell">
          <div class="section4-hotspot">
            <div class="hotspot-copy">
              <span class="plan-label">PLAN YOUR VISIT</span>
              <h2 id="locationTitle">EXPLORE<br>VICTORIA MEMORIAL<br>LAAL MEJHE</h2>
              <p id="locationDescription">A heritage experience in the heart of Kolkata.</p>
            </div>
            <div class="kolkata-map-art" aria-label="Kolkata location">
              <picture><source media="(max-width: 767px)" srcset="/assets/section4-map-mobile.png"><img src="/assets/section4-map-desktop.png" alt="Kolkata map"></picture>
              <div class="hotspot-pins" id="hotspotPins"></div>
            </div>
            <div class="hotspot-footer">
              <button type="button" class="see-map-btn" id="seeMapBtn"><span class="see-map-thumb"><iframe id="miniLocationMap" src="<?=hs($first['map']??'https://www.google.com/maps?q=Kolkata,West%20Bengal&z=12&output=embed')?>" loading="eager" title="Selected location map"></iframe></span><span>See on Map</span></button>
              <button type="button" class="hotspot-book-btn" id="scrollBookingBtn">Book your slot <span>→</span></button>
            </div>
          </div>

          <div class="section4-booking-card">
            <div class="section4-location-head">
              <div class="section4-address"><span class="pin-icon">●</span><span id="locationAddress">Victoria Memorial Hall, 1, Queens Way, Maidan, Kolkata, West Bengal 700071</span></div>
              <a id="directionBtn" href="#" target="_blank" rel="noopener" class="section4-direction">Get Direction →</a>
              <div class="section4-map"><iframe id="locationMap" src="<?=hs($first['map']??'https://www.google.com/maps?q=Kolkata,West%20Bengal&z=12&output=embed')?>" loading="eager" title="Selected location map"></iframe></div>
            </div>
            <div class="booking-card-head"><h2>Book Your Lal Mejhe</h2><p>Select a date first, then choose 1-hour time slot.</p></div>
            <form method="post" action="book.php" id="bookingForm"><input type="hidden" name="csrf_token" value="<?=hs(csrf_token())?>">
              <input type="hidden" name="location_id" id="location_id" value="<?=hs($first['id']??0)?>">
              <input type="hidden" name="guest_name" id="guest_name"><input type="hidden" name="guest_mobile" id="guest_mobile"><input type="hidden" name="slot_date" id="slotDateInput"><input type="hidden" name="seats" id="seatsInput">
              <div class="book-step wide"><div class="step-no">01</div><div class="step-content"><h3>Select Date</h3><div id="dateButtons" class="date-buttons"></div></div></div>
              <div class="book-step slot-step-full" id="slotStep"><div class="step-no">02</div><div class="step-content"><h3>Select 1-Hour Slot</h3><div class="slot-pills" id="slotPills"><span class="slot-empty">Select date first</span></div><select id="slotSelect" name="slot_id" required disabled><option value="">Select date first</option></select></div></div>
              <div class="book-step seat-step-design4 seat-step-full" id="seatStep"><div class="step-no">03</div><div class="step-content"><h3>Select No. of People <small id="seatMaxLabel">Choose a slot first</small></h3><div class="mini-seat-grid" id="seatGrid"></div><div class="seat-info" id="seatInfo">Select a date and slot to see available seats.</div></div></div>
              <div class="booking-note" id="selectionSummary">Select a date first.</div><button class="confirm-btn" type="submit">CONFIRM MY BOOKING <span>→</span></button>
            </form>
          </div>
        </div>
      </section>
    </div>
    <div class="floating-nav-v94"><button type="button" class="page-nav-v94 page-back-v94" data-prev aria-label="Previous page">← <span>BACK</span></button><button type="button" class="page-nav-v94 page-next-v94" data-next aria-label="Next page"><span>NEXT</span> →</button></div>
    <?php include 'page-footer.php'; ?>
  </article>

  <article class="experience-page-v94 gated-page-v94" data-page="3" data-booking-required="1" id="page-section-5">
    <?php include 'page-header.php'; ?>
    <div class="artwork-page-v94 artwork-s5-v94"><picture><source media="(max-width: 767px)" srcset="/assets/section5-mobile.jpg?v=118.0.0"><img src="/assets/section5-desktop.jpg" data-fallback="/assets/section5.webp" alt="Lal Mejhe Adda final artwork"></picture></div>
    <div class="booking-confirm-modal-v95" id="bookingConfirmModal" aria-hidden="true">
      <div class="booking-confirm-overlay" data-close-booking></div>
      <div class="booking-confirm-card" role="dialog" aria-modal="true" aria-labelledby="bookingConfirmTitle">
        <button type="button" class="booking-confirm-close" data-close-booking aria-label="Close booking details">×</button>
        <span class="booking-confirm-eyebrow">BOOKING CONFIRMED</span>
        <h2 id="bookingConfirmTitle">Your Lal Mejhe Booking</h2>
        <div class="booking-confirm-code" id="confirmBookingCode">—</div>
        <div class="booking-confirm-details" id="confirmBookingDetails"></div>
        <button type="button" class="booking-confirm-ok" data-close-booking>CONTINUE EXPERIENCE →</button>
      </div>
    </div>
    <div class="floating-nav-v94"><button type="button" class="page-nav-v94 page-back-v94" data-prev aria-label="Previous page">← <span>BACK</span></button></div>
    <?php include 'page-footer.php'; ?>
  </article>
</main>
<script src="/assets/index-booking.js?v=124.0.0" defer></script>
