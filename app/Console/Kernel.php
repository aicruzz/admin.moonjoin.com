<?php

namespace App\Console;

use App\Models\BusinessSetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [];

    protected function schedule(Schedule $schedule)
    {
        // Test — updates DB every minute to confirm cron is running
        $schedule->call(function () {
            \App\Models\BusinessSetting::updateOrInsert(
                ['key' => 'cron_last_run'],
                ['value' => now(), 'updated_at' => now()]
            );
        })->everyMinute();

        $schedule->command('store:disbursement')
            ->cron($this->buildCron('store'))
            ->withoutOverlapping();

        $schedule->command('dm:disbursement')
            ->cron($this->buildCron('dm'))
            ->withoutOverlapping();

        $schedule->command('ve:disbursement')
            ->cron($this->buildCron('ve'))
            ->withoutOverlapping();
    }

    private function buildCron(string $prefix): string
    {
        try {
            $settings = array_column(
                BusinessSetting::whereIn('key', [
                    "{$prefix}_disbursement_time_period",
                    "{$prefix}_disbursement_week_start",
                    "{$prefix}_disbursement_create_time",
                ])->get()->toArray(),
                'value',
                'key'
            );

            $frequency = $settings["{$prefix}_disbursement_time_period"] ?? 'daily';
            $weekDay = $settings["{$prefix}_disbursement_week_start"] ?? 'sunday';
            $time = $settings["{$prefix}_disbursement_create_time"] ?? '12:00';

            [$hour, $min] = explode(':', $time);

            $days = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
            $day = $days[$weekDay] ?? 0;

            return match ($frequency) {
                'daily' => "{$min} {$hour} * * *",
                'weekly' => "{$min} {$hour} * * {$day}",
                'monthly' => "{$min} {$hour} 28-31 * *",
                default => "{$min} {$hour} * * *",
            };
        } catch (\Throwable $e) {
            return '0 12 * * *';
        }
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}