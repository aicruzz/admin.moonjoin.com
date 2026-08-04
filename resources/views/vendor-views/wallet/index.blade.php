@php
    $vendorData = \App\CentralLogics\Helpers::get_store_data();
    $title = $vendorData?->module_type == 'rental' && addon_published_status('Rental') ? 'Provider' : 'Store';
@endphp

@extends('layouts.vendor.app')
@section('title', translate('messages.' . $title . '_wallet'))

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
                            {{translate('messages.' . $title . '_wallet')}}
                        </span>
                    </h2>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <?php
        $wallet = \App\Models\StoreWallet::where('vendor_id',\App\CentralLogics\Helpers::get_vendor_id())->first();
        if(isset($wallet)==false){
            \Illuminate\Support\Facades\DB::table('store_wallets')->insert([
                'vendor_id'=>\App\CentralLogics\Helpers::get_vendor_id(),
                'created_at'=>now(),
                'updated_at'=>now()
            ]);
            $wallet = \App\Models\StoreWallet::where('vendor_id',\App\CentralLogics\Helpers::get_vendor_id())->first();
        }
        ?>
        @include('vendor-views.wallet.partials._balance_data',['wallet'=>$wallet])

        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="datatable"
                       class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                       data-hs-datatables-options='{
                                    "order": [],
                                    "orderCellsTop": true,
                                    "paging":false
                                }' >
                    <thead class="thead-light">
                    <tr>
                        <th>{{ translate('messages.sl') }}</th>
                        <th>{{translate('messages.amount')}}</th>
                        <th>{{translate('messages.request_time')}}</th>
                        <th>{{translate('messages.disbursement_method')}}</th>
                        <th>{{translate('messages.Transaction_Type')}}</th>
                        <th>{{translate('messages.status')}}</th>
                        <th >{{translate('messages.note')}}</th>
                        <th class="w-5px">{{ translate('messages.Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($withdraw_req as $k=>$wr)

                        <tr>
                            <td>{{$k+$withdraw_req->firstItem()}}</td>
                            <td> {{ \App\CentralLogics\Helpers::format_currency($wr['amount'])}}</td>

                            <td>
                                <span class="d-block">{{ \App\CentralLogics\Helpers::time_date_format($wr['created_at'])}}</span>
                            </td>
                            <td>
                                @if($wr->method)

                                    <a href="#" data-toggle="modal" data-target="#exampleModal1-{{ $wr->id }}">
                                        {{translate($wr->method->method_name)}}</a>
                                    <!-- Modal -->
                                    <div class="modal fade" id="exampleModal1-{{ $wr->id }}" tabindex="-1"  role="dialog" aria-labelledby="exampleModalLabel"        aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">{{translate('messages.disbursement_method_details')}}  </h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>

                                                </div>
                                                @php
                                                $fields = json_decode($wr->withdrawal_method_fields, true);
                                                @endphp
                                                <div class="form-group">
                                                    <label class="mt-2">{{ translate('messages.bank_name') }}</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $fields['bank_name'] ?? '-' }}"
                                                        readonly>
                                                    <label class="mt-2">{{ translate('messages.account_number') }}</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $fields['account_number'] ?? '-' }}"
                                                        readonly>
                                                    <label class="mt-2">{{ translate('messages.account_name') }}</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $fields['account_name'] ?? '-' }}"
                                                        readonly>
                                                </div>
                                                <div class="modal-footer">
                                                    <button id="reset_btn" type="reset" data-dismiss="modal" class="btn btn-secondary" >{{ translate('Close') }} </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                @else
                                    {{ translate('Default_method') }}
                                @endif

                            </td>
                            <td>
                                @if ($wr->type ==  'adjustment' )
                                    {{ translate('Wallet_Adjustment') }}
                                @elseif ($wr->type == 'manual' )
                                    {{ translate('Withdraw_Request') }}
                                @elseif ($wr->type == 'disbursement' )
                                    {{ translate('disbursement') }}
                                @else
                                    {{ translate($wr->type) }}
                                @endif
                            </td>
                            <td>
                                @if($wr->approved==0)
                                    <label class="badge badge-soft-info">{{translate('messages.pending')}}</label>
                                @elseif($wr->approved==1)
                                    <label class="badge badge-soft-success">{{translate('messages.approved')}}</label>
                                @else
                                    <label class="badge badge-soft-danger">{{translate('messages.denied')}}</label>
                                @endif
                            </td>


                            <td >
                                @if($wr->transaction_note )
                                    @if($wr->transaction_note == 'Store_wallet_adjustment_partial' )
                                   {{ translate('Adjusted_Amount_Partially') }}
                                    @elseif($wr->transaction_note == 'Store_wallet_adjustment_full' )
                                        {{ translate('Adjusted_Amount') }}
                                    @else
                                        {!!
                                   Str::limit(translate($wr->transaction_note), 20,
                                   '<a  href="#" class="showMyModal" data-message="'.translate($wr->transaction_note).'" >...Read more.</a>'
                                   )  !!}
                                    @endif

                                @else
                                    {{ translate('messages.N/A') }}
                                @endif
                            </td>

                            <td>

                                @if($wr->approved==0)
                                    <a class="btn btn-outline-danger btn--danger action-btn form-alert" href="javascript:" data-id="withdraw-{{$wr['id']}}" data-message="{{ translate('Want to delete this  ?') }}" title="{{translate('messages.delete')}}"><i class="tio-delete-outlined"></i>
                                    </a>

                                    <form action="{{route('vendor.wallet.close-request',[$wr['id']])}}"
                                          method="post" id="withdraw-{{$wr['id']}}">
                                        @csrf @method('delete')
                                    </form>
                                @else
                                    <label>{{translate('messages.complete')}}</label>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if(count($withdraw_req) === 0)
                    <div class="empty--data">
                        <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                        <h5>
                            {{translate('no_data_found')}}
                        </h5>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-footer pt-0 border-0">
            {{$withdraw_req->links()}}
        </div>
    </div>

    <div class="modal fade" id="payment_model" tabindex="-1"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{translate('messages.Pay_Via_Online')}}  </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>

                </div>
                <form action="{{ route('vendor.wallet.make_payment') }}" method="POST" class="needs-validation">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" value="{{ \App\CentralLogics\Helpers::get_store_id() }}" name="store_id"/>
                        <input type="hidden" value="{{ abs($wallet->collected_cash) }}" name="amount"/>
                        <h5 class="mb-5 ">{{ translate('Pay_Via_Online') }} &nbsp; <small>({{ translate('Faster_&_secure_way_to_pay_bill') }})</small></h5>
                        <div class="row g-3">
                            @forelse ($data as $item)
                                <div class="col-sm-6">
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="radio" required id="{{$item['gateway'] }}" name="payment_gateway" value="{{$item['gateway'] }}">
                                        <label for="{{$item['gateway'] }}" class="d-flex align-items-center gap-3 mb-0">
                                            <img height="24" src="{{ asset('storage/app/public/payment_modules/gateway_image/'. $item['gateway_image']) }}" alt="">
                                            {{ $item['gateway_title'] }}
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <h1>{{ translate('no_payment_gateway_found') }}</h1>
                            @endforelse
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button id="reset_btn" type="reset" data-dismiss="modal" class="btn btn-secondary" >{{ translate('Close') }} </button>
                        <button type="submit" class="btn btn-primary">{{ translate('Proceed') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>


    <div class="modal fade" id="Adjust_wallet" tabindex="-1"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{translate('messages.Adjust_Wallet')}}  </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>

                </div>
                <form action="{{ route('vendor.wallet.make_wallet_adjustment') }}" method="POST" class="needs-validation">
                    <div class="modal-body">
                        @csrf
                        <h5 class="mb-5 ">{{ translate('This_will_adjust_the_collected_cash_on_your_earning') }} </h5>
                    </div>

                    <div class="modal-footer">
                        <button id="reset_btn" type="reset" data-dismiss="modal" class="btn btn-secondary" >{{ translate('Close') }} </button>
                        <button type="submit" class="btn btn-primary">{{ translate('Proceed') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/vendor/wallet-method.js"></script>

<script>
    "use strict";

    $('#withdraw_method').on('change', function () {
        $('#submit_button').attr("disabled", "true");
        let method_id = this.value;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: "{{route('vendor.wallet.method-list')}}" + "?method_id=" + method_id,
            data: {},
            processData: false,
            contentType: false,
            type: 'get',
            success: function (response) {
                $('#submit_button').removeAttr('disabled');
                let method_fields = response.content.method_fields;
                $("#method-filed__div").html("");
                
                method_fields.forEach((element, index) => {
                    let fieldHTML = "";

                    // Account Number field with built-in layout validation button
                    if (element.input_name === 'account_number') {
                        fieldHTML = `
                            <div class="form-group mt-2">
                                <label for="${element.input_name}" class="fz-16 text-capitalize c1 mb-2">${element.input_name.replaceAll('_', ' ')}</label>
                                <div class="input-group">
                                    <input type="${element.input_type == 'phone' ? 'number' : element.input_type}" class="form-control" id="account_number_field" name="${element.input_name}" placeholder="${element.placeholder}" ${element.is_required === 1 ? 'required' : ''}>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary" id="dynamic_verify_btn">{{ translate('Verify') }}</button>
                                    </div>
                                </div>
                                <div id="dynamic_verify_result" class="small mt-1"></div>
                            </div>
                        `;
                    } else {
                        // Standard generation for any other fields that might be added later
                        fieldHTML = `
                            <div class="form-group mt-2">
                                <label for="${element.input_name}" class="fz-16 text-capitalize c1 mb-2">${element.input_name.replaceAll('_', ' ')}</label>
                                <input type="${element.input_type == 'phone' ? 'number' : element.input_type}" class="form-control" name="${element.input_name}" placeholder="${element.placeholder}" ${element.is_required === 1 ? 'required' : ''}>
                            </div>
                        `;
                    }

                    $("#method-filed__div").append(fieldHTML);
                });

            },
            error: function () {
                $('#submit_button').removeAttr('disabled');
            }
        });
    });

    // Verification engine interceptor
    $(document).on('click', '#dynamic_verify_btn', function() {
        let accountNumber = $("#account_number_field").val();
        
        // FIX: Grab the text (the bank name like 'Opay', 'Palmpay') from the selected option on top
        let bankName = $('#withdraw_method option:selected').text().trim(); 
        
        let resultDiv = $('#dynamic_verify_result');
        let button = $(this);

        if (!accountNumber) {
            resultDiv.html('<span class="text-danger">Please enter an account number first.</span>');
            return;
        }

        if (!bankName || $('#withdraw_method').val() == "") {
            resultDiv.html('<span class="text-danger">Please select a payment method/bank first.</span>');
            return;
        }

        button.prop('disabled', true).text('Verifying...');
        resultDiv.html('<span class="text-muted">Performing name enquiry...</span>');

        $.ajax({
            url: "{{ route('vendor.wallet.name-enquiry') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                accountNumber: accountNumber, 
                bankCode: bankName // Passes the text name ('Opay') into your bankCode field parameters
            },
            success: function(response) {
                button.prop('disabled', false).text('Verify');
                
                if (response.success && response.data) {
                    let accountName = response.data.accountName;
                    resultDiv.html(`<span class="text-success">✔ Verified: <strong>${accountName}</strong></span>`);
                    
                    // Auto-fill an account_name field if it exists in the form
                    if ($("input[name='account_name']").length) {
                        $("input[name='account_name']").val(accountName);
                    }
                } else {
                    let errorMsg = response.message || 'Account could not be verified';
                    resultDiv.html(`<span class="text-danger">❌ ${errorMsg}</span>`);
                }
            },
            error: function(xhr) {
                button.prop('disabled', false).text('Verify');
                let parseErr = xhr.responseJSON ? (xhr.responseJSON.message || 'Error executing name enquiry.') : 'Error processing request.';
                resultDiv.html(`<span class="text-danger">❌ ${parseErr}</span>`);
            }
        });
    });

    $(document).ready(function() {
        $("form").on("submit", function(event) {
            $('#set_disable').attr('disabled', true);
        });
    });
</script>
@endpush
