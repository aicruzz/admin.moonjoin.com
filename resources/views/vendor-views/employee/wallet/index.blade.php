@extends('layouts.vendor.app')

@section('title', translate('messages.debit_employee_wallet'))

@section('content')
<div class="content container-fluid">
    <div class="card">
        <div class="card-header">
            <h5>{{ translate('messages.debit_employee_wallet') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('vendor.employee.wallet.debit') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>{{ translate('messages.select_employee') }}</label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->f_name }} {{ $employee->l_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ translate('messages.amount') }}</label>
                    <input type="number" name="amount" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label>{{ translate('messages.note') }}</label>
                    <textarea name="note" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn--primary">
                    {{ translate('messages.debit_wallet') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection