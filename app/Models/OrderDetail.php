<?php

namespace App\Models;

use App\Traits\ReportFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderDetail extends Model
{
    use HasFactory, ReportFilter;

    protected $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'item_id',
        'item_details',
        'price',
        'quantity',
        'tax_amount',
        'discount_on_item',
        'variant',
        'variation',
        'add_ons',
        'total_add_on_price',
        'item_campaign_id',
    ];

    protected $casts = [
        'price' => 'float',
        'discount_on_item' => 'float',
        'total_add_on_price' => 'float',
        'tax_amount' => 'float',
        'item_id' => 'integer',
        'order_id' => 'integer',
        'quantity' => 'integer',
        'item_campaign_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function campaign()
    {
        return $this->belongsTo(ItemCampaign::class, 'item_campaign_id');
    }

    public function store()
    {
        return $this->order?->store;
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->has('order');
        });
    }
}