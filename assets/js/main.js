
/* ── Theme: apply immediately to avoid flash ── */
(function () {
  const saved = localStorage.getItem('rv_theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  document.documentElement.setAttribute(
    'data-theme',
    (saved ? saved === 'dark' : prefersDark) ? 'dark' : 'light')
     if (new URLSearchParams(window.location.search).get('loggedout') === '1') {
    localStorage.removeItem('rv_theme');
    history.replaceState(null, '', window.location.pathname);}
  ;
})();
  // ... rest of your existing main.js code
/* ═══════════════════════════════════════════════════
   ReVènta — main.js
   Hamburger nav  ·  Product routing  ·  Page loader
═══════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function () {
  const html = document.documentElement;

  function applyTheme(dark) {
    html.setAttribute('data-theme', dark ? 'dark' : 'light');
  }

  const saved = localStorage.getItem('rv_theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

  applyTheme(saved ? saved === 'dark' : prefersDark);
});
document.addEventListener('DOMContentLoaded', function () {

  /* ── 1. Hamburger / Mobile Menu ─────────────────── */
  var btn     = document.getElementById('navHamburger');
  var menu    = document.getElementById('navMobileMenu');
  var overlay = document.getElementById('navOverlay');

  function openMenu() {
    btn.classList.add('open');
    menu.classList.add('open');
    if (overlay) overlay.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
  }

  function closeMenu() {
    btn.classList.remove('open');
    menu.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
  }

  if (btn && menu) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.contains('open') ? closeMenu() : openMenu();
    });

    if (overlay) overlay.addEventListener('click', closeMenu);

    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', closeMenu);
    });
  }

  /* ── 2. Product Page — hydrate from localStorage ── */
  if (document.querySelector('.product-body')) {
    loadProductPage();
  }

});


/* ─────────────────────────────────────────────────
   goToProduct(data)
   Call from any page to navigate to product.html
   with the clicked item's data.
───────────────────────────────────────────────── */
function goToProduct(data) {
  localStorage.setItem('rv_product', JSON.stringify(data));
  var inPages = window.location.pathname.includes('/pages/');
  window.location.href = inPages ? 'product.html' : '/pages/product.html';
}


/* ─────────────────────────────────────────────────
   loadProductPage()
   Reads rv_product from localStorage and updates
   all fields in product.html.
───────────────────────────────────────────────── */
function loadProductPage() {
  var raw = localStorage.getItem('rv_product');
  if (!raw) return;

  var p;
  try { p = JSON.parse(raw); } catch (e) { return; }

  /* Brand */
  var brandEl = document.querySelector('.product-brand');
  if (brandEl && p.brand) brandEl.textContent = p.brand;

  /* Name */
  var nameEl = document.querySelector('.product-name');
  if (nameEl && p.name) nameEl.textContent = p.name;

  /* Price */
  var priceEl = document.querySelector('.product-detail-price');
  if (priceEl && p.price) {
    priceEl.innerHTML = p.price +
      (p.original ? ' <span class="original">' + p.original + '</span>' : '');
  }

  /* Condition tag */
  var condEl = document.querySelector('.product-condition-tag');
  if (condEl && p.condition) condEl.textContent = p.condition;

  /* Carousel slides — three slight variations */
  if (p.gradient) {
    var slides = document.querySelectorAll('.carousel-slide');
    var g = p.gradient;
    /* derive subtle alternate shades from the base gradient */
    var g2 = p.gradient2 || g.replace(/145deg/, '160deg');
    var g3 = p.gradient3 || g.replace(/145deg/, '130deg');
    var grads = [g, g2, g3];
    slides.forEach(function (slide, i) {
      slide.style.background = grads[i];
    });
  }

  /* Sizes */
  var sizeWrap = document.querySelector('.size-options');
  if (sizeWrap && p.sizes && p.sizes.length) {
    sizeWrap.innerHTML = '';
    p.sizes.forEach(function (s) {
      var b = document.createElement('button');
      b.className = 'size-btn';
      b.textContent = s;
      b.addEventListener('click', function () {
        document.querySelectorAll('.size-btn').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active');
      });
      sizeWrap.appendChild(b);
    });
  }

  /* Seller handle */
  var sellerEl = document.querySelector('.seller-info h5');
  if (sellerEl && p.seller) sellerEl.textContent = p.seller;

  /* Description (first accordion block) */
  var descBlocks = document.querySelectorAll('.product-desc-text');
  if (descBlocks[0] && p.description) descBlocks[0].textContent = p.description;

  /* Page <title> */
  if (p.brand && p.name) {
    document.title = p.brand + ' — ' + p.name + ' | ReVènta';
  }
  
}