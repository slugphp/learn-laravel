<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;

class Controller extends BaseController
{
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    protected function whereParam($query)
    {
        $kwd = trim($_GET['search']);
        $obj = $query->where([]);
        if (!empty($kwd)) {
            $columns = $_GET['columns'];
            foreach ($columns as $column) {
                if (!$column['searchable'] || !$column['data']) continue;
                if (!$column['data']) continue;
                $obj->orWhere($column['data'], 'like', "%$kwd%");
            }
        }
        return $obj;
    }

    protected function orderParam($defaultColumn = '', $defaultDir = 'asc')
    {
        $columns = $_GET['columns'];
        $order = $_GET['order'][0];
        $column = $columns[$order['column']]['data'] ?: $defaultColumn;
        if (!$column) {
            throw new Exception('error params');
        }
        $dir = $order['dir'] ?: $defaultDir;
        return [$column, $dir];
    }
}
