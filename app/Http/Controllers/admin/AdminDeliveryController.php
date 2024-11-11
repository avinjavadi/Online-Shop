<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\post;
use Illuminate\Http\Request;

class AdminDeliveryController extends Controller
{
    function delivery(){
        $post=post::all();
        return view('admin/post',['posts'=>$post]);
    }
    function delivery_update(Request $req){
        post::where('post',$req->post_name)->update(['cost'=>$req->cost]);      
        return redirect()->route('delivery')->with('success',' ✅هزینه پست با موفقیت بروزرسانی شد');
    }
    function delivery_add(Request $req){
        $newDelivery= new post();
        $newDelivery->post=$req->name;
        $newDelivery->cost=$req->cost;
        $newDelivery->save();
        return redirect()->route('delivery')->with('success','  ✅پست با موفقیت ثبت شد');
    }
    function delivery_remove(Request $req){
        post::where('post',$req->post_name)->delete();
        return redirect()->route('delivery')->with('success','  ✅پست با موفقیت حذف شد');
    }
}
