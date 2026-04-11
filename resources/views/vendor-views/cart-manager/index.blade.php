@extends('layouts.vendor.app')

@section('title', translate('messages.Cart_Manager'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-shopping-cart"></i>
                </span>
                <span>
                    {{ translate('messages.Customer_Carts') }} <span class="badge badge-soft-dark ml-1">{{ count($carts) }}</span>
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            <div class="col-12 col-md-6">
                                <h5 class="card-header-title">{{ translate('messages.active_carts_list') }}</h5>
                            </div>
                            <div class="col-12 col-md-6">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group input-group-merge input-group-flush">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                                            placeholder="{{ translate('messages.search_by_name_or_phone') }}"
                                            aria-label="{{ translate('messages.search') }}" value="{{ request('search') }}">
                                        <button type="submit" class="btn btn-primary">{{ translate('messages.search') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('messages.sl') }}</th>
                                    <th>{{ translate('messages.customer_info') }}</th>
                                    <th>{{ translate('messages.total_items') }}</th>
                                    <th>{{ translate('messages.est_total') }}</th>
                                    <th class="text-center">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($carts as $userId => $userCarts)
                                    @php
                                        $user = \App\Models\User::find($userId);
                                        $totalAmount = $userCarts->sum(function($item){ return $item->price * $item->quantity; });
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @if($user)
                                                <a href="#" class="text-body text-hover-primary">
                                                    {{ $user->f_name }} {{ $user->l_name }}
                                                </a>
                                                <br>
                                                <small>{{ $user->phone }}</small>
                                            @else
                                                {{ translate('messages.unknown_user') }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-info p-2">{{ $userCarts->count() }} {{ translate('messages.items') }}</span>
                                        </td>
                                        <td>
                                            {{ \App\CentralLogics\Helpers::format_currency($totalAmount) }}
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                                    href="{{ route('vendor.cart-manager.view', $userId) }}" title="{{ translate('messages.view_cart') }}">
                                                    <i class="tio-visible"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center p-4">
                                            {{ translate('messages.no_customer_carts_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
