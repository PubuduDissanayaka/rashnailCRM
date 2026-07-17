<div class="tab-pane" id="pos-tab" role="tabpanel">
    <form id="pos-settings-form" action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="group" value="pos">

        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-semibold mb-3"><i class="ti ti-settings me-1 text-primary"></i> POS Defaults</h6>

                <div class="mb-3">
                    <label for="pos_default_payment" class="form-label">Default Payment Method</label>
                    <select class="form-select" id="pos_default_payment" name="settings[pos.default_payment]">
                        <option value="cash" {{ (old('settings.pos.default_payment', $pos['pos.default_payment'] ?? 'cash') == 'cash') ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ (old('settings.pos.default_payment', $pos['pos.default_payment'] ?? 'cash') == 'card') ? 'selected' : '' }}>Card</option>
                    </select>
                    <div class="form-text">Pre-selected payment method when POS opens</div>
                </div>

                <div class="mb-3">
                    <label for="pos_receipt_footer" class="form-label">Receipt Footer Text</label>
                    <textarea class="form-control" id="pos_receipt_footer" name="settings[pos.receipt_footer]" rows="3">{{ old('settings.pos.receipt_footer', $pos['pos.receipt_footer'] ?? 'Thank you for your visit!') }}</textarea>
                    <div class="form-text">Text printed at the bottom of every receipt</div>
                </div>

                <div class="mb-3">
                    <label for="pos_show_tax" class="form-label">Tax Display</label>
                    <select class="form-select" id="pos_show_tax" name="settings[pos.show_tax]">
                        <option value="1" {{ (old('settings.pos.show_tax', $pos['pos.show_tax'] ?? '1') == '1') ? 'selected' : '' }}>Show tax on POS</option>
                        <option value="0" {{ (old('settings.pos.show_tax', $pos['pos.show_tax'] ?? '1') == '0') ? 'selected' : '' }}>Hide tax (included in price)</option>
                    </select>
                    <div class="form-text">Whether to display tax separately on POS terminal</div>
                </div>
            </div>

            <div class="col-md-6">
                <h6 class="fw-semibold mb-3"><i class="ti ti-currency me-1 text-primary"></i> Quick Amounts</h6>

                <div class="mb-3">
                    <label for="pos_quick_amounts" class="form-label">Quick Amount Presets (comma-separated)</label>
                    <input type="text" class="form-control" id="pos_quick_amounts" name="settings[pos.quick_amounts]"
                           value="{{ old('settings.pos.quick_amounts', $pos['pos.quick_amounts'] ?? '50,100,500,1000') }}">
                    <div class="form-text">Amount buttons shown in POS payment modal</div>
                </div>

                <h6 class="fw-semibold mb-3 mt-4"><i class="ti ti-devices me-1 text-primary"></i> Display</h6>

                <div class="mb-3">
                    <label for="pos_product_cols" class="form-label">Product Grid Columns</label>
                    <select class="form-select" id="pos_product_cols" name="settings[pos.product_cols]">
                        <option value="3" {{ (old('settings.pos.product_cols', $pos['pos.product_cols'] ?? '3') == '3') ? 'selected' : '' }}>3 Columns</option>
                        <option value="4" {{ (old('settings.pos.product_cols', $pos['pos.product_cols'] ?? '3') == '4') ? 'selected' : '' }}>4 Columns</option>
                        <option value="5" {{ (old('settings.pos.product_cols', $pos['pos.product_cols'] ?? '3') == '5') ? 'selected' : '' }}>5 Columns</option>
                    </select>
                    <div class="form-text">Number of product cards per row on desktop</div>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[pos.auto_apply_discount]" value="0">
                    <input type="checkbox" class="form-check-input" id="auto_apply_discount"
                           name="settings[pos.auto_apply_discount]" value="1"
                           {{ (old('settings.pos.auto_apply_discount', $pos['pos.auto_apply_discount'] ?? '0') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_apply_discount">Auto-recall held sales on login</label>
                    <div class="form-text">If a held sale exists for this user, show a prompt on POS load</div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <button type="submit" class="btn btn-primary setting-save-btn">
                    <span class="btn-text"><i class="ti ti-device-floppy me-1"></i> Save POS Settings</span>
                    <span class="btn-spinner d-none"><span class="spinner-border spinner-border-sm me-1"></span> Saving...</span>
                </button>
            </div>
        </div>
    </form>
</div>
