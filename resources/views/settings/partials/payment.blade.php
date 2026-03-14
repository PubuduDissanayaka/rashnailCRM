<div class="tab-pane" id="payment-tab" role="tabpanel">
    <form id="payment-form" action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="group" value="payment">
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="currency_code" class="form-label">Currency Code</label>
                    <select class="form-select" id="currency_code" name="settings[payment.currency_code]">
                        <option value="USD" {{ (old('settings.payment.currency_code', $payment['payment.currency_code'] ?? 'USD') == 'USD') ? 'selected' : '' }}>USD - US Dollar</option>
                        <option value="EUR" {{ (old('settings.payment.currency_code', $payment['payment.currency_code'] ?? 'USD') == 'EUR') ? 'selected' : '' }}>EUR - Euro</option>
                        <option value="GBP" {{ (old('settings.payment.currency_code', $payment['payment.currency_code'] ?? 'USD') == 'GBP') ? 'selected' : '' }}>GBP - British Pound</option>
                        <option value="CAD" {{ (old('settings.payment.currency_code', $payment['payment.currency_code'] ?? 'USD') == 'CAD') ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                        <option value="LKR" {{ (old('settings.payment.currency_code', $payment['payment.currency_code'] ?? 'USD') == 'LKR') ? 'selected' : '' }}>LKR - Sri Lankan Rupees</option>
                        <!-- Add more currencies as needed -->
                    </select>
                    <div class="form-text">Currency code (ISO 4217)</div>
                </div>
                
                <div class="mb-3">
                    <label for="currency_symbol" class="form-label">Currency Symbol</label>
                    <input type="text" class="form-control" id="currency_symbol" name="settings[payment.currency_symbol]" 
                           value="{{ old('settings.payment.currency_symbol', $payment['payment.currency_symbol'] ?? '$') }}" maxlength="3">
                    <div class="form-text">Currency symbol</div>
                </div>
                
                <div class="mb-3">
                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                    <input type="number" class="form-control" id="tax_rate" name="settings[payment.tax_rate]" 
                           value="{{ old('settings.payment.tax_rate', $payment['payment.tax_rate'] ?? '0') }}" min="0" max="100">
                    <div class="form-text">Tax rate percentage</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                    <input type="text" class="form-control" id="invoice_prefix" name="settings[payment.invoice_prefix]" 
                           value="{{ old('settings.payment.invoice_prefix', $payment['payment.invoice_prefix'] ?? 'INV') }}" maxlength="10">
                    <div class="form-text">Invoice number prefix</div>
                </div>
                
                <div class="mb-3">
                    <label for="next_invoice_number" class="form-label">Next Invoice Number</label>
                    <input type="number" class="form-control" id="next_invoice_number" name="settings[payment.next_invoice_number]" 
                           value="{{ old('settings.payment.next_invoice_number', $payment['payment.next_invoice_number'] ?? '1') }}" min="1">
                    <div class="form-text">Next invoice number to be generated</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[payment.require_deposit]" value="0">
                    <input type="checkbox" class="form-check-input" id="require_deposit" name="settings[payment.require_deposit]" 
                           value="1" {{ (old('settings.payment.require_deposit', $payment['payment.require_deposit'] ?? '0') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="require_deposit">Require Deposit for Bookings</label>
                    <div class="form-text">Require deposit for bookings</div>
                </div>
                
                <div class="mb-3">
                    <label for="deposit_percentage" class="form-label">Deposit Percentage Required (%)</label>
                    <input type="number" class="form-control" id="deposit_percentage" name="settings[payment.deposit_percentage]" 
                           value="{{ old('settings.payment.deposit_percentage', $payment['payment.deposit_percentage'] ?? '50') }}" 
                           min="0" max="100" 
                           @if(old('settings.payment.require_deposit', $payment['payment.require_deposit'] ?? '0') != '1') disabled @endif>
                    <div class="form-text">Deposit percentage required</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="payment_methods" class="form-label">Accepted Payment Methods</label>
                    <div class="d-flex flex-wrap">
                        @php
                            $paymentMethods = [];
                            if (isset($payment['payment.methods'])) {
                                $value = $payment['payment.methods'];
                                if (is_array($value)) {
                                    $paymentMethods = $value;
                                } elseif (is_string($value)) {
                                    $paymentMethods = json_decode($value, true) ?? [];
                                }
                            }
                        @endphp
                        <div class="form-check me-3">
                            <input type="checkbox" class="form-check-input" id="method_cash" name="payment_methods[]"
                                   value="cash" {{ in_array('cash', $paymentMethods) ? 'checked' : '' }}>
                            <label class="form-check-label" for="method_cash">Cash</label>
                        </div>
                        <div class="form-check me-3">
                            <input type="checkbox" class="form-check-input" id="method_card" name="payment_methods[]"
                                   value="card" {{ in_array('card', $paymentMethods) ? 'checked' : '' }}>
                            <label class="form-check-label" for="method_card">Card</label>
                        </div>
                        <div class="form-check me-3">
                            <input type="checkbox" class="form-check-input" id="method_check" name="payment_methods[]"
                                   value="check" {{ in_array('check', $paymentMethods) ? 'checked' : '' }}>
                            <label class="form-check-label" for="method_check">Check</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="method_online" name="payment_methods[]"
                                   value="online" {{ in_array('online', $paymentMethods) ? 'checked' : '' }}>
                            <label class="form-check-label" for="method_online">Online</label>
                        </div>
                    </div>
                    <div class="form-text">Accepted payment methods</div>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="refund_policy" class="form-label">Refund Policy</label>
            <textarea class="form-control" id="refund_policy" name="settings[payment.refund_policy]" rows="4">{{ old('settings.payment.refund_policy', $payment['payment.refund_policy'] ?? 'Refunds are processed within 5-7 business days. Cancellation fees may apply.') }}</textarea>
            <div class="form-text">Refund policy text</div>
        </div>
        
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> Save Payment Settings
            </button>
        </div>
    </form>
</div>