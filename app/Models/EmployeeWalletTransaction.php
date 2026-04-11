<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeWalletTransaction extends Model
{
    protected $fillable = [
        'employee_id',
        'store_id',
        'amount',
        'reason',
        'note',
        'type',
    ];

    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'employee_id');
    }
}