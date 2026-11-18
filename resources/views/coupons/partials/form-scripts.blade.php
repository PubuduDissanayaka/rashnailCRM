<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Coupon code: force uppercase, strip spaces ---
        const codeInput = document.getElementById('code');
        if (codeInput) {
            codeInput.addEventListener('input', function () {
                this.value = this.value.toUpperCase().replace(/\s+/g, '');
            });
        }

        // --- Discount fields follow the coupon type ---
        const typeSelect = document.getElementById('type');
        const discountValueGroup = document.getElementById('discount-value-group');
        const discountPercentageGroup = document.getElementById('discount-percentage-group');
        const maxDiscountGroup = document.getElementById('max-discount-group');

        function toggleDiscountFields() {
            if (!typeSelect) return;
            const type = typeSelect.value;
            const show = (el, on) => { if (el) el.style.display = on ? 'block' : 'none'; };

            show(discountPercentageGroup, type === 'percentage');
            show(discountValueGroup, type === 'fixed');
            show(maxDiscountGroup, type === 'percentage');
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', toggleDiscountFields);
            toggleDiscountFields();
        }

        // --- Conditional restriction pickers ---
        function toggleConditional(triggerId, targetId, showValue) {
            const trigger = document.getElementById(triggerId);
            const target = document.getElementById(targetId);
            if (!trigger || !target) return;
            function update() { target.style.display = trigger.value === showValue ? 'block' : 'none'; }
            trigger.addEventListener('change', update);
            update();
        }

        toggleConditional('customer_eligibility_type', 'customer-groups-group', 'groups');
        toggleConditional('location_restriction_type', 'location-specific-group', 'specific');
        toggleConditional('product_restriction_type', 'product-specific-group', 'specific');
        toggleConditional('product_restriction_type', 'category-specific-group', 'categories');

        // --- Flash messages ---
        if (window.Swal) {
            @if(session('success'))
                Swal.fire({ title: 'Success!', text: @json(session('success')), icon: 'success', confirmButtonClass: 'btn btn-primary' });
            @endif
            @if(session('error'))
                Swal.fire({ title: 'Not saved', text: @json(session('error')), icon: 'error', confirmButtonClass: 'btn btn-primary' });
            @endif
        }
    });
</script>
