let likedIds = new Set([2]);
let currentTab = 'home';
let exploreFilter = 'all';
let homeFilter = 'all';

// ── TOAST ─────────────────────────────────────
function showToast(msg) {
  const old = document.getElementById('thrift-toast');
  if (old) old.remove();
  const t = document.createElement('div');
  t.id = 'thrift-toast';
  t.textContent = msg;
  document.body.appendChild(t);
  requestAnimationFrame(() => t.classList.add('show'));
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2500);
}

// ── TABS ──────────────────────────────────────
function switchTab(tabId) {
  if (tabId === currentTab) return;
  currentTab = tabId;
  document.querySelectorAll('.tab-page').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + tabId)?.classList.add('active');
  document.querySelectorAll('#bottom-nav .bottom-item').forEach(i => i.classList.toggle('active', i.dataset.tab === tabId));
  document.querySelectorAll('.nav-tab-link').forEach(l => l.classList.toggle('active', l.dataset.tab === tabId));
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (tabId === 'likes') renderLikes();
  if (tabId === 'profile') renderProfileGrid();
}

// ── LIKES ─────────────────────────────────────
function toggleLike(id, btn) {
  if (likedIds.has(id)) {
    likedIds.delete(id);
    btn.classList.remove('liked');
    btn.innerHTML = hO();
  } else {
    likedIds.add(id);
    btn.classList.add('liked');
    btn.innerHTML = hF();
    btn.style.transform = 'scale(1.3)';
    setTimeout(() => btn.style.transform = '', 200);
  }
  updateLikesCount();
}

function updateLikesCount() {
  const el = document.getElementById('likes-count');
  if (el) el.textContent = likedIds.size + (likedIds.size === 1 ? ' item' : ' items');
}

// ── CARD ──────────────────────────────────────
function cardHTML(p) {
  const bc = p.badge === 'Sold' ? 'sold' : p.badge === 'Hot' ? 'hot' : '';
  const liked = likedIds.has(p.id);
  return `<div class="product-card" data-id="${p.id}" data-cat="${p.cat}">
    <div class="product-img">
      <img src="${p.img}" alt="${p.title}" loading="lazy">
      ${p.badge ? `<span class="product-badge ${bc}">${p.badge}</span>` : ''}
      <button class="like-btn ${liked ? 'liked' : ''}" data-id="${p.id}" onclick="event.stopPropagation();toggleLike(${p.id},this)">${liked ? hF() : hO()}</button>
    </div>
    <div class="product-info">
      <div class="brand">${p.brand}</div>
      <div class="title">${p.title}</div>
      <div class="meta"><span class="price">$${p.price}</span><span class="condition">${p.condition}</span></div>
    </div>
  </div>`;
}

function attachCardClicks(el) {
  el.querySelectorAll('.product-card').forEach(c =>
    c.addEventListener('click', () => openModal(parseInt(c.dataset.id)))
  );
}

// ── RENDER FUNCTIONS ──────────────────────────
function renderGrid(id, items) {
  const el = document.getElementById(id);
  if (!el) return;
  el.innerHTML = items.length
    ? items.map(cardHTML).join('')
    : `<div class="empty-state"><div class="empty-icon">🔍</div><div class="empty-title">No items found</div><div class="empty-sub">Try a different category</div></div>`;
  attachCardClicks(el);
}

function renderHome(cat = 'all') {
  homeFilter = cat;
  renderGrid('home-grid', cat === 'all' ? PRODUCTS.slice(0, 8) : PRODUCTS.filter(p => p.cat === cat).slice(0, 8));
}

function renderExplore(cat = 'all') {
  exploreFilter = cat;
  let items = cat === 'all' ? [...PRODUCTS] : PRODUCTS.filter(p => p.cat === cat);
  const sort = document.getElementById('sort-select')?.value || 'newest';
  const cond = document.getElementById('condition-select')?.value || 'all';
  if (cond !== 'all') {
    const m = { 'like-new': 'Like New', 'very-good': 'Very Good', 'good': 'Good' };
    items = items.filter(p => p.condition === m[cond]);
  }
  if (sort === 'price-low') items.sort((a, b) => a.price - b.price);
  if (sort === 'price-high') items.sort((a, b) => b.price - a.price);
  renderGrid('explore-grid', items);
}

function renderLikes() {
  const el = document.getElementById('likes-grid');
  if (!el) return;
  const items = PRODUCTS.filter(p => likedIds.has(p.id));
  if (!items.length) {
    el.innerHTML = `<div class="empty-state"><div class="empty-icon">♡</div><div class="empty-title">No liked items yet</div><div class="empty-sub">Tap the heart on any item to save it here</div><button class="btn-primary" onclick="switchTab('explore')" style="margin-top:1.5rem">Browse Items</button></div>`;
  } else {
    el.innerHTML = items.map(cardHTML).join('');
    attachCardClicks(el);
  }
  updateLikesCount();
}

function renderProfileGrid() {
  renderGrid('profile-grid', PRODUCTS.slice(0, 6));
}

function renderSellers() {
  const el = document.getElementById('sellers-row');
  if (!el) return;
  el.innerHTML = SELLERS.map(s =>
    `<div class="seller-card">
      <div class="seller-avatar"><img src="${s.img}" alt="${s.name}" loading="lazy"></div>
      <div class="seller-name">${s.name}</div>
      <div class="seller-items">${s.items} items</div>
    </div>`
  ).join('');
}

// ── INIT ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  renderHome();
  renderExplore();
  renderSellers();
  updateLikesCount();

  // Nav
  document.querySelectorAll('#bottom-nav .bottom-item').forEach(i =>
    i.addEventListener('click', () => switchTab(i.dataset.tab))
  );
  document.querySelectorAll('.nav-tab-link').forEach(l =>
    l.addEventListener('click', e => { e.preventDefault(); switchTab(l.dataset.tab); })
  );
  document.getElementById('nav-sell-btn')?.addEventListener('click', () => switchTab('sell'));
  document.querySelector('.nav-logo')?.addEventListener('click', () => switchTab('home'));

  // Category filters
  document.getElementById('category-filters')?.addEventListener('click', e => {
    const p = e.target.closest('.cat-pill');
    if (!p) return;
    document.querySelectorAll('#category-filters .cat-pill').forEach(x => x.classList.remove('active'));
    p.classList.add('active');
    renderHome(p.dataset.cat);
  });
  document.getElementById('explore-filters')?.addEventListener('click', e => {
    const p = e.target.closest('.cat-pill');
    if (!p) return;
    document.querySelectorAll('#explore-filters .cat-pill').forEach(x => x.classList.remove('active'));
    p.classList.add('active');
    renderExplore(p.dataset.cat);
  });

  // Explore sort/condition
  document.getElementById('sort-select')?.addEventListener('change', () => renderExplore(exploreFilter));
  document.getElementById('condition-select')?.addEventListener('change', () => renderExplore(exploreFilter));

  // Search
  const si = document.getElementById('search-input');
  si?.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      switchTab('explore');
      const q = si.value.toLowerCase();
      const results = PRODUCTS.filter(p =>
        p.title.toLowerCase().includes(q) || p.brand.toLowerCase().includes(q)
      );
      renderGrid('explore-grid', results);
    }
  });

  // Sell form condition pills
  document.querySelectorAll('.cond-pill').forEach(p =>
    p.addEventListener('click', () => {
      document.querySelectorAll('.cond-pill').forEach(x => x.classList.remove('active'));
      p.classList.add('active');
    })
  );

  // Profile tabs
  document.querySelectorAll('.profile-tab').forEach(t =>
    t.addEventListener('click', () => {
      document.querySelectorAll('.profile-tab').forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      if (t.dataset.ptab === 'sold') renderGrid('profile-grid', PRODUCTS.slice(2, 5));
      else if (t.dataset.ptab === 'reviews') {
        document.getElementById('profile-grid').innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">⭐</div><div class="empty-title">All 5-star reviews</div><div class="empty-sub">98% positive rating from 204 buyers</div></div>`;
      } else {
        renderProfileGrid();
      }
    })
  );

  // Sell page
  document.querySelector('.btn-list')?.addEventListener('click', () => showToast('🎉 Item listed successfully!'));
  document.getElementById('upload-zone')?.addEventListener('click', () => showToast('📸 Photo upload would open here'));
});