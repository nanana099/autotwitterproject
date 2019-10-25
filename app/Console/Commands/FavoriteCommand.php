<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FavoriteExecutor;
use \Exception;

class FavoriteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:favorite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自動でいいねを実行する';

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
