# Coupon System POS Staff Guide

## Introduction

This guide is for Point‑of‑Sale (POS) staff who apply coupons during customer checkout. It explains how to validate, apply, and remove coupons, interpret error messages, and handle common scenarios.

## Prerequisites

### Required Permissions
- **`view sales`** – Required to access POS interface
- **`apply coupons`** – Implicit with `view sales` (if coupon module is enabled)

### POS Interface Overview

The POS interface includes a dedicated **Coupon** section with:
- **Coupon Code input field** – Enter coupon codes
- **Applied Coupons list** – Shows currently applied coupons with discount amounts
- **Validation messages** – Success/error alerts
- **Remove buttons** – Remove individual coupons

## Applying a Coupon

### Step‑by‑Step Process

1. **Add items** to the sale as usual.
2. **Click the Coupon field** in the right‑hand panel (labeled “Coupon Code”).
3. **Enter the coupon code** exactly as provided (case‑insensitive).
4. **Press Enter** or click the **Apply** button.
5. **Observe the validation result**:
   - **Success** – Coupon appears in Applied Coupons list with discount amount.
   - **Error** – Red alert appears explaining why coupon cannot be applied.

### Visual Feedback

- **Valid coupon** – Green checkmark, coupon added to list, sale totals updated immediately.
- **Invalid coupon** – Red “X” icon with error message; sale totals unchanged.

### Multiple Coupons

If the coupon is **stackable**, you can apply additional coupons:
1. Apply first coupon successfully.
2. Enter another coupon code in the same field.
3. Repeat until all desired coupons are applied.

**Note:** The system prevents applying the same coupon multiple times (unless per‑customer limit allows).

## Understanding Validation Errors

When a coupon fails validation, one of the following messages will appear:

| Error Message | Meaning | Action Required |
|---------------|---------|-----------------|
| `Coupon not found.` | Code doesn’t match any active coupon. | Verify code spelling; ask customer for correct code. |
| `Coupon is not active or has expired.` | Coupon is inactive or past its end date. | Inform customer coupon is no longer valid; suggest alternative. |
| `Coupon usage limit reached.` | Total redemption limit exhausted. | Inform customer coupon is fully used; cannot be applied. |
| `You have already used this coupon the maximum number of times.` | Customer has hit per‑customer limit. | Inform customer they cannot use this coupon again. |
| `Minimum purchase amount not met.` | Sale subtotal is below coupon’s minimum requirement. | Add more items to sale or increase quantities. |
| `Coupon is not valid for this location.` | Location restriction prevents redemption here. | Inform customer coupon is location‑specific; cannot override. |
| `Coupon is for new customers only.` | Customer has previous purchases or account older than 30 days. | Verify customer status; if they are new, ensure they’re logged in. |
| `Coupon is for existing customers only.` | Customer has no prior purchases. | Customer must have at least one completed sale. |
| `You are not eligible for this coupon.` | Customer not in required customer group. | Explain coupon is for specific group (e.g., VIP, Students). |
| `Coupon does not apply to any items in the sale.` | Product restriction excludes all items in cart. | Check which products are eligible; suggest adding eligible items. |
| `Coupon cannot be combined with other coupons.` | Coupon is not stackable and other coupons are already applied. | Remove other coupons or ask customer which coupon they prefer. |

### How to Resolve Common Errors

#### “Minimum purchase amount not met”
- **Action:** Increase sale subtotal by adding items or upgrading quantities.
- **Tip:** Tell customer “You need to spend $X more to use this coupon.”

#### “Coupon not valid for this location”
- **Action:** Confirm coupon is intended for this store. If mistake, apologize and offer alternative discount if authorized.

#### “Coupon is for new customers only”
- **Action:** Verify customer’s purchase history. If they are genuinely new, ensure they are logged into their account (or create one). If they have previous purchases, explain terms.

#### “Coupon does not apply to any items in the sale”
- **Action:** Show customer which items are eligible (ask manager for list). Suggest swapping in eligible items.

## Removing a Coupon

### Single Coupon Removal
1. In the **Applied Coupons** list, click the **X** icon next to the coupon.
2. Confirm removal if prompted.
3. Sale totals will be recalculated without that discount.

### Removing All Coupons
- Click **Clear All Coupons** button (if available) or remove each individually.

**Note:** Removing a coupon does not affect its redemption count; the redemption record remains (coupon considered “used”). To fully reverse a redemption, you must void the sale.

## Stackable vs Non‑Stackable Coupons

### Stackable Coupons
- Can be combined with other coupons.
- Indicated by “Stackable: Yes” in coupon details (admin view).
- No limit on number of stackable coupons (practical limit may apply).

### Non‑Stackable Coupons
- Cannot be combined with any other coupon.
- If a non‑stackable coupon is applied, any existing coupons are automatically removed (system may prompt).
- Indicated by “Stackable: No”.

### Best Practice
- Always inform customer if their coupon cannot be combined with others.
- When multiple non‑stackable coupons are available, let customer choose which one to apply.

## Customer‑Specific Coupons

### Customer Eligibility Types
Coupons may be restricted to:
- **All customers** – No restriction.
- **New customers** – Customer account created within last 30 days with zero prior purchases.
- **Existing customers** – At least one completed sale in history.
- **Customer groups** – Customer must belong to selected group(s).

### Verifying Customer Eligibility
1. Ensure customer is **logged in** (their name appears in POS header).
2. If customer is not logged in, prompt them to log in or create account.
3. If coupon requires group membership, check customer’s group in their profile (ask supervisor if unsure).

## Special Coupon Types

### Percentage Discount
- Discount calculated as percentage of subtotal.
- May have **maximum discount cap**.
- Example: 10% off up to $20.

### Fixed Amount Discount
- Flat amount deducted from subtotal.
- Example: $5 off.

### Buy X Get Y (BOGO)
- Buy certain quantity, get another free (or discounted).
- System automatically identifies eligible items and applies discount.
- Staff may need to verify items meet BOGO conditions.

### Free Shipping
- Waives shipping fees (if shipping is charged separately).
- Discount appears as shipping cost reduction.

### Tiered Discount
- Discount varies based on purchase amount.
- Example: Spend $100 → 5% off; Spend $200 → 10% off.
- System automatically selects correct tier.

## Handling Customer Questions

### “Can I use this coupon?”
- **Check:** Apply coupon; if error, read message to customer.
- **Explain:** “The coupon requires a minimum purchase of $X” or “This coupon is only for new customers.”

### “Why is my discount less than expected?”
- **Possible reasons:**
  - Percentage coupon capped at maximum amount.
  - Tiered discount based on lower tier.
  - Product restrictions exclude some items.
- **Action:** Show applied discount breakdown in POS; refer to coupon terms.

### “Can I use multiple coupons?”
- **Answer:** “Yes, if they are stackable. Let me check.” Apply first coupon, then attempt second.

### “The coupon code isn’t working.”
- **Troubleshooting steps:**
  1. Verify code spelling (watch for 0/O, 1/I).
  2. Check coupon is still valid (not expired, usage limit not reached).
  3. Ensure customer meets eligibility criteria.
  4. Try applying on a different sale (test with small eligible purchase).
  5. If still failing, contact supervisor or admin.

## Voiding a Sale with Coupons

When voiding a sale that used coupons:
1. Void sale as usual (POS void function).
2. **Coupon redemptions are automatically reversed** – the coupon’s usage count is decremented.
3. Coupon becomes available for future use (unless other restrictions apply).

**Important:** Only voids performed through the POS void routine trigger redemption reversal. Manual database edits will not.

## Training Scenarios

### Scenario 1: First‑Time Customer Coupon
- Customer presents “WELCOME10” (10% off first purchase).
- Steps:
  1. Verify customer is new (no prior purchases).
  2. Apply coupon.
  3. If error “Coupon is for new customers only,” create customer account before applying.

### Scenario 2: Minimum Purchase Coupon
- Coupon “SAVE20” requires $50 minimum.
- Sale subtotal is $45.
- Action: Suggest adding a $5+ item to qualify.

### Scenario 3: Product‑Specific Coupon
- Coupon “HAIRCUT5” valid only on hair‑cutting services.
- Customer has haircut and shampoo.
- Discount applies only to haircut line item.

### Scenario 4: Expired Coupon
- Coupon expired yesterday.
- Error: “Coupon is not active or has expired.”
- Action: Inform customer coupon is expired; offer alternative if available.

## POS Interface Tips

### Keyboard Shortcuts
- **Tab** – Move to coupon field after scanning items.
- **Enter** – Apply coupon after typing code.
- **Esc** – Clear coupon input.

### Quick Validation
- To quickly test a coupon without affecting a live sale, use the **Test Validation** tool in admin panel (requires admin access).

### Viewing Applied Discounts
- Applied coupons list shows discount per coupon and total discount.
- Hover over coupon for brief details (type, restrictions).

## Escalation Procedures

### When to Contact Supervisor
- Coupon validation error that seems incorrect (e.g., eligible customer rejected).
- System error (e.g., “Internal server error”).
- Customer disputes discount amount.
- Suspected coupon fraud (multiple identical codes).

### When to Contact Admin
- Need to create/update a coupon immediately.
- Bulk coupon import required.
- Report of coupon performance needed for manager.

## Security & Fraud Prevention

### Staff Guidelines
- **Never** share internal coupon codes with customers.
- **Verify** customer eligibility when required.
- **Report** suspicious patterns (same customer using many coupons, counterfeit codes).
- **Do not** manually override coupon restrictions (system prevents this).

### Coupon Security Features
- Unique codes generated with randomness.
- Usage limits prevent unlimited redemptions.
- IP address and user agent logged for each redemption.
- Redemption history auditable.

## Quick Reference Card

### Steps to Apply Coupon
1. Scan/Add items.
2. Click **Coupon** field.
3. Type code (case‑insensitive).
4. Press **Enter**.
5. Check for success/error.

### Common Error Quick Fixes
- **“Not found”** – Check spelling.
- **“Expired”** – Offer apology; cannot override.
- **“Minimum purchase”** – Increase subtotal.
- **“Not valid for location”** – Cannot override.
- **“Not valid for customer”** – Verify login/group.

### Phone Support
- Supervisor: Ext. 1234
- Admin: Ext. 5678
- IT Support: Ext. 9012

---

*Document last updated: March 8, 2026*