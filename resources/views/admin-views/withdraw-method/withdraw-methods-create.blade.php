@extends('layouts.admin.app')

@section('title',  translate('Create Withdraw Method'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Skeleton Shimmer Loading Animation */
        .bank-skeleton-container {
            width: 100%;
        }
        
        .skeleton-field {
            height: 45px;
            width: 100%;
            background: linear-gradient(90deg, #f2f2f2 25%, #e6e6e6 50%, #f2f2f2 75%);
            background-size: 200% 100%;
            animation: bankShimmer 1.5s infinite linear;
            border-radius: 0.375rem;
        }

        @keyframes bankShimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Clean Error Design */
        .bank-error { 
            color: #dc3545; 
            font-size: 0.875rem; 
            padding: 0.5rem 12px; 
            border: 1px solid #f8d7da;
            background-color: #f8d7da33;
            border-radius: 0.375rem;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 withdraw-header-sticky z-2">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3">
                <h2 class="page-title m-0">
                    <img width="20" src="{{asset('/public/assets/admin/img/withdraw-icon.png')}}" alt="">
                    {{ translate('Create Withdraw Method')}}
                </h2>
                <button class="btn btn--primary" id="add-more-field">
                    <i class="tio-add-circle"></i> {{ translate('messages.Add_New_Field')}}
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <form action="{{route('admin.transactions.withdraw-method.store')}}" method="POST">
                    @csrf
                    <div class="card card-body">
                        <div class="bg-1079801A p--20 rounded">

                            {{-- METHOD NAME — bank dropdown loaded from 9PSB --}}
                            <div class="form-group mb-3">
                                <label class="text-title mb-1">
                                    {{ translate('messages.method_name')}}
                                    <span class="input-label-secondary text-danger">*</span>
                                </label>

                                {{-- Modern Skeleton Loading State --}}
                                <div id="bank-loading" class="bank-skeleton-container">
                                    <div class="skeleton-field"></div>
                                </div>

                                {{-- Error state (hidden by default) --}}
                                <div id="bank-error" class="bank-error d-none">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="tio-warning-outlined mr-1"></i>
                                            {{ translate('Failed to load banks dynamically.') }}
                                        </span>
                                        <a href="#" id="retry-banks" class="btn btn-sm btn-outline-danger py-0 px-2 fw-bold">{{ translate('Retry') }}</a>
                                    </div>
                                    {{-- Fallback manual input if API keeps failing --}}
                                    <input type="text" class="form-control mt-2" name="method_name"
                                           id="method_name_fallback"
                                           placeholder="{{ translate('messages.Ex:_Enter bank name manually')}}" value="">
                                </div>

                                {{-- Bank select (hidden until loaded) --}}
                                <select class="form-control js-select js-select2-custom d-none"
                                        name="method_name"
                                        id="method_name">
                                    <option value="" selected disabled>{{ translate('-- Select Bank --') }}</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-start mt-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input checkbox-theme single-select" type="checkbox"
                                           value="1" name="is_default" id="flexCheckDefaultMethod">
                                    <label class="form-check-label" for="flexCheckDefaultMethod">
                                        {{ translate('Mark this Method as Default')}}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div id="custom-field-section">
                            <div class="card card-body">
                                <div class="bg-1079801A p--20 rounded">
                                    <div class="row gy-2 align-items-center">
                                        <div class="col-md-4 col-12">
                                            <label class="text-title">{{ translate('messages.Input_Field_Type')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                            <select class="form-control js-select js-select2-custom" name="field_type[]" required>
                                                <option value="string">{{ translate('messages.Text')}}</option>
                                                <option value="number">{{ translate('messages.Number')}}</option>
                                                <option value="date">{{ translate('messages.Date')}}</option>
                                                <option value="email">{{ translate('messages.Email')}}</option>
                                                <option value="phone">{{ translate('messages.Phone')}}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-floating">
                                                <label class="text-title">{{ translate('messages.field_name')}} <span
                                                    class="input-label-secondary text-danger">*</span></label>
                                                <input type="text" class="form-control" name="field_name[]"
                                                       placeholder="{{ translate('messages.Ex:_Account_name')}} "
                                                       value="" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <div class="form-floating">
                                                <label class="text-title">{{ translate('messages.placeholder_text')}} <span
                                                    class="input-label-secondary text-danger">*</span></label>
                                                <input type="text" class="form-control" name="placeholder_text[]"
                                                       placeholder="{{ translate('messages.Ex:_John')}} "
                                                       value="" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-12">
                                            <div class="d-flex align-items-center justify-content-between pt-1">
                                                <div class="form-check">
                                                    <input class="form-check-input checkbox-theme single-select"
                                                           type="checkbox" value="1" name="is_required[0]"
                                                           id="flexCheckDefault__0" checked>
                                                    <label class="form-check-label" for="flexCheckDefault__0">
                                                        {{ translate('messages.Is_required_')}}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="reset" class="btn btn--reset min-w-120px mx-2">{{ translate('messages.Reset')}}</button>
                            <button type="submit" class="btn btn--primary min-w-120px demo_check">{{ translate('messages.Submit')}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('script_2')
    <script>
        "use strict";
        let counter = 0;

        async function loadBanks() {
            const loadingEl  = document.getElementById('bank-loading');
            const errorEl    = document.getElementById('bank-error');
            const selectEl   = document.getElementById('method_name');
            const fallbackEl = document.getElementById('method_name_fallback');

            // Reset states
            loadingEl.classList.remove('d-none');
            errorEl.classList.add('d-none');
            selectEl.classList.add('d-none');
            selectEl.removeAttribute('required');
            if (fallbackEl) fallbackEl.removeAttribute('required');

            try {
                const response = await fetch('{{ route('admin.ninepsb.banks') }}', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const json = await response.json();

                // Safe parsing of the deeply nested 9PSB response log configuration
                let banks = [];
                if (json.body && json.body.data && json.body.data.data && json.body.data.data.bankList) {
                    banks = json.body.data.data.bankList;
                } else if (json.data && json.data.data && json.data.data.bankList) {
                    banks = json.data.data.bankList;
                } else if (json.bankList) {
                    banks = json.bankList;
                }

                if (!Array.isArray(banks) || banks.length === 0) {
                    throw new Error('No banks found in payload structure');
                }

                // Rebuild options securely
                selectEl.innerHTML = '<option value="" selected disabled>{{ translate('-- Select Bank --') }}</option>';

                banks.forEach(function (bank) {
                    const name = (bank.bankName ?? bank.name ?? '').trim();
                    const code = (bank.bankCode ?? bank.code ?? '').trim();

                    if (!name) return;

                    const option       = document.createElement('option');
                    option.value       = name;
                    option.dataset.code = code;
                    option.textContent = name;
                    selectEl.appendChild(option);
                });

                // Show selector field, clean loading element wrapper
                loadingEl.classList.add('d-none');
                selectEl.classList.remove('d-none');
                selectEl.setAttribute('required', 'required');

                // Re-init Select2 engine cleanly
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery('#method_name').select2();
                }

            } catch (err) {
                console.error('loadBanks error:', err);
                loadingEl.classList.add('d-none');
                errorEl.classList.remove('d-none');
                if (fallbackEl) fallbackEl.setAttribute('required', 'required');
            }
        }

 
        jQuery(document).ready(function ($) {
            counter = 1;

            // Load banks on page ready
            loadBanks();

            // Retry button
            document.getElementById('retry-banks').addEventListener('click', function (e) {
                e.preventDefault();
                const fallbackEl = document.getElementById('method_name_fallback');
                if (fallbackEl) fallbackEl.removeAttribute('required');
                loadBanks();
            });

 
            $('#add-more-field').on('click', function (event) {
                if (counter < 15) {
                    event.preventDefault();

                    $('#custom-field-section').append(
                        `<div class="card card-body mt-3" id="field-row--${counter}">
                            <div class="bg-1079801A p--20 rounded">
                                <div class="row gy-2 align-items-center">
                                    <div class="col-md-4 col-12">
                                        <label class="text-title">{{ translate('messages.Input_Field_Type')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                        <select class="form-control js-select js-select2-custom" name="field_type[]" required>
                                            <option value="string">{{ translate('messages.Text')}}</option>
                                            <option value="number">{{ translate('messages.Number')}}</option>
                                            <option value="date">{{ translate('messages.Date')}}</option>
                                            <option value="email">{{ translate('messages.Email')}}</option>
                                            <option value="phone">{{ translate('messages.Phone')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-floating">
                                            <label class="text-title">{{ translate('messages.field_name')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                            <input type="text" class="form-control" name="field_name[]"
                                                placeholder="{{ translate('messages.Ex:_Bank')}}" value="" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-floating">
                                            <label class="text-title">{{ translate('messages.placeholder_text')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                            <input type="text" class="form-control" name="placeholder_text[]"
                                                placeholder="{{ translate('messages.Ex:_John')}}" value="" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-center justify-content-between pt-1">
                                            <div class="form-check">
                                                <input class="form-check-input checkbox-theme single-select" type="checkbox" value="1" name="is_required[${counter}]" id="flexCheckDefault__${counter}" checked>
                                                <label class="form-check-label" for="flexCheckDefault__${counter}">
                                                    {{ translate('messages.Is_required_')}}
                                                </label>
                                            </div>
                                            <span class="btn w-30px h-30 py-1 px-1 btn-danger remove-field" data-id="${counter}">
                                                <i class="tio-delete"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`
                    );

                    $(".js-select").select2();

                    const newRow = document.getElementById(`field-row--${counter}`);
                    if (newRow) {
                        setTimeout(function () {
                            const targetTop = newRow.getBoundingClientRect().top + window.pageYOffset - 100;
                            try {
                                window.scrollTo({ top: targetTop, behavior: 'smooth' });
                            } catch (e) {
                                $('html, body').stop().animate({ scrollTop: targetTop }, 400);
                            }
                        }, 100);
                    }

                    counter++;
                } else {
                    Swal.fire({
                        title: '{{ translate('messages.Reached_maximum')}}',
                        confirmButtonText: '{{ translate('messages.ok')}}',
                    });
                }
            });

    
            $('form').on('reset', function () {
                if (counter > 1) {
                    $('#custom-field-section').html(`
                        <div class="card card-body">
                            <div class="bg-1079801A p--20 rounded">
                                <div class="row gy-2 align-items-center">
                                    <div class="col-md-4 col-12">
                                        <label class="text-title">{{ translate('messages.Input_Field_Type')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <select class="form-control js-select js-select2-custom" name="field_type[]" required>
                                            <option value="string">{{ translate('messages.Text')}}</option>
                                            <option value="number">{{ translate('messages.Number')}}</option>
                                            <option value="date">{{ translate('messages.Date')}}</option>
                                            <option value="email">{{ translate('messages.Email')}}</option>
                                            <option value="phone">{{ translate('messages.Phone')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-floating">
                                            <label class="text-title">{{ translate('messages.field_name')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                            <input type="text" class="form-control" name="field_name[]"
                                                    placeholder="{{ translate('messages.Ex:_Account_name')}} "
                                                    value="" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <div class="form-floating">
                                            <label class="text-title">{{ translate('messages.placeholder_text')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                            <input type="text" class="form-control" name="placeholder_text[]"
                                                    placeholder="{{ translate('messages.Ex:_John')}} "
                                                    value="" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-12">
                                        <div class="d-flex align-items-center justify-content-between pt-1">
                                            <div class="form-check">
                                                <input class="form-check-input checkbox-theme single-select"
                                                       type="checkbox" value="1" name="is_required[0]"
                                                       id="flexCheckDefault__0" checked>
                                                <label class="form-check-label" for="flexCheckDefault__0">
                                                    {{ translate('messages.Is_required_')}}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                }

                counter = 1;

                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery('#method_name').val('').trigger('change');
                } else {
                    document.getElementById('method_name').value = '';
                }
            });

          
            $(document).on('click', '.remove-field', function () {
                const fieldRowId = $(this).data('id');
                const rowEl = document.getElementById(`field-row--${fieldRowId}`);
                if (!rowEl) { counter--; return; }
                $(rowEl).slideUp(250, function () { this.remove(); });
                counter--;
            });
        });
    </script>

    <script>
        jQuery(function ($) {
            const $sticky = $('.withdraw-header-sticky').first();
            if (!$sticky.length) return;
            const origTop = $sticky.offset().top;
            function update() {
                $sticky.toggleClass('scrolling', $(window).scrollTop() >= origTop);
            }
            $(window).on('scroll', update);
            update();
        });
    </script>
@endpush