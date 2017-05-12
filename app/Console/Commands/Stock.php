<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Stock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "stock
                            {action : syncIndustry 同步行业\nsyncComment 同步评论}";

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
        $action = $this->argument('action');
        $stock = new \App\Stock();
        if (!method_exists($stock, $action)) {
            return $this->error('Wrong action!');
        }
        $stock->$action();
    }
}
