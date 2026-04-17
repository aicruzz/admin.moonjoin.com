@extends('layouts.vendor.app')

@section('title',translate('messages.employee_wallet'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h2 class="page-header-title text-capitalize">
                        <div class="card-header-icon d-inline-flex mr-2 img">
                            <img src="{{asset('/public/assets/admin/img/image_90.png')}}" alt="public">
                        </div>
                        <span>
                            {{translate('messages.employee_disbursement_method_setup')}}
                        </span>
                    </h2>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h3 class="card-title">
                        {{ translate('employee_disbursement_methods') }}
                    </h3>
                    <form >
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control" placeholder="{{ translate('Ex : Search by name') }}"  value="{{ request()?->search ?? null }}" aria-label="Search">

                            <button type="submit" class="btn btn--secondary">
                                <i class="tio-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                &nbsp;
                <div class="p--10px">
                    <a class="btn btn--primary btn-outline-primary w-100" href="javascript:" data-toggle="modal" data-target="#balance-modal">{{translate('messages.add_new_method')}}</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable"
                           class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table" data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                        <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.sl') }}</th>
                            <th>{{translate('messages.payment_method_name')}}</th>
                            <th>{{translate('messages.payment_info')}}</th>
                            <th>{{translate('messages.default')}}</th>
                            <th class="w-100px text-center">{{translate('messages.action')}}</th>
                        </tr>
                        </thead>
                        <tbody id="set-rows">
                        
                        </tbody>
                    </table>
                    @if(count($vendor_withdrawal_methods ?? []) === 0)
                        <div class="empty--data">
                            <img src="{{asset('public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                            <h5>
                                {{translate('no_data_found')}}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-footer">
                
            </div>
        </div>
        <!-- Card -->
    </div>
@endsection
