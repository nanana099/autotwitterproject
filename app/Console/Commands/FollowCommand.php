<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FollowExecutor;
use \Exception;

class FollowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:follow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自動でフォローを実行する';

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
            new FollowExecutor(),  // フォロー機能
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
