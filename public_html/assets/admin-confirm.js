document.addEventListener('submit', function (e) {
  var m = e.target && e.target.dataset && e.target.dataset.confirm;
  if (m && !window.confirm(m)) e.preventDefault();
}, true);
