@extends('layouts.vendor.app')

@section('title',translate('messages.Owner_Permissions'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-user-switch"></i>
            </span>
            <span>{{translate('messages.Owner_Permissions')}}</span>
        </h1>
        <p class="text-muted mb-0">
            {{translate('messages.These capabilities apply to you as the store owner. Employee capabilities are managed separately under Employee Roles.')}}
        </p>
    </div>

    <div class="row g-2">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('vendor.permission.update')}}" method="POST">
                        @csrf
                        <div class="d-flex flex-wrap module-wise-gap">
                            <div class="check-item p-2">
                                <div class="form-group form-check form--check m-0">
                                    <input type="checkbox" name="modules[]" value="claim" class="form-check-input rounded"
                                        id="owner_claim" {{in_array('claim',(array)$permission->modules)?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="owner_claim">{{translate('messages.Claim Funds')}}</label>
                                </div>
                            </div>
                            <div class="check-item p-2">
                                <div class="form-group form-check form--check m-0">
                                    <input type="checkbox" name="modules[]" value="payout" class="form-check-input rounded"
                                        id="owner_payout" {{in_array('payout',(array)$permission->modules)?'checked':''}}>
                                    <label class="form-check-label qcont text-dark" for="owner_payout">{{translate('messages.Payout')}}</label>
                                </div>
                            </div>
                        </div>

                        <div class="btn--container justify-content-end mt-3">
                            <button type="submit" class="btn btn--primary">{{translate('messages.save')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
