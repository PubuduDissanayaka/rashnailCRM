/**
 * Enterprise Point of Sale (POS) System JavaScript
 * Professional POS with live search, visual payment, numeric keypad, and cart persistence
 */

// Import required libraries
import Swal from 'sweetalert2';
import Choices from 'choices.js';

document.addEventListener('DOMContentLoaded', function () {
    // Initialize customer selection with Choices.js
    const customerSelect = document.getElementById('customer-select');
    let customerChoices = null;

    if (customerSelect) {
        customerChoices = new Choices(customerSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search for customer...',
            shouldSort: true,
            itemSelectText: 'Press to select',
            placeholder: true,
            noResultsText: 'No customers found',
            noChoicesText: 'No customers to choose from'
        });
    }

    // Initialize staff selection with Choices.js
    const staffSelect = document.getElementById('staff-select');
    let staffChoices = null;

    if (staffSelect) {
        staffChoices = new Choices(staffSelect, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search for staff member...',
            shouldSort: true,
            itemSelectText: 'Press to select',
            placeholder: true,
            noResultsText: 'No staff members found',
            noChoicesText: 'No staff members to choose from'
        });
    }

    // ========================================
    // QUICK ADD CUSTOMER MODAL HANDLER
    // ========================================
    const quickAddCustomerForm = document.getElementById('quick-add-customer-form');
    const customerModal = document.getElementById('customerModal');
    const saveCustomerBtn = document.getElementById('save-customer-btn');
    const customerErrorAlert = document.getElementById('customer-error-alert');
    const customerErrorList = document.getElementById('customer-error-list');

    if (quickAddCustomerForm) {
        quickAddCustomerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Hide previous errors
            customerErrorAlert.classList.add('d-none');
            customerErrorList.innerHTML = '';

            // Disable submit button
            saveCustomerBtn.disabled = true;
            saveCustomerBtn.innerHTML = '<i class="ti ti-loader"></i> Saving...';

            // Get form data
            const formData = new FormData(quickAddCustomerForm);
            const data = {
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                phone: formData.get('phone'),
                email: formData.get('email') || null,
                gender: formData.get('gender') || null,
            };

            try {
                // Make AJAX request to create customer
                const response = await fetch('/pos/customers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Success: Add customer to Choices.js dropdown and select it
                    if (customerChoices) {
                        customerChoices.setChoices([
                            {
                                value: result.customer.id,
                                label: `${result.customer.first_name} ${result.customer.last_name}`,
                                selected: true
                            }
                        ], 'value', 'label', false);
                    } else {
                        // Fallback: Add to native select if Choices.js failed
                        const newOption = new Option(
                            `${result.customer.first_name} ${result.customer.last_name}`,
                            result.customer.id,
                            true,
                            true
                        );
                        customerSelect.add(newOption);
                    }

                    // Close modal
                    const modalInstance = bootstrap.Modal.getInstance(customerModal);
                    modalInstance.hide();

                    // Reset form
                    quickAddCustomerForm.reset();

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Customer Added!',
                        text: `${result.customer.first_name} ${result.customer.last_name} has been added successfully.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    // Validation errors
                    if (result.errors) {
                        customerErrorList.innerHTML = '';
                        Object.values(result.errors).forEach(errorArray => {
                            errorArray.forEach(error => {
                                const li = document.createElement('li');
                                li.textContent = error;
                                customerErrorList.appendChild(li);
                            });
                        });
                        customerErrorAlert.classList.remove('d-none');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Failed to create customer.'
                        });
                    }
                }
            } catch (error) {
                console.error('Customer creation error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred. Please try again.'
                });
            } finally {
                // Re-enable submit button
                saveCustomerBtn.disabled = false;
                saveCustomerBtn.innerHTML = '<i class="ti ti-check me-1"></i>Save Customer';
            }
        });

        // Reset form and errors when modal is closed
        if (customerModal) {
            customerModal.addEventListener('hidden.bs.modal', function () {
                quickAddCustomerForm.reset();
                customerErrorAlert.classList.add('d-none');
                customerErrorList.innerHTML = '';
            });
        }
    }

    // Define global variables for cart and elements
    let cart = []; // Initialize cart array
    let discountType = 'fixed'; // 'fixed' or 'percent'
    let discountAmount = 0;
    let amountReceivedValue = 0;
    let appliedCoupons = []; // array of validated coupons

    // Helper function to escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return text.replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    // Cart elements - updated IDs to match new view
    const cartItemsEl = document.getElementById('cart-items-container');
    const cartSubtotalEl = document.getElementById('cart-subtotal');
    const cartDiscountEl = document.getElementById('cart-discount');
    const cartTaxEl = document.getElementById('cart-tax');
    const cartTotalEl = document.getElementById('cart-total');
    const btnTotalEl = document.getElementById('btn-total');
    const cartCountEl = document.getElementById('cart-count');
    const emptyCartState = document.getElementById('empty-cart-state');

    // Button elements
    const completeSaleBtn = document.getElementById('complete-sale-btn');
    const clearCartBtn = document.getElementById('clear-cart-btn');
    const holdOrderBtn = document.getElementById('hold-order-btn');

    // Discount elements
    const discountAmountInput = document.getElementById('discount-amount');
    const discountTypeLabel = document.getElementById('discount-type-label');

    // Coupon elements
    const couponCodeInput = document.getElementById('coupon-code');
    const validateCouponBtn = document.getElementById('validate-coupon-btn');
    const couponErrorEl = document.getElementById('coupon-error');
    const appliedCouponsContainer = document.getElementById('applied-coupons-container');

    // ====================
    // UTILITY FUNCTIONS
    // ====================

    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        const container = document.querySelector('.toast-container');
        if (!container) return;

        const template = document.getElementById('toast-template').cloneNode(true);
        template.id = '';
        template.style.display = 'block';

        const bgClasses = {
            'success': 'bg-success text-white',
            'error': 'bg-danger text-white',
            'warning': 'bg-warning',
            'info': 'bg-info text-white'
        };

        const classes = bgClasses[type].split(' ');
        for (const cls of classes) {
            template.classList.add(cls);
        }

        const icons = {
            'success': 'ti-check-circle',
            'error': 'ti-x-circle',
            'warning': 'ti-alert-triangle',
            'info': 'ti-info-circle'
        };

        const iconEl = template.querySelector('.toast-body i');
        if (iconEl) {
            iconEl.className = `ti ${icons[type]} me-2`;
        }

        template.querySelector('.toast-message').textContent = message;
        container.appendChild(template);

        const toast = new bootstrap.Toast(template, { delay: 3000 });
        toast.show();

        template.addEventListener('hidden.bs.toast', () => template.remove());
    }

    /**
     * Calculate discount based on type and amount
     */
    function calculateDiscount(subtotal) {
        if (discountType === 'percent') {
            return subtotal * (discountAmount / 100);
        }
        return discountAmount;
    }

    /**
     * Calculate total coupon discount from applied coupons.
     */
    function calculateCouponDiscount(subtotal) {
        let total = 0;
        appliedCoupons.forEach(coupon => {
            total += coupon.discount_amount || 0;
        });
        return total;
    }

    /**
     * Render applied coupons in the UI.
     */
    function renderAppliedCoupons() {
        if (!appliedCouponsContainer) return;
        
        if (appliedCoupons.length === 0) {
            appliedCouponsContainer.innerHTML = '';
            return;
        }

        const currencySymbol = window.posSettings?.currencySymbol || '$';
        let html = '<div class="list-group list-group-flush">';
        appliedCoupons.forEach((coupon, index) => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <div>
                        <strong>${escapeHtml(coupon.code)}</strong>
                        <small class="text-muted d-block">${escapeHtml(coupon.name)}</small>
                        <small class="text-success">-${currencySymbol}${coupon.discount_amount.toFixed(2)}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeCoupon(${index})" title="Remove coupon">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            `;
        });
        html += '</div>';
        appliedCouponsContainer.innerHTML = html;
    }

    /**
     * Validate a coupon code via AJAX.
     */
    async function validateCoupon() {
        if (!couponCodeInput || !validateCouponBtn) return;

        const code = couponCodeInput.value.trim();
        if (!code) {
            showToast('Please enter a coupon code', 'error');
            return;
        }

        // Check if coupon already applied
        if (appliedCoupons.find(c => c.code === code)) {
            showToast('Coupon already applied', 'error');
            return;
        }

        // Disable button and show loading
        validateCouponBtn.disabled = true;
        validateCouponBtn.innerHTML = '<i class="ti ti-loader animate-spin me-1"></i> Validating';

        // Prepare request data
        const customerId = customerSelect ? customerSelect.value : null;
        const locationId = null; // TODO: get location from POS
        const items = cart.map(item => ({
            type: item.type,
            id: item.id,
            price: item.price,
            quantity: item.quantity,
        }));

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/api/coupons/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    code,
                    customer_id: customerId,
                    location_id: locationId,
                    items
                })
            });

            const result = await response.json();

            if (result.valid) {
                // Add coupon to applied list
                appliedCoupons.push({
                    id: result.coupon.id,
                    code: result.coupon.code,
                    name: result.coupon.name,
                    type: result.coupon.type,
                    discount_value: result.coupon.discount_value,
                    max_discount_amount: result.coupon.max_discount_amount,
                    stackable: result.coupon.stackable,
                    discount_amount: result.discount_amount
                });
                couponCodeInput.value = '';
                showToast(result.message || 'Coupon applied successfully', 'success');
                renderAppliedCoupons();
                updateTotals();
                saveCartToStorage();
            } else {
                // Show errors
                const errorMsg = result.errors?.join(', ') || 'Invalid coupon';
                showToast(errorMsg, 'error');
                if (couponErrorEl) {
                    couponErrorEl.textContent = errorMsg;
                    couponErrorEl.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Coupon validation error:', error);
            showToast('Network error. Please try again.', 'error');
        } finally {
            validateCouponBtn.disabled = false;
            validateCouponBtn.innerHTML = 'Validate';
            if (couponErrorEl) couponErrorEl.style.display = 'none';
        }
    }

    /**
     * Remove an applied coupon.
     */
    function removeCoupon(index) {
        if (index >= 0 && index < appliedCoupons.length) {
            const removed = appliedCoupons.splice(index, 1)[0];
            showToast(`Coupon ${removed.code} removed`, 'info');
            renderAppliedCoupons();
            updateTotals();
            saveCartToStorage();
        }
    }

    // Make removeCoupon globally accessible for inline onclick
    window.removeCoupon = removeCoupon;

    /**
     * Save cart state to localStorage
     */
    function saveCartToStorage() {
        const state = {
            cart: cart,
            customer_id: customerSelect ? customerSelect.value : null,
            discount_amount: discountAmount,
            discount_type: discountType,
            applied_coupons: appliedCoupons,
            timestamp: Date.now()
        };
        localStorage.setItem('pos_cart_state', JSON.stringify(state));
    }

    /**
     * Load cart state from localStorage
     */
    function loadCartFromStorage() {
        const stored = localStorage.getItem('pos_cart_state');
        if (!stored) return;

        try {
            const state = JSON.parse(stored);
            const hours = (Date.now() - state.timestamp) / (1000 * 60 * 60);

            // Only restore if less than 24 hours old
            if (hours > 24) {
                localStorage.removeItem('pos_cart_state');
                return;
            }

            cart = state.cart || [];
            discountAmount = state.discount_amount || 0;
            discountType = state.discount_type || 'fixed';
            appliedCoupons = state.applied_coupons || [];

            if (state.customer_id && customerChoices) {
                customerChoices.setChoiceByValue(state.customer_id);
            }

            if (discountAmountInput) {
                discountAmountInput.value = discountAmount;
            }

            if (discountTypeLabel) {
                discountTypeLabel.textContent = discountType === 'fixed' ? (window.posSettings?.currencySymbol || '$') : '%';
            }

            renderCart();
            renderAppliedCoupons();
            updateTotals();
            showToast('Previous cart restored', 'info');
        } catch (error) {
            console.error('Error loading cart from storage:', error);
            localStorage.removeItem('pos_cart_state');
        }
    }

    // ====================
    // CART FUNCTIONS
    // ====================

    /**
     * Add service/package to cart
     */
    function addToCart(item) {
        // Check if item already exists in cart
        const existingItem = cart.find(cartItem =>
            cartItem.id === item.id &&
            cartItem.type === item.type &&
            cartItem.price === item.price
        );

        if (existingItem) {
            existingItem.quantity += 1;
            showToast(`Updated ${item.name} quantity`, 'success');
        } else {
            cart.push({
                id: item.id,
                type: item.type, // 'service' or 'package'
                name: item.name,
                price: parseFloat(item.price),
                originalPrice: parseFloat(item.price), // Store original price for comparison
                quantity: 1
            });
            showToast(`${item.name} added to cart`, 'success');
        }

        renderCart();
        updateTotals();
        saveCartToStorage();
    }

    /**
     * Remove item from cart
     */
    function removeFromCart(index) {
        const item = cart[index];
        cart.splice(index, 1);
        renderCart();
        updateTotals();
        saveCartToStorage();
        showToast(`${item.name} removed from cart`, 'info');
    }

    /**
     * Update item quantity in cart
     */
    function updateQuantity(index, newQuantity) {
        if (newQuantity <= 0) {
            removeFromCart(index);
        } else {
            cart[index].quantity = newQuantity;
            renderCart();
            updateTotals();
            saveCartToStorage();
        }
    }

    /**
     * Render cart items with animations and editable prices
     */
    function renderCart() {
        if (cart.length === 0) {
            if (cartItemsEl) cartItemsEl.innerHTML = '';
            if (emptyCartState) emptyCartState.style.display = 'block';
            return;
        }

        if (emptyCartState) emptyCartState.style.display = 'none';
        if (!cartItemsEl) return;

        const currencySymbol = window.posSettings?.currencySymbol || '$';

        cartItemsEl.innerHTML = '';
        cart.forEach((item, index) => {
            const cartItemEl = document.createElement('div');
            cartItemEl.className = 'cart-item p-3 mb-2 bg-light rounded-2';
            cartItemEl.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-semibold">${escapeHtml(item.name)}</h6>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <span class="input-group-text">${escapeHtml(currencySymbol)}</span>
                                <input type="number"
                                       class="form-control item-price-input"
                                       data-index="${index}"
                                       value="${item.price.toFixed(2)}"
                                       step="0.01"
                                       min="0">
                            </div>
                            <small class="text-muted">×</span> ${item.quantity}</small>
                            <small class="text-primary fw-bold">
                                = ${escapeHtml(currencySymbol)}${(item.price * item.quantity).toFixed(2)}
                            </small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-2">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, ${item.quantity - 1})">
                                <i class="ti ti-minus"></i>
                            </button>
                            <button class="btn btn-outline-secondary disabled" style="min-width: 40px;">
                                ${item.quantity}
                            </button>
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, ${item.quantity + 1})">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})"
                                data-bs-toggle="tooltip" title="Remove item">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
                ${item.price !== item.originalPrice ?
                    `<div class="text-warning fs-xs">
                        <i class="ti ti-alert-circle me-1"></i>
                        Custom price (Original: ${escapeHtml(currencySymbol)}${item.originalPrice.toFixed(2)})
                    </div>` : ''}
            `;
            cartItemsEl.appendChild(cartItemEl);

            // Add slide-in animation
            setTimeout(() => cartItemEl.classList.add('new'), 10);
        });

        // Add event listeners for price inputs
        document.querySelectorAll('.item-price-input').forEach(input => {
            input.addEventListener('change', function () {
                const index = parseInt(this.dataset.index);
                const newPrice = parseFloat(this.value) || 0;
                updateItemPrice(index, newPrice);
            });
        });

        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
    }

    /**
     * Update item price in cart
     */
    function updateItemPrice(index, newPrice) {
        if (newPrice < 0) {
            showToast('Price cannot be negative', 'error');
            renderCart();
            return;
        }

        cart[index].price = newPrice;
        renderCart();
        updateTotals();
        saveCartToStorage();
        showToast('Price updated', 'success');
    }

    // Make it globally available
    window.updateItemPrice = updateItemPrice;

    /**
     * Update cart totals with discount calculation
     */
    function updateTotals() {
        const currencySymbol = window.posSettings?.currencySymbol || '$';

        if (cart.length === 0) {
            if (cartSubtotalEl) cartSubtotalEl.textContent = `${currencySymbol}0.00`;
            if (cartDiscountEl) cartDiscountEl.textContent = `-${currencySymbol}0.00`;
            if (cartTaxEl) cartTaxEl.textContent = `${currencySymbol}0.00`;
            if (cartTotalEl) cartTotalEl.textContent = `${currencySymbol}0.00`;
            if (btnTotalEl) btnTotalEl.textContent = `${currencySymbol}0.00`;
            if (cartCountEl) cartCountEl.textContent = '0';
            return;
        }

        // Calculate subtotal
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        // Calculate manual discount
        const manualDiscount = calculateDiscount(subtotal);
        // Calculate coupon discount
        const couponDiscount = calculateCouponDiscount(subtotal);
        const totalDiscount = manualDiscount + couponDiscount;

        // Calculate tax on discounted amount
        const taxableAmount = subtotal - totalDiscount;
        const taxRate = window.posSettings?.taxRate || 0;
        const taxAmount = taxableAmount * (taxRate / 100);

        // Calculate total
        const total = taxableAmount + taxAmount;

        // Update DOM with proper currency formatting
        if (cartSubtotalEl) cartSubtotalEl.textContent = `${currencySymbol}${subtotal.toFixed(2)}`;
        if (cartDiscountEl) cartDiscountEl.textContent = `-${currencySymbol}${totalDiscount.toFixed(2)}`;
        if (cartTaxEl) cartTaxEl.textContent = `${currencySymbol}${taxAmount.toFixed(2)}`;
        if (cartTotalEl) cartTotalEl.textContent = `${currencySymbol}${total.toFixed(2)}`;
        if (btnTotalEl) btnTotalEl.textContent = `${escapeHtml(currencySymbol)}${total.toFixed(2)}`;
        if (cartCountEl) cartCountEl.textContent = cart.length;
    }

    // ====================
    // EVENT LISTENERS
    // ====================

    /**
     * Product card click handlers with pulse animation
     */
    document.querySelectorAll('.pos-product-card').forEach(card => {
        card.addEventListener('click', function () {
            // Add pulse animation
            this.classList.add('adding');
            setTimeout(() => {
                this.classList.remove('adding');
            }, 300);

            const serviceId = this.dataset.serviceId;
            const serviceType = this.dataset.serviceType;
            const serviceName = this.dataset.serviceName;
            const servicePrice = this.dataset.servicePrice;

            addToCart({
                id: serviceId,
                type: serviceType,
                name: escapeHtml(serviceName),
                price: servicePrice
            });
        });
    });

    /**
     * Discount type toggle
     */
    document.querySelectorAll('[data-discount-type]').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            discountType = this.dataset.discountType;
            if (discountTypeLabel) {
                discountTypeLabel.textContent = discountType === 'fixed' ? (window.posSettings?.currencySymbol || '$') : '%';
            }
            updateTotals();
            saveCartToStorage();
        });
    });

    /**
     * Discount amount input
     */
    if (discountAmountInput) {
        discountAmountInput.addEventListener('input', function () {
            discountAmount = parseFloat(this.value) || 0;
            updateTotals();
            saveCartToStorage();
        });
    }

    /**
     * Category filter handlers
     */
    document.querySelectorAll('[name="category-filter"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const category = this.value;
            document.querySelectorAll('.pos-product-card').forEach(card => {
                const type = card.dataset.serviceType;
                const col = card.closest('.col');
                if (col) {
                    if (category === 'all' || type === category) {
                        col.style.display = 'block';
                    } else {
                        col.style.display = 'none';
                    }
                }
            });
        });
    });

    /**
     * Coupon validation button click
     */
    if (validateCouponBtn) {
        validateCouponBtn.addEventListener('click', validateCoupon);
    }

    /**
     * Coupon code input Enter key press
     */
    if (couponCodeInput) {
        couponCodeInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validateCoupon();
            }
        });
    }

    // ====================
    // PAYMENT MODAL - ENTERPRISE EDITION
    // ====================

    const paymentModal = document.getElementById('paymentModal');
    let paymentModalInstance = null;

    if (paymentModal) {
        paymentModalInstance = new bootstrap.Modal(paymentModal);
    }

    // Payment state management
    let paymentState = {
        amountReceived: 0,
        amountReceivedString: '',
        currentStep: 1,
        paymentMethod: 'cash',
        paymentReference: '',
        paymentNotes: '',
        soundEnabled: window.posSettings?.enableSoundEffects ?? true
    };

    /**
     * Format currency with commas (1234.56 → 1,234.56)
     */
    function formatCurrency(value) {
        if (!value) return '0.00';

        const parts = value.toString().split('.');
        const dollars = parts[0] || '0';
        const cents = parts[1] || '';

        // Add commas: 1234 → 1,234
        const formatted = dollars.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        return cents ? `${formatted}.${cents.padEnd(2, '0')}` : `${formatted}.00`;
    }

    /**
     * Generate smart quick amounts based on total
     */
    function generateSmartQuickAmounts(total) {
        const amounts = [];

        if (total <= 0) return [20, 50, 100];

        const roundUpAmount = (value) => {
            if (value <= 10) return Math.ceil(value);
            if (value <= 20) return Math.ceil(value / 5) * 5;
            if (value <= 100) return Math.ceil(value / 10) * 10;
            if (value <= 500) return Math.ceil(value / 25) * 25;
            if (value <= 1000) return Math.ceil(value / 50) * 50;
            return Math.ceil(value / 100) * 100;
        };

        // Nearest round number
        amounts.push(roundUpAmount(total));

        // +15% rounded
        amounts.push(roundUpAmount(total * 1.15));

        // Nice round number or +30%
        if (total < 50) {
            amounts.push(50);
        } else if (total < 100) {
            amounts.push(100);
        } else {
            amounts.push(roundUpAmount(total * 1.3));
        }

        // Higher round number
        if (total < 50) {
            amounts.push(100);
        } else if (total < 100) {
            amounts.push(150);
        } else if (total < 500) {
            amounts.push(500);
        } else {
            amounts.push(roundUpAmount(total * 1.5));
        }

        // Remove duplicates and return up to 4 amounts
        return [...new Set(amounts)].slice(0, 4);
    }

    /**
     * Generate quick amount buttons
     */
    function generateQuickAmountButtons(total) {
        const container = document.getElementById('quick-amounts-container');
        if (!container) return;

        const mode = window.posSettings?.quickAmountsMode || 'smart';
        const currencySymbol = window.posSettings?.currencySymbol || '$';
        let amounts = [];

        if (mode === 'fixed') {
            amounts = window.posSettings?.quickAmountsFixed || [20, 50, 100];
        } else if (mode === 'percentage') {
            const percentages = window.posSettings?.quickAmountsPercentages || [105, 110, 120];
            amounts = percentages.map(p => Math.ceil(total * p / 100));
        } else {
            // Smart mode (default)
            amounts = generateSmartQuickAmounts(total);
        }

        container.innerHTML = amounts.map(amount => `
            <div class="col-3">
                <button class="btn btn-success btn-lg w-100 quick-amount-btn" data-amount="${amount}">
                    ${currencySymbol}${amount}
                </button>
            </div>
        `).join('');

        // Attach event listeners
        container.querySelectorAll('.quick-amount-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const amount = parseFloat(this.dataset.amount);
                paymentState.amountReceivedString = amount.toFixed(2);
                syncDisplayFromState();
                animateButton(this);
            });
        });
    }

    /**
     * Play keypad sound effect
     */
    function playKeypadSound() {
        if (!paymentState.soundEnabled) return;

        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.05);

            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.05);
        } catch (e) {
            // Ignore audio errors
        }
    }

    /**
     * Animate button on click
     */
    function animateButton(btn) {
        btn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            btn.style.transform = '';
        }, 100);
    }

    /**
     * Format a numeric string with thousand-separator commas
     */
    function formatWithCommas(numStr) {
        if (!numStr) return '';
        const parts = numStr.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    /**
     * Canonical way to push state → display. Every code path that changes
     * paymentState.amountReceivedString MUST call this afterward.
     */
    function syncDisplayFromState() {
        const amountInput = document.getElementById('amount-received-display');
        if (amountInput) {
            amountInput.value = formatWithCommas(paymentState.amountReceivedString);
        }
        paymentState.amountReceived = parseFloat(paymentState.amountReceivedString) || 0;
        updatePaymentDisplay();
    }

    /**
     * Handle keypad input
     */
    function handleKeypadInput(key) {
        playKeypadSound();

        if (key === 'clear') {
            paymentState.amountReceivedString = '';
        } else if (key === 'backspace') {
            paymentState.amountReceivedString = paymentState.amountReceivedString.slice(0, -1);
        } else if (key === '.') {
            if (!paymentState.amountReceivedString.includes('.')) {
                paymentState.amountReceivedString += '.';
            }
        } else {
            // Prevent more than 2 decimal places
            if (paymentState.amountReceivedString.includes('.')) {
                const parts = paymentState.amountReceivedString.split('.');
                if (parts[1]?.length >= 2) return;
            }
            paymentState.amountReceivedString += key;
        }

        syncDisplayFromState();
    }

    /**
     * Update change display + visual feedback (reads from paymentState only)
     */
    function updatePaymentDisplay() {
        const currencySymbol = window.posSettings?.currencySymbol || '$';
        const modalTotalEl = document.getElementById('modal-total');
        const total = parseFloat(modalTotalEl?.dataset?.total || modalTotalEl?.textContent.replace(currencySymbol, '').replace(/,/g, '') || 0);
        const received = paymentState.amountReceived;
        const change = Math.max(0, received - total);

        const amountReceivedDisplay = document.getElementById('amount-received-display');
        const changeDisplay = document.getElementById('change-display');

        if (amountReceivedDisplay) {
            // Visual feedback — only valid when actually sufficient
            if (received > 0 && received >= total) {
                amountReceivedDisplay.classList.remove('is-invalid');
                amountReceivedDisplay.classList.add('is-valid');
            } else {
                amountReceivedDisplay.classList.remove('is-valid');
                amountReceivedDisplay.classList.remove('is-invalid');
            }
        }

        // Always update change display
        if (changeDisplay) {
            changeDisplay.textContent = `${currencySymbol}${formatWithCommas(change.toFixed(2))}`;
        }
    }

    /**
     * Update payment method specific fields
     */
    function updatePaymentMethodFields(method) {
        paymentState.paymentMethod = method;

        // Hide all fields first
        document.querySelectorAll('.payment-fields').forEach(field => {
            field.style.display = 'none';
        });

        // Show fields for selected method
        const methodField = document.querySelector(`.payment-fields[data-method="${method}"]`);
        if (methodField) {
            methodField.style.display = 'block';
        }
    }

    /**
     * Validate payment before preview
     */
    function validatePayment() {
        const currencySymbol = window.posSettings?.currencySymbol || '$';
        const modalTotalEl = document.getElementById('modal-total');
        const total = parseFloat(modalTotalEl?.dataset?.total || modalTotalEl?.textContent.replace(currencySymbol, '').replace(/,/g, '') || 0);
        const amount = paymentState.amountReceived;

        // Check minimum amount
        if (amount < total) {
            showToast('Amount must be equal to or greater than total', 'error');
            const amountDisplay = document.getElementById('amount-received-display');
            if (amountDisplay) {
                amountDisplay.classList.add('is-invalid');
                amountDisplay.style.animation = 'shake 0.5s';
                setTimeout(() => amountDisplay.style.animation = '', 500);
            }
            return false;
        }

        // Check maximum amount
        const maxAmount = window.posSettings?.maxPaymentAmount || 100000;
        if (amount > maxAmount) {
            showToast(`Maximum payment amount is ${currencySymbol}${maxAmount.toLocaleString()}`, 'error');
            return false;
        }

        // Check reference number requirements
        const method = paymentState.paymentMethod;
        const requireRef = window.posSettings?.requireReference?.[method] ?? false;
        const referenceField = document.getElementById(`${method === 'bank_transfer' ? 'bank' : method}-reference`);

        if (requireRef && referenceField) {
            paymentState.paymentReference = referenceField.value.trim();
            if (!paymentState.paymentReference) {
                showToast(`Reference number required for ${method.replace('_', ' ')} payments`, 'error');
                referenceField.focus();
                referenceField.classList.add('is-invalid');
                return false;
            }
            // Validate format (alphanumeric, dash, underscore, hash)
            if (!/^[A-Za-z0-9\-_#]+$/.test(paymentState.paymentReference)) {
                showToast('Reference number can only contain letters, numbers, dash, underscore, and hash', 'error');
                referenceField.focus();
                referenceField.classList.add('is-invalid');
                return false;
            }
            referenceField.classList.remove('is-invalid');
        }

        // Get payment notes
        paymentState.paymentNotes = document.getElementById('payment-notes')?.value.trim() || '';

        return true;
    }

    /**
     * Generate receipt preview HTML
     */
    function generateReceiptPreview() {
        const currencySymbol = window.posSettings?.currencySymbol || '$';
        const businessName = window.posSettings?.businessName || 'Business Name';
        const total = parseFloat(document.getElementById('modal-total')?.textContent.replace(currencySymbol, '').replace(/,/g, '') || 0);
        const change = Math.max(0, paymentState.amountReceived - total);

        // Build items list
        let itemsHtml = cart.map(item => `
            <div class="receipt-item">
                <div class="d-flex justify-content-between">
                    <span>${item.name}</span>
                    <span>${currencySymbol}${(item.price * item.quantity).toFixed(2)}</span>
                </div>
                <div class="receipt-item-details text-muted small">
                    ${item.quantity} x ${currencySymbol}${item.price.toFixed(2)}
                </div>
            </div>
        `).join('');

        return `
            <div class="receipt-header text-center mb-3">
                <h4 class="fw-bold">${businessName}</h4>
                <p class="small text-muted mb-0">${new Date().toLocaleString()}</p>
            </div>
            <hr>
            <div class="receipt-items mb-3">
                ${itemsHtml}
            </div>
            <hr>
            <div class="receipt-totals mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Subtotal:</span>
                    <span>${document.getElementById('modal-subtotal')?.textContent || '$0.00'}</span>
                </div>
                <div class="d-flex justify-content-between mb-1 text-success">
                    <span>Discount:</span>
                    <span>${document.getElementById('modal-discount')?.textContent || '-$0.00'}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Tax:</span>
                    <span>${document.getElementById('modal-tax')?.textContent || '$0.00'}</span>
                </div>
                <div class="d-flex justify-content-between fw-bold fs-5 pt-2 border-top">
                    <span>Total:</span>
                    <span>${currencySymbol}${total.toFixed(2)}</span>
                </div>
            </div>
            <hr>
            <div class="receipt-payment mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Payment Method:</span>
                    <span class="text-capitalize">${paymentState.paymentMethod.replace('_', ' ')}</span>
                </div>
                ${paymentState.paymentReference ? `
                <div class="d-flex justify-content-between mb-1">
                    <span>Reference:</span>
                    <span>${paymentState.paymentReference}</span>
                </div>
                ` : ''}
                <div class="d-flex justify-content-between mb-1">
                    <span>Amount Received:</span>
                    <span>${currencySymbol}${paymentState.amountReceived.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between fw-bold text-success fs-5 pt-2 border-top">
                    <span>Change:</span>
                    <span>${currencySymbol}${change.toFixed(2)}</span>
                </div>
            </div>
        `;
    }

    /**
     * Navigate between steps
     */
    function goToStep(step) {
        paymentState.currentStep = step;

        const step1 = document.getElementById('payment-step-1');
        const step2 = document.getElementById('payment-step-2');
        const step1Footer = document.getElementById('step-1-footer');
        const step2Footer = document.getElementById('step-2-footer');
        const modalTitle = document.getElementById('modal-step-title');

        if (step === 1) {
            if (step1) step1.style.display = 'block';
            if (step2) step2.style.display = 'none';
            if (step1Footer) step1Footer.style.display = 'flex';
            if (step2Footer) step2Footer.style.display = 'none';
            if (modalTitle) modalTitle.textContent = 'Complete Payment';
        } else if (step === 2) {
            if (step1) step1.style.display = 'none';
            if (step2) step2.style.display = 'block';
            if (step1Footer) step1Footer.style.display = 'none';
            if (step2Footer) step2Footer.style.display = 'flex';
            if (modalTitle) modalTitle.textContent = 'Receipt Preview';

            // Generate receipt preview
            const previewContent = document.getElementById('receipt-preview-content');
            if (previewContent) {
                previewContent.innerHTML = generateReceiptPreview();
            }
        }
    }

    /**
     * Complete sale button - Opens payment modal
     */
    if (completeSaleBtn) {
        completeSaleBtn.addEventListener('click', function () {
            if (cart.length === 0) {
                showToast('Cart is empty', 'error');
                return;
            }

            // Copy totals to modal
            const modalSubtotal = document.getElementById('modal-subtotal');
            const modalDiscount = document.getElementById('modal-discount');
            const modalTax = document.getElementById('modal-tax');
            const modalTotal = document.getElementById('modal-total');

            if (modalSubtotal) modalSubtotal.textContent = cartSubtotalEl.textContent;
            if (modalDiscount) modalDiscount.textContent = cartDiscountEl.textContent;
            if (modalTax) modalTax.textContent = cartTaxEl.textContent;
            if (modalTotal) {
                modalTotal.textContent = cartTotalEl.textContent;
                // Store raw numeric total for reliable parsing
                const rawTotal = parseFloat(cartTotalEl.textContent.replace(currencySymbol, '').replace(/,/g, '')) || 0;
                modalTotal.dataset.total = rawTotal;
            }

            // Reset payment state
            paymentState.amountReceivedString = '';
            paymentState.currentStep = 1;
            paymentState.paymentMethod = 'cash';
            paymentState.paymentReference = '';
            paymentState.paymentNotes = '';

            // Reset display — single source of truth
            syncDisplayFromState();
            goToStep(1);

            // Generate quick amounts
            const rawTotal = parseFloat(modalTotal?.dataset?.total || 0);
            generateQuickAmountButtons(rawTotal);

            // Reset payment method to first option
            const firstPaymentMethod = document.querySelector('[name="payment-method"]');
            if (firstPaymentMethod) {
                firstPaymentMethod.checked = true;
                updatePaymentMethodFields(firstPaymentMethod.value);
            }

            // Show modal
            if (paymentModalInstance) {
                paymentModalInstance.show();
            }
        });
    }

    /**
     * Visual button animation
     */
    function animateButton(element, event = null) {
        element.classList.add('keypad-btn-pressed');

        if (event) {
            // Create ripple effect at click position
            const rect = element.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            const ripple = document.createElement('span');
            ripple.classList.add('keypad-btn-ripple-effect');
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.position = 'absolute';
            ripple.style.width = '10px';
            ripple.style.height = '10px';

            // Remove any existing ripples
            element.querySelectorAll('.keypad-btn-ripple-effect').forEach(ripple => ripple.remove());

            element.appendChild(ripple);

            // Remove ripple after animation completes
            setTimeout(() => {
                if (ripple.parentNode) {
                    ripple.remove();
                }
            }, 600);
        }

        // Remove pressed animation after completion
        setTimeout(() => {
            element.classList.remove('keypad-btn-pressed');
        }, 200);
    }

    /**
     * Numeric keypad handlers
     */
    document.querySelectorAll('.keypad-btn-enterprise').forEach(btn => {
        btn.addEventListener('click', function (event) {
            const key = this.dataset.key;
            if (key) {
                handleKeypadInput(key);
                animateButton(this, event);
            } else {
                // If no dataset key, try to get from child element
                const childBtn = this.querySelector('[data-key]');
                if (childBtn) {
                    const key = childBtn.dataset.key;
                    if (key) {
                        handleKeypadInput(key);
                        animateButton(this, event);
                    }
                }
            }
        });
    });

    /**
     * Keyboard input on amount-received field — auto-format with commas
     */
    const amountInput = document.getElementById('amount-received-display');
    if (amountInput) {
        function syncFromInput() {
            // Strip everything except digits and dot
            let raw = amountInput.value.replace(/[^0-9.]/g, '');
            // Only allow one decimal point
            const dotIdx = raw.indexOf('.');
            if (dotIdx !== -1) {
                raw = raw.slice(0, dotIdx + 1) + raw.slice(dotIdx + 1).replace(/\./g, '');
                // Max 2 decimal places
                const decimals = raw.slice(dotIdx + 1);
                if (decimals.length > 2) raw = raw.slice(0, dotIdx + 3);
            }

            // Update state from keyboard input
            paymentState.amountReceivedString = raw;
            paymentState.amountReceived = parseFloat(raw) || 0;

            // Auto-format display with commas (preserve cursor position)
            const cursorPos = amountInput.selectionStart;
            const oldLen = amountInput.value.length;
            amountInput.value = formatWithCommas(raw);
            const newLen = amountInput.value.length;
            const newPos = Math.max(0, cursorPos + (newLen - oldLen));
            amountInput.setSelectionRange(newPos, newPos);

            updatePaymentDisplay();
        }

        amountInput.addEventListener('input', syncFromInput);
        amountInput.addEventListener('paste', function () { setTimeout(syncFromInput, 0); });
    }

    /**
     * Exact amount button
     */
    const exactAmountBtn = document.getElementById('exact-amount-btn');
    if (exactAmountBtn) {
        exactAmountBtn.addEventListener('click', function (event) {
            const currencySymbol = window.posSettings?.currencySymbol || '$';
            const modalTotalEl = document.getElementById('modal-total');
            const total = parseFloat(modalTotalEl?.dataset?.total || modalTotalEl?.textContent.replace(currencySymbol, '').replace(/,/g, '') || 0);
            paymentState.amountReceivedString = total.toFixed(2);
            syncDisplayFromState();
            animateButton(this, event);
        });
    }

    /**
     * Payment method change handler
     */
    document.querySelectorAll('[name="payment-method"]').forEach(radio => {
        radio.addEventListener('change', function () {
            updatePaymentMethodFields(this.value);
        });
    });

    /**
     * Preview payment button
     */
    const previewPaymentBtn = document.getElementById('preview-payment-btn');
    if (previewPaymentBtn) {
        previewPaymentBtn.addEventListener('click', function () {
            if (validatePayment()) {
                goToStep(2);
            }
        });
    }

    /**
     * Back to payment button
     */
    const backToPaymentBtn = document.getElementById('back-to-payment-btn');
    if (backToPaymentBtn) {
        backToPaymentBtn.addEventListener('click', function () {
            goToStep(1);
        });
    }

    /**
     * Cancel payment button
     */
    const cancelPaymentBtn = document.getElementById('cancel-payment-btn');
    if (cancelPaymentBtn) {
        cancelPaymentBtn.addEventListener('click', function () {
            if (paymentModalInstance) {
                paymentModalInstance.hide();
            }
        });
    }

    /**
     * Close modal button
     */
    const paymentModalClose = document.getElementById('payment-modal-close');
    if (paymentModalClose) {
        paymentModalClose.addEventListener('click', function () {
            if (paymentModalInstance) {
                paymentModalInstance.hide();
            }
        });
    }

    /**
     * Confirm payment handler (shared by step 1 and step 2 buttons)
     */
    async function handleConfirmPayment(triggerBtn) {
            const customerId = customerSelect ? customerSelect.value : null;
            const staffId = document.getElementById('staff-select')?.value || null;
            const notes = document.getElementById('sale-notes')?.value || null;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Calculate subtotal for coupon discount
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const couponDiscountAmount = calculateCouponDiscount(subtotal);
            const appliedCouponsData = appliedCoupons.map(coupon => ({
                id: coupon.id,
                code: coupon.code,
                discount_amount: coupon.discount_amount,
            }));

            const saleData = {
                customer_id: customerId,
                staff_id: staffId,
                items: cart.map(item => ({
                    type: item.type,
                    id: item.id,
                    quantity: item.quantity,
                    price: item.price
                })),
                payment_method: paymentState.paymentMethod,
                amount_received: paymentState.amountReceived,
                payment_reference: paymentState.paymentReference || null,
                payment_notes: paymentState.paymentNotes || null,
                discount_amount: discountAmount,
                discount_type: discountType,
                coupon_discount_amount: couponDiscountAmount,
                applied_coupons: appliedCouponsData,
                notes: notes,
                _token: csrfToken
            };

            // Disable button during processing
            triggerBtn.disabled = true;
            triggerBtn.innerHTML = '<i class="ti ti-loader-2 animate-spin me-2"></i>Processing...';

            try {
                const response = await fetch('/pos/sale', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(saleData)
                });

                if (response.status === 422) {
                    // Validation error
                    const errorData = await response.json();
                    let errorMessage = 'Validation errors occurred:';
                    if (errorData.errors) {
                        for (const [field, messages] of Object.entries(errorData.errors)) {
                            errorMessage += `\n- ${field}: ${messages.join(', ')}`;
                        }
                    }
                    showToast(errorMessage, 'error');
                    goToStep(1); // Go back to payment entry
                } else {
                    const result = await response.json();

                    if (result.success) {
                        // Close modal
                        if (paymentModalInstance) {
                            paymentModalInstance.hide();
                        }

                        // Show success message with change amount
                        const currencySymbol = window.posSettings?.currencySymbol || '$';
                        Swal.fire({
                            icon: 'success',
                            title: 'Sale Completed!',
                            html: `
                                <p>Sale #${result.sale_number || result.sale_id}</p>
                                <p class="fs-4 fw-bold text-success">Change: ${currencySymbol}${result.change_amount.toFixed(2)}</p>
                            `,
                            showConfirmButton: true,
                            showCancelButton: true,
                            confirmButtonText: 'View Receipt',
                            cancelButtonText: 'Close'
                        }).then((swalResult) => {
                            if (swalResult.isConfirmed && result.sale_id) {
                                // View receipt in browser
                                window.open(`/pos/receipt/${result.sale_id}`, '_blank');
                            }
                        });

                        // Reset cart and form
                        cart = [];
                        discountAmount = 0;
                        if (discountAmountInput) discountAmountInput.value = '';
                        renderCart();
                        updateTotals();
                        localStorage.removeItem('pos_cart_state');
                    } else {
                        showToast(result.message || 'Failed to process sale', 'error');
                    }
                }
            } catch (error) {
                console.error('Error processing sale:', error);
                if (error instanceof TypeError && error.message.includes('fetch')) {
                    showToast('Network error. Please check your connection and try again.', 'error');
                } else {
                    showToast('Failed to process sale. Please try again.', 'error');
                }
            } finally {
                // Re-enable button
                triggerBtn.disabled = false;
                triggerBtn.innerHTML = '<i class="ti ti-check me-1"></i> Confirm & Complete';
            }
    }

    // Bind confirm to both step-1 and step-2 buttons
    document.querySelectorAll('#confirm-payment-btn, #confirm-payment-btn-step2').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!validatePayment()) return;
            handleConfirmPayment(this);
        });
    });

    /**
     * Clear cart button
     */
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function () {
            if (cart.length === 0) {
                showToast('Cart is already empty', 'info');
                return;
            }

            // Check if SweetAlert2 is available before using it
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Clear Cart?',
                    text: 'This will remove all items from the cart',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, clear it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        cart = [];
                        discountAmount = 0;
                        if (discountAmountInput) discountAmountInput.value = '';
                        renderCart();
                        updateTotals();
                        localStorage.removeItem('pos_cart_state');
                        showToast('Cart cleared', 'info');
                    }
                });
            } else {
                // Fallback to native confirm dialog if SweetAlert2 is not available
                if (confirm('Clear Cart?\nThis will remove all items from the cart')) {
                    cart = [];
                    discountAmount = 0;
                    if (discountAmountInput) discountAmountInput.value = '';
                    renderCart();
                    updateTotals();
                    localStorage.removeItem('pos_cart_state');
                    showToast('Cart cleared', 'info');
                }
            }
        });
    }

    /**
     * Hold order button
     */
    if (holdOrderBtn) {
        holdOrderBtn.addEventListener('click', function () {
            if (cart.length === 0) {
                showToast('Cart is empty', 'error');
                return;
            }

            showToast('Cart saved! It will be restored on next visit', 'success');
            saveCartToStorage();
        });
    }

    // ====================
    // KEYBOARD SHORTCUTS
    // ====================

    /**
     * Global keyboard shortcuts
     */
    document.addEventListener('keydown', function (e) {
        // Don't trigger shortcuts when typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            return;
        }

        switch (e.key) {
            case 'F1':
                e.preventDefault();
                document.getElementById('item-search')?.focus();
                break;

            case 'F2':
                e.preventDefault();
                completeSaleBtn?.click();
                break;

            case 'Escape':
                const modalElement = document.getElementById('paymentModal');
                if (modalElement && modalElement.classList.contains('show')) {
                    paymentModalInstance?.hide();
                }
                break;
        }
    });

    // ====================
    // LIVE SEARCH
    // ====================

    /**
     * Live search with AJAX and debounce
     */
    const searchInput = document.getElementById('item-search');
    const searchLoading = document.getElementById('search-loading');

    if (searchInput) {
        let searchTimeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                // Reset to show all items
                document.querySelectorAll('.pos-product-card').forEach(card => {
                    const col = card.closest('.col');
                    if (col) col.style.display = 'block';
                });

                // Hide loading indicator
                if (searchLoading) searchLoading.style.display = 'none';
                return;
            }

            // Show loading indicator
            if (searchLoading) searchLoading.style.display = 'inline-block';

            // Debounce search to avoid excessive requests
            searchTimeout = setTimeout(() => {
                performLiveSearch(query);
            }, 300); // Wait 300ms after user stops typing
        });

        /**
         * Perform client-side search filtering
         */
        function performLiveSearch(query) {
            try {
                // Get all service and package cards
                const allCards = document.querySelectorAll('.pos-product-card');

                if (!query || query.length < 1) {
                    // Show all cards if search is empty
                    allCards.forEach(card => {
                        const col = card.closest('.col');
                        if (col) col.style.display = 'block';
                    });
                    return;
                }

                // Convert query to lowercase for case-insensitive search
                const searchTerm = query.toLowerCase();

                // Filter cards based on search term
                allCards.forEach(card => {
                    // Get the card's content to match against
                    const cardName = escapeHtml(card.getAttribute('data-service-name') || '').toLowerCase();
                    const cardDescription = escapeHtml(card.textContent).toLowerCase(); // This includes name, price, etc.

                    // Check if any relevant field matches the search term
                    const isMatch = cardName.includes(searchTerm) ||
                        cardDescription.includes(searchTerm);

                    const col = card.closest('.col');
                    if (col) {
                        col.style.display = isMatch ? 'block' : 'none';
                    }
                });
            } catch (error) {
                console.error('Client-side search error:', error);
                // In case of error, show all items so user can continue working
                document.querySelectorAll('.pos-product-card').forEach(card => {
                    const col = card.closest('.col');
                    if (col) col.style.display = 'block';
                });
                showToast('Search failed. Showing all items.', 'warning');
            } finally {
                // Hide loading indicator
                if (searchLoading) searchLoading.style.display = 'none';
            }
        }
    }

    // ====================
    // INITIALIZATION
    // ====================

    /**
     * Load cart from storage on page load
     */
    loadCartFromStorage();

    /**
     * Make functions available globally for inline onclick handlers
     */
    window.updateQuantity = updateQuantity;
    window.removeFromCart = removeFromCart;
});