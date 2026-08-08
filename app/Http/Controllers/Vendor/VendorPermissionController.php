<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\VendorPermission;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Lets a vendor owner manage their own capabilities.
 *
 * Owner-only by design: employees receive capabilities through their employee
 * role, and allowing an employee here would let them grant themselves the very
 * financial permissions the role system is meant to control.
 */
class VendorPermissionController extends Controller
{
    public function index()
    {
        if (!auth('vendor')->check()) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }

        $permission = VendorPermission::where('vendor_id', auth('vendor')->id())->first();

        // No runtime defaulting: a missing record means the seeding migration
        // did not run, which is a deployment fault rather than an empty state.
        if (!$permission) {
            Toastr::error(translate('messages.vendor_permission_record_missing'));
            return back();
        }

        return view('vendor-views.permission.index', compact('permission'));
    }

    public function update(Request $request)
    {
        if (!auth('vendor')->check()) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }

        $request->validate([
            'modules'   => 'nullable|array',
            'modules.*' => 'string|in:' . implode(',', VendorPermission::AVAILABLE_MODULES),
        ]);

        $permission = VendorPermission::where('vendor_id', auth('vendor')->id())->first();

        if (!$permission) {
            Toastr::error(translate('messages.vendor_permission_record_missing'));
            return back();
        }

        // Only the capabilities offered here may be written, so a crafted request
        // cannot inject an unrelated module name into the array.
        $submitted = array_values(array_intersect(
            (array) $request->input('modules', []),
            VendorPermission::AVAILABLE_MODULES
        ));

        $permission->modules = $submitted;
        $permission->save();

        Toastr::success(translate('messages.permission_updated_successfully'));
        return back();
    }
}
