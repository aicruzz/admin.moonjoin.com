<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartModificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'user_id',
        'store_id',
        'vendor_id',
        'original_item_id',
        'replacement_item_id',
        'request_type',
        'status',
        'reason',
        'refund_amount',
        'is_refunded',
        'customer_responded_at',
    ];

    protected $casts = [
        'cart_id' => 'integer',
        'user_id' => 'integer',
        'store_id' => 'integer',
        'vendor_id' => 'integer',
        'original_item_id' => 'integer',
        'replacement_item_id' => 'integer',
        'refund_amount' => 'float',
        'is_refunded' => 'boolean',
        'customer_responded_at' => 'datetime',
    ];

    /**
     * Get the cart associated with the request
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the user/customer associated with the request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store associated with the request
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the vendor associated with the request
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the original item
     */
    public function originalItem()
    {
        return $this->belongsTo(Item::class, 'original_item_id');
    }

    /**
     * Get the replacement item
     */
    public function replacementItem()
    {
        return $this->belongsTo(Item::class, 'replacement_item_id');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for removal requests
     */
    public function scopeRemovalRequests($query)
    {
        return $query->where('request_type', 'remove');
    }

    /**
     * Scope for replacement requests
     */
    public function scopeReplacementRequests($query)
    {
        return $query->where('request_type', 'replace');
    }
}
