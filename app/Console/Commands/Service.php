<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Service extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service';

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
        list($h, $i, $s) = explode(':', date('H:i:s'));

        // check Lantern update
        if ($i % 20 == 0) {
            echo 'check Lantern update';
            $email = new \App\Models\sendLanternEmail();
            $email->check();
        }

    }
}
