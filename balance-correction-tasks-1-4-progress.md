# Balance correction — tasks 1 to 4

Source: Balance Correction Required, updated 25-09-2026 at 6 PM.

Status key: `[ ]` not started, `[~]` in progress, `[x]` done, `[!]` blocked.

Current position: browser check on 28 Sep 2026 at http://127.0.0.1:8000. The TEXT columns are on `user_details` and a business customer save wrote Account Type and Territory. Test rows were removed afterward. Still open: Series edit, product save/reload, a new product, Chandigarh UTGST (no company address contains Chandigarh), and company/brand Not In List.

Locked decisions:

- Series Master filters and sorts every stored column: ID, name, code, description, status, created, updated.
- A saved SKU photo can be replaced and cannot be removed.
- Saved batch rows are frozen. New unsaved batch rows can still be removed.
- Territory auto-selects from the customer business state and can still be changed.
- Tax follows the note: same state is CGST+SGST, Maharashtra to Delhi is IGST, Chandigarh seller to Delhi is UTGST, India to another country offers LUT or IGST.
- Not In List is added only where a custom value can be stored as text. Closed lists and id-based dropdowns are left unchanged.

## Task 1 — Series Master, filters, and sorting

The `series_masters` table is already present with id, name, code, description, status, created_at, and updated_at.

- [x] 1. Lock task 1 from the source note: Series Master must be added with all filters and sorting.
- [x] 2. Confirm the current pieces: `SeriesMasterController`, `SeriesMaster`, `routes/admin.php` resource `series`, menu entry Series Master, and `resources/views/backend/series/index.blade.php`.
- [x] 3. Compare that list with Tax Master so filters, sort links, reset, and the “filters applied” badge follow the same admin pattern.
- [x] 4. Filter every stored field: ID, name, code, description, status, created from/to, updated from/to.
- [x] 5. Sort every stored column: ID, name, code, description, status, created, updated.
- [x] 6. Keep the active filter values when a column sort changes, and keep the sort when a filter is applied.
- [x] 7. Keep pagination on the same filter and sort.
- [x] 8. Confirm Add, Edit, and Delete still save name, code, description, and status.
- [x] 9. The `series_masters` table already exists, so no new SQL is required for this task.
- [~] 10. Browser: empty list, Add saved “Browser Check Series”, code filter returned that row, and all seven sort links are present. Edit, reset, and a multi-row sort order were not clicked. The test row was deleted.
- [x] 11. The list, filter modal, and sortable headers already cover every column. No further code gap was found.
- [ ] 12. Mark task 1 done only when the list, filters, and sorting are clicked through in the browser.

## Task 2 — Add/Edit product, Price & Stock: freeze SKU, photo, and attributes

- [x] 13. Lock task 2: saved SKU and photo cannot be deleted; they can be added or edited; a saved photo can be replaced.
- [x] 14. Locate the edit screen: `resources/views/backend/product/products/edit.blade.php`, `delete_variant()`, photo `.remove-files`, and `resources/views/backend/product/products/sku_combinations_edit.blade.php`.
- [x] 15. Separate a saved SKU row from a row created in the current unsaved edit.
- [x] 16. On edit, saved SKU rows have no delete control.
- [x] 17. Leave price, stock, and the other existing SKU fields editable.
- [x] 18. Allow a newly added attribute to create a new SKU row in the same edit.
- [x] 19. Stop removal of an attribute choice that already belongs to a saved SKU. `restoreLockedAttributes()` and `ProductService::preserveSavedVariantChoices()` put a removed saved attribute back.
- [x] 20. On edit, stop removal of a photo that is already saved on that SKU. The remove control is stripped and an empty upload keeps the saved file.
- [x] 21. Choosing another file replaces the saved photo.
- [x] 22. Reject a posted delete of a saved SKU or saved photo on the server. An empty SKU keeps the saved SKU. An empty photo keeps the saved photo.
- [x] 23. Leave the new-product screen able to add and remove SKU rows and photos before the first save.
- [x] 24. Saved batch rows show “Saved” instead of a remove button. `removeBatchRow()` returns when the row has an id. Update sync does not delete a saved batch that is missing from the post. A new unsaved batch row can still be removed.
- [~] 25. Browser on product 43 edit: clearing SKU DPI-B47 restored it, locked photos have no remove control, and saved batches show “Saved”. Photo replace, a new attribute, save, and reload were not done.
- [ ] 26. Check that creating a brand-new product still adds, edits, and removes SKU rows before save.

## Task 3 — Customer registration: Account Type, Territory, and tax

The TEXT columns are on `user_details`. Add and Edit show Account Type, Territory, and the tax label. A domestic save no longer stores International IGST unless that sale is international.

- [x] 27. Lock task 3: Account Type and Territory dropdowns; four account types; Not In List; territory from the business state; tax from Tax Master beside the account number; seller from Company Master.
- [x] 28. Add Account Type with Customers, Customers and Suppliers, Suppliers, Service Providers, and Not In List.
- [x] 29. Show a manual text box only when Not In List is selected.
- [x] 30. Territory lists business states and auto-selects the business state.
- [x] 31. The auto-selected territory stays changeable, including Not In List.
- [x] 32. Read the seller name and state from Company Master address text.
- [x] 33. Read the buyer state and country from the customer business address on the same form.
- [x] 34. Same Indian state shows Intra-State CGST+SGST.
- [x] 35. Two different Indian states show Inter-State IGST. Maharashtra seller to Delhi buyer uses IGST.
- [x] 36. A Union Territory seller to a different state shows UTGST. Chandigarh seller to Delhi buyer uses UTGST. Delhi is not treated as that seller case, so the Maharashtra-to-Delhi example stays IGST.
- [x] 37. A buyer country other than India shows International LUT and International IGST.
- [x] 38. The tax label is placed beside the account number.
- [x] 39. The rate is taken from an active Tax Master row. If no row matches, the label says the rate was not found.
- [x] 40. The account name used in the sidebar updates from Account Name.
- [x] 41. Edit of customer 2668 saved Account Type `Customers` and Territory `Telangana`. Not In List saved `Browser Type 928` into `account_type_custom`. Those test values were cleared back to NULL.
- [~] 42. Add form: Maharashtra seller to Maharashtra is CGST+SGST and territory becomes Maharashtra; Delhi is IGST and territory becomes Delhi; Iran shows LUT and IGST. Chandigarh to Delhi was not shown because neither company address contains Chandigarh. Seller used was Virbac Animal Health India Pvt Ltd, Maharashtra.

## Task 4 — Not In List on dropdowns

Not In List is on dropdowns whose saved value is free text. It was not added to closed lists or to dropdowns that store a numeric id.

Already covered:

- Customer Type, Account Type, and Territory on business customer Add/Edit.
- Company Type on the company form.
- Product brand name, and marketed-by, on the product form.

Left unchanged because a custom value would fail validation or a foreign key:

- Current Status, domestic/international, GST/Aadhaar/IEC/passport.
- Country, state, and city.
- Shipping method, transport mode, surface mode, delivery type.
- Transport and Booked To.
- Tax type, video provider, and batch discount type.

- [x] 43. Lock task 4: Not In List shows a text box, and it is not applied where an enum or id would break the save.
- [x] 44. Reuse `__not_in_list__` and the show/hide text box.
- [x] 45. List the admin dropdowns on the customer, company, and product forms and skip the closed lists above.
- [x] 46. Add Not In List to Customer Type, Account Type, and Territory. Company Type, brand name, and marketed-by already had it.
- [x] 47. Store the typed value on that record. It is not inserted into the master list.
- [x] 48. Restore a saved manual value on Edit and keep Not In List selected.
- [ ] 49. Check one Add and one Edit for each dropdown changed in this task.
- [ ] 50. Re-check company type, brand name, and marketed-by so those existing Not In List fields still save.

## SQL to run before task 3 can save

`series_masters` already exists. Do not run a migration. Run this on `user_details` only if those columns are still missing:

`user_details` is already near MySQL’s 65535-byte row limit, so these columns are TEXT. TEXT is stored off the main row. Do not use VARCHAR here.

```sql
ALTER TABLE user_details
    ADD COLUMN account_type TEXT NULL AFTER account_name_business,
    ADD COLUMN account_type_custom TEXT NULL AFTER account_type,
    ADD COLUMN territory TEXT NULL AFTER account_type_custom,
    ADD COLUMN territory_custom TEXT NULL AFTER territory,
    ADD COLUMN international_tax_choice TEXT NULL AFTER territory_custom;
```
