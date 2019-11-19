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
        // 前提：サーバーのcronから各コマンドが毎分実行される。

        // ツイート（毎分実行）
        $schedule->command('command:tweet')->withoutOverlapping()->runInBackground();
        // フォロー（10分に一度実行）
        $schedule->command('command:follow')->withoutOverlapping()->everyTenMinutes();
        // アンフォロー（10分に一度実行）
        $schedule->command('command:unfollow')->withoutOverlapping()->everyTenMinutes();
        // いいね（10分に一度実行）
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
