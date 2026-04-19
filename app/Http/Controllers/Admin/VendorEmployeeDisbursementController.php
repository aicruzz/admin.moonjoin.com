<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Exports\DisbursementExport;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\VendorEmployee;
use App\Models\VendorEmployeeWallet;
use App\Models\Disbursement;
use App\Models\DisbursementDetails;
use App\Models\WithdrawRequest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;

class VendorEmployeeDisbursementController extends Controller
{
    public function list(Request $request)
    {
        $status = $request->status ?? 'all';
        $disbursements = Disbursement::when($status != 'all', function ($q) use ($status) {
            return $q->where('status', $status);
        })
            ->where('created_for', 'vendor_employee')
            ->latest()
            ->paginate(config('default_pagination'));

        return view('admin-views.ve-disbursement.index', compact('disbursements', 'status'));
    }

    public function view(Request $request, $id)
    {
        $key = explode(' ', $request['search'] ?? '');
        $vendor_employee_id = $request->query('vendor_employee_id', 'all');
        $payment_method_id = $request->query('payment_method_id', 'all');
        $disbursement = Disbursement::findOrFail($id);

        $disbursements = DisbursementDetails::with('vendor_employee', 'withdraw_method')
            ->where(['disbursement_id' => $id])
            ->when(isset($key), function ($q) use ($key) {
                $q->whereHas('vendor_employee', function ($q) use ($key) {
                    $q->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%")
                                ->orWhere('phone', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->when(isset($vendor_employee_id) && is_numeric($vendor_employee_id), function ($q) use ($vendor_employee_id) {
                $q->where('vendor_employee_id', $vendor_employee_id);
            })
            ->when(isset($payment_method_id) && is_numeric($payment_method_id), function ($q) use ($payment_method_id) {
                $q->whereHas('withdraw_method', function ($q) use ($payment_method_id) {
                    $q->where('withdrawal_method_id', $payment_method_id);
                });
            })
            ->latest();

        $ve_ids = json_encode($disbursements->pluck('vendor_employee_id')->toArray());
        $disbursement_ve = $disbursements->paginate(config('default_pagination'));

        return view('admin-views.ve-disbursement.view', compact(
            'disbursement',
            'disbursement_ve',
            'vendor_employee_id',
            've_ids',
            'payment_method_id'
        ));
    }

    public function export(Request $request, $id, $type = 'excel')
    {
        $key = explode(' ', $request['search'] ?? '');
        $vendor_employee_id = $request->query('vendor_employee_id', 'all');
        $payment_method_id = $request->query('payment_method_id', 'all');
        $disbursement = Disbursement::findOrFail($id);

        $disbursements = DisbursementDetails::where(['disbursement_id' => $id])
            ->when(isset($key), function ($q) use ($key) {
                $q->whereHas('vendor_employee', function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($vendor_employee_id) && is_numeric($vendor_employee_id), function ($q) use ($vendor_employee_id) {
                $q->where('vendor_employee_id', $vendor_employee_id);
            })
            ->when(isset($payment_method_id) && is_numeric($payment_method_id), function ($q) use ($payment_method_id) {
                $q->whereHas('withdraw_method', function ($q) use ($payment_method_id) {
                    $q->where('withdrawal_method_id', $payment_method_id);
                });
            })
            ->latest()->get();

        $data = [
            'type' => 've',
            'disbursement' => $disbursement,
            'disbursements' => $disbursements,
        ];

        if ($type == 'pdf') {
            $mpdf_view = View::make('admin-views.ve-disbursement.pdf', compact('disbursement', 'disbursements'));
            Helpers::gen_mpdf(view: $mpdf_view, file_prefix: 'Disbursement', file_postfix: $id);
        } elseif ($type == 'csv') {
            return Excel::download(new DisbursementExport($data), 'Disbursement.csv');
        }

        return Excel::download(new DisbursementExport($data), 'Disbursement.xlsx');
    }

    public function status(Request $request)
    {
        $disbursements = DisbursementDetails::where(['disbursement_id' => $request->disbursement_id])
            ->whereIn('vendor_employee_id', $request->vendor_employee_ids)
            ->get();

        foreach ($disbursements as $disbursement) {
            $wallet = VendorEmployeeWallet::where('vendor_employee_id', $disbursement->vendor_employee_id)->first();

            if ((string) $wallet->total_earning < (string) ($wallet->total_withdrawn + $wallet->pending_withdraw)) {
                return response()->json([
                    'status' => 'error',
                    'message' => translate('messages.Blalnce_mismatched_total_earning_is_too_low_for') . ' ' . $disbursement->vendor_employee?->name,
                ]);
            }

            if ($request->status == 'completed') {
                if ($disbursement->status != 'completed') {
                    $withdraw = new WithdrawRequest();
                    $withdraw->vendor_employee_id = $disbursement->vendor_employee_id;
                    $withdraw->amount = $disbursement['disbursement_amount'];
                    $withdraw->withdrawal_method_id = $disbursement['payment_method'];
                    $withdraw->withdrawal_method_fields = $disbursement->withdraw_method->method_fields;
                    $withdraw->approved = 1;
                    $withdraw->transaction_note = $disbursement->id;
                    $withdraw->type = 'disbursement';

                    if ($disbursement->status == 'canceled') {
                        $wallet->increment('total_withdrawn', $disbursement['disbursement_amount']);
                    } else {
                        $wallet->decrement('pending_withdraw', $disbursement['disbursement_amount']);
                        $wallet->increment('total_withdrawn', $disbursement['disbursement_amount']);
                    }

                    $withdraw->save();
                }
            } elseif ($request->status == 'canceled') {
                if ($disbursement->status == 'completed') {
                    return response()->json([
                        'status' => 'error',
                        'message' => translate('messages.can_not_cancel_completed_disbursement_,_uncheck_completed_disbursements'),
                    ]);
                }
                $wallet->decrement('pending_withdraw', $disbursement['disbursement_amount']);
            }

            $disbursement->status = $request->status;
            $disbursement->save();
        }

        self::check_status($request->disbursement_id);

        return response()->json([
            'status' => 'success',
            'message' => translate('messages.status_updated'),
        ]);
    }

    public function statusById($id, $status)
    {
        $disbursement = DisbursementDetails::find($id);
        $wallet = VendorEmployeeWallet::where('vendor_employee_id', $disbursement->vendor_employee_id)->first();

        if ((string) $wallet->total_earning < (string) ($wallet->total_withdrawn + $wallet->pending_withdraw)) {
            Toastr::error(translate('messages.Blalnce_mismatched_total_earning_is_too_low'));
            return back();
        }

        if ($status == 'completed') {
            $withdraw = new WithdrawRequest();
            $withdraw->vendor_employee_id = $disbursement->vendor_employee_id;
            $withdraw->amount = $disbursement['disbursement_amount'];
            $withdraw->withdrawal_method_id = $disbursement['payment_method'];
            $withdraw->withdrawal_method_fields = $disbursement->withdraw_method->method_fields;
            $withdraw->approved = 1;
            $withdraw->transaction_note = $id;
            $withdraw->type = 'disbursement';

            if ($disbursement->status == 'canceled') {
                $wallet->increment('total_withdrawn', $disbursement['disbursement_amount']);
            } else {
                $wallet->decrement('pending_withdraw', $disbursement['disbursement_amount']);
                $wallet->increment('total_withdrawn', $disbursement['disbursement_amount']);
            }

            $withdraw->save();

        } elseif ($status == 'canceled') {
            if ($disbursement->status == 'completed') {
                Toastr::error(translate('messages.can_not_cancel_completed_disbursement_,_uncheck_completed_disbursements'));
                return back();
            }
            $wallet->decrement('pending_withdraw', $disbursement['disbursement_amount']);

        } elseif ($status == 'pending') {
            if ($disbursement->status == 'completed') {
                $withdraw = WithdrawRequest::where('transaction_note', $id)
                    ->where('vendor_employee_id', $disbursement->vendor_employee_id)
                    ->first();
                if ($withdraw)
                    $withdraw->delete();
            }
            $wallet->decrement('total_withdrawn', $disbursement['disbursement_amount']);
            $wallet->increment('pending_withdraw', $disbursement['disbursement_amount']);
        }

        $disbursement->status = $status;
        $disbursement->save();

        self::check_status($disbursement->disbursement_id);

        Toastr::success(translate('messages.status_updated'));
        return back();
    }

    /**
     * Called by VendorEmployeeDisbursementScheduler → ve:disbursement
     */
    public function generate_disbursement()
    {
        $vendor_employees = VendorEmployee::where('status', 1)->get();
        $disbursement_details = [];
        $total_amount = 0;

        $disbursement = new Disbursement();
        $disbursement->id = 1000 + Disbursement::count() + 1;
        if (Disbursement::find($disbursement->id)) {
            $disbursement->id = Disbursement::orderBy('id', 'desc')->first()->id + 1;
        }
        $disbursement->title = 'Disbursement # ' . $disbursement->id;
        $minimum_amount = BusinessSetting::where(['key' => 've_disbursement_min_amount'])->first()?->value;

        foreach ($vendor_employees as $ve) {
            if (isset($ve->wallet)) {
                $total_earning = $ve->wallet->total_earning ?? 0;
                $total_withdraw = ($ve->wallet->total_withdrawn ?? 0) + ($ve->wallet->pending_withdraw ?? 0);
                $total_cash_in_hand = $ve->wallet->collected_cash ?? 0;

                $disbursement_amount = ((string) $total_earning > (string) ($total_withdraw + $total_cash_in_hand))
                    ? ($total_earning - ($total_withdraw + $total_cash_in_hand))
                    : 0;

                if ($disbursement_amount > $minimum_amount && isset($ve->disbursement_method)) {
                    $disbursement_details[] = [
                        'disbursement_id' => $disbursement->id,
                        'vendor_employee_id' => $ve->id,
                        'disbursement_amount' => $disbursement_amount,
                        'payment_method' => $ve->disbursement_method->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $total_amount += $disbursement_amount;

                    $ve->wallet->pending_withdraw += $disbursement_amount;
                    $ve->wallet->save();
                }
            }
        }

        if ($total_amount > 0) {
            $disbursement->total_amount = $total_amount;
            $disbursement->created_for = 'vendor_employee';
            $disbursement->save();

            DisbursementDetails::insert($disbursement_details);
        }

        info("VE-----Disbursement");
        return true;
    }

    public function check_status($id)
    {
        $disbursements = DisbursementDetails::where(['disbursement_id' => $id])->get();
        $statusCounts = $disbursements->countBy('status');
        $disbursement = Disbursement::find($id);

        if (isset($statusCounts['pending']) && $statusCounts['pending'] == count($disbursements)) {
            $disbursement->status = 'pending';
        } elseif (isset($statusCounts['canceled']) && $statusCounts['canceled'] == count($disbursements)) {
            $disbursement->status = 'canceled';
        } elseif (isset($statusCounts['completed']) && $statusCounts['completed'] == count($disbursements)) {
            $disbursement->status = 'completed';
        } else {
            $disbursement->status = 'partially_completed';
        }

        return $disbursement->save();
    }
}