@extends('layouts.admin.app')

@section('title', translate('messages.delivery_man_settings'))


@section('content')
@php use App\CentralLogics\Helpers; @endphp
<div class="content">
    <form action="{{ route('admin.business-settings.update-dm') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex align-items-center justify-content-between gap-1 w-100">
                    <h1 class="page-header-title mr-3">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                        </span>
                        <span>
                            {{translate('business_setup')}}
                        </span>
                    </h1>
                    @if (!(Request::is('admin/business-settings/language') || Request::is('admin/business-settings/business-setup/refund-settings') || Request::is('admin/business-settings/business-setup/automated-message')))
                        <div class="d-flex flex-wrap justify-content-end align-items-center flex-grow-1">
                            <div class="blinkings active">
                                <i class="tio-info-outined"></i>
                                <div class="business-notes">
                                    <h6><img src="{{asset('/public/assets/admin/img/notes.png')}}" alt="">
                                        {{translate('Note')}}</h6>
                                    <div>
                                        @if (Request::is('admin/business-settings/business-setup/refund-settings'))
                                            {{ translate('messages.*If_the_Admin_enables_the_‘Refund_Request_Mode’,_customers_can_request_a_refund.') }}
                                        @else
                                            {{translate('messages.don’t_forget_to_click_the_‘Save Information’_button_below_to_save_changes.')}}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @include('admin-views.business-settings.partials.nav-menu')
            </div>
            <!-- Page Header -->

            <!-- End Page Header -->

            <div class="row g-2">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="rounded p-xxl-20 p-3 bg-light2">

                                <div class="row g-3">
                                    <div class="col-sm-6 col-lg-4">
                                        @php($dm_tips_status = Helpers::get_business_settings('dm_tips_status'))
                                        <div class="form-group mb-0">
                                            <span class="d-flex align-items-center mb-2">
                                                <span class="text-dark pr-1">
                                                    {{ translate('messages.Tips_for_Deliveryman') }}
                                                </span>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Customer_can_give_tips_to_deliveryman_during_checkout_from_the_customer_app_&_website._From_this,_admin_has_no_commission.') }}">
                                                    <i class="tio-info text-light-gray"></i>
                                                </span>
                                            </span>
                                            <label
                                                class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                <span class="line--limit-1 switch--label">
                                                    {{ translate('messages.Status') }}
                                                </span>
                                                <input type="checkbox" data-id="dm_tips_status" data-type="toggle"
                                                    data-image-on="{{ asset('/public/assets/admin/img/modal/dm-tips-on.png') }}"
                                                    data-image-off="{{ asset('/public/assets/admin/img/modal/dm-tips-off.png') }}"
                                                    data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                    data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                    data-text-on="<p>{{ translate('messages.If_you_enable_this,_Customers_can_give_tips_to_a_deliveryman_during_checkout.') }}</p>"
                                                    data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_Tips_for_Deliveryman_feature_will_be_hidden_from_the_Customer_App_and_Website.') }}</p>"
                                                    class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                                    name="dm_tips_status" id="dm_tips_status" {{ $dm_tips_status == '1' ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        @php($show_dm_earning = Helpers::get_business_settings('show_dm_earning')  )
                                        <div class="form-group mb-0">
                                            <span class="d-flex align-items-center mb-2">
                                                <span class="text-dark pr-1">
                                                    {{ translate('messages.Show Earnings in App') }}
                                                </span>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.With_this_feature,_Deliverymen_can_see_their_earnings_on_a_specific_order_while_accepting_it.') }}">
                                                    <i class="tio-info text-light-gray"></i>
                                                </span>
                                            </span>
                                            <label
                                                class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                <span class="pr-1 d-flex align-items-center switch--label">
                                                    <span class="line--limit-1">
                                                        {{ translate('Status') }}
                                                    </span>
                                                </span>
                                                <input type="checkbox" data-id="show_dm_earning" data-type="toggle"
                                                    data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                    data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                    data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                    data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                    data-text-on="<p>{{ translate('messages.If_you_enable_this,_Deliverymen_can_see_their_earning_per_order_request_from_the_Order_Details_page_in_the_Deliveryman_App.') }}</p>"
                                                    data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_feature_will_be_hidden_from_the_Deliveryman_App.') }}</p>"
                                                    class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                                    name="show_dm_earning" id="show_dm_earning" {{ $show_dm_earning == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">

                                        @php($toggle_dm_registration = Helpers::get_business_settings('toggle_dm_registration') )
                                        <div class="form-group mb-0">
                                            <span class="d-flex align-items-center mb-2">
                                                <span class="text-dark pr-1">
                                                    {{ translate('messages.dm_self_registration') }}
                                                </span>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.With_this_feature,_deliverymen_can_register_themselves_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page._The_admin_will_receive_an_email_notification_and_can_accept_or_reject_the_request.') }}">
                                                    <i class="tio-info text-light-gray"></i>
                                                </span>
                                            </span>
                                            <label
                                                class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                <span class="pr-1 d-flex align-items-center switch--label">
                                                    <span class="line--limit-1">
                                                        {{ translate('messages.Status') }}
                                                    </span>
                                                </span>
                                                <input type="checkbox" data-id="dm_self_registration1"
                                                    data-type="toggle"
                                                    data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                    data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                    data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                    data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                    data-text-on="<p>{{ translate('messages.If_you_enable_this,_users_can_register_as_Deliverymen_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                    data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                    class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                                    name="toggle_dm_registration" id="dm_self_registration1" {{ $toggle_dm_registration == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        @php($dm_maximum_orders = Helpers::get_business_settings('dm_maximum_orders')   )
                                        <div class="form-group mb-0">
                                            <label class="form-label text-capitalize" for="dm_maximum_orders">
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        class="line--limit-1 flex-grow pr-1">{{ translate('Maximum Assigned Order Limit') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.Set_the_maximum_order_limit_a_Deliveryman_can_take_at_a_time.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </div>
                                            </label>
                                            <input type="number" name="dm_maximum_orders" class="form-control"
                                                id="dm_maximum_orders" min="1" value="{{ $dm_maximum_orders ?? 1 }}"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        @php($canceled_by_deliveryman = Helpers::get_business_settings('canceled_by_deliveryman'))
                                        <div class="form-group mb-0">
                                            <label class="input-label text-capitalize d-flex align-items-center"><span
                                                    class="line--limit-1 pr-1">{{ translate('messages.Can_A_Deliveryman_Cancel_Order?') }}</span>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Admin_can_enable/disable_Deliveryman’s_order_cancellation_option_in_the_respective_app.') }}"><i
                                                        class="tio-info text-light-gray"></i></span></label>
                                            <div class="resturant-type-group border">
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input" type="radio" value="1"
                                                        name="canceled_by_deliveryman" id="canceled_by_deliveryman" {{ $canceled_by_deliveryman == 1 ? 'checked' : '' }}>
                                                    <span class="form-check-label">
                                                        {{ translate('yes') }}
                                                    </span>
                                                </label>
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input" type="radio" value="0"
                                                        name="canceled_by_deliveryman" id="canceled_by_deliveryman2" {{ $canceled_by_deliveryman == 0 ? 'checked' : '' }}>
                                                    <span class="form-check-label">
                                                        {{ translate('no') }}
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        @php($dm_picture_upload_status = Helpers::get_business_settings('dm_picture_upload_status'))
                                        <div class="form-group mb-0">
                                            <span class="d-flex align-items-center mb-2">
                                                <span class="text-dark pr-1">
                                                    {{ translate('messages.Take_Picture_For_Completing_Delivery') }}
                                                </span>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.If_enabled,_deliverymen_will_see_an_option_to_take_pictures_of_the_delivered_products_when_he_swipes_the_delivery_confirmation_slide.') }}">
                                                    <i class="tio-info text-light-gray"></i>
                                                </span>
                                            </span>
                                            <label
                                                class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                <span class="pr-1 d-flex align-items-center switch--label">
                                                    <span class="line--limit-1">
                                                        {{ translate('messages.Status') }}
                                                    </span>
                                                </span>
                                                <input type="checkbox" data-id="dm_picture_upload_status"
                                                    data-type="toggle"
                                                    data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                    data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                    data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                    data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                    data-text-on="<p>{{ translate('messages.If_you_enable_this,_delivery_man_can_upload_order_proof_before_order_delivery.') }}</p>"
                                                    data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_delivery_man_app.') }}</p>"
                                                    class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                                    name="dm_picture_upload_status" id="dm_picture_upload_status" {{ $dm_picture_upload_status == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>




                                    <div class="col-sm-6 col-lg-4">
                                        @php($cash_in_hand_overflow = Helpers::get_business_settings('cash_in_hand_overflow_delivery_man'))
                                        <div class="form-label  mb-0 ">
                                            <span class="d-flex align-items-center mb-2">
                                                <span class="text-dark pr-1">
                                                    {{ translate('messages.Cash_In_Hand_Overflow') }}
                                                </span>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.If_enabled,_delivery_men_will_be_automatically_suspended_by_the_system_when_their_‘Cash_in_Hand’_limit_is_exceeded.') }}">
                                                    <i class="tio-info text-light-gray"></i>
                                                </span>
                                            </span>
                                            <label
                                                class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                <span class="pr-1 d-flex align-items-center switch--label">
                                                    <span class="line--limit-1">
                                                        {{ translate('messages.Status') }}
                                                    </span>
                                                </span>
                                                <input type="checkbox" data-id="cash_in_hand_overflow"
                                                    data-type="toggle"
                                                    data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                    data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                    data-title-on="{{ translate('Want_to_enable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                    data-title-off="{{ translate('Want_to_disable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                    data-text-on="<p>{{ translate('If_enabled,_delivery_men_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                    data-text-off="<p>{{ translate('If_disabled,_delivery_men_do_not_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                    class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                                    name="cash_in_hand_overflow_delivery_man" id="cash_in_hand_overflow"
                                                    {{ $cash_in_hand_overflow == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        @php($dm_max_cash_in_hand = Helpers::get_business_settings('dm_max_cash_in_hand') )
                                        <div class="form-label mb-0">
                                            <label class="d-flex text-capitalize" for="dm_max_cash_in_hand">
                                                <span class="line--limit-1">
                                                    {{translate('Delivery_Man_Maximum_Cash_in_Hand')}}
                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                </span>
                                                <span data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{translate('Deliveryman_can_not_accept_any_orders_when_the_Cash_In_Hand_limit_exceeds_and_must_deposit_the_amount_to_the_admin_before_accepting_new_orders')}}"
                                                    class="input-label-secondary"><i
                                                        class="tio-info text-light-gray"></i></span>
                                            </label>
                                            <input type="number" name="dm_max_cash_in_hand" class="form-control"
                                                id="dm_max_cash_in_hand" min="0" step=".001"
                                                value="{{ $dm_max_cash_in_hand ?? '' }}" {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                        </div>
                                    </div>



                                    <div class="col-sm-6 col-lg-4">
                                        @php($min_amount_to_pay_dm = Helpers::get_business_settings('min_amount_to_pay_dm')  )
                                        <div class="form-label mb-0">
                                            <label class="text-capitalize" for="min_amount_to_pay_dm">
                                                <span>
                                                    {{ translate('Minimum_Amount_To_Pay') }}
                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})

                                                </span>

                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('Enter_the_minimum_cash_amount_delivery_men_can_pay') }}"><i
                                                        class="tio-info text-light-gray"></i></span>
                                            </label>
                                            <input type="number" name="min_amount_to_pay_dm" class="form-control"
                                                id="min_amount_to_pay_dm" min="0" step=".001"
                                                value="{{ $min_amount_to_pay_dm ?? '' }}" {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    @php($dm_loyality_point_status = Helpers::get_business_settings('dm_loyality_point_status')  )
                    @php($dm_loyality_point_per_order = Helpers::get_business_settings('dm_loyality_point_per_order')  )
                    @php($dm_loyality_point_conversion_rate = Helpers::get_business_settings('dm_loyality_point_conversion_rate')  )
                    @php($dm_min_loyality_point_to_convert = Helpers::get_business_settings('dm_min_loyality_point_to_convert')  )

                    <div class="card mt-20 card-container">
                        <div class="card-body">
                            <div
                                class="d-flex align-items-center justify-content-between gap-2 flex-sm-nowrap flex-wrap">
                                <div>
                                    <h4 class="mb-1">{{translate('Loyalty Point')}}</h4>
                                    <p class="fs-12 m-0">
                                        {{translate('If enabled, deliverymen will earn a certain number of points for each successful delivery.')}}
                                    </p>
                                </div>
                                <div
                                    class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-3">
                                    <div
                                        class="view_toggle_btn fz--14px info-dark cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                                        {{ translate('messages.view') }}
                                        <i class="tio-chevron-down fs-22"></i>
                                    </div>
                                    <div class="mb-0">
                                        <label class="toggle-switch toggle-switch-sm mb-0">
                                            <input type="checkbox" data-type="toggle" class="status toggle-switch-input"
                                                name="dm_loyality_point_status" id="dm_loyality_point_status" value="1"
                                                {{ $dm_loyality_point_status == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text mb-0">
                                                <span class="toggle-switch-indicator">
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-details-body {{ !$dm_loyality_point_status ? 'd-none' : '' }} ">
                                <div class="bg-light2  rounded p-xxl-20 p-3 mt-20">
                                    <div class="row g-3">
                                        <div class="col-sm-6 col-lg-4">
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="dm_loyality_point_per_order">
                                                    <div class="d-flex align-items-center">
                                                        <span
                                                            class="line--limit-1 flex-grow pr-1">{{ translate('Loyalty Point Earn Per Order') }}
                                                        </span>
                                                    </div>
                                                </label>
                                                <input type="number" name="dm_loyality_point_per_order"
                                                    class="form-control" min="0" max="9999999999"
                                                    id="dm_loyality_point_per_order" placeholder="1"
                                                    value="{{ $dm_loyality_point_per_order ?? ''}}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="dm_loyality_point_conversion_rate">
                                                    <div class="d-flex align-items-center">
                                                        <span
                                                            class="line--limit-1 flex-grow pr-1">{{ \App\CentralLogics\Helpers::currency_symbol() }}
                                                            {{ translate('1.00 Equivalent To Points') }} </span>
                                                    </div>
                                                </label>
                                                <input type="number" name="dm_loyality_point_conversion_rate" min="0"
                                                    max="999999999" class="form-control"
                                                    id="dm_loyality_point_conversion_rate" placeholder="100"
                                                    value="{{ $dm_loyality_point_conversion_rate ?? ''}}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="dm_min_loyality_point_to_convert">
                                                    <div class="d-flex align-items-center">
                                                        <span
                                                            class="line--limit-1 flex-grow pr-1">{{ translate('Minimum Point Required To Convert') }}
                                                        </span>
                                                    </div>
                                                </label>
                                                <input type="number" name="dm_min_loyality_point_to_convert" min="0"
                                                    max="999999999" class="form-control"
                                                    id="dm_min_loyality_point_to_convert" placeholder="200"
                                                    value="{{ $dm_min_loyality_point_to_convert ?? '' }}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    @php($dm_referal_status = Helpers::get_business_settings('dm_referal_status')  )
                    @php($dm_referal_amount = Helpers::get_business_settings('dm_referal_amount')  )
                    @php($dm_referal_bonus = Helpers::get_business_settings('dm_referal_bonus')  )

                    <div class="card mt-20 card-container">
                        <div class="card-body">
                            <div
                                class="d-flex align-items-center justify-content-between gap-2 flex-sm-nowrap flex-wrap">
                                <div>
                                    <h4 class="mb-1">{{translate('Deliveryman Referral Earning Settings')}}</h4>
                                    <p class="fs-12 m-0">
                                        {{translate('Allow Drivers to refer your app to friends and family using a unique code and earn rewards.')}}
                                    </p>
                                </div>
                                <div
                                    class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-3">
                                    <div
                                        class="view_toggle_btn fz--14px info-dark cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                                        {{ translate('messages.view') }}
                                        <i class="tio-chevron-down fs-22"></i>
                                    </div>
                                    <div class="mb-0">
                                        <label class="toggle-switch toggle-switch-sm mb-0">
                                            <input type="checkbox" data-type="toggle" class="status toggle-switch-input"
                                                name="dm_referal_status" id="dm_referal_status" value="1" {{ $dm_referal_status == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text mb-0">
                                                <span class="toggle-switch-indicator">
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-details-body {{ !$dm_referal_status ? 'd-none' : '' }}">
                                <div class="bg-light2 d-flex flex-column gap-4 rounded p-xxl-20 p-3 mt-20">
                                    <div class="row g-3">
                                        <div class="col-md-6 col-lg-4">
                                            <div>
                                                <h4 class="mb-1">{{translate('Who Share the Code')}}</h4>
                                                <p class="fs-12 m-0">
                                                    {{translate('Set the reward amount that drivers will earn for each successful referral. The reward will be given to the person who uses the referral code during signup and completes their first order.')}}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-8">
                                            <div class="bg-white rounded p-xxl-20 p-2">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize" for="dm_referal_amount">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ translate('Earning Per Referral') }}
                                                                ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                                <span class="text-danger">*</span> </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_referal_amount" min="0"
                                                        max="999999999" step="0.001" class="form-control "
                                                        id="dm_referal_amount" placeholder="100"
                                                        value="{{ $dm_referal_amount ?? '' }}" {{ $dm_referal_status ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6 col-lg-4">
                                            <div>
                                                <h4 class="mb-1">{{translate('Who Use the Code')}}</h4>
                                                <p class="fs-12 m-0">
                                                    {{translate('Set the reward amount that drivers receive when signing up with a referral code & completes first order')}}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-8">
                                            <div class="bg-white rounded p-xxl-20 p-2">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize" for="dm_referal_bonus">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ translate('Bonus In Wallet') }}
                                                                ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                                <span class="text-danger">*</span> </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_referal_bonus" min="0" max="999999999"
                                                        step="0.001" class="form-control " id="dm_referal_bonus"
                                                        placeholder="100" value="{{ $dm_referal_bonus ?? ''}}" {{ $dm_referal_status ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-0 footer-sticky">
            <div class="container-fluid">
                <div class="btn--container justify-content-end py-3">
                    <button type="reset" id="reset_btn"
                        class="btn min-w-120px btn--reset location-reload">{{ translate('messages.reset') }}</button>
                    <button type="submit" id="submit"
                        class="btn min-w-120px btn--primary">{{ translate('messages.save_information') }}</button>
                </div>
            </div>
        </div>
    </form>
    @extends('layouts.admin.app')

    @section('title', translate('messages.delivery_man_settings'))


    @section('content')
    @php use App\CentralLogics\Helpers; @endphp
    <div class="content">
        <form action="{{ route('admin.business-settings.update-dm') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex align-items-center justify-content-between gap-1 w-100">
                        <h1 class="page-header-title mr-3">
                            <span class="page-header-icon">
                                <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                            </span>
                            <span>
                                {{translate('business_setup')}}
                            </span>
                        </h1>
                        @if (!(Request::is('admin/business-settings/language') || Request::is('admin/business-settings/business-setup/refund-settings') || Request::is('admin/business-settings/business-setup/automated-message')))
                            <div class="d-flex flex-wrap justify-content-end align-items-center flex-grow-1">
                                <div class="blinkings active">
                                    <i class="tio-info-outined"></i>
                                    <div class="business-notes">
                                        <h6><img src="{{asset('/public/assets/admin/img/notes.png')}}" alt="">
                                            {{translate('Note')}}</h6>
                                        <div>
                                            @if (Request::is('admin/business-settings/business-setup/refund-settings'))
                                                {{ translate('messages.*If_the_Admin_enables_the_‘Refund_Request_Mode’,_customers_can_request_a_refund.') }}
                                            @else
                                                {{translate('messages.don’t_forget_to_click_the_‘Save Information’_button_below_to_save_changes.')}}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @include('admin-views.business-settings.partials.nav-menu')
                </div>
                <!-- Page Header -->

                <!-- End Page Header -->

                <div class="row g-2">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="rounded p-xxl-20 p-3 bg-light2">

                                    <div class="row g-3">
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_tips_status = Helpers::get_business_settings('dm_tips_status'))
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Tips_for_Deliveryman') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.Customer_can_give_tips_to_deliveryman_during_checkout_from_the_customer_app_&_website._From_this,_admin_has_no_commission.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="line--limit-1 switch--label">
                                                        {{ translate('messages.Status') }}
                                                    </span>
                                                    <input type="checkbox" data-id="dm_tips_status" data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/dm-tips-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/dm-tips-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_Customers_can_give_tips_to_a_deliveryman_during_checkout.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_Tips_for_Deliveryman_feature_will_be_hidden_from_the_Customer_App_and_Website.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="dm_tips_status" id="dm_tips_status" {{ $dm_tips_status == '1' ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($show_dm_earning = Helpers::get_business_settings('show_dm_earning')  )
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Show Earnings in App') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.With_this_feature,_Deliverymen_can_see_their_earnings_on_a_specific_order_while_accepting_it.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="show_dm_earning" data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_Deliverymen_can_see_their_earning_per_order_request_from_the_Order_Details_page_in_the_Deliveryman_App.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_feature_will_be_hidden_from_the_Deliveryman_App.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="show_dm_earning" id="show_dm_earning" {{ $show_dm_earning == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">

                                            @php($toggle_dm_registration = Helpers::get_business_settings('toggle_dm_registration') )
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.dm_self_registration') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.With_this_feature,_deliverymen_can_register_themselves_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page._The_admin_will_receive_an_email_notification_and_can_accept_or_reject_the_request.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('messages.Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="dm_self_registration1"
                                                        data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_users_can_register_as_Deliverymen_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="toggle_dm_registration"
                                                        id="dm_self_registration1" {{ $toggle_dm_registration == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_maximum_orders = Helpers::get_business_settings('dm_maximum_orders')   )
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize" for="dm_maximum_orders">
                                                    <div class="d-flex align-items-center">
                                                        <span
                                                            class="line--limit-1 flex-grow pr-1">{{ translate('Maximum Assigned Order Limit') }}
                                                        </span>
                                                        <span class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('messages.Set_the_maximum_order_limit_a_Deliveryman_can_take_at_a_time.') }}">
                                                            <i class="tio-info text-light-gray"></i>
                                                        </span>
                                                    </div>
                                                </label>
                                                <input type="number" name="dm_maximum_orders" class="form-control"
                                                    id="dm_maximum_orders" min="1" value="{{ $dm_maximum_orders ?? 1 }}"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($canceled_by_deliveryman = Helpers::get_business_settings('canceled_by_deliveryman'))
                                            <div class="form-group mb-0">
                                                <label
                                                    class="input-label text-capitalize d-flex align-items-center"><span
                                                        class="line--limit-1 pr-1">{{ translate('messages.Can_A_Deliveryman_Cancel_Order?') }}</span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.Admin_can_enable/disable_Deliveryman’s_order_cancellation_option_in_the_respective_app.') }}"><i
                                                            class="tio-info text-light-gray"></i></span></label>
                                                <div class="resturant-type-group border">
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="1"
                                                            name="canceled_by_deliveryman" id="canceled_by_deliveryman"
                                                            {{ $canceled_by_deliveryman == 1 ? 'checked' : '' }}>
                                                        <span class="form-check-label">
                                                            {{ translate('yes') }}
                                                        </span>
                                                    </label>
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="0"
                                                            name="canceled_by_deliveryman" id="canceled_by_deliveryman2"
                                                            {{ $canceled_by_deliveryman == 0 ? 'checked' : '' }}>
                                                        <span class="form-check-label">
                                                            {{ translate('no') }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_picture_upload_status = Helpers::get_business_settings('dm_picture_upload_status'))
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Take_Picture_For_Completing_Delivery') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.If_enabled,_deliverymen_will_see_an_option_to_take_pictures_of_the_delivered_products_when_he_swipes_the_delivery_confirmation_slide.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('messages.Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="dm_picture_upload_status"
                                                        data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_delivery_man_can_upload_order_proof_before_order_delivery.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_delivery_man_app.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="dm_picture_upload_status"
                                                        id="dm_picture_upload_status" {{ $dm_picture_upload_status == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>




                                        <div class="col-sm-6 col-lg-4">
                                            @php($cash_in_hand_overflow = Helpers::get_business_settings('cash_in_hand_overflow_delivery_man'))
                                            <div class="form-label  mb-0 ">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Cash_In_Hand_Overflow') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.If_enabled,_delivery_men_will_be_automatically_suspended_by_the_system_when_their_‘Cash_in_Hand’_limit_is_exceeded.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('messages.Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="cash_in_hand_overflow"
                                                        data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                        data-title-on="{{ translate('Want_to_enable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                        data-title-off="{{ translate('Want_to_disable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                        data-text-on="<p>{{ translate('If_enabled,_delivery_men_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                        data-text-off="<p>{{ translate('If_disabled,_delivery_men_do_not_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="cash_in_hand_overflow_delivery_man"
                                                        id="cash_in_hand_overflow" {{ $cash_in_hand_overflow == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_max_cash_in_hand = Helpers::get_business_settings('dm_max_cash_in_hand') )
                                            <div class="form-label mb-0">
                                                <label class="d-flex text-capitalize" for="dm_max_cash_in_hand">
                                                    <span class="line--limit-1">
                                                        {{translate('Delivery_Man_Maximum_Cash_in_Hand')}}
                                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                    </span>
                                                    <span data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{translate('Deliveryman_can_not_accept_any_orders_when_the_Cash_In_Hand_limit_exceeds_and_must_deposit_the_amount_to_the_admin_before_accepting_new_orders')}}"
                                                        class="input-label-secondary"><i
                                                            class="tio-info text-light-gray"></i></span>
                                                </label>
                                                <input type="number" name="dm_max_cash_in_hand" class="form-control"
                                                    id="dm_max_cash_in_hand" min="0" step=".001"
                                                    value="{{ $dm_max_cash_in_hand ?? '' }}" {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                            </div>
                                        </div>



                                        <div class="col-sm-6 col-lg-4">
                                            @php($min_amount_to_pay_dm = Helpers::get_business_settings('min_amount_to_pay_dm')  )
                                            <div class="form-label mb-0">
                                                <label class="text-capitalize" for="min_amount_to_pay_dm">
                                                    <span>
                                                        {{ translate('Minimum_Amount_To_Pay') }}
                                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})

                                                    </span>

                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('Enter_the_minimum_cash_amount_delivery_men_can_pay') }}"><i
                                                            class="tio-info text-light-gray"></i></span>
                                                </label>
                                                <input type="number" name="min_amount_to_pay_dm" class="form-control"
                                                    id="min_amount_to_pay_dm" min="0" step=".001"
                                                    value="{{ $min_amount_to_pay_dm ?? '' }}" {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        @php($dm_loyality_point_status = Helpers::get_business_settings('dm_loyality_point_status')  )
                        @php($dm_loyality_point_per_order = Helpers::get_business_settings('dm_loyality_point_per_order')  )
                        @php($dm_loyality_point_conversion_rate = Helpers::get_business_settings('dm_loyality_point_conversion_rate')  )
                        @php($dm_min_loyality_point_to_convert = Helpers::get_business_settings('dm_min_loyality_point_to_convert')  )

                        <div class="card mt-20 card-container">
                            <div class="card-body">
                                <div
                                    class="d-flex align-items-center justify-content-between gap-2 flex-sm-nowrap flex-wrap">
                                    <div>
                                        <h4 class="mb-1">{{translate('Loyalty Point')}}</h4>
                                        <p class="fs-12 m-0">
                                            {{translate('If enabled, deliverymen will earn a certain number of points for each successful delivery.')}}
                                        </p>
                                    </div>
                                    <div
                                        class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-3">
                                        <div
                                            class="view_toggle_btn fz--14px info-dark cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                                            {{ translate('messages.view') }}
                                            <i class="tio-chevron-down fs-22"></i>
                                        </div>
                                        <div class="mb-0">
                                            <label class="toggle-switch toggle-switch-sm mb-0">
                                                <input type="checkbox" data-type="toggle"
                                                    class="status toggle-switch-input" name="dm_loyality_point_status"
                                                    id="dm_loyality_point_status" value="1" {{ $dm_loyality_point_status == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text mb-0">
                                                    <span class="toggle-switch-indicator">
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-details-body {{ !$dm_loyality_point_status ? 'd-none' : '' }} ">
                                    <div class="bg-light2  rounded p-xxl-20 p-3 mt-20">
                                        <div class="row g-3">
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                        for="dm_loyality_point_per_order">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ translate('Loyalty Point Earn Per Order') }}
                                                            </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_loyality_point_per_order"
                                                        class="form-control" min="0" max="9999999999"
                                                        id="dm_loyality_point_per_order" placeholder="1"
                                                        value="{{ $dm_loyality_point_per_order ?? ''}}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                        for="dm_loyality_point_conversion_rate">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ \App\CentralLogics\Helpers::currency_symbol() }}
                                                                {{ translate('1.00 Equivalent To Points') }} </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_loyality_point_conversion_rate"
                                                        min="0" max="999999999" class="form-control"
                                                        id="dm_loyality_point_conversion_rate" placeholder="100"
                                                        value="{{ $dm_loyality_point_conversion_rate ?? ''}}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                        for="dm_min_loyality_point_to_convert">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ translate('Minimum Point Required To Convert') }}
                                                            </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_min_loyality_point_to_convert" min="0"
                                                        max="999999999" class="form-control"
                                                        id="dm_min_loyality_point_to_convert" placeholder="200"
                                                        value="{{ $dm_min_loyality_point_to_convert ?? '' }}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        @php($dm_referal_status = Helpers::get_business_settings('dm_referal_status')  )
                        @php($dm_referal_amount = Helpers::get_business_settings('dm_referal_amount')  )
                        @php($dm_referal_bonus = Helpers::get_business_settings('dm_referal_bonus')  )

                        <div class="card mt-20 card-container">
                            <div class="card-body">
                                <div
                                    class="d-flex align-items-center justify-content-between gap-2 flex-sm-nowrap flex-wrap">
                                    <div>
                                        <h4 class="mb-1">{{translate('Deliveryman Referral Earning Settings')}}</h4>
                                        <p class="fs-12 m-0">
                                            {{translate('Allow Drivers to refer your app to friends and family using a unique code and earn rewards.')}}
                                        </p>
                                    </div>
                                    <div
                                        class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-3">
                                        <div
                                            class="view_toggle_btn fz--14px info-dark cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                                            {{ translate('messages.view') }}
                                            <i class="tio-chevron-down fs-22"></i>
                                        </div>
                                        <div class="mb-0">
                                            <label class="toggle-switch toggle-switch-sm mb-0">
                                                <input type="checkbox" data-type="toggle"
                                                    class="status toggle-switch-input" name="dm_referal_status"
                                                    id="dm_referal_status" value="1" {{ $dm_referal_status == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text mb-0">
                                                    <span class="toggle-switch-indicator">
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-details-body {{ !$dm_referal_status ? 'd-none' : '' }}">
                                    <div class="bg-light2 d-flex flex-column gap-4 rounded p-xxl-20 p-3 mt-20">
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-4">
                                                <div>
                                                    <h4 class="mb-1">{{translate('Who Share the Code')}}</h4>
                                                    <p class="fs-12 m-0">
                                                        {{translate('Set the reward amount that drivers will earn for each successful referral. The reward will be given to the person who uses the referral code during signup and completes their first order.')}}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-lg-8">
                                                <div class="bg-white rounded p-xxl-20 p-2">
                                                    <div class="form-group mb-0">
                                                        <label class="form-label text-capitalize"
                                                            for="dm_referal_amount">
                                                            <div class="d-flex align-items-center">
                                                                <span
                                                                    class="line--limit-1 flex-grow pr-1">{{ translate('Earning Per Referral') }}
                                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                                    <span class="text-danger">*</span> </span>
                                                            </div>
                                                        </label>
                                                        <input type="number" name="dm_referal_amount" min="0"
                                                            max="999999999" step="0.001" class="form-control "
                                                            id="dm_referal_amount" placeholder="100"
                                                            value="{{ $dm_referal_amount ?? '' }}" {{ $dm_referal_status ? 'required' : 'readonly' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-4">
                                                <div>
                                                    <h4 class="mb-1">{{translate('Who Use the Code')}}</h4>
                                                    <p class="fs-12 m-0">
                                                        {{translate('Set the reward amount that drivers receive when signing up with a referral code & completes first order')}}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-lg-8">
                                                <div class="bg-white rounded p-xxl-20 p-2">
                                                    <div class="form-group mb-0">
                                                        <label class="form-label text-capitalize"
                                                            for="dm_referal_bonus">
                                                            <div class="d-flex align-items-center">
                                                                <span
                                                                    class="line--limit-1 flex-grow pr-1">{{ translate('Bonus In Wallet') }}
                                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                                    <span class="text-danger">*</span> </span>
                                                            </div>
                                                        </label>
                                                        <input type="number" name="dm_referal_bonus" min="0"
                                                            max="999999999" step="0.001" class="form-control "
                                                            id="dm_referal_bonus" placeholder="100"
                                                            value="{{ $dm_referal_bonus ?? ''}}" {{ $dm_referal_status ? 'required' : 'readonly' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-0 footer-sticky">
                <div class="container-fluid">
                    <div class="btn--container justify-content-end py-3">
                        <button type="reset" id="reset_btn"
                            class="btn min-w-120px btn--reset location-reload">{{ translate('messages.reset') }}</button>
                        <button type="submit" id="submit"
                            class="btn min-w-120px btn--primary">{{ translate('messages.save_information') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @extends('layouts.admin.app')

    @section('title', translate('messages.delivery_man_settings'))


    @section('content')
    @php use App\CentralLogics\Helpers; @endphp
    <div class="content">
        <form action="{{ route('admin.business-settings.update-dm') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex align-items-center justify-content-between gap-1 w-100">
                        <h1 class="page-header-title mr-3">
                            <span class="page-header-icon">
                                <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                            </span>
                            <span>
                                {{translate('business_setup')}}
                            </span>
                        </h1>
                        @if (!(Request::is('admin/business-settings/language') || Request::is('admin/business-settings/business-setup/refund-settings') || Request::is('admin/business-settings/business-setup/automated-message')))
                            <div class="d-flex flex-wrap justify-content-end align-items-center flex-grow-1">
                                <div class="blinkings active">
                                    <i class="tio-info-outined"></i>
                                    <div class="business-notes">
                                        <h6><img src="{{asset('/public/assets/admin/img/notes.png')}}" alt="">
                                            {{translate('Note')}}</h6>
                                        <div>
                                            @if (Request::is('admin/business-settings/business-setup/refund-settings'))
                                                {{ translate('messages.*If_the_Admin_enables_the_‘Refund_Request_Mode’,_customers_can_request_a_refund.') }}
                                            @else
                                                {{translate('messages.don’t_forget_to_click_the_‘Save Information’_button_below_to_save_changes.')}}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @include('admin-views.business-settings.partials.nav-menu')
                </div>
                <!-- Page Header -->

                <!-- End Page Header -->

                <div class="row g-2">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="rounded p-xxl-20 p-3 bg-light2">

                                    <div class="row g-3">
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_tips_status = Helpers::get_business_settings('dm_tips_status'))
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Tips_for_Deliveryman') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.Customer_can_give_tips_to_deliveryman_during_checkout_from_the_customer_app_&_website._From_this,_admin_has_no_commission.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="line--limit-1 switch--label">
                                                        {{ translate('messages.Status') }}
                                                    </span>
                                                    <input type="checkbox" data-id="dm_tips_status" data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/dm-tips-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/dm-tips-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Tips_for_Deliveryman_feature?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_Customers_can_give_tips_to_a_deliveryman_during_checkout.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_Tips_for_Deliveryman_feature_will_be_hidden_from_the_Customer_App_and_Website.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="dm_tips_status" id="dm_tips_status" {{ $dm_tips_status == '1' ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($show_dm_earning = Helpers::get_business_settings('show_dm_earning')  )
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Show Earnings in App') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.With_this_feature,_Deliverymen_can_see_their_earnings_on_a_specific_order_while_accepting_it.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="show_dm_earning" data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Show_Earnings_in_App?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_Deliverymen_can_see_their_earning_per_order_request_from_the_Order_Details_page_in_the_Deliveryman_App.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_feature_will_be_hidden_from_the_Deliveryman_App.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="show_dm_earning" id="show_dm_earning" {{ $show_dm_earning == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">

                                            @php($toggle_dm_registration = Helpers::get_business_settings('toggle_dm_registration') )
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.dm_self_registration') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.With_this_feature,_deliverymen_can_register_themselves_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page._The_admin_will_receive_an_email_notification_and_can_accept_or_reject_the_request.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('messages.Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="dm_self_registration1"
                                                        data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.Deliveryman_Self_Registration?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_users_can_register_as_Deliverymen_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_Customer_App,_Website_or_Deliveryman_App_or_Admin_Landing_Page.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="toggle_dm_registration"
                                                        id="dm_self_registration1" {{ $toggle_dm_registration == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_maximum_orders = Helpers::get_business_settings('dm_maximum_orders')   )
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize" for="dm_maximum_orders">
                                                    <div class="d-flex align-items-center">
                                                        <span
                                                            class="line--limit-1 flex-grow pr-1">{{ translate('Maximum Assigned Order Limit') }}
                                                        </span>
                                                        <span class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('messages.Set_the_maximum_order_limit_a_Deliveryman_can_take_at_a_time.') }}">
                                                            <i class="tio-info text-light-gray"></i>
                                                        </span>
                                                    </div>
                                                </label>
                                                <input type="number" name="dm_maximum_orders" class="form-control"
                                                    id="dm_maximum_orders" min="1" value="{{ $dm_maximum_orders ?? 1 }}"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($canceled_by_deliveryman = Helpers::get_business_settings('canceled_by_deliveryman'))
                                            <div class="form-group mb-0">
                                                <label
                                                    class="input-label text-capitalize d-flex align-items-center"><span
                                                        class="line--limit-1 pr-1">{{ translate('messages.Can_A_Deliveryman_Cancel_Order?') }}</span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.Admin_can_enable/disable_Deliveryman’s_order_cancellation_option_in_the_respective_app.') }}"><i
                                                            class="tio-info text-light-gray"></i></span></label>
                                                <div class="resturant-type-group border">
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="1"
                                                            name="canceled_by_deliveryman" id="canceled_by_deliveryman"
                                                            {{ $canceled_by_deliveryman == 1 ? 'checked' : '' }}>
                                                        <span class="form-check-label">
                                                            {{ translate('yes') }}
                                                        </span>
                                                    </label>
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="0"
                                                            name="canceled_by_deliveryman" id="canceled_by_deliveryman2"
                                                            {{ $canceled_by_deliveryman == 0 ? 'checked' : '' }}>
                                                        <span class="form-check-label">
                                                            {{ translate('no') }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_picture_upload_status = Helpers::get_business_settings('dm_picture_upload_status'))
                                            <div class="form-group mb-0">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Take_Picture_For_Completing_Delivery') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.If_enabled,_deliverymen_will_see_an_option_to_take_pictures_of_the_delivered_products_when_he_swipes_the_delivery_confirmation_slide.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('messages.Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="dm_picture_upload_status"
                                                        data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/dm-self-reg-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/dm-self-reg-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.picture_upload_before_complete?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_delivery_man_can_upload_order_proof_before_order_delivery.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_this_feature_will_be_hidden_from_the_delivery_man_app.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="dm_picture_upload_status"
                                                        id="dm_picture_upload_status" {{ $dm_picture_upload_status == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>




                                        <div class="col-sm-6 col-lg-4">
                                            @php($cash_in_hand_overflow = Helpers::get_business_settings('cash_in_hand_overflow_delivery_man'))
                                            <div class="form-label  mb-0 ">
                                                <span class="d-flex align-items-center mb-2">
                                                    <span class="text-dark pr-1">
                                                        {{ translate('messages.Cash_In_Hand_Overflow') }}
                                                    </span>
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.If_enabled,_delivery_men_will_be_automatically_suspended_by_the_system_when_their_‘Cash_in_Hand’_limit_is_exceeded.') }}">
                                                        <i class="tio-info text-light-gray"></i>
                                                    </span>
                                                </span>
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('messages.Status') }}
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="cash_in_hand_overflow"
                                                        data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                        data-title-on="{{ translate('Want_to_enable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                        data-title-off="{{ translate('Want_to_disable') }} <strong>{{ translate('Cash_In_Hand_Overflow') }}</strong>?"
                                                        data-text-on="<p>{{ translate('If_enabled,_delivery_men_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                        data-text-off="<p>{{ translate('If_disabled,_delivery_men_do_not_have_to_provide_collected_cash_by_themselves.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1" name="cash_in_hand_overflow_delivery_man"
                                                        id="cash_in_hand_overflow" {{ $cash_in_hand_overflow == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-4">
                                            @php($dm_max_cash_in_hand = Helpers::get_business_settings('dm_max_cash_in_hand') )
                                            <div class="form-label mb-0">
                                                <label class="d-flex text-capitalize" for="dm_max_cash_in_hand">
                                                    <span class="line--limit-1">
                                                        {{translate('Delivery_Man_Maximum_Cash_in_Hand')}}
                                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                    </span>
                                                    <span data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{translate('Deliveryman_can_not_accept_any_orders_when_the_Cash_In_Hand_limit_exceeds_and_must_deposit_the_amount_to_the_admin_before_accepting_new_orders')}}"
                                                        class="input-label-secondary"><i
                                                            class="tio-info text-light-gray"></i></span>
                                                </label>
                                                <input type="number" name="dm_max_cash_in_hand" class="form-control"
                                                    id="dm_max_cash_in_hand" min="0" step=".001"
                                                    value="{{ $dm_max_cash_in_hand ?? '' }}" {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                            </div>
                                        </div>



                                        <div class="col-sm-6 col-lg-4">
                                            @php($min_amount_to_pay_dm = Helpers::get_business_settings('min_amount_to_pay_dm')  )
                                            <div class="form-label mb-0">
                                                <label class="text-capitalize" for="min_amount_to_pay_dm">
                                                    <span>
                                                        {{ translate('Minimum_Amount_To_Pay') }}
                                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }})

                                                    </span>

                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('Enter_the_minimum_cash_amount_delivery_men_can_pay') }}"><i
                                                            class="tio-info text-light-gray"></i></span>
                                                </label>
                                                <input type="number" name="min_amount_to_pay_dm" class="form-control"
                                                    id="min_amount_to_pay_dm" min="0" step=".001"
                                                    value="{{ $min_amount_to_pay_dm ?? '' }}" {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        @php($dm_loyality_point_status = Helpers::get_business_settings('dm_loyality_point_status')  )
                        @php($dm_loyality_point_per_order = Helpers::get_business_settings('dm_loyality_point_per_order')  )
                        @php($dm_loyality_point_conversion_rate = Helpers::get_business_settings('dm_loyality_point_conversion_rate')  )
                        @php($dm_min_loyality_point_to_convert = Helpers::get_business_settings('dm_min_loyality_point_to_convert')  )

                        <div class="card mt-20 card-container">
                            <div class="card-body">
                                <div
                                    class="d-flex align-items-center justify-content-between gap-2 flex-sm-nowrap flex-wrap">
                                    <div>
                                        <h4 class="mb-1">{{translate('Loyalty Point')}}</h4>
                                        <p class="fs-12 m-0">
                                            {{translate('If enabled, deliverymen will earn a certain number of points for each successful delivery.')}}
                                        </p>
                                    </div>
                                    <div
                                        class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-3">
                                        <div
                                            class="view_toggle_btn fz--14px info-dark cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                                            {{ translate('messages.view') }}
                                            <i class="tio-chevron-down fs-22"></i>
                                        </div>
                                        <div class="mb-0">
                                            <label class="toggle-switch toggle-switch-sm mb-0">
                                                <input type="checkbox" data-type="toggle"
                                                    class="status toggle-switch-input" name="dm_loyality_point_status"
                                                    id="dm_loyality_point_status" value="1" {{ $dm_loyality_point_status == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text mb-0">
                                                    <span class="toggle-switch-indicator">
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-details-body {{ !$dm_loyality_point_status ? 'd-none' : '' }} ">
                                    <div class="bg-light2  rounded p-xxl-20 p-3 mt-20">
                                        <div class="row g-3">
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                        for="dm_loyality_point_per_order">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ translate('Loyalty Point Earn Per Order') }}
                                                            </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_loyality_point_per_order"
                                                        class="form-control" min="0" max="9999999999"
                                                        id="dm_loyality_point_per_order" placeholder="1"
                                                        value="{{ $dm_loyality_point_per_order ?? ''}}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                        for="dm_loyality_point_conversion_rate">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ \App\CentralLogics\Helpers::currency_symbol() }}
                                                                {{ translate('1.00 Equivalent To Points') }} </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_loyality_point_conversion_rate"
                                                        min="0" max="999999999" class="form-control"
                                                        id="dm_loyality_point_conversion_rate" placeholder="100"
                                                        value="{{ $dm_loyality_point_conversion_rate ?? ''}}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                        for="dm_min_loyality_point_to_convert">
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="line--limit-1 flex-grow pr-1">{{ translate('Minimum Point Required To Convert') }}
                                                            </span>
                                                        </div>
                                                    </label>
                                                    <input type="number" name="dm_min_loyality_point_to_convert" min="0"
                                                        max="999999999" class="form-control"
                                                        id="dm_min_loyality_point_to_convert" placeholder="200"
                                                        value="{{ $dm_min_loyality_point_to_convert ?? '' }}" {{ $dm_loyality_point_status == 1 ? 'required' : 'readonly' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        @php($dm_referal_status = Helpers::get_business_settings('dm_referal_status')  )
                        @php($dm_referal_amount = Helpers::get_business_settings('dm_referal_amount')  )
                        @php($dm_referal_bonus = Helpers::get_business_settings('dm_referal_bonus')  )

                        <div class="card mt-20 card-container">
                            <div class="card-body">
                                <div
                                    class="d-flex align-items-center justify-content-between gap-2 flex-sm-nowrap flex-wrap">
                                    <div>
                                        <h4 class="mb-1">{{translate('Deliveryman Referral Earning Settings')}}</h4>
                                        <p class="fs-12 m-0">
                                            {{translate('Allow Drivers to refer your app to friends and family using a unique code and earn rewards.')}}
                                        </p>
                                    </div>
                                    <div
                                        class="d-flex flex-sm-nowrap flex-wrap justify-content-end justify-content-end align-items-center gap-3">
                                        <div
                                            class="view_toggle_btn fz--14px info-dark cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                                            {{ translate('messages.view') }}
                                            <i class="tio-chevron-down fs-22"></i>
                                        </div>
                                        <div class="mb-0">
                                            <label class="toggle-switch toggle-switch-sm mb-0">
                                                <input type="checkbox" data-type="toggle"
                                                    class="status toggle-switch-input" name="dm_referal_status"
                                                    id="dm_referal_status" value="1" {{ $dm_referal_status == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text mb-0">
                                                    <span class="toggle-switch-indicator">
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-details-body {{ !$dm_referal_status ? 'd-none' : '' }}">
                                    <div class="bg-light2 d-flex flex-column gap-4 rounded p-xxl-20 p-3 mt-20">
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-4">
                                                <div>
                                                    <h4 class="mb-1">{{translate('Who Share the Code')}}</h4>
                                                    <p class="fs-12 m-0">
                                                        {{translate('Set the reward amount that drivers will earn for each successful referral. The reward will be given to the person who uses the referral code during signup and completes their first order.')}}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-lg-8">
                                                <div class="bg-white rounded p-xxl-20 p-2">
                                                    <div class="form-group mb-0">
                                                        <label class="form-label text-capitalize"
                                                            for="dm_referal_amount">
                                                            <div class="d-flex align-items-center">
                                                                <span
                                                                    class="line--limit-1 flex-grow pr-1">{{ translate('Earning Per Referral') }}
                                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                                    <span class="text-danger">*</span> </span>
                                                            </div>
                                                        </label>
                                                        <input type="number" name="dm_referal_amount" min="0"
                                                            max="999999999" step="0.001" class="form-control "
                                                            id="dm_referal_amount" placeholder="100"
                                                            value="{{ $dm_referal_amount ?? '' }}" {{ $dm_referal_status ? 'required' : 'readonly' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-4">
                                                <div>
                                                    <h4 class="mb-1">{{translate('Who Use the Code')}}</h4>
                                                    <p class="fs-12 m-0">
                                                        {{translate('Set the reward amount that drivers receive when signing up with a referral code & completes first order')}}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-lg-8">
                                                <div class="bg-white rounded p-xxl-20 p-2">
                                                    <div class="form-group mb-0">
                                                        <label class="form-label text-capitalize"
                                                            for="dm_referal_bonus">
                                                            <div class="d-flex align-items-center">
                                                                <span
                                                                    class="line--limit-1 flex-grow pr-1">{{ translate('Bonus In Wallet') }}
                                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                                    <span class="text-danger">*</span> </span>
                                                            </div>
                                                        </label>
                                                        <input type="number" name="dm_referal_bonus" min="0"
                                                            max="999999999" step="0.001" class="form-control "
                                                            id="dm_referal_bonus" placeholder="100"
                                                            value="{{ $dm_referal_bonus ?? ''}}" {{ $dm_referal_status ? 'required' : 'readonly' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-0 footer-sticky">
                <div class="container-fluid">
                    <div class="btn--container justify-content-end py-3">
                        <button type="reset" id="reset_btn"
                            class="btn min-w-120px btn--reset location-reload">{{ translate('messages.reset') }}</button>
                        <button type="submit" id="submit"
                            class="btn min-w-120px btn--primary">{{ translate('messages.save_information') }}</button>
                    </div>
                </div>
            </div>
        </form>

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
            <form action="{{ route('admin.business-settings.update-store') }}" method="post"
                enctype="multipart/form-data">
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
                                            <label class="input-label text-capitalize d-flex alig-items-center"><span
                                                    class="line--limit-1">{{ translate('messages.Can_a_Vendor_Cancel_Order?') }}
                                                </span><span class="input-label-secondary text--title"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.Admin_can_enable/disable_Vendor’s_order_cancellation_option.') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span></label>
                                            <div class="restaurant-type-group border">
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input" type="radio" value="1"
                                                        name="canceled_by_store" id="canceled_by_store" {{ $canceled_by_store == 1 ? 'checked' : '' }}>
                                                    <span class="form-check-label">
                                                        {{ translate('yes') }}
                                                    </span>
                                                </label>
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input" type="radio" value="0"
                                                        name="canceled_by_store" id="canceled_by_store2" {{ $canceled_by_store == 0 ? 'checked' : '' }}>
                                                    <span class="form-check-label">
                                                        {{ translate('no') }}
                                                    </span>
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
                                                    <span class="line--limit-1">
                                                        {{ translate('messages.Vendor_self_registration') }}
                                                    </span>
                                                    <span class="form-label-secondary text-danger d-flex"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('messages.A_vendor_can_send_a_registration_request_through_their_vendor_or_customer.') }}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="{{ translate('messages.vendor_self_registration') }}">
                                                        *
                                                    </span>
                                                </span>
                                                <input type="checkbox" data-id="store_self_registration1"
                                                    data-type="toggle"
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
                                                    <span class="line--limit-1">
                                                        {{translate('messages.Product_Gallery') }}
                                                    </span>
                                                    <span class="form-label-secondary text-danger d-flex"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('messages.If_you_enable_this,_any_vendor_can_duplicate_product_and_create_a_new_product_by_use_this.')}}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
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
                                        class="col-sm-6 col-lg-4 {{ $product_gallery == 1 ? ' ' : 'd-none' }}  access_all_products">
                                        @php($access_all_products = \App\Models\BusinessSetting::where('key', 'access_all_products')->first()?->value ?? 0)
                                        <div class="form-group mb-0">
                                            <label
                                                class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                <span class="pr-1 d-flex align-items-center switch--label">
                                                    <span class="line--limit-1">
                                                        {{translate('messages.access_all_products') }}
                                                    </span>
                                                    <span class="form-label-secondary text-danger d-flex"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('messages.If_you_enable_this_vendors_can_access_all_products_of_other_vendors.')}}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
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
                                                    <span class="line--limit-1">
                                                        {{translate('messages.Need_Approval_for_Products') }}
                                                    </span>
                                                    <span class="form-label-secondary text-danger d-flex"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('messages.If_enabled,_this_option_to_require_admin_approval_for_products_to_be_displayed_on_the_user_side.')}}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="{{ translate('messages.customer_verification_toggle') }}">
                                                        *
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
                                                    <span class="line--limit-1">
                                                        {{ translate('Vendor_Can_Reply_Review') }}
                                                    </span>
                                                    <span class="form-label-secondary text-danger d-flex"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('If enabled, vendors can actively engage with the customers by responding to the reviews left for their orders') }}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
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
                                <div class="mt-4  mb-4 access_product_approval">
                                    <label class="mb-2 input-label text-capitalize d-flex alig-items-center" for="">
                                        {{ translate('Need_Approval_When') }}</label>
                                    <div class="justify-content-between border form-control">
                                        <div class="form-check form-check-inline mx-4  ">
                                            <input class="mx-2 form-check-input" type="checkbox" {{  data_get($product_approval_datas, 'Add_new_product', null) == 1 ? 'checked' : '' }} id="inlineCheckbox1" value="1" name="Add_new_product"
                                                {{  $product_approval == 1 ? ' ' : 'disabled'}}>
                                            <label class=" form-check-label"
                                                for="inlineCheckbox1">{{ translate('Add_new_product') }}</label>
                                        </div>
                                        <div class="form-check form-check-inline mx-4  ">
                                            <input class="mx-2 form-check-input" type="checkbox" {{  data_get($product_approval_datas, 'Update_product_price', null) == 1 ? 'checked' : '' }} id="inlineCheckbox2" value="1"
                                                name="Update_product_price" {{  $product_approval == 1 ? ' ' : 'disabled'}}>
                                            <label class=" form-check-label"
                                                for="inlineCheckbox2">{{ translate('Update_product_price') }}</label>
                                        </div>
                                        <div class="form-check form-check-inline mx-4  ">
                                            <input class="mx-2 form-check-input" type="checkbox" {{  data_get($product_approval_datas, 'Update_product_variation', null) == 1 ? 'checked' : '' }} id="inlineCheckbox3" value="1"
                                                name="Update_product_variation" {{  $product_approval == 1 ? ' ' : 'disabled'}}>
                                            <label class=" form-check-label"
                                                for="inlineCheckbox3">{{ translate('Update_product_variation') }}</label>
                                        </div>
                                        <div class="form-check form-check-inline mx-4  ">
                                            <input class="mx-2 form-check-input" type="checkbox" {{  data_get($product_approval_datas, 'Update_anything_in_product_details', null) == 1 ? 'checked' : '' }} id="inlineCheckbox4" value="1"
                                                name="Update_anything_in_product_details" {{  $product_approval == 1 ? ' ' : 'disabled'}}>
                                            <label class=" form-check-label"
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
                                                    <span class="line--limit-1">
                                                        {{ translate('messages.Cash_In_Hand_Overflow') }}
                                                    </span>
                                                    <span class="form-label-secondary text-danger d-flex"
                                                        data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('If_enabled,_vendors_will_be_automatically_suspended_by_the_system_when_their_‘Cash_in_Hand’_limit_is_exceeded.') }}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="{{ translate('messages.cash_in_hand_overflow') }}"> *
                                                    </span>
                                                </span>
                                                <input type="checkbox" data-id="cash_in_hand_overflow"
                                                    data-type="toggle"
                                                    data-image-on="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-on.png') }}"
                                                    data-image-off="{{ asset('/public/assets/admin/img/modal/show-earning-in-apps-off.png') }}"
                                                    data-title-on="{{translate('Want_to_enable')}} <strong>{{translate('Cash_In_Hand_Overflow')}}</strong>"
                                                    data-title-off="{{translate('Want_to_disable')}} <strong>{{translate('Cash_In_Hand_Overflow')}}</strong> "
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
                                            <label class=" input-label text-capitalize"
                                                for="cash_in_hand_overflow_store_amount">
                                                <span>
                                                    {{ translate('Maximum_Amount_to_Hold_Cash_in_Hand') }}
                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                </span>

                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('Enter_the_maximum_cash_amount_vendors_can_hold._If_this_number_exceeds,_vendors_will_be_suspended_and_not_receive_any_orders.') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.dm_cancel_order_hint') }}"></span>
                                            </label>
                                            <input type="number" name="cash_in_hand_overflow_store_amount"
                                                class="form-control" id="cash_in_hand_overflow_store_amount" min="0"
                                                step=".001"
                                                value="{{ $cash_in_hand_overflow_store_amount ? $cash_in_hand_overflow_store_amount->value : '' }}"
                                                {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                        </div>
                                    </div>


                                    <div class="col-lg-4 col-sm-6">
                                        @php($min_amount_to_pay_store = \App\Models\BusinessSetting::where('key', 'min_amount_to_pay_store')->first())
                                        <div class="form-group mb-0">
                                            <label class=" input-label text-capitalize" for="min_amount_to_pay_store">
                                                <span>
                                                    {{ translate('Minimum_Amount_To_Pay') }}
                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})

                                                </span>

                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('Enter_the_minimum_cash_amount_vendors_can_pay') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.dm_cancel_order_hint') }}"></span>
                                            </label>
                                            <input type="number" name="min_amount_to_pay_store" class="form-control"
                                                id="min_amount_to_pay_store" min="0" step=".001"
                                                value="{{ $min_amount_to_pay_store ? $min_amount_to_pay_store->value : '' }}"
                                                {{ $cash_in_hand_overflow == 1 ? 'required' : 'readonly' }}>
                                        </div>
                                    </div>
                                </div>

                                <div class="btn--container justify-content-end mt-20">
                                    <button type="reset"
                                        class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                    <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                        class="btn btn--primary call-demo">{{ translate('save_information') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </form>

            {{-- ====== Debit Reasons for Delivery Man ====== --}}
            <div class="mt-4">
                <h4 class="card-title mb-3">
                    <i class="tio-remove-from-trash mr-1"></i>
                    {{ translate('Debit Reasons for Delivery Man') }}
                </h4>
                <div class="card">
                    <div class="card-body">

                        {{-- Add Form --}}
                        <form action="{{ route('admin.business-settings.debit-deliveryman-reasons.store') }}"
                            method="post">
                            @csrf
                            @php($dm_language = \App\Models\BusinessSetting::where('key', 'language')->first())
                            @php($dm_language = $dm_language->value ?? null)

                            @if ($dm_language)
                                <div
                                    class="js-nav-scroller tabs-slide-wrap tabs-slide-space position-relative hs-nav-scroller-horizontal">
                                    <ul class="nav nav-tabs tabs-inner nav--tabs mb-4 border-0">
                                        <li class="nav-item">
                                            <a class="nav-link dm_lang_link active" href="#"
                                                id="dm-default-link">{{ translate('Default') }}</a>
                                        </li>
                                        @foreach (json_decode($dm_language) as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link dm_lang_link" href="#"
                                                    id="dm-{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
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
                                {{-- Default language input --}}
                                <div class="col-sm-6 dm_lang_form dm-default-form">
                                    <label for="dm_order_cancellation" class="form-label">
                                        {{ translate('Debit Reason') }} ({{ translate('messages.default') }})
                                    </label>
                                    <input type="text" class="form-control h--45px" name="reason[]"
                                        id="dm_order_cancellation" placeholder="{{ translate('Ex: Cash Shortage') }}">
                                    <input type="hidden" name="lang[]" value="default">
                                </div>

                                @if ($dm_language)
                                    @foreach (json_decode($dm_language) as $lang)
                                        <div class="col-sm-6 d-none dm_lang_form" id="dm-{{ $lang }}-form">
                                            <label for="dm_order_cancellation_{{ $lang }}" class="form-label">
                                                {{ translate('Debit Reason') }} ({{ strtoupper($lang) }})
                                            </label>
                                            <input type="text" class="form-control h--45px" name="reason[]"
                                                id="dm_order_cancellation_{{ $lang }}"
                                                placeholder="{{ translate('Ex:_Item_is_Broken') }}">
                                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                                        </div>
                                    @endforeach
                                @endif

                                {{-- No user_type needed — this table is exclusively for debit reasons --}}
                            </div>

                            <div class="mt-2 text-muted fs-12">
                                {{ translate('These reasons will appear in the Debit Delivery Man form. Add all valid debit reasons here so admins can select them when deducting from a delivery man\'s wallet.') }}
                            </div>

                            <div class="btn--container justify-content-end mt-3 mb-4">
                                <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                    class="btn btn--primary call-demo">{{ translate('Submit') }}</button>
                            </div>
                        </form>

                        {{-- Reasons Table --}}
                        @php($dm_reasons = \App\Models\DebitDeliverymanReason::latest()->paginate(config('default_pagination', 25), ['*'], 'dm_page'))
                        <div class="card">
                            <div class="card-body mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-md-0 mb-3">
                                    <div class="mx-1">
                                        <h5 class="form-label mb-4">
                                            {{ translate('Debit Reason List for Delivery Man') }}
                                        </h5>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <div class="table-responsive datatable-custom">
                                        <table class="table table-borderless table-thead-bordered table-align-middle"
                                            data-hs-datatables-options='{"isResponsive": false,"isShowPaging": false,"paging":false}'>
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="border-0">{{ translate('messages.SL') }}</th>
                                                    <th class="border-0">{{ translate('messages.Reason') }}</th>
                                                    <th class="border-0">{{ translate('messages.status') }}</th>
                                                    <th class="border-0 text-center">{{ translate('messages.action') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($dm_reasons as $dm_key => $dm_reason)
                                                <tr>
                                                    <td>{{ $dm_key + $dm_reasons->firstItem() }}</td>
                                                    <td>
                                                        <span class="d-block font-size-sm text-body"
                                                            title="{{ $dm_reason->reason }}">
                                                            {{ Str::limit($dm_reason->reason, 40, '...') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <label class="toggle-switch toggle-switch-sm"
                                                            for="dmCheckbox{{ $dm_reason->id }}">
                                                            <input type="checkbox"
                                                                data-url="{{ route('admin.business-settings.debit-deliveryman-reasons.status', [$dm_reason->id, $dm_reason->status ? 0 : 1]) }}"
                                                                class="toggle-switch-input redirect-url"
                                                                id="dmCheckbox{{ $dm_reason->id }}" {{ $dm_reason->status ? 'checked' : '' }}>
                                                            <span class="toggle-switch-label">
                                                                <span class="toggle-switch-indicator"></span>
                                                            </span>
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <div class="btn--container justify-content-center">
                                                            <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                                                title="{{ translate('messages.edit') }}"
                                                                data-toggle="modal"
                                                                data-target="#dm_update_reason_{{ $dm_reason->id }}">
                                                                <i class="tio-edit"></i>
                                                            </a>
                                                            <a class="btn btn-sm btn--danger btn-outline-danger action-btn form-alert"
                                                                href="javascript:"
                                                                data-id="dm-debit-reason-{{ $dm_reason->id }}"
                                                                data-message="{{ translate('messages.If_you_want_to_delete_this_reason,_please_confirm_your_decision.') }}"
                                                                title="{{ translate('messages.delete') }}">
                                                                <i class="tio-delete-outlined"></i>
                                                            </a>
                                                            <form
                                                                action="{{ route('admin.business-settings.debit-deliveryman-reasons.destroy', $dm_reason->id) }}"
                                                                method="post" id="dm-debit-reason-{{ $dm_reason->id }}">
                                                                @csrf @method('delete')
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>

                                                {{-- Edit Modal --}}
                                                <div class="modal fade" id="dm_update_reason_{{ $dm_reason->id }}"
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
                                                                action="{{ route('admin.business-settings.debit-deliveryman-reasons.update') }}"
                                                                method="post">
                                                                <div class="modal-body">
                                                                    @csrf
                                                                    @method('put')

                                                                    @php($dm_edit = \App\Models\DebitDeliverymanReason::withoutGlobalScope('translate')->with('translations')->find($dm_reason->id))
                                                                    @php($dm_edit_lang = \App\Models\BusinessSetting::where('key', 'language')->first())
                                                                    @php($dm_edit_lang = $dm_edit_lang->value ?? null)

                                                                    <ul class="nav nav-tabs nav--tabs mb-3 border-0">
                                                                        <li class="nav-item">
                                                                            <a class="nav-link dm-update-lang_link add_active active"
                                                                                href="#"
                                                                                id="dm-edit-default-link">{{ translate('Default') }}</a>
                                                                        </li>
                                                                        @if ($dm_edit_lang)
                                                                            @foreach (json_decode($dm_edit_lang) as $edit_lang)
                                                                                <li class="nav-item">
                                                                                    <a class="nav-link dm-update-lang_link"
                                                                                        href="#"
                                                                                        data-reason-id="{{ $dm_edit->id }}"
                                                                                        id="dm-edit-{{ $edit_lang }}-link">
                                                                                        {{ \App\CentralLogics\Helpers::get_language_name($edit_lang) . '(' . strtoupper($edit_lang) . ')' }}
                                                                                    </a>
                                                                                </li>
                                                                            @endforeach
                                                                        @endif
                                                                    </ul>

                                                                    <input type="hidden" name="reason_id"
                                                                        value="{{ $dm_edit->id }}">

                                                                    <div class="form-group mb-3 add_active_2 dm-update-lang_form"
                                                                        id="dm-edit-default-form_{{ $dm_edit->id }}">
                                                                        <label class="form-label">
                                                                            {{ translate('Debit Reason') }}
                                                                            ({{ translate('messages.default') }})
                                                                        </label>
                                                                        <input class="form-control" name="reason[]"
                                                                            value="{{ $dm_edit?->getRawOriginal('reason') }}"
                                                                            type="text">
                                                                        <input type="hidden" name="lang1[]"
                                                                            value="default">
                                                                    </div>

                                                                    @if ($dm_edit_lang)
                                                                        @foreach (json_decode($dm_edit_lang) as $edit_lang)
                                                                                                                                    <?php
                                                                            $dm_translate = [];
                                                                            if ($dm_edit?->translations) {
                                                                                foreach ($dm_edit->translations as $t) {
                                                                                    if ($t->locale == $edit_lang && $t->key == 'reason') {
                                                                                        $dm_translate[$edit_lang]['reason'] = $t->value;
                                                                                    }
                                                                                }
                                                                            }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ?>
                                                                                                                                    <div class="form-group mb-3 d-none dm-update-lang_form"
                                                                                                                                        id="dm-edit-{{ $edit_lang }}-langform_{{ $dm_edit->id }}">
                                                                                                                                        <label class="form-label">
                                                                                                                                            {{ translate('Debit Reason') }}
                                                                                                                                            ({{ strtoupper($edit_lang) }})
                                                                                                                                        </label>
                                                                                                                                        <input class="form-control" name="reason[]"
                                                                                                                                            placeholder="{{ translate('Ex:_Item_is_Broken') }}"
                                                                                                                                            value="{{ $dm_translate[$edit_lang]['reason'] ?? null }}"
                                                                                                                                            type="text">
                                                                                                                                        <input type="hidden" name="lang1[]"
                                                                                                                                            value="{{ $edit_lang }}">
                                                                                                                                    </div>
                                                                        @endforeach
                                                                    @endif

                                                                    {{-- Always deliveryman for this section --}}
                                                                    <input type="hidden" name="user_type"
                                                                        value="deliveryman">
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
                                </div>
                                {{-- Pagination --}}
                                @if ($dm_reasons->hasPages())
                                    <div class="mt-3 d-flex justify-content-end">
                                        {{ $dm_reasons->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            {{-- ====== End: Debit Reasons for Delivery Man ====== --}}

        </div>

        @endsection


    </div>
    @endsection

    @push('script_2')

        <script>
            "use strict";
            $(document).on('ready', function () {

                function toggleFields(checkbox, fields) {
                    if ($(checkbox).is(':checked')) {
                        $(fields).attr('required', true).removeAttr('readonly');
                    } else {
                        $(fields).attr('required', false).attr('readonly', true);
                    }
                }

                $('#dm_referal_status').on('change', function () {
                    toggleFields(this, '#dm_referal_amount, #dm_referal_bonus');
                }).trigger('change');

                $('#dm_loyality_point_status').on('change', function () {
                    toggleFields(this, '#dm_loyality_point_per_order, #dm_loyality_point_conversion_rate, #dm_min_loyality_point_to_convert');
                }).trigger('change');

            });

        </script>
    @endpush
    @endsection

    @push('script_2')

        <script>
            "use strict";
            $(document).on('ready', function () {

                function toggleFields(checkbox, fields) {
                    if ($(checkbox).is(':checked')) {
                        $(fields).attr('required', true).removeAttr('readonly');
                    } else {
                        $(fields).attr('required', false).attr('readonly', true);
                    }
                }

                $('#dm_referal_status').on('change', function () {
                    toggleFields(this, '#dm_referal_amount, #dm_referal_bonus');
                }).trigger('change');

                $('#dm_loyality_point_status').on('change', function () {
                    toggleFields(this, '#dm_loyality_point_per_order, #dm_loyality_point_conversion_rate, #dm_min_loyality_point_to_convert');
                }).trigger('change');

            });

        </script>
    @endpush
</div>

@endsection

@push('script_2')

    <script>
        "use strict";
        $(document).on('ready', function () {

            function toggleFields(checkbox, fields) {
                if ($(checkbox).is(':checked')) {
                    $(fields).attr('required', true).removeAttr('readonly');
                } else {
                    $(fields).attr('required', false).attr('readonly', true);
                }
            }

            $('#dm_referal_status').on('change', function () {
                toggleFields(this, '#dm_referal_amount, #dm_referal_bonus');
            }).trigger('change');

            $('#dm_loyality_point_status').on('change', function () {
                toggleFields(this, '#dm_loyality_point_per_order, #dm_loyality_point_conversion_rate, #dm_min_loyality_point_to_convert');
            }).trigger('change');

        });

    </script>
@endpush