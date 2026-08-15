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
| **Rental** (Car Rental, Short Apt Rental) | ✅ **Separate rental-owned campaign layer**, see §5 | **Promotional redemption cap** — never physical availability |

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
branch — but neither can reach this code. Neither exposes a Flash Sale menu here, and
Rental does not use the `Item` model at all; it has its own campaign layer (§5).

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

## 5. Rental Flash Sale — implemented separately (supersedes the earlier exclusion)

An earlier revision of this section said Rental must not receive a Flash Sale at all.
That is now superseded: Rental has its own campaign layer under `Modules/Rental/`. The
part that still stands is that Rental must **never** be forced into `flash_sale_items` --
the shared engine resolves everything through `App\Models\Item`, and a rental listing is
a `Vehicle` priced on three axes. No fake `Item` rows, no polymorphic `FlashSaleItem`, no
change to the shared engine.

### Rental domain (verified in source)

| Concept | Reality |
|---|---|
| Listing | `vehicles` (`Modules\Rental\Entities\Vehicle`) |
| **Physical units** | **`vehicle_identities`** |
| **Availability** | **`withCount('vehicleIdentities as total_vehicle_count')` filtered by the requested `schedule_at`**, rejected at `TripController:408-417` |
| Pricing axes | `hourly_price × estimated_hours` · `day_wise_price × ceil(hours/24)` · `distance_price × distance` (`TripController:427-434`) |
| Existing discount | `vehicles.discount_type` / `discount_price`, provider-controlled |
| Booking | `trips` + `trip_details`; transaction `TripController:106 → ~248` |
| Quantity | `trip_details.quantity`, genuinely > 1 and multiplies price |
| Module isolation | `trips.module_id`, from the provider's module |

An earlier audit claimed Rental had *no* availability model. That was wrong -- it missed
`vehicle_identities`. `multiple_vehicles` is a dead UI flag, but it is not the availability
mechanism.

### Design

Two rental-owned tables, `rental_flash_sales` (campaign) and
`rental_flash_sale_vehicles` (participating vehicle).

- **Promotional allocation, not availability.** `redemption_cap` / `redeemed` cap
  promotional redemptions. Physical availability remains `vehicle_identities` and is
  untouched. **A booking must pass both gates**, and the two failures are reported
  distinctly -- flash exhaustion never reads as vehicle unavailability.
- **Quantity-based redemption.** A booking of 3 units consumes 3 redemptions.
  All-or-nothing: 3 remaining refuses a quantity of 4 and consumes nothing.
- **Atomic.** `RentalFlashSaleVehicle::reserve()` issues one conditional UPDATE
  (`redemption_cap IS NULL OR redeemed + qty <= redemption_cap`). The database evaluates
  the cap under the row lock, so competing bookings serialise. Zero rows → the trip
  transaction rolls back with code `rental_flash_sale`.
- **Booking-time eligibility.** The campaign must be running when the booking is placed;
  the rental dates themselves may fall outside the window.
- **Pricing axis.** `applies_to` (`all`/`hourly`/`distance_wise`/`day_wise`) selects which
  axis is discounted, matching the `rental_type` vocabulary.
- **One winning discount.** A running campaign replaces the provider's vehicle discount,
  and the provider/admin store-discount override is skipped when any line is on a
  campaign -- mirroring the shared engine, which skips the store discount for
  `flash_sale` lines. Coupons are unchanged.
- **Attribution.** `discount_on_trip_by` is written as `admin` for a campaign line
  (admin-controlled), `vendor` otherwise. No new `trip_details` column was needed.
- **Module isolation.** Enforced in `resolveFor()` via the campaign's `module_id`, so a
  Car Rental campaign can never price a Short Apt Rental booking or vice versa.
- **Estimates consume nothing.** Reservation runs only when `$increment === true`, the
  flow's existing signal for a real booking rather than a price estimate.

### Cancellation — redemptions are never restored

Investigated rather than inherited. All four rental cancel paths decrement the
**statistics** counter `total_trip` by `$detail->quantity`
(`Api/User/TripController:677`, `Web/Admin/TripController:186`,
`Web/Provider/TripController:167`, `Api/Provider/ProviderTripController:100`). There is no
precedent for restoring a *promotional* allocation. Per the approved product decision,
a cancelled booking does **not** return its redemption, which prevents cancel/rebook
abuse of a limited campaign. A rolled-back booking is different: it never redeemed.

### Admin management

`Web\Admin\Promotions\RentalFlashSaleController`, routed under
`admin/rental/flash-sale` with the existing `module:promotion` middleware. Rental-owned;
the shared `admin.flash-sale` routes are untouched.

Create/edit/publish/status/delete a campaign, attach vehicles with discount type, value,
`applies_to`, redemption cap and status, and detach or disable them. Campaigns are listed
and resolved through `Config::get('module.current_module_id')`, so an admin only ever sees
and edits campaigns for the rental module they are working in.

Enforced server-side, not trusted from the request:

- the campaign's module must be `module_type = 'rental'`;
- a vehicle's module comes from **its provider's store**, so a Car Rental vehicle cannot
  be attached to a Short Apt Rental campaign by submitting a different id;
- percent discounts stay below 100 and amount discounts never exceed the applicable
  axis price (`all` is checked against the cheapest non-zero axis);
- duplicate attachment is refused, backed by the unique index;
- `RentalFlashSaleVehicle::hasOverlappingCampaign()` refuses a vehicle already in another
  campaign in the same module whose window overlaps, so the pricing engine never has to
  choose a winner.

### API payload

Additive only. `Vehicle` appends a `flash_sale` attribute, so every existing customer
vehicle response (listing, detail, search, top-rated) carries it without a new endpoint
and without renaming or removing any field. It is `null` when no campaign is running or
the allocation is exhausted.

It resolves through the same `RentalFlashSaleVehicle::resolveFor()` the booking engine
uses, with the module taken from the provider's store, so **the price displayed and the
price charged cannot come from different campaigns or different modules**.

Exposed: `title`, `discount_type`, `discount`, `applies_to`, `start_date`, `end_date`, and
per-axis `original_price` / `flash_price` / `discount_amount` for the axes the campaign
covers. `redeemed` and `redemption_cap` are deliberately **not** exposed -- no product
requirement needs them and they are mutable internals.

Known cost: the accessor resolves per vehicle, so a large listing performs one campaign
lookup per row. Acceptable for correctness; revisit with eager loading if listing latency
becomes a concern.

### Car Rental vs Short Apt Rental

Identical in code -- same `vehicles`, `trips`, pricing and statuses. There is no apartment
entity anywhere in `Modules/Rental/`; a "Short Apt Rental" listing is a `Vehicle` row. One
implementation serves both, separated only by `module_id`.

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
