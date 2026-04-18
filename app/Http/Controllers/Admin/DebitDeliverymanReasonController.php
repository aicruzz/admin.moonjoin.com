<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\DebitDeliverymanReason;
use App\Http\Controllers\Controller;
use App\Models\Translation;
use Brian2694\Toastr\Facades\Toastr;

class DebitDeliverymanReasonController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reason'   => 'required|max:255',
            'reason.0' => 'required',
        ], [
            'reason.0.required' => translate('default_reason_is_required'),
        ]);

        $reason = new DebitDeliverymanReason();
        $reason->reason     = $request->reason[array_search('default', $request->lang)];
        $reason->user_type  = 'deliveryman';
        $reason->status     = 1;
        $reason->created_at = now();
        $reason->updated_at = now();
        $reason->save();

        $data         = [];
        $default_lang = str_replace('_', '-', app()->getLocale());

        foreach ($request->lang as $index => $key) {
            if ($default_lang == $key && !($request->reason[$index])) {
                if ($key != 'default') {
                    array_push($data, [
                        'translationable_type' => 'App\Models\DebitDeliverymanReason',
                        'translationable_id'   => $reason->id,
                        'locale'               => $key,
                        'key'                  => 'reason',
                        'value'                => $reason->reason,
                    ]);
                }
            } else {
                if ($request->reason[$index] && $key != 'default') {
                    array_push($data, [
                        'translationable_type' => 'App\Models\DebitDeliverymanReason',
                        'translationable_id'   => $reason->id,
                        'locale'               => $key,
                        'key'                  => 'reason',
                        'value'                => $request->reason[$index],
                    ]);
                }
            }
        }

        Translation::insert($data);
        Toastr::success(translate('Debit reason added successfully.'));
        return back();
    }

    public function update(Request $request)
    {
        $request->validate([
            'reason'   => 'required|max:255',
            'reason.0' => 'required',
        ], [
            'reason.0.required' => translate('default_reason_is_required'),
        ]);

        $reason         = DebitDeliverymanReason::findOrFail($request->reason_id);
        $reason->reason = $request->reason[array_search('default', $request->lang1)];
        $reason->save();

        $default_lang = str_replace('_', '-', app()->getLocale());

        foreach ($request->lang1 as $index => $key) {
            if ($default_lang == $key && !($request->reason[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\DebitDeliverymanReason',
                            'translationable_id'   => $reason->id,
                            'locale'               => $key,
                            'key'                  => 'reason',
                        ],
                        ['value' => $reason->reason]
                    );
                }
            } else {
                if ($request->reason[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\DebitDeliverymanReason',
                            'translationable_id'   => $reason->id,
                            'locale'               => $key,
                            'key'                  => 'reason',
                        ],
                        ['value' => $request->reason[$index]]
                    );
                }
            }
        }

        Toastr::success(translate('Debit reason updated successfully.'));
        return back();
    }

    public function destroy($id)
    {
        $reason = DebitDeliverymanReason::findOrFail($id);
        $reason->translations()?->delete();
        $reason->delete();
        Toastr::success(translate('Debit reason deleted successfully.'));
        return back();
    }

    public function status($id, $status)
    {
        $reason         = DebitDeliverymanReason::findOrFail($id);
        $reason->status = $status;
        $reason->save();
        Toastr::success(translate('messages.status_updated'));
        return back();
    }
}
