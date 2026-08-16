<?php

namespace Modules\Rental\Http\Controllers\Web\Admin\Promotions;

use App\Models\Module;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Modules\Rental\Entities\RentalFlashSale;
use Modules\Rental\Entities\RentalFlashSaleVehicle;
use Modules\Rental\Entities\Vehicle;

/**
 * Admin management for rental flash sale campaigns.
 *
 * Rental-owned on purpose: the shared Admin\FlashSaleController drives
 * flash_sales/flash_sale_items through App\Models\Item, and this must not touch it.
 *
 * Every rule the booking engine relies on is enforced here rather than trusted from
 * the request: the vehicle's module comes from its provider, not from submitted
 * input, and a vehicle may never be in two campaigns whose windows overlap.
 */
class RentalFlashSaleController extends Controller
{
    /** Campaign list for the module the admin is currently working in. */
    public function index(Request $request)
    {
        $module_id = Config::get('module.current_module_id');

        $flash_sales = RentalFlashSale::where('module_id', $module_id)
            ->withCount('vehicles')
            ->when($request->search, function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate(config('default_pagination'));

        return view('rental::admin.flash-sale.index', compact('flash_sales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ], [
            'end_date.after' => translate('messages.end_date_must_be_after_start_date'),
        ]);

        $module_id = Config::get('module.current_module_id');

        if (!$this->isRentalModule($module_id)) {
            Toastr::error(translate('messages.rental_flash_sale_requires_a_rental_module'));

            return back();
        }

        $flash_sale = new RentalFlashSale();
        $flash_sale->module_id = $module_id;
        $flash_sale->title = $request->title;
        $flash_sale->start_date = $request->start_date;
        $flash_sale->end_date = $request->end_date;
        $flash_sale->is_publish = 0;
        $flash_sale->status = 1;
        $flash_sale->admin_discount_percentage = $request->admin_discount_percentage ?? 100;
        $flash_sale->vendor_discount_percentage = $request->vendor_discount_percentage ?? 0;
        $flash_sale->save();

        Toastr::success(translate('messages.rental_flash_sale_created_successfully'));

        return back();
    }

    public function edit($id)
    {
        $flash_sale = $this->findInCurrentModule($id);

        if (!$flash_sale) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $vehicles = RentalFlashSaleVehicle::with('vehicle')
            ->where('rental_flash_sale_id', $flash_sale->id)
            ->paginate(config('default_pagination'));

        // Vehicles the admin may still attach. Scoped to the campaign's module through
        // the provider relationship -- the same authoritative check storeVehicle()
        // re-applies on submit -- and with the already-attached ones removed so a
        // duplicate cannot be picked in the first place.
        $attached_vehicle_ids = RentalFlashSaleVehicle::where('rental_flash_sale_id', $flash_sale->id)
            ->pluck('vehicle_id');

        $selectable_vehicles = Vehicle::with('provider')
            ->whereHas('provider', function ($query) use ($flash_sale) {
                $query->where('module_id', $flash_sale->module_id);
            })
            ->whereNotIn('id', $attached_vehicle_ids)
            ->orderBy('name')
            ->get();

        return view('rental::admin.flash-sale.edit', compact('flash_sale', 'vehicles', 'selectable_vehicles'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $flash_sale = $this->findInCurrentModule($id);

        if (!$flash_sale) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $flash_sale->title = $request->title;
        $flash_sale->start_date = $request->start_date;
        $flash_sale->end_date = $request->end_date;
        $flash_sale->admin_discount_percentage = $request->admin_discount_percentage ?? $flash_sale->admin_discount_percentage;
        $flash_sale->vendor_discount_percentage = $request->vendor_discount_percentage ?? $flash_sale->vendor_discount_percentage;
        $flash_sale->save();

        Toastr::success(translate('messages.rental_flash_sale_updated_successfully'));

        return back();
    }

    /**
     * Publish or unpublish. Unlike the shared engine this does not force other
     * campaigns down: overlap is prevented per vehicle at attach time, which is the
     * narrower and more predictable rule.
     */
    public function publish($id, $publish)
    {
        $flash_sale = $this->findInCurrentModule($id);

        if (!$flash_sale) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $flash_sale->is_publish = $publish ? 1 : 0;
        $flash_sale->save();

        Toastr::success(translate('messages.rental_flash_sale_publish_updated'));

        return back();
    }

    public function status($id, $status)
    {
        $flash_sale = $this->findInCurrentModule($id);

        if (!$flash_sale) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $flash_sale->status = $status ? 1 : 0;
        $flash_sale->save();

        Toastr::success(translate('messages.status_updated_successfully'));

        return back();
    }

    public function destroy($id)
    {
        $flash_sale = $this->findInCurrentModule($id);

        if (!$flash_sale) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $flash_sale->vehicles()->delete();
        $flash_sale->delete();

        Toastr::success(translate('messages.rental_flash_sale_deleted_successfully'));

        return back();
    }

    /**
     * Attach a vehicle to a campaign.
     *
     * The module is taken from the vehicle's provider, never from the request, so a
     * Car Rental vehicle cannot be attached to a Short Apt Rental campaign by
     * submitting a different id.
     */
    public function storeVehicle(Request $request)
    {
        $request->validate([
            'rental_flash_sale_id' => 'required|integer',
            'vehicle_id' => 'required|integer',
            'discount_type' => 'required|in:percent,amount',
            'discount' => 'required|numeric|min:0.01',
            'applies_to' => 'required|in:all,hourly,distance_wise,day_wise',
            'redemption_cap' => 'nullable|integer|min:1',
        ]);

        $flash_sale = $this->findInCurrentModule($request->rental_flash_sale_id);

        if (!$flash_sale) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $vehicle = Vehicle::with('provider')->find($request->vehicle_id);

        if (!$vehicle) {
            Toastr::error(translate('messages.vehicle_not_found'));

            return back();
        }

        // Authoritative module check: the relationship decides, not the request.
        if ((int) ($vehicle->provider?->module_id) !== (int) $flash_sale->module_id) {
            Toastr::error(translate('messages.vehicle_does_not_belong_to_this_rental_module'));

            return back();
        }

        if ($this->violatesDiscountRules($request, $vehicle)) {
            return back();
        }

        if (RentalFlashSaleVehicle::where('rental_flash_sale_id', $flash_sale->id)->where('vehicle_id', $vehicle->id)->exists()) {
            Toastr::error(translate('messages.vehicle_already_added_to_this_flash_sale'));

            return back();
        }

        // A vehicle may never sit in two campaigns whose windows overlap, so the
        // pricing engine never has to choose a winner.
        if (RentalFlashSaleVehicle::hasOverlappingCampaign($vehicle->id, $flash_sale)) {
            Toastr::error(translate('messages.vehicle_already_in_an_overlapping_flash_sale'));

            return back();
        }

        $campaign_vehicle = new RentalFlashSaleVehicle();
        $campaign_vehicle->rental_flash_sale_id = $flash_sale->id;
        $campaign_vehicle->vehicle_id = $vehicle->id;
        $campaign_vehicle->discount_type = $request->discount_type;
        $campaign_vehicle->discount = $request->discount;
        $campaign_vehicle->applies_to = $request->applies_to;
        $campaign_vehicle->redemption_cap = $request->redemption_cap;
        $campaign_vehicle->redeemed = 0;
        $campaign_vehicle->status = 1;
        $campaign_vehicle->save();

        Toastr::success(translate('messages.vehicle_added_to_flash_sale_successfully'));

        return back();
    }

    public function vehicleStatus($id, $status)
    {
        $campaign_vehicle = RentalFlashSaleVehicle::with('flashSale')->find($id);

        if (!$campaign_vehicle || (int) $campaign_vehicle->flashSale?->module_id !== (int) Config::get('module.current_module_id')) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $campaign_vehicle->status = $status ? 1 : 0;
        $campaign_vehicle->save();

        Toastr::success(translate('messages.status_updated_successfully'));

        return back();
    }

    /**
     * Detach a vehicle. redeemed history goes with it, which is acceptable because a
     * detached vehicle is no longer part of the campaign's accounting.
     */
    public function destroyVehicle($id)
    {
        $campaign_vehicle = RentalFlashSaleVehicle::with('flashSale')->find($id);

        if (!$campaign_vehicle || (int) $campaign_vehicle->flashSale?->module_id !== (int) Config::get('module.current_module_id')) {
            Toastr::error(translate('messages.not_found'));

            return back();
        }

        $campaign_vehicle->delete();

        Toastr::success(translate('messages.vehicle_removed_from_flash_sale'));

        return back();
    }

    // ------------------------------------------------------------------ internals

    private function findInCurrentModule($id): ?RentalFlashSale
    {
        return RentalFlashSale::where('id', $id)
            ->where('module_id', Config::get('module.current_module_id'))
            ->first();
    }

    private function isRentalModule($module_id): bool
    {
        return Module::where('id', $module_id)->where('module_type', 'rental')->exists();
    }

    /**
     * Same guards the provider vehicle-discount form already enforces: a percentage
     * below 100, and an amount that never exceeds the price it discounts.
     */
    private function violatesDiscountRules(Request $request, Vehicle $vehicle): bool
    {
        if ($request->discount_type === 'percent' && $request->discount >= 100) {
            Toastr::error(translate('messages.discount_percentage_must_be_less_than_100'));

            return true;
        }

        if ($request->discount_type === 'amount') {
            $applicable = match ($request->applies_to) {
                'hourly' => (float) $vehicle->hourly_price,
                'distance_wise' => (float) $vehicle->distance_price,
                'day_wise' => (float) ($vehicle->day_wise_price ?? 0),
                // 'all' must not exceed the cheapest axis it can discount.
                default => min(array_filter([
                    (float) $vehicle->hourly_price,
                    (float) $vehicle->distance_price,
                    (float) ($vehicle->day_wise_price ?? 0),
                ], fn ($price) => $price > 0) ?: [0]),
            };

            if ($applicable > 0 && $request->discount > $applicable) {
                Toastr::error(translate('messages.discount_amount_cannot_exceed_the_applicable_price'));

                return true;
            }
        }

        return false;
    }

}
