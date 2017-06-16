<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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
        // Construct new Adldap instance.
        $ad = new \Adldap\Adldap();

        // Create a configuration array.
        $config = [
          // Your account suffix, for example: jdoe@corp.acme.org
          'account_suffix'        => '@vvchuan.com',

          // The domain controllers option is an array of your LDAP hosts. You can
          // use the either the host name or the IP address of your host.
          'domain_controllers'    => ['172.30.223.152'],

          // The base distinguished name of your domain.
          'base_dn'               => 'dc=vvchuan,dc=com',


          // The account to use for querying / modifying LDAP records. This
          // does not need to be an actual admin account.
          'admin_username'        => 'admin',
          'admin_password'        => 'ZAQ!xsw234',
        ];

        // Add a connection provider to Adldap.
        $ad->addProvider($config);

        try {
            // If a successful connection is made to your server, the provider will be returned.
            $provider = $ad->connect();

            // Performing a query.
            $results = $provider->search()->where('ou', '=', 'IT')->get();

            // Finding a record.
            $user = $provider->search()->find('John Doe');

            // Creating a new LDAP entry. You can pass in attributes into the make methods.
            $user2 =  $provider->make()->user([
                'cn'          => 'John Doe',
                'title'       => 'Accountant',
                'description' => 'User Account',
            ]);


            // Setting a model's attribute.
            $user2->cn = 'John Doe';
                die(var_dump($user, $user2->save()));

            // Saving the changes to your LDAP server.
            if ($user2->save()) {
                // User was saved!
            }
        } catch (\Adldap\Auth\BindException $e) {
            echo $e->getMessage(), "\r\n";
            // There was an issue binding / connecting to the server.

        }
    }
}
