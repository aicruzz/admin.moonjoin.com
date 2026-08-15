# Flash Sale — Architecture Record

**Scope:** Laravel Admin / backend (`admin.moonjoin.com`) only.
**Status:** Food enablement **COMPLETE / FROZEN** for this scope.
**Manual verification:** Implementation validated at code level; manual Food Flash Sale end-to-end verification remains pending.

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
  existing validator, is still written to `available_stock`, and still decrements as
  orders are placed. When the allocation is exhausted the item drops out through the
  existing `available_stock > 0` filtering.
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

There is no Flash Sale automated test suite in this codebase; only the default Laravel
`ExampleTest` / `TranslateHelperTest` exist.

## 7. Manual verification — PENDING

**Implementation validated at code level; manual Food Flash Sale end-to-end
verification remains pending.**

Highest-value manual checks, in order:

1. Add a Food item to a Flash Sale — must **not** error `Item_stock_exceeded` (proves the fix).
2. Over-allocate a Grocery item — must **still** error (proves no regression).
3. Food: create → add item → publish → confirm module scoping and zone targeting.
4. Grocery and Fashion: create/publish end-to-end unchanged.
5. `GET /api/v1/flash-sales` with `moduleId` = Food and a valid `zoneId` returns the sale.
6. Pharmacy and Rental remain untouched.

## 8. Frontend status

**No Flutter code was changed in this session.** Food Flash Sale exists in the Admin and
API but will not surface in the User App until implemented in the separate Flutter
User App & Web codebase, against the approved MoonJoin flash card design.

MoonJoin consists of four separate codebases — Flutter User App & Web, Flutter Vendor
App, Flutter Delivery Man App, and this Laravel Admin/backend. Frontend and backend work
must not be combined in one session.
