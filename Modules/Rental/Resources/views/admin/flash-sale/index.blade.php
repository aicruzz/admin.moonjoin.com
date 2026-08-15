@extends('layouts.admin.app')

@section('title', translate('messages.rental_flash_sales'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-flash"></i></span>
                <span>{{ translate('messages.rental_flash_sales') }}</span>
            </h1>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.rental.flash-sale.store') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="input-label">{{ translate('messages.title') }}</label>
                            <input type="text" name="title" class="form-control" required maxlength="191">
                        </div>
                        <div class="col-md-3">
                            <label class="input-label">{{ translate('messages.start_date') }}</label>
                            <input type="datetime-local" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="input-label">{{ translate('messages.end_date') }}</label>
                            <input type="datetime-local" name="end_date" class="form-control" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn--primary w-100">{{ translate('messages.submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ translate('messages.campaigns') }}
                    <span class="badge badge-soft-dark ml-2">{{ $flash_sales->total() }}</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.title') }}</th>
                            <th>{{ translate('messages.duration') }}</th>
                            <th>{{ translate('messages.vehicles') }}</th>
                            <th>{{ translate('messages.publish') }}</th>
                            <th>{{ translate('messages.status') }}</th>
                            <th>{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flash_sales as $flash_sale)
                            <tr>
                                <td>{{ $flash_sale->title }}</td>
                                <td>{{ $flash_sale->start_date }} &mdash; {{ $flash_sale->end_date }}</td>
                                <td>{{ $flash_sale->vehicles_count }}</td>
                                <td>
                                    <a class="btn btn-sm {{ $flash_sale->is_publish ? 'btn--primary' : 'btn-outline-secondary' }}"
                                       href="{{ route('admin.rental.flash-sale.publish', [$flash_sale->id, $flash_sale->is_publish ? 0 : 1]) }}">
                                        {{ $flash_sale->is_publish ? translate('messages.published') : translate('messages.unpublished') }}
                                    </a>
                                </td>
                                <td>
                                    <a class="btn btn-sm {{ $flash_sale->status ? 'btn--success' : 'btn-outline-secondary' }}"
                                       href="{{ route('admin.rental.flash-sale.status', [$flash_sale->id, $flash_sale->status ? 0 : 1]) }}">
                                        {{ $flash_sale->status ? translate('messages.active') : translate('messages.inactive') }}
                                    </a>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn--primary" href="{{ route('admin.rental.flash-sale.edit', [$flash_sale->id]) }}">
                                        <i class="tio-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.rental.flash-sale.delete', [$flash_sale->id]) }}" method="post" class="d-inline">
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
                {!! $flash_sales->links() !!}
            </div>
        </div>
    </div>
@endsection
