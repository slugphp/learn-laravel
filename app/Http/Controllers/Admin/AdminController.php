<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use App\User;

class AdminController extends Controller
{

    public function __construct()
    {

    }

    public function list(Request $request)
    {
        list($column, $dir) = $this->orderParam('created_at', 'desc');
        $queryFunc = function ($query) {
            $this->whereParam($query);
        };
        $list = DB::table('users')
            ->where($queryFunc)
            ->orderBy($column, $dir)
            ->skip(intval($_GET['start']))
            ->take(intval($_GET['length']))
            ->get();
        $recordsTotal = DB::table('users')
            ->count();
        $recordsFiltered = DB::table('users')
            ->where($queryFunc)
            ->count();
        $data['draw'] = $_GET['draw'] ?: 1;
        $data['recordsTotal'] = $recordsTotal;
        $data['recordsFiltered'] = $recordsFiltered;
        $data['data'] = $list;
        return response(json_encode($data));
    }

    public function add(Request $request)
    {
        $hasThisUser = User::where([
                'name' => $request->name
            ])->orWhere([
                'email' => $request->email
            ])->first(['id']);
        if ($hasThisUser) {
            return response('该用户已存在', 501);
        }
        $res = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        return $res == true ? response('添加成功') : response('添加失败');
    }

    public function edit(Request $request)
    {
        $hasThisUser = User::where('id', $request->id)->first(['id']);
        if (!$hasThisUser) {
            return response('该用户不存在', 501);
        }
        $userInfo = [
            'name' => $request->name,
            'email' => $request->email
        ];
        if ($request->password != '' && $request->password != '******') {
            $userInfo['password'] = bcrypt($request->password);
        }
        $res = User::where('id', $request->id)
          ->update($userInfo);
        return $res == true ? response('更新成功') : response('更新失败');
    }

    public function status(Request $request)
    {
        $hasThisUser = User::where('id', $request->id)->first(['id']);
        if (!$hasThisUser) {
            return response('该用户不存在', 501);
        }
        $res = User::where('id', $request->id)
          ->update(['status' => $request->status]);
        return $res == true ? response('更新成功') : response('更新失败');
    }

    public function delete(Request $request)
    {
        $hasThisUser = User::where('id', $request->id)->first(['id']);
        if (!$hasThisUser) {
            return response('该用户不存在', 501);
        }
        $res = User::where('id', $request->id)
          ->delete();
        return $res == true ? response('删除成功') : response('删除失败');
    }
}
