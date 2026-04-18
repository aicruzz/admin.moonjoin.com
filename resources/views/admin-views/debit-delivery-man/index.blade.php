@extends('layouts.admin.app')

@section('title', translate('Debit Delivery Men'))

@section('content')
    <div class="content container-fluid">
        {{-- Page Header --}}
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/collect-cash.png') }}" class="w--22" alt="">
                </span>
                <span>{{ translate('Debit Delivery Men') }}</span>
            </h1>
        </div>

        {{-- Debit Form --}}
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.transactions.debit-delivery-man.store') }}" method="post" id="debit_dm_form">
                    @csrf
                    <div class="row g-3">

                        {{-- Delivery Man Select --}}
                        <div class="col-sm-6">
                            <div class="form-group mb-0">
                                <label class="form-label" for="deliveryman">
                                    {{ translate('messages.deliveryman') }}
                                    <span class="input-label-secondary"></span>
                                </label>
                                <select id="deliveryman" name="deliveryman_id"
                                    data-placeholder="{{ translate('messages.select_deliveryman') }}" class="form-control"
                                    title="Select deliveryman">
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
                                <input class="form-control" type="number" min="0.01" step="0.01" name="amount" id="amount"
                                    max="999999999999.99" placeholder="{{ translate('ex_100') }}">
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div class="col-sm-6">
                            <div class="form-group mb-0">
                                <label class="form-label" for="reason">
                                    {{ translate('Reason') }}
                                    <span class="text-danger">*</span>
                                </label>
                                @php
                                    $dm_cancel_reasons = \App\Models\DebitDeliverymanReason::where('status', 1)
                                        ->latest()
                                        ->get();
                                @endphp
                                <select class="form-control" name="reason" id="reason" required>
                                    <option value="">-- {{ translate('Select reason') }} --</option>
                                    @forelse ($dm_cancel_reasons as $dm_reason)
                                        <option value="{{ $dm_reason->id }}">{{ $dm_reason->reason }}</option>
                                    @empty
                                        <option value="" disabled>
                                            {{ translate('No reasons configured. Please add them in Delivery Man Settings.') }}
                                        </option>
                                    @endforelse
                                </select>
                                @if ($dm_cancel_reasons->isEmpty())
                                    <small class="text-warning d-block mt-1">
                                        <i class="tio-warning-outlined"></i>
                                        {{ translate('No active debit delivery man reasons found. Go to') }}
                                        <a href="{{ route('admin.business-settings.order-index') }}" target="_blank">
                                            {{ translate('Order Settings') }}
                                        </a>
                                        {{ translate('to add them.') }}
                                    </small>
                                @endif
                            </div>
                        </div>

                        {{-- Note / Description --}}
                        <div class="col-sm-6">
                            <div class="form-group mb-0">
                                <label class="form-label" for="note">
                                    {{ translate('Note') }}
                                    <span class="input-label-secondary">({{ translate('Optional') }})</span>
                                </label>
                                <input class="form-control" type="text" name="note" id="note" maxlength="500"
                                    placeholder="{{ translate('Enter additional note...') }}">
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="col-sm-12">
                            <div class="btn--container justify-content-end">
                                <button class="btn btn--reset" type="reset" id="reset_btn">
                                    {{ translate('messages.reset') }}
                                </button>
                                <button class="btn btn--danger" type="button" id="confirm_debit_btn">
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
                                    <input id="datatableSearch" name="search" type="search" class="form-control h--40px"
                                        placeholder="{{ translate('ex_: search_delivery_man') }}"
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
                                        <th class="border-0">{{ translate('messages.delivery_man') }}</th>
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
                                                @if ($record->delivery_man)
                                                    <a
                                                        href="{{ route('admin.users.delivery-man.preview', $record->delivery_man_id) }}">
                                                        {{ $record->delivery_man->f_name . ' ' . $record->delivery_man->l_name }}
                                                    </a>
                                                @else
                                                    <span class="text-danger text-capitalize">
                                                        {{ translate('messages.deliveryman_deleted') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-danger font-weight-bold">
                                                    - {{ \App\CentralLogics\Helpers::format_currency($record->amount) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-soft-warning text-capitalize">
                                                    {{ $debit_reasons[$record->reason] ?? translate(str_replace('_', ' ', $record->reason)) }}
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

    {{-- ============================================================ --}}
    {{-- Confirmation Modal --}}
    {{-- ============================================================ --}}
    <div class="modal fade" id="debitConfirmModal" tabindex="-1" role="dialog" aria-labelledby="debitConfirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="debitConfirmModalLabel">
                        <i class="tio-remove-from-trash text-danger mr-1"></i>
                        {{ translate('Confirm Debit') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="mb-3 text-muted">{{ translate('Please review the details before confirming.') }}</p>

                    <div class="card bg-soft-danger border-0">
                        <div class="card-body py-3">
                            <div class="row gy-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">{{ translate('messages.delivery_man') }}</small>
                                    <strong id="modal_dm_name">—</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">{{ translate('messages.amount') }}</small>
                                    <strong class="text-danger" id="modal_amount">—</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">{{ translate('Reason') }}</small>
                                    <strong id="modal_reason">—</strong>
                                </div>
                                <div class="col-6" id="modal_note_wrapper">
                                    <small class="text-muted d-block">{{ translate('Note') }}</small>
                                    <strong id="modal_note">—</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 mb-0 text-danger font-weight-bold">
                        <i class="tio-warning-outlined mr-1"></i>
                        {{ translate('This action will deduct the amount from the delivery man\'s wallet and cannot be undone.') }}
                    </p>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn--reset" data-dismiss="modal">
                        {{ translate('messages.cancel') }}
                    </button>
                    <button type="button" class="btn btn--danger" id="modal_confirm_btn">
                        <i class="tio-remove-from-trash mr-1"></i>
                        {{ translate('Yes, Debit Now') }}
                    </button>
                </div>

            </div>
        </div>
    </div>
    {{-- ============================================================ --}}

@endsection

@push('script_2')
    <script>
        "use strict";

        // Initialize Delivery Man Select2 with AJAX
        $('#deliveryman').select2({
            ajax: {
                url: '{{ url('/') }}/admin/users/delivery-man/get-deliverymen',
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return { results: data };
                }
            },
            placeholder: '{{ translate('messages.select_deliveryman') }}'
        });

        // Show wallet balance when a DM is selected
        $('#deliveryman').on('change', function () {
            var dmId = $(this).val();
            if (!dmId) {
                $('#account_info').html('');
                return;
            }
            $.get({
                url: '{{ url('/') }}/admin/users/delivery-man/get-account-data/' + dmId,
                dataType: 'json',
                success: function (data) {
                    $('#account_info').html(
                        ' ({{ translate('messages.earning_balance') }}: ' + data.earning_balance + ')'
                    );
                }
            });
        });

        // Open confirmation modal — validate first, then populate and show
        $('#confirm_debit_btn').on('click', function () {
            var dmSelect = $('#deliveryman');
            var dmName = dmSelect.find('option:selected').text().trim();
            var dmId = dmSelect.val();
            var amount = $('#amount').val();
            var reasonVal = $('#reason').val();
            var reasonTxt = $('#reason option:selected').text().trim();
            var note = $('#note').val().trim();

            // Basic front-end validation before opening modal
            if (!dmId) {
                toastr.error('{{ translate('messages.select_deliveryman') }}');
                return;
            }
            if (!amount || parseFloat(amount) <= 0) {
                toastr.error('{{ translate('messages.amount_required') }}');
                return;
            }
            if (!reasonVal) {
                toastr.error('{{ translate('Please select a reason') }}');
                return;
            }

            // Populate modal summary
            $('#modal_dm_name').text(dmName);
            $('#modal_amount').text('- ' + '{{ \App\CentralLogics\Helpers::currency_symbol() }}' + ' ' + parseFloat(amount).toFixed(2));
            $('#modal_reason').text(reasonTxt);
            $('#modal_note').text(note || '—');

            $('#debitConfirmModal').modal('show');
        });

        // Confirmed — submit the form via AJAX
        $('#modal_confirm_btn').on('click', function () {
            $('#debitConfirmModal').modal('hide');

            var formData = new FormData($('#debit_dm_form')[0]);

            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            // Disable button to prevent double submit
            $('#modal_confirm_btn').prop('disabled', true);

            $.post({
                url: '{{ route('admin.transactions.debit-delivery-man.store') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                        $('#modal_confirm_btn').prop('disabled', false);
                    } else {
                        toastr.success('{{ translate('messages.transaction_saved') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function () {
                            location.href = '{{ route('admin.transactions.debit-delivery-man.index') }}';
                        }, 2000);
                    }
                },
                error: function () {
                    toastr.error('{{ translate('messages.something_went_wrong') }}');
                    $('#modal_confirm_btn').prop('disabled', false);
                }
            });
        });

        // Reset account info and select when form is reset
        $('#reset_btn').on('click', function () {
            $('#account_info').html('');
            $('#deliveryman').val(null).trigger('change');
        });
    </script>
@endpush