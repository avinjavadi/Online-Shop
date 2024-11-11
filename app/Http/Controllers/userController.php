<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class userController extends Controller
{
    //____________________ Save users information in Database and Signin
    function signin(Request $req){
        $req->validate([
            'name'=>'required | regex:/(^([a-zA-z0-9 آ-ی]+)(\d+)?$)/u ',
            'email'=>'required |unique:users| email',
            'pswd'=>'required | min:4 | max:8'
        ]);

        $user=new User;
        $user->name=$req->name;
        $user->email=$req->email;
        $user->admin=0;
        $user->password=Hash::make($req->pswd);
        $user->save();

        return view('login' , ['success'=>'ثبت‌نام با موفقیت انجام حالا می توانید وارد حساب خود شوید']);
    }

    //____________________ Login
    function login(Request $req){
        $user=user::where(['email'=>$req->email])->first();
        if($user && Hash::check($req->pswd,$user->password)){
            $req->session()->put('user',$user);
            if($user->admin)
                return redirect()->route('admin.panel');

            return redirect()->route('home');
        }

        else{
            return view('login', ['error'=>'ایمیل یا رمز عبور اشتباه است']);
        }
    }

    //____________________ exit account
    function logout(){
        Session::forget('user');
        return redirect('/');
    }

    //____________________ Edit User Info
    function edit_user_info(Request $req){
        $user_id=Session::get('user')['id'];

        $req->validate([
            'username'=>'Nullable|regex:/(^([a-zA-z0-9 آ-ی]+)(\d+)?$)/u',
            'email'=>'unique:users| email |Nullable'
        ]);

        if($req->username){
            User::where('id',$user_id)->update(['name'=>$req->username]);
        }

        if($req->email){
            User::where('id',$user_id)->update(['email'=>$req->email]);
        }

        return redirect()->route('user.panel')->with('success_message','تغییرات موردنظر با موفیقت انجام شد✅');
    }

    //____________________ Edit User Info
    function edit_password(Request $req){
        $req->validate([
            'oldpswd'=>'required',
            'newpswd'=>'required',
            'reNewpswd'=>'required|same:newpswd|min:4|max:8'
        ]);

        $user_id=Session::get('user')['id'];
        $old_pass=Hash::check($req->oldpswd , user::where('id',$user_id)->first()->password);
        if($old_pass){
            user::where('id',$user_id)->update(['password'=>Hash::make($req->newpswd)]);
            return redirect()->route('user.panel')->with('success_message','رمز عبور شما با موفقیت تغییر کرد ✅');
        }
        return redirect()->route('user.panel')->with('error_message','رمز عبور شما اشتباه است❗');
    }
}
