<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\User;
use App\Models\CreditTransaction;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        // You can add custom Artisan commands here if needed
    ];

    protected function schedule(Schedule $schedule)
    {
        // Daily credit reward at midnight
        $schedule->call(function () {
            User::chunk(100, function($users) {
                foreach ($users as $user) {
                    $credit = 100; // daily bonus
                    $user->increment('credits', $credit);

                    // Log the transaction
                    CreditTransaction::create([
                        'user_id' => $user->id,
                        'type' => 'credit',
                        'amount' => $credit,
                        'source' => 'daily_bonus'
                    ]);
                }
            });
        })->dailyAt('00:00'); // runs at midnight
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    
}
