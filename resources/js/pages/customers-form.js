/**
 * Customer Form - Country Code & Phone Number Handler
 */

import Choices from 'choices.js';

document.addEventListener('DOMContentLoaded', function() {
    const countryCodeSelect = document.getElementById('country-code-select');
    const localPhoneInput = document.getElementById('local-phone-input');
    const phoneHiddenInput = document.getElementById('phone');
    const formatHint = document.getElementById('format-example');

    if (!countryCodeSelect || !localPhoneInput) {
        return; // Not on customer form page
    }

    // Initialize Choices.js for country selector
    const countryChoices = new Choices(countryCodeSelect, {
        searchEnabled: true,
        searchPlaceholderValue: 'Search country...',
        shouldSort: false,
        itemSelectText: '',
        placeholder: false,
        noResultsText: 'No countries found',
        searchFields: ['label']
    });

    // Update placeholder based on selected country
    function updateFormatHint() {
        const selectedOption = countryCodeSelect.options[countryCodeSelect.selectedIndex];
        const format = selectedOption.getAttribute('data-format');

        if (format) {
            formatHint.textContent = format;
            localPhoneInput.placeholder = format;
        }
    }

    // Combine country code + local number into hidden field
    function updateFullPhone() {
        const countryCode = countryCodeSelect.value;
        const localNumber = localPhoneInput.value.replace(/[^0-9]/g, ''); // Remove non-digits

        // WhatsApp compatible format: pure numbers only
        const fullPhone = countryCode + localNumber;
        phoneHiddenInput.value = fullPhone;
    }

    // Event listeners
    countryCodeSelect.addEventListener('change', function() {
        updateFormatHint();
        updateFullPhone();
    });

    localPhoneInput.addEventListener('input', function(e) {
        // Auto-format local number as user types (optional)
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits

        // Format with spaces for readability (doesn't affect stored value)
        if (value.length > 2) {
            value = value.substring(0, 2) + ' ' + value.substring(2);
        }
        if (value.length > 6) {
            value = value.substring(0, 6) + ' ' + value.substring(6);
        }

        e.target.value = value;
        updateFullPhone();
    });

    // Initialize on page load
    updateFormatHint();
    updateFullPhone();
});
