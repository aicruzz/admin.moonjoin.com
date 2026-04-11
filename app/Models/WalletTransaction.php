<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'credit',
        'debit',
        'admin_bonus',
        'balance',
        'transaction_type',
        'reference',
        'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'credit' => 'float',
        'debit' => 'float',
        'admin_bonus' => 'float',
        'balance' => 'float',
        'reference' => 'string',
        'created_at' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}