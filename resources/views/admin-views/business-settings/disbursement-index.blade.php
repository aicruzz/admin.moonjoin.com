@extends('layouts.admin.app')

@section('title', translate('Disbursement Settings'))


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

    @php($store_disbursement_type = \App\Models\BusinessSetting::where('key', 'store_disbursement_type')->first())
    @php($store_disbursement_type = $store_disbursement_type ? $store_disbursement_type->value : 'manual')
    @php($dm_disbursement_type = \App\Models\BusinessSetting::where('key', 'dm_disbursement_type')->first())
    @php($dm_disbursement_type = $dm_disbursement_type ? $dm_disbursement_type->value : 'manual')
    @php($ve_disbursement_type = \App\Models\BusinessSetting::where('key', 've_disbursement_type')->first())
    @php($ve_disbursement_type = $ve_disbursement_type ? $ve_disbursement_type->value : 'manual')
    @php($store_disbursement_command = \App\Models\BusinessSetting::where('key', 'store_disbursement_command')->first())
    @php($store_disbursement_command = $store_disbursement_command ? $store_disbursement_command->value : '')
    @php($dm_disbursement_command = \App\Models\BusinessSetting::where('key', 'dm_disbursement_command')->first())
    @php($dm_disbursement_command = $dm_disbursement_command ? $dm_disbursement_command->value : '')
    @php($ve_disbursement_command = \App\Models\BusinessSetting::where('key', 've_disbursement_command')->first())
    @php($ve_disbursement_command = $ve_disbursement_command ? $ve_disbursement_command->value : '')

    <form action="{{ route('admin.business-settings.update-disbursement') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="row g-2">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        {{-- ===================== SHARED DISBURSEMENT TYPE TOGGLE ===================== --}}
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label text-capitalize d-flex align-items-center">
                                        <span class="line--limit-1">{{ translate('Disbursement request type') }}</span>
                                        <span class="form-label-secondary"
                                              data-toggle="tooltip" data-placement="right"
                                              data-original-title="{{ translate('Choose Manual or Automated disbursement requests. In Automated mode, withdrawal requests are generated automatically. In Manual mode, stores must request withdrawals themselves.') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="{{ translate('Disbursement request type') }}">
                                        </span>
                                    </label>
                                    <div class="restaurant-type-group border">
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="manual"
                                                   name="shared_disbursement_type" id="shared_disbursement_type_manual"
                                                {{ $store_disbursement_type == 'manual' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('Manual') }}</span>
                                        </label>
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="automated"
                                                   name="shared_disbursement_type" id="shared_disbursement_type_automated"
                                                {{ $store_disbursement_type == 'automated' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('Automated') }}</span>
                                        </label>
                                    </div>
                                    {{-- Hidden field mirrors shared toggle for store key --}}
                                    <input type="hidden" name="store_disbursement_type" id="store_disbursement_type_hidden" value="{{ $store_disbursement_type }}">
                                </div>
                            </div>

                            <div class="col-6 both_automated_section {{ $store_disbursement_type == 'manual' ? 'd-none' : '' }}">
                                <div class="text-right mt-4 pt-2">
                                    <button type="button" class="btn btn--primary" data-toggle="modal" data-target="#myModal">{{ translate('Check dependencies') }}</button>
                                </div>
                            </div>
                        </div>

                        {{-- ===================== STORE PANEL ===================== --}}
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">{{ translate('Store') }}</h5>
                            </div>

                            {{-- Store: PHP Path (only shown when automated) --}}
                            <div class="col-6 store_automated_section {{ $store_disbursement_type == 'manual' ? 'd-none' : '' }}">
                                @php($system_php_path = \App\Models\BusinessSetting::where('key', 'system_php_path')->first())
                                @php($system_php_path = $system_php_path ? $system_php_path->value : '')
                                <div class="form-group lang_form default-form">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="system_php_path" class="form-label text-capitalize m-0">
                                            {{ translate('System PHP path') }}
                                            <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                  data-original-title="{{ translate('The location where the PHP executable is installed on the server.') }}">
                                                <i class="tio-info-outined"></i>
                                            </span>
                                        </label>
                                    </div>
                                    <input id="system_php_path" type="text" placeholder="{{ translate('e.g. /usr/bin/php') }}"
                                           class="form-control h--45px" name="system_php_path" value="{{ $system_php_path }}">
                                </div>
                            </div>

                            {{-- Store: Automated Settings --}}
                            <div class="col-12 store_automated_section {{ $store_disbursement_type == 'manual' ? 'd-none' : '' }}">
                                <div class="__bg-F8F9FC-card">
                                    <div class="row">
                                        @php($store_disbursement_time_period = \App\Models\BusinessSetting::where('key', 'store_disbursement_time_period')->first())
                                        @php($store_disbursement_time_period = $store_disbursement_time_period ? $store_disbursement_time_period->value : 'daily')

                                        <div class="{{ $store_disbursement_time_period == 'weekly' ? 'col-6' : 'col-12' }}" id="store_time_period_section">
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="store_disbursement_time_period" class="form-label text-capitalize m-0">
                                                        {{ translate('Disbursement frequency') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('How often disbursement requests are generated: daily, weekly, or monthly.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <select name="store_disbursement_time_period" id="store_disbursement_time_period" class="form-control">
                                                    <option value="daily" {{ $store_disbursement_time_period == 'daily' ? 'selected' : '' }}>{{ translate('messages.daily') }}</option>
                                                    <option value="weekly" {{ $store_disbursement_time_period == 'weekly' ? 'selected' : '' }}>{{ translate('messages.weekly') }}</option>
                                                    <option value="monthly" {{ $store_disbursement_time_period == 'monthly' ? 'selected' : '' }}>{{ translate('messages.monthly') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 {{ $store_disbursement_time_period == 'weekly' ? '' : 'd-none' }}" id="store_week_day_section">
                                            @php($store_disbursement_week_start = \App\Models\BusinessSetting::where('key', 'store_disbursement_week_start')->first())
                                            @php($store_disbursement_week_start = $store_disbursement_week_start ? $store_disbursement_week_start->value : 'saturday')
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="store_disbursement_week_start" class="form-label text-capitalize m-0">
                                                        {{ translate('Week starts on') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The day the weekly disbursement cycle begins. Only applies when weekly frequency is selected.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <select name="store_disbursement_week_start" id="store_disbursement_week_start" class="form-control">
                                                    @foreach(['saturday','sunday','monday','tuesday','wednesday','thursday','friday'] as $day)
                                                        <option value="{{ $day }}" {{ $store_disbursement_week_start == $day ? 'selected' : '' }}>{{ translate('messages.'.$day) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            @php($store_disbursement_create_time = \App\Models\BusinessSetting::where('key', 'store_disbursement_create_time')->first())
                                            @php($store_disbursement_create_time = $store_disbursement_create_time ? $store_disbursement_create_time->value : '')
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="store_disbursement_create_time" class="form-label text-capitalize m-0">
                                                        {{ translate('Generation time') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The time of day when the disbursement request will be generated automatically.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <input type="time" id="store_disbursement_create_time" class="form-control h--45px"
                                                       name="store_disbursement_create_time" value="{{ $store_disbursement_create_time }}">
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            @php($store_disbursement_min_amount = \App\Models\BusinessSetting::where('key', 'store_disbursement_min_amount')->first())
                                            @php($store_disbursement_min_amount = $store_disbursement_min_amount ? $store_disbursement_min_amount->value : '')
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="store_disbursement_min_amount" class="form-label text-capitalize m-0">
                                                        {{ translate('Minimum eligible amount') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The minimum balance required to trigger an automatic disbursement request.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <input id="store_disbursement_min_amount" type="number" placeholder="{{ translate('e.g. 100') }}"
                                                       class="form-control h--45px" min="1" name="store_disbursement_min_amount" value="{{ $store_disbursement_min_amount }}">
                                            </div>
                                        </div>
                                    </div>

                                    @php($store_disbursement_waiting_time = \App\Models\BusinessSetting::where('key', 'store_disbursement_waiting_time')->first())
                                    @php($store_disbursement_waiting_time = $store_disbursement_waiting_time ? $store_disbursement_waiting_time->value : '')
                                    <div class="form-group lang_form default-form">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label for="store_disbursement_waiting_time" class="form-label text-capitalize m-0">
                                                {{ translate('Processing time (days)') }}
                                                <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                      data-original-title="{{ translate('Number of days allowed to complete a disbursement after it has been created.') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <input id="store_disbursement_waiting_time" type="number" placeholder="{{ translate('e.g. 7') }}"
                                               min="1" class="form-control h--45px" name="store_disbursement_waiting_time" value="{{ $store_disbursement_waiting_time }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- ===================== DELIVERY MAN PANEL ===================== --}}
                        <div class="row g-3 mb-2">
                            <div class="col-12">
                                <h5 class="mb-3">{{ translate('Delivery man') }}</h5>
                            </div>

                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label text-capitalize d-flex align-items-center">
                                        <span class="line--limit-1">{{ translate('Disbursement request type') }}</span>
                                        <span class="form-label-secondary"
                                              data-toggle="tooltip" data-placement="right"
                                              data-original-title="{{ translate('Choose Manual or Automated disbursement requests. In Automated mode, withdrawal requests are generated automatically. In Manual mode, delivery men must request withdrawals themselves.') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="{{ translate('Disbursement request type') }}">
                                        </span>
                                    </label>
                                    <div class="restaurant-type-group border">
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="manual"
                                                   name="dm_disbursement_type" id="dm_disbursement_type_manual"
                                                {{ $dm_disbursement_type == 'manual' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('Manual') }}</span>
                                        </label>
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="automated"
                                                   name="dm_disbursement_type" id="dm_disbursement_type_automated"
                                                {{ $dm_disbursement_type == 'automated' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('Automated') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- DM: Automated Settings --}}
                            <div class="col-12 dm_automated_section {{ $dm_disbursement_type == 'manual' ? 'd-none' : '' }}">
                                <div class="__bg-F8F9FC-card">
                                    <div class="row">
                                        @php($dm_disbursement_time_period = \App\Models\BusinessSetting::where('key', 'dm_disbursement_time_period')->first())
                                        @php($dm_disbursement_time_period = $dm_disbursement_time_period ? $dm_disbursement_time_period->value : 'daily')

                                        <div class="{{ $dm_disbursement_time_period == 'weekly' ? 'col-6' : 'col-12' }}" id="dm_time_period_section">
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="dm_disbursement_time_period" class="form-label text-capitalize m-0">
                                                        {{ translate('Disbursement frequency') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('How often disbursement requests are generated: daily, weekly, or monthly.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <select name="dm_disbursement_time_period" id="dm_disbursement_time_period" class="form-control">
                                                    <option value="daily" {{ $dm_disbursement_time_period == 'daily' ? 'selected' : '' }}>{{ translate('messages.daily') }}</option>
                                                    <option value="weekly" {{ $dm_disbursement_time_period == 'weekly' ? 'selected' : '' }}>{{ translate('messages.weekly') }}</option>
                                                    <option value="monthly" {{ $dm_disbursement_time_period == 'monthly' ? 'selected' : '' }}>{{ translate('messages.monthly') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        @php($dm_disbursement_week_start = \App\Models\BusinessSetting::where('key', 'dm_disbursement_week_start')->first())
                                        @php($dm_disbursement_week_start = $dm_disbursement_week_start ? $dm_disbursement_week_start->value : 'saturday')
                                        <div class="col-6 {{ $dm_disbursement_time_period == 'weekly' ? '' : 'd-none' }}" id="dm_week_day_section">
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="dm_disbursement_week_start" class="form-label text-capitalize m-0">
                                                        {{ translate('Week starts on') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The day the weekly disbursement cycle begins. Only applies when weekly frequency is selected.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <select name="dm_disbursement_week_start" id="dm_disbursement_week_start" class="form-control">
                                                    @foreach(['saturday','sunday','monday','tuesday','wednesday','thursday','friday'] as $day)
                                                        <option value="{{ $day }}" {{ $dm_disbursement_week_start == $day ? 'selected' : '' }}>{{ translate('messages.'.$day) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            @php($dm_disbursement_create_time = \App\Models\BusinessSetting::where('key', 'dm_disbursement_create_time')->first())
                                            @php($dm_disbursement_create_time = $dm_disbursement_create_time ? $dm_disbursement_create_time->value : '')
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="dm_disbursement_create_time" class="form-label text-capitalize m-0">
                                                        {{ translate('Generation time') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The time of day when the disbursement request will be generated automatically.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <input id="dm_disbursement_create_time" type="time" class="form-control h--45px"
                                                       name="dm_disbursement_create_time" value="{{ $dm_disbursement_create_time }}">
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            @php($dm_disbursement_min_amount = \App\Models\BusinessSetting::where('key', 'dm_disbursement_min_amount')->first())
                                            @php($dm_disbursement_min_amount = $dm_disbursement_min_amount ? $dm_disbursement_min_amount->value : '')
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="dm_disbursement_min_amount" class="form-label text-capitalize m-0">
                                                        {{ translate('Minimum eligible amount') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The minimum balance required to trigger an automatic disbursement request.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <input id="dm_disbursement_min_amount" type="number" placeholder="{{ translate('e.g. 100') }}"
                                                       class="form-control h--45px" min="1" name="dm_disbursement_min_amount" value="{{ $dm_disbursement_min_amount }}">
                                            </div>
                                        </div>
                                    </div>

                                    @php($dm_disbursement_waiting_time = \App\Models\BusinessSetting::where('key', 'dm_disbursement_waiting_time')->first())
                                    @php($dm_disbursement_waiting_time = $dm_disbursement_waiting_time ? $dm_disbursement_waiting_time->value : '')
                                    <div class="form-group lang_form default-form">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label for="dm_disbursement_waiting_time" class="form-label text-capitalize m-0">
                                                {{ translate('Processing time (days)') }}
                                                <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                      data-original-title="{{ translate('Number of days allowed to complete a disbursement after it has been created.') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <input id="dm_disbursement_waiting_time" type="number" min="1" placeholder="{{ translate('e.g. 7') }}"
                                               class="form-control h--45px" name="dm_disbursement_waiting_time" value="{{ $dm_disbursement_waiting_time }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- ===================== VENDOR EMPLOYEE PANEL ===================== --}}
                        <div class="row g-3 mb-2">
                            <div class="col-12">
                                <h5 class="mb-3">{{ translate('Vendor employee') }}</h5>
                            </div>

                            <div class="col-6">
                                <div class="form-group">
                                    <label class="input-label text-capitalize d-flex align-items-center">
                                        <span class="line--limit-1">{{ translate('Disbursement request type') }}</span>
                                        <span class="form-label-secondary"
                                              data-toggle="tooltip" data-placement="right"
                                              data-original-title="{{ translate('Choose Manual or Automated disbursement requests. In Automated mode, withdrawal requests are generated automatically. In Manual mode, vendor employees must request withdrawals themselves.') }}">
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="{{ translate('Disbursement request type') }}">
                                        </span>
                                    </label>
                                    <div class="restaurant-type-group border">
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="manual"
                                                   name="ve_disbursement_type" id="ve_disbursement_type_manual"
                                                {{ $ve_disbursement_type == 'manual' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('Manual') }}</span>
                                        </label>
                                        <label class="form-check form--check mr-2 mr-md-4">
                                            <input class="form-check-input" type="radio" value="automated"
                                                   name="ve_disbursement_type" id="ve_disbursement_type_automated"
                                                {{ $ve_disbursement_type == 'automated' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ translate('Automated') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 ve_automated_section {{ $ve_disbursement_type == 'manual' ? 'd-none' : '' }}">
                                <div class="__bg-F8F9FC-card">
                                    <div class="row">
                                        @php($ve_disbursement_time_period = \App\Models\BusinessSetting::where('key', 've_disbursement_time_period')->first())
                                        @php($ve_disbursement_time_period = $ve_disbursement_time_period ? $ve_disbursement_time_period->value : 'daily')

                                        <div class="{{ $ve_disbursement_time_period == 'weekly' ? 'col-6' : 'col-12' }}" id="ve_time_period_section">
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="ve_disbursement_time_period" class="form-label text-capitalize m-0">
                                                        {{ translate('Disbursement frequency') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('How often disbursement requests are generated: daily, weekly, or monthly.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <select name="ve_disbursement_time_period" id="ve_disbursement_time_period" class="form-control">
                                                    <option value="daily" {{ $ve_disbursement_time_period == 'daily' ? 'selected' : '' }}>{{ translate('messages.daily') }}</option>
                                                    <option value="weekly" {{ $ve_disbursement_time_period == 'weekly' ? 'selected' : '' }}>{{ translate('messages.weekly') }}</option>
                                                    <option value="monthly" {{ $ve_disbursement_time_period == 'monthly' ? 'selected' : '' }}>{{ translate('messages.monthly') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        @php($ve_disbursement_week_start = \App\Models\BusinessSetting::where('key', 've_disbursement_week_start')->first())
                                        @php($ve_disbursement_week_start = $ve_disbursement_week_start ? $ve_disbursement_week_start->value : 'saturday')
                                        <div class="col-6 {{ $ve_disbursement_time_period == 'weekly' ? '' : 'd-none' }}" id="ve_week_day_section">
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="ve_disbursement_week_start" class="form-label text-capitalize m-0">
                                                        {{ translate('Week starts on') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The day the weekly disbursement cycle begins. Only applies when weekly frequency is selected.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <select name="ve_disbursement_week_start" id="ve_disbursement_week_start" class="form-control">
                                                    @foreach(['saturday','sunday','monday','tuesday','wednesday','thursday','friday'] as $day)
                                                        <option value="{{ $day }}" {{ $ve_disbursement_week_start == $day ? 'selected' : '' }}>{{ translate('messages.'.$day) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            @php($ve_disbursement_create_time = \App\Models\BusinessSetting::where('key', 've_disbursement_create_time')->first())
                                            @php($ve_disbursement_create_time = $ve_disbursement_create_time ? $ve_disbursement_create_time->value : '')
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="ve_disbursement_create_time" class="form-label text-capitalize m-0">
                                                        {{ translate('Generation time') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The time of day when the disbursement request will be generated automatically.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <input id="ve_disbursement_create_time" type="time" class="form-control h--45px"
                                                       name="ve_disbursement_create_time" value="{{ $ve_disbursement_create_time }}">
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            @php($ve_disbursement_min_amount = \App\Models\BusinessSetting::where('key', 've_disbursement_min_amount')->first())
                                            @php($ve_disbursement_min_amount = $ve_disbursement_min_amount ? $ve_disbursement_min_amount->value : '')
                                            <div class="form-group lang_form default-form">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="ve_disbursement_min_amount" class="form-label text-capitalize m-0">
                                                        {{ translate('Minimum eligible amount') }}
                                                        <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                              data-original-title="{{ translate('The minimum balance required to trigger an automatic disbursement request.') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                                <input id="ve_disbursement_min_amount" type="number" placeholder="{{ translate('e.g. 100') }}"
                                                       class="form-control h--45px" min="1" name="ve_disbursement_min_amount" value="{{ $ve_disbursement_min_amount }}">
                                            </div>
                                        </div>
                                    </div>

                                    @php($ve_disbursement_waiting_time = \App\Models\BusinessSetting::where('key', 've_disbursement_waiting_time')->first())
                                    @php($ve_disbursement_waiting_time = $ve_disbursement_waiting_time ? $ve_disbursement_waiting_time->value : '')
                                    <div class="form-group lang_form default-form">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label for="ve_disbursement_waiting_time" class="form-label text-capitalize m-0">
                                                {{ translate('Processing time (days)') }}
                                                <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right"
                                                      data-original-title="{{ translate('Number of days allowed to complete a disbursement after it has been created.') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <input id="ve_disbursement_waiting_time" type="number" min="1" placeholder="{{ translate('e.g. 7') }}"
                                               class="form-control h--45px" name="ve_disbursement_waiting_time" value="{{ $ve_disbursement_waiting_time }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="btn--container justify-content-end">
                            <button type="reset" id="reset_btn" class="btn btn--reset location-reload">{{ translate('messages.reset') }}</button>
                            <button type="submit" id="submit" class="btn btn--primary">{{ translate('messages.save_information') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Dependencies Modal --}}
    <div class="modal" id="myModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center">{{ translate('Cron commands for disbursement') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <span class="text--base">
                            {{ translate('On some servers, the PHP exec function may be disabled, which prevents creating cron jobs programmatically. If that is the case, use the commands below to set up cron jobs manually.') }}
                        </span>
                    </div>
                    <label for="storeDisbursementCommand" class="form-label text-capitalize">{{ translate('Store cron command') }}</label>
                    <div class="input--group input-group mb-3">
                        <input type="text" value="{{ $store_disbursement_command }}" class="form-control" id="storeDisbursementCommand" readonly>
                        <button class="btn btn-primary copy-btn copy-to-clipboard" data-id="storeDisbursementCommand">{{ translate('Copy') }}</button>
                    </div>
                    <label for="dmDisbursementCommand" class="form-label text-capitalize">{{ translate('Delivery man cron command') }}</label>
                    <div class="input--group input-group mb-3">
                        <input type="text" value="{{ $dm_disbursement_command }}" class="form-control" id="dmDisbursementCommand" readonly>
                        <button class="btn btn-primary copy-btn copy-to-clipboard" data-id="dmDisbursementCommand">{{ translate('Copy') }}</button>
                    </div>
                    <label for="veDisbursementCommand" class="form-label text-capitalize">{{ translate('Vendor employee cron command') }}</label>
                    <div class="input--group input-group">
                        <input type="text" value="{{ $ve_disbursement_command }}" class="form-control" id="veDisbursementCommand" readonly>
                        <button class="btn btn-primary copy-btn copy-to-clipboard" data-id="veDisbursementCommand">{{ translate('Copy') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/view-pages/disbursement.js') }}"></script>
    @php($flag = session('disbursement_exec'))
    <script>
        "use strict";

        $(document).on('ready', function () {

            // ── Shared toggle — controls Store panel only ────────────────────
            $('input[name="shared_disbursement_type"]').on('change', function () {
                var val = $(this).val();

                // Keep store hidden field in sync
                $('#store_disbursement_type_hidden').val(val);

                if (val === 'automated') {
                    $('.store_automated_section, .both_automated_section').removeClass('d-none').show();
                } else {
                    $('.store_automated_section, .both_automated_section').addClass('d-none').hide();
                }
            });

            // ── DM radio — controls DM panel independently ───────────────────
            $('input[name="dm_disbursement_type"]').on('change', function () {
                var val = $(this).val();

                if (val === 'automated') {
                    $('.dm_automated_section').removeClass('d-none').show();
                } else {
                    $('.dm_automated_section').addClass('d-none').hide();
                }
            });

            // ── Store: weekly day-of-week visibility ────────────────────────
            $('#store_disbursement_time_period').on('change', function () {
                if ($(this).val() === 'weekly') {
                    $('#store_week_day_section').removeClass('d-none');
                    $('#store_time_period_section').removeClass('col-12').addClass('col-6');
                } else {
                    $('#store_week_day_section').addClass('d-none');
                    $('#store_time_period_section').removeClass('col-6').addClass('col-12');
                }
            });

            // ── DM: weekly day-of-week visibility ───────────────────────────
            $('#dm_disbursement_time_period').on('change', function () {
                if ($(this).val() === 'weekly') {
                    $('#dm_week_day_section').removeClass('d-none');
                    $('#dm_time_period_section').removeClass('col-12').addClass('col-6');
                } else {
                    $('#dm_week_day_section').addClass('d-none');
                    $('#dm_time_period_section').removeClass('col-6').addClass('col-12');
                }
            });

            // ── VE radio — controls VE panel independently ───────────────────
            $('input[name="ve_disbursement_type"]').on('change', function () {
                var val = $(this).val();

                if (val === 'automated') {
                    $('.ve_automated_section').removeClass('d-none').show();
                } else {
                    $('.ve_automated_section').addClass('d-none').hide();
                }
            });

            // ── VE: weekly day-of-week visibility ───────────────────────────
            $('#ve_disbursement_time_period').on('change', function () {
                if ($(this).val() === 'weekly') {
                    $('#ve_week_day_section').removeClass('d-none');
                    $('#ve_time_period_section').removeClass('col-12').addClass('col-6');
                } else {
                    $('#ve_week_day_section').addClass('d-none');
                    $('#ve_time_period_section').removeClass('col-6').addClass('col-12');
                }
            });

            // ── Auto-open modal if session flag set ─────────────────────────
            @if (isset($flag) && $flag)
            $('#myModal').modal('show');
            @endif
        });
    </script>
@endpush