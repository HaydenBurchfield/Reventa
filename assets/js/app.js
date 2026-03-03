// Likes are stored in memory — in a real app this would use a backend or localStorage
let likedIds = new Set([2]);

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
  renderGrid('home-grid', cat === 'all' ? PRODUCTS.slice(0, 8) : PRODUCTS.filter(p => p.cat === cat).slice(0, 8));
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