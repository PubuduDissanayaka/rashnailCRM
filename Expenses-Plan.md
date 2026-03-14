# Comprehensive Expense Tracking System - Implementation Plan

## Overview
Build a full-featured expense tracking system for utilities and other business expenses with approval workflow, file attachments, recurring expenses, and budget tracking.

## Features
- ✅ Full CRUD for expense categories (admin creates custom categories)
- ✅ Full CRUD for expenses with approval workflow
- ✅ File attachments for receipts (PDF, images)
- ✅ Recurring expenses (monthly, quarterly, yearly)
- ✅ Budget tracking per category
- ✅ Dashboard with charts and statistics
- ✅ Uses existing currency settings from `payment.currency_symbol`
- ✅ Follows existing inventory system patterns

## Implementation Phases

### Phase 1: Database Schema (5 migrations)

**File: `database/migrations/2026_01_11_000001_create_expense_categories_table.php`**
```php
- id, name, slug (unique)
- description, icon, color (for UI)
- parent_id (hierarchical categories)
- is_active, sort_order
- budget_amount, budget_period (monthly/quarterly/yearly)
- timestamps, soft_deletes
```

**File: `database/migrations/2026_01_11_000002_create_expenses_table.php`**
```php
- id, expense_number (unique: EXP-2026-0001)
- title, description
- category_id (foreign key)
- vendor_name, vendor_contact
- amount, tax_amount, total_amount (decimal:2)
- currency (default from settings)
- payment_method (cash/card/check/bank_transfer/online)
- payment_reference
- expense_date, due_date, paid_date
- status (draft/pending/approved/rejected/paid)
- created_by, approved_by, approved_at (foreign keys to users)
- rejection_reason
- is_recurring, recurring_frequency, recurring_end_date
- parent_expense_id (for recurring expenses)
- notes, metadata (JSON)
- timestamps, soft_deletes
- Indexes: expense_number, category_id, status, expense_date, [status+expense_date]
```

**File: `database/migrations/2026_01_11_000003_create_expense_attachments_table.php`**
```php
- id, expense_id (foreign key, cascade delete)
- filename, file_path, file_type, file_size
- attachment_type (receipt/invoice/contract/other)
- description
- uploaded_by (foreign key to users)
- timestamps
```

**File: `database/migrations/2026_01_11_000004_create_expense_budgets_table.php`**
```php
- id, category_id (nullable)
- name, description
- budget_amount, spent_amount (decimal:2)
- start_date, end_date
- is_active, send_alerts, alert_threshold
- created_by
- timestamps, soft_deletes
```

**File: `database/migrations/2026_01_11_000005_create_expense_comments_table.php`**
```php
- id, expense_id (foreign key, cascade delete)
- user_id (foreign key)
- comment
- is_internal (boolean)
- timestamps
```

### Phase 2: Models (5 models)

**File: `app/Models/ExpenseCategory.php`**
- Fillable: name, slug, description, icon, color, parent_id, is_active, sort_order, budget_amount, budget_period
- Casts: budget_amount → decimal:2, is_active → boolean
- Relationships: parent() BelongsTo, children() HasMany, expenses() HasMany, budgets() HasMany
- Scopes: active(), rootCategories()
- Methods: isParent(), getTotalExpenses($period), getBudgetUtilization()

**File: `app/Models/Expense.php`**
- Fillable: expense_number, title, description, category_id, vendor_name, vendor_contact, amount, tax_amount, total_amount, currency, payment_method, payment_reference, expense_date, due_date, paid_date, status, created_by, approved_by, approved_at, rejection_reason, is_recurring, recurring_frequency, recurring_end_date, parent_expense_id, notes, metadata
- Casts: amount/tax_amount/total_amount → decimal:2, dates → date, approved_at → datetime, is_recurring → boolean, metadata → array
- Relationships: category(), creator(), approver(), attachments(), comments(), parentExpense(), recurringExpenses()
- Scopes: byStatus(), pending(), approved(), paid(), byCategory(), byDateRange(), overdue(), recurring()
- Accessors: formatted_amount (with currency symbol), status_badge (array with class and text)
- Methods: approve($user), reject($user, $reason), markAsPaid($data), isOverdue(), generateExpenseNumber()
- Boot: Auto-generate expense_number, auto-calculate total_amount

**File: `app/Models/ExpenseAttachment.php`**
- Fillable: expense_id, filename, file_path, file_type, file_size, attachment_type, description, uploaded_by
- Relationships: expense(), uploader()
- Methods: getUrl(), getFormattedSize()
- Boot: Delete file from storage on model deletion

**File: `app/Models/ExpenseBudget.php`**
- Fillable: category_id, name, description, budget_amount, spent_amount, start_date, end_date, is_active, send_alerts, alert_threshold, created_by
- Casts: amounts → decimal:2, dates → date, is_active/send_alerts → boolean
- Relationships: category(), creator()
- Methods: updateSpentAmount(), getUtilizationPercentage(), isOverBudget()

**File: `app/Models/ExpenseComment.php`**
- Fillable: expense_id, user_id, comment, is_internal
- Relationships: expense(), user()
- Casts: is_internal → boolean

### Phase 3: Controllers (2 controllers)

**File: `app/Http/Controllers/Expense/ExpenseCategoryController.php`**
Pattern: Follow `app/Http/Controllers/Inventory/SupplyController.php`
- index(): List all categories with stats (total, active, with_budget)
- create(): Show form with parent categories dropdown
- store(): Validate and create category, auto-generate slug
- edit(): Show form with existing data
- update(): Validate and update category
- destroy(): Delete if no expenses exist

**File: `app/Http/Controllers/Expense/ExpenseController.php`**
Pattern: Follow `app/Http/Controllers/Inventory/SupplyController.php`
- index(): List expenses with filters (status, category, date range), stats
- create(): Show form with categories and payment methods
- store(): Validate, create expense, handle file uploads
- show(): Display expense details with attachments and comments
- edit(): Show form (only if status != paid/rejected)
- update(): Update expense (only if status != paid/rejected)
- destroy(): Delete expense (only if status != paid)
- approve($expense): Change status to approved (only from pending)
- reject($expense): Change status to rejected with reason (only from pending)
- markAsPaid($expense): Change status to paid with payment details (only from approved)
- dashboard(): Show stats, charts, recent expenses, overdue expenses

### Phase 4: Views (11 Blade templates)

**File: `resources/views/expenses/dashboard.blade.php`**
Pattern: Follow `resources/views/inventory/supplies/index.blade.php`
- Stats cards: Paid this month, Total pending, Total approved, Total paid
- Charts: Monthly trend (area chart), Category breakdown (donut chart), Budget utilization (bar chart)
- Recent expenses table
- Overdue expenses list

**File: `resources/views/expenses/index.blade.php`**
Pattern: Follow `resources/views/inventory/supplies/index.blade.php`
- Stats bar: Total, Pending, Approved, Paid, Total Amount
- Filters: Status dropdown, Category dropdown, Search box
- Custom table with data attributes (data-table, data-table-search, data-table-filter)
- Columns: Expense #, Title, Category, Amount, Date, Status, Actions
- Action buttons: View, Edit (if not paid/rejected), Delete (if not paid)

**File: `resources/views/expenses/create.blade.php`**
- Form with title, description, category, vendor, amount, tax, payment method, dates
- Recurring expense fields (checkbox, frequency, end date)
- File upload for attachments (multiple files)
- Status selection (draft or pending)
- Submit and Cancel buttons

**File: `resources/views/expenses/edit.blade.php`**
- Same as create but pre-filled with existing data
- Cannot edit if status is paid or rejected

**File: `resources/views/expenses/show.blade.php`**
- Expense details with all fields
- Status badge
- Attachments section with download links
- Comments section
- Action buttons: Approve, Reject, Mark as Paid, Edit, Delete (based on status and permissions)
- Timeline/audit trail

**File: `resources/views/expenses/partials/form.blade.php`**
Pattern: Reusable form fields for create and edit
- All input fields with validation errors
- old() helper for form repopulation
- @error() directives

**File: `resources/views/expenses/partials/status-badge.blade.php`**
- Bootstrap badge with color based on status
- draft → secondary, pending → warning, approved → info, rejected → danger, paid → success

**File: `resources/views/expenses/partials/approve-modal.blade.php`**
- Bootstrap modal for approval confirmation
- Simple form with POST to approve route

**File: `resources/views/expenses/partials/reject-modal.blade.php`**
- Bootstrap modal with rejection reason textarea
- Form POST to reject route

**File: `resources/views/expenses/partials/payment-modal.blade.php`**
- Bootstrap modal for marking as paid
- Fields: paid_date, payment_method, payment_reference
- Form POST to mark-paid route

**File: `resources/views/expenses/categories/index.blade.php`**
Pattern: Follow `resources/views/inventory/supplies/index.blade.php`
- List all categories with stats
- Columns: Name, Description, Budget, # of Expenses, Status, Actions
- CRUD actions

### Phase 5: Frontend JavaScript (1 file)

**File: `resources/js/pages/expenses-dashboard.js`**
Pattern: Follow existing chart patterns from `resources/js/app.js`
- Import CustomApexChart and ins() helper
- Monthly trend chart: Area chart with gradient fill
- Category breakdown: Donut chart with category colors
- Budget utilization: Horizontal bar chart showing percentage
- All charts use theme-aware colors via ins() helper
- Auto-rerender on theme change

**Add to: `vite.config.js`**
```javascript
"resources/js/pages/expenses-dashboard.js"
```

### Phase 6: Routes Configuration

**File: `routes/web.php`** (add these routes)
```php
use App\Http\Controllers\Expense\{ExpenseController, ExpenseCategoryController};

// Expense Dashboard
Route::get('/expenses/dashboard', [ExpenseController::class, 'dashboard'])
    ->name('expenses.dashboard')
    ->middleware(['auth', 'can:expenses.view']);

// Expense Categories CRUD
Route::middleware(['auth', 'can:expenses.view'])->group(function () {
    Route::resource('expenses/categories', ExpenseCategoryController::class)
        ->names([
            'index' => 'expenses.categories.index',
            'create' => 'expenses.categories.create',
            'store' => 'expenses.categories.store',
            'show' => 'expenses.categories.show',
            'edit' => 'expenses.categories.edit',
            'update' => 'expenses.categories.update',
            'destroy' => 'expenses.categories.destroy',
        ]);
});

// Expenses CRUD
Route::middleware(['auth', 'can:expenses.view'])->group(function () {
    Route::resource('expenses', ExpenseController::class);
});

// Expense Workflow Actions
Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])
    ->name('expenses.approve')
    ->middleware(['auth', 'can:expenses.approve']);
Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])
    ->name('expenses.reject')
    ->middleware(['auth', 'can:expenses.approve']);
Route::post('/expenses/{expense}/mark-as-paid', [ExpenseController::class, 'markAsPaid'])
    ->name('expenses.mark-paid')
    ->middleware(['auth', 'can:expenses.manage']);
```

### Phase 7: Menu Integration

**File: `resources/views/layouts/partials/menu.blade.php`**
Add menu item under main navigation:
```blade
@can('expenses.view')
<li class="menu-item">
    <a href="#expensesMenu" data-bs-toggle="collapse" class="menu-link">
        <span class="menu-icon"><i class="ti ti-wallet"></i></span>
        <span class="menu-text"> Expenses </span>
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse" id="expensesMenu">
        <ul class="sub-menu">
            <li class="menu-item">
                <a href="{{ route('expenses.dashboard') }}" class="menu-link">
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{ route('expenses.index') }}" class="menu-link">
                    <span class="menu-text">All Expenses</span>
                </a>
            </li>
            @can('expenses.create')
            <li class="menu-item">
                <a href="{{ route('expenses.create') }}" class="menu-link">
                    <span class="menu-text">Add Expense</span>
                </a>
            </li>
            @endcan
            <li class="menu-item">
                <a href="{{ route('expenses.categories.index') }}" class="menu-link">
                    <span class="menu-text">Categories</span>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcan
```

### Phase 8: Permissions & Seeder

Create expense permissions (add to existing permission seeder):
```php
'expenses.view' => 'View expenses',
'expenses.create' => 'Create expenses',
'expenses.manage' => 'Edit and delete expenses',
'expenses.approve' => 'Approve/reject expenses',
```

**Optional: Create seeder for default categories**
```php
ExpenseCategory::create([
    'name' => 'Utilities',
    'slug' => 'utilities',
    'icon' => 'ti-bolt',
    'color' => '#F59E0B',
    'is_active' => true,
]);
// Add more: Rent, Office Supplies, Travel, Equipment, Salaries, etc.
```

## Key Technical Decisions

### Currency Handling
- Uses existing `Setting::get('payment.currency_symbol', '$')` from settings system
- Stores currency code in each expense for multi-currency support
- All amounts use `decimal(12, 2)` for precision

### File Storage
- Files stored in `storage/app/public/expenses/{expense_id}/`
- Validation: PDF, JPG, JPEG, PNG, DOC, DOCX (max 10MB)
- Auto-cleanup on expense deletion via model boot event

### Workflow States
```
draft → pending → approved → paid
              ↓
           rejected
```
- Draft: Editable by creator
- Pending: Waiting approval, not editable
- Approved: Ready for payment, not editable
- Rejected: Denied with reason, not editable
- Paid: Completed, cannot delete or edit

### Auto-Generation
- Expense numbers: `EXP-{YEAR}-{SEQUENTIAL}` (e.g., EXP-2026-0001)
- Total amount: Auto-calculated from amount + tax_amount
- Slug: Auto-generated from category name

### Authorization Strategy
- Permissions: `expenses.view`, `expenses.create`, `expenses.manage`, `expenses.approve`
- Controller-level: `$this->authorize('permission')`
- View-level: `@can('permission')` directives
- Route-level: `middleware(['can:permission'])`

### Client-Side Tables
- Uses existing `custom-table.js` for filtering, sorting, pagination
- Data attributes: `data-table`, `data-table-search`, `data-table-filter`, `data-table-sort`
- No custom JavaScript needed for basic CRUD

### Charts & Dashboard
- Uses `CustomApexChart` wrapper for theme compatibility
- Colors via `ins()` helper (e.g., `ins('primary')`)
- Automatic re-rendering on theme change

## Critical Files Reference

**Models to create:**
- `app/Models/ExpenseCategory.php`
- `app/Models/Expense.php`
- `app/Models/ExpenseAttachment.php`
- `app/Models/ExpenseBudget.php`
- `app/Models/ExpenseComment.php`

**Controllers to create:**
- `app/Http/Controllers/Expense/ExpenseCategoryController.php`
- `app/Http/Controllers/Expense/ExpenseController.php`

**Key reference files (existing patterns):**
- `app/Http/Controllers/Inventory/SupplyController.php` - CRUD pattern
- `app/Models/Supply.php` - Model structure
- `resources/views/inventory/supplies/index.blade.php` - Table pattern
- `resources/views/settings/partials/payment.blade.php` - Currency settings
- `resources/js/app.js` - Chart helpers (CustomApexChart, ins())

## Implementation Sequence

1. **Database** (30 min): Create 5 migrations, run `php artisan migrate`
2. **Models** (45 min): Create 5 models with relationships, scopes, methods
3. **Controllers** (90 min): Create 2 controllers with full CRUD + workflow actions
4. **Views - Categories** (30 min): Create category CRUD views
5. **Views - Expenses** (90 min): Create expense CRUD views and partials
6. **Views - Dashboard** (45 min): Create dashboard with charts
7. **JavaScript** (30 min): Create dashboard charts file, update vite.config.js
8. **Routes** (15 min): Add all routes to web.php
9. **Menu** (10 min): Add expense menu to sidebar
10. **Permissions** (15 min): Add permissions to seeder
11. **Testing** (60 min): Test all CRUD operations, workflow, file uploads
12. **Polish** (30 min): Form validation messages, success alerts, error handling

**Total estimated time:** 6-8 hours

## Testing Checklist

- [ ] Create expense category
- [ ] Edit expense category
- [ ] Delete expense category (with and without expenses)
- [ ] Create expense with file attachment
- [ ] Submit expense for approval (draft → pending)
- [ ] Approve expense (pending → approved)
- [ ] Reject expense with reason (pending → rejected)
- [ ] Mark expense as paid (approved → paid)
- [ ] Cannot edit paid/rejected expenses
- [ ] Cannot delete paid expenses
- [ ] File attachments upload and download correctly
- [ ] Recurring expenses work
- [ ] Budget tracking calculates correctly
- [ ] Dashboard charts display data
- [ ] Filters and search work
- [ ] Pagination works
- [ ] Currency symbol from settings displays correctly
- [ ] Permissions enforce access control

## Notes

- All monetary values use existing currency settings from `payment.currency_symbol` and `payment.currency_code`
- Follows exact same patterns as inventory system for consistency
- Uses Bootstrap 5 for UI components
- Uses Tabler Icons (`ti ti-*`) for icons
- Uses SweetAlert2 for confirmations (already included)
- File uploads handled by Laravel's Storage facade
- Soft deletes enabled for audit trail
- All dates use Carbon for manipulation
