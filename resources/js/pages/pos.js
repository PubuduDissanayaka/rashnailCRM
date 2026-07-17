/**
 * POS Terminal — Mobile-First
 * Rash Nail Lounge CRM
 */
(function () {
    'use strict';

    // ============================
    // STATE
    // ============================
    const CS = window.currencySymbol || 'Rs.';
    let cart = [];
    let discountType = 'fixed';
    let discountAmount = 0;
    let appliedCoupons = [];
    let paymentMethod = 'cash';
    let isSplit = false;
    let receivedStr = '';
    let splitPayments = [];

    // ============================
    // DOM REFS
    // ============================
    const $ = (s) => document.querySelector(s);
    const $$ = (s) => document.querySelectorAll(s);

    // Service
    const servicesGrid = document.getElementById('services-grid');
    const packagesGrid = document.getElementById('packages-grid');
    const searchInput = document.getElementById('item-search');
    const filterRadios = $$('input[name="cf"]');

    // Cart
    const cartList = document.getElementById('cart-items-list');
    const emptyCart = document.getElementById('empty-cart');
    const cartCountBadge = document.getElementById('cart-count-badge');
    const subEl = document.getElementById('cart-subtotal');
    const discEl = document.getElementById('cart-discount');
    const taxEl = document.getElementById('cart-tax');
    const totalEl = document.getElementById('cart-total');
    const btnTotal = document.getElementById('btn-total');
    const discountInput = document.getElementById('discount-input');
    const discountLabel = document.getElementById('discount-label');
    const couponInput = document.getElementById('coupon-input');
    const couponBtn = document.getElementById('coupon-validate-btn');
    const couponFb = document.getElementById('coupon-feedback');
    const saleNotes = document.getElementById('sale-notes');
    const completeBtn = document.getElementById('complete-sale-btn');
    const holdBtn = document.getElementById('hold-btn');
    const clearBtn = document.getElementById('clear-btn');
    const customerSelect = document.getElementById('customer-select');
    const staffSelect = document.getElementById('staff-select');

    // Mobile
    const cartBar = document.getElementById('cart-bar');
    const cartBarTotal = document.getElementById('cart-bar-total');
    const cartBarCount = document.getElementById('cart-bar-count');
    const cartBarView = document.getElementById('cart-bar-view');
    const cartBarPay = document.getElementById('cart-bar-pay');
    const cartOverlay = document.getElementById('cart-overlay');
    const cartDrawer = document.getElementById('cart-drawer');
    const drawerClose = document.getElementById('drawer-close');
    const drawerContent = document.getElementById('drawer-content');
    const drawerCount = document.getElementById('drawer-count');

    // Payment
    const payModal = document.getElementById('payModal');
    const pmTotal = document.getElementById('pm-total');
    const pmChange = document.getElementById('pm-change');
    const pmSub = document.getElementById('pm-sub');
    const pmDisc = document.getElementById('pm-disc');
    const pmTax = document.getElementById('pm-tax');
    const payCash = document.getElementById('pay-cash');
    const payCard = document.getElementById('pay-card');
    const payCashAmt = document.getElementById('pay-cash-amt');
    const payCardAmt = document.getElementById('pay-card-amt');
    const splitSwitch = document.getElementById('split-switch');
    const splitBadge = document.getElementById('split-badge');
    const splitArea = document.getElementById('split-area');
    const payNotes = document.getElementById('pay-notes');
    const confirmBtn = document.getElementById('confirm-pay-btn');
    const quickAmounts = document.getElementById('quick-amounts');
    const exactBtn = document.getElementById('exact-btn');

    // Customer modal
    const custForm = document.getElementById('quick-customer-form');
    const custFname = document.getElementById('cust-fname');
    const custLname = document.getElementById('cust-lname');
    const custPhone = document.getElementById('cust-phone');
    const custEmail = document.getElementById('cust-email');
    const custErr = document.getElementById('cust-err');
    const custErrList = document.getElementById('cust-err-list');
    const custSaveBtn = document.getElementById('cust-save-btn');

    let payModalInstance = null;
    if (payModal) {
        try { payModalInstance = new bootstrap.Modal(payModal, { backdrop: 'static', keyboard: false }); } catch (e) {}
    }

    // ============================
    // HELPERS
    // ============================
    function fmt(n) { return CS + parseFloat(n || 0).toFixed(2); }
    function round2(n) { return Math.round((n || 0) * 100) / 100; }
    function sum(arr) { return arr.reduce((s, i) => s + (i.price * i.quantity), 0); }
    function toast(msg, type) {
        const t = document.getElementById('toast-tpl');
        if (!t) return;
        const clone = t.cloneNode(true);
        clone.style.display = '';
        clone.querySelector('.toast-msg').textContent = msg;
        clone.classList.add('bg-' + (type === 'error' ? 'danger' : 'success'), 'text-white');
        document.querySelector('.toast-container').appendChild(clone);
        const bsToast = new bootstrap.Toast(clone, { delay: 3000 });
        bsToast.show();
        clone.addEventListener('hidden.bs.toast', () => clone.remove());
    }

    // ============================
    // SEARCH & FILTER
    // ============================
    function filterCards() {
        const q = (searchInput?.value || '').toLowerCase();
        const cat = (document.querySelector('input[name="cf"]:checked') || {}).value || 'all';
        const activeTab = document.querySelector('.tab-pane.active');
        const grid = activeTab?.id === 'tab-packages' ? packagesGrid : servicesGrid;
        if (!grid) return;
        grid.querySelectorAll('.svc-card').forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const type = card.dataset.type;
            const match = name.includes(q) && (cat === 'all' || type === cat);
            card.style.display = match ? '' : 'none';
        });
    }
    if (searchInput) searchInput.addEventListener('input', filterCards);
    filterRadios.forEach(r => r.addEventListener('change', filterCards));

    // Tab change re-filters
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(t => {
        t.addEventListener('shown.bs.tab', filterCards);
    });

    // ============================
    // CART OPERATIONS
    // ============================
    function addToCart(item) {
        const existing = cart.find(i => i.id === item.id && i.type === item.type);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({ id: item.id, type: item.type, name: item.name, price: parseFloat(item.price) || 0, quantity: 1 });
        }
        renderCart();
        toast(item.name + ' added');
    }

    function removeFromCart(idx) {
        cart.splice(idx, 1);
        renderCart();
    }

    function updateQty(idx, delta) {
        if (idx < 0 || idx >= cart.length) return;
        cart[idx].quantity = Math.max(1, (cart[idx].quantity || 1) + delta);
        renderCart();
    }

    function clearCart() {
        cart = [];
        discountAmount = 0;
        discountType = 'fixed';
        appliedCoupons = [];
        if (discountInput) discountInput.value = '';
        if (couponInput) couponInput.value = '';
        if (couponFb) couponFb.innerHTML = '';
        renderCart();
    }

    // Service card clicks
    function bindServiceClicks() {
        document.querySelectorAll('.svc-card').forEach(card => {
            card.addEventListener('click', function () {
                this.classList.add('adding');
                setTimeout(() => this.classList.remove('adding'), 300);
                addToCart({
                    id: this.dataset.id,
                    type: this.dataset.type,
                    name: this.dataset.name,
                    price: this.dataset.price
                });
            });
        });
    }
    bindServiceClicks();

    // ============================
    // RENDER CART
    // ============================
    function renderCart() {
        const currencySymbol = CS;
        const hasItems = cart.length > 0;

        // Toggle empty state
        if (emptyCart) emptyCart.style.display = hasItems ? 'none' : '';
        if (cartList) cartList.innerHTML = hasItems ? cart.map((item, idx) => `
            <div class="cart-item">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-qty">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window._posDecQty(${idx})">−</button>
                    <span class="fw-bold">${item.quantity}</span>
                    <button class="btn btn-outline-secondary btn-sm" onclick="window._posIncQty(${idx})">+</button>
                </div>
                <div class="cart-item-price">${currencySymbol}${(item.price * item.quantity).toFixed(2)}</div>
                <button class="btn btn-sm btn-outline-danger border-0" onclick="window._posRemove(${idx})"><i class="ti ti-x"></i></button>
            </div>
        `).join('') : '';

        updateTotals();
    }

    // Expose for inline onclick
    window._posRemove = removeFromCart;
    window._posDecQty = (i) => updateQty(i, -1);
    window._posIncQty = (i) => updateQty(i, 1);

    // ============================
    // TOTALS
    // ============================
    function updateTotals() {
        const subtotal = sum(cart);
        const discVal = discountType === 'percent' ? subtotal * (discountAmount / 100) : discountAmount;
        const finalDisc = Math.min(discVal, subtotal);
        const taxRate = parseFloat(window.posSettings?.taxRate || 0) / 100;
        const taxable = Math.max(0, subtotal - finalDisc);
        const tax = taxable * taxRate;
        const total = taxable + tax;
        const couponDisc = appliedCoupons.reduce((s, c) => s + (parseFloat(c.discount_amount) || 0), 0);
        const finalTotal = Math.max(0, total - couponDisc);

        if (subEl) subEl.textContent = fmt(subtotal);
        if (discEl) discEl.textContent = '-' + fmt(finalDisc + couponDisc);
        if (taxEl) taxEl.textContent = fmt(tax);
        if (totalEl) totalEl.textContent = fmt(finalTotal);
        if (btnTotal) btnTotal.textContent = fmt(finalTotal);

        // Mobile bar
        if (cartBarTotal) cartBarTotal.textContent = fmt(finalTotal);
        if (cartBarCount) cartBarCount.textContent = cart.length;
        if (cartCountBadge) cartCountBadge.textContent = cart.length;
        if (drawerCount) drawerCount.textContent = cart.length;

        // Show/hide mobile bar
        if (cartBar) {
            if (cart.length > 0 && window.innerWidth < 992) {
                cartBar.style.display = 'flex';
            } else if (window.innerWidth < 992) {
                cartBar.style.display = 'none';
            }
        }
    }

    // ============================
    // DISCOUNT HANDLERS
    // ============================
    if (discountInput) {
        discountInput.addEventListener('input', function () {
            discountAmount = parseFloat(this.value) || 0;
            updateTotals();
        });
    }
    document.querySelectorAll('[data-dtype]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            discountType = this.dataset.dtype;
            if (discountLabel) discountLabel.textContent = discountType === 'fixed' ? CS : '%';
            updateTotals();
        });
    });

    // ============================
    // COUPON
    // ============================
    if (couponBtn) {
        couponBtn.addEventListener('click', async function () {
            const code = couponInput?.value?.trim();
            if (!code) { toast('Enter a coupon code', 'error'); return; }
            if (!customerSelect) { toast('Select a customer first', 'error'); return; }
            const customerId = customerSelect.value;
            try {
                const res = await fetch('/coupons/validate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ code, customer_id: customerId, cart_total: sum(cart) })
                });
                const data = await res.json();
                if (data.valid) {
                    appliedCoupons.push({ id: data.coupon.id, code, discount_amount: data.discount_amount });
                    if (couponFb) couponFb.innerHTML = `<span class="text-success">✅ Coupon applied: -${CS}${parseFloat(data.discount_amount).toFixed(2)}</span>`;
                    if (couponInput) couponInput.value = '';
                    updateTotals();
                    toast('Coupon applied!');
                } else {
                    if (couponFb) couponFb.innerHTML = `<span class="text-danger">${data.message || 'Invalid coupon'}</span>`;
                }
            } catch (e) {
                toast('Failed to validate coupon', 'error');
            }
        });
    }

    // ============================
    // MOBILE CART DRAWER
    // ============================
    function openDrawer() {
        if (!cartDrawer || !cartOverlay) return;
        // Copy cart sidebar content
        const sidebar = document.getElementById('cart-body');
        if (sidebar && drawerContent) {
            drawerContent.innerHTML = sidebar.innerHTML;
            // Rebind events in drawer
            drawerContent.querySelectorAll('[data-dtype]').forEach(el => {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    discountType = this.dataset.dtype;
                    if (discountLabel) discountLabel.textContent = discountType === 'fixed' ? CS : '%';
                    updateTotals();
                });
            });
            const discInp = drawerContent.querySelector('#discount-input');
            if (discInp) discInp.addEventListener('input', function () {
                discountAmount = parseFloat(this.value) || 0;
                updateTotals();
            });
            const coupBtn = drawerContent.querySelector('#coupon-validate-btn');
            if (coupBtn) coupBtn.addEventListener('click', couponBtn?.click.bind(couponBtn));
            // Bind cart item buttons (they use onclick via window.*)
        }
        cartDrawer.classList.add('open');
        cartOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (drawerCount) drawerCount.textContent = cart.length;
    }

    function closeDrawer() {
        if (!cartDrawer || !cartOverlay) return;
        cartDrawer.classList.remove('open');
        cartOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (cartBarView) cartBarView.addEventListener('click', openDrawer);
    if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
    if (cartOverlay) cartOverlay.addEventListener('click', closeDrawer);

    // Mobile Pay button
    if (cartBarPay) {
        cartBarPay.addEventListener('click', function () {
            closeDrawer();
            setTimeout(() => { if (completeBtn) completeBtn.click(); }, 250);
        });
    }

    // ============================
    // PAYMENT MODAL
    // ============================
    function getTotal() {
        const t = totalEl?.textContent?.replace(/[^0-9.]/g, '').replace(/^\.+/, '') || '0';
        return parseFloat(t) || 0;
    }

    function getSplitTotal() {
        return splitPayments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);
    }

    function getRemaining() {
        return Math.max(0, getTotal() - getSplitTotal());
    }

    // ============================
    // PAYMENT AMOUNT (numeric only)
    // ============================
    let receivedAmount = 0;
    const receivedInput = document.getElementById('pm-received');
    let keypadBuffer = '';

    function updateReceivedDisplay() {
        const display = receivedAmount.toFixed(2);
        const input = document.getElementById('pm-received');
        if (input && input.value !== display) {
            input.value = display;
        }
        const total = getTotal();
        const effectiveReceived = isSplit ? getSplitTotal() : receivedAmount;
        const change = Math.max(0, effectiveReceived - total);
        const chEl = document.getElementById('pm-change');
        const tEl = document.getElementById('pm-total');
        if (chEl) chEl.textContent = fmt(change);
        if (tEl) tEl.textContent = fmt(total);
    }

    function resetReceivedAmount(val) {
        receivedAmount = Math.max(0, parseFloat(val) || 0);
        updateReceivedDisplay();
    }

    function keypadToAmount() {
        let cleaned = keypadBuffer.replace(/^0+(?!\.|$)/, '');
        if (cleaned === '' || cleaned === '.') cleaned = '0';
        const num = parseFloat(cleaned);
        if (!isNaN(num) && num >= 0) {
            receivedAmount = Math.round(num * 100) / 100;
        }
        updateReceivedDisplay();
    }

    // OLD syncPaymentDisplay → updateReceivedDisplay alias
    function syncPaymentDisplay() { updateReceivedDisplay(); }

    // Open modal
    if (completeBtn) {
        completeBtn.addEventListener('click', function () {
            if (cart.length === 0) { toast('Cart is empty', 'error'); return; }
            const total = getTotal();

            // Create modal instance on demand
            const pmEl = document.getElementById('payModal');
            if (!pmEl) { toast('Payment system error', 'error'); return; }
            let modalInstance = null;
            try { modalInstance = bootstrap.Modal.getInstance(pmEl) || new bootstrap.Modal(pmEl, { backdrop: 'static', keyboard: false }); } catch(e) {}
            if (!modalInstance) { toast('Payment system error', 'error'); return; }

            // Copy totals — re-query DOM to ensure fresh references
            const pmTotalEl = document.getElementById('pm-total');
            const pmSubEl = document.getElementById('pm-sub');
            const pmDiscEl = document.getElementById('pm-disc');
            const pmTaxEl = document.getElementById('pm-tax');
            const cartSub = document.getElementById('cart-subtotal')?.textContent || '$0.00';
            const cartDisc = document.getElementById('cart-discount')?.textContent || '-$0.00';
            const cartTx = document.getElementById('cart-tax')?.textContent || '$0.00';
            const cartTtl = document.getElementById('cart-total')?.textContent || '$0.00';
            if (pmSubEl) pmSubEl.textContent = cartSub;
            if (pmDiscEl) pmDiscEl.textContent = cartDisc;
            if (pmTaxEl) pmTaxEl.textContent = cartTx;
            if (pmTotalEl) pmTotalEl.textContent = cartTtl;
            // Force via setTimeout as fallback
            setTimeout(function() {
                const e = document.getElementById('pm-total');
                if (e && e.textContent !== cartTtl) e.textContent = cartTtl;
                const se = document.getElementById('pm-sub');
                if (se && se.textContent !== cartSub) se.textContent = cartSub;
            }, 50);

            // Reset state
            resetReceivedAmount(total);
            keypadBuffer = total.toFixed(2);
            paymentMethod = 'cash';
            isSplit = false;
            splitPayments = [{ method: 'cash', amount: total, reference: '' }];
            splitSwitch.checked = false;
            splitArea.classList.add('d-none');
            splitBadge.classList.add('d-none');
            payCash.classList.add('active');
            payCard.classList.remove('active');
            payCashAmt.textContent = fmt(total);
            payCardAmt.textContent = '──';
            payNotes.value = '';

            updateReceivedDisplay();
            generateQuickAmounts(total);
            if (modalInstance) modalInstance.show();
        });
    }

    // Cash/Card toggle
    [payCash, payCard].forEach(btn => {
        if (!btn) return;
        btn.addEventListener('click', function () {
            const method = this.dataset.method;
            paymentMethod = method;
            isSplit = false;
            splitSwitch.checked = false;
            splitArea.classList.add('d-none');
            splitBadge.classList.add('d-none');

            document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const total = getTotal();
            resetReceivedAmount(total);
            keypadBuffer = total.toFixed(2);
            splitPayments = [{ method, amount: total, reference: '' }];

            if (method === 'cash') {
                payCashAmt.textContent = fmt(total);
                payCardAmt.textContent = '──';
            } else {
                payCardAmt.textContent = fmt(total);
                payCashAmt.textContent = '──';
            }
            syncPaymentDisplay();
        });
    });

    // Split toggle
    if (splitSwitch) {
        splitSwitch.addEventListener('change', function () {
            isSplit = this.checked;
            const total = getTotal();
            if (isSplit) {
                splitArea.classList.remove('d-none');
                const half = Math.ceil(total / 2);
                splitPayments = [
                    { method: 'cash', amount: half, reference: '' },
                    { method: 'card', amount: Math.floor(total / 2), reference: '' }
                ];
                const diff = total - splitPayments.reduce((s, p) => s + p.amount, 0);
                if (diff > 0) splitPayments[0].amount += diff;
                renderSplitPayments();
            } else {
                splitArea.classList.add('d-none');
                splitBadge.classList.add('d-none');
                const method = document.querySelector('.pay-btn.active')?.dataset?.method || 'cash';
                splitPayments = [{ method, amount: getTotal(), reference: '' }];
                resetReceivedAmount(getTotal());
                keypadBuffer = getTotal().toFixed(2);
                updateReceivedDisplay();
            }
        });
    }

    function renderSplitPayments() {
        const total = getTotal();
        const sum = getSplitTotal();
        const remaining = getRemaining();

        splitArea.innerHTML = splitPayments.map((p, i) => `
            <div class="card mb-1 split-row">
                <div class="card-body py-1 px-2">
                    <div class="d-flex gap-1 align-items-start">
                        <select class="form-select form-select-sm split-method" data-idx="${i}">
                            <option value="cash" ${p.method === 'cash' ? 'selected' : ''}>Cash</option>
                            <option value="card" ${p.method === 'card' ? 'selected' : ''}>Card</option>
                            <option value="mobile" ${p.method === 'mobile' ? 'selected' : ''}>Mobile</option>
                            <option value="check" ${p.method === 'check' ? 'selected' : ''}>Check</option>
                        </select>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text px-1">${CS}</span>
                            <input type="text" class="form-control text-end fw-bold split-amount" value="${parseFloat(p.amount).toFixed(2)}" data-idx="${i}">
                        </div>
                        <input type="text" class="form-control form-control-sm ref-input" value="${p.reference || ''}" data-idx="${i}" placeholder="Ref">
                        <button class="btn btn-sm btn-outline-danger border-0 split-remove" data-idx="${i}" ${splitPayments.length <= 1 ? 'disabled' : ''}><i class="ti ti-x"></i></button>
                    </div>
                </div>
            </div>
        `).join('');

        // Update badge
        if (remaining <= 0) {
            splitBadge.textContent = 'Covered ✓';
            splitBadge.className = 'badge bg-success fs-xs';
            splitBadge.classList.remove('d-none');
        } else {
            splitBadge.textContent = `Short ${CS}${remaining.toFixed(2)}`;
            splitBadge.className = 'badge bg-danger fs-xs';
            splitBadge.classList.remove('d-none');
        }

        // Bind events
        splitArea.querySelectorAll('.split-method').forEach(sel => {
            sel.addEventListener('change', function () {
                const i = parseInt(this.dataset.idx);
                splitPayments[i].method = this.value;
                renderSplitPayments();
            });
        });
        splitArea.querySelectorAll('.split-amount').forEach(inp => {
            inp.addEventListener('input', function () {
                const i = parseInt(this.dataset.idx);
                splitPayments[i].amount = parseFloat(this.value.replace(/[^0-9.]/g, '')) || 0;
                updateReceivedDisplay();
                renderSplitPayments();
            });
        });
        splitArea.querySelectorAll('.ref-input').forEach(inp => {
            inp.addEventListener('input', function () {
                const i = parseInt(this.dataset.idx);
                splitPayments[i].reference = this.value;
            });
        });
        splitArea.querySelectorAll('.split-remove').forEach(btn => {
            btn.addEventListener('click', function () {
                const i = parseInt(this.dataset.idx);
                if (splitPayments.length <= 1) return;
                splitPayments.splice(i, 1);
                renderSplitPayments();
                updateReceivedDisplay();
            });
        });

        // Add button
        if (!splitArea.querySelector('.add-split-btn')) {
            const addBtn = document.createElement('button');
            addBtn.className = 'btn btn-outline-primary btn-sm w-100 mt-1 add-split-btn';
            addBtn.innerHTML = '<i class="ti ti-plus me-1"></i> Add Method';
            addBtn.addEventListener('click', function () {
                const used = splitPayments.map(p => p.method);
                const avail = ['card', 'mobile', 'check', 'cash'].find(m => !used.includes(m)) || 'card';
                splitPayments.push({ method: avail, amount: 0, reference: '' });
                renderSplitPayments();
            });
            splitArea.appendChild(addBtn);
        }
    }

    // ============================
    // KEYPAD
    // Keypad
    document.querySelectorAll('[data-k]').forEach(btn => {
        btn.addEventListener('click', function () {
            const k = this.dataset.k;
            if (isSplit) return;
            if (k === 'bs') {
                keypadBuffer = keypadBuffer.slice(0, -1);
            } else if (k === 'clr') {
                keypadBuffer = '';
            } else if (k === '.') {
                if (!keypadBuffer.includes('.')) keypadBuffer += '.';
            } else {
                // Digit — start fresh if buffer is auto-filled (ends with .00)
                if (keypadBuffer.endsWith('.00') && keypadBuffer.length > 3) {
                    keypadBuffer = k;
                } else {
                    if (keypadBuffer.includes('.')) {
                        const parts = keypadBuffer.split('.');
                        if (parts[1]?.length >= 2) return; // max 2 decimals
                    }
                    keypadBuffer += k;
                }
            }
            keypadToAmount();
        });
    });

    // Exact amount button
    if (exactBtn) {
        exactBtn.addEventListener('click', function () {
            if (isSplit) return;
            resetReceivedAmount(getTotal());
            keypadBuffer = receivedAmount.toFixed(2);
        });
    }

    // Direct input (fallback if user types in the field)
    // NOTE: This only updates display, never modifies receivedAmount directly
    if (receivedInput) {
        receivedInput.addEventListener('input', function () {
            if (isSplit) return;
            const raw = this.value.replace(/[^0-9.]/g, '');
            const num = parseFloat(raw);
            if (!isNaN(num) && num >= 0) {
                // Only update if it makes sense (prevent mangling from browser locale)
                const rounded = Math.round(num * 100) / 100;
                if (rounded >= 0 && rounded <= 100000) {
                    receivedAmount = rounded;
                }
            }
            // Always reformat display to prevent the 0.25 vs 25.00 bug
            const input = document.getElementById('pm-received');
            if (input) input.value = receivedAmount.toFixed(2);
            updateReceivedDisplay();
        });
        receivedInput.addEventListener('focus', function() { this.select(); });
        receivedInput.addEventListener('blur', function() {
            this.value = receivedAmount.toFixed(2);
        });
    }

    // ============================
    // QUICK AMOUNTS (LKR Notes)
    // ============================
    function generateQuickAmounts(total) {
        if (!quickAmounts) return;
        const LKR = [50, 100, 500, 1000, 2000, 5000];
        if (total <= 0) { quickAmounts.innerHTML = '<button class="btn btn-outline-success btn-sm quick-btn" data-a="50">50</button><button class="btn btn-outline-success btn-sm quick-btn" data-a="100">100</button><button class="btn btn-outline-success btn-sm quick-btn" data-a="500">500</button>'; bindQuick(); return; }
        const idx = LKR.findIndex(n => n >= total);
        if (idx === -1) {
            const base = Math.ceil(total / 1000) * 1000;
            quickAmounts.innerHTML = [base, base + 1000, base + 2000, base + 5000].map(a => `<button class="btn btn-outline-success btn-sm quick-btn" data-a="${a}">${CS}${a}</button>`).join('');
        } else {
            let amts = LKR.slice(idx, idx + 4);
            if (amts[0] > total * 1.5 && total >= 20) {
                const mid = Math.ceil(total / 10) * 10 + 10;
                if (mid < amts[0]) amts.unshift(mid);
            }
            quickAmounts.innerHTML = amts.map(a => `<button class="btn btn-outline-success btn-sm quick-btn" data-a="${a}">${CS}${a}</button>`).join('');
        }
        bindQuick();
    }

    function bindQuick() {
        quickAmounts.querySelectorAll('.quick-btn').forEach(b => {
            b.addEventListener('click', function () {
                if (isSplit) { splitPayments[0].amount = parseFloat(this.dataset.a); renderSplitPayments(); updateReceivedDisplay(); return; }
                resetReceivedAmount(parseFloat(this.dataset.a));
                keypadBuffer = receivedAmount.toFixed(2);
            });
        });
    }

    function bindQuick() {
        quickAmounts.querySelectorAll('.quick-btn').forEach(b => {
            b.addEventListener('click', function () {
                if (isSplit) { splitPayments[0].amount = parseFloat(this.dataset.a); renderSplitPayments(); updateReceivedDisplay(); return; }
                resetReceivedAmount(parseFloat(this.dataset.a));
                keypadBuffer = receivedAmount.toFixed(2);
            });
        });
    }

    // ============================
    // CONFIRM PAYMENT
    // ============================
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function () {
            const total = getTotal();
            let received, paymentsData;

            if (isSplit) {
                const sum = getSplitTotal();
                if (splitPayments.some(p => !p.amount || parseFloat(p.amount) <= 0)) { toast('All split amounts must be > 0', 'error'); return; }
                if (sum < total) { toast('Split total must cover the bill', 'error'); return; }
                received = sum;
                paymentsData = splitPayments.map(p => ({ method: p.method, amount: parseFloat(p.amount) || 0, reference: p.reference || null, notes: null }));
            } else {
                if (receivedAmount < total) { toast('Amount must cover the total', 'error'); return; }
                paymentsData = [{ method: paymentMethod, amount: receivedAmount, reference: null, notes: null }];
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const data = {
                customer_id: customerSelect?.value || null,
                staff_id: staffSelect?.value || null,
                items: cart.map(i => ({ type: i.type, id: i.id, quantity: i.quantity, price: i.price })),
                payments: paymentsData,
                discount_amount: discountAmount,
                discount_type: discountType,
                coupon_discount_amount: appliedCoupons.reduce((s, c) => s + (parseFloat(c.discount_amount) || 0), 0),
                applied_coupons: appliedCoupons.map(c => ({ id: c.id, code: c.code, discount_amount: c.discount_amount })),
                notes: saleNotes?.value || null,
                _token: csrf
            };

            this.disabled = true;
            this.innerHTML = '<i class="ti ti-loader-2 animate-spin me-1"></i>Processing...';

            try {
                const res = await fetch('/pos/sale', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    if (modalInstance) modalInstance.hide();
                    // Success
                    let pmtsHtml = '';
                    if (paymentsData.length > 0) {
                        pmtsHtml = '<hr class="my-1"><div class="small text-start px-2">' +
                            paymentsData.map(p => `<div class="d-flex justify-content-between"><span class="text-capitalize">${p.method}</span><span>${CS}${p.amount.toFixed(2)}</span></div>`).join('') +
                            '</div>';
                    }
                    Swal.fire({
                        icon: 'success', title: 'Sale Completed!',
                        html: `<p>Sale #${result.sale_number || result.sale_id}</p><p class="fs-4 fw-bold text-success">Change: ${CS}${result.change_amount.toFixed(2)}</p>${pmtsHtml}`,
                        showConfirmButton: true, showCancelButton: true,
                        confirmButtonText: 'View Receipt', cancelButtonText: 'Close'
                    }).then(r => { if (r.isConfirmed && result.sale_id) window.open('/pos/receipt/' + result.sale_id, '_blank'); });
                    clearCart();
                } else {
                    toast(result.message || 'Sale failed', 'error');
                }
            } catch (e) {
                toast('Network error', 'error');
            }
            this.disabled = false;
            this.innerHTML = 'Confirm & Complete';
        });
    }

    // ============================
    // HOLD / CLEAR
    // ============================
    if (holdBtn) {
        holdBtn.addEventListener('click', function () {
            if (cart.length === 0) { toast('Cart is empty', 'error'); return; }
            try {
                localStorage.setItem('pos_hold', JSON.stringify({ cart, discountAmount, discountType, appliedCoupons }));
                toast('Order held');
                clearCart();
            } catch (e) { toast('Failed to hold', 'error'); }
        });
    }

    // Restore held order
    try {
        const held = localStorage.getItem('pos_hold');
        if (held) {
            const data = JSON.parse(held);
            if (data.cart?.length > 0) {
                cart = data.cart;
                discountAmount = data.discountAmount || 0;
                discountType = data.discountType || 'fixed';
                appliedCoupons = data.appliedCoupons || [];
                renderCart();
                localStorage.removeItem('pos_hold');
                setTimeout(() => toast('Held order restored'), 300);
            }
        }
    } catch (e) {}

    if (clearBtn) clearBtn.addEventListener('click', function () {
        if (cart.length === 0) return;
        if (confirm('Clear cart?')) clearCart();
    });

    // ============================
    // CUSTOMER QUICK ADD
    // ============================
    if (custForm) {
        custForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            custSaveBtn.disabled = true;
            custSaveBtn.innerHTML = '<i class="ti ti-loader-2 animate-spin me-1"></i>Saving...';
            custErr.classList.add('d-none');

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch('/customers/quick-add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({
                        first_name: custFname?.value, last_name: custLname?.value,
                        phone: custPhone?.value, email: custEmail?.value
                    })
                });
                const data = await res.json();
                if (data.success) {
                    // Add to select
                    const opt = document.createElement('option');
                    opt.value = data.customer.id;
                    opt.text = data.customer.first_name + ' ' + data.customer.last_name;
                    if (customerSelect) { customerSelect.appendChild(opt); customerSelect.value = data.customer.id; }
                    const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                    if (modal) modal.hide();
                    toast('Customer added');
                    custForm.reset();
                } else {
                    custErr.classList.remove('d-none');
                    custErrList.innerHTML = Object.values(data.errors || { 'error': [data.message || 'Failed'] }).map(e => `<li>${e}</li>`).join('');
                }
            } catch (e) { toast('Failed to save customer', 'error'); }
            custSaveBtn.disabled = false;
            custSaveBtn.innerHTML = '<i class="ti ti-check me-1"></i>Save';
        });
    }

    // ============================
    // RESIZE HANDLER
    // ============================
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            if (cartDrawer) cartDrawer.classList.remove('open');
            if (cartOverlay) cartOverlay.classList.remove('show');
            if (cartBar) cartBar.style.display = 'none';
            document.body.style.overflow = '';
        }
        updateTotals();
    });

    // ============================
    // INIT
    // ============================
    document.addEventListener('DOMContentLoaded', function() {
        renderCart();
        // Show desktop cart on large screens
        if (window.innerWidth >= 992) {
            const dc = document.getElementById('cart-desktop');
            if (dc) dc.style.display = 'block';
        }
        // Initialize modal after DOM ready
        const pmEl = document.getElementById('payModal');
        if (pmEl && typeof bootstrap !== 'undefined') {
            try { payModalInstance = new bootstrap.Modal(pmEl, { backdrop: 'static', keyboard: false }); } catch(e) { console.warn('Modal init:', e); }
        }
        console.log('POS initialized ✅');
    });

})();