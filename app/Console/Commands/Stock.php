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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('stock')
            ->setDescription('stock commands')
            ->setHelp(<<<'EOF'
同步新浪行业:
  <info>php %command.full_name% syncIndustry</info>
同步腾讯评论:
  <info>php %command.full_name% syncComment</info>
EOF
            )
        ;
    }

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
