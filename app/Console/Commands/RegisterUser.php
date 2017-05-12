<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class RegisterUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register {name} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'register {name} {email}';

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
        $hasThisUser = User::where([
                'name' => $this->argument('name')]
            )->orWhere([
                'email' => $this->argument('email')
            ])->first(['id']);
        if ($hasThisUser) {
            die("用户名已存在！请重试。\n");
        }
        User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => bcrypt($this->ask('请输入密码')),
        ]);
        echo "注册成功！";
    }
}
