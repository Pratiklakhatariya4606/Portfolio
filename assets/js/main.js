(function () {
  var $ = function (id) { return document.getElementById(id); };
  var url = document.body.dataset.shareUrl, title = document.body.dataset.shareTitle;

  $('burger').onclick = function () { $('menu').classList.toggle('open'); };

  var box = $('shareBox');
  function open() {
    $('shareLink').value = url;
    var u = encodeURIComponent(url), t = encodeURIComponent(title);
    $('wa').href = 'https://wa.me/?text=' + t + '%20' + u;
    $('li').href = 'https://www.linkedin.com/sharing/share-offsite/?url=' + u;
    $('tw').href = 'https://twitter.com/intent/tweet?text=' + t + '&url=' + u;
    $('ml').href = 'mailto:?subject=' + t + '&body=' + u;
    box.classList.add('open');
  }
  // Use the phone/OS native share sheet when available, otherwise show our own box.
  $('shareBtn').onclick = function () {
    if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); }
    else { open(); }
  };
  $('closeShare').onclick = function () { box.classList.remove('open'); };
  box.onclick = function (e) { if (e.target === box) box.classList.remove('open'); };
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') box.classList.remove('open'); });

  $('copyBtn').onclick = function () {
    var b = this;
    (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).catch(function () {
      $('shareLink').select(); document.execCommand('copy');
    }).finally(function () { b.textContent = 'Copied!'; setTimeout(function () { b.textContent = 'Copy link'; }, 1500); });
  };
})();

(function () {
  var $ = function (i) { return document.getElementById(i); };
  var t = $('welcome');
  if (t) { setTimeout(function () { t.classList.add('out'); }, 3000); setTimeout(function () { t.remove(); }, 3500); }
  var b = $('exitBtn'); if (!b) return;
  var box = $('exitBox'), d = { rating: 'like', changes: 'no', comments: '' };
  function step(n) { ['st1', 'st2', 'st3'].forEach(function (s, i) { $(s).hidden = (i + 1 !== n); }); }
  b.onclick = function () { step(1); box.classList.add('open'); };
  $('exitClose').onclick = function () { box.classList.remove('open'); };
  document.querySelectorAll('[data-rate]').forEach(function (x) { x.onclick = function () { d.rating = x.dataset.rate; step(2); }; });
  $('chYes').onclick = function () { d.changes = 'yes'; step(3); };
  $('chNo').onclick = function () { d.changes = 'no'; send(); };
  $('chSend').onclick = function () { d.comments = $('chText').value; send(); };
  function send() {
    var f = new FormData(); f.append('csrf', document.body.dataset.csrf);
    f.append('rating', d.rating); f.append('changes', d.changes); f.append('comments', d.comments);
    fetch('exit.php', { method: 'POST', body: f }).catch(function () {}).finally(function () {
      box.classList.remove('open'); $('bye').hidden = false;
      setTimeout(function () { window.close(); }, 2200); // browsers only allow this for tabs opened by script
    });
  }
})();
