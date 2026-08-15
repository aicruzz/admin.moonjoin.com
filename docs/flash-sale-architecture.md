# Flash Sale — Architecture Record

**Scope:** Laravel Admin / backend (`admin.moonjoin.com`) only.
**Status:** Food Flash Sale enablement is **COMPLETE and FROZEN**.
**Manual verification:** Admin Panel flow verified live; User App consumption verified separately (see §7).

---

## 1. Module support

| Module | Flash Sale | Stock semantics |
|---|---|---|
| Grocery | ✅ Supported (pre-existing) | Real inventory — allocation may not exceed `items.stock` |
| Fashion / ecommerce | ✅ Supported (pre-existing) | Real inventory — allocation may not exceed `items.stock` |
| **Food** | ✅ **Supported (this change)** | **Allocation cap** — no comparison against `items.stock` |
| Pharmacy | ❌ Not enabled — intentional | Inventory protection unchanged (`stock => true`) |
| Parcel | ❌ Not enabled | n/a |
| Rental | ❌ Not enabled — intentional, see §5 | n/a |

## 2. Food now uses the existing Flash Sale engine

Food was enabled by **reusing the existing Flash Sale architecture**. No new Flash Sale
engine, table, model, controller, route, or API was created.

The engine was already module-generic before this change:

- `flash_sales.module_id` — scopes a sale to any module
- `flash_sale_items.item_id` — FK to `items`
- `App\Models\FlashSale` — `scopeModule()`, `scopeActive()`, `scopeRunning()`, `activeProducts()`
- `App\Models\FlashSaleItem` — `scopeActive()`, `scopeAvailable()` (`available_stock > 0`)
- `App\Http\Controllers\Admin\FlashSaleController` — scopes on `Config::get('module.current_module_id')`
- `App\Http\Controllers\Api\V1\FlashSaleController` — scopes on `config('module.current_module_data')['id']`
- Routes (`routes/admin.php`, `routes/vendor.php`, `routes/api/v1/api.php`) — **no module middleware**

Food was excluded only by **UI menu gating** plus **one stock assumption**. Both are
addressed in §3.

## 3. Food stock semantics — allocation cap

Food has stock management disabled in the existing module configuration
(`config/module.php` → `food => ['stock' => false]`), and `items.stock` is
`int(11) DEFAULT 0`. Food items therefore never populate `stock`, so the pre-existing
guard `$request->stock > $item->stock` rejected every Food allocation with
`Item_stock_exceeded`.

The guard in `FlashSaleController@store_product` is now **module-aware**:

```php
$module_type   = $item?->module?->module_type;
$manages_stock = $module_type ? (config('module.' . $module_type)['stock'] ?? true) : true;

if ($manages_stock && $request->stock > $item->stock) { /* reject */ }
```

Consequences:

- **Modules with `stock => true`** (grocery, ecommerce, pharmacy) — inventory
  protection is **unchanged**. A Flash Sale can still never allocate more units than
  the item actually holds.
- **Food (`stock => false`)** — the entered quantity is the **allocation cap for the
  Flash Sale**, not an inventory draw. It is still required to be `>= 1` by the
  existing validator and is still written to `available_stock`, which every read path
  (`FlashSale::activeProducts()`, `FlashSaleItem::scopeAvailable()`, the API items
  query, and the `Helpers` pricing paths) filters on identically for all modules.
  It is depleted at order time exactly as every other module is — see §9.
- **Unknown / null module type** — fails closed; the inventory check is enforced.

This is **not** an unlimited-stock Flash Sale.

Note: `parcel` and `rental` also carry `stock => false`, so they would take the same
branch — but neither can reach this code. Neither exposes a Flash Sale menu, and
Rental does not use the `Item` model at all (§5).

## 4. Files changed

| File | Change |
|---|---|
| `resources/views/layouts/admin/partials/_sidebar_food.blade.php` | Flash Sale menu block added, copied verbatim from `_sidebar_grocery.blade.php` and placed at the same structural position inside the Orders section |
| `resources/views/layouts/vendor/partials/_sidebar.blade.php` | Module allow-list `['grocery','ecommerce']` → `['grocery','ecommerce','food']` |
| `app/Http/Controllers/Admin/FlashSaleController.php` | Inventory comparison made module-aware (§3) |

**Not modified:** Pharmacy sidebar or behaviour · `Modules/Rental/` · the Flash Sale
API controller · `FlashSale` / `FlashSaleItem` models · any migration or schema · any
Flutter codebase.

- **No database migration was required.** All required columns already existed.
- **No API contract was changed.** `GET /api/v1/flash-sales` and `/items` are unchanged;
  Food sales are served by the existing endpoint with the existing response shape.
- **`moduleId` and zone scoping remain the source of truth** for what a client receives.
  Publish behaviour (one published sale per module) is unchanged.

## 5. Rental — deliberately excluded

Rental **must not** be extended into `flash_sales` / `flash_sale_items` / `items.stock`.

Reasons, verified in code:

- `Modules/Rental/Entities/` contains `Vehicle`, `VehicleBrand`, `VehicleCategory`,
  `Trips`, `TripDetails`, `RentalCart`, … — **there is no `Item` model**.
- `flash_sale_items.item_id` is a foreign key to `items`. Vehicles are not items.
- No Flash Sale reference exists anywhere under `Modules/Rental/`.
- Semantically, Flash Sale means *discounted units drawn from finite stock*, while
  rental means *time-windowed availability of a specific asset*. "50 units of this car"
  has no meaning.

**Do not** create fake `Item` records, polymorphic `FlashSaleItem` keys, or alter the
Flash Sale tables to accommodate Rental.

**Future direction:** Rental should receive a **separate, rental-specific discount
mechanism** built on rental/vehicle availability and date ranges — reusing the
*pattern* (start/end date, publish flag, module/zone/store scoping) but not the
`flash_sale_items` table. Not implemented; no Rental tables, APIs, or Admin screens
were created.

**Future UI note (Flutter, not this codebase):** when Rental discounts are eventually
built, the User App may reuse the approved MoonJoin Flash Sale card visual for
consistency, with rental-specific content (e.g. "Special Rental Offer", "₦X / day",
available dates) instead of "Sold 0/100" / "packs". The **backend business model must
remain rental-specific** regardless of visual reuse.

## 6. Validation performed

| Check | Result |
|---|---|
| `php -l app/Http/Controllers/Admin/FlashSaleController.php` | No syntax errors |
| Blade compile — `_sidebar_food.blade.php` | OK |
| Blade compile — vendor `_sidebar.blade.php` | OK |
| `php -l` on both compiled templates | Clean |
| `phpunit tests/Unit` | OK — 10 tests, 27 assertions (1 pre-existing deprecation) |
| Module-aware guard logic table | grocery/ecommerce/pharmacy ENFORCED; food SKIPPED; null/unknown ENFORCED |

Flash sale allocation accounting is covered by
`tests/Feature/FlashSaleAllocationTest.php` (6 tests, 29 assertions), added with the §9
depletion work. Full suite: 17 tests, 57 assertions, passing.

## 7. Manual verification — COMPLETE

### Admin Panel (verified live)

- Food module exposes the **Flash Sales** menu.
- Flash Sale Setup contains an active **Pizza** Flash Sale, duration
  **15/Aug/2026 – 30/Dec/2026**, Active Products **1**, Publish **ON**.
- Flash Sale Product Setup: Product **Pizza**, Store **Perozona**,
  Stock For This Sale **50**, Qty Sold **0**, Price **₦3,750**, Status **ON**.
- Adding the Food item did **not** raise `Item_stock_exceeded` — confirming the
  module-aware guard (§3) works against `items.stock = 0`.

### User App (verified separately, Flutter session)

- Pizza Flash Sale renders with original price **₦7,500**, flash price **₦3,750**,
  **50% OFF**, **Sold 0/50** — served by the existing unchanged API.

### Grocery regression check (verified live)

- Grocery Flash Sale remains functional and independent; **Mango** shows its existing
  Flash Sale behaviour and product-detail flow unchanged.

### Not exercised

A Food flash-sale **order** has not yet been placed, so the sold/allocation
consumption path has not been observed end to end. See §9.

## 8. Frontend status

**No Flutter code was changed in this session.** Food Flash Sale exists in the Admin and
API but will not surface in the User App until implemented in the separate Flutter
User App & Web codebase, against the approved MoonJoin flash card design.

MoonJoin consists of four separate codebases — Flutter User App & Web, Flutter Vendor
App, Flutter Delivery Man App, and this Laravel Admin/backend. Frontend and backend work
must not be combined in one session.

## 9. Order-time depletion — CORRECTED

**An earlier revision of this section was wrong.** It claimed that no code updates
`FlashSaleItem.sold` / `available_stock` at order time. That statement was false and is
retracted. `ProductLogic::update_flash_stock()` already existed and already performed the
depletion; the earlier audit searched for direct column writes and missed the helper.

### What was actually true

- `ProductLogic::update_flash_stock()` incremented `sold`, recomputed
  `available_stock = stock - sold`, and was persisted by the caller's `?->save()`.
- It was called from five order/POS paths: `PlaceNewOrder.php`,
  `Admin/OrderController.php`, `Admin/POSController.php`, `Vendor/OrderController.php`,
  `Vendor/POSController.php`.
- **Grocery and Ecommerce therefore already depleted correctly.**
- **Food did not.** Every call site ran the helper inside
  `if (count($product_data) > 0)`, and `$product_data` is only populated under
  `config('module.'.$type)['stock']`. Food has `stock => false`, so the loop never ran and
  `sold` stayed at `0` — exactly the "Qty Sold 0/50" seen on the Pizza sale.

### The approved fix

1. **Depletion no longer depends on the module stock flag.** `makeOrderDetails()` collects
   a dedicated `$flash_sale_data[]` for every line whose `discount_type == 'flash_sale'`,
   using the real ordered quantity. The same condition that grants the flash sale price
   records the line for depletion, so pricing and allocation cannot disagree.
2. **One authoritative path.** The helper call was removed from the `$product_data` loop in
   `PlaceNewOrder.php` and replaced by a single loop over `$flash_sale_data`, so a
   stock-managing module can never be counted twice.
3. **Overselling is now impossible.** `update_flash_stock()` performs a single conditional
   `UPDATE ... WHERE id = ? AND available_stock >= ?`. InnoDB evaluates the predicate under
   the row lock, so concurrent buyers serialise. A zero-row result returns `null`, and the
   order rolls back with a `flash_sale` 403 rather than completing.
4. **Transactional.** Depletion runs inside the existing order transaction, so a failed
   order consumes no allocation and a successful order always consumes it.
5. Callers still receive a model-or-null, so the four POS/admin/vendor call sites keep
   working unchanged and gain the same oversell protection.

### Deliberately unchanged

- **Cancellation / refund does not restore allocation** (approved decision). Normal product
  stock is restored via `update_stock($item, -$qty, …)`; flash sale allocation is not, and
  that asymmetry is retained. A separate approved requirement may revisit it.
- **`Helpers::product_discount_calculate()` is untouched** (approved decision "D3"). It
  still matches on `item_id` without an `available_stock > 0` condition. If a depleted sale
  reaches placement, the atomic guard rejects the order rather than silently repricing.

## 10. Freeze record

**Food Flash Sale enablement is COMPLETE and FROZEN.**

- Flash Sale remains **module-generic**; `module_id` and module scoping remain the source of truth.
- **Food is now an enabled module** on the existing engine.
- Food uses **allocation-cap semantics**; modules with `stock => true` keep their inventory protection.
- **No migration or schema change** was introduced.
- **No API contract change** was introduced.
- **No Flutter change** was required in this codebase.
- **Existing Grocery behaviour remains unchanged** (verified live — see §7).
- **Pharmacy remains intentionally excluded.**
- **Rental remains intentionally excluded** and must not be extended into `flash_sale_items` (§5).
- **Admin Food Flash Sale flow was manually verified** in the live Admin Panel (§7).
- **User App Food Flash Sale consumption was manually verified separately** (§7).

Validation at freeze: `php -l` clean; both changed Blade templates compile and their
compiled output lints clean; `tests/Unit` passes 10/10; module-aware guard logic table
confirms grocery/ecommerce/pharmacy ENFORCED, food SKIPPED, null/unknown ENFORCED.

No further work is required on this scope unless a new approved requirement is introduced.
