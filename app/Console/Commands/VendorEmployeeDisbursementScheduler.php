<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VendorEmployeeDisbursementScheduler extends Command
{
    protected $signature = 've:disbursement';
    protected $description = 'Vendor Employee disbursement scheduling based on business settings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Try to call Vendor Employee Disbursement Controller if it exists
        if (class_exists('App\Http\Controllers\Admin\VendorEmployeeDisbursementController') && method_exists('App\Http\Controllers\Admin\VendorEmployeeDisbursementController', 'generate_disbursement')) {
            app('App\Http\Controllers\Admin\VendorEmployeeDisbursementController')->generate_disbursement();
            $this->info('Vendor Employee disbursement scheduler executed successfully.');
        } else {
            $this->info('Vendor Employee disbursement controller not found. Please implement it.');
        }
    }
}
