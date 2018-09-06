<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Lantern extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lantern {action}';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('lantern')
            ->setDescription('lantern commands')
            ->setHelp(<<<'EOF'
New Lantern Ad Issue:
  <info>php %command.full_name% newissue</info>
Close Last Ad Issue:
  <info>php %command.full_name% closeissue</info>
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
        $lantern = new \App\Models\Lantern();
        if (!method_exists($lantern, $action)) {
            return $this->error('Wrong action!');
        }
        $lantern->$action();
    }
}
