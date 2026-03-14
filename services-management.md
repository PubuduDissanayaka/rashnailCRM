# Service Management & Service Packages Implementation Plan

## Executive Summary

Implement a comprehensive **Service Management System** with **Service Packages/Bundles** feature for Rash Nail Lounge. This will enable the salon to:
- Manage individual services (manicures, pedicures, gel nails, etc.)
- Create service packages/bundles with discounted pricing
- Offer combo deals and promotions
- Streamline appointment booking with pre-configured service bundles

**Estimated Time:** 4-6 hours total
- Phase 1 (Services): 2-3 hours
- Phase 2 (Packages): 2-3 hours

---

## Why Service Packages Are Essential

Service packages provide significant business value:
- **Increased Revenue**: Encourage customers to book multiple services together
- **Competitive Pricing**: Offer attractive discounts while maintaining profitability
- **Simplified Booking**: Pre-configured bundles streamline the appointment process
- **Popular Combos**: Classic combinations like "Mani+Pedi" or "Full Set" become one-click bookings
- **Marketing Power**: Promotional packages drive customer engagement

---

## Implementation Strategy

### Two-Phase Approach

**Phase 1: Service Management** (Foundation)
- Individual service CRUD operations
- Service pricing and duration management
- Active/inactive status
- Foundation for both appointments and packages

**Phase 2: Service Packages** (Enhancement)
- Package creation with multiple services
- Bundle pricing with discounts
- Many-to-many relationships via pivot table
- Package-based appointment booking

---

## Database Architecture

### Phase 1: Existing Services Table

**Table:** `services` (already exists)
```sql
- id (bigint, PK)
- name (varchar 255)
- description (text, nullable)
- price (decimal 10,2)
- duration (integer, minutes)
- is_active (boolean, default true)
- created_at, updated_at
```

**Status:** ✅ No migration needed - table already exists and is properly structured.

---

### Phase 2: New Tables for Service Packages

#### Service Packages Table

**Migration:** `create_service_packages_table.php`

```php
Schema::create('service_packages', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->decimal('base_price', 10, 2); // Sum of services
    $table->decimal('discounted_price', 10, 2); // Actual selling price
    $table->decimal('discount_percentage', 5, 2)->nullable();
    $table->integer('total_duration'); // Pre-calculated in minutes
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Key Design Decisions:**
- `base_price`: Sum of all included service prices (for transparency and auditing)
- `discounted_price`: Actual bundle price customers pay (enables promotional pricing)
- `discount_percentage`: Auto-calculated field showing savings percentage
- `total_duration`: Pre-calculated total for efficient appointment scheduling

---

#### Pivot Table: Package-Service Relationship

**Migration:** `create_package_service_table.php`

```php
Schema::create('package_service', function (Blueprint $table) {
    $table->id();
    $table->foreignId('package_id')->constrained('service_packages')->onDelete('cascade');
    $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
    $table->integer('quantity')->default(1); // Allow 2x same service
    $table->integer('sort_order')->default(0); // Display order
    $table->timestamps();

    $table->unique(['package_id', 'service_id']);
    $table->index('package_id');
    $table->index('service_id');
});
```

**Why This Structure:**
- `quantity`: Support bundles like "2x Massage + 1x Facial"
- `sort_order`: Control display order of services in package
- Unique constraint: Prevent duplicate service entries per package
- CASCADE delete: Automatically clean up relationships when package/service deleted

---

## Key Features

### Service Management Features
1. ✅ Create/edit/delete individual services
2. ✅ Set pricing and duration for each service
3. ✅ Activate/deactivate services (seasonal offerings)
4. ✅ Soft delete for audit trail
5. ✅ Business logic prevents deleting services in use
6. ✅ Permission-based access control

### Service Package Features
1. ✅ Create bundles with 2+ services
2. ✅ Set discounted pricing (automatic savings calculation)
3. ✅ Support service quantities (e.g., "2x manicure")
4. ✅ Dynamic pricing preview during package creation
5. ✅ Visual savings display (show customer what they save)
6. ✅ Prevent deletion of packages with appointments
7. ✅ Full CRUD with permission checks

---

## Implementation Phases

### Phase 1: Service Management (2-3 hours)

**Step-by-step:**

1. **Add SoftDeletes to Service Model**
   - Create migration: `add_soft_deletes_to_services_table.php`
   - Update Service model with `SoftDeletes` trait

2. **Create ServiceController**
   - Generate: `php artisan make:controller ServiceController`
   - Implement CRUD methods with authorization
   - Add validation rules

3. **Create Service Views**
   - Index page with DataTable and stats cards
   - Create/Edit forms with Choices.js
   - Show page with appointment history

4. **Configure Routes & Permissions**
   - Add routes to `web.php`
   - Update `RoleSeeder` with service permissions
   - Add menu item to sidebar

5. **Test & Seed**
   - Test CRUD operations
   - Create `ServiceSeeder` with nail salon services
   - Verify permission checks

---

### Phase 2: Service Packages (2-3 hours)

**Step-by-step:**

1. **Create Database Structure**
   - Migration: `create_service_packages_table.php`
   - Migration: `create_package_service_table.php`
   - Run migrations

2. **Create Models**
   - Create `ServicePackage` model
   - Update `Service` model with packages relationship

3. **Create ServicePackageController**
   - Implement complex store logic (attach services with pivot data)
   - Implement update logic (sync services)
   - Auto-calculate pricing and duration

4. **Create Package Views**
   - Index page with savings display
   - Create form with dynamic service selection
   - JavaScript for real-time pricing calculation
   - Edit form with pre-populated services

5. **Configure & Test**
   - Add routes and permissions
   - Add menu item
   - Test package creation with multiple services
   - Verify pricing calculations
   - Create sample packages

---

## Success Metrics

### Service Management
- ✅ Admin can create services
- ✅ Services display in list view with stats
- ✅ Service price and duration editable
- ✅ Services can be activated/deactivated
- ✅ Service deletion prevents if used in appointments or packages
- ✅ Soft deletes work correctly
- ✅ Staff can view but not create/edit/delete

### Service Package Management
- ✅ Admin can create packages with 2+ services
- ✅ Package pricing auto-calculated correctly
- ✅ Discount percentage shown accurately
- ✅ Services can have quantities in packages
- ✅ Package edit syncs services correctly
- ✅ Package deletion prevents if used in appointments
- ✅ Package list shows savings per bundle
- ✅ Staff can view but not create/edit/delete

---

## Future Enhancements

1. **Appointment Integration**
   - Add package selection in appointment booking
   - Update appointments table with `package_id` FK
   - Show package details in appointment views

2. **Reporting & Analytics**
   - Most popular services
   - Most popular packages
   - Revenue by service/package
   - Package conversion rate

3. **Advanced Pricing**
   - Time-based pricing (peak/off-peak hours)
   - Tiered discounts (buy 3+ packages, get extra discount)
   - Seasonal promotions

4. **Service Categories**
   - Group services (Nails, Spa, Beauty, etc.)
   - Filter by category

5. **Online Booking**
   - Customer-facing package selection
   - Real-time pricing display
   - Package recommendations

---

## Critical Files

### Phase 1 Files:
- `database/migrations/XXXX_add_soft_deletes_to_services_table.php` (new)
- `app/Models/Service.php` (modify)
- `app/Http/Controllers/ServiceController.php` (new)
- `resources/views/services/*.blade.php` (new - 4 files)
- `routes/web.php` (modify)
- `database/seeders/RoleSeeder.php` (modify)
- `database/seeders/ServiceSeeder.php` (new)

### Phase 2 Files:
- `database/migrations/XXXX_create_service_packages_table.php` (new)
- `database/migrations/XXXX_create_package_service_table.php` (new)
- `app/Models/ServicePackage.php` (new)
- `app/Http/Controllers/ServicePackageController.php` (new)
- `resources/views/service-packages/*.blade.php` (new - 4 files)
- `database/seeders/ServicePackageSeeder.php` (new)

---

## Sample Services for Seeding

**Nail Services:**
- Basic Manicure - $25 - 30 min
- Deluxe Manicure - $35 - 45 min
- Gel Manicure - $45 - 60 min
- Basic Pedicure - $40 - 45 min
- Deluxe Pedicure - $55 - 60 min
- Gel Pedicure - $65 - 75 min
- Full Set Acrylic - $60 - 90 min
- Nail Art - $15 - 20 min

**Sample Packages:**
- "Mani + Pedi Combo" - Basic Manicure + Basic Pedicure - $60 (save $5) - 75 min
- "Gel Duo" - Gel Manicure + Gel Pedicure - $100 (save $10) - 135 min
- "Full Pamper Package" - Deluxe Manicure + Deluxe Pedicure + Nail Art - $95 (save $10) - 125 min

---

## Conclusion

This implementation provides a robust foundation for service and package management at Rash Nail Lounge. By following established patterns from customer and user management, the system maintains consistency while adding powerful new capabilities for service bundling and promotional pricing.

**Total Estimated Time:** 4-6 hours for both phases combined.

**Next Steps:** Begin Phase 1 implementation with Service Management CRUD operations.
