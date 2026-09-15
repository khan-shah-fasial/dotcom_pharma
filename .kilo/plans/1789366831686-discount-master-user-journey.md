# Discount Master — User Journey

## Scope
This document covers the **end-to-end user journey** for the Discount Master module only. It does not cover order creation, reporting, or product management flows.

---

## 1. Actors & Permissions

| Actor | Permissions |
|---|---|
| **Super Admin** | Full CRUD on all discount masters, bulk activate/deactivate |
| **Admin** | CRUD on own-created masters |
| **Seller** | CRUD on own-created masters (own products/batches only) |
| **Staff (Sales)** | View active discounts only (read-only) |

---

## 2. Journey 1 — Admin/Seller Creates a New Discount Entry

### Step 1: Open Discount Master
- Path: Admin panel → Marketing → Discount Master
- System displays the **listing page** with a searchable, filterable table of all existing discount entries.
- Each row shows: Discount ID, Type badge, Applied On, Product/SKU/Batch, Base Rate, Discount Value, Effective Rate, Status pill, Date Range, Actions.

### Step 2: Click "Add New Discount"
- System opens the **Create form**.
- The form is divided into sections that show/hide based on selections.

### Step 3: Select Discount Type
- User picks one: **Batchwise Discount**, **Productwise Discount**, **Pointwise Earn**, **Amount wise Discount**, **Schemewise Discount**.
- Discount ID is auto-generated (e.g., `D-BW-001`) and shown as read-only.
- Changing the type reveals or hides the relevant section below.

### Step 4: Select "Applied On"
- User picks: **SKU Product**, **Full Variant**, or **Same as Batch/Lot.No**.
- The form adapts:
  - `SKU Product` → product search field + stock selector
  - `Full Variant` → product selector + variant/stock selector
  - `Same as Batch/Lot.No` → batch selector + batch details (Mfg Date, Expiry, COA)
- Selected product/batch details auto-populate: Stock Available, MRP, Role Prices (PTS/PTR/PTD/Govt/Export/B2C).

### Step 5: Set Base Rate ("Stock Value As Per")
- User selects the price type to use as the base: PTS / PTR / PTD / Govt. / Export / Customers(B2C) / M.R.P
- The corresponding rate field is highlighted and filled.
- The full role price grid is shown for reference but only the selected price is editable.
- Qty Slab (From / To) is entered if tiered pricing is needed.
- Rate, Amount, and Effective Rate auto-calculate.

### Step 6: Configure Discount Value
- Fields change based on Discount Type:
  - **Batchwise / Productwise / Amount Wise**: Discount value + type (Amount or %). Discount % auto-calculates.
  - **Pointwise**: Points Earn per unit, Points Amount auto-calculates.
  - **Schemewise**: Choose Scheme Free Qty / Scheme % / Scheme Value. Toggle "If Scheme Product Is Same" → if No, a Scheme Product selector appears.
- Unselected discount-type sections are hidden automatically.

### Step 7: Set Dates & Activation
- **From Date** (required): when the discount becomes active.
- **To Date** (optional): if blank, discount stays active until manually deactivated.
- **Status**: Active / Deactivate toggle (default = Active).

### Step 8: Save
- User clicks **Save**.
- System validates required fields, date ranges, discount value > 0, percent ≤ 100.
- On success: Flash message "Discount Master saved successfully." Redirect to listing.
- On failure: Inline validation errors, form state preserved.

---

## 3. Journey 2 — Admin Manages the Listing Page

### Step 1: View Listing
- Table columns: Checkbox | Discount ID | Type | Applied On | Product/SKU/Batch | Base Rate | Discount Value | Effective Rate | Status | Date Range | Actions.

### Step 2: Search & Filter
- **Search**: by Discount ID, Product name, SKU, Batch number.
- **Filter by Type**: dropdown.
- **Filter by Status**: Active / Inactive / All.
- **Filter by Date Range**: calendar picker.
- **Filter by Category**: cascading dropdown.

### Step 3: Bulk Actions
- Select rows → Bulk Activate / Bulk Deactivate / Bulk Delete.
- Confirmation modal before destructive actions.

### Step 4: Quick Actions
- Toggle Active/Inactive directly from the row (AJAX, no reload).
- Click a row to expand inline details: base rate, discount %, effective rate, COA download link.

---

## 4. Journey 3 — Seller Manages Own Discounts

### Step 1: Open Seller Discount Master
- Path: Seller Dashboard → Discount Master.
- System shows only discounts created by the logged-in seller.

### Step 2: Create / Edit
- Same form as Admin, but product/batch selectors are limited to the seller's own catalog.

### Step 3: Visibility
- Seller's discounts appear to Admin staff during backend order creation.
- Seller cannot see or modify Admin-created discounts.

---

## 5. Journey 4 — Discount Expiry & Auto-Deactivation

### Step 1: Daily Cron
- Command: `discount:deactivate-expired`
- Finds all `active` discounts where `discount_end_date < now()`.
- Sets `status = inactive`.
- Logs each deactivation.

### Step 2: User Impact
- Expired discounts disappear from the order creation picker.
- Existing orders that already used the discount are **not changed**.

---

## 6. Journey 5 — Staff (Sales) Views Active Discounts (Read-Only)

### Step 1: Open Discount Master (Read-Only)
- Path: Admin panel → Discount Master (staff view).
- All create/edit/delete/toggle buttons are hidden.
- Search and filter remain available.

### Step 2: Reference During Order Entry
- Staff creating a backend order can reference this page to see which discounts are active for a product before adding it to the order.
- The order creation product picker also surfaces active discounts inline.

---

## 7. Dynamic Form Behavior Summary

| User Action | System Response |
|---|---|
| Change Discount Type | Hides all type-specific sections; shows only the relevant one |
| Change Applied On | Switches between product search / variant selector / batch selector |
| Select "Same as Batch/Lot.No" | Reveals batch fields (Mfg, Expiry, COA); hides product/variant selectors |
| Select Stock Value As Per (e.g., PTR) | Highlights PTR rate; dims other role prices |
| Toggle "Scheme Product Is Same" OFF | Reveals Scheme Product selector |
| Set From/To Date with To < From | Shows validation error |
| Click Save | Validates, saves, redirects to listing with flash message |

---

## 8. End-to-End Flow Diagram

```
Admin/Seller
    │
    ├─► Open Discount Master Listing
    │       │
    │       ├─► Search / Filter / Bulk Activate / Deactivate / Delete
    │       │
    │       └─► Click "Add New Discount"
    │               │
    │               ├─► Select Discount Type
    │               │       └─► Form sections show/hide accordingly
    │               │
    │               ├─► Select Applied On
    │               │       └─► Product / Variant / Batch selector appears
    │               │
    │               ├─► Set Stock Value As Per (base rate)
    │               │       └─► Rate / Amount / Effective Rate auto-calculate
    │               │
    │               ├─► Enter Discount Value (Amount or %)
    │               │       └─► Discount % auto-calculates
    │               │
    │               ├─► Set From Date / To Date
    │               │
    │               ├─► Toggle Active / Deactivate
    │               │
    │               └─► Save
    │                       └─► Redirect to Listing (success flash)
    │
    └─► Daily: Cron deactivates expired entries
            └─► Expired discounts no longer appear in order creation
```

---

## 9. Out of Scope (Not Part of This Module)
- Order creation flow
- Coupon management
- Flash deals
- Discount reporting / analytics
- Product import/export
- Backend order pricing engine changes
