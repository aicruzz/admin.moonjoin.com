<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitDeliveryMan extends Model
{
    use HasFactory;

    protected $table = 'debit_delivery_men';

    protected $fillable = [
        'delivery_man_id',
        'amount',
        'reason',
        'note',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }
}
