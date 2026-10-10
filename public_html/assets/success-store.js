try {
  var d = JSON.parse(document.getElementById('lm-booking').textContent);
  localStorage.setItem('bookingConfirmed', d.code);
  // One-time permission to show the confirmation on Page 5 after returning home (cleared when the visitor leaves Page 5).
  sessionStorage.setItem('pendingBookingConfirmation', d.code);
  localStorage.setItem('lastBookingDetails', JSON.stringify(d.details));
} catch (e) {}
