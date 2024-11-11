<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    function users(){
        $users=User::where('admin',0)->get();
        return view('admin/users-management',['users'=>$users]);
    }

    //______________________________________________Users informations and orders
    function users_info($id){
        try{
            $user=User::findOrFail($id);
        }
        catch(\Exception $exception){
            abort(404);
        }
        $orders= DB::table('orders')
        ->where('user_id' , $id)
        ->select('orderCode','created_at',DB::raw('count("orderCode") as occurences'))
        ->groupBy('orderCode','created_at')
        ->having('occurences', '>', 0)
        ->get()->sortByDesc('created_at');
        return view('admin/user-info',['user'=>$user , 'orders'=>$orders]);
    }

    //______________________________________________User Orders
    static function order_group_list($order,$user_id){
        $joined_order=DB::table('orders')
        ->join('products' , 'orders.product_id' , '=' , 'products.id')
        ->where(['orders.user_id'=>$user_id , 'orders.orderCode'=>$order])
        ->select('products.*','orders.*')
        ->get();
        return $joined_order;
    }

    //______________________________________________Remove a User
    function users_remove($id){
        try{
            User::destroy($id);
        }
        catch(\Exception $exception){
            abort(404);
        }
        return redirect()->route('userController.view')->with('success','کاربر مورد نظر با موفقیت حذف شد');
    }    

    //______________________________________________All Orders
    function order_view(){
        $orders= DB::table('orders')
        ->join('users' , 'orders.user_id' , '=' , 'users.id')
        ->where('status',1)
        ->select('orderCode','orders.created_at','users.name as username',DB::raw('count("orderCode") as occurences'))
        ->groupBy('orderCode','orders.created_at','username')
        ->having('occurences', '>', 0)
        ->get()->sortByDesc('created_at');
        return view('admin/admin-orders',['orders'=>$orders]);
    }
    static function admin_order_group_list($order){
        $joined_order=DB::table('orders')
        ->join('products' , 'orders.product_id' , '=' , 'products.id')
        // ->join('users' , 'orders.user_id' , '=' , 'users.id')
        ->where(['orders.orderCode'=>$order])
        ->select('products.*','orders.*')
        ->get();
        return $joined_order;
    }

}
