@extends('layouts.vendor.app')

@section('title', translate('messages.Order Details'))


@section('content')
    <?php

    $tax_included =0;
    $campaign_order = false;
    foreach ($order->details as $details) {
        if ($details->item_campaign_id) {
            $campaign_order = true;
            break;
        }
    }
    $max_processing_time = explode('-', $order['store']['delivery_time'])[0];
    $editing = $editing??false;
    ?>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('/public/assets/admin/img/shopping-basket.png') }}" class="w--20"
                                alt="">
                        </span>
                        <span>
                            {{ translate('order_details') }} <span
                                class="badge badge-soft-dark rounded-circle ml-1">{{ $order->details->count() }}</span>
                        </span>
                    </h1>
                </div>

                <div class="col-sm-auto">
                    <a class="btn btn-icon btn-sm btn-soft-secondary rounded-circle mr-1"
                        href="{{ route('vendor.order.details', [$order['id'] - 1]) }}" data-toggle="tooltip"
                        data-placement="top" title="Previous order">
                        <i class="tio-chevron-left"></i>
                    </a>
                    <a class="btn btn-icon btn-sm btn-soft-secondary rounded-circle"
                        href="{{ route('vendor.order.details', [$order['id'] + 1]) }}" data-toggle="tooltip"
                        data-placement="top" title="Next order">
                        <i class="tio-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row" id="printableArea">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <!-- Card -->
                <div class="card mb-3 mb-lg-5">
                    <!-- Header -->
                    <div class="card-header border-0 align-items-start flex-wrap">
                        <div class="order-invoice-left d-flex d-sm-flex justify-content-between">
                            <div>
                                <h1 class="page-header-title">
                                    {{ translate('messages.order') }} #{{ $order['id'] }}
                                    @if ($order->edited)
                                        <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.edited') }}
                                        </span>
                                    @endif
                                </h1>
                                <span class="mt-2 d-block">
                                    <i class="tio-date-range"></i>
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order['created_at'])) }}
                                </span>
                                @if ($order->schedule_at && $order->scheduled)
                                    <h6 class="text-capitalize">
                                        {{ translate('messages.scheduled_at') }}
                                        : <label
                                            class="fz--10 badge badge-soft-warning">{{ date('d M Y ' . config('timeformat'), strtotime($order['schedule_at'])) }}</label>
                                    </h6>
                                @endif
                                @if($order['cancellation_reason'])
                                <h6>
                                    <span class="text-danger">{{ translate('messages.order_cancellation_reason') }} :</span>
                                    {{ $order['cancellation_reason'] }}
                                </h6>
                                @endif

                           
                                <!-- New Note -->
                                @if ($order['bring_change_amount'] > 0)
                                <div class="info-notes-bg px-3 color-222324CC py-2 rounded fs-12  gap-2 mt-2">
                                    {{ translate('Please_bring') }} <strong class="text-title"> {{  \App\CentralLogics\Helpers::format_currency($order['bring_change_amount']) }}</strong> {{ translate('in_change_when_making_the_delivery') }}.
                                </div>
                                @endif
                                <!-- New Note End -->
                                @if ($order['unavailable_item_note'])
                                    <h6 class="w-100 badge-soft-warning p-1 rounded mt-2">
                                        <span class="text-dark">
                                            {{ translate('messages.order_unavailable_item_note') }} :
                                        </span>
                                        {{ $order['unavailable_item_note'] }}
                                    </h6>
                                @endif
                                @if ($order['delivery_instruction'])
                                    <h6 class="w-100 badge-soft-warning p-1 rounded mt-2">
                                        <span class="text-dark">
                                            {{ translate('messages.order_delivery_instruction') }} :
                                        </span>
                                        {{ $order['delivery_instruction'] }}
                                    </h6>
                                @endif
                                @if ($order['order_note'])
                                    <h6>
                                        {{ translate('messages.order_note') }} :
                                        {{ $order['order_note'] }}
                                    </h6>
                                @endif
                            </div>
                            <div class="d-sm-none">
                                <a class="btn btn--primary print--btn font-regular"
                                    href={{ route('vendor.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                            </div>
                        </div>


                        <div class="order-invoice-right mt-3 mt-sm-0">
                            <div class="btn--container ml-auto align-items-center justify-content-end">
                                 @if ( !$editing && in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted']) &&
                                         isset($order->store) && !$campaign_order &&
                                         $order->prescription_order == 0 )
                                    <button class="btn btn-sm btn--danger btn-outline-danger font-regular edit-order" type="button">
                                        <i class="tio-edit"></i> {{ translate('messages.edit') }}
                                    </button>
                                @endif
                                <a class="btn btn--primary print--btn font-regular d-none d-sm-block"
                                    href={{ route('vendor.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                            </div>
                            <div class="text-right mt-3 order-invoice-right-contents text-capitalize">
                                <h6>
                                    {{ translate('messages.payment_status') }} :
                                    @if ($order['payment_status'] == 'paid')
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.paid') }}
                                        </span>
                                        @elseif ($order['payment_status'] == 'partially_paid')

                                        @if ($order->payments()->where('payment_status','unpaid')->exists())
                                        <span class="text-danger">{{ translate('messages.partially_paid') }}</span>
                                        @else
                                        <span class="text-success">{{ translate('messages.paid') }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.unpaid') }}
                                        </span>
                                    @endif
                                </h6>
                                @if ($order->store && $order->store->module->module_type == 'food')
                                <h6>
                                    <span>{{ translate('cutlery') }}</span> <span>:</span>
                                    @if ($order['cutlery'] == '1')
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.yes') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.no') }}
                                        </span>
                                    @endif

                                </h6>
                                @endif
                                <h6 class="text-capitalize">
                                    {{ translate('messages.payment_method') }} :
                                    {{ translate(str_replace('_', ' ', $order['payment_method'])) }}
                                </h6>
                                @if ($order['transaction_reference'])
                                    <h6 class="">
                                        {{ translate('messages.reference_code') }} :
                                        <button class="btn btn-outline-primary btn-sm" data-toggle="modal"
                                            data-target=".bd-example-modal-sm">
                                            {{ translate('messages.add') }}
                                        </button>
                                    </h6>
                                @endif
                                <h6 class="text-capitalize">{{ translate('messages.order_type') }}
                                    : <label
                                        class="fz--10 badge m-0 badge-soft-primary">{{ translate(str_replace('_', ' ', $order['order_type'])) }}</label>
                                </h6>
                                <h6>
                                    {{ translate('messages.order_status') }} :
                                    @if ($order['order_status'] == 'pending')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($order['order_status'] == 'confirmed')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.confirmed') }}
                                        </span>
                                    @elseif($order['order_status'] == 'processing')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.processing') }}
                                        </span>
                                    @elseif($order['order_status'] == 'picked_up')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.out_for_delivery') }}
                                        </span>
                                    @elseif($order['order_status'] == 'delivered')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.delivered') }}
                                        </span>
                                    @elseif($order['order_status'] == 'failed')
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.payment_failed') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ str_replace('_', ' ', $order['order_status']) }}
                                        </span>
                                    @endif
                                </h6>
                                @if ($order->order_attachment)
                                        @php
                                            $order_images = json_decode($order->order_attachment,true);
                                        @endphp
                                    {{-- @if (is_array($order_images)) --}}
                                        <h5 class="text-dark">
                                            {{ translate('messages.prescription') }}:
                                        </h5>
                                        <div class="d-flex flex-wrap flex-md-row-reverse __gap-15px" >
                                            @foreach ($order_images as $key => $item)
                                            @php($item = is_array($item)?$item:['img'=>$item,'storage'=>'public'])
                                                <div>
                                                    <button class="btn w-100 px-0" data-toggle="modal"
                                                        data-target="#prescriptionimagemodal{{ $key }}"
                                                        title="{{ translate('messages.order_attachment') }}">
                                                        <div class="gallary-card ml-auto">
                                                            <img  src="{{\App\CentralLogics\Helpers::get_full_url('order',$item['img'],$item['storage']) }}"
                                                                alt="{{ translate('messages.prescription') }}"
                                                                class="initial--22 object-cover">
                                                        </div>
                                                    </button>
                                                </div>
                                                <div class="modal fade" id="prescriptionimagemodal{{ $key }}" tabindex="-1"
                                                    role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h4 class="modal-title" id="myModalLabel">
                                                                    {{ translate('messages.prescription') }}</h4>
                                                                <button type="button" class="close"
                                                                    data-dismiss="modal"><span
                                                                        aria-hidden="true">&times;</span><span
                                                                        class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <img src="{{\App\CentralLogics\Helpers::get_full_url('order',$item['img'],$item['storage']) }}"
                                                                    class="initial--22 w-100" alt="image">
                                                            </div>
                                                            @php($storage = $item['storage']??'public')
                                                            @php($file = $storage == 's3'?base64_encode('order/' . $item['img']):base64_encode('public/order/' . $item['img']))
                                                            <div class="modal-footer">
                                                                <a class="btn btn-primary"
                                                                    href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                        class="tio-download"></i>
                                                                    {{ translate('messages.download') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body px-0">
                        <!-- item cart -->
                        @if ($editing && !$campaign_order)
                            <hr>
                            <div class="row  px-4 py-5">
                                <div class="col-12">
                                    <div class="row justify-content-end">
                                        <div class="col-sm-6">
                                            <form id="search-form">
                                                <!-- Search -->
                                                <div class="input-group input--group">
                                                    <input id="datatableSearch" type="search"
                                                           value="{{ $keyword ? $keyword : '' }}" name="search"
                                                           class="form-control h--45px" placeholder="Search here"
                                                           aria-label="Search here">
                                                    <button class="btn btn--secondary h--45px"><i
                                                            class="tio-search"></i></button>
                                                </div>
                                                <!-- End Search -->
                                            </form>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="input-group header-item w-100">
                                                <select name="category" id="category"
                                                        class="form-control js-select2-custom mx-1 set-category-filter"
                                                        title="{{ translate('messages.select_category') }}">
                                                    <option value="">{{ translate('messages.all_categories') }}
                                                    </option>
                                                    @foreach ($categories as $item)
                                                        <option value="{{ $item->id }}"
                                                            {{ $category == $item->id ? 'selected' : '' }}>
                                                            {{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-5" id="items">
                                    <div class="row g-3 mb-auto justify-content-center">
                                        @foreach ($products as $product)
                                            <div class="order--item-box item-box">
                                                @include('vendor-views.order.partials._single_product', [
                                                    'product' => $product,
                                                    'store_data' => $order->store,
                                                ])
                                                {{-- <hr class="d-sm-none"> --}}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12 text-center mt-3">
                                    {!! $products->withQueryString()->links() !!}
                                </div>
                            </div>
                        @endif

                        <?php
                        $coupon = null;
                        $total_addon_price = 0;
                        $product_price = 0;
                        if ($order->prescription_order == 1) {
                            $product_price = $order['order_amount'] - $order['delivery_charge'] - $order['total_tax_amount'] - $order['dm_tips'] - $order['additional_charge'] + $order['store_discount_amount'];
                            if($order->tax_status == 'included'){
                                $product_price += $order['total_tax_amount'];
                            }
                        }
                        $store_discount_amount = 0;
                        $admin_flash_discount_amount = $order['flash_admin_discount_amount'];
                        $ref_bonus_amount = $order['ref_bonus_amount'];
                        $extra_packaging_amount = $order['extra_packaging_amount'];
                        $store_flash_discount_amount = $order['flash_store_discount_amount'];

                        $del_c = $order['delivery_charge'];
                        if ($editing) {
                            $del_c = $order['original_delivery_charge'];
                        }
                        if ($order->coupon_code) {
                            $coupon = \App\Models\Coupon::where(['code' => $order['coupon_code']])->first();
                            if ($editing && $coupon && $coupon->coupon_type == 'free_delivery') {
                                $del_c = 0;
                                $coupon = null;
                            }
                        }

                        $details = $order->details;
                        if ($editing) {
                            $details = session('order_cart');
                        } else {
                            foreach ($details as $key => $item) {
                                $details[$key]->status = true;
                            }
                        }
                        ?>
                        <div class="table-responsive">
                            <table
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('messages.#') }}</th>
                                        <th class="border-0">{{ translate('messages.item_details') }}</th>
                                        @if ($order->store->module->module_type == 'food')
                                            <th class="border-0">{{ translate('messages.addons') }}</th>
                                        @endif
                                        <th class="text-right  border-0">{{ translate('messages.price') }}</th>
                                        @if ($editing)
                                            <th class="border-0">{{ translate('messages.action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($details as $key => $detail)
                                        @if (isset($detail->item_id) && $detail->status)
                                            <?php
                                            if (!$editing) {
                                                $detail->item = json_decode($detail->item_details, true);
                                            }
                                            $product = \App\Models\Item::where(['id' => data_get($detail->item,'id') ?? $detail->item_id])->first();
                                            if (!$detail->item) {
                                                $detail->item = $product ? \App\CentralLogics\Helpers::product_data_formatting($product, false) : null;
                                            }
                                            ?>
                                            <!-- Media -->
                                            <tr>
                                                <td>
                                                    <div>
                                                        {{ $key + 1 }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                        @if ($editing)
                                                            <div class="avatar avatar-lg mr-3 cursor-pointer quick-view-cart-item" data-key="{{ $key }}"
                                                                 title="{{ translate('messages.click_to_edit_this_item') }}">
                                                                    <span
                                                                        class="avatar-status avatar-lg-status avatar-status-dark"><i
                                                                            class="tio-edit"></i></span>
                                                                <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                                     src="{{ $product?->image_full_url ??asset('public/assets/admin/img/100x100/2.png') }}"
                                                                     data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}"
                                                                     alt="Image Description">
                                                            </div>
                                                        @else
                                                            <a class="avatar avatar-xl mr-3"
                                                                href="{{ $detail->item ? route('vendor.item.view', $detail->item['id']) : '#' }}">
                                                                <img class="img-fluid rounded onerror-image"
                                                                src="{{ $product?->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                     data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                    alt="Image Description">
                                                            </a>
                                                        @endif
                                                        <div class="media-body">
                                                            <div>
                                                                <strong
                                                                    class="line--limit-1">{{ Str::limit($detail->item['name'] ?? 'Unknown Item', 25, '...') }}</strong>
                                                                <h6>
                                                                    {{ $detail['quantity'] }} x
                                                                    {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                </h6>
                                                                @if ($order->store && $order->store->module->module_type == 'food')
                                                                    @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                        @foreach (json_decode($detail['variation'], true) as $variation)
                                                                            @if (isset($variation['name']) && isset($variation['values']))
                                                                                <span class="d-block text-capitalize">
                                                                                    <strong>
                                                                                        {{ $variation['name'] }} -
                                                                                    </strong>
                                                                                </span>
                                                                                @foreach ($variation['values'] as $value)
                                                                                    @if (is_array($value) && isset($value['label']))
                                                                                    <span class="d-block text-capitalize">
                                                                                        &nbsp; &nbsp;
                                                                                        {{ $value['label'] }} :
                                                                                        <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice'] ?? 0) }}</strong>
                                                                                    </span>
                                                                                    @endif
                                                                                @endforeach
                                                                            @else
                                                                                @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                    <strong><u>
                                                                                            {{ translate('messages.Variation') }}
                                                                                            : </u></strong>
                                                                                    @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                        <div
                                                                                            class="font-size-sm text-body">
                                                                                            <span>{{ $key1 }}
                                                                                                : </span>
                                                                                            <span
                                                                                                class="font-weight-bold">{{ $variation }}</span>
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endif
                                                                                {{-- @break --}}
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @else
                                                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                                                        <strong><u>{{ translate('messages.variation') }}
                                                                                :
                                                                            </u></strong>
                                                                    <?php
                                                                        $detailsVariation = isset(json_decode($detail['variation'], true)[0]) ? json_decode($detail['variation'], true)[0] : json_decode($detail['variation'], true);
                                                                    ?>
                                                                        @foreach ($detailsVariation as $key1 => $variation)
                                                                            @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                    <span class="font-weight-bold">
                                                                                        {{ Str::limit(implode(', ', (array) $variation), 15, '...') }}
                                                                                    </span>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($order->store->module->module_type == 'food')
                                                    <td>
                                                        <div>
                                                            @foreach (json_decode($detail['add_ons'], true) ?? [] as $key2 => $addon)
                                                                @if ($key2 == 0)
                                                                    <strong><u>{{ translate('messages.addons') }} :
                                                                        </u></strong>
                                                                @endif
                                                                <div class="font-size-sm text-body">
                                                                    <span>{{ Str::limit($addon['name'], 25, '...') }} :
                                                                    </span>
                                                                    <span class="font-weight-bold">
                                                                        {{ $addon['quantity'] }} x
                                                                        {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                    </span>
                                                                </div>
                                                                @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @endif
                                                <td>
                                                    <div class="text-right">
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}</h5>
                                                    </div>
                                                </td>
                                                @if ($editing)
                                                    <td>
                                                        <div class="btn--container justify-content-center">
                                                            <a class="btn action-btn btn--danger btn-outline-danger removeFromCart" href="javascript:"
                                                                data-key="{{ $key }}"
                                                                title="{{ translate('messages.remove_from_cart') }}">
                                                                <i class="tio-delete-outlined"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                            @php($product_price += $amount)
                                            @php($store_discount_amount += $detail['discount_on_item']  * ( $detail['discount_on_product_by'] == 'store_discount' ? 1 :$detail['quantity']  ))
                                            <!-- End Media -->
                                        @elseif(isset($detail->item_campaign_id) && $detail->status)
                                            <?php
                                            if (!$editing) {
                                                $detail->campaign = json_decode($detail->item_details, true);
                                            }
                                            $campaign = \App\Models\ItemCampaign::where(['id' => $detail->campaign['id']])->first();
                                            ?>
                                            <!-- Media -->
                                            <tr>
                                                <td>
                                                    <div>
                                                        {{ $key + 1 }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                        @if ($editing)
                                                            <div class="avatar avatar-xl mr-3  cursor-pointer quick-view-cart-item" data-key="{{ $key }}"
                                                                 title="{{ translate('messages.click_to_edit_this_item') }}">
                                                                    <span
                                                                        class="avatar-status avatar-lg-status avatar-status-dark"><i
                                                                            class="tio-edit"></i></span>
                                                                    <img class="img-fluid rounded onerror-image"
                                                                        src="{{ $campaign?->image_full_url ?? asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                        alt="Image Description">
                                                                </div>
                                                        @else
                                                            <div class="avatar avatar-xl mr-3">
                                                                <img class="img-fluid onerror-image"
                                                                src="{{$campaign?->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"

                                                                     data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                    alt="Image Description">
                                                            </div>
                                                        @endif
                                                        <div class="media-body">
                                                            <div>
                                                                <strong
                                                                    class="line--limit-1">{{ Str::limit($detail->campaign['name'], 25, '...') }}</strong>

                                                                <h6>
                                                                    {{ $detail['quantity'] }} x
                                                                    {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                </h6>

                                                                @if (count(json_decode($detail['variation'], true)) > 0)
                                                                    <strong><u>{{ translate('messages.variation') }} :
                                                                        </u></strong>
                                                                    @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                        @if ($key1 != 'stock')
                                                                            <div class="font-size-sm text-body">
                                                                                <span>{{ $key1 }} : </span>
                                                                                <span
                                                                                    class="font-weight-bold">{{ Str::limit($variation, 25, '...') }}</span>
                                                                            </div>
                                                                        @endif
                                                                    @endforeach
                                                                @endif

                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($order->store->module->module_type == 'food')
                                                    <td>
                                                        @foreach (json_decode($detail['add_ons'], true) ?? [] as $key2 => $addon)
                                                            @if ($key2 == 0)
                                                                <strong><u>{{ translate('messages.addons') }} :
                                                                    </u></strong>
                                                            @endif
                                                            <div class="font-size-sm text-body">
                                                                <span>{{ Str::limit($addon['name'], 20, '...') }} : </span>
                                                                <span class="font-weight-bold">
                                                                    {{ $addon['quantity'] }} x
                                                                    {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                </span>
                                                            </div>
                                                            @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                        @endforeach
                                                    </td>
                                                @endif
                                                <td>
                                                    <div class="text-right">
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}</h5>
                                                    </div>
                                                </td>
                                                @if ($editing)
                                                    <td>
                                                        <div class="btn--container justify-content-center">
                                                            <a class="btn action-btn btn--danger btn-outline-danger removeFromCart" href="javascript:"
                                                                data-key="{{ $key }}"
                                                                title="{{ translate('messages.remove_from_cart') }}">
                                                                <i class="tio-delete-outlined"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                            @php($product_price += $amount)
                                            @php($store_discount_amount += $detail['discount_on_item'] *  ( $detail['discount_on_product_by'] == 'store_discount' ?  1:$detail['quantity'] ))
                                            <!-- End Media -->
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mx-3">
                            <hr>
                        </div>
                        <?php
                        $coupon_discount_amount = $order['coupon_discount_amount'];
                        $old_store_discount_amount =0;
                        $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount - $admin_flash_discount_amount - $ref_bonus_amount - $store_flash_discount_amount - $extra_packaging_amount;

                        $total_tax_amount = $order['total_tax_amount'];
                        if($order->tax_status == 'included'){
                                $total_tax_amount=0;
                            }

                        if ($editing) {
                            $store_discount = \App\CentralLogics\Helpers::get_store_discount($order->store);
                            if (isset($store_discount)) {
                                if ($product_price + $total_addon_price < $store_discount['min_purchase']) {
                                    $store_discount_amount = 0;
                                }

                                if ($store_discount_amount > $store_discount['max_discount'] && $store_discount_amount > $store_discount['max_discount']) {
                                    $old_store_discount_amount = $store_discount_amount;
                                    $store_discount_amount = $store_discount['max_discount'];
                                }
                                $store_discount_amount=  max($store_discount_amount,$old_store_discount_amount);
                            }

                            $coupon_discount_amount = $coupon ? \App\CentralLogics\CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount ) : $order['coupon_discount_amount'];

                            $tax_amount = session()->get('edit_tax_amount');
                            $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount;

                            $total_tax_amount = $tax_amount;

                            $total_tax_amount = round($total_tax_amount, 2);

                            $tax_included = session()->get('edit_tax_included');
                            if ($tax_included ==  1){
                                $total_tax_amount=0;
                            }

                            $store_discount_amount = round($store_discount_amount, 2);

                            if ($order?->store?->free_delivery) {
                                $del_c = 0;
                            }
                        } else {
                            $store_discount_amount = $order['store_discount_amount'];
                        }
                        ?>
                        <div class="row justify-content-md-end mb-3 mx-0 mt-4">
                            <div class="col-md-9 col-lg-8">
                                <dl class="row text-right">
                                    <dt class="col-6">{{ translate('messages.items_price') }}:</dt>
                                    <dd class="col-6">{{ \App\CentralLogics\Helpers::format_currency($product_price) }}
                                    </dd>
                                    @if ($order->store->module->module_type == 'food')
                                        <dt class="col-6">{{ translate('messages.addon_cost') }}:</dt>

                                        <dd class="col-6">
                                            {{ \App\CentralLogics\Helpers::format_currency($total_addon_price) }}
                                            <hr>
                                        </dd>
                                    @endif

                                    <dt class="col-6">{{ translate('messages.subtotal') }}
                                        @if ($order->tax_status == 'included' ||  $tax_included ==  1)
                                        ({{ translate('messages.TAX_Included') }})
                                        @endif
                                        :</dt>

                                    <dd class="col-6">
                                         @if (in_array($order['order_status'],['pending','confirmed','processing','accepted']))
                                             <button class="btn btn-sm" type="button" data-toggle="modal"
                                                 data-target="#edit-order-amount"><i class="tio-edit"></i></button>
                                         @endif
                                        {{ \App\CentralLogics\Helpers::format_currency($product_price + $total_addon_price) }}
                                    </dd>
                                    <dt class="col-6">{{ translate('messages.discount') }}:</dt>
                                    <dd class="col-6">
                                         @if (in_array($order['order_status'],['pending','confirmed','processing','accepted']))
                                             <button class="btn btn-sm" type="button" data-toggle="modal"
                                                 data-target="#edit-discount-amount"><i class="tio-edit"></i></button>
                                         @endif
                                        - {{ \App\CentralLogics\Helpers::format_currency($store_discount_amount + $admin_flash_discount_amount  +$store_flash_discount_amount) }}
                                    </dd>



                                    <dt class="col-6">{{ translate('messages.coupon_discount') }}:</dt>
                                    <dd class="col-6">
                                        - {{ \App\CentralLogics\Helpers::format_currency($coupon_discount_amount) }}</dd>

                                    @if ($ref_bonus_amount > 0)
                                    <dt class="col-6">{{ translate('messages.Referral_Discount') }}:</dt>
                                    <dd class="col-6">
                                        - {{ \App\CentralLogics\Helpers::format_currency($ref_bonus_amount) }}</dd>

                                    @endif

                                    @if ($order->tax_status == 'excluded' || $order->tax_status == null  )
                                    <dt class="col-sm-6">{{ translate('messages.vat/tax') }}:</dt>
                                    <dd class="col-sm-6">
                                        +
                                        {{ \App\CentralLogics\Helpers::format_currency($total_tax_amount) }}
                                    </dd>
                                    @endif
                                    <dt class="col-6">{{ translate('messages.delivery_man_tips') }}</dt>
                                    <dd class="col-6">
                                        + {{ \App\CentralLogics\Helpers::format_currency($order->dm_tips) }}</dd>
                                    <dt class="col-6">{{ translate('messages.delivery_fee') }}:</dt>
                                    <dd class="col-6">
                                        @php($del_c = $order['delivery_charge'])
                                        + {{ \App\CentralLogics\Helpers::format_currency($del_c) }}
                                        <hr>
                                    </dd>
                                    <dt class="col-6">{{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}:</dt>
                                    <dd class="col-6">
                                        @php($additional_charge = $order['additional_charge'])
                                        + {{ \App\CentralLogics\Helpers::format_currency($additional_charge) }}
                                    </dd>
                                    @if ($extra_packaging_amount > 0)
                                    <dt class="col-6">{{ translate('messages.Extra_Packaging_Amount') }}:</dt>
                                    <dd class="col-6">
                                        + {{ \App\CentralLogics\Helpers::format_currency($extra_packaging_amount) }}</dd>
                                    @endif
                                    @if ($order['partially_paid_amount'] > 0)

                                    <dt class="col-6">{{ translate('messages.partially_paid_amount') }}:</dt>
                                    <dd class="col-6">
                                        @php($partially_paid_amount = $order['partially_paid_amount'])
                                            {{ \App\CentralLogics\Helpers::format_currency($partially_paid_amount) }}
                                    </dd>
                                    <dt class="col-6">{{ translate('messages.due_amount') }}:</dt>
                                    @if ($order['payment_method'] == 'partial_payment')

                                    <dd class="col-6">
                                            {{ \App\CentralLogics\Helpers::format_currency($order->order_amount-$partially_paid_amount) }}
                                    </dd>
                                    @else
                                    <dd class="col-6">
                                            {{ \App\CentralLogics\Helpers::format_currency(0) }}
                                    </dd>
                                    @endif
                                    @endif

                                    <dt class="col-6">{{ translate('messages.total') }}:</dt>
                                    <dd class="col-6">
                                        {{ \App\CentralLogics\Helpers::format_currency($product_price + $del_c + $total_tax_amount + $total_addon_price + $additional_charge - $coupon_discount_amount - $store_discount_amount - $admin_flash_discount_amount  - $ref_bonus_amount + $extra_packaging_amount-$store_flash_discount_amount + $order->dm_tips) }}
                                    </dd>

                                    @php(
                                        $wallet_adjust_amount = \App\Models\WalletTransaction::where('user_id', $order->user_id)
                                            ->where('reference', 'like', '%order ID: ' . $order->id)
                                            ->where('transaction_type', 'order_place')
                                            ->sum('debit')
                                    )
                                    @php(
                                        $wallet_refund_amount = \App\Models\WalletTransaction::where('user_id', $order->user_id)
                                            ->where('reference', 'like', '%order ID: ' . $order->id)
                                            ->where('transaction_type', 'order_refund')
                                            ->sum('credit')
                                    )

                                    @if($wallet_adjust_amount > 0)
                                    <dt class="col-6 text-danger">{{ translate('messages.adjust_amount') }}:</dt>
                                    <dd class="col-6 text-danger">
                                        - {{ \App\CentralLogics\Helpers::format_currency($wallet_adjust_amount) }}
                                    </dd>
                                    @endif

                                    @if($wallet_refund_amount > 0)
                                    <dt class="col-6 text-success">{{ translate('messages.refund') }}:</dt>
                                    <dd class="col-6 text-success">
                                        + {{ \App\CentralLogics\Helpers::format_currency($wallet_refund_amount) }}
                                    </dd>
                                    @endif
                                    @if ($editing)
                                        <div class="col-12 mt-3">
                                            <div class="btn--container justify-content-end">
                                                <button class="btn btn-sm btn--reset cancel-edit-order" type="button">
                                                    <i class="tio-clear"></i> {{ translate('messages.cancel') }}
                                                </button>
                                                <button class="btn btn-sm btn--primary submit-edit-order" type="button">
                                                    <i class="tio-save"></i> {{ translate('messages.submit') }}
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($order?->payments)
                                        @foreach ($order?->payments as $payment)
                                            @if ($payment->payment_status == 'paid')
                                                @if ( $payment->payment_method == 'cash_on_delivery')

                                                <dt class="col-sm-6">{{ translate('messages.Paid_with_Cash') }} ({{  translate('COD')}}) :</dt>
                                                @else

                                                <dt class="col-sm-6">{{ translate('messages.Paid_by') }} {{  translate($payment->payment_method)}} :</dt>
                                                @endif
                                            @else

                                            <dt class="col-sm-6">{{ translate('Due_Amount') }} ({{  $payment->payment_method == 'cash_on_delivery' ?  translate('messages.COD') : translate($payment->payment_method) }}) :</dt>
                                            @endif
                                        <dd class="col-sm-6">
                                            {{ \App\CentralLogics\Helpers::format_currency($payment->amount) }}
                                        </dd>
                                        @endforeach
                                    @endif
                                </dl>
                                <!-- End Row -->
                            </div>
                        </div>
                        <!-- End Row -->
                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4">
                <!-- Card -->
                @if ($order->order_status != 'refund_requested' &&
                    $order->order_status != 'refunded' &&
                    $order->order_status != 'delivered')
                    <div class="card mb-2">
                        <!-- Header -->
                        <div class="card-header justify-content-center text-center px-0 mx-4">
                            <h5 class="card-header-title text-capitalize">
                                <span>{{ translate('messages.order_setup') }}</span>
                            </h5>
                        </div>
                        <!-- End Header -->

                        <!-- Body -->

                        <div class="card-body">
                            @if($order->is_unpaid_order)
                                <div class="text-center bg-light2 rounded p-xxl-20 p-3 mb-3">
                                    <h4 class="text-danger fs-14px fw-medium mb-2">{{ translate('messages.Payment_failed!') }}</h4>
                                    <p class="fs-12 text-dark mb-0">{{ translate('messages.the customer\'s payment couldn\'t be processed.') }}</p>
                                </div>
                            @endif
                            <!-- Order Status Flow Starts -->
                            @php($order_delivery_verification = (bool) \App\Models\BusinessSetting::where(['key' => 'order_delivery_verification'])->first()->value)
                            <div class="mb-4">
                                <div class="row g-1">
                                    <div class="{{ config('canceled_by_store') ? 'col-6' : 'col-12' }}">
                                        <a class="btn btn--primary w-100 fz--13 px-2 {{ $order['order_status'] == 'pending' ? '' : 'd-none' }} route-alert"
                                           data-url="{{ route('vendor.order.status', ['id' => $order['id'], 'order_status' => 'confirmed']) }}"
                                           data-message="{{ translate('messages.confirm_this_order_?') }}"
                                            href="javascript:">{{ translate('messages.confirm_this_order') }}</a>
                                    </div>
                                    @if (config('canceled_by_store'))
                                        <div class="col-6">
                                            <a class="btn btn--danger w-100 fz--13 px-2 cancelled-status {{ $order['order_status'] == 'pending' ? '' : 'd-none' }}"
                                               >{{ translate('Cancel Order') }}</a>
                                        </div>
                                    @endif
                                </div>
                                    @if ($order->store && $order->store->module->module_type == 'food')
                                        <a class="btn btn--primary w-100 order-status-change-alert {{ $order['order_status'] == 'confirmed' || $order['order_status'] == 'accepted' ? '' : 'd-none' }}"

                                           data-url="{{ route('vendor.order.status', ['id' => $order['id'], 'order_status' => 'processing']) }}"
                                           data-message="{{ translate('Change status to cooking ?') }}"
                                           data-verification="false"
                                           data-processing-time="{{ $max_processing_time }}"
                                           href="javascript:">{{ translate('messages.proceed_for_processing') }}</a>
                                    @else
                                    <a class="btn btn--primary w-100 route-alert  {{ $order['order_status'] == 'confirmed' || $order['order_status'] == 'accepted' ? '' : 'd-none' }}"
                                       data-url="{{ route('vendor.order.status', ['id' => $order['id'], 'order_status' => 'processing']) }}"
                                       data-message="{{ translate('messages.proceed_for_processing') }}"
                                    href="javascript:">{{ translate('messages.proceed_for_processing') }}</a>
                                    @endif
                                <a class="btn btn--primary w-100 route-alert {{ $order['order_status'] == 'processing' ? '' : 'd-none' }}"
                                   data-url="{{ route('vendor.order.status', ['id' => $order['id'], 'order_status' => 'handover']) }}"
                                   data-message="{{ translate('messages.make_ready_for_handover') }}"
                                    href="javascript:">{{ translate('messages.make_ready_for_handover') }}</a>
                                 @if($order['order_status'] == 'handover'|| ($order['order_status'] == 'picked_up' && $order->store->sub_self_delivery == 1))
                                    <a class="btn  w-100
                                    {{ ($order['order_type'] == 'take_away' || $order->store->sub_self_delivery == 1)  ?  'btn--primary order-status-change-alert'  :  'btn--secondary  self-delivery-warning' }} "
                                       data-url="{{ route('vendor.order.status', ['id' => $order['id'], 'order_status' => 'delivered']) }}"
                                       data-message="{{ translate('messages.Change status to delivered (payment status will be paid if not)?') }}"
                                       data-verification="{{ $order_delivery_verification ? 'true' : 'false' }}"
                                        href="javascript:">{{ translate('messages.make_delivered') }}</a>
                                 @endif

                            </div>
                        </div>

                        <!-- End Body -->
                    </div>
                @endif
                <!-- End Card -->
                @if ($order->order_status == 'canceled')
                <ul class="delivery--information-single mt-3">
                    <li>
                        <span class=" badge badge-soft-danger "> {{ translate('messages.Cancel_Reason') }} :</span>
                        <span class="info">  {{ $order->cancellation_reason }} </span>
                    </li>

                    <li>
                        <span class="name">{{ translate('Cancel_Note') }} </span>
                        <span class="info">  {{ $order->cancellation_note ?? translate('messages.N/A')}} </span>
                    </li>
                    <li>
                        <span class="name">{{ translate('Canceled_By') }} </span>
                        <span class="info">  {{ translate($order->canceled_by) }} </span>
                    </li>
                    @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid' )
                            @if ( $order?->payments)
                                @php( $pay_infos =$order->payments()->where('payment_status','paid')->get())
                                @foreach ($pay_infos as $pay_info)
                                    <li>
                                        <span class="name">{{ translate('Amount_paid_by') }} {{ translate($pay_info->payment_method) }} </span>
                                        <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($pay_info->amount)  }} </span>
                                    </li>
                                @endforeach
                            @else
                            <li>
                                <span class="name">{{ translate('Amount_paid_by') }} {{ translate($order->payment_method) }} </span>
                                <span class="info ">  {{ \App\CentralLogics\Helpers::format_currency($order->order_amount)  }} </span>
                            </li>
                            @endif
                    @endif

                    @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid')
                        @if ( $order?->payments)
                            @php( $amount =$order->payments()->where('payment_status','paid')->sum('amount'))
                                <li>
                                    <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                                    <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($amount)  }} </span>
                                </li>
                        @else
                        <li>
                            <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                            <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($order->order_amount)  }} </span>
                        </li>
                        @endif
                    @endif
                </ul>
                <hr class="w-100">
            @endif
                @if ($order['order_type'] != 'take_away')
                    <!-- Card -->
                    <div class="card mb-2">
                        <!-- Header -->
                        <div class="card-header">
                            <h4 class="card-header-title">
                                <span class="card-header-icon"><i class="tio-user"></i></span>
                                <span>{{ translate('messages.Delivery Man') }}</span>
                            </h4>
                        </div>
                        <!-- End Header -->

                        <!-- Body -->
                        <div class="card-body">
                            @if ($order->delivery_man)
                                <div class="media align-items-center customer--information-single" href="javascript:">
                                    <div class="avatar avatar-circle">
                                        <img class="avatar-img onerror-image"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                             src="{{ $order->delivery_man->image_full_url }}"
                                            alt="Image Description">
                                    </div>
                                    <div class="media-body">
                                        <span
                                            class="text-body d-block text-hover-primary mb-1">{{ $order->delivery_man['f_name'] . ' ' . $order->delivery_man['l_name'] }}</span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-shopping-basket-outlined mr-2"></i>
                                            {{ $order->delivery_man->orders_count }}
                                            {{ translate('messages.orders_delivered') }}
                                        </span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-call-talking-quiet mr-2"></i>
                                            {{ $order->delivery_man['phone'] }}
                                        </span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-email-outlined mr-2"></i>
                                            {{ $order->delivery_man['email'] }}
                                        </span>
                                    </div>
                                </div>

                                @if ($order['order_type'] != 'take_away')
                                    <hr>
                                    @php($address = $order->dm_last_location)
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>{{ translate('messages.last_location') }}</h5>
                                    </div>
                                    @if (isset($address))
                                        <span class="d-block">
                                            <a target="_blank"
                                                href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                                <i class="tio-map"></i> {{ $address['location'] }}<br>
                                            </a>
                                        </span>
                                    @else
                                        <span class="d-block text-lowercase qcont">
                                            {{ translate('messages.location_not_found') }}
                                        </span>
                                    @endif
                                @endif
                            @else
                                <span class="badge badge-soft-danger py-2 d-block qcont">
                                    {{ translate('messages.deliveryman_not_found') }}
                                </span>
                            @endif
                        </div>
                        <!-- End Body -->
                    </div>
                @endif
                <!-- End Card -->

                <!-- order proof -->
                <div class="card mb-2 mt-2">
                    <div class="card-header border-0 text-center pb-0">
                        <h4 class="m-0">{{ translate('messages.delivery_proof') }} </h4>
                        @if ($order['store']['sub_self_delivery'])

                        <button class="btn btn-outline-primary btn-sm" data-toggle="modal"
                                            data-target=".order-proof-modal">
                                            {{ translate('messages.add') }}
                                        </button>
                        @endif
                    </div>
                    @php($data = isset($order->order_proof) ? json_decode($order->order_proof, true) : 0)
                    <div class="card-body pt-2">
                        @if ($data)
                        <label class="input-label"
                            for="order_proof">{{ translate('messages.image') }} : </label>
                        <div class="row g-3">
                                @foreach ($data as $key => $img)
                                @php($img = is_array($img)?$img:['img'=>$img,'storage'=>'public'])
                                    <div class="col-3">
                                        <img class="img__aspect-1 rounded border w-100 onerror-image" data-toggle="modal"
                                            data-target="#imagemodal{{ $key }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             src="{{\App\CentralLogics\Helpers::get_full_url('order',$img['img'],$img['storage']) }}"
                                             alt="image">
                                    </div>
                                    <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1"
                                        role="dialog" aria-labelledby="order_proof_{{ $key }}"
                                        aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title"
                                                        id="order_proof_{{ $key }}">
                                                        {{ translate('order_proof_image') }}</h4>
                                                    <button type="button" class="close"
                                                        data-dismiss="modal"><span
                                                            aria-hidden="true">&times;</span><span
                                                            class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    <img src="{{\App\CentralLogics\Helpers::get_full_url('order',$img['img'],$img['storage']) }}"
                                                        class="initial--22 w-100" alt="img">
                                                </div>
                                                @php($storage = $img['storage']??'public')
                                                @php($file = $storage == 's3'?base64_encode('order/' . $img['img']):base64_encode('public/order/' . $img['img']))
                                                <div class="modal-footer">
                                                    <a class="btn btn-primary"
                                                        href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                            class="tio-download"></i>
                                                        {{ translate('messages.download') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @endif
                    </div>
                </div>

                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    {{-- =====================================================================
                         ADDED: "Edit Manage" button in the Customer card header.
                         Only shows for pending, confirmed, or processing orders.
                         Clicking opens the #edit-manage-modal where vendor marks
                         unavailable items and writes an optional note.
                    ====================================================================== --}}
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-header-title mb-0">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>{{ translate('messages.customer') }}</span>
                        </h4>
                        @if (in_array($order->order_status, ['pending', 'confirmed', 'processing']))
                            <button class="btn btn-sm btn--warning font-regular"
                                    type="button"
                                    data-toggle="modal"
                                    data-target="#edit-manage-modal"
                                    title="{{ translate('messages.mark_unavailable_items') }}">
                                <i class="tio-edit"></i> {{ translate('messages.edit_manage') }}
                            </button>
                        @endif
                    </div>
                    {{-- END ADDED --}}
                    <!-- End Header -->

                    <!-- Body -->
                    @if ($order->customer)
                        <div class="card-body">

                            <div class="media align-items-center customer--information-single" href="javascript:">
                                <div class="avatar avatar-circle">
                                    <img class="avatar-img onerror-image "
                                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                         src="{{ $order->customer->image_full_url }}"
                                        alt="Image Description">
                                </div>
                                <div class="media-body">
                                    <span
                                        class="text-body d-block text-hover-primary mb-1">{{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}</span>

                                    <span class="text--title font-semibold d-flex align-items-center">
                                        <i class="tio-shopping-basket-outlined mr-2"></i>
                                        {{ $order->customer->orders_count }}
                                        {{ translate('messages.orders_delivered') }}
                                    </span>

                                    <span class="text--title font-semibold d-flex align-items-center">
                                        <i class="tio-call-talking-quiet mr-2"></i>
                                        {{ $order->customer['phone'] }}
                                    </span>

                                    <span class="text--title font-semibold d-flex align-items-center">
                                        <i class="tio-email-outlined mr-2"></i>
                                        {{ $order->customer['email'] }}
                                    </span>

                                </div>
                            </div>
                            <hr>




                            @if ($order->delivery_address)
                                @php($address = json_decode($order->delivery_address, true))
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>{{ translate('messages.delivery_info') }}</h5>
                                </div>
                                @if (isset($address))
                                    <span class="delivery--information-single d-block">
                                        <div class="d-flex">
                                            <span class="name">{{ translate('messages.name') }}:</span>
                                            <span class="info">{{ $address['contact_person_name'] }}</span>
                                        </div>
                                        <div class="d-flex">
                                            <span class="name">{{ translate('messages.contact') }}:</span>
                                            <a class="info deco-none"
                                                href="tel:{{ $address['contact_person_number'] }}">
                                                {{ $address['contact_person_number'] }}</a>
                                        </div>
                                        <div class="d-flex">
                                            <span class="name">{{ translate('Floor') }}:</span>
                                            <span
                                                class="info">{{ isset($address['floor']) ? $address['floor'] : '' }}</span>
                                        </div>

                                        <div class="d-flex mb-2">
                                            <span class="name">{{ translate('House') }}:</span>
                                            <span
                                                class="info">{{ isset($address['house']) ? $address['house'] : '' }}</span>
                                        </div>
                                        <div class="d-flex">
                                            <span class="name">{{ translate('Road') }}:</span>
                                            <span
                                                class="info">{{ isset($address['road']) ? $address['road'] : '' }}</span>
                                        </div>
                                        @if ($order['order_type'] != 'take_away' && isset($address['address']))
                                            @if (isset($address['latitude']) && isset($address['longitude']))
                                                <a target="_blank"
                                                    href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                                    <i class="tio-map"></i>{{ $address['address'] }}<br>
                                                </a>
                                            @else
                                                <i class="tio-map"></i>{{ $address['address'] }}<br>
                                            @endif
                                        @endif
                                    </span>
                                @endif
                            @endif
                        </div>

                    @elseif($order->is_guest)
                        <div class="card-body">
                            <span class="badge badge-soft-success py-2 mb-2 d-block qcont">
                                {{ translate('Guest_user') }}
                            </span>
                            @if ($order->delivery_address)
                            @php($address = json_decode($order->delivery_address, true))
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>{{ translate('messages.delivery_info') }}</h5>
                            </div>
                            @if (isset($address))
                                <span class="delivery--information-single d-block">
                                    <div class="d-flex">
                                        <span class="name">{{ translate('messages.name') }}:</span>
                                        <span class="info">{{ $address['contact_person_name'] }}</span>
                                    </div>
                                    <div class="d-flex">
                                        <span class="name">{{ translate('messages.contact') }}:</span>
                                        <a class="info deco-none"
                                            href="tel:{{ $address['contact_person_number'] }}">
                                            {{ $address['contact_person_number'] }}</a>
                                    </div>
                                    <div class="d-flex">
                                        <span class="name">{{ translate('Floor') }}:</span>
                                        <span
                                            class="info">{{ isset($address['floor']) ? $address['floor'] : '' }}</span>
                                    </div>

                                    <div class="d-flex mb-2">
                                        <span class="name">{{ translate('House') }}:</span>
                                        <span
                                            class="info">{{ isset($address['house']) ? $address['house'] : '' }}</span>
                                    </div>

                                    <div class="d-flex">
                                        <span class="name">{{ translate('Road') }}:</span>
                                        <span
                                            class="info">{{ isset($address['road']) ? $address['road'] : '' }}</span>
                                    </div>

                                    @if ($order['order_type'] != 'take_away' && isset($address['address']))
                                    <hr>
                                        @if (isset($address['latitude']) && isset($address['longitude']))
                                            <a target="_blank"
                                                href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                                <i class="tio-map"></i>{{ $address['address'] }}<br>
                                            </a>
                                        @else
                                            <i class="tio-map"></i>{{ $address['address'] }}<br>
                                        @endif
                                    @endif
                                </span>
                            @endif
                        @endif

                        </div>
                    @else
                        <div class="card-body">
                            <span class="badge badge-soft-danger py-2 d-block qcont">
                                {{ translate('Customer Not found!') }}
                            </span>
                        </div>
                    @endif
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>
        </div>
        <!-- End Row -->
    </div>



        <!-- Modal -->
        <div class="modal fade order-proof-modal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{ translate('messages.add_delivery_proof') }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                        aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{ route('vendor.order.add-order-proof', [$order['id']]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <!-- Input Group -->
                        <div class="flex-grow-1 mx-auto">

                            <div class="d-flex flex-wrap __gap-12px __new-coba" id="coba">
                                @php($proof = isset($order->order_proof) ? json_decode($order->order_proof, true) : 0)
                                @if ($proof)

                                @foreach ($proof as $key => $photo)
                                @php($photo = is_array($photo)?$photo:['img'=>$photo,'storage'=>'public'])

                                            <div class="spartan_item_wrapper min-w-176px max-w-176px">
                                                <img class="img--square"
                                                    src="{{\App\CentralLogics\Helpers::get_full_url('order',$photo['img'],$photo['storage']) }}"
                                                    alt="order image">

                                                <div class="pen spartan_remove_row"><i class="tio-edit"></i></div>
                                                <a href="{{ route('vendor.order.remove-proof-image', ['id' => $order['id'], 'name' => $photo]) }}"
                                                    class="spartan_remove_row"><i class="tio-add-to-trash"></i></a>
                                            </div>
                                        @endforeach
                                @endif
                            </div>
                        </div>
                        <!-- End Input Group -->
                        <div class="text-right mt-2">
                            <button class="btn btn--primary">{{ translate('messages.submit') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->

    <div class="modal fade" id="edit-order-amount" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('messages.update_order_amount') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('vendor.order.update-order-amount') }}" method="POST" class="row">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <div class="form-group col-12">
                            <label for="order_amount">{{ translate('messages.order_amount') }}</label>
                            <input id="order_amount" type="number" class="form-control" name="order_amount" min="0"
                                value="{{ round($order['order_amount'] - $order['total_tax_amount']  - $order['additional_charge'] -  $order['delivery_charge'] + $order['store_discount_amount'] - $order['dm_tips'] ,6) }}" step=".01">
                        </div>

                        <div class="form-group col-sm-12">
                            <button class="btn btn-sm btn-primary"
                                type="submit">{{ translate('messages.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="edit-discount-amount" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('messages.update_discount_amount') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('vendor.order.update-discount-amount') }}" method="POST" class="row">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <div class="form-group col-12">
                            <label for="discount_amount">{{ translate('messages.discount_amount') }}</label>
                            <input type="number" id="discount_amount" class="form-control" name="discount_amount" min="0"
                                value="{{ $order['store_discount_amount'] }}" step=".01">
                        </div>

                        <div class="form-group col-sm-12">
                            <button class="btn btn-sm btn-primary"
                                type="submit">{{ translate('messages.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- End Content -->


    <div class="modal fade" id="quick-view" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" id="quick-view-modal">

            </div>
        </div>
    </div>


    {{-- ==========================================================================
         ADDED: Edit Manage Modal
         - Lists all order items with checkboxes to mark as unavailable
         - Optional note textarea for vendor message to customer
         - On submit, POSTs to vendor.order.mark-unavailable route which:
             1. Saves the unavailable item IDs + note on the order
             2. Sends a push notification to the customer
             3. Sets a flag so the customer app shows the Edit ✍️ icon
    =========================================================================== --}}
    @if (in_array($order->order_status, ['pending', 'confirmed', 'processing']))
    <div class="modal fade" id="edit-manage-modal" tabindex="-1" role="dialog" aria-labelledby="editManageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editManageModalLabel">
                        <i class="tio-edit mr-1"></i>
                        {{ translate('messages.edit_manage') }} &mdash; {{ translate('messages.order') }} #{{ $order->id }}
                    </h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal" aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <?php
                    /* Safely decode already-marked unavailable item IDs */
                    $already_unavailable_ids = [];
                    if (!empty($order->unavailable_item_ids)) {
                        $decoded = json_decode($order->unavailable_item_ids, true);
                        $already_unavailable_ids = is_array($decoded) ? $decoded : [];
                    }
                ?>
                <form action="{{ url('vendor-panel/order/' . $order->id . '/mark-unavailable') }}" method="POST" id="edit-manage-form">
                    @csrf
                    <div class="modal-body">

                        {{-- Instruction note --}}
                        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                            <i class="tio-info-outined mr-2"></i>
                            <span>{{ translate('messages.check_items_that_are_unavailable_then_submit_to_notify_customer') }}</span>
                        </div>

                        {{-- Order items list with checkboxes --}}
                        <div class="table-responsive mb-3">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0" style="width:40px;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="select-all-unavailable">
                                                <label class="custom-control-label" for="select-all-unavailable"></label>
                                            </div>
                                        </th>
                                        <th class="border-0">{{ translate('messages.item_details') }}</th>
                                        <th class="border-0 text-right">{{ translate('messages.price') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->details as $emDetail)
                                        <?php
                                            $emItemDetails = json_decode($emDetail->item_details, true);
                                            $emItemName    = isset($emItemDetails['name']) ? $emItemDetails['name'] : '-';
                                            $emItemAmount  = $emDetail->price * $emDetail->quantity;
                                            $emItemId      = $emDetail->id;
                                            $emUnavailable = in_array($emItemId, $already_unavailable_ids);
                                        ?>
                                        <tr class="{{ $emUnavailable ? 'table-danger' : '' }}" id="em-row-{{ $emItemId }}">
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox"
                                                           class="custom-control-input unavailable-item-check"
                                                           id="unavail-{{ $emItemId }}"
                                                           name="unavailable_item_ids[]"
                                                           value="{{ $emItemId }}"
                                                           {{ $emUnavailable ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="unavail-{{ $emItemId }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="media-body">
                                                    <strong class="d-block">{{ Str::limit($emItemName, 40, '...') }}</strong>
                                                    <span class="text-muted">
                                                        {{ $emDetail->quantity }} x
                                                        {{ \App\CentralLogics\Helpers::format_currency($emDetail->price) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                {{ \App\CentralLogics\Helpers::format_currency($emItemAmount) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Vendor note to customer --}}
                        <div class="form-group">
                            <label for="vendor-unavailable-note" class="input-label">
                                <i class="tio-annotation-outlined mr-1"></i>
                                {{ translate('messages.note_to_customer') }}
                                <span class="text-muted">({{ translate('messages.optional') }})</span>
                            </label>
                            <textarea id="vendor-unavailable-note"
                                      name="unavailable_note"
                                      class="form-control"
                                      rows="3"
                                      placeholder="{{ translate('messages.e.g._some_items_are_out_of_stock_please_edit_your_order') }}">{{ $order->unavailable_item_note ?? '' }}</textarea>
                        </div>

                    </div><!-- /.modal-body -->

                    <div class="modal-footer d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="tio-info-outined"></i>
                            {{ translate('messages.customer_will_receive_a_notification_to_edit_their_order') }}
                        </small>
                        <div>
                            <button type="button" class="btn btn-sm btn--reset mr-2" data-dismiss="modal">
                                <i class="tio-clear"></i> {{ translate('messages.cancel') }}
                            </button>
                            <button type="submit" class="btn btn-sm btn--primary" id="edit-manage-submit-btn">
                                <i class="tio-send"></i> {{ translate('messages.submit_and_notify_customer') }}
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    @endif
    {{-- END ADDED: Edit Manage Modal --}}

@endsection
@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script type="text/javascript">
        "use strict";


        $('.self-delivery-warning').on('click',function (event ){
            event.preventDefault();
            toastr.info(
                "{{ translate('messages.Self_Delivery_is_Disable') }}", {
                    CloseButton: true,
                    ProgressBar: true
                });
        });



        $('.cancelled-status').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.Change status to canceled ?') }}',
                type: 'warning',
                html:
                    `   <select class="form-control js-select2-custom mx-1" name="reason" id="reason">
                    @foreach ($reasons as $r)
                    <option value="{{ $r->reason }}">
                            {{ $r->reason }}
                    </option>
                    @endforeach

                    </select>`,
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true,
                onOpen: function () {
                    $('.js-select2-custom').select2({
                        minimumResultsForSearch: 5,
                        width: '100%',
                        placeholder: "Select Reason",
                        language: "en",
                    });
                }
            }).then((result) => {
                if (result.value) {
                    let reason = document.getElementById('reason').value;
                    location.href = '{!! route('vendor.order.status', ['id' => $order['id'],'order_status' => 'canceled']) !!}&reason='+reason,'{{ translate('Change status to canceled ?') }}';
                }
            })

        });

        $('.order-status-change-alert').on('click',function (){
            let route = $(this).data('url');
            let message = $(this).data('message');
            let verification = $(this).data('verification');
            let processing = $(this).data('processing-time') ?? false;

            if (verification) {
                Swal.fire({
                    title: '{{ translate('Enter order verification code') }}',
                    input: 'text',
                    inputAttributes: {
                        autocapitalize: 'off'
                    },
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    confirmButtonText: '{{ translate('messages.submit') }}',
                    showLoaderOnConfirm: true,
                    preConfirm: (otp) => {
                        location.href = route + '&otp=' + otp;
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                })
            } else if (processing) {
                Swal.fire({
                    title: '{{ translate('messages.Are you sure ?') }}',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.Cancel') }}',
                    confirmButtonText: '{{ translate('messages.submit') }}',
                    inputPlaceholder: "{{ translate('Enter processing time') }}",
                    input: 'text',
                    html: message + '<br/>'+'<label>{{ translate('Enter Processing time in minutes') }}</label>',
                    inputValue: processing,
                    preConfirm: (processing_time) => {
                        location.href = route + '&processing_time=' + processing_time;
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                })
            } else {
                Swal.fire({
                    title: '{{ translate('messages.Are you sure ?') }}',
                    text: message,
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.No') }}',
                    confirmButtonText: '{{ translate('messages.Yes') }}',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        location.href = route;
                    }
                })
            }

        });

        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'order_proof[]',
                maxCount: 6-{{ ($order->order_proof && is_array($order->order_proof))?count(json_decode($order->order_proof)):0 }},
                rowHeight: '176px !important',
                groupClassName: 'spartan_item_wrapper min-w-176px max-w-176px',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/upload-img.png') }}",
                    width: '176px'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function() {
                    toastr.error(
                        "{{ translate('messages.please_only_input_png_or_jpg_type_file') }}", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                },
                onSizeErr: function() {
                    toastr.error("{{ translate('messages.file_size_too_big') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });

        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            var keyword = $('#datatableSearch').val();
            var nurl = new URL('{!! url()->full() !!}');
            nurl.searchParams.set('keyword', keyword);
            location.href = nurl;
        });

        $('.set-category-filter').on('change', function() {
            let id = $(this).val();
            var nurl = new URL('{!! url()->full() !!}');
            nurl.searchParams.set('category_id', id);
            location.href = nurl;
        })

        $('.quick-view-cart-item').on('click',function (){
            let key = $(this).data('key');
            $.get({
                url: '{{ route('vendor.order.quick-view-cart-item') }}',
                dataType: 'json',
                data: {
                    key: key,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        })

        $('.quick-view').on('click',function (){
            let product_id = $(this).data('product-id');
            quickView(product_id);
        })

        function quickView(product_id) {
            $.get({
                url: '{{ route('vendor.order.quick-view') }}',
                dataType: 'json',
                data: {
                    product_id: product_id,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    console.log("success...")
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        }

        function cartQuantityInitialize() {
            $('.btn-number').click(function(e) {
                e.preventDefault();

                var fieldName = $(this).attr('data-field');
                var type = $(this).attr('data-type');
                var input = $("input[name='" + fieldName + "']");
                var currentVal = parseInt(input.val());

                if (!isNaN(currentVal)) {
                    if (type == 'minus') {

                        if (currentVal > input.attr('min')) {
                            input.val(currentVal - 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('min')) {
                            $(this).attr('disabled', true);
                        }

                    } else if (type == 'plus') {

                        if (currentVal < input.attr('max')) {
                            input.val(currentVal + 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('max')) {
                            $(this).attr('disabled', true);
                        }

                    }
                } else {
                    input.val(0);
                }
            });

            $('.input-number').focusin(function() {
                $(this).data('oldValue', $(this).val());
            });

            $('.input-number').change(function() {

                var minValue = parseInt($(this).attr('min'));
                var maxValue = parseInt($(this).attr('max'));
                var valueCurrent = parseInt($(this).val());

                var name = $(this).attr('name');
                if (valueCurrent >= minValue) {
                    $(".btn-number[data-type='minus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, the minimum value was reached'
                    });
                    $(this).val($(this).data('oldValue'));
                }
                if (valueCurrent <= maxValue) {
                    $(".btn-number[data-type='plus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, stock limit exceeded.'
                    });
                    $(this).val($(this).data('oldValue'));
                }
            });
            $(".input-number").keydown(function(e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
                    // Allow: Ctrl+A
                    (e.keyCode == 65 && e.ctrlKey === true) ||
                    // Allow: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    // let it happen, don't do anything
                    return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        }

        function getVariantPrice() {
            if ($('#add-to-cart-form input[name=quantity]').val() > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
                $.ajax({
                    type: "POST",
                    url: '{{ route('vendor.item.variant-price') }}',
                    data: $('#add-to-cart-form').serializeArray(),
                    success: function(data) {
                        $('#add-to-cart-form #chosen_price_div').removeClass('d-none');
                        $('#add-to-cart-form #chosen_price_div #chosen_price').html(data.price);
                    }
                });
            }
        }

        $(document).on('click', '.update_order_item', function () {
            update_order_item();
        })

        function update_order_item(form_id = 'add-to-cart-form') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.order.add-to-cart') }}',
                data: $('#' + form_id).serializeArray(),
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    if (data.data == 1) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Cart',
                            text: "{{ translate('messages.product_already_added_in_cart') }}"
                        });
                        return false;
                    } else if (data.data == 0) {
                        toastr.success('{{ translate('messages.product_has_been_added_in_cart') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        location.reload();
                        return false;
                    } else if (data.data == 'variation_error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cart',
                            text: data.message
                        });
                        return false;
                    }
                    $('.call-when-done').click();

                    toastr.success('{{ translate('messages.order_updated_successfully') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    location.reload();
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        }

        $(document).on('click', '.removeFromCart', function () {
            let key = $(this).data('key');
            removeFromCart(key);
        })

        function removeFromCart(key) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_remove_this_order_item') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.post('{{ route('vendor.order.remove-from-cart') }}', {
                        _token: '{{ csrf_token() }}',
                        key: key,
                        order_id: '{{ $order->id }}'
                    }, function(data) {
                        if (data.errors) {
                            for (var i = 0; i < data.errors.length; i++) {
                                toastr.error(data.errors[i].message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                        } else {
                            toastr.success(
                                '{{ translate('messages.item_has_been_removed_from_cart') }}', {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            location.reload();
                        }

                    });
                }
            })

        }

        $('.edit-order').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_edit_this_order') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = '{{ route('vendor.order.edit', $order->id) }}';
                }
            })
        })

        $('.cancel-edit-order').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_cancel_editing') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = '{{ route('vendor.order.edit', $order->id) }}?cancle=true';
                }
            })
        })

        $('.submit-edit-order').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_submit_all_changes_for_this_order') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = '{{ route('vendor.order.update', $order->id) }}';
                }
            })
        })

        // =====================================================================
        // ADDED: Edit Manage Modal JS
        // =====================================================================

        // Select-all checkbox behaviour
        $('#select-all-unavailable').on('change', function () {
            $('.unavailable-item-check').prop('checked', $(this).is(':checked'));
            highlightUnavailableRows();
        });

        // Highlight rows when individual checkboxes change
        $(document).on('change', '.unavailable-item-check', function () {
            highlightUnavailableRows();
            // Sync select-all state
            var total   = $('.unavailable-item-check').length;
            var checked = $('.unavailable-item-check:checked').length;
            $('#select-all-unavailable').prop('indeterminate', checked > 0 && checked < total);
            $('#select-all-unavailable').prop('checked', checked === total);
        });

        function highlightUnavailableRows() {
            $('.unavailable-item-check').each(function () {
                var row = $(this).closest('tr');
                if ($(this).is(':checked')) {
                    row.addClass('table-danger');
                } else {
                    row.removeClass('table-danger');
                }
            });
        }

        // Confirm before submitting Edit Manage form
        $('#edit-manage-form').on('submit', function (e) {
            e.preventDefault();
            var checkedCount = $('.unavailable-item-check:checked').length;
            if (checkedCount === 0) {
                toastr.warning(
                    "{{ translate('messages.please_select_at_least_one_unavailable_item') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                return false;
            }
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.this_will_notify_the_customer_to_edit_their_order') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes_notify_customer') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#edit-manage-submit-btn').prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm mr-1"></span> {{ translate('messages.sending') }}...');
                    this.submit();
                }
            });
        });
        // END ADDED: Edit Manage Modal JS
    </script>
@endpush