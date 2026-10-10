<?php
include 'header.php';
$code = $_GET['code'] ?? '';
$booking = null;
if ($code !== '') {
    try {
        $q = $pdo->prepare('SELECT b.booking_code,b.name,COALESCE(b.mobile,b.whatsapp) AS mobile,b.seats,l.name AS location_name,s.slot_date,s.slot_time FROM bookings b JOIN locations l ON l.id=b.location_id JOIN slots s ON s.id=b.slot_id WHERE b.booking_code=? LIMIT 1');
        $q->execute([$code]);
        $booking = $q->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}
?>
<section class="success">
  <div>
    <span class="eyebrow">BOOKING CONFIRMED</span>
    <h1>Your Booking is Confirmed!</h1>
    <p>Your unique booking code</p>
    <h2><?php echo htmlspecialchars($code); ?></h2>
    <p class="booking-reference-reminder lm-success-1">Please save this code for your booking reference.</p>


    <a class="btn lm-success-2" href="index.php">BACK TO HOME →</a>
  </div>
</section>
<?php if ($booking): ?>
<script type="application/json" id="lm-booking"><?=json_encode(['code'=>$booking['booking_code'],'details'=>[
  'name'=>$booking['name'],
  'location'=>$booking['location_name'],
  'date'=>date('d M Y', strtotime($booking['slot_date'])),
  'time'=>$booking['slot_time'],
  'seats'=>'Seats: '.$booking['seats']
]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script>
<script src="/assets/success-store.js?v=1" defer></script>
<?php endif; ?>
<?php include 'footer.php'; ?>
