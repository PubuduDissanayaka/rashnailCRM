<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a paginated list of invoices.
     */
    public function index()
    {
        $this->authorize('view invoices');

        $invoices = Invoice::with(['sale', 'customer'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        return view('invoice.invoices', compact('invoices', 'currencySymbol'));
    }

    /**
     * Show a single invoice with full details.
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view invoices');

        $invoice->load(['sale.items.sellable', 'sale.payments', 'customer', 'sale.user']);

        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $businessName = Setting::get('business.name', config('app.name'));
        $businessAddress = Setting::get('business.address', '');
        $businessPhone = Setting::get('business.phone', '');
        $businessEmail = Setting::get('business.email', '');
        $businessLogo = Setting::get('business.logo');

        return view('invoice.details', compact(
            'invoice', 'currencySymbol',
            'businessName', 'businessAddress', 'businessPhone',
            'businessEmail', 'businessLogo'
        ));
    }

    /**
     * Show form to create an invoice from a specific sale.
     */
    public function createFromSale(Sale $sale)
    {
        $this->authorize('create invoices');

        // Check if invoice already exists
        if ($sale->invoice) {
            return redirect()->route('invoices.show', $sale->invoice)
                ->with('info', 'An invoice already exists for this sale.');
        }

        $sale->load(['customer', 'items.sellable', 'payments', 'user']);
        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $nextInvoiceNumber = Invoice::generateInvoiceNumberPreview();

        return view('invoice.create', compact('sale', 'currencySymbol', 'nextInvoiceNumber'));
    }

    /**
     * Generate (store) an invoice from a completed sale.
     */
    public function storeFromSale(Request $request, Sale $sale)
    {
        $this->authorize('create invoices');

        if ($sale->invoice) {
            return redirect()->route('invoices.show', $sale->invoice)
                ->with('info', 'An invoice already exists for this sale.');
        }

        if ($sale->status !== 'completed') {
            return redirect()->back()->with('error', 'Invoices can only be generated for completed sales.');
        }

        $validated = $request->validate([
            'due_date' => 'nullable|date|after_or_equal:today',
            'terms' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $customerDetails = $sale->customer ? [
            'name' => $sale->customer->full_name,
            'email' => $sale->customer->email,
            'phone' => $sale->customer->phone,
            'address' => $sale->customer->address,
        ] : [];

        $invoice = DB::transaction(function () use ($sale, $customerDetails, $validated) {
            return $sale->generateInvoice($customerDetails, $validated['due_date'] ?? null, $validated['terms'] ?? null, $validated['notes'] ?? null);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice #' . $invoice->invoice_number . ' generated successfully.');
    }

    /**
     * Mark an invoice as paid.
     */
    public function markPaid(Invoice $invoice)
    {
        $this->authorize('edit invoices');

        $invoice->markAsPaid();

        return redirect()->back()->with('success', 'Invoice marked as paid.');
    }

    /**
     * Mark an invoice as sent.
     */
    public function markSent(Invoice $invoice)
    {
        $this->authorize('edit invoices');

        $invoice->markAsSent();

        return redirect()->back()->with('success', 'Invoice marked as sent.');
    }

    /**
     * Soft-delete an invoice.
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete invoices');

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
