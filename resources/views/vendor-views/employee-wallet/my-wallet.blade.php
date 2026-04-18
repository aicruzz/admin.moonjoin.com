@extends('layouts.vendor.app')

@section('title', translate('messages.my_wallet'))

@section('content')
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <i class="tio-wallet mr-2"></i>
                        {{ translate('messages.my_wallet') }}
                    </h1>
                </div>
            </div>
        </div>

        {{-- Wallet Balance Card --}}
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card card-hover-shadow">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">
                            {{ translate('messages.current_balance') }}
                        </h6>
                        <div class="row align-items-center gx-2">
                            <div class="col">
                                <h2 class="card-title text-success mb-0">
                                    {{ \App\CentralLogics\Helpers::format_currency($employee->wallet_balance ?? 0) }}
                                </h2>
                            </div>
                            <div class="col-auto">
                                <span class="badge badge-soft-success p-2">
                                    <i class="tio-trending-up"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-muted font-size-sm">
                                {{ $employee->f_name }} {{ $employee->l_name }}
                                &mdash;
                                {{ $employee->role->name ?? translate('messages.employee') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transaction History --}}
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">
                    {{ translate('messages.transaction_history') }}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ translate('messages.amount') }}</th>
                                <th>{{ translate('messages.type') }}</th>
                                <th>{{ translate('messages.reason') }}</th>
                                <th>{{ translate('messages.note') }}</th>
                                <th>{{ translate('messages.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $index => $transaction)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $index }}</td>
                                    <td>
                                        <strong class="text-{{ $transaction->type == 'debit' ? 'danger' : 'success' }}">
                                            {{ $transaction->type == 'debit' ? '-' : '+' }}
                                            {{ \App\CentralLogics\Helpers::format_currency($transaction->amount) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-soft-{{ $transaction->type == 'debit' ? 'danger' : 'success' }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->reason }}</td>
                                    <td>{{ $transaction->note ?? '—' }}</td>
                                    <td>{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt=""
                                            style="width: 100px;" class="mb-3">
                                        <p class="text-muted mb-0">
                                            {{ translate('messages.no_transaction_found') }}
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($transactions->hasPages())
                    <div class="card-footer border-top">
                        {{ $transactions->links() }}
                    </div>
                @endif

            </div>
        </div>

    </div>
@endsection