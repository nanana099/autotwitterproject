<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FavoriteExecutor;
use App\Services\FollowExecutor;
use App\Services\UnfollowExecutor;
use App\Services\TweetExecutor;
use \Exception;

class TwitterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:twitter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Twitter自動化機能のコマンド';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $executors = [
            new TweetExecutor(),  // フォロー機能
            new FollowExecutor(),  // フォロー機能
            new UnfollowExecutor(),// アンフォロー機能
            new FavoriteExecutor(),// いいね機能
        ];
        
        foreach ($executors as $executor) {
            try {
                $executor->prepare();
                $executor->execute();
            } catch (Exception $e) {
                logger()->error($e);
            }
        }
    }
}
