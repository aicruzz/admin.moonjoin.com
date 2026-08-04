@extends('layouts.vendor.app')

@section('title', translate('Credit Employee Wallet'))

@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/collect-cash.png') }}" class="w--22" alt="">
            </span>
            <span>{{ translate('Credit Employee Wallet') }}</span>
        </h1>
    </div>

    {{-- Credit Form --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('vendor.employee.wallet.credit') }}" method="post" id="credit_employee_form">
                @csrf

                <div class="row g-3">

                    {{-- Employee Select --}}
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="employee_id">
                                {{ translate('messages.employees') }}
                            </label>

                            <select id="employee_id" name="employee_id"
                                class="form-control js-select2-custom"
                                data-placeholder="{{ translate('Select Employee') }}"
                                title="{{ translate('Select Employee') }}" required>

                                <option value="">
                                    -- {{ translate('Select Employee') }} --
                                </option>

                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->f_name . ' ' . $employee->l_name }}
                                        ({{ \App\CentralLogics\Helpers::format_currency($employee->wallet_balance) }})
                                    </option>
                                @endforeach

                            </select>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="amount">
                                {{ translate('messages.amount') }}
                                {{ \App\CentralLogics\Helpers::currency_symbol() }}
                            </label>

                            <input class="form-control"
                                type="number"
                                min="0.01"
                                step="0.01"
                                name="amount"
                                id="amount"
                                max="999999999999.99"
                                placeholder="{{ translate('ex_100') }}"
                                required>
                        </div>
                    </div>

                    {{-- Reason --}}
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="reason">
                                {{ translate('Reason') }}
                                <span class="text-danger">*</span>
                            </label>

                            <select class="form-control" name="reason" id="reason" required>
                                <option value="">
                                    -- {{ translate('Select reason') }} --
                                </option>

                                <option value="salary">
                                    {{ translate('Salary') }}
                                </option>

                                <option value="bonus">
                                    {{ translate('Bonus') }}
                                </option>

                                <option value="commission">
                                    {{ translate('Commission') }}
                                </option>

                                <option value="incentive">
                                    {{ translate('Incentive') }}
                                </option>

                                <option value="refund">
                                    {{ translate('Refund') }}
                                </option>

                                <option value="other">
                                    {{ translate('Other') }}
                                </option>
                            </select>
                        </div>
                    </div>

                    {{-- Note --}}
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="note">
                                {{ translate('Note') }}
                                <span class="input-label-secondary">
                                    ({{ translate('Optional') }})
                                </span>
                            </label>

                            <input class="form-control"
                                type="text"
                                name="note"
                                id="note"
                                maxlength="500"
                                placeholder="{{ translate('Enter additional note...') }}">
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="col-sm-12">
                        <div class="btn--container justify-content-end">

                            <button class="btn btn--reset"
                                type="reset"
                                id="reset_btn">
                                {{ translate('messages.reset') }}
                            </button>

                            <button class="btn btn--primary"
                                type="submit">
                                <i class="tio-money-vs mr-1"></i>
                                {{ translate('Credit Wallet') }}
                            </button>

                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- Credit History Table --}}
    <div class="row mt-3">
        <div class="col-md-12">

            <div class="card">

                <div class="card-header py-2 border-0">
                    <div class="search--button-wrapper">

                        <h5 class="card-title d-flex gap-2 align-items-center">
                            <span class="card-header-icon">
                                <i class="tio-wallet"></i>
                            </span>

                            <span>
                                {{ translate('Credit History') }}
                            </span>

                            <span class="badge badge-soft-secondary" id="itemCount">
                                {{ $credit_records->total() }}
                            </span>
                        </h5>

                        {{-- Search --}}
                        <form class="search-form theme-style">
                            <div class="input-group input--group">

                                <input id="datatableSearch"
                                    name="search"
                                    type="search"
                                    class="form-control h--40px"
                                    placeholder="{{ translate('Search by employee name...') }}"
                                    value="{{ request()->search ?? null }}"
                                    aria-label="{{ translate('messages.search_here') }}">

                                <button type="submit" class="btn btn--secondary h--40px">
                                    <i class="tio-search"></i>
                                </button>

                            </div>
                        </form>

                    </div>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">
                        <table id="datatable"
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">

                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('SL') }}</th>
                                    <th class="border-0">{{ translate('messages.employees') }}</th>
                                    <th class="border-0">{{ translate('Role') }}</th>
                                    <th class="border-0">{{ translate('messages.amount') }}</th>
                                    <th class="border-0">{{ translate('Reason') }}</th>
                                    <th class="border-0">{{ translate('Note') }}</th>
                                    <th class="border-0">{{ translate('messages.date') }}</th>
                                </tr>
                            </thead>

                            <tbody id="set-rows">

                                @foreach ($credit_records as $k => $record)

                                    <tr>

                                        <td>
                                            {{ $k + $credit_records->firstItem() }}
                                        </td>

                                        <td>
                                            @if ($record->employee)
                                                {{ $record->employee->f_name . ' ' . $record->employee->l_name }}
                                            @else
                                                <span class="text-danger text-capitalize">
                                                    {{ translate('Employee Deleted') }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge badge-soft-info text-capitalize">
                                                {{ $record->employee->role->name ?? '—' }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="text-success font-weight-bold">
                                                + {{ \App\CentralLogics\Helpers::format_currency($record->amount) }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="badge badge-soft-success text-capitalize">
                                                {{ translate(str_replace('_', ' ', $record->reason)) }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ $record->note ?? '—' }}
                                        </td>

                                        <td>
                                            {{ \App\CentralLogics\Helpers::time_date_format($record->created_at) }}
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>
                    </div>

                </div>

                @if (count($credit_records) !== 0)
                    <hr>
                @endif

                <div class="page-area">
                    {!! $credit_records->links() !!}
                </div>

                @if (count($credit_records) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="empty">
                        <h5>{{ translate('no_data_found') }}</h5>
                    </div>
                @endif

            </div>

        </div>
    </div>

</div>

{{-- Confirmation Modal --}}
<div id="confirmModal"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">

    <div style="background:#fff; border-radius:16px; padding:36px 32px 28px; width:380px; max-width:calc(100vw - 32px); text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.18); position:relative;">

        {{-- Close --}}
        <button onclick="closeConfirm()"
            style="position:absolute; top:14px; right:16px; background:none; border:none; font-size:18px; color:#adb5bd; cursor:pointer; line-height:1; padding:0;">
            &times;
        </button>

        {{-- Icon --}}
        <div style="width:72px; height:72px; border-radius:50%; border:3px solid #1abc9c; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
            <span style="font-size:32px; font-weight:700; color:#1abc9c; line-height:1;">
                ✓
            </span>
        </div>

        {{-- Title --}}
        <h5 style="margin-bottom:8px; font-size:18px; font-weight:700; color:#1a1a2e; letter-spacing:-0.2px;">
            {{ translate('Are you sure ?') }}
        </h5>

        {{-- Message --}}
        <p style="color:#6c757d; font-size:14px; margin-bottom:28px; line-height:1.5;" id="confirmMessage"></p>

        {{-- Buttons --}}
        <div style="display:flex; gap:12px; justify-content:center;">

            <button onclick="closeConfirm()"
                style="flex:1; background:#f0f0f0; color:#555; border:none; padding:11px 20px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600;">
                {{ translate('No') }}
            </button>

            <button id="confirmYesBtn"
                style="flex:1; background:#1a7a6e; color:#fff; border:none; padding:11px 20px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600;">
                {{ translate('Yes') }}
            </button>

        </div>

    </div>
</div>

@endsection

@push('script_2')
<script>
    "use strict";

    // Initialize Select2
    $('#employee_id').select2({
        placeholder: '{{ translate('Select Employee') }}'
    });

    // Handle form submit
    $('#credit_employee_form').on('submit', function (e) {

        e.preventDefault();

        var form = this;

        showConfirm(
            '{{ translate('Are you sure you want to credit this employee wallet?') }}',
            function () {

                var formData = new FormData(form);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.post({
                    url: '{{ route('vendor.employee.wallet.credit') }}',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,

                    success: function (data) {

                        toastr.success(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });

                        setTimeout(function () {
                            location.href = '{{ route('vendor.employee.wallet.credit.page') }}';
                        }, 2000);
                    },

                    error: function (xhr) {

                        var response = xhr.responseJSON;

                        if (xhr.status === 422 && response) {

                            if (response.errors &&
                                typeof response.errors === 'object' &&
                                !Array.isArray(response.errors)) {

                                $.each(response.errors, function (field, messages) {
                                    toastr.error(messages[0], {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                });

                            } else if (response.message) {

                                toastr.error(response.message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }

                        } else if (response && response.message) {

                            toastr.error(response.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });

                        } else {

                            toastr.error(
                                '{{ translate('Something went wrong. Please try again.') }}',
                                {
                                    CloseButton: true,
                                    ProgressBar: true
                                }
                            );
                        }
                    }
                });

            }
        );

    });

    // Reset
    $('#reset_btn').on('click', function () {
        $('#employee_id').val(null).trigger('change');
    });

    // Confirm Modal
    function showConfirm(message, callback) {
        $('#confirmMessage').text(message);
        $('#confirmModal').css('display', 'flex');

        $('#confirmYesBtn').off('click').on('click', function () {
            closeConfirm();
            callback();
        });
    }

    function closeConfirm() {
        $('#confirmModal').hide();
    }

    // Close on backdrop click
    $('#confirmModal').on('click', function (e) {
        if ($(e.target).is('#confirmModal')) {
            closeConfirm();
        }
    });

    // ESC close
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closeConfirm();
        }
    });
</script>
@endpush