<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class CreateMissingPosTables extends Command
{
    protected $signature = 'pos:create-missing-tables';
    protected $description = 'Create missing POS tables that were not properly created during migration';

    public function handle()
    {
        // Create sales table if it doesn't exist
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('sale_number')->unique(); // e.g., SALE-2025-00001
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users'); // Staff member
                $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

                // Financial columns
                $table->decimal('subtotal', 10, 2);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->decimal('change_amount', 10, 2)->default(0);

                // Metadata
                $table->enum('status', ['completed', 'pending', 'cancelled', 'refunded'])->default('completed');
                $table->enum('sale_type', ['walk_in', 'appointment', 'service_package'])->default('walk_in');
                $table->text('notes')->nullable();
                $table->timestamp('sale_date');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['sale_number', 'status', 'sale_date']);
                $table->index('sale_date');
            });
            
            $this->info('Sales table created successfully.');
        } else {
            $this->info('Sales table already exists.');
        }

        // Create sale_items table if it doesn't exist
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

                // Polymorphic: can be service OR service package
                $table->morphs('sellable'); // sellable_type, sellable_id

                $table->string('item_name'); // Snapshot of name at time of sale
                $table->string('item_code')->nullable(); // For service codes/packages
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('line_total', 10, 2);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('sale_id');
            });
            
            $this->info('Sale items table created successfully.');
        } else {
            $this->info('Sale items table already exists.');
        }

        // Create payments table if it doesn't exist
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->enum('payment_method', ['cash', 'card', 'mobile', 'check', 'bank_transfer', 'store_credit']);
                $table->decimal('amount', 10, 2);
                $table->string('reference_number')->nullable(); // Card transaction ID, check number, etc.
                $table->text('notes')->nullable();
                $table->timestamp('payment_date');
                $table->timestamps();

                $table->index('sale_id');
            });
            
            $this->info('Payments table created successfully.');
        } else {
            $this->info('Payments table already exists.');
        }

        // Create invoices table if it doesn't exist
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique(); // INV-2025-00001
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

                // Customer snapshot (in case customer is deleted)
                $table->json('customer_details'); // name, email, phone, address

                // Invoice details
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');

                // Financial
                $table->decimal('subtotal', 10, 2);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->decimal('balance_due', 10, 2)->default(0);

                $table->text('notes')->nullable();
                $table->text('terms')->nullable(); // Payment terms
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['invoice_number', 'status', 'invoice_date']);
            });
            
            $this->info('Invoices table created successfully.');
        } else {
            $this->info('Invoices table already exists.');
        }

        // Create refunds table if it doesn't exist
        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->string('refund_number')->unique(); // REF-2025-00001
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users'); // Staff who processed refund
                $table->decimal('refund_amount', 10, 2);
                $table->enum('refund_method', ['cash', 'card', 'store_credit', 'original_payment']);
                $table->text('reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('refund_date');
                $table->timestamps();

                $table->index('sale_id');
            });
            
            $this->info('Refunds table created successfully.');
        } else {
            $this->info('Refunds table already exists.');
        }

        // Create service_package_sales table if it doesn't exist
        if (!Schema::hasTable('service_package_sales')) {
            Schema::create('service_package_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
                $table->foreignId('service_package_id')->constrained('service_packages')->cascadeOnDelete();
                $table->integer('sessions_used')->default(0);
                $table->integer('sessions_remaining');
                $table->timestamp('expires_at')->nullable();
                $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
                $table->timestamps();

                $table->index(['service_package_id', 'status']);
            });
            
            $this->info('Service package sales table created successfully.');
        } else {
            $this->info('Service package sales table already exists.');
        }

        // Create cash_drawer_sessions table if it doesn't exist
        if (!Schema::hasTable('cash_drawer_sessions')) {
            Schema::create('cash_drawer_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users'); // Cashier
                $table->decimal('opening_amount', 10, 2);
                $table->decimal('closing_amount', 10, 2)->nullable();
                $table->decimal('expected_amount', 10, 2)->nullable();
                $table->decimal('difference', 10, 2)->nullable(); // Over/short
                $table->timestamp('opened_at');
                $table->timestamp('closed_at')->nullable();
                $table->text('opening_notes')->nullable();
                $table->text('closing_notes')->nullable();
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->timestamps();

                $table->index(['user_id', 'status', 'opened_at']);
            });
            
            $this->info('Cash drawer sessions table created successfully.');
        } else {
            $this->info('Cash drawer sessions table already exists.');
        }

        $this->info('All POS tables have been created successfully!');
    }
}