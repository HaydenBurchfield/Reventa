/* ============================================================
   settings.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  /* ── Mobile nav hamburger ── */
  const ham  = document.getElementById('navHamburger');
  const menu = document.getElementById('navMobileMenu');
  if (ham && menu) {
    ham.addEventListener('click', () => {
      ham.classList.toggle('open');
      menu.classList.toggle('open');
    });
  }

  /* ── Dark mode ── */
  
  const html         = document.documentElement;
  const darkToggle   = document.getElementById('darkModeToggle');
  console.log('toggle found:', darkToggle); // check this in DevTools
  const headerBtn    = document.getElementById('themeToggle');
  const themeIcon    = document.getElementById('themeIcon');
  const themeLabel   = document.getElementById('themeLabel');

  function applyTheme(dark) {
    html.setAttribute('data-theme', dark ? 'dark' : 'light');
    if (darkToggle)  darkToggle.checked      = dark;
    if (themeIcon)   themeIcon.textContent   = dark ? '☀' : '☾';
    if (themeLabel)  themeLabel.textContent  = dark ? 'Light' : 'Dark';
    localStorage.setItem('rv_theme', dark ? 'dark' : 'light');
  }

  /* Restore saved preference or system preference */
  const saved       = localStorage.getItem('rv_theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  applyTheme(saved ? saved === 'dark' : prefersDark);

  if (darkToggle) {
    darkToggle.addEventListener('change', () => applyTheme(darkToggle.checked));
  }
  if (headerBtn) {
    headerBtn.addEventListener('click', () => {
      applyTheme(html.getAttribute('data-theme') !== 'dark');
    });
  }

  /* ── Password strength meter ── */
  const newPw  = document.getElementById('new_password');
  const confPw = document.getElementById('confirm_password');
  const bar    = document.getElementById('pw-bar');
  const label  = document.getElementById('pw-strength-label');
  const hint   = document.getElementById('pw-match-hint');

  if (newPw) {
    newPw.addEventListener('input', function () {
      const v = this.value;
      let score = 0;
      if (v.length >= 8)           score++;
      if (/[A-Z]/.test(v))         score++;
      if (/[0-9]/.test(v))         score++;
      if (/[^A-Za-z0-9]/.test(v))  score++;
      const widths = ['0%', '25%', '50%', '75%', '100%'];
      const colors = ['#eee', '#999', '#777', '#444', '#0D0D0D'];
      const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
      if (bar)   { bar.style.width = widths[score]; bar.style.background = colors[score]; }
      if (label) label.textContent = labels[score];
    });
  }

  if (confPw) {
    confPw.addEventListener('input', function () {
      if (!hint || !newPw) return;
      if (this.value === '') { hint.textContent = ''; return; }
      if (this.value === newPw.value) {
        hint.textContent = '✓ Passwords match';
        hint.style.color = '#1A7A40';
      } else {
        hint.textContent = '✗ Passwords do not match';
        hint.style.color = '#C0392B';
      }
    });
  }

  /* ── Size chips ── */
  document.querySelectorAll('.size-grid').forEach(function (grid) {
    grid.querySelectorAll('.size-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        grid.querySelectorAll('.size-chip').forEach(function (c) {
          c.classList.remove('active');
        });
        chip.classList.add('active');
      });
    });
  });

});