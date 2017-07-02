<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdldapController extends Controller
{

    public function __construct()
    {

    }

    public function test()
    {
        // Construct new Adldap instance.
        $ad = new \Adldap\Adldap();

        // Create a configuration array.
        $config = [
          // Your account suffix, for example: jdoe@corp.acme.org
        ];

        // Add a connection provider to Adldap.
        $ad->addProvider($config);

        try {
            // If a successful connection is made to your server, the provider will be returned.
            $provider = $ad->connect();

            // Performing a query.
            // DC=vvchuan,DC=com
            $search = $provider->search();
            $results = $provider->search()->raw()->where('ou', '=', 'IT-dev')->get();
            // $results = $provider->search()->find('wangwl');

            // Retrieve all users.
            $results = $search->users()->raw()->get();

            // Retrieve all printers.
            // $results = $search->printers()->get();

            // Retrieve all organizational units.
            // $results = $search->ous()->raw()->get();

            // Retrieve all groups.
            // $results = $search->groups()->raw()->get();

            // Retrieve all containers.
            // $results = $search->containers()->get();

            // Retrieve all contacts.
            // $results = $search->contacts()->get();

            // Retrieve all computers.
            // $results = $search->computers()->get();

            die(simple_dump($this->getData($results)));

            // Finding a record.
            $user = $provider->search()->find('vvchuan');

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

    function getData($results)
    {
        $res = [];
        if (!$results['count']) return [];
        for ($i = 0; $i < $results['count']; $i++) {
            if (is_array($results[$i])) {
                $res[$i] = $this->getData($results[$i]);
            } else {
                $kExist = array_key_exists($results[$i], $results);
                $k = $kExist ? $results[$i] : $i;
                $res[$k] = $kExist
                    ? $this->getData($results[$k])
                    : $results[$i];
            }
        }
        return count($res) === 1 ? $res[0] : $res;
    }

}
