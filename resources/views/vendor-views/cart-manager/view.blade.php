@extends('layouts.vendor.app')

@section('title', translate('messages.Cart_Details'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <i class="tio-shopping-cart-outlined"></i>
                        </span>
                        <span>{{ translate('messages.Cart_Details_for') }} {{ $user->f_name }} {{ $user->l_name }}</span>
                    </h1>
                </div>
                <div class="col-sm-auto d-flex gap-2">
                    <a class="btn btn--primary btn-outline-primary" href="{{ route('vendor.cart-manager.notify-customer', $user->id) }}">
                        <i class="tio-notifications-on-outlined"></i> {{ translate('messages.notify_customer_to_re-edit') }}
                    </a>
                    <a class="btn btn-primary" href="{{ route('vendor.cart-manager.list') }}">
                        <i class="tio-home-outlined"></i> {{ translate('messages.back_to_list') }}
                    </a>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-header-title">{{ translate('messages.add_item_to_cart') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('vendor.cart-manager.add-item') }}" method="POST">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="row align-items-end">
                                <div class="col-sm-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('messages.select_item') }}</label>
                                        <select name="item_id" id="add_item_id" class="form-control js-select2-custom" required>
                                            <option value="" selected disabled>{{ translate('messages.select_item') }}</option>
                                            @foreach($storeItems as $item)
                                                <option value="{{ $item->id }}" data-price="{{ $item->price }}">{{ $item->name }} ({{ \App\CentralLogics\Helpers::format_currency($item->price) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('messages.price') }}</label>
                                        <input type="number" name="price" id="add_item_price" class="form-control" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-group mb-0">
                                        <label class="input-label">{{ translate('messages.quantity') }}</label>
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <button type="submit" class="btn btn-primary btn-block">{{ translate('messages.add') }}</button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="consentCheckbox" name="consent_checkbox" required>
                                    <label class="custom-control-label" for="consentCheckbox">
                                        {{ translate('messages.i_confirm_customer_consent_for_this_action') }}
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-header-title">{{ translate('messages.cart_items') }}</h5>
                        <button class="btn btn-sm btn-primary" onclick="$('#add_item_id').select2('open');">
                            <i class="tio-add"></i> {{ translate('messages.add_new_item') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('messages.item') }}</th>
                                        <th>{{ translate('messages.price') }}</th>
                                        <th>{{ translate('messages.quantity') }}</th>
                                        <th>{{ translate('messages.total') }}</th>
                                        <th class="text-center">{{ translate('messages.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($grandTotal = 0)
                                    @foreach($cartItems as $cart)
                                        @php($product = $cart->item)
                                        @if($product)
                                            @php($grandTotal += ($cart->price * $cart->quantity))
                                            <tr>
                                                <td>
                                                    <div class="media">
                                                        <img class="avatar avatar-sm mr-3"
                                                             src="{{ asset('storage/app/public/product') }}/{{ $product->image }}"
                                                             onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'"
                                                             alt="{{ $product->name }}">
                                                        <div class="media-body">
                                                            <h5 class="text-hover-primary mb-0">{{ $product->name }}</h5>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ \App\CentralLogics\Helpers::format_currency($cart->price) }}</td>
                                                <td>
                                                    <form action="{{ route('vendor.cart-manager.update-quantity') }}" method="POST" class="d-flex align-items-center">
                                                        @csrf
                                                        <input type="hidden" name="cart_id" value="{{ $cart->id }}">
                                                        <input type="number" name="quantity" class="form-control form-control-sm" value="{{ $cart->quantity }}" min="1" style="width: 80px;" onchange="this.form.submit()">
                                                    </form>
                                                </td>
                                                <td>{{ \App\CentralLogics\Helpers::format_currency($cart->price * $cart->quantity) }}</td>
                                                <td>
                                                    <div class="btn--container justify-content-center">
                                                        <button class="btn btn-sm btn--primary btn-outline-primary action-btn edit-cart-item"
                                                                data-id="{{ $cart->id }}"
                                                                title="{{ translate('messages.edit') }}">
                                                            <i class="tio-edit"></i>
                                                        </button>
                                                        {{-- Remove triggers refund automatically --}}
                                                        <a class="btn btn-sm btn--danger btn-outline-danger action-btn"
                                                           href="{{ route('vendor.cart-manager.remove-item', $cart->id) }}"
                                                           onclick="return confirm('{{ translate('messages.Are_you_sure_remove_this_item_REFUND') }}')"
                                                           title="{{ translate('messages.remove_and_refund') }}">
                                                            <i class="tio-delete-outlined"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php($grandTotal += $cart->price * $cart->quantity)
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row justify-content-end">
                            <div class="col-md-6">
                                <dl class="row text-right">
                                    <dt class="col-6">{{ translate('messages.total') }}:</dt>
                                    <dd class="col-6">{{ \App\CentralLogics\Helpers::format_currency($grandTotal) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-user"></i> {{ translate('messages.customer_details') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="media align-items-center mb-3">
                            <div class="avatar avatar-circle mr-3">
                                <img src="{{ asset('storage/app/public/profile') }}/{{ $user->image }}" 
                                     onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'" 
                                     alt="Image">
                            </div>
                            <div class="media-body">
                                <h5 class="text-hover-primary mb-0">{{ $user->f_name }} {{ $user->l_name }}</h5>
                                <span class="text-body font-size-sm">{{ $user->email }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-body font-weight-bold">{{ translate('messages.contact') }}:</span>
                            <span>{{ $user->phone }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-body font-weight-bold">{{ translate('messages.wallet_balance') }}:</span>
                            <span>{{ \App\CentralLogics\Helpers::format_currency($user->wallet_balance) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Cart Item Modal -->
    <div class="modal fade" id="editCartModal" tabindex="-1" role="dialog" aria-labelledby="editCartModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCartModalLabel">{{ translate('messages.edit_cart_item') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editCartForm" action="{{ route('vendor.cart-manager.update-item') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="cart_id" id="modal_cart_id">
                        <div class="form-group">
                            <label class="input-label" id="modal_item_name"></label>
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.price') }}</label>
                            <input type="number" name="price" id="modal_item_price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('messages.quantity') }}</label>
                            <input type="number" name="quantity" id="modal_item_quantity" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('messages.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('messages.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).on('click', '.edit-cart-item', function() {
            let cartId = $(this).data('id');
            $.ajax({
                url: '{{ route('vendor.cart-manager.item-details') }}',
                method: 'GET',
                data: { id: cartId },
                success: function(response) {
                    if (response.success) {
                        $('#modal_cart_id').val(response.cart.id);
                        $('#modal_item_name').text(response.item.name);
                        $('#modal_item_price').val(response.cart.price);
                        $('#modal_item_quantity').val(response.cart.quantity);
                        $('#editCartModal').modal('show');
                    }
                },
                error: function() {
                    toastr.error('{{ translate('messages.failed_to_fetch_details') }}');
                }
            });
        });

        $('#add_item_id').on('change', function() {
            let price = $(this).find(':selected').data('price');
            $('#add_item_price').val(price);
        });
    </script>
@endpush
