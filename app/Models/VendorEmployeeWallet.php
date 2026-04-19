<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorEmployeeWallet extends Model
{
    protected $fillable = [
        'vendor_employee_id',
        'total_earning',
        'total_withdrawn',
        'pending_withdraw',
        'collected_cash',
    ];

    protected $casts = [
        'total_earning' => 'float',
        'total_withdrawn' => 'float',
        'pending_withdraw' => 'float',
        'collected_cash' => 'float',
    ];

    public function vendorEmployee()
    {
        return $this->belongsTo(VendorEmployee::class, 'vendor_employee_id');
    }
}

/*
|──────────────────────────────────────────────────────────────
| RUN THIS SQL IN phpMyAdmin to create the wallet table
| and add vendor_employee_id to disbursement_details
|──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `vendor_employee_wallets` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_employee_id` bigint(20) UNSIGNED NOT NULL,
  `total_earning` decimal(24,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn` decimal(24,2) NOT NULL DEFAULT 0.00,
  `pending_withdraw` decimal(24,2) NOT NULL DEFAULT 0.00,
  `collected_cash` decimal(24,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ve_wallet_ve_id_unique` (`vendor_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `disbursement_details`
  ADD COLUMN IF NOT EXISTS `vendor_employee_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `delivery_man_id`;

*/