<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\TwitterCommand;
use \Exception;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        TwitterCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // ツイート
        $schedule->command('command:tweet')->withoutOverlapping()->runInBackground();
        // フォロー
        $schedule->command('command:follow')->withoutOverlapping()->everyTenMinutes();
        // アンフォロー
        $schedule->command('command:unfollow')->withoutOverlapping()->everyTenMinutes();
        // いいね
        $schedule->command('command:favorite')->withoutOverlapping()->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
