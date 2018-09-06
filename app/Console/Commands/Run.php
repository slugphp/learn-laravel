<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DiDom\Document;

class Run extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Run {model} {action}';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('run commands')
            ->setHelp(<<<'EOF'
php artisan run {Model} {Action}
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
        $model = '\\App\\Models\\' . $this->argument('model');
        $action = $this->argument('action');
        $m = new $model();
        if (!method_exists($m, $action)) {
            return $this->error('Wrong action!');
        }
        $m->$action();
    }
}
