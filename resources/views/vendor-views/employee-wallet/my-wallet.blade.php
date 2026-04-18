@extends('layouts.vendor.app')

@section('title', translate('messages.my_wallet'))

@section('content')
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h2 class="page-header-title text-capitalize">
                        <div class="card-header-icon d-inline-flex mr-2 img">
                            <img src="{{ asset('/public/assets/admin/img/image_90.png') }}" alt="wallet">
                        </div>
                        <span>{{ translate('messages.my_wallet') }}</span>
                    </h2>
                </div>
            </div>
        </div>

        {{-- Row 1: Current Balance | Withdrawable Balance | Request Withdraw --}}
        <div class="row g-2 mb-3">

            {{-- Current Balance --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="font-weight-bold mb-1">
                                {{ \App\CentralLogics\Helpers::format_currency($employee->wallet_balance ?? 0) }}
                            </h3>
                            <p class="text-muted mb-0">{{ translate('messages.current_balance') }}</p>
                            <small class="text-muted">
                                {{ $employee->f_name }} {{ $employee->l_name }}
                                &mdash;
                                {{ $employee->role->name ?? translate('messages.employee') }}
                            </small>
                        </div>
                        <img src="{{ asset('public/assets/admin/img/icons/cash-in-hand.png') }}" alt="" width="45">
                    </div>
                </div>
            </div>

            {{-- Withdrawable Balance --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="font-weight-bold mb-1">
                                {{ \App\CentralLogics\Helpers::format_currency($withdrawable_balance) }}
                            </h3>
                            <p class="text-muted mb-0">{{ translate('messages.withdrawable_balance') }}</p>
                        </div>
                        <img src="{{ asset('public/assets/admin/img/icons/withdraw.png') }}" alt="" width="45">
                    </div>
                </div>
            </div>

            {{-- Request Withdraw Button — only shown on manual disbursement --}}
            @if($ve_disbursement_type == 'manual')
                <div class="col-sm-6 col-lg-4 mb-3 d-flex align-items-center">
                    <button type="button" data-toggle="modal" data-target="#withdraw_modal"
                        class="btn btn-lg btn--primary w-100 py-3">
                        {{ translate('messages.request_withdraw') }}
                        <i class="tio-arrow-forward ml-1"></i>
                    </button>
                </div>
            @endif
        </div>

        {{-- Row 2: Pending Withdraw | Total Withdrawn | Total Earning --}}
        <div class="row g-2 mb-4">

            {{-- Pending Withdraw --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <div class="card h-100" style="background: #fff0f0;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="font-weight-bold mb-1">
                                {{ \App\CentralLogics\Helpers::format_currency($pending_withdraw) }}
                            </h3>
                            <p class="text-muted mb-0">{{ translate('messages.pending_withdraw') }}</p>
                        </div>
                        <img src="{{ asset('public/assets/admin/img/icons/pending.png') }}" alt="" width="45">
                    </div>
                </div>
            </div>

            {{-- Total Withdrawn --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <div class="card h-100" style="background: #f0fff4;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="font-weight-bold mb-1">
                                {{ \App\CentralLogics\Helpers::format_currency($total_withdrawn) }}
                            </h3>
                            <p class="text-muted mb-0">{{ translate('messages.total_withdrawn') }}</p>
                        </div>
                        <img src="{{ asset('public/assets/admin/img/icons/total-order.png') }}" alt="" width="45">
                    </div>
                </div>
            </div>

            {{-- Total Earning --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <div class="card h-100" style="background: #f0f4ff;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="font-weight-bold mb-1">
                                {{ \App\CentralLogics\Helpers::format_currency($total_credited) }}
                            </h3>
                            <p class="text-muted mb-0">{{ translate('messages.total_earning') }}</p>
                        </div>
                        <img src="{{ asset('public/assets/admin/img/icons/earning.png') }}" alt="" width="45">
                    </div>
                </div>
            </div>

        </div>

        {{-- Transaction History Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="datatable"
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                    data-hs-datatables-options='{
                                                                                                                                                                                "order": [],
                                                                                                                                                                                "orderCellsTop": true,
                                                                                                                                                                                "paging": false
                                                                                                                                                                            }'>
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.sl') }}</th>
                            <th>{{ translate('messages.amount') }}</th>
                            <th>{{ translate('messages.request_time') }}</th>
                            <th>{{ translate('messages.disbursement_method') }}</th>
                            <th>{{ translate('messages.Transaction_Type') }}</th>
                            <th>{{ translate('messages.status') }}</th>
                            <th>{{ translate('messages.note') }}</th>
                            <th class="w-5px">{{ translate('messages.Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $k => $tr)
                            <tr>
                                <td>{{ $k + $transactions->firstItem() }}</td>
                                <td>
                                    <strong class="text-{{ $tr->type == 'debit' ? 'danger' : 'success' }}">
                                        {{ $tr->type == 'debit' ? '-' : '+' }}
                                        {{ \App\CentralLogics\Helpers::format_currency($tr->amount) }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="d-block">
                                        {{ \App\CentralLogics\Helpers::time_date_format($tr->created_at) }}
                                    </span>
                                </td>
                                <td>
                                    @if($tr->withdrawal_method_id ?? null)
                                        <a href="#" data-toggle="modal" data-target="#methodModal-{{ $tr->id }}">
                                            {{ translate($tr->method->method_name ?? 'View Details') }}
                                        </a>
                                        <div class="modal fade" id="methodModal-{{ $tr->id }}" tabindex="-1" role="dialog"
                                            aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            {{ translate('messages.disbursement_method_details') }}
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        @foreach(json_decode($tr->withdrawal_method_fields ?? '[]', true) as $key => $field)
                                                            <label class="mt-2">{{ translate($key) }}</label>
                                                            <input type="text" class="form-control" readonly value="{{ $field }}">
                                                        @endforeach
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" data-dismiss="modal" class="btn btn-secondary">
                                                            {{ translate('Close') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        {{ translate('Default_method') }}
                                    @endif
                                </td>
                                <td>
                                    @if($tr->type == 'debit')
                                        <span class="badge badge-soft-danger">{{ translate('messages.debit') }}</span>
                                    @elseif($tr->type == 'credit')
                                        <span class="badge badge-soft-success">{{ translate('messages.credit') }}</span>
                                    @elseif($tr->type == 'withdraw')
                                        <span class="badge badge-soft-warning">{{ translate('messages.withdraw') }}</span>
                                    @else
                                        <span class="badge badge-soft-info">{{ translate($tr->type) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tr->status == 'pending')
                                        <label class="badge badge-soft-info">{{ translate('messages.pending') }}</label>
                                    @elseif($tr->status == 'approved')
                                        <label class="badge badge-soft-success">{{ translate('messages.approved') }}</label>
                                    @elseif($tr->status == 'denied')
                                        <label class="badge badge-soft-danger">{{ translate('messages.denied') }}</label>
                                    @else
                                        <label class="badge badge-soft-success">{{ translate('messages.complete') }}</label>
                                    @endif
                                </td>
                                <td>{{ $tr->note ?? translate('messages.N/A') }}</td>
                                <td>
                                    @if(isset($tr->status) && $tr->status == 'pending' && $tr->type == 'withdraw')
                                        <a class="btn btn-outline-danger btn--danger action-btn form-alert" href="javascript:"
                                            data-id="withdraw-{{ $tr->id }}" data-message="{{ translate('Want to delete this ?') }}"
                                            title="{{ translate('messages.delete') }}">
                                            <i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('vendor.employee.wallet.withdraw.cancel', $tr->id) }}" method="POST"
                                            id="withdraw-{{ $tr->id }}">
                                            @csrf @method('DELETE')
                                        </form>
                                    @else
                                        <label>{{ translate('messages.complete') }}</label>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if(count($transactions) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                        <h5>{{ translate('no_data_found') }}</h5>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-footer pt-0 border-0">
            {{ $transactions->links() }}
        </div>

    </div>

    @if($ve_disbursement_type == 'manual')
        <div class="modal fade" id="withdraw_modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('messages.request_withdraw') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('vendor.employee.wallet.withdraw.request') }}" method="POST" id="withdraw_form"
                        class="needs-validation">
                        @csrf
                        <input type="hidden" name="account_name" id="hidden_account_name">
                        <input type="hidden" name="bank_name" id="hidden_bank_name">

                        <div class="modal-body">

                            {{-- Amount --}}
                            <div class="form-group">
                                <label>{{ translate('messages.amount') }}</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $withdrawable_balance }}"
                                    class="form-control" name="amount" placeholder="{{ translate('messages.enter_amount') }}"
                                    required>
                                <small class="text-muted">
                                    {{ translate('messages.withdrawable_balance') }}:
                                    <strong>{{ \App\CentralLogics\Helpers::format_currency($withdrawable_balance) }}</strong>
                                </small>
                            </div>

                            {{-- Bank Dropdown — loaded from API --}}
                            <div class="form-group">
                                <label>{{ translate('messages.select_bank') }}</label>
                                <select class="form-control" name="bank_code" id="bank_select" required>
                                    <option value="">-- {{ translate('messages.loading_banks') }} --</option>
                                </select>
                            </div>

                            {{-- Account Number + Verify --}}
                            <div class="form-group">
                                <label>{{ translate('messages.account_number') }}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="account_number" id="account_number"
                                        maxlength="10" placeholder="{{ translate('messages.enter_account_number') }}" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn--primary" id="verify_btn">
                                            <span id="verify_text">{{ translate('messages.verify') }}</span>
                                            <span id="verify_spinner" class="spinner-border spinner-border-sm d-none"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Verified Account Name --}}
                            <div id="account_name_box" class="alert alert-success py-2 px-3 d-none">
                                <small class="d-block text-muted">{{ translate('messages.account_name') }}</small>
                                <strong id="resolved_account_name"></strong>
                            </div>

                            {{-- Error --}}
                            <div id="enquiry_error" class="alert alert-danger py-2 px-3 d-none"></div>

                        </div>
                        <div class="modal-footer">
                            <button type="reset" data-dismiss="modal" class="btn btn-secondary">
                                {{ translate('Close') }}
                            </button>
                            <button type="submit" id="set_disable" class="btn btn--primary" disabled>
                                {{ translate('messages.submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('script_2')
    <script>
        "use strict";

        // ── Load banks when modal opens ──────────────────────────────
        $('#withdraw_modal').on('show.bs.modal', function () {
            let $bankSelect = $('#bank_select');
            if ($bankSelect.find('option').length > 1) return;

            $.ajax({
                url: "{{ route('vendor.employee.wallet.banks') }}",
                type: 'GET',
                success: function (response) {
                    $bankSelect.html('<option value="">-- {{ translate('messages.select_bank') }} --</option>');
                    if (response.data.data.bankList) {
                        $.each(response.data.data.bankList, function (i, bank) {
                            $bankSelect.append(
                                `<option value="${bank.bankCode}" data-name="${bank.bankName}">
                                ${bank.bankName}
                            </option>`
                            );
                        });
                    }
                },
                error: function () {
                    $bankSelect.html('<option value="">-- Failed to load banks --</option>');
                }
            });
        });

        // ── Reset if bank or account number changes ──────────────────
        $('#bank_select, #account_number').on('change input', function () {
            resetVerification();
        });

        // ── Verify account ───────────────────────────────────────────
        $('#verify_btn').on('click', function () {
            let accountNumber = $('#account_number').val().trim();
            let bankCode = $('#bank_select').val();
            let bankName = $('#bank_select option:selected').data('name');

            if (!bankCode) {
                toastr.warning('{{ translate('messages.select_bank') }}');
                return;
            }
            if (!accountNumber || accountNumber.length < 10) {
                toastr.warning('{{ translate('messages.enter_valid_account_number') }}');
                return;
            }

            $('#verify_text').addClass('d-none');
            $('#verify_spinner').removeClass('d-none');
            $('#verify_btn').attr('disabled', true);
            $('#account_name_box').addClass('d-none');
            $('#enquiry_error').addClass('d-none').text('');

            $.ajax({
                url: "{{ route('vendor.employee.wallet.name-enquiry') }}",
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { accountNumber: accountNumber, bankCode: bankCode },
                success: function (response) {
                    if (response.success) {
                        $('#resolved_account_name').text(response.data.accountName);
                        $('#hidden_account_name').val(response.data.accountName);
                        $('#hidden_bank_name').val(bankName);
                        $('#account_name_box').removeClass('d-none');
                        $('#set_disable').removeAttr('disabled');
                    } else {
                        $('#enquiry_error').removeClass('d-none').text(response.message);
                    }
                },
                error: function (xhr) {
                    $('#enquiry_error').removeClass('d-none')
                        .text(xhr.responseJSON?.message ?? 'Verification failed');
                },
                complete: function () {
                    $('#verify_text').removeClass('d-none');
                    $('#verify_spinner').addClass('d-none');
                    $('#verify_btn').removeAttr('disabled');
                }
            });
        });

        // ── Reset on modal close ─────────────────────────────────────
        $('#withdraw_modal').on('hidden.bs.modal', function () {
            $('#bank_select').val('');
            $('#account_number').val('');
            resetVerification();
        });

        function resetVerification() {
            $('#account_name_box').addClass('d-none');
            $('#enquiry_error').addClass('d-none').text('');
            $('#resolved_account_name').text('');
            $('#hidden_account_name').val('');
            $('#hidden_bank_name').val('');
            $('#set_disable').attr('disabled', true);
        }

        $(document).ready(function () {
            $("#withdraw_form").on("submit", function () {
                $('#set_disable').attr('disabled', true);
            });
        });
    </script>
@endpush