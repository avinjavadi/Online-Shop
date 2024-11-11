<?php

namespace App\Http\Controllers\admin;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;

class adminController extends Controller
{
    function admin_panel(){
        $date= date('Ymd');
        $money=DB::table('orders')
            ->join('products','orders.product_id','=','products.id')
            ->where('orders.date',$date)
            ->sum('price');

        $user_count=User::all()->count();
        $admin=Session::get('user')['name'];

        return view('admin/admin-panel',['users_count'=>$user_count , 'money'=>$money , 'admin'=>$admin ]);
    }

    //__________________________________________ Add ADMIN
    function add_admin(Request $req){
        $req->validate([
            'name'=>'required | regex:/(^([a-zA-z0-9 آ-ی]+)(\d+)?$)/u ',
            'email'=>'required |unique:users| email',
            'pswd'=>'required | min:4 | max:8'
        ]);

        $user=new User;
        $user->name=$req->name;
        $user->email=$req->email;
        $user->admin=1;
        $user->password=Hash::make($req->pswd);
        $user->save();

        return view('admin/add-admin' , ['success'=>'ادمین جدید با موفقیت اضافه شد']);
    }

  

}
