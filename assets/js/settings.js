const newPw  = document.getElementById('new_password');
const confPw = document.getElementById('confirm_password');
const bar    = document.getElementById('pw-bar');
const label  = document.getElementById('pw-strength-label');
const hint   = document.getElementById('pw-match-hint');

if (newPw) {
  newPw.addEventListener('input', function() {
    const v = this.value;
    let score = 0;
    if (v.length >= 8)           score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v))  score++;
    const widths = ['0%','25%','50%','75%','100%'];
    const colors = ['#eee','#999','#777','#444','#0D0D0D'];
    const labels = ['','Weak','Fair','Good','Strong'];
    if (bar)   { bar.style.width = widths[score]; bar.style.background = colors[score]; }
    if (label)   label.textContent = labels[score];
  });
}
if (confPw) {
  confPw.addEventListener('input', function() {
    if (!hint || !newPw) return;
    if (this.value === '') { hint.textContent = ''; return; }
    if (this.value === newPw.value) {
      hint.textContent = '✓ Passwords match'; hint.style.color = '#1A7A40';
    } else {
      hint.textContent = '✗ Passwords do not match'; hint.style.color = '#C0392B';
    }
  });
}

// Delete confirm toggle
function toggleDelete() {
  const wrap  = document.getElementById('delete-confirm');
  const arrow = document.getElementById('delete-arrow');
  const open  = wrap.classList.toggle('open');
  if (arrow) arrow.textContent = open ? '⌄' : '›';
}

// Dark mode toggle (UI-only)
const darkToggle = document.getElementById('dark-toggle');
if (darkToggle) {
  darkToggle.addEventListener('change', function() {
    const r = document.documentElement;
    if (this.checked) {
      r.style.setProperty('--bg',     '#161616');
      r.style.setProperty('--white',  '#1E1E1E');
      r.style.setProperty('--border', '#2E2E2E');
      r.style.setProperty('--black',  '#F5F5F5');
      r.style.setProperty('--charcoal','#CCCCCC');
      r.style.setProperty('--mid',    '#888888');
      document.body.style.background = '#111';
    } else {
      r.style.setProperty('--bg',     '#F5F5F5');
      r.style.setProperty('--white',  '#FFFFFF');
      r.style.setProperty('--border', '#E8E8E8');
      r.style.setProperty('--black',  '#0D0D0D');
      r.style.setProperty('--charcoal','#2C2C2C');
      r.style.setProperty('--mid',    '#6B6B6B');
      document.body.style.background = '';
    }
  });
  const ham  = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });

/* ── Dark mode ── */
const html         = document.documentElement;
const darkToggle   = document.getElementById('darkModeToggle');
const headerBtn    = document.getElementById('themeToggle');
const themeIcon    = document.getElementById('themeIcon');
const themeLabel   = document.getElementById('themeLabel');

function applyTheme(dark) {
  html.setAttribute('data-theme', dark ? 'dark' : 'light');
  darkToggle.checked = dark;
  themeIcon.textContent  = dark ? '☀' : '☾';
  themeLabel.textContent = dark ? 'Light' : 'Dark';
  localStorage.setItem('rv_theme', dark ? 'dark' : 'light');
}

/* Restore saved preference */
const saved = localStorage.getItem('rv_theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
applyTheme(saved ? saved === 'dark' : prefersDark);

darkToggle.addEventListener('change', () => applyTheme(darkToggle.checked));
headerBtn.addEventListener('click',   () => applyTheme(html.getAttribute('data-theme') !== 'dark'));

/* ── Size chips ── */
document.querySelectorAll('.size-grid').forEach(grid => {
  grid.querySelectorAll('.size-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      grid.querySelectorAll('.size-chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
    });
  });
});
}