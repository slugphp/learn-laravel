<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Route;
use Cookie;

class TestController extends Controller
{

    public function __construct()
    {
    }

    public function index(Request $request)
    {
        die(simple_dump($_GET));
    }

    public function adldap(Request $request)
    {
        ob_start();$s=array($var);foreach($s as $v){var_dump($v);}die('<pre style="white-space:pre-wrap;word-wrap:break-word;">'.preg_replace(array('/\]\=\>\n(\s+)/m','/</m','/>/m'),array('] => ','&lt;','&gt;'),ob_get_clean()).'');
    }

}
