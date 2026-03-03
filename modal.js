let modalActiveImg = 0;
let modalProduct = null;

function hO(s = 14) {
  return `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
}
function hF(s = 14) {
  return `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
}

function openModal(id) {
  const p = PRODUCTS.find(x => x.id === id);
  if (!p) return;
  modalProduct = p;
  modalActiveImg = 0;
  const existing = document.getElementById('product-modal');
  if (existing) existing.remove();

  const isSold = p.badge === 'Sold';
  const isLiked = likedIds.has(p.id);
  const modal = document.createElement('div');
  modal.id = 'product-modal';
  modal.innerHTML = `
    <div class="modal-backdrop" id="modal-backdrop"></div>
    <div class="modal-sheet" id="modal-sheet">
      <button class="modal-close" id="modal-close">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
      <div class="modal-gallery">
        <div class="modal-main-img">
          <img src="${p.images[0]}" alt="${p.title}" id="modal-hero-img">
          ${p.badge ? `<span class="product-badge ${p.badge === 'Sold' ? 'sold' : p.badge === 'Hot' ? 'hot' : ''}">${p.badge}</span>` : ''}
          <button class="modal-img-btn modal-img-prev" id="modal-prev">&#8249;</button>
          <button class="modal-img-btn modal-img-next" id="modal-next">&#8250;</button>
        </div>
        <div class="modal-thumbs" id="modal-thumbs">
          ${p.images.map((img, i) => `<div class="modal-thumb ${i === 0 ? 'active' : ''}" data-idx="${i}"><img src="${img.replace('w=900', 'w=200')}" alt=""></div>`).join('')}
        </div>
      </div>
      <div class="modal-info">
        <div class="modal-top-row">
          <div><div class="modal-brand">${p.brand}</div><h2 class="modal-title">${p.title}</h2></div>
          <button class="modal-like ${isLiked ? 'liked' : ''}" id="modal-like-btn">${isLiked ? hF(20) : hO(20)}</button>
        </div>
        <div class="modal-price-row">
          <span class="modal-price">$${p.price}</span>
          <span class="modal-condition-badge">${p.condition}</span>
        </div>
        <div class="modal-section">
          <div class="modal-label">Size</div>
          <div class="modal-sizes">${p.sizes.map(s => `<div class="modal-size-pill ${s === p.size ? 'active' : ''}" data-size="${s}">${s}</div>`).join('')}</div>
        </div>
        <div class="modal-section">
          <div class="modal-label">Description</div>
          <p class="modal-desc">${p.desc}</p>
        </div>
        <div class="modal-seller">
          <img src="${p.seller.img}" alt="${p.seller.name}" class="modal-seller-img">
          <div class="modal-seller-info">
            <div class="modal-seller-name">${p.seller.name}</div>
            <div class="modal-seller-meta"><span class="modal-star">★ ${p.seller.rating}</span><span class="modal-dot">·</span><span>${p.seller.sales} sales</span></div>
          </div>
          <button class="modal-follow-btn">Follow</button>
        </div>
        <div class="modal-actions">
          ${isSold
            ? `<button class="modal-btn-sold" disabled>Sold Out</button>`
            : `<button class="modal-btn-buy" id="modal-buy-btn">Buy Now · $${p.price}</button>
               <button class="modal-btn-offer" id="modal-offer-btn">Make Offer</button>`
          }
        </div>
      </div>
    </div>`;

  document.body.appendChild(modal);
  document.body.style.overflow = 'hidden';
  requestAnimationFrame(() => modal.classList.add('open'));

  modal.querySelectorAll('.modal-thumb').forEach(t => {
    t.addEventListener('click', () => setModalImg(parseInt(t.dataset.idx)));
  });
  document.getElementById('modal-prev').addEventListener('click', e => {
    e.stopPropagation();
    setModalImg((modalActiveImg - 1 + p.images.length) % p.images.length);
  });
  document.getElementById('modal-next').addEventListener('click', e => {
    e.stopPropagation();
    setModalImg((modalActiveImg + 1) % p.images.length);
  });

  document.getElementById('modal-like-btn').addEventListener('click', e => {
    e.stopPropagation();
    const btn = e.currentTarget;
    if (likedIds.has(p.id)) {
      likedIds.delete(p.id);
      btn.classList.remove('liked');
      btn.innerHTML = hO(20);
    } else {
      likedIds.add(p.id);
      btn.classList.add('liked');
      btn.innerHTML = hF(20);
      btn.style.transform = 'scale(1.3)';
      setTimeout(() => btn.style.transform = '', 200);
    }
    updateLikesCount();
    document.querySelectorAll(`.like-btn[data-id="${p.id}"]`).forEach(b => {
      if (likedIds.has(p.id)) { b.classList.add('liked'); b.innerHTML = hF(); }
      else { b.classList.remove('liked'); b.innerHTML = hO(); }
    });
  });

  modal.querySelectorAll('.modal-size-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      modal.querySelectorAll('.modal-size-pill').forEach(x => x.classList.remove('active'));
      pill.classList.add('active');
    });
  });

  document.getElementById('modal-backdrop').addEventListener('click', closeModal);
  document.getElementById('modal-close').addEventListener('click', closeModal);
  document.getElementById('modal-buy-btn')?.addEventListener('click', () => showToast('🎉 Added to checkout!'));
  document.getElementById('modal-offer-btn')?.addEventListener('click', () => showToast('💬 Offer sent to seller!'));
  document.addEventListener('keydown', handleModalKey);
}

function setModalImg(idx) {
  if (!modalProduct) return;
  modalActiveImg = idx;
  const img = document.getElementById('modal-hero-img');
  if (img) {
    img.style.opacity = '0';
    img.style.transform = 'scale(1.04)';
    setTimeout(() => {
      img.src = modalProduct.images[idx];
      img.style.opacity = '1';
      img.style.transform = 'scale(1)';
    }, 130);
  }
  document.querySelectorAll('.modal-thumb').forEach((t, i) => t.classList.toggle('active', i === idx));
}

function closeModal() {
  const modal = document.getElementById('product-modal');
  if (!modal) return;
  modal.classList.remove('open');
  modal.classList.add('closing');
  document.body.style.overflow = '';
  document.removeEventListener('keydown', handleModalKey);
  setTimeout(() => modal.remove(), 320);
}

function handleModalKey(e) {
  if (e.key === 'Escape') closeModal();
  if (e.key === 'ArrowLeft' && modalProduct) setModalImg((modalActiveImg - 1 + modalProduct.images.length) % modalProduct.images.length);
  if (e.key === 'ArrowRight' && modalProduct) setModalImg((modalActiveImg + 1) % modalProduct.images.length);
}