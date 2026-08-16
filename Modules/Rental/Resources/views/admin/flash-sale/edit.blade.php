@extends('layouts.admin.app')

@section('title', translate('messages.rental_flash_sale_update'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-flash"></i></span>
                <span>{{ $flash_sale->title }}</span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.rental.flash-sale.update', [$flash_sale->id]) }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="input-label">{{ translate('messages.title') }}</label>
                            <input type="text" name="title" class="form-control" value="{{ $flash_sale->title }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="input-label">{{ translate('messages.start_date') }}</label>
                            <input type="datetime-local" name="start_date" class="form-control"
                                   value="{{ $flash_sale->start_date?->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="input-label">{{ translate('messages.end_date') }}</label>
                            <input type="datetime-local" name="end_date" class="form-control"
                                   value="{{ $flash_sale->end_date?->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn--primary w-100">{{ translate('messages.update') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Attach a vehicle. The picker only offers vehicles from this campaign's
             module, and the controller re-checks the provider relationship on submit,
             so a crafted request cannot cross modules either. --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">{{ translate('messages.add_vehicle') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.rental.flash-sale.store-vehicle') }}" method="post">
                    @csrf
                    <input type="hidden" name="rental_flash_sale_id" value="{{ $flash_sale->id }}">
                    <div class="row g-3">
                        {{-- Searchable vehicle picker. js-select2-custom is initialised
                             globally by public/assets/admin/js/app-blade/admin.js, which
                             supplies the in-dropdown search box, so no page script is
                             needed. The option text carries the vehicle name, its id and
                             the provider name, so select2's own search matches any of
                             the three. The submitted value stays the real vehicle id. --}}
                        <div class="col-md-4">
                            <label class="input-label">{{ translate('messages.vehicle') }}</label>
                            <select name="vehicle_id" class="form-control js-select2-custom" required
                                    title="{{ translate('messages.select_vehicle') }}">
                                <option value="">{{ translate('messages.select_vehicle') }}</option>
                                {{-- Grouped by rental type (vehicle category): Car Rental,
                                     Short Apt Rental, and any other configured category. Within a
                                     group the vehicle comes first, then its owning provider, which
                                     is what an admin recognises; the id trails as a secondary
                                     reference they never have to type. All three are in the option
                                     text, so select2's search matches vehicle or provider alike. --}}
                                @foreach ($selectable_vehicles as $category_name => $group)
                                    <optgroup label="{{ $category_name }}">
                                        @foreach ($group as $selectable)
                                            <option value="{{ $selectable->id }}">
                                                {{ $selectable->name }} &mdash; {{ $selectable->provider?->name }} (#{{ $selectable->id }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="input-label">{{ translate('messages.discount_type') }}</label>
                            <select name="discount_type" class="form-control">
                                <option value="percent">{{ translate('messages.percent') }}</option>
                                <option value="amount">{{ translate('messages.amount') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="input-label">{{ translate('messages.discount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="discount" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="input-label">{{ translate('messages.applies_to') }}</label>
                            <select name="applies_to" class="form-control">
                                <option value="all">{{ translate('messages.all') }}</option>
                                <option value="hourly">{{ translate('messages.hourly') }}</option>
                                <option value="distance_wise">{{ translate('messages.distance_wise') }}</option>
                                <option value="day_wise">{{ translate('messages.day_wise') }}</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="input-label">{{ translate('messages.redemption_cap') }}</label>
                            <input type="number" name="redemption_cap" class="form-control" min="1"
                                   placeholder="{{ translate('messages.unlimited') }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn--primary w-100">{{ translate('messages.add') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ translate('messages.vehicles') }}
                    <span class="badge badge-soft-dark ml-2">{{ $vehicles->total() }}</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.vehicle') }}</th>
                            <th>{{ translate('messages.discount') }}</th>
                            <th>{{ translate('messages.applies_to') }}</th>
                            <th>{{ translate('messages.redemption_cap') }}</th>
                            <th>{{ translate('messages.redeemed') }}</th>
                            <th>{{ translate('messages.status') }}</th>
                            <th>{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vehicles as $item)
                            <tr>
                                <td>{{ $item->vehicle?->name ?? '#' . $item->vehicle_id }}</td>
                                <td>{{ $item->discount }} {{ $item->discount_type == 'percent' ? '%' : '' }}</td>
                                <td>{{ translate('messages.' . $item->applies_to) }}</td>
                                <td>{{ $item->redemption_cap ?? translate('messages.unlimited') }}</td>
                                <td>{{ $item->redeemed }}</td>
                                <td>
                                    <a class="btn btn-sm {{ $item->status ? 'btn--success' : 'btn-outline-secondary' }}"
                                       href="{{ route('admin.rental.flash-sale.vehicle-status', [$item->id, $item->status ? 0 : 1]) }}">
                                        {{ $item->status ? translate('messages.active') : translate('messages.inactive') }}
                                    </a>
                                </td>
                                <td>
                                    <form action="{{ route('admin.rental.flash-sale.delete-vehicle', [$item->id]) }}" method="post">
                                        @csrf @method('delete')
                                        <button class="btn btn-sm btn--danger" type="submit"><i class="tio-delete"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {!! $vehicles->links() !!}
            </div>
        </div>
    </div>
@endsection
