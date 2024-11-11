<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\category;
use App\Models\product;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{

    function category_view(){
        $ctg=category::all();
        return view('admin/add-category',['categories'=>$ctg]);
    }

//__________________________________________ Add category
    function add_category(Request $req){
        $req->validate([
            'category'=>'required|unique:category',
            'c_image'=>'required|image'
        ]);

        //save image
        $file=$req->file('c_image');
        $ctgname=$file->getClientOriginalName();
        $dstPath=public_path()."/images/category";
        $file->move($dstPath,$ctgname);
        
        //Make Folder
        $img_path=public_path()."/images/products/$req->category";
        if(!file_exists($img_path))
            File::makeDirectory($img_path);

        $newctg= new category;
        $newctg->category=$req->category;
        $newctg->image=$ctgname;
        $newctg->save();

        return redirect()->route('addCategory.view')->with('success','دسته‌بندی جدید با موفقیت ثبت شد');
    }

    function remove_category(Request $req){
        $product=product::where('category',$req->category)->first();
        if($product)
            return redirect()->route('addCategory.view')->with('error','لطفا ابتدا دسته‌بندی محصولاتی که دارای این دسته بندی هستند را تغییر دهید');
        category::where('category',$req->category)->delete();
        return redirect()->route('addCategory.view')->with('success','دسته‌بندی با موفقیت حذف شد');
    }
}
