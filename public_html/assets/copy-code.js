document.querySelector('.copy-btn')?.addEventListener('click', function () {
  var b = this;
  navigator.clipboard.writeText(document.getElementById('bookingCode').innerText).then(function () {
    b.innerText = 'Copied ✓'; setTimeout(function () { b.innerText = 'Copy Code'; }, 1500);
  }, function () {});
});
