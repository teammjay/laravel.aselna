# Bulk Listing Update Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a dedicated bulk update page that allows multi-field adjustments (price, quantity, titles) for selected listings with intelligent feed batching to respect marketplace limits.

**Architecture:** 
1. **Frontend:** New blade view for bulk configuration.
2. **Backend:** New controller methods to process bulk logic, update local database models (with history tracking), and trigger batched feed generation jobs.
3. **Batching:** Logic to split selected items into marketplace-specific feed sizes (e.g., 1000 items per feed).

**Tech Stack:** Laravel 11, Blade, Tailwind/AdminLTE, PHP 8.2

---

### Task 1: Routes and Basic Controller Setup

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ListingController.php`

- [ ] **Step 1: Add routes for bulk edit page and processing**
Add the following to the `ListingController` group in `routes/web.php`:
```php
Route::post('/listing/bulk-edit', 'bulkEdit')->name('listing.bulkEdit');
Route::post('/listing/bulk-update-process', 'bulkUpdateProcess')->name('listing.bulkUpdateProcess');
```

- [ ] **Step 2: Add placeholder methods in ListingController**
```php
public function bulkEdit(Request $request) {
    $selected_ids = $request->input('selected_list', []);
    if (empty($selected_ids)) {
        return redirect()->back()->with('error', 'No items selected.');
    }
    $listings = ListingItem::with('storeProduct.store')->whereIn('id', $selected_ids)->get();
    return view('listing.bulk_edit', compact('listings'));
}

public function bulkUpdateProcess(Request $request) {
    // Logic will be implemented in Task 3
    return response()->json(['success' => true]);
}
```

- [ ] **Step 3: Commit**
```bash
git add routes/web.php app/Http/Controllers/ListingController.php
git commit -m "feat: add bulk update routes and controller placeholders"
```

---

### Task 2: Create Bulk Update Blade View

**Files:**
- Create: `resources/views/listing/bulk_edit.blade.php`

- [ ] **Step 1: Design the multi-field configuration page**
Create a form with sections for:
- **Price Adjustment:** (Fixed, Add/Sub, Percentage)
- **Quantity Adjustment:** (Fixed, Add/Sub)
- **Title Modification:** (Prefix, Suffix, Find/Replace)
- **Processing Days:** (Fixed)

- [ ] **Step 2: Add listing summary table**
Display SKUs and Titles of selected items at the bottom of the page to confirm selection.

- [ ] **Step 3: Commit**
```bash
git add resources/views/listing/bulk_edit.blade.php
git commit -m "feat: create bulk edit configuration view"
```

---

### Task 3: Implement Bulk Update Logic & Database Batching

**Files:**
- Modify: `app/Http/Controllers/ListingController.php`

- [ ] **Step 1: Implement processing logic in `bulkUpdateProcess`**
- Group items by `store_id`.
- For each store:
    - Update `listing_item` fields based on formulas.
    - Create `ListingItemHistory` records for each.
    - Split into batches (e.g., 1000 items).
    - Create a new `Listing` record for each batch with appropriate `feed_type`.
    - Create `ListingItemLink` records for each batch.
    - Dispatch `Bus::chain([new FeedGenerationJob($listing_id)])`.

- [ ] **Step 2: Write logic for price/qty formulas**
```php
private function calculateValue($current, $type, $value) {
    switch($type) {
        case 'fixed': return $value;
        case 'add': return $current + $value;
        case 'percent': return $current * (1 + ($value / 100));
        default: return $current;
    }
}
```

- [ ] **Step 3: Commit**
```bash
git add app/Http/Controllers/ListingController.php
git commit -m "feat: implement bulk update processing with batching and history"
```

---

### Task 4: UI Integration on Listing Page

**Files:**
- Modify: `resources/views/listing/list.blade.php`

- [ ] **Step 1: Add "Bulk Update" button to actions**
Add a button that submits the selected checkbox IDs to the `/listing/bulk-edit` POST route.

- [ ] **Step 2: Update JavaScript to handle the new action**
```javascript
$('#bulk_update_btn').on('click', function() {
    let selected = [];
    $('input[name="listing_item[]"]:checked').each(function() {
        selected.push($(this).val());
    });
    if (selected.length === 0) return alert('Select items first');
    
    // Create hidden form to submit via POST
    let form = $('<form action="{{ route("listing.bulkEdit") }}" method="POST">@csrf</form>');
    selected.forEach(id => form.append(`<input type="hidden" name="selected_list[]" value="${id}">`));
    $('body').append(form);
    form.submit();
});
```

- [ ] **Step 3: Commit**
```bash
git add resources/views/listing/list.blade.php
git commit -m "feat: integrate bulk update button into listing list"
```

---

### Task 5: Testing & Validation

- [ ] **Step 1: Create a feature test for bulk pricing**
Create `tests/Feature/BulkUpdateTest.php` and verify that price adjustments (+2$) correctly update `listing_item` and create `Listing` batches.

- [ ] **Step 2: Verify history creation**
Ensure `listing_item_history` contains the old values after a bulk run.

- [ ] **Step 3: Verify feed batching**
Select 1500 items and verify that 2 `Listing` records are created for a store.

- [ ] **Step 4: Commit**
```bash
git add tests/Feature/BulkUpdateTest.php
git commit -m "test: add bulk update validation tests"
```
