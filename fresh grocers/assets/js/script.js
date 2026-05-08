// ============================================================
// FRESH GROCERS - Main Script (assets/js/script.js)
// ============================================================

// ---- PASSWORD TOGGLE ----
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// ---- AUTO DISMISS ALERTS (4 seconds) ----
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(function (el) {
            if (!el.querySelector('.no-auto-dismiss')) {
                try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch (e) { }
            }
        });
    }, 4000);
});

// ---- PASSWORD MATCH VALIDATION ----
document.addEventListener('DOMContentLoaded', function () {
    const pwd = document.getElementById('password');
    const confirm = document.getElementById('confirmpassword');
    const form = document.querySelector('form');
    if (form && pwd && confirm) {
        form.addEventListener('submit', function (e) {
            if (pwd.value !== confirm.value) {
                e.preventDefault();
                pwd.classList.add('is-invalid');
                confirm.classList.add('is-invalid');
                const existing = document.getElementById('pwd-match-error');
                if (!existing) {
                    const msg = document.createElement('div');
                    msg.id = 'pwd-match-error';
                    msg.className = 'text-danger small mt-1';
                    msg.innerText = 'Passwords do not match!';
                    confirm.closest('.input-group').after(msg);
                    confirm.focus();
                }
            }
        });
        confirm.addEventListener('input', function () {
            if (pwd.value === confirm.value) {
                pwd.classList.remove('is-invalid');
                confirm.classList.remove('is-invalid');
                const msg = document.getElementById('pwd-match-error');
                if (msg) msg.remove();
            }
        });
    }
});

// ---- QUANTITY CONTROLS ----
function increaseQty(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const max = parseInt(input.getAttribute('max')) || 9999;
    const current = parseInt(input.value) || 1;
    if (current < max) {
        input.value = current + 1;
    } else {
        input.classList.add('is-invalid');
        setTimeout(() => input.classList.remove('is-invalid'), 1000);
    }
}
function decreaseQty(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const min = parseInt(input.getAttribute('min')) || 1;
    const current = parseInt(input.value) || 1;
    if (current > min) input.value = current - 1;
}

// ---- CONFIRM DELETE ----
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

// ---- CONFIRM CART REMOVE ----
function confirmRemove() {
    return confirm('Remove this item from your cart?');
}

// ---- ACTIVE NAV HIGHLIGHT ----
document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage) && currentPage !== '') {
            link.classList.add('active', 'fw-bold');
        }
    });
});

// ---- TOAST NOTIFICATION ----
function showToast(message, type) {
    type = type || 'success';
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}


// ============================================================
// CSR PLACE ORDER — Product Rows + Summary
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const productRowsContainer = document.getElementById('product-rows');
    // STRICT GUARD: If product-rows doesn't exist, this is NOT the CSR place order page. Stop here.
    if (!productRowsContainer) return;

    const addRowBtn = document.getElementById('add-product-row');
    const templateHTML = document.getElementById('product-options') ? document.getElementById('product-options').innerHTML : '';
    const summaryContainer = document.getElementById('order-summary');
    const orderTotalEl = document.getElementById('order-total');
    const itemCountEl = document.getElementById('item-count');
    const addressInput = document.getElementById('delivery-address');
    const summaryAddress = document.getElementById('summary-address');
    const paymentSelect = document.getElementById('payment-method');
    const payStatusPreview = document.getElementById('pay-status-preview');
    const agentCardsEl = document.getElementById('agent-cards');
    const btnFind = document.getElementById('btn-find-agents');

    if (addressInput && summaryAddress) {
        addressInput.addEventListener('input', function () {
            summaryAddress.textContent = this.value.trim() || '—';
        });
        if (addressInput.value.trim()) summaryAddress.textContent = addressInput.value.trim();
    }

    function updatePayBadge() {
        if (!paymentSelect || !payStatusPreview) return;
        if (paymentSelect.value === 'Card') {
            payStatusPreview.className = 'badge bg-success shadow-sm px-3 py-2 rounded-pill';
            payStatusPreview.textContent = 'Paid';
        } else {
            payStatusPreview.className = 'badge bg-warning text-dark shadow-sm px-3 py-2 rounded-pill';
            payStatusPreview.textContent = 'Pending';
        }
    }
    if (paymentSelect) {
        paymentSelect.addEventListener('change', updatePayBadge);
        updatePayBadge();
    }

    function createRow() {
        const div = document.createElement('div');
        div.className = 'product-row row g-2 align-items-center mb-3 p-3 rounded-2 border border-secondary-subtle bg-white shadow-sm';
        div.style.borderLeft = '4px solid #0d6efd';
        div.innerHTML = `
            <div class="col-md-6 d-flex align-items-center gap-3">
                <div class="d-none d-md-block">
                    <img src="https://via.placeholder.com/52?text=?" class="rounded-2 border border-secondary-subtle row-thumb" style="width:52px;height:52px;object-fit:cover;" alt="Product">
                </div>
                <div class="flex-grow-1">
                    <select name="product_ids[]" class="form-select product-select fw-semibold" required>
                        ${templateHTML}
                    </select>
                    <small class="stock-hint text-muted d-block mt-1"></small>
                </div>
            </div>
            <div class="col-md-2 d-flex justify-content-center align-items-center">
                <div class="input-group shadow-sm qty-controls" style="width:120px;max-width:100%;height:38px;">
                    <button type="button" class="btn btn-outline-secondary qty-minus px-2" style="height:100%;">−</button>
                    <input type="number" name="quantities[]" class="form-control text-center qty-input fw-bold" value="1" min="1" style="max-width:60px;height:100%;">
                    <button type="button" class="btn btn-outline-secondary qty-plus px-2" style="height:100%;">+</button>
                </div>
            </div>
            <div class="col-md-2 d-flex justify-content-center align-items-center">
                <div class="fw-bold text-success row-subtotal">Rs. 0.00</div>
            </div>
            <div class="col-md-2 d-flex justify-content-end align-items-center">
                <button type="button" class="btn btn-light border border-danger-subtle text-danger shadow-sm remove-row rounded-2" style="height:38px;width:38px;padding:0;display:flex;align-items:center;justify-content:center;" title="Remove row">
                    <i class="bi bi-trash-fill fs-6"></i>
                </button>
            </div>
        `;
        productRowsContainer.appendChild(div);
        attachEvents(div);
    }

    function attachEvents(row) {
        const select = row.querySelector('.product-select');
        const qtyInput = row.querySelector('.qty-input');
        const qtyMinus = row.querySelector('.qty-minus');
        const qtyPlus = row.querySelector('.qty-plus');
        const removeBtn = row.querySelector('.remove-row');
        const thumb = row.querySelector('.row-thumb');
        const stockHint = row.querySelector('.stock-hint');

        select.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const price = parseFloat(opt.dataset.price) || 0;
            const stock = parseInt(opt.dataset.stock) || 0;
            const image = opt.dataset.image || 'https://via.placeholder.com/52?text=?';

            if (thumb) {
                thumb.src = image;
                thumb.onerror = function () { this.src = 'https://via.placeholder.com/52?text=?'; };
            }
            if (stockHint) {
                if (price > 0) {
                    stockHint.innerHTML = stock > 5 ? `<i class="bi bi-check-circle-fill text-success me-1"></i>${stock} in stock` : stock > 0 ? `<i class="bi bi-exclamation-circle-fill text-warning me-1"></i>Only ${stock} left!` : `<i class="bi bi-x-circle-fill text-danger me-1"></i>Out of stock`;
                } else { stockHint.textContent = ''; }
            }
            qtyInput.max = stock || 9999;
            if (stock > 0 && parseInt(qtyInput.value) > stock) qtyInput.value = stock;
            calculateAll();
        });

        qtyInput.addEventListener('input', function () {
            const opt = select.options[select.selectedIndex];
            const stock = parseInt(opt.dataset.stock) || 0;
            if (stock > 0 && parseInt(this.value) > stock) this.value = stock;
            if (parseInt(this.value) < 1 || isNaN(parseInt(this.value))) this.value = 1;
            calculateAll();
        });

        qtyMinus.addEventListener('click', function () {
            if (parseInt(qtyInput.value) > 1) { qtyInput.value = parseInt(qtyInput.value) - 1; calculateAll(); }
        });

        qtyPlus.addEventListener('click', function () {
            const opt = select.options[select.selectedIndex];
            const stock = parseInt(opt.dataset.stock) || 0;
            const cur = parseInt(qtyInput.value) || 1;
            if (stock === 0 || cur < stock) { qtyInput.value = cur + 1; calculateAll(); }
        });

        removeBtn.addEventListener('click', function () {
            if (productRowsContainer.querySelectorAll('.product-row').length > 1) {
                row.remove();
            } else {
                select.selectedIndex = 0;
                qtyInput.value = 1;
                if (thumb) thumb.src = 'https://via.placeholder.com/52?text=?';
                if (stockHint) stockHint.textContent = '';
            }
            calculateAll();
        });
    }

    function calculateAll() {
        const rows = productRowsContainer.querySelectorAll('.product-row');
        let total = 0, itemsCount = 0, summaryHTML = '';

        rows.forEach(function (row) {
            const select = row.querySelector('.product-select');
            const qtyInput = row.querySelector('.qty-input');
            const option = select ? select.options[select.selectedIndex] : null;
            const subtotalEl = row.querySelector('.row-subtotal');

            if (option && option.value) {
                const price = parseFloat(option.dataset.price) || 0;
                const image = option.dataset.image || '';
                const name = option.dataset.name || option.text.split('(')[0].trim();
                const stock = parseInt(option.dataset.stock) || 0;
                let q = parseInt(qtyInput.value) || 1;

                if (stock > 0 && q > stock) { q = stock; qtyInput.value = stock; }
                const sub = price * q;
                if (subtotalEl) subtotalEl.textContent = 'Rs. ' + sub.toLocaleString('en-LK', { minimumFractionDigits: 2 });
                total += sub; itemsCount += q;

                summaryHTML += `
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            ${image ? `<img src="${image}" class="rounded-2 border flex-shrink-0" style="width:38px;height:38px;object-fit:cover;" onerror="this.src='https://via.placeholder.com/38?text=?'" alt="${name}">` : ''}
                            <div style="line-height:1.3;">
                                <div class="fw-semibold text-dark" style="font-size:0.82rem;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${name}">${name}</div>
                                <div class="text-muted" style="font-size:0.73rem;">${q} × Rs. ${price.toLocaleString('en-LK', { minimumFractionDigits: 2 })}</div>
                            </div>
                        </div>
                        <span class="fw-bold text-success" style="font-size:0.9rem;white-space:nowrap;">Rs. ${sub.toLocaleString('en-LK', { minimumFractionDigits: 2 })}</span>
                    </div>`;
            }
            else {
                if (subtotalEl) subtotalEl.textContent = 'Rs. 0.00';
            }
        });

        if (summaryContainer) {
            summaryContainer.innerHTML = summaryHTML || `<p class="text-muted small text-center py-4 my-2 fw-semibold"><i class="bi bi-basket-fill d-block fs-1 mb-2 opacity-25"></i>Add products to see summary</p>`;
        }
        if (orderTotalEl) orderTotalEl.textContent = 'Rs. ' + total.toLocaleString('en-LK', { minimumFractionDigits: 2 });
        if (itemCountEl) itemCountEl.textContent = itemsCount;
    }

    createRow();
    if (addRowBtn) addRowBtn.addEventListener('click', createRow);

    function bindAgentCards() {
        document.querySelectorAll('.agent-card').forEach(function (card) {
            const fresh = card.cloneNode(true);
            card.parentNode.replaceChild(fresh, card);
            fresh.addEventListener('click', function () {
                document.querySelectorAll('.agent-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('.agent-radio');
                if (radio) radio.checked = true;
                const nameEl = this.querySelector('p.fw-semibold, p.fw-bold');
                const name = nameEl ? nameEl.textContent.trim() : '';
                const display = document.getElementById('agent-selected-name');
                const sumDisplay = document.getElementById('summary-agent');
                const infoBox = document.getElementById('agent-selected-info');
                if (display) display.textContent = name;
                if (sumDisplay) sumDisplay.textContent = name;
                if (infoBox) infoBox.classList.remove('d-none');
            });
        });
        const firstCard = document.querySelector('.agent-card');
        if (firstCard) firstCard.click();
    }
    bindAgentCards();

    // CSR Find Agents
    if (btnFind) {
        btnFind.addEventListener('click', function () {
            const addr = addressInput ? addressInput.value.trim() : '';
            if (!addr) { alert('Please type a delivery address first.'); return; }

            const originalHTML = btnFind.innerHTML;
            btnFind.innerHTML = '<span class="spinner-border spinner-border-sm mb-1"></span><small class="fw-bold">Finding...</small>';
            btnFind.disabled = true;

            const currentUrl = window.location.href.split('?')[0];

            fetch(`${currentUrl}?geocode_address=${encodeURIComponent(addr)}`)
                .then(r => {
                    const ct = r.headers.get("content-type");
                    if (ct && ct.includes("application/json")) return r.json();
                    throw new Error("Server returned an invalid response (HTML instead of JSON).");
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    if (!Array.isArray(data) || data.length === 0) throw new Error('Could not find coordinates. Try adding a city name.');

                    const lat = data[0].lat;
                    const lng = data[0].lon;
                    const latEl = document.getElementById('delivery-lat');
                    const lngEl = document.getElementById('delivery-lng');
                    if (latEl) latEl.value = lat;
                    if (lngEl) lngEl.value = lng;

                    return fetch(`${currentUrl}?ajax_agents=1&lat=${lat}&lng=${lng}`);
                })
                .then(res => res.text())
                .then(html => {
                    if (html && agentCardsEl) {
                        agentCardsEl.innerHTML = html;
                        bindAgentCards();
                        showToast('Found nearest delivery agents!', 'success');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error finding agents: ' + err.message);
                })
                .finally(() => {
                    btnFind.innerHTML = originalHTML;
                    btnFind.disabled = false;
                });
        });
    }
});


// ============================================================
// DELIVERY — Update Location (Leaflet Map)
// ============================================================
let fgmap = null;
let fgmarker = null;

function initMap(lat, lng) {
    try {
        if (fgmap) {
            fgmap.setView([lat, lng], 16);
            if (fgmarker) fgmarker.setLatLng([lat, lng]);
            else fgmarker = L.marker([lat, lng]).addTo(fgmap);
            return;
        }
        fgmap = L.map('map').setView([lat, lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors', maxZoom: 19
        }).addTo(fgmap);

        const orangeIcon = L.divIcon({
            html: `<div style="background:#fd7e14;width:20px;height:20px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>`,
            iconSize: [20, 20], iconAnchor: [10, 20], className: ''
        });
        fgmarker = L.marker([lat, lng], { icon: orangeIcon, draggable: true })
            .addTo(fgmap).bindPopup('<b>You are here</b>').openPopup();
        fgmarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            const latEl = document.getElementById('latitude');
            const lngEl = document.getElementById('longitude');
            if (latEl) latEl.value = pos.lat;
            if (lngEl) lngEl.value = pos.lng;
            reverseGeocode(pos.lat, pos.lng);
        });
    } catch (e) { console.error(e); }
}

function detectLocation() {
    const btn = document.getElementById('detect-btn');
    const status = document.getElementById('map-status');
    const accBadge = document.getElementById('accuracy-badge');
    const accText = document.getElementById('accuracy-text');

    if (!navigator.geolocation) {
        if (status) status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Geolocation not supported.</span>';
        return;
    }
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Detecting...'; }
    if (status) status.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Getting your exact GPS location...';

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const acc = Math.round(pos.coords.accuracy);
            const latEl = document.getElementById('latitude');
            const lngEl = document.getElementById('longitude');
            if (latEl) latEl.value = lat;
            if (lngEl) lngEl.value = lng;
            if (accBadge) {
                accBadge.classList.remove('d-none', 'bg-secondary', 'bg-success', 'bg-warning', 'bg-danger');
                accBadge.classList.add(acc < 50 ? 'bg-success' : acc < 150 ? 'bg-warning' : 'bg-danger');
            }
            if (accText) accText.textContent = acc + 'm accuracy';
            if (status) status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Location detected! Drag pin to adjust.</span>';
            initMap(lat, lng);
            reverseGeocode(lat, lng);
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Refresh Exact Location'; }
        },
        function (err) {
            const msgs = { 1: 'Permission denied — allow location in browser.', 2: 'Position unavailable — check GPS/network.', 3: 'Timed out — try again.' };
            if (status) status.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle me-1"></i>${msgs[err.code] || 'Location error.'}</span>`;
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-crosshair me-2"></i>Detect My Exact Location'; }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}
window.detectLocation = detectLocation;

function reverseGeocode(lat, lng) {
    const el = document.getElementById('location-text');
    if (el) el.value = 'Fetching exact address...';
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
        .then(r => r.json())
        .then(data => { if (data && data.display_name && el) el.value = data.display_name; })
        .catch(() => { if (el) el.value = `GPS: ${lat.toFixed(5)}, ${lng.toFixed(5)}`; });
}
window.reverseGeocode = reverseGeocode;

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('location-form');
    if (!form) return;
    form.addEventListener('submit', function () {
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const btn = form.querySelector('[name="update_location"]');
        if (btn) {
            const note = document.createElement('small');
            note.className = 'text-muted d-block mt-2 text-center';
            note.textContent = `Saving location at ${time}...`;
            btn.after(note);
        }
    });
});


// ============================================================
// CUSTOMER CHECKOUT — GPS + Agent Cards + Payment Options
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const btnGps = document.getElementById('btn-detect-gps');
    const btnFind = document.getElementById('btn-find-agents');
    const addrInput = document.getElementById('delivery-address');
    const agentCards = document.getElementById('agent-cards');
    const placeholder = document.getElementById('agent-placeholder');
    const gpsStatus = document.getElementById('gps-status');

    // STRICT GUARD: If there's no GPS button, we are NOT on the Customer Checkout page. Stop here!
    if (!btnGps) return;

    function bindAgentCards() {
        const cards = document.querySelectorAll('.agent-card');
        if (!cards) return;
        cards.forEach(card => {
            card.addEventListener('click', function () {
                cards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('.agent-radio');
                if (radio) radio.checked = true;
            });
        });
        const first = document.querySelector('.agent-card');
        if (first) first.click();
    }

    function loadAgents(lat, lng) {
        if (!agentCards || !placeholder) return;
        placeholder.classList.add('d-none');
        agentCards.innerHTML = `
            <div class="col-12 text-center py-3">
                <span class="spinner-border spinner-border-sm text-success me-2"></span>
                <span class="text-muted small">Finding all agents...</span>
            </div>`;

        const currentUrl = window.location.href.split('?')[0];
        fetch(`${currentUrl}?ajax_agents=1&lat=${lat}&lng=${lng}`)
            .then(r => r.text())
            .then(html => { agentCards.innerHTML = html; bindAgentCards(); })
            .catch(() => { agentCards.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Failed to load agents. Please try again.</div></div>'; });
    }

    if (btnGps) {
        btnGps.addEventListener('click', function () {
            if (!navigator.geolocation) {
                if (gpsStatus) gpsStatus.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Geolocation not supported.</small>';
                return;
            }
            btnGps.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Detecting your location...';
            btnGps.disabled = true;
            if (gpsStatus) gpsStatus.innerHTML = '';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const acc = Math.round(pos.coords.accuracy);
                    const latEl = document.getElementById('delivery-lat');
                    const lngEl = document.getElementById('delivery-lng');
                    if (latEl) latEl.value = lat;
                    if (lngEl) lngEl.value = lng;
                    if (gpsStatus) gpsStatus.innerHTML = `<small class="text-success"><i class="bi bi-check-circle me-1"></i>Location detected! ${acc}m accuracy</small>`;

                    const currentUrl = window.location.href.split('?')[0];
                    fetch(`${currentUrl}?reverse_geocode=1&lat=${lat}&lng=${lng}`)
                        .then(r => r.json())
                        .then(data => { if (data && data.display_name && addrInput) addrInput.value = data.display_name; })
                        .catch(() => { });

                    loadAgents(lat, lng);
                    btnGps.innerHTML = '<i class="bi bi-check-circle me-2"></i>Location Detected — Click to Refresh';
                    btnGps.disabled = false;
                },
                function (err) {
                    const msgs = { 1: 'Permission denied — allow location access.', 2: 'Position unavailable.', 3: 'Request timed out.' };
                    if (gpsStatus) gpsStatus.innerHTML = `<small class="text-danger"><i class="bi bi-x-circle me-1"></i>${msgs[err.code]}</small>`;
                    btnGps.innerHTML = '<i class="bi bi-crosshair me-2"></i>Use My Current Location (GPS)';
                    btnGps.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }

    if (btnFind) {
        btnFind.addEventListener('click', function () {
            const addr = addrInput ? addrInput.value.trim() : '';
            if (!addr) { alert('Please enter a delivery address first.'); return; }
            btnFind.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Searching address...';
            btnFind.disabled = true;

            const currentUrl = window.location.href.split('?')[0];

            fetch(`${currentUrl}?geocode_address=${encodeURIComponent(addr)}`)
                .then(r => {
                    const ct = r.headers.get("content-type");
                    if (ct && ct.includes("application/json")) return r.json();
                    throw new Error("Server returned invalid response (HTML instead of JSON).");
                })
                .then(data => {
                    if (data && data.error) throw new Error('Server: ' + data.error);
                    if (Array.isArray(data) && data.length > 0) {
                        const lat = data[0].lat;
                        const lng = data[0].lon;
                        const latEl = document.getElementById('delivery-lat');
                        const lngEl = document.getElementById('delivery-lng');
                        if (latEl) latEl.value = lat;
                        if (lngEl) lngEl.value = lng;
                        loadAgents(lat, lng);
                    } else throw new Error('Address not found. Add your city name.');
                })
                .catch(err => alert(err.message))
                .finally(() => {
                    btnFind.innerHTML = '<i class="bi bi-geo-alt me-2"></i>Find Delivery Agents using address above';
                    btnFind.disabled = false;
                });
        });
    }

    // Payment option cards
    document.querySelectorAll('.payment-option').forEach(label => {
        label.addEventListener('click', function () {
            document.querySelectorAll('.payment-option').forEach(l => l.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
});


// ============================================================
// CUSTOMER TRACK ORDER — Star Rating
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const starLabels = document.querySelectorAll('.star-label');
    const starRadios = document.querySelectorAll('input[name="rating_score"]');
    if (!starLabels.length || !starRadios.length) return;

    function updateStars(hoverValue) {
        starLabels.forEach((label, index) => {
            const icon = label.querySelector('i');
            if (!icon) return;
            if (index < hoverValue) {
                icon.classList.remove('bi-star');
                icon.classList.add('bi-star-fill');
            } else {
                icon.classList.remove('bi-star-fill');
                icon.classList.add('bi-star');
            }
        });
    }
    starLabels.forEach((label, index) => {
        label.addEventListener('mouseover', () => updateStars(index + 1));
        label.addEventListener('mouseout', () => {
            const checked = document.querySelector('input[name="rating_score"]:checked');
            updateStars(checked ? parseInt(checked.value) : 0);
        });
    });
    starRadios.forEach(radio => {
        radio.addEventListener('change', function () { updateStars(parseInt(this.value)); });
    });
});


// ============================================================
// ADMIN — CSR Edit Modal
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const editCsrModal = document.getElementById('editCsrModal');
    if (editCsrModal) {
        editCsrModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data-* attributes
            const id = button.getAttribute('data-id');
            const fname = button.getAttribute('data-fname');
            const lname = button.getAttribute('data-lname');
            const username = button.getAttribute('data-username');
            const email = button.getAttribute('data-email');

            // Update the modal's content.
            editCsrModal.querySelector('#edit_id').value = id;
            editCsrModal.querySelector('#edit_fname').value = fname;
            editCsrModal.querySelector('#edit_lname').value = lname;
            editCsrModal.querySelector('#edit_username').value = username;
            editCsrModal.querySelector('#edit_email').value = email;
            
            // Clear password field for security
            editCsrModal.querySelector('#edit_password').value = '';
        });
    }
});




// ============================================================
// ADMIN — Delivery Agents Edit Modal
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const editAgentModal = document.getElementById('editAgentModal');
    if (editAgentModal) {
        editAgentModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data-* attributes
            const id = button.getAttribute('data-id');
            const fname = button.getAttribute('data-fname');
            const lname = button.getAttribute('data-lname');
            const phone = button.getAttribute('data-phone');
            const email = button.getAttribute('data-email');
            const status = button.getAttribute('data-status');

            // Update the modal's content.
            editAgentModal.querySelector('#edit_id').value = id;
            editAgentModal.querySelector('#edit_fname').value = fname;
            editAgentModal.querySelector('#edit_lname').value = lname;
            editAgentModal.querySelector('#edit_phone').value = phone;
            editAgentModal.querySelector('#edit_email').value = email;
            
            // Handle the Toggle Switch (Active/Inactive)
            const statusSwitch = editAgentModal.querySelector('#edit_status');
            statusSwitch.checked = (status == "1");
            
            // Clear password field
            editAgentModal.querySelector('#edit_password').value = '';
        });
    }
});




// ============================================================
// ADMIN — Login Password Toggle
// ============================================================
function toggleLocalPassword() {
    const pwdInput = document.getElementById('adminPwd');
    const pwdIcon = document.getElementById('pwdIcon');
    if (!pwdInput || !pwdIcon) return;
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        pwdIcon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        pwdIcon.classList.add('text-success');
    } else {
        pwdInput.type = 'password';
        pwdIcon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        pwdIcon.classList.remove('text-success');
    }
}


// ============================================================
// ADMIN — Messages View Modal
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const viewModal = document.getElementById('viewMessageModal');
    if (!viewModal) return;
    viewModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const vmname = document.getElementById('vm-name');
        const vmdate = document.getElementById('vm-date');
        const vmphone = document.getElementById('vm-phone');
        const vmsubject = document.getElementById('vm-subject');
        const vmcontent = document.getElementById('vm-content');
        const emailLink = document.getElementById('vm-email');

        if (vmname) vmname.textContent = btn.getAttribute('data-name');
        if (vmdate) vmdate.textContent = btn.getAttribute('data-date');
        if (vmphone) vmphone.textContent = btn.getAttribute('data-phone');
        if (vmsubject) vmsubject.textContent = btn.getAttribute('data-subject');
        if (vmcontent) vmcontent.textContent = btn.getAttribute('data-content');

        if (emailLink) {
            const email = btn.getAttribute('data-email');
            if (email) {
                emailLink.textContent = email;
                emailLink.href = 'mailto:' + email;
                emailLink.classList.remove('text-muted');
            } else {
                emailLink.textContent = 'No email';
                emailLink.href = '';
                emailLink.classList.add('text-muted');
            }
        }
    });
});


// ============================================================
// ADMIN — Orders Assign Agent Modal
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const assignBtns = document.querySelectorAll('.assign-agent-btn');
    if (!assignBtns.length) return;
    assignBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const orderId = this.dataset.orderId;
            const agentId = this.dataset.agentId;
            const modal = document.getElementById('assignAgentModal');
            if (!modal) return;
            const orderInp = modal.querySelector('input[name="order_id"]');
            const select = modal.querySelector('select[name="agent_id"]');
            if (orderInp) orderInp.value = orderId;
            if (select && agentId) select.value = agentId;
        });
    });
});
