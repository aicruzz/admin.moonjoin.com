<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitDeliverymanReason extends Model
{
    protected $fillable = ['reason', 'user_type', 'status'];

    protected $table = 'debit_deliveryman_reasons';

    // Translation scope (same pattern as OrderCancelReason)
    protected static function booted()
    {
        static::addGlobalScope('translate', function ($builder) {
            $builder->when(
                app()->getLocale() !== 'en',
                fn($q) => $q->with('translations')
            );
        });
    }

    public function translations()
    {
        return $this->hasMany(Translation::class, 'translationable_id')
            ->where('translationable_type', self::class);
    }

   public function getRawOriginal($key = null, $default = null)
{
    if (is_null($key)) {
        return $this->attributes;
    }
    return $this->attributes[$key] ?? $default;
}
}