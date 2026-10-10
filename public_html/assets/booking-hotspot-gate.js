/* V127: Require explicit hotspot selection before showing booking details.
 * Additive only; leaves existing booking/OTP/seat/confirmation logic untouched.
 */
(function () {
  'use strict';
  function init() {
    var page = document.querySelector('.page-booking-v94');
    var card = page && page.querySelector('.section4-booking-card');
    var pins = page && page.querySelector('#hotspotPins');
    var dateButtons = page && page.querySelector('#dateButtons');
    var prompt = page && page.querySelector('#hotspotSelectionPrompt');
    if (!page || !card || !pins || !prompt) return;

    // A fresh visit must always begin without a preselected hotspot.
    card.setAttribute('data-location-selected', '0');
    page.querySelectorAll('.hotspot-pin').forEach(function (pin) {
      pin.classList.remove('active');
      pin.setAttribute('aria-pressed', 'false');
    });

    pins.addEventListener('click', function (event) {
      var pin = event.target.closest('.hotspot-pin');
      if (!pin || !pins.contains(pin)) return;
      // Existing pin handler updates location fields first; wait for that update.
      window.setTimeout(function () {
        card.setAttribute('data-location-selected', '1');
        page.querySelectorAll('.hotspot-pin').forEach(function (item) {
          var selected = item === pin;
          item.classList.toggle('active', selected);
          item.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        // On phones, bring the date controls into view after a location is chosen.
        if (window.matchMedia('(max-width: 767px)').matches && dateButtons) {
          window.setTimeout(function () {
            dateButtons.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }, 100);
        }
      }, 60);
    });

    var bookButton = page.querySelector('#scrollBookingBtn');
    if (bookButton) {
      bookButton.addEventListener('click', function (event) {
        if (card.getAttribute('data-location-selected') !== '1') {
          event.preventDefault();
          prompt.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, true);
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
