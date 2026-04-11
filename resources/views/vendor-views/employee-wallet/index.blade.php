@extends('layouts.vendor.app')

@section('title', translate('Debit Employee Wallet'))

@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/collect-cash.png') }}" class="w--22" alt="">
            </span>
            <span>{{ translate('Debit Employee Wallet') }}</span>
        </h1>
    </div>

    {{-- Debit Form --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('vendor.employee.wallet.debit') }}" method="post" id="debit_employee_form">
                @csrf
                <div class="row g-3">

                    {{-- Employee Select --}}
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="employee_id">
                                {{ translate('messages.employees') }}
                                <span class="input-label-secondary"></span>
                            </label>
                            <select id="employee_id" name="employee_id"
                                class="form-control js-select2-custom"
                                data-placeholder="{{ translate('Select Employee') }}"
                                title="{{ translate('Select Employee') }}" required>
                                <option value="">-- {{ translate('Select Employee') }} --</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->f_name . ' ' . $employee->l_name }}
                                        ({{ $employee->role->name ?? translate('No Role') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="amount">
                                {{ translate('messages.amount') }} {{ \App\CentralLogics\Helpers::currency_symbol() }}
                                <span class="input-label-secondary text-info" id="account_info"></span>
                            </label>
                            <input class="form-control" type="number" min="0.01" step="0.01"
                                name="amount" id="amount" max="999999999999.99"
                                placeholder="{{ translate('ex_100') }}" required>
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
                                <option value="">-- {{ translate('Select reason') }} --</option>
                                <option value="salary_deduction">{{ translate('Salary Deduction') }}</option>
                                <option value="late_penalty">{{ translate('Late Penalty') }}</option>
                                <option value="damage_or_loss">{{ translate('Damage / Item Loss') }}</option>
                                <option value="cash_shortage">{{ translate('Cash Shortage') }}</option>
                                <option value="other">{{ translate('Other') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Note --}}
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="note">
                                {{ translate('Note') }}
                                <span class="input-label-secondary">({{ translate('Optional') }})</span>
                            </label>
                            <input class="form-control" type="text" name="note" id="note"
                                maxlength="500" placeholder="{{ translate('Enter additional note...') }}">
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="col-sm-12">
                        <div class="btn--container justify-content-end">
                            <button class="btn btn--reset" type="reset" id="reset_btn">
                                {{ translate('messages.reset') }}
                            </button>
                            <button class="btn btn--danger" type="submit">
                                <i class="tio-remove-from-trash mr-1"></i>
                                {{ translate('Debit Account') }}
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- Transaction History Table --}}
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header py-2 border-0">
                    <div class="search--button-wrapper">
                        <h5 class="card-title d-flex gap-2 align-items-center">
                            <span class="card-header-icon"><i class="tio-remove-from-trash"></i></span>
                            <span>{{ translate('Debit History') }}</span>
                            <span class="badge badge-soft-secondary" id="itemCount">
                                {{ $debit_records->total() }}
                            </span>
                        </h5>

                        <form class="search-form theme-style">
                            <div class="input-group input--group">
                                <input id="datatableSearch" name="search" type="search"
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
                                @foreach ($debit_records as $k => $record)
                                    <tr>
                                        <td>{{ $k + $debit_records->firstItem() }}</td>
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
                                            <span class="text-danger font-weight-bold">
                                                - {{ \App\CentralLogics\Helpers::format_currency($record->amount) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-warning text-capitalize">
                                                {{ translate(str_replace('_', ' ', $record->reason)) }}
                                            </span>
                                        </td>
                                        <td>{{ $record->note ?? '—' }}</td>
                                        <td>{{ \App\CentralLogics\Helpers::time_date_format($record->created_at) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if (count($debit_records) !== 0)
                    <hr>
                @endif
                <div class="page-area">
                    {!! $debit_records->links() !!}
                </div>

                @if (count($debit_records) === 0)
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
<div id="confirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:36px 32px 28px; width:380px; max-width:calc(100vw - 32px); text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.18); position:relative;">

        {{-- Close (×) button --}}
        <button onclick="closeConfirm()" style="position:absolute; top:14px; right:16px; background:none; border:none; font-size:18px; color:#adb5bd; cursor:pointer; line-height:1; padding:0;">&times;</button>

        {{-- Warning icon --}}
        <div style="width:72px; height:72px; border-radius:50%; border:3px solid #f6a623; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
            <span style="font-size:32px; font-weight:700; color:#f6a623; line-height:1;">!</span>
        </div>

        {{-- Title --}}
        <h5 style="margin-bottom:8px; font-size:18px; font-weight:700; color:#1a1a2e; letter-spacing:-0.2px;">
            {{ translate('Are you sure ?') }}
        </h5>

        {{-- Dynamic message --}}
        <p style="color:#6c757d; font-size:14px; margin-bottom:28px; line-height:1.5;" id="confirmMessage"></p>

        {{-- Action buttons --}}
        <div style="display:flex; gap:12px; justify-content:center;">
            <button onclick="closeConfirm()"
                style="flex:1; background:#f0f0f0; color:#555; border:none; padding:11px 20px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; transition:background .2s;"
                onmouseover="this.style.background='#e2e2e2'" onmouseout="this.style.background='#f0f0f0'">
                {{ translate('No') }}
            </button>
            <button id="confirmYesBtn"
                style="flex:1; background:#1a7a6e; color:#fff; border:none; padding:11px 20px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; transition:background .2s;"
                onmouseover="this.style.background='#155f55'" onmouseout="this.style.background='#1a7a6e'">
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

    // Handle form submission via AJAX
    $('#debit_employee_form').on('submit', function (e) {
        e.preventDefault();
        var form = this;

        showConfirm('{{ translate('Are you sure you want to debit this employee wallet? This action cannot be undone.') }}', function () {
            var formData = new FormData(form);

            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            $.post({
                url: '{{ route('vendor.employee.wallet.debit') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    // HTTP 200 — transaction saved
                    toastr.success(data.message, { CloseButton: true, ProgressBar: true });
                    setTimeout(function () {
                        location.href = '{{ route('vendor.employee.wallet.index') }}';
                    }, 2000);
                },
                error: function (xhr) {
                    var response = xhr.responseJSON;

                    if (xhr.status === 422 && response) {
                        // Laravel validation errors: { errors: { field: ['msg'] } }
                        if (response.errors && typeof response.errors === 'object' && !Array.isArray(response.errors)) {
                            $.each(response.errors, function (field, messages) {
                                toastr.error(messages[0], { CloseButton: true, ProgressBar: true });
                            });
                        }
                        // Custom 422 business logic error: { status: 'error', message: '...' }
                        else if (response.message) {
                            toastr.error(response.message, { CloseButton: true, ProgressBar: true });
                        }
                    }
                    // 404 or any other HTTP error with a message
                    else if (response && response.message) {
                        toastr.error(response.message, { CloseButton: true, ProgressBar: true });
                    }
                    // Fallback
                    else {
                        toastr.error('{{ translate('Something went wrong. Please try again.') }}', { CloseButton: true, ProgressBar: true });
                    }
                }
            });
        });
    });

    // Reset select2 on form reset
    $('#reset_btn').on('click', function () {
        $('#employee_id').val(null).trigger('change');
        $('#account_info').html('');
    });

    // ---- Confirm Modal Helpers ----

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
        if ($(e.target).is('#confirmModal')) closeConfirm();
    });

    // Close on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closeConfirm();
    });
</script>
@endpush