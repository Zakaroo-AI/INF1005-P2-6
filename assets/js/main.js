// ============================================================
// assets/js/main.js — PokéMart Global Custom JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    initAutocomplete();
    initCartButtons();
    initWishlistButtons();
    animateStatBars();
    initNavbarScroll();
    initCardAnimations();
});

// ---- SEARCH AUTOCOMPLETE ----------------------------------------
function initAutocomplete() {
    const input = document.getElementById('searchInput');
    const list  = document.getElementById('autocomplete-list');
    if (!input || !list) return;

    let debounceTimer;
    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 2) { list.style.display = 'none'; return; }

        debounceTimer = setTimeout(async () => {
            try {
                const res  = await fetch(`/api/search.php?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                list.innerHTML = '';
                if (data.length === 0) { list.style.display = 'none'; return; }
                data.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    li.textContent = item.name;
                    li.addEventListener('click', () => {
                        input.value = item.name;
                        list.style.display = 'none';
                        input.closest('form').submit();
                    });
                    list.appendChild(li);
                });
                list.style.display = 'block';
            } catch (e) { console.error('Autocomplete error:', e); }
        }, 250);
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target)) list.style.display = 'none';
    });
}

// ---- CART QUANTITY BUTTONS (AJAX) --------------------------------
function initCartButtons() {
    // Qty update
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const listingId = btn.dataset.listing;
            const action    = btn.dataset.action; // 'increase' or 'decrease'
            try {
                const res  = await fetch('/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, listing_id: listingId })
                });
                const data = await res.json();
                if (data.success) {
                    updateCartUI(data);
                } else {
                    showToast(data.message || 'Error updating cart', 'danger');
                }
            } catch (e) { showToast('Network error', 'danger'); }
        });
    });

    // Remove item
    document.querySelectorAll('.remove-cart-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const listingId = btn.dataset.listing;
            try {
                const res  = await fetch('/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove', listing_id: listingId })
                });
                const data = await res.json();
                if (data.success) {
                    // Remove row from DOM
                    const row = document.getElementById(`cart-row-${listingId}`);
                    if (row) row.remove();
                    updateCartUI(data);
                    if (data.cart_count === 0) {
                        const cartContainer = document.getElementById('cart-items');
                        if (cartContainer) {
                            cartContainer.innerHTML = '<p class="text-muted text-center py-4">Your cart is empty. <a href="/browse.php">Browse listings</a></p>';
                        }
                        const summary = document.getElementById('cart-summary');
                        if (summary) summary.style.display = 'none';
                    }
                }
            } catch (e) { showToast('Network error', 'danger'); }
        });
    });
}

function updateCartUI(data) {
    // Update qty display
    if (data.listing_id) {
        const qtyEl = document.getElementById(`qty-${data.listing_id}`);
        if (qtyEl) qtyEl.textContent = data.new_qty ?? 0;
        const subtotalEl = document.getElementById(`subtotal-${data.listing_id}`);
        if (subtotalEl && data.subtotal !== undefined) subtotalEl.textContent = `$${parseFloat(data.subtotal).toFixed(2)}`;
    }
    // Update total
    const totalEl = document.getElementById('cart-total');
    if (totalEl && data.cart_total !== undefined) totalEl.textContent = `$${parseFloat(data.cart_total).toFixed(2)}`;
    // Update navbar badge
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.textContent = data.cart_count ?? 0;
        badge.style.display = (data.cart_count > 0) ? '' : 'none';
        if (data.cart_count > 0) {
            badge.classList.remove('badge-bounce');
            void badge.offsetWidth; // force reflow to restart animation
            badge.classList.add('badge-bounce');
            badge.addEventListener('animationend', () => badge.classList.remove('badge-bounce'), { once: true });
        }
    }
}

// ---- WISHLIST TOGGLE (AJAX) --------------------------------------
function initWishlistButtons() {
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const listingId = btn.dataset.listing;
            try {
                const res  = await fetch('/api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ listing_id: listingId })
                });
                const data = await res.json();
                if (data.success) {
                    btn.classList.toggle('wishlisted', data.wishlisted);
                    btn.setAttribute('aria-pressed', data.wishlisted);
                    btn.title = data.wishlisted ? 'Remove from wishlist' : 'Add to wishlist';
                    showToast(data.message, 'success');
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } catch (e) { showToast('Network error', 'danger'); }
        });
    });
}

// ---- STAT BARS ANIMATION ----------------------------------------
function animateStatBars() {
    document.querySelectorAll('.stat-bar[data-value]').forEach(bar => {
        const val = parseInt(bar.dataset.value, 10);
        const max = parseInt(bar.dataset.max  || 200, 10);
        const pct = Math.min((val / max) * 100, 100);
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = pct + '%'; }, 100);
    });
}

// ---- TOAST NOTIFICATION ----------------------------------------
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = 9999;
        document.body.appendChild(container);
    }
    const id   = 'toast-' + Date.now();
    const html = `
    <div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(id);
    const toast   = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
    requestAnimationFrame(() => toast.show());
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// ---- FORM VALIDATION (Bootstrap) --------------------------------
window.addEventListener('load', () => {
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});

// ---- NAVBAR HIDE ON SCROLL --------------------------------------
function initNavbarScroll() {
    const navbar = document.querySelector('.pm-navbar');
    if (!navbar) return;

    let prevY = window.scrollY;

    window.addEventListener('scroll', () => {
        const y = window.scrollY;
        if (y > prevY && y > 56) {
            navbar.classList.add('navbar-hidden');
        } else if (y < prevY) {
            navbar.classList.remove('navbar-hidden');
        }
        prevY = y;
    }, { passive: true });
}

// ---- CARD ENTRANCE ANIMATIONS ----------------------------------
function initCardAnimations() {
    const cards = document.querySelectorAll('.listing-card');
    if (!cards.length) return;

    // Hide all cards initially
    cards.forEach(card => { card.style.opacity = '0'; });

    const observer = new IntersectionObserver((entries) => {
        // Sort by position so stagger follows visual order
        const visible = entries.filter(e => e.isIntersecting);
        visible.forEach((entry, i) => {
            const card = entry.target;
            setTimeout(() => {
                card.classList.add('card-visible');
                // Once animation finishes, clean up so hover still works
                card.addEventListener('animationend', () => {
                    card.classList.remove('card-visible');
                    card.style.opacity = '1';
                }, { once: true });
                observer.unobserve(card);
            }, i * 60);
        });
    }, { threshold: 0.08 });

    cards.forEach(card => observer.observe(card));
}

// Expose showToast globally for inline use
window.showToast = showToast;
