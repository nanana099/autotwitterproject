<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FavoriteExecutor;

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
            new FavoriteExecutor()
        ];
        foreach ($executors as $executor) {
            $executor->prepare();
            $executor->execute();
        }
    }
}
