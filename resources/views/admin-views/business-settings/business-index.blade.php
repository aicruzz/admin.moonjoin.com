@extends('layouts.admin.app')

@section('title', translate('business_setup'))


@section('content')
<div class="content">
    <form class="validate-form" action="{{ route('admin.business-settings.update-setup') }}" method="post" enctype="multipart/form-data">
            @csrf
            @php($name = \App\Models\BusinessSetting::where('key', 'business_name')->first())
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-header-title fs-24 mr-3">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                    </span>
                    <span>
                        {{ translate('messages.business_settings') }}
                    </span>
                </h1>
                @include('admin-views.business-settings.partials.nav-menu')
            </div>
            <!-- End Page Header -->
        
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-xxl-9 col-lg-8 col-md-7 col-sm-6">
                            <div>
                                <h3 class="mb-1">
                                    {{ translate('Maintenance Mode') }}
                                </h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Turn on the Maintenance Mode will temporarily deactivate your selected systems as of your chosen date and time.') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-lg-4 col-md-5 col-sm-6">
                            <div
                                class="maintenance-mode-toggle-bar d-flex flex-wrap justify-content-between border rounded align-items-center py-2 px-3">
                                @php($config = \App\CentralLogics\Helpers::get_business_settings('maintenance_mode'))
                                <h5 class="text-capitalize m-0 font-weight-normal fs-14 text-dark">
                                    {{ translate('messages.maintenance_mode') }}
                                </h5>
                                <label class="toggle-switch toggle-switch-sm">
                                    <input type="checkbox" class="status toggle-switch-input maintenance-mode" {{ isset($config) && $config ? 'checked' : '' }}>
                                    <span class="toggle-switch-label text mb-0">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="mb-1">
                                    {{ translate('Basic Information') }}
                                </h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Here you setup your all business information.') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-8 shadow-sm">
                                    <div class="p-xxl-20 p-xl-3 p-2 bg-white">
                                        <div class="row g-3">
                                            <div class="col-sm-6 col-md-6">
                                                <div class="form-group mb-0">
                                                    <label class="form-label"
                                                        for="store_name">{{ translate('messages.company_name') }} <span
                                                            class="text-danger">*</span></label>
                                                    <input id="store_name" type="text" name="store_name"
                                                        value="{{ $name->value ?? '' }}" class="form-control"
                                                        placeholder="{{ translate('messages.new_company') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-md-6">
                                                @php($email = \App\Models\BusinessSetting::where('key', 'email_address')->first())
                                                <div class="form-group mb-0">
                                                    <label class="form-label" for="email">{{ translate('messages.email') }}
                                                        <span class="text-danger">*</span></label>
                                                    <input id="email" type="email" value="{{ $email->value ?? '' }}"
                                                        name="email" class="form-control"
                                                        placeholder="{{ translate('messages.Ex_:_ex@example.com') }}"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-md-6">
                                                @php($phone = \App\Models\BusinessSetting::where('key', 'phone')->first())
                                                <div class="form-group mb-0">
                                                    <label class="form-label" for="phone">{{ translate('messages.phone') }}
                                                    </label>
                                                    <input type="tel" value="{{ $phone->value ?? '' }}" id="phone"
                                                        name="phone" class="form-control"
                                                        placeholder="{{ translate('messages.Ex: +3264124565') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-md-6">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                        for="country">{{ translate('messages.country') }} <span
                                                            class="text-danger">*</span></label>
                                                    <select id="country" name="country"
                                                        class="form-control  js-select2-custom">
                                                        <option value="AF">Afghanistan</option>
                                                        <option value="AX">Åland Islands</option>
                                                        <option value="AL">Albania</option>
                                                        <option value="DZ">Algeria</option>
                                                        <option value="AS">American Samoa</option>
                                                        <option value="AD">Andorra</option>
                                                        <option value="AO">Angola</option>
                                                        <option value="AI">Anguilla</option>
                                                        <option value="AQ">Antarctica</option>
                                                        <option value="AG">Antigua and Barbuda</option>
                                                        <option value="AR">Argentina</option>
                                                        <option value="AM">Armenia</option>
                                                        <option value="AW">Aruba</option>
                                                        <option value="AU">Australia</option>
                                                        <option value="AT">Austria</option>
                                                        <option value="AZ">Azerbaijan</option>
                                                        <option value="BS">Bahamas</option>
                                                        <option value="BH">Bahrain</option>
                                                        <option value="BD">Bangladesh</option>
                                                        <option value="BB">Barbados</option>
                                                        <option value="BY">Belarus</option>
                                                        <option value="BE">Belgium</option>
                                                        <option value="BZ">Belize</option>
                                                        <option value="BJ">Benin</option>
                                                        <option value="BM">Bermuda</option>
                                                        <option value="BT">Bhutan</option>
                                                        <option value="BO">Bolivia, Plurinational State of</option>
                                                        <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
                                                        <option value="BA">Bosnia and Herzegovina</option>
                                                        <option value="BW">Botswana</option>
                                                        <option value="BV">Bouvet Island</option>
                                                        <option value="BR">Brazil</option>
                                                        <option value="IO">British Indian Ocean Territory</option>
                                                        <option value="BN">Brunei Darussalam</option>
                                                        <option value="BG">Bulgaria</option>
                                                        <option value="BF">Burkina Faso</option>
                                                        <option value="BI">Burundi</option>
                                                        <option value="KH">Cambodia</option>
                                                        <option value="CM">Cameroon</option>
                                                        <option value="CA">Canada</option>
                                                        <option value="CV">Cape Verde</option>
                                                        <option value="KY">Cayman Islands</option>
                                                        <option value="CF">Central African Republic</option>
                                                        <option value="TD">Chad</option>
                                                        <option value="CL">Chile</option>
                                                        <option value="CN">China</option>
                                                        <option value="CX">Christmas Island</option>
                                                        <option value="CC">Cocos (Keeling) Islands</option>
                                                        <option value="CO">Colombia</option>
                                                        <option value="KM">Comoros</option>
                                                        <option value="CG">Congo</option>
                                                        <option value="CD">Congo, the Democratic Republic of the</option>
                                                        <option value="CK">Cook Islands</option>
                                                        <option value="CR">Costa Rica</option>
                                                        <option value="CI">Côte d'Ivoire</option>
                                                        <option value="HR">Croatia</option>
                                                        <option value="CU">Cuba</option>
                                                        <option value="CW">Curaçao</option>
                                                        <option value="CY">Cyprus</option>
                                                        <option value="CZ">Czech Republic</option>
                                                        <option value="DK">Denmark</option>
                                                        <option value="DJ">Djibouti</option>
                                                        <option value="DM">Dominica</option>
                                                        <option value="DO">Dominican Republic</option>
                                                        <option value="EC">Ecuador</option>
                                                        <option value="EG">Egypt</option>
                                                        <option value="SV">El Salvador</option>
                                                        <option value="GQ">Equatorial Guinea</option>
                                                        <option value="ER">Eritrea</option>
                                                        <option value="EE">Estonia</option>
                                                        <option value="ET">Ethiopia</option>
                                                        <option value="FK">Falkland Islands (Malvinas)</option>
                                                        <option value="FO">Faroe Islands</option>
                                                        <option value="FJ">Fiji</option>
                                                        <option value="FI">Finland</option>
                                                        <option value="FR">France</option>
                                                        <option value="GF">French Guiana</option>
                                                        <option value="PF">French Polynesia</option>
                                                        <option value="TF">French Southern Territories</option>
                                                        <option value="GA">Gabon</option>
                                                        <option value="GM">Gambia</option>
                                                        <option value="GE">Georgia</option>
                                                        <option value="DE">Germany</option>
                                                        <option value="GH">Ghana</option>
                                                        <option value="GI">Gibraltar</option>
                                                        <option value="GR">Greece</option>
                                                        <option value="GL">Greenland</option>
                                                        <option value="GD">Grenada</option>
                                                        <option value="GP">Guadeloupe</option>
                                                        <option value="GU">Guam</option>
                                                        <option value="GT">Guatemala</option>
                                                        <option value="GG">Guernsey</option>
                                                        <option value="GN">Guinea</option>
                                                        <option value="GW">Guinea-Bissau</option>
                                                        <option value="GY">Guyana</option>
                                                        <option value="HT">Haiti</option>
                                                        <option value="HM">Heard Island and McDonald Islands</option>
                                                        <option value="VA">Holy See (Vatican City State)</option>
                                                        <option value="HN">Honduras</option>
                                                        <option value="HK">Hong Kong</option>
                                                        <option value="HU">Hungary</option>
                                                        <option value="IS">Iceland</option>
                                                        <option value="IN">India</option>
                                                        <option value="ID">Indonesia</option>
                                                        <option value="IR">Iran, Islamic Republic of</option>
                                                        <option value="IQ">Iraq</option>
                                                        <option value="IE">Ireland</option>
                                                        <option value="IM">Isle of Man</option>
                                                        <option value="IL">Israel</option>
                                                        <option value="IT">Italy</option>
                                                        <option value="JM">Jamaica</option>
                                                        <option value="JP">Japan</option>
                                                        <option value="JE">Jersey</option>
                                                        <option value="JO">Jordan</option>
                                                        <option value="KZ">Kazakhstan</option>
                                                        <option value="KE">Kenya</option>
                                                        <option value="KI">Kiribati</option>
                                                        <option value="KP">Korea, Democratic People's Republic of</option>
                                                        <option value="KR">Korea, Republic of</option>
                                                        <option value="KW">Kuwait</option>
                                                        <option value="KG">Kyrgyzstan</option>
                                                        <option value="LA">Lao People's Democratic Republic</option>
                                                        <option value="LV">Latvia</option>
                                                        <option value="LB">Lebanon</option>
                                                        <option value="LS">Lesotho</option>
                                                        <option value="LR">Liberia</option>
                                                        <option value="LY">Libya</option>
                                                        <option value="LI">Liechtenstein</option>
                                                        <option value="LT">Lithuania</option>
                                                        <option value="LU">Luxembourg</option>
                                                        <option value="MO">Macao</option>
                                                        <option value="MK">Macedonia, the former Yugoslav Republic of
                                                        </option>
                                                        <option value="MG">Madagascar</option>
                                                        <option value="MW">Malawi</option>
                                                        <option value="MY">Malaysia</option>
                                                        <option value="MV">Maldives</option>
                                                        <option value="ML">Mali</option>
                                                        <option value="MT">Malta</option>
                                                        <option value="MH">Marshall Islands</option>
                                                        <option value="MQ">Martinique</option>
                                                        <option value="MR">Mauritania</option>
                                                        <option value="MU">Mauritius</option>
                                                        <option value="YT">Mayotte</option>
                                                        <option value="MX">Mexico</option>
                                                        <option value="FM">Micronesia, Federated States of</option>
                                                        <option value="MD">Moldova, Republic of</option>
                                                        <option value="MC">Monaco</option>
                                                        <option value="MN">Mongolia</option>
                                                        <option value="ME">Montenegro</option>
                                                        <option value="MS">Montserrat</option>
                                                        <option value="MA">Morocco</option>
                                                        <option value="MZ">Mozambique</option>
                                                        <option value="MM">Myanmar</option>
                                                        <option value="NA">Namibia</option>
                                                        <option value="NR">Nauru</option>
                                                        <option value="NP">Nepal</option>
                                                        <option value="NL">Netherlands</option>
                                                        <option value="NC">New Caledonia</option>
                                                        <option value="NZ">New Zealand</option>
                                                        <option value="NI">Nicaragua</option>
                                                        <option value="NE">Niger</option>
                                                        <option value="NG">Nigeria</option>
                                                        <option value="NU">Niue</option>
                                                        <option value="NF">Norfolk Island</option>
                                                        <option value="MP">Northern Mariana Islands</option>
                                                        <option value="NO">Norway</option>
                                                        <option value="OM">Oman</option>
                                                        <option value="PK">Pakistan</option>
                                                        <option value="PW">Palau</option>
                                                        <option value="PS">Palestinian Territory, Occupied</option>
                                                        <option value="PA">Panama</option>
                                                        <option value="PG">Papua New Guinea</option>
                                                        <option value="PY">Paraguay</option>
                                                        <option value="PE">Peru</option>
                                                        <option value="PH">Philippines</option>
                                                        <option value="PN">Pitcairn</option>
                                                        <option value="PL">Poland</option>
                                                        <option value="PT">Portugal</option>
                                                        <option value="PR">Puerto Rico</option>
                                                        <option value="QA">Qatar</option>
                                                        <option value="RE">Réunion</option>
                                                        <option value="RO">Romania</option>
                                                        <option value="RU">Russian Federation</option>
                                                        <option value="RW">Rwanda</option>
                                                        <option value="BL">Saint Barthélemy</option>
                                                        <option value="SH">Saint Helena, Ascension and Tristan da Cunha
                                                        </option>
                                                        <option value="KN">Saint Kitts and Nevis</option>
                                                        <option value="LC">Saint Lucia</option>
                                                        <option value="MF">Saint Martin (French part)</option>
                                                        <option value="PM">Saint Pierre and Miquelon</option>
                                                        <option value="VC">Saint Vincent and the Grenadines</option>
                                                        <option value="WS">Samoa</option>
                                                        <option value="SM">San Marino</option>
                                                        <option value="ST">Sao Tome and Principe</option>
                                                        <option value="SA">Saudi Arabia</option>
                                                        <option value="SN">Senegal</option>
                                                        <option value="RS">Serbia</option>
                                                        <option value="SC">Seychelles</option>
                                                        <option value="SL">Sierra Leone</option>
                                                        <option value="SG">Singapore</option>
                                                        <option value="SX">Sint Maarten (Dutch part)</option>
                                                        <option value="SK">Slovakia</option>
                                                        <option value="SI">Slovenia</option>
                                                        <option value="SB">Solomon Islands</option>
                                                        <option value="SO">Somalia</option>
                                                        <option value="ZA">South Africa</option>
                                                        <option value="GS">South Georgia and the South Sandwich Islands
                                                        </option>
                                                        <option value="SS">South Sudan</option>
                                                        <option value="ES">Spain</option>
                                                        <option value="LK">Sri Lanka</option>
                                                        <option value="SD">Sudan</option>
                                                        <option value="SR">Suriname</option>
                                                        <option value="SJ">Svalbard and Jan Mayen</option>
                                                        <option value="SZ">Swaziland</option>
                                                        <option value="SE">Sweden</option>
                                                        <option value="CH">Switzerland</option>
                                                        <option value="SY">Syrian Arab Republic</option>
                                                        <option value="TW">Taiwan, Province of China</option>
                                                        <option value="TJ">Tajikistan</option>
                                                        <option value="TZ">Tanzania, United Republic of</option>
                                                        <option value="TH">Thailand</option>
                                                        <option value="TL">Timor-Leste</option>
                                                        <option value="TG">Togo</option>
                                                        <option value="TK">Tokelau</option>
                                                        <option value="TO">Tonga</option>
                                                        <option value="TT">Trinidad and Tobago</option>
                                                        <option value="TN">Tunisia</option>
                                                        <option value="TR">Turkey</option>
                                                        <option value="TM">Turkmenistan</option>
                                                        <option value="TC">Turks and Caicos Islands</option>
                                                        <option value="TV">Tuvalu</option>
                                                        <option value="UG">Uganda</option>
                                                        <option value="UA">Ukraine</option>
                                                        <option value="AE">United Arab Emirates</option>
                                                        <option value="GB">United Kingdom</option>
                                                        <option value="US">United States</option>
                                                        <option value="UM">United States Minor Outlying Islands</option>
                                                        <option value="UY">Uruguay</option>
                                                        <option value="UZ">Uzbekistan</option>
                                                        <option value="VU">Vanuatu</option>
                                                        <option value="VE">Venezuela, Bolivarian Republic of</option>
                                                        <option value="VN">Viet Nam</option>
                                                        <option value="VG">Virgin Islands, British</option>
                                                        <option value="VI">Virgin Islands, U.S.</option>
                                                        <option value="WF">Wallis and Futuna</option>
                                                        <option value="EH">Western Sahara</option>
                                                        <option value="YE">Yemen</option>
                                                        <option value="ZM">Zambia</option>
                                                        <option value="ZW">Zimbabwe</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 col-md-12">
                                                @php($address = \App\Models\BusinessSetting::where('key', 'address')->first())
                                                <div class="form-group mb-0">
                                                    <label class="form-label"
                                                        for="address">{{ translate('messages.address') }} <span
                                                            class="text-danger">*</span>
                                                        <span class="" data-toggle="tooltip" data-placement="right"
                                                            data-original-title="The physical location of your business">
                                                            <i class="tio-info text-muted"></i>
                                                        </span>
                                                    </label>
                                                    <textarea type="text" id="address" name="address" class="form-control"
                                                        placeholder="{{ translate('messages.Ex: address') }}" rows="1"
                                                        required>{{ $address->value ?? '' }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-12 mt-1">
                                                <div class="">
                                                    <div class="position-relative">
                                                        <!-- <div class="d-flex mb-3 fs-12">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V11H13V17ZM13 9H11V7H13V9Z"
                                                                    fill="#039D55" />
                                                            </svg>
                                                            <div class="w-0 flex-grow pl-2">
                                                                {{ translate('clicking_on_the_map_will_set_Latitude_and_Longitude_automatically') }}
                                                            </div>
                                                        </div> -->
                                                        <input id="pac-input" class="controls rounded" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('messages.search_your_location_here') }}"
                                                            type="text"
                                                            placeholder="{{ translate('messages.search_here') }}" />
                                                        <div id="location_map_canvas"
                                                            class="overflow-hidden rounded height-285px"></div>

                                                        <!-- latlong -->
                                                        <div
                                                            class="lat-long-adjust py-1 px-1 position-absolute bottom-0 mb-2 flex-sm-nowrap flex-wrap rounded bg-white d-flex justify-content-center align-items-center gap-1">
                                                            @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
                                                            @php($default_location = $default_location?->value ? json_decode($default_location->value, true) : 0)
                                                            <div class="form-group mb-0">
                                                                <input type="text" id="latitude" name="latitude"
                                                                    class="w-auto border-0 p-0 m-0 text-center"
                                                                    placeholder="{{ translate('messages.Ex:') }} -94.22213"
                                                                    value="{{ $default_location ? $default_location['lat'] : 0 }}"
                                                                    required readonly>
                                                            </div>
                                                            <div class="line"></div>
                                                            <div class="form-group mb-0">
                                                                <input type="text" name="longitude"
                                                                    class="w-auto border-0 p-0 m-0 text-center"
                                                                    placeholder="{{ translate('messages.Ex:') }} 103.344322"
                                                                    id="longitude"
                                                                    value="{{ $default_location ? $default_location['lng'] : 0 }}"
                                                                    required readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @php($logo = \App\Models\BusinessSetting::where('key', 'logo')->first())

                                <div class="col-lg-4 shadow-sm">
                                    <div class="d-flex flex-column gap-4 shadow-sm h--37px">
                                        <div class="bg-light2 rounded p-20">
                                            <div class="mb-15">
                                                <h4 class="mb-1">{{ translate('Upload Logo') }} </h4>
                                                <p class="mb-0 fs-12 gray-dark">
                                                    {{translate('Upload your business logo')}}
                                                </p>
                                            </div>
                                            @include('admin-views.partials._image-uploader', [
                                                    'id' => 'image-input',
                                                    'name' => 'logo',
                                                    'ratio' => '3:1',
                                                    'isRequired' => $logo?->value ? false : true,
                                                    'existingImage' => \App\CentralLogics\Helpers::get_full_url('business', $logo?->value ?? '', $logo?->storage[0]?->value ?? 'public', 'upload_image'),
                                                    'imageExtension' => IMAGE_EXTENSION,
                                                    'imageFormat' => IMAGE_FORMAT,
                                                    'maxSize' => MAX_FILE_SIZE,
                                                    'textPosition' => 'bottom',
                                                    ])
                                        </div>
                                        @php($icon = \App\Models\BusinessSetting::where('key', 'icon')->first())

                                        <div class="bg-light2 rounded p-20">
                                            <div class="text-start">
                                                <div class="mb-15">
                                                    <h4 class="mb-1">{{ translate('Favicon') }} </h4>
                                                    <p class="mb-0 fs-12 gray-dark">
                                                        {{translate('Upload your website favicon')}}
                                                    </p>
                                                </div>
                                                @include('admin-views.partials._image-uploader', [
                                                    'id' => 'image-input',
                                                    'name' => 'icon',
                                                    'ratio' => '1:1',
                                                    'isRequired' => $icon?->value ? false : true,
                                                    'existingImage' => \App\CentralLogics\Helpers::get_full_url('business', $icon?->value ?? '', $icon?->storage[0]?->value ?? 'public', 'upload_image'),
                                                    'imageExtension' => IMAGE_EXTENSION,
                                                    'imageFormat' => IMAGE_FORMAT,
                                                    'maxSize' => MAX_FILE_SIZE,
                                                    'textPosition' => 'bottom',
                                                    ])
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="row g-4">
                                        <div class="col-sm-6">
                                            @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
                                            @php($default_location = $default_location?->value ? json_decode($default_location->value, true) : 0)
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="latitude">{{ translate('messages.latitude') }}<span
                                                        class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.click_on_the_map_select_your_defaul_location') }}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="{{ translate('messages.click_on_the_map_select_your_defaul_location') }}"></span></label>
                                                <input type="text" id="latitude" name="latitude" class="form-control"
                                                    placeholder="{{ translate('messages.Ex:') }} -94.22213"
                                                    value="{{ $default_location ? $default_location['lat'] : 0 }}" required
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="longitude">{{ translate('messages.longitude') }}<span
                                                        class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.click_on_the_map_select_your_defaul_location') }}"><img
                                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                            alt="{{ translate('messages.click_on_the_map_select_your_defaul_location') }}"></span></label>
                                                <input type="text" name="longitude" class="form-control"
                                                    placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                                                    value="{{ $default_location ? $default_location['lng'] : 0 }}" required
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div> -->
                            <div class="info-notes-bg px-3 py-2 rounded fz-11  gap-2 align-items-center d-flex mt-20">
                                <img src="{{asset('public/assets/admin/img/info-idea.svg')}}" alt="">
                                <span>
                                    {{translate('For the address setup you can simply drag the map to pick for the perfect')}}
                                    <strong class="text-title"> {{translate('Lat(Latitude) & Log(Longitude)')}}</strong>
                                    {{translate('value')}}.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">

                    <div class="card ">
                        <div class="card-header">
                            <div>
                                <h3 class="mb-1">
                                    {{ translate('General Setup') }}
                                </h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Here you can manage time settings to match with your business criteria') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body py-xxl-4 py-3 px-xxl-4 px-lg-3 px-0">
                            <div class="shadow-sm p-xxl-20 p-xl-3 p-2 bg-white mb-20">
                                <div class="mb-20">
                                    <h4 class="mb-1">
                                        {{ translate('Time Setup') }}
                                    </h4>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Vendor Logo & Covers') }}
                                    </p>
                                </div>
                                <div class="bg-light2 rounded p-xxl-20 p-3">
                                    <div class="row g-3">
                                        <div class="col-sm-6 col-md-4 col-xl-4">
                                            @php($tz = \App\Models\BusinessSetting::where('key', 'timezone')->first())
                                            @php($settings_timezone = $tz ? $tz->value : 0)
                                            <div class="form-group mb-0">
                                                <label class="input-label d-flex align-items-center gap-1">
                                                    {{ translate('messages.time_zone') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select name="timezone" class="form-control js-select2-custom">
                                                    @foreach(timezone_identifiers_list() as $tz)
                                                        <?php
                                                            $dt = new DateTime("now", new DateTimeZone($tz));
                                                            $offset = $dt->getOffset(); // in seconds
                                                            $hours = intdiv($offset, 3600);
                                                            $minutes = abs(($offset % 3600) / 60);
                                                            $sign = $hours >= 0 ? '+' : '-';
                                                            $gmt = sprintf("GMT%s%02d:%02d", $sign, abs($hours), $minutes);
                                                        ?>
                                                        <option value="{{ $tz }}" {{ isset($settings_timezone) && $settings_timezone == $tz ? 'selected' : '' }}>
                                                            ({{ $gmt }}) {{ $tz }}
                                                        </option>
                                                    @endforeach
                                                <option value="US/Central" {{ isset($settings_timezone) && $settings_timezone == 'US/Central' ? 'selected' :  '' }}> (GMT-06:00) Central Time (US & Canada)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-xl-4">
                                            @php($tf = \App\Models\BusinessSetting::where('key', 'timeformat')->first())
                                            @php($tf = $tf ? $tf->value : '24')
                                            <div class="form-group mb-0">
                                                <label for="time_format"
                                                    class="form-label text-capitalize">{{ translate('messages.time_format') }}
                                                    <span class="text-danger">*</span></label>
                                                <!-- <select id="time_format" name="time_format" class="form-control">
                                                    <option value="12" {{ $tf == '12' ? 'selected' : '' }}>
                                                        {{ translate('messages.12_hour') }}
                                                    </option>
                                                    <option value="24" {{ $tf == '24' ? 'selected' : '' }}>
                                                        {{ translate('messages.24_hour') }}
                                                    </option>
                                                </select> -->
                                                <div class="resturant-type-group bg-white border">
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="12"
                                                            name="time_format" {{ $tf == '12' ? 'checked' : '' }}>
                                                        <span class="form-check-label">
                                                            {{translate('12 Hours')}}
                                                        </span>
                                                    </label>
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="24"
                                                            name="time_format" {{ $tf == '24' ? 'checked' : '' }}>
                                                        <span class="form-check-label">
                                                            {{translate('24 Hours')}}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="shadow-sm p-xxl-20 p-xl-3 p-2 bg-white mb-20">

                                <div class="mb-20">
                                    <h4 class="mb-1">
                                        {{ translate('Currency Setup') }}
                                    </h4>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Here you can manage currency settings to match with your business criteria') }}
                                    </p>
                                </div>
                                <div class="bg-light2 rounded p-xxl-20 p-3">
                                    <div class="row g-3">
                                        <div class="col-sm-6 col-md-4 col-xl-4">
                                            @php($currency_code = \App\Models\BusinessSetting::where('key', 'currency')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label"
                                                    for="currency">{{ translate('Currency Symbol') }}</label>
                                                <select id="change_currency" name="currency"
                                                    class="form-control js-select2-custom">
                                                    @foreach (\App\Models\Currency::orderBy('currency_code')->get() as $currency)
                                                        <option value="{{ $currency['currency_code'] }}" {{ $currency_code ? ($currency_code->value == $currency['currency_code'] ? 'selected' : '') : '' }}>
                                                            {{ $currency['currency_code'] }}
                                                            ({{ $currency['currency_symbol'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-xl-4">
                                            @php($currency_symbol_position = \App\Models\BusinessSetting::where('key', 'currency_symbol_position')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="currency_symbol_position">{{ translate('Currency Position') }}
                                                </label>
                                                <div class="resturant-type-group bg-white border">
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="left"
                                                            name="currency_symbol_position" {{ $currency_symbol_position ? ($currency_symbol_position->value == 'left' ? 'checked' : '') : '' }}>
                                                        <span class="form-check-label">
                                                            ({{\App\CentralLogics\Helpers::currency_symbol()}})
                                                            {{translate('Left')}}
                                                        </span>
                                                    </label>
                                                    <label class="form-check form--check mr-2 mr-md-4">
                                                        <input class="form-check-input" type="radio" value="right"
                                                            name="currency_symbol_position" {{ $currency_symbol_position ? ($currency_symbol_position->value == 'right' ? 'checked' : '') : '' }}>
                                                        <span class="form-check-label">
                                                            ({{\App\CentralLogics\Helpers::currency_symbol()}})
                                                            {{translate('Right')}}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-xl-4">
                                            @php($digit_after_decimal_point = \App\Models\BusinessSetting::where('key', 'digit_after_decimal_point')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                    for="digit_after_decimal_point">{{ translate('messages.Digit after decimal point') }}
                                                </label>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('how_many_fractional_digit_to_show_after_decimal_value') }}">
                                                        <i class="tio-info text-muted"></i>
                                                </span>
                                                <input type="number" name="digit_after_decimal_point" class="form-control"
                                                    id="digit_after_decimal_point"
                                                    placeholder="{{ translate('messages.ex_:_2') }}"
                                                    value="{{ $digit_after_decimal_point ? $digit_after_decimal_point->value : 0 }}"
                                                    min="0" max="4" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            @php($subscription_business_model = \App\Models\BusinessSetting::where('key', 'subscription_business_model')->first())
                            @php($subscription_business_model = $subscription_business_model ? $subscription_business_model->value : 0)

                            @php($commission_business_model = \App\Models\BusinessSetting::where('key', 'commission_business_model')->first())
                            @php($commission_business_model = $commission_business_model ? $commission_business_model->value : 0)
                            <div class="shadow-sm p-xxl-20 p-xl-3 p-2 bg-white mb-20">
                                <div class="mb-20">
                                    <h4 class="mb-1">
                                        {{ translate('Business Model Setup') }}
                                    </h4>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Setup your business time zone and format from here') }}
                                    </p>
                                </div>
                                <div class="bg-light2 rounded p-xxl-20 p-3">
                                    <div class="row g-3">
                                        <div class="col-lg-12">
                                            <label class="form-label" for="footer_text">{{translate('Business Model')}}
                                                <span class="text-danger">*</span>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right" data-original-title="{{ translate('Choose the model that decides how you earn money and process orders.') }}">
                                                    <i class="tio-info text-muted"></i>
                                                </span>
                                            </label>
                                            <div class="bg-white rounded p-3 border mb-20">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input"
                                                                    id="subs" name="subscription_business_model" {{ $subscription_business_model ? 'checked' : '' }} value="1">
                                                                <label class="custom-control-label" for="subs">
                                                                    <h5 class="mb-1">{{ translate('Subscription') }}</h5>
                                                                    <p class="mb-0 fs-12">
                                                                        {{ translate('By selecting subscription based business model restaurants can run business with you based on subscription package.') }}
                                                                    </p>
                                                                    <div
                                                                        class="d-flex p-2 px-3 rounded gap-2 bg-opacity-warning-10 mt-3">
                                                                        <i class="tio-info text-warning"></i>
                                                                        <p class="fz-12px mb-0">
                                                                            {{translate('To active subscription based business model 1st you need to add subscription package from')}}
                                                                            <a href="{{route('admin.business-settings.subscriptionackage.index')}}"
                                                                                class="fz-12px font-semibold info-dark text-underline">{{translate('Subscription Packages')}}</a>
                                                                        </p>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input"
                                                                    id="commission" name="commission_business_model" {{ $commission_business_model ? 'checked' : '' }} value="1">
                                                                <label class="custom-control-label" for="commission">
                                                                    <h5 class="mb-1">{{ translate('Commission') }}</h5>
                                                                    <p class="mb-0 fs-12">
                                                                        {{ translate('By selecting commission based business model restaurants can run business with you based on commission based payment per order.') }}
                                                                    </p>
                                                                    <div
                                                                        class="info-notes-bg px-3 py-2 rounded fz-11  gap-2 d-flex mt-20">
                                                                        <img src="{{asset('public/assets/admin/img/info-idea.svg')}}"
                                                                            alt="">
                                                                        <span>
                                                                            {{translate('To set different commission for commission based restaurants.')}}
                                                                            {{translate('Go to')}}: <span
                                                                                class="fz-12px font-semibold info-dark">{{translate('Restaurant List')}}
                                                                                > {{translate('Restaurant Details')}} >
                                                                                {{translate('Business Plan')}}</span>
                                                                        </span>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-sm-6 col-lg-6">
                                                    @php($admin_commission = \App\Models\BusinessSetting::where('key', 'admin_commission')->first())
                                                    <div class="form-group mb-0">
                                                        <label class="form-label text-capitalize" for="admin_commission">
                                                            {{ translate('messages.Default_Commission_Rate_On_Order') }} (%)
                                                            <span class="text-danger">*</span>
                                                            <span class="form-label-secondary" data-toggle="tooltip"
                                                                data-placement="right"
                                                                data-original-title="{{ translate('messages.Set_up_‘Default_Commission_Rate’_on_every_Order._Admin_can_also_set_store-wise_different_commission_rates_from_respective_store_settings.') }}">
                                                                <i class="tio-info text-muted"></i>
                                                            </span>
                                                        </label>
                                                        <input type="number" name="admin_commission" class="form-control"
                                                            id="admin_commission"
                                                            placeholder="{{ translate('messages.Ex:_10') }}"
                                                            value="{{ $admin_commission ? $admin_commission->value : 0 }}"
                                                            min="0" max="100" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-lg-6">
                                                    @php($delivery_charge_comission = \App\Models\BusinessSetting::where('key', 'delivery_charge_comission')->first())
                                                    <div class="form-group mb-0">
                                                        <label class="input-label text-capitalize d-flex alig-items-center"
                                                            for="admin_comission_in_delivery_charge">
                                                            {{translate('messages.Commission_Rate_On_Delivery_Charge')}} (%)
                                                            <span class="text-danger">*</span>
                                                            <span class="form-label-secondary ml-1" data-toggle="tooltip"
                                                                data-placement="right"
                                                                data-original-title="{{ translate('messages.Set_a_default_‘Commission_Rate’_for_freelance_deliverymen_(under_admin)_on_every_deliveryman. ') }}">
                                                                <i class="tio-info text-muted"></i>
                                                            </span>
                                                        </label>
                                                        <input type="number" name="admin_comission_in_delivery_charge"
                                                            class="form-control" id="admin_comission_in_delivery_charge"
                                                            placeholder="{{ translate('messages.Ex:_10') }}" min="0"
                                                            max="100" step="0.01"
                                                            value="{{ $delivery_charge_comission ? $delivery_charge_comission->value : 0 }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="shadow-sm p-xxl-20 p-xl-3 p-2 bg-white mb-20">
                                <div class="row g-3">
                                    <div class="col-xxl-9 col-lg-8 col-md-7 col-sm-6">
                                        <div>
                                            <h4 class="mb-1">
                                                {{ translate('Additional Charge Setup') }}
                                            </h4>
                                            <p class="mb-0 fs-12">
                                                {{ translate('By switching this feature ON, Customer need to pay the amount you set. ') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-lg-4 col-md-5 col-sm-6">
                                        @php($additional_charge_status = \App\Models\BusinessSetting::where('key', 'additional_charge_status')->first())
                                        @php($additional_charge_status = $additional_charge_status ? $additional_charge_status->value : 0)
                                        <div class="form-group mb-0">
                                            <label
                                                class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                <span class="pr-1 d-flex align-items-center switch--label">
                                                    <span class="line--limit-1">
                                                        {{translate('messages.Status') }}
                                                    </span>
                                                </span>
                                                <input type="checkbox" data-id="additional_charge_status" data-type="toggle"
                                                    data-image-on="{{ asset('/public/assets/admin/img/modal/dm-tips-on.png') }}"
                                                    data-image-off="{{ asset('/public/assets/admin/img/modal/dm-tips-off.png') }}"
                                                    data-title-on="<strong>{{ translate('messages.Want_to_enable_additional_charge?') }}</strong>"
                                                    data-title-off="<strong>{{ translate('messages.Want_to_disable_additional_charge?') }}</strong>"
                                                    data-text-on="<p>{{ translate('messages.If_you_enable_this,_additional_charge_will_be_added_with_order_amount,_it_will_be_added_in_admin_wallet') }}</p>"
                                                    data-text-off="<p>{{ translate('messages.If_you_disable_this,_additional_charge_will_not_be_added_with_order_amount.') }}</p>"
                                                    class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                                    name="additional_charge_status" id="additional_charge_status" {{ $additional_charge_status == 1 ? 'checked' : '' }}>
                                                <span class="toggle-switch-label text">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light2 rounded p-xxl-20 p-3 additional__body mt-20">
                                    <div class="row g-3">
                                        <div class="col-sm-6 col-lg-6">
                                            @php($additional_charge_name = \App\Models\BusinessSetting::where('key', 'additional_charge_name')->first())
                                            <div class="form-group mb-0">
                                                <label
                                                    class="form-label d-flex justify-content-between text-capitalize mb-1"
                                                    for="additional_charge_name">
                                                    <span
                                                        class="line--limit-1">{{ translate('messages.additional_charge_name') }}
                                                        <span class="text-danger">*</span>
                                                    </span>
                                                </label>

                                                <input type="text" name="additional_charge_name" class="form-control"
                                                    id="additional_charge_name"
                                                    placeholder="{{ translate('messages.Ex:_Processing_Fee') }}"
                                                    value="{{ $additional_charge_name ? $additional_charge_name->value : '' }}"
                                                    {{ isset($additional_charge_status) ? '' : 'readonly' }} required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-6">
                                            @php($additional_charge = \App\Models\BusinessSetting::where('key', 'additional_charge')->first())
                                            <div class="form-group mb-0">
                                                <label
                                                    class="form-label d-flex justify-content-between text-capitalize mb-1"
                                                    for="additional_charge">
                                                    <span class="line--limit-1">{{ translate('messages.charge_amount') }}
                                                        ({{ \App\CentralLogics\Helpers::currency_symbol() }}) <span
                                                            class="text-danger">*</span>
                                                    </span>
                                                </label>

                                                <input type="number" name="additional_charge" class="form-control"
                                                    id="additional_charge" placeholder="{{ translate('messages.Ex:_10') }}"
                                                    value="{{ $additional_charge ? $additional_charge->value : 0 }}" min="0"
                                                    step=".01" {{ isset($additional_charge_status) ? '' : 'readonly' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="info-notes-bg px-3 py-2 rounded fz-11  gap-2 d-flex mt-20">
                                    <img src="{{asset('public/assets/admin/img/info-idea.svg')}}" alt="">
                                    <span>
                                        {{translate('Only admin will get the additional amount & customer must pay the amount.')}}
                                    </span>
                                </div>
                            </div>
                            <div class="shadow-sm p-xxl-20 p-xl-3 p-2 bg-white">
                                <div class="mb-20">
                                    <h4 class="mb-1">
                                        {{ translate('Copyright & Cookies Text') }}
                                    </h4>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Add the necessary texts to display in required sections') }}
                                    </p>
                                </div>
                                <div class="bg-light2 rounded p-xxl-20 p-3">
                                    <div class="row g-3">
                                        <div class="col-md-6 col-xl-6">
                                            @php($footer_text = \App\Models\BusinessSetting::where('key', 'footer_text')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label"
                                                    for="footer_text">{{ translate('Copyright Text') }}
                                                    <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.make_visitors_aware_of_your_business‘s_rights_&_legal_information.') }}">
                                                        <i class="tio-info text-muted"></i>
                                                    </span>
                                                </label>
                                                <textarea type="text" id="footer_text" maxlength="100" name="footer_text"
                                                    class="form-control" rows="3"
                                                    placeholder="{{ translate('messages.Ex_:_Copyright_Text') }}"
                                                    required>{{ $footer_text->value ?? '' }}</textarea>
                                                <span
                                                    class="text-right text-counting color-A7A7A7 d-block mt-1">0/100</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-6">
                                            @php($cookies_text = \App\Models\BusinessSetting::where('key', 'cookies_text')->first())
                                            <div class="form-group mb-0">
                                                <label class="form-label" for="cookies_text">{{ translate('Cookies Text') }}
                                                </label>
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.make_visitors_aware_of_your_business‘s_rights_&_legal_information.') }}">
                                                        <i class="tio-info text-muted"></i>
                                                    </span>
                                                <textarea type="text" id="cookies_text" maxlength="100" name="cookies_text"
                                                    class="form-control " rows="3"
                                                    placeholder="{{ translate('messages.Ex_:_Cookies_Text') }}"
                                                    required>{{ $cookies_text->value ?? '' }}</textarea>
                                                <span
                                                    class="text-right text-counting color-A7A7A7 d-block mt-1">0/100</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bottom -->
                <div class="col-lg-12">
                    <h4 class="card-title mb-3 d-flex align-items-center"> <span class="card-header-icon mr-2"><i
                                class="tio-neighborhood"></i></span>
                        <span>{{ translate('messages.Business_Rules_setup') }} </span>
                    </h4>
                    <div class="card">
                        <div class="card-body">
                            <div class="__bg-F8F9FC-card p-0 mt-4">
                                <div class="border-bottom p-3">
                                    <h4 class="card-title m-0 text--title">{{translate('Payment')}}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-sm-6 col-lg-6">
                                            @php($partial_payment = \App\Models\BusinessSetting::where('key', 'partial_payment_status')->first())
                                            @php($partial_payment = $partial_payment ? $partial_payment->value : 0)
                                            <div class="form-group mb-0">
                                                <label
                                                    class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                                    <span class="pr-1 d-flex align-items-center switch--label">
                                                        <span class="line--limit-1">
                                                            {{ translate('messages.partial_payment') }}
                                                        </span>
                                                        <span class="form-label-secondary text-danger d-flex"
                                                            data-toggle="tooltip" data-placement="right"
                                                            data-original-title="{{ translate('messages.If_enabled,_customers_can_make_partial_payments._For_example,_a_customer_can_pay_$20_initially_out_of_their_$50_payment_&_use_other_payment_methods_for_the_rest._Partial_payments_must_be_made_through_their_wallets.')}}"><img
                                                                src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                                alt="{{ translate('messages.customer_varification_toggle') }}">
                                                            *
                                                        </span>
                                                    </span>
                                                    <input type="checkbox" data-id="partial_payment" data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/schedule-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/schedule-off.png') }}"
                                                        data-title-on="{{ translate('messages.Want_to_enable') }} <strong>{{ translate('messages.partial_payment_?') }}</strong>"
                                                        data-title-off="{{ translate('messages.Want_to_disable') }} <strong>{{ translate('messages.partial_payment_?') }}</strong>"
                                                        data-text-on="<p>{{ translate('messages.If_you_enable_this,_customers_can_choose_partial_payment_during_checkout.') }}</p>"
                                                        data-text-off="<p>{{ translate('messages.If_you_disable_this,_the_partial_payment_feature_will_be_hidden.') }}</p>"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle" value="1"
                                                        name="partial_payment_status" id="partial_payment" {{ $partial_payment == 1 ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-6">
                                            @php($partial_payment_method = \App\Models\BusinessSetting::where('key', 'partial_payment_method')->first())
                                            <div class="form-group mb-0">
                                                <label class="input-label text-capitalize d-flex alig-items-center"><span
                                                        class="line--limit-1">{{ translate('Can_Pay_the_Rest_Amount_using') }}
                                                        <span class="form-label-secondary" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('messages.Set_the_method(s)_that_customers_can_pay_the_remainder_after_partial_payment.') }}">
                                                        <i class="tio-info text-muted"></i>
                                                    </span>
                                                    </span>
                                                </label>
                                                <div class="resturant-type-group border bg-white">
                                                    <label class="form-check form--check">
                                                        <input class="form-check-input" type="radio" value="cod"
                                                            name="partial_payment_method" {{ $partial_payment_method ? ($partial_payment_method->value == 'cod' ? 'checked' : '') : '' }}>
                                                        <span class="form-check-label">
                                                            {{translate('cod')}}
                                                        </span>
                                                    </label>
                                                    <label class="form-check form--check">
                                                        <input class="form-check-input" type="radio" value="digital_payment"
                                                            name="partial_payment_method" {{ $partial_payment_method ? ($partial_payment_method->value == 'digital_payment' ? 'checked' : '') : '' }}>
                                                        <span class="form-check-label">
                                                            {{translate('digital_payment')}}
                                                        </span>
                                                    </label>
                                                    <label class="form-check form--check">
                                                        <input class="form-check-input" type="radio" value="both"
                                                            name="partial_payment_method" {{ $partial_payment_method ? ($partial_payment_method->value == 'both' ? 'checked' : '') : '' }}>
                                                        <span class="form-check-label">
                                                            {{translate('both')}}
                                                        </span>
                                                    </label>
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
                    <button type="reset" class="btn btn--reset min-w-120px">{{ translate('messages.reset') }}</button>
                    <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                        class="btn btn--primary call-demo min-w-120px"><i class="tio-save">x</i>
                        {{ translate('save_information') }}</button>
                </div>
            </div>
        </div>
    </form>
</div>



<div class="modal fade" id="currency-warning-modal">
    <div class="modal-dialog modal-dialog-centered status-warning-modal">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true" class="tio-clear"></span>
                </button>
            </div>
            <div class="modal-body pb-5 pt-0">
                <div class="max-349 mx-auto mb-20">
                    <div>
                        <div class="text-center">
                            <img width="80" src="{{  asset('public/assets/admin/img/modal/currency.png') }}"
                                class="mb-20">
                            <h5 class="modal-title"></h5>
                        </div>
                        <div class="text-center">
                            <h3> {{ translate('Are_you_sure_to_change_the_currency_?') }}</h3>
                            <div>
                                <p>{{ translate('If_you_enable_this_currency,_you_must_active_at_least_one_digital_payment_method_that_supports_this_currency._Otherwise_customers_cannot_pay_via_digital_payments_from_the_app_and_websites._And_Also_restaurants_cannot_pay_you_digitally') }}
                                </p>
                            </div>
                        </div>

                        <div class="text-center mb-4">
                            <a class="text--underline"
                                href="{{ route('admin.business-settings.third-party.payment-method') }}">
                                {{ translate('Go_to_payment_method_settings.') }}</a>
                        </div>
                    </div>

                    <div class="btn--container justify-content-center">
                        <button data-dismiss="modal" id="confirm-currency-change"
                            class="btn btn--cancel min-w-120">{{translate("Cancel")}}</button>
                        <button data-dismiss="modal" type="button"
                            class="btn btn--primary min-w-120">{{translate('OK')}}</button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')

<script>
    "use strict";

    $(document).ready(function () {
        let selectedCurrency = "{{ $currency_code ? $currency_code->value : 'USD' }}";
        let currencyConfirmed = false;
        let updatingCurrency = false;

        $("#change_currency").change(function () {
            if (!updatingCurrency) check_currency($(this).val());
        });

        $("#confirm-currency-change").click(function () {
            currencyConfirmed = true;
            update_currency(selectedCurrency);
            $('#currency-warning-modal').modal('hide');
        });

        function check_currency(currency) {
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{route('admin.system_currency')}}",
                method: 'GET',
                data: { currency: currency },
                success: function (response) {
                    if (response.data) {
                        $('#currency-warning-modal').modal('show');
                    } else {
                        update_currency(currency);
                    }
                }
            });
        }

        function update_currency(currency) {
            if (currencyConfirmed) {
                updatingCurrency = true;
                $("#change_currency").val(currency).trigger('change');
                updatingCurrency = false;
                currencyConfirmed = false;
            }
        }
    });

</script>

<script
    src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places,marker&v=3.61">
    </script>
<script>
    "use strict";
    $(document).on('ready', function () {
        @php($country = \App\Models\BusinessSetting::where('key', 'country')->first())

        @if ($country)
            $("#country option[value='{{ $country->value }}']").attr('selected', 'selected').change();
        @endif
        });

    @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
    @php($language = $language->value ?? null)
    let language = <?php echo $language; ?>;
    $('[id=language]').val(language);


    $(document).on('click', '.maintenance-mode', function () {
        @if (env('APP_MODE') == 'demo')
            toastr.warning('Sorry! You can not enable maintenance mode in demo!');
        @else
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: '{{ translate('messages.all_your_apps_and_customer_website_will_be_disabled_until_you_‘Turn_Off’ _maintenance_mode.') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#00868F',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: '{{ route('admin.maintenance-mode') }}',
                        contentType: false,
                        processData: false,
                        beforeSend: function () {
                            $('#loading').show();
                        },
                        success: function (data) {
                            toastr.success(data.message);
                        },
                        complete: function () {
                            $('#loading').hide();
                        },
                    });
                } else {
                    location.reload();
                }
            })
        @endif

        });


    function readURL(input, viewer) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $('#' + viewer).attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#customFileEg1").change(function () {
        readURL(this, 'viewer');
    });

    $("#favIconUpload").change(function () {
        readURL(this, 'iconViewer');
    });

    function initAutocomplete() {
        const mapId = "{{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}"

        var myLatLng = {
            lat: {{ $default_location ? $default_location['lat'] : '-33.8688' }},
            lng: {{ $default_location ? $default_location['lng'] : '151.2195' }}
            };
        const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
            center: {
                lat: {{ $default_location ? $default_location['lat'] : '-33.8688' }},
                lng: {{ $default_location ? $default_location['lng'] : '151.2195' }}
                },
            zoom: 13,
            mapTypeId: "roadmap",
            mapId: mapId,
        });

        const { AdvancedMarkerElement } = google.maps.marker;

        var marker = new AdvancedMarkerElement({
            position: myLatLng,
            map: map,
        });

        var geocoder = geocoder = new google.maps.Geocoder();
        google.maps.event.addListener(map, 'click', function (mapsMouseEvent) {
            var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
            var coordinates = JSON.parse(coordinates);
            var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
            marker.position = latlng;
            map.panTo(latlng);

            document.getElementById('latitude').value = coordinates['lat'];
            document.getElementById('longitude').value = coordinates['lng'];


            geocoder.geocode({
                'latLng': latlng
            }, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    if (results[1]) {
                        document.getElementById('address').value = results[1].formatted_address;
                    }
                }
            });
        });
        // Create the search box and link it to the UI element.
        const input = document.getElementById("pac-input");
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
        // Bias the SearchBox results towards current map's viewport.
        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });
        let markers = [];
        // Listen for the event fired when the user selects a prediction and retrieve
        // more details for that place.
        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();

            if (places.length == 0) {
                return;
            }
            // Clear out the old markers.
            markers.forEach((marker) => {
                marker.setMap(null);
            });
            markers = [];
            // For each place, get the icon, name and location.
            const bounds = new google.maps.LatLngBounds();
            places.forEach((place) => {
                if (!place.geometry || !place.geometry.location) {
                    console.log("Returned place contains no geometry");
                    return;
                }
                const { AdvancedMarkerElement } = google.maps.marker;
                var mrkr = new AdvancedMarkerElement({
                    map,
                    title: place.name,
                    position: place.geometry.location,
                });
                google.maps.event.addListener(mrkr, "click", function (event) {
                    document.getElementById('latitude').value = this.position.lat();
                    document.getElementById('longitude').value = this.position.lng();
                });

                markers.push(mrkr);

                if (place.geometry.viewport) {
                    // Only geocodes have viewport.
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });
            map.fitBounds(bounds);
        });
    };

    $(document).on('ready', function () {
        initAutocomplete();
    });

    $(document).on("keydown", "input", function (e) {
        if (e.which === 13) e.preventDefault();
    });
</script>
@endpush
