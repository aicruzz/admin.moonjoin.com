@extends('layouts.admin.app')

@section('title', translate('store_setup'))


@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title mr-3">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
            </span>
            <span>
                {{ translate('messages.business_setup') }}
            </span>
        </h1>
        @include('admin-views.business-settings.partials.nav-menu')
    </div>

    <form action="{{ route('admin.business-settings.update-store') }}" method="post" enctype="multipart/form-data">
        @csrf
        @php($name = \App\Models\BusinessSetting::where('key', 'business_name')->first())

        <div class="row g-3">
            @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
            @php($default_location = $default_location->value ? json_decode($default_location->value, true) : 0)
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-sm-6">
                                @php($canceled_by_store = \App\Models\BusinessSetting::where('key', 'canceled_by_store')->first())
                                @php($canceled_by_store = $canceled_by_store ? $canceled_by_store->value : 0)
                                <div class="form-group mb-0">
                                    <label class="input-label text-capitalize d-flex alig-items-center">
                                        <span
                                            class="line--limit-1">{{ translate('messages.Can_a_Vendor_Cancel_Order?') }}</span>
                                        {{-- FIX: escaped apostrophe in Vendor's --}}
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('messages.Admin_can_enable/disable_Vendor\'s_order_cancellation_option.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <div class="restaurant-type-group border">
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="1"
                                                name="canceled_by_store" id="canceled_by_store" {{ $canceled_by_store == 1 ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('yes') }}</span>
                                        </label>
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="0"
                                                name="canceled_by_store" id="canceled_by_store2" {{ $canceled_by_store == 0 ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('no') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6">
                                @php($store_self_registration = \App\Models\BusinessSetting::where('key', 'toggle_store_registration')->first())
                                @php($store_self_registration = $store_self_registration ? $store_self_registration->value : 0)
                                <div class="form-group mb-0">
                                    <label
                                        class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                        <span class="pr-1 d-flex align-items-center switch--label">
                                            <span
                                                class="line--limit-1">{{ translate('messages.Vendor_self_registration') }}</span>
                                            <span class="form-label-secondary text-danger d-flex" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.A_vendor_can_send_a_registration_request_through_their_vendor_or_customer.') }}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.vendor_self_registration') }}"> *
                                            </span>
                                        </span>
                                        <input type="checkbox" data-id="store_self_registration1" data-type="toggle"
                                            data-image-on="{{ asset('/public/assets/admin/img/modal/store-self-reg-on.png') }}"
                                            data-image-off="{{ asset('/public/assets/admin/img/modal/store-self-reg-off.png') }}"
                                            data-title-on="" data-title-off=""
                                            data-text-on="<p>{{ translate('messages.If_you_enable_this,_vendors_can_do_self-registration_from_the_vendor_or_customer_app_or_website.') }}</p>"
                                            data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_Vendor_Self-Registration_feature_will_be_hidden_from_the_vendor_or_customer_app,_website,_or_admin_landing_page.') }}</p>"
                                            class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                            name="store_self_registration" id="store_self_registration1" {{ $store_self_registration == 1 ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="col-sm-6 col-lg-4">
                                @php($product_gallery = \App\Models\BusinessSetting::where('key', 'product_gallery')->first()?->value ?? 0)
                                <div class="form-group mb-0">
                                    <label
                                        class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                        <span class="pr-1 d-flex align-items-center switch--label">
                                            <span class="line--limit-1">{{translate('messages.Product_Gallery')}}</span>
                                            <span class="form-label-secondary text-danger d-flex" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.If_you_enable_this,_any_vendor_can_duplicate_product_and_create_a_new_product_by_use_this.')}}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.Product_Gallery') }}"> *
                                            </span>
                                        </span>
                                        <input type="checkbox" data-id="product_gallery" data-type="toggle"
                                            data-image-on="{{ asset('/public/assets/admin/img/modal/store-reg-on.png') }}"
                                            data-image-off="{{ asset('/public/assets/admin/img/modal/store-reg-off.png') }}"
                                            data-title-on="<strong>{{translate('messages.Want_to_enable_product_gallery?')}}</strong>"
                                            data-title-off="<strong>{{translate('messages.Want_to_disable_product_gallery?')}}</strong>"
                                            data-text-on="<p>{{ translate('messages.If_you_enable_this,can_create_duplicate_products') }}</p>"
                                            data-text-off="<p>{{ translate('messages.If_you_disable_this,can_not_create_duplicate_products.') }}</p>"
                                            class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                            name="product_gallery" id="product_gallery" {{ $product_gallery == 1 ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div
                                class="col-sm-6 col-lg-4 {{ $product_gallery == 1 ? '' : 'd-none' }} access_all_products">
                                @php($access_all_products = \App\Models\BusinessSetting::where('key', 'access_all_products')->first()?->value ?? 0)
                                <div class="form-group mb-0">
                                    <label
                                        class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                        <span class="pr-1 d-flex align-items-center switch--label">
                                            <span
                                                class="line--limit-1">{{translate('messages.access_all_products')}}</span>
                                            <span class="form-label-secondary text-danger d-flex" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.If_you_enable_this_vendors_can_access_all_products_of_other_vendors.')}}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.access_all_products') }}"> *
                                            </span>
                                        </span>
                                        <input type="checkbox" data-id="access_all_products" data-type="toggle"
                                            data-image-on="{{ asset('/public/assets/admin/img/modal/store-reg-on.png') }}"
                                            data-image-off="{{ asset('/public/assets/admin/img/modal/store-reg-off.png') }}"
                                            data-title-on="<strong>{{translate('messages.Want_to_enable_access_all_products?')}}</strong>"
                                            data-title-off="<strong>{{translate('messages.Want_to_disable_access_all_products?')}}</strong>"
                                            data-text-on="<p>{{ translate('messages.If_you_enable_this,_vendors_can_access_all_products_of_other_available_vendors') }}</p>"
                                            data-text-off="<p>{{ translate('messages.If_you_disable_this,_vendors_can_not_access_all_products_of_other_vendors.') }}</p>"
                                            class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                            name="access_all_products" id="access_all_products" {{ $access_all_products == 1 ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6">
                                @php($product_approval = \App\Models\BusinessSetting::where('key', 'product_approval')->first()?->value ?? 0)
                                <div class="form-group mb-0">
                                    <label
                                        class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                        <span class="pr-1 d-flex align-items-center switch--label">
                                            <span
                                                class="line--limit-1">{{translate('messages.Need_Approval_for_Products')}}</span>
                                            <span class="form-label-secondary text-danger d-flex" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.If_enabled,_this_option_to_require_admin_approval_for_products_to_be_displayed_on_the_user_side.')}}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.customer_verification_toggle') }}"> *
                                            </span>
                                        </span>
                                        <input type="checkbox" data-id="product_approval" data-type="toggle"
                                            data-image-on="{{ asset('/public/assets/admin/img/modal/store-reg-on.png') }}"
                                            data-image-off="{{ asset('/public/assets/admin/img/modal/store-reg-off.png') }}"
                                            data-title-on="<strong>{{translate('messages.Want_to_enable_product_approval?')}}</strong>"
                                            data-title-off="<strong>{{translate('messages.Want_to_disable_product_approval?')}}</strong>"
                                            data-text-on="<p>{{ translate('messages.If_you_enable_this,_option_to_require_admin_approval_for_products_to_be_displayed_on_the_user_side') }}</p>"
                                            data-text-off="<p>{{ translate('messages.If_you_disable_this,products_will_to_be_displayed_on_the_user_side_without_admin_approval.') }}</p>"
                                            class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                            name="product_approval" id="product_approval" {{ $product_approval == 1 ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6">
                                @php($store_review_reply = \App\Models\BusinessSetting::where('key', 'store_review_reply')->first())
                                @php($store_review_reply = $store_review_reply ? $store_review_reply->value : 0)
                                <div class="form-group mb-0">
                                    <label
                                        class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                        <span class="pr-1 d-flex align-items-center switch--label">
                                            <span
                                                class="line--limit-1">{{ translate('Vendor_Can_Reply_Review') }}</span>
                                            <span class="form-label-secondary text-danger d-flex" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('If enabled, vendors can actively engage with the customers by responding to the reviews left for their orders') }}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_review_reply') }}">
                                            </span>
                                        </span>
                                        <input type="checkbox" data-id="store_review_reply1" data-type="toggle"
                                            data-image-on="{{ asset('/public/assets/admin/img/modal/store-self-reg-on.png') }}"
                                            data-image-off="{{ asset('/public/assets/admin/img/modal/store-self-reg-off.png') }}"
                                            data-title-on="{{ translate('Want to enable the option vendor to reply?') }}"
                                            data-title-off="{{ translate('Want_to_disable_the_option_vendor_to_reply?') }}"
                                            data-text-on="<p>{{ translate('If enabled, vendors can actively engage with the customers by responding to the reviews left for their orders.') }}</p>"
                                            data-text-off="<p>{{ translate('If_disabled,_a_vendor_can_not_reply_to_a_review') }}</p>"
                                            class="toggle-switch-input dynamic-checkbox-toggle" value="1"
                                            name="store_review_reply" id="store_review_reply1" {{ $store_review_reply == 1 ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        @php($product_approval_datas = \App\Models\BusinessSetting::where('key', 'product_approval_datas')->first()?->value ?? '')
                        @php($product_approval_datas = json_decode($product_approval_datas, true))
                        <div class="mt-4 mb-4 access_product_approval">
                            <label class="mb-2 input-label text-capitalize d-flex alig-items-center"
                                for="">{{ translate('Need_Approval_When') }}</label>
                            <div class="justify-content-between border form-control">
                                <div class="form-check form-check-inline mx-4">
                                    <input class="mx-2 form-check-input" type="checkbox" {{ data_get($product_approval_datas, 'Add_new_product', null) == 1 ? 'checked' : '' }} id="inlineCheckbox1" value="1" name="Add_new_product" {{ $product_approval == 1 ? '' : 'disabled' }}>
                                    <label class="form-check-label"
                                        for="inlineCheckbox1">{{ translate('Add_new_product') }}</label>
                                </div>
                                <div class="form-check form-check-inline mx-4">
                                    <input class="mx-2 form-check-input" type="checkbox" {{ data_get($product_approval_datas, 'Update_product_price', null) == 1 ? 'checked' : '' }} id="inlineCheckbox2" value="1" name="Update_product_price" {{ $product_approval == 1 ? '' : 'disabled' }}>
                                    <label class="form-check-label"
                                        for="inlineCheckbox2">{{ translate('Update_product_price') }}</label>
                                </div>
                                <div class="form-check form-check-inline mx-4">
                                    <input class="mx-2 form-check-input" type="checkbox" {{ data_get($product_approval_datas, 'Update_product_variation', null) == 1 ? 'checked' : '' }} id="inlineCheckbox3" value="1" name="Update_product_variation"
                                        {{ $product_approval == 1 ? '' : 'disabled' }}>
                                    <label class="form-check-label"
                                        for="inlineCheckbox3">{{ translate('Update_product_variation') }}</label>
                                </div>
                                <div class="form-check form-check-inline mx-4">
                                    <input class="mx-2 form-check-input" type="checkbox" {{ data_get($product_approval_datas, 'Update_anything_in_product_details', null) == 1 ? 'checked' : '' }} id="inlineCheckbox4" value="1"
                                        name="Update_anything_in_product_details" {{ $product_approval == 1 ? '' : 'disabled' }}>
                                    <label class="form-check-label"
                                        for="inlineCheckbox4">{{ translate('Update_anything_in_product_details') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-sm-6">
                                @php($cash_in_hand_overflow = \App\Models\BusinessSetting::where('key', 'cash_in_hand_overflow_store')->first())
                                @php($cash_in_hand_overflow = $cash_in_hand_overflow ? $cash_in_hand_overflow->value : '')
                                <div class="form-group mb-0">
                                    <label
                                        class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                        <span class="pr-1 d-flex align-items-center switch--label">
                                            <span
                                                class="line--limit-1">{{ translate('messages.Cash_In_Hand_Overflow') }}</span>
                                            {{-- FIX: removed inner single quotes around Cash_in_Hand --}}
                                            <span class="form-label-secondary text-danger d-flex" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('If_enabled,_vendors_will_be_automatically_suspended_by_the_system_when_their_Cash_in_Hand_limit_is_exceeded.') }}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.cash_in_hand_overflow') }}"> *
                                            </span>
                                        </span>
                                        <input type="checkbox" data-id="cash_in_hand_overflow" data-type="toggle"
                                            data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                            data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                            data-title-on="{{translate('Want_to_enable')}} <strong>{{translate('Cash_In_Hand_Overflow')}}</strong>"
                                            data-title-off="{{translate('Want_to_disable')}} <strong>{{translate('Cash_In_Hand_Overflow')}}</strong>"
                                            data-text-on="<p>{{ translate('If_enabled,_vendors_have_to_provide_collected_cash_by_them_self') }}</p>"
                                            data-text-off="<p>{{ translate('If_disabled,_vendors_do_not_have_to_provide_collected_cash_by_them_self') }}</p>"
                                            class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                            name="cash_in_hand_overflow_store" id="cash_in_hand_overflow" {{ $cash_in_hand_overflow == 1 ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6">
                                @php($cash_in_hand_overflow_store_amount = \App\Models\BusinessSetting::where('key', 'cash_in_hand_overflow_store_amount')->first())
                                <div class="form-group mb-0">
                                    <label class="input-label text-capitalize" for="cash_in_hand_overflow_store_amount">
                                        <span>{{ translate('Maximum_Amount_to_Hold_Cash_in_Hand') }}
                                            ({{ \App\CentralLogics\Helpers::currency_symbol() }})</span>
                                        <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('Enter_the_maximum_cash_amount_vendors_can_hold._If_this_number_exceeds,_vendors_will_be_suspended_and_not_receive_any_orders.') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.dm_cancel_order_hint') }}">
                                        </span>
                                    </label>
                                    <input type="number" name="cash_in_hand_overflow_store_amount" class="form-control"
                                        id="cash_in_hand_overflow_store_amount" min="0" step=".001"
                                        value="{{ $cash_in_hand_overflow_store_amount ? $cash_in_hand_overflow_store_amount->value : '' }}"
                                        {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6">
                                @php($min_amount_to_pay_store = \App\Models\BusinessSetting::where('key', 'min_amount_to_pay_store')->first())
                                <div class="form-group mb-0">
                                    <label class="input-label text-capitalize" for="min_amount_to_pay_store">
                                        <span>{{ translate('Minimum_Amount_To_Pay') }}
                                            ({{ \App\CentralLogics\Helpers::currency_symbol() }})</span>
                                        <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                            data-original-title="{{ translate('Enter_the_minimum_cash_amount_vendors_can_pay') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                alt="{{ translate('messages.dm_cancel_order_hint') }}">
                                        </span>
                                    </label>
                                    <input type="number" name="min_amount_to_pay_store" class="form-control"
                                        id="min_amount_to_pay_store" min="0" step=".001"
                                        value="{{ $min_amount_to_pay_store ? $min_amount_to_pay_store->value : '' }}" {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                </div>
                            </div>
                        </div>

                        {{-- ===== Employee Earnings Global Setup ===== --}}
                        @php($enabled_raw = \App\Models\BusinessSetting::where('key','employee_earning_enabled_types')->first()?->value ?? '["none"]')
                        @php($enabled_types = json_decode($enabled_raw, true) ?? ['none'])
                        @php($global_earning_fixed = \App\Models\BusinessSetting::where('key','employee_earning_fixed_amount')->first()?->value)
                        @php($global_earning_pct   = \App\Models\BusinessSetting::where('key','employee_earning_percentage')->first()?->value)
                        @php($global_earning_cap   = \App\Models\BusinessSetting::where('key','employee_earning_cap')->first()?->value)
                        <hr class="mt-4">
                        <div class="row g-3 align-items-start mt-2">
                            <div class="col-12">
                                <h6 class="font-semibold mb-1">{{ translate('Vendor Employee Earning Setup') }}</h6>
                                <p class="fs-12 text-muted m-0">{{ translate('Select which earning types are available system-wide. Admins can then pick one per store from the enabled options.') }}</p>
                            </div>

                            {{-- Multi-select checkboxes --}}
                            <div class="col-12">
                                <label class="input-label text-capitalize d-block mb-2">
                                    {{ translate('Enable Earning Types') }}
                                    <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                          data-original-title="{{ translate('Check all earning types you want admins to be able to assign to stores.') }}">
                                        <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                    </span>
                                </label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="border rounded px-3 py-2 d-flex align-items-center gap-2 cursor-pointer {{ in_array('none', $enabled_types) ? 'border-primary' : '' }}">
                                        <input type="checkbox" name="employee_earning_enabled_types[]" value="none"
                                               id="enable_none" class="global-earning-checkbox"
                                            {{ in_array('none', $enabled_types) ? 'checked' : '' }}>
                                        <span>{{ translate('None') }}</span>
                                    </label>
                                    <label class="border rounded px-3 py-2 d-flex align-items-center gap-2 cursor-pointer {{ in_array('fixed', $enabled_types) ? 'border-primary' : '' }}">
                                        <input type="checkbox" name="employee_earning_enabled_types[]" value="fixed"
                                               id="enable_fixed" class="global-earning-checkbox"
                                            {{ in_array('fixed', $enabled_types) ? 'checked' : '' }}>
                                        <span>{{ translate('Fixed Amount') }}</span>
                                    </label>
                                    <label class="border rounded px-3 py-2 d-flex align-items-center gap-2 cursor-pointer {{ in_array('percentage', $enabled_types) ? 'border-primary' : '' }}">
                                        <input type="checkbox" name="employee_earning_enabled_types[]" value="percentage"
                                               id="enable_percentage" class="global-earning-checkbox"
                                            {{ in_array('percentage', $enabled_types) ? 'checked' : '' }}>
                                        <span>{{ translate('Percentage (with Cap)') }}</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Default Fixed Amount (shown when fixed is checked) --}}
                            <div class="col-lg-4 col-sm-6 global-earning-fixed-section {{ !in_array('fixed', $enabled_types) ? 'd-none' : '' }}">
                                <div class="form-group mb-0">
                                    <label class="input-label text-capitalize" for="employee_earning_fixed_amount">
                                        {{ translate('Default Fixed Amount Per Order') }}
                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                        <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                              data-original-title="{{ translate('Default flat amount per completed order. Stores can override this.') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </label>
                                    <input id="employee_earning_fixed_amount" type="number" step="0.01" min="0"
                                           class="form-control" name="employee_earning_fixed_amount"
                                           placeholder="{{ translate('e.g. 500') }}" value="{{ $global_earning_fixed }}">
                                </div>
                            </div>

                            {{-- Default Percentage (shown when percentage is checked) --}}
                            <div class="col-lg-4 col-sm-6 global-earning-pct-section {{ !in_array('percentage', $enabled_types) ? 'd-none' : '' }}">
                                <div class="form-group mb-0">
                                    <label class="input-label text-capitalize" for="employee_earning_percentage">
                                        {{ translate('Default Percentage (%)') }}
                                        <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                              data-original-title="{{ translate('Default percentage of order amount. Stores can override this.') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </label>
                                    <input id="employee_earning_percentage" type="number" step="0.01" min="0" max="100"
                                           class="form-control" name="employee_earning_percentage"
                                           placeholder="{{ translate('e.g. 10') }}" value="{{ $global_earning_pct }}">
                                </div>
                            </div>

                            {{-- Default Cap (shown when percentage is checked) --}}
                            <div class="col-lg-4 col-sm-6 global-earning-pct-section {{ !in_array('percentage', $enabled_types) ? 'd-none' : '' }}">
                                <div class="form-group mb-0">
                                    <label class="input-label text-capitalize" for="employee_earning_cap">
                                        {{ translate('Default Maximum Cap') }}
                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                        <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                              data-original-title="{{ translate('Max earning per order regardless of percentage. Optional.') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </label>
                                    <input id="employee_earning_cap" type="number" step="0.01" min="0"
                                           class="form-control" name="employee_earning_cap"
                                           placeholder="{{ translate('e.g. 2000 (optional)') }}" value="{{ $global_earning_cap }}">
                                </div>
                            </div>
                        </div>
                        {{-- ===== End Employee Earnings Global Setup ===== --}}

                        <div class="btn--container justify-content-end mt-20">
                            <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                            <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                class="btn btn--primary call-demo">{{ translate('save_information') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- ====== Debit Reasons for Vendor ====== --}}
    {{-- Same structure as the Debit Reasons card on the Deliveryman settings page --}}
    <div class="row g-2 mt-2">
        <div class="col-lg-12">
            <div class="card card-container">
                <div class="card-body">
                    {{-- Section header --}}
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-sm-nowrap flex-wrap">
                        <div>
                            <h4 class="mb-1">
                                <i class="tio-remove-from-trash mr-1"></i>
                                {{ translate('Debit Reasons for Vendor') }}
                            </h4>
                            <p class="fs-12 m-0">
                                {{ translate('These reasons appear in the Debit Vendor form. Add all valid debit reasons so admins can select them when deducting from a vendor\'s wallet.') }}
                            </p>
                        </div>
                        <div
                            class="view_toggle_btn fz--14px info-dark cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                            {{ translate('messages.view') }}
                            <i class="tio-chevron-down fs-22"></i>
                        </div>
                    </div>

                    {{-- Collapsible body --}}
                    <div class="card-details-body">
                        <div class="bg-light2 rounded p-xxl-20 p-3 mt-20">

                            {{-- Add Form --}}
                            <form action="{{ route('admin.business-settings.debit-vendor-employee-reasons.store') }}"
                                method="post">
                                @csrf
                                @php($store_language = \App\Models\BusinessSetting::where('key', 'language')->first())
                                @php($store_language = $store_language->value ?? null)

                                @if ($store_language)
                                    <div
                                        class="js-nav-scroller tabs-slide-wrap tabs-slide-space position-relative hs-nav-scroller-horizontal">
                                        <ul class="nav nav-tabs tabs-inner nav--tabs mb-4 border-0">
                                            <li class="nav-item">
                                                <a class="nav-link store_lang_link active" href="#"
                                                    id="store-default-link">{{ translate('Default') }}</a>
                                            </li>
                                            @foreach (json_decode($store_language) as $lang)
                                                <li class="nav-item">
                                                    <a class="nav-link store_lang_link" href="#"
                                                        id="store-{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="arrow-area">
                                            <div class="button-prev align-items-center">
                                                <button type="button"
                                                    class="btn btn-click-prev mr-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                    <i class="tio-chevron-left fs-24"></i>
                                                </button>
                                            </div>
                                            <div class="button-next align-items-center">
                                                <button type="button"
                                                    class="btn btn-click-next ml-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                    <i class="tio-chevron-right fs-24"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-sm-6 store_lang_form store-default-form">
                                        <label for="store_debit_reason_default" class="form-label">
                                            {{ translate('Debit Reason') }} ({{ translate('messages.default') }})
                                        </label>
                                        <input type="text" class="form-control h--45px" name="reason[]"
                                            id="store_debit_reason_default"
                                            placeholder="{{ translate('Ex: Cash Shortage') }}">
                                        <input type="hidden" name="lang[]" value="default">
                                    </div>

                                    @if ($store_language)
                                        @foreach (json_decode($store_language) as $lang)
                                            <div class="col-sm-6 d-none store_lang_form" id="store-{{ $lang }}-form">
                                                <label for="store_debit_reason_{{ $lang }}" class="form-label">
                                                    {{ translate('Debit Reason') }} ({{ strtoupper($lang) }})
                                                </label>
                                                <input type="text" class="form-control h--45px" name="reason[]"
                                                    id="store_debit_reason_{{ $lang }}"
                                                    placeholder="{{ translate('Ex:_Item_is_Broken') }}">
                                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <input type="hidden" name="user_type" value="store">

                                <div class="btn--container justify-content-end mt-3">
                                    <button type="reset"
                                        class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                    <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn--primary call-demo">{{ translate('Submit') }}</button>
                                </div>
                            </form>

                            {{-- Reasons Table --}}
                            @php($store_reasons = \App\Models\DebitStoreReason::latest()->paginate(config('default_pagination', 25), ['*'], 'store_page'))

                            <div class="mt-20">
                                <h5 class="mb-3">{{ translate('Debit Reason List for Vendor') }}</h5>
                                <div class="table-responsive datatable-custom">
                                    <table
                                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle"
                                        data-hs-datatables-options='{"isResponsive": false,"isShowPaging": false,"paging":false}'>
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="border-0">{{ translate('messages.SL') }}</th>
                                                <th class="border-0">{{ translate('messages.Reason') }}</th>
                                                <th class="border-0">{{ translate('messages.status') }}</th>
                                                <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($store_reasons as $store_key => $store_reason)
                                            <tr>
                                                <td>{{ $store_key + $store_reasons->firstItem() }}</td>
                                                <td>
                                                    <span class="d-block font-size-sm text-body"
                                                        title="{{ $store_reason->reason }}">
                                                        {{ Str::limit($store_reason->reason, 40, '...') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <label class="toggle-switch toggle-switch-sm"
                                                        for="storeCheckbox{{ $store_reason->id }}">
                                                        <input type="checkbox"
                                                            data-url="{{ route('admin.business-settings.debit-store-reasons.status', [$store_reason->id, $store_reason->status ? 0 : 1]) }}"
                                                            class="toggle-switch-input redirect-url"
                                                            id="storeCheckbox{{ $store_reason->id }}" {{ $store_reason->status ? 'checked' : '' }}>
                                                        <span class="toggle-switch-label">
                                                            <span class="toggle-switch-indicator"></span>
                                                        </span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <div class="btn--container justify-content-center">
                                                        <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                                            title="{{ translate('messages.edit') }}" data-toggle="modal"
                                                            data-target="#store_update_reason_{{ $store_reason->id }}">
                                                            <i class="tio-edit"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn--danger btn-outline-danger action-btn form-alert"
                                                            href="javascript:"
                                                            data-id="store-debit-reason-{{ $store_reason->id }}"
                                                            data-message="{{ translate('messages.If_you_want_to_delete_this_reason,_please_confirm_your_decision.') }}"
                                                            title="{{ translate('messages.delete') }}">
                                                            <i class="tio-delete-outlined"></i>
                                                        </a>
                                                        <form
                                                            action="{{ route('admin.business-settings.debit-store-reasons.destroy', $store_reason->id) }}"
                                                            method="post"
                                                            id="store-debit-reason-{{ $store_reason->id }}">
                                                            @csrf @method('delete')
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- Edit Modal --}}
                                            <div class="modal fade" id="store_update_reason_{{ $store_reason->id }}"
                                                tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                {{ translate('Debit Reason') }} —
                                                                {{ translate('messages.Update') }}
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form
                                                            action="{{ route('admin.business-settings.debit-store-reasons.update') }}"
                                                            method="post">
                                                            <div class="modal-body">
                                                                @csrf
                                                                @method('put')

                                                                @php($store_edit = $store_reason->loadMissing('translations'))
                                                                @php($store_edit_lang = \App\Models\BusinessSetting::where('key', 'language')->first())
                                                                @php($store_edit_lang = $store_edit_lang->value ?? null)

                                                                <ul class="nav nav-tabs nav--tabs mb-3 border-0">
                                                                    <li class="nav-item">
                                                                        <a class="nav-link store-update-lang_link add_active active"
                                                                            href="#"
                                                                            id="store-edit-default-link">{{ translate('Default') }}</a>
                                                                    </li>
                                                                    @if ($store_edit_lang)
                                                                        @foreach (json_decode($store_edit_lang) as $edit_lang)
                                                                            <li class="nav-item">
                                                                                <a class="nav-link store-update-lang_link"
                                                                                    href="#"
                                                                                    data-reason-id="{{ $store_edit->id }}"
                                                                                    id="store-edit-{{ $edit_lang }}-link">
                                                                                    {{ \App\CentralLogics\Helpers::get_language_name($edit_lang) . '(' . strtoupper($edit_lang) . ')' }}
                                                                                </a>
                                                                            </li>
                                                                        @endforeach
                                                                    @endif
                                                                </ul>

                                                                <input type="hidden" name="reason_id"
                                                                    value="{{ $store_edit->id }}">

                                                                <div class="form-group mb-3 add_active_2 store-update-lang_form"
                                                                    id="store-edit-default-form_{{ $store_edit->id }}">
                                                                    <label class="form-label">
                                                                        {{ translate('Debit Reason') }}
                                                                        ({{ translate('messages.default') }})
                                                                    </label>
                                                                    <input class="form-control" name="reason[]"
                                                                        value="{{ $store_edit?->getRawOriginal('reason') }}"
                                                                        type="text">
                                                                    <input type="hidden" name="lang1[]" value="default">
                                                                </div>

                                                                @if ($store_edit_lang)
                                                                    @foreach (json_decode($store_edit_lang) as $edit_lang)
                                                                                                                            <?php
                                                                        $store_translate = [];
                                                                        if ($store_edit?->translations) {
                                                                            foreach ($store_edit->translations as $t) {
                                                                                if ($t->locale == $edit_lang && $t->key == 'reason') {
                                                                                    $store_translate[$edit_lang]['reason'] = $t->value;
                                                                                }
                                                                            }
                                                                        }
                                                                                                                                                                                                                                                                                                                                                                                                                                                    ?>
                                                                                                                            <div class="form-group mb-3 d-none store-update-lang_form"
                                                                                                                                id="store-edit-{{ $edit_lang }}-langform_{{ $store_edit->id }}">
                                                                                                                                <label class="form-label">
                                                                                                                                    {{ translate('Debit Reason') }}
                                                                                                                                    ({{ strtoupper($edit_lang) }})
                                                                                                                                </label>
                                                                                                                                <input class="form-control" name="reason[]"
                                                                                                                                    placeholder="{{ translate('Ex:_Item_is_Broken') }}"
                                                                                                                                    value="{{ $store_translate[$edit_lang]['reason'] ?? null }}"
                                                                                                                                    type="text">
                                                                                                                                <input type="hidden" name="lang1[]"
                                                                                                                                    value="{{ $edit_lang }}">
                                                                                                                            </div>
                                                                    @endforeach
                                                                @endif

                                                                <input type="hidden" name="user_type" value="store">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-dismiss="modal">
                                                                    {{ translate('Close') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">
                                                                    {{ translate('Save_changes') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- End Edit Modal --}}

                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">
                                                    {{ translate('No debit reasons configured yet. Add reasons above.') }}
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @if ($store_reasons->hasPages())
                                    <div class="mt-3 d-flex justify-content-end">
                                        {{ $store_reasons->links() }}
                                    </div>
                                @endif
                            </div>

                        </div>{{-- /bg-light2 --}}
                    </div>{{-- /card-details-body --}}
                </div>{{-- /card-body --}}
            </div>{{-- /card --}}
        </div>
    </div>
    {{-- ====== End: Debit Reasons for Vendor ====== --}}

</div>

@endsection

@push('script_2')
<script>
    "use strict";
    $(document).ready(function () {
        function syncGlobalEarningFields() {
            $('#enable_fixed').is(':checked')
                ? $('.global-earning-fixed-section').removeClass('d-none')
                : $('.global-earning-fixed-section').addClass('d-none');

            $('#enable_percentage').is(':checked')
                ? $('.global-earning-pct-section').removeClass('d-none')
                : $('.global-earning-pct-section').addClass('d-none');
        }

        $('.global-earning-checkbox').on('change', function () {
            syncGlobalEarningFields();
            // Highlight checked label
            $(this).closest('label').toggleClass('border-primary', $(this).is(':checked'));
        });
    });
</script>
@endpush