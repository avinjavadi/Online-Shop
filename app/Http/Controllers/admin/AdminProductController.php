<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\category;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AdminProductController extends Controller
{
    function product_management(){
        $categories=category::all();
        $product=product::where(['category'=>'موبایل' ,'product_status'=>1])->get();
        return view('admin/product-management' , [ 'categories'=>$categories , 'products'=>$product]);
    }
    function product_control(Request $req){
        $output="";
        $products=product::where(['category'=>$req->category , 'product_status'=>1])->get();
        if($products){
           foreach($products as $product){
               $output.='
               <div class="d-flex justify-content-center row my-3 ">
                            <div class="col-md-10 shadow">
                                <div class="row p-2 bg-light rounded">
                                    <div class="col-md-4 mt-1">
                                        <a href="/details/'.$product->id.'">
                                            <img class="img-fluid rounded shadow"  src="'.URL::asset("images/products/$req->category/$product->image").'">
                                        </a>
                                    </div>
                                    <div class="col-md-5 mt-1 d-flex align-items-center justify-content-center">
                                        <p class="form-center">'.$product->name.'</p>
                                    </div>
                                    <div class="col-md-3 mt-1 d-flex justify-content-center align-items-center flex-column">
                                        <div>
                                            <a class="btn btn-eshop" href="'.route("edit.view",$product->id).'">ویرایش</a>
                                        </div>
                                        <div class=" mt-4 ">
                                            <div class="btn btn-remove" onclick='.'remove_product('.$product->id.')'.'>حذف‌<i class="fas fa-trash-alt"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
               ';
           }
        }
        return $output;
    }

    //__________________________________________ Edit product 
    function edit_product_view($id){
        try{
            $product=product::where('id',$id)->first();
        }
        catch(\Exception $exception){
            abort(404);
        }
        $ctg=category::all();
        return view('admin/edit-product',['product'=>$product , 'category' =>$ctg]);
    }
    function edit_product(Request $req , $id){
        $req->validate([
            'price'=>'Nullable|numeric',
            'image'=>'Nullable|image'
        ]); 

        if($req->name)
            product::where('id',$id)->update(['name'=>$req->name]); 
        if($req->price) 
            product::where('id',$id)->update(['price'=>$req->price]);
        if($req->description) 
            product::where('id',$id)->update(['description'=>$req->description]);
        if($req->category != 'not_selected')
            product::where('id',$id)->update(['category'=>$req->category]);
        

        if($req->file('image')){
            $file=$req->file('image');
            $productname=$file->getClientOriginalName();
            $dstPath=public_path()."/images/products/$req->category";
            $file->move($dstPath,$productname);
        }

        return redirect()->route('editProduct',['id'=>$id])->with('success','تغییرات با موفقیت اعمال شدند');
    }

    //__________________________________________ Remove product 
    function remove_product($id){
        product::where('id',$id)->update(['product_status'=>0]);
        return redirect()->route('product.management')->with('success','محصول مورد نظر با موفقیت حذف شد');
    }

    //__________________________________________ Search product 
    function search_product(Request $req){
        $output="";
        $products=product::where('name','like','%'.$req->search.'%')->get();
        if(!$products->all()){
            $output="<div class="."text-center".">محصولی یافت نشد!</div>";
            return $output;
        }
        else{
           foreach($products as $product){
               $image=URL::asset("images/products/$product->category/$product->image");
               $output.='
               <div class="d-flex justify-content-center row my-3 ">
                            <div class="col-md-10 shadow">
                                <div class="row p-2 bg-light rounded">
                                    <div class="col-md-4 mt-1">
                                        <a href="/details/'.$product->id.'">
                                            <img class="img-fluid rounded shadow"  src='.$image.'>
                                        </a>
                                    </div>
                                    <div class="col-md-5 mt-1 d-flex align-items-center justify-content-center">
                                        <p class="form-center">'.$product->name.'</p>
                                    </div>
                                    <div class="col-md-3 mt-1 d-flex justify-content-center align-items-center flex-column">
                                        <div>
                                            <a class="btn btn-eshop" href="'.route("edit.view",$product->id).'">ویرایش</a>
                                        </div>
                                        <div class=" mt-4 ">
                                            <div class="btn btn-remove" onclick='.'remove_product('.$product->id.')'.'>حذف‌<i class="fas fa-trash-alt"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
               ';
           }
           return $output;
        }
        
    }

    //__________________________________________Add New product
    function add_product_view(){
        $category=category::all();
        return view('admin/add-product',['category'=>$category]);
    }
    function add_product(Request $req){
        $req->validate([
            'name'=>'required',
            'price'=>'required | numeric',
            'description'=>'required',
            'image'=>'required|image'
        ]);

        //save image
        $file=$req->file('image');
        $productname=$file->getClientOriginalName();
        $dstPath=public_path()."/images/products/$req->category";
        $file->move($dstPath,$productname);

        $newProduct= new product;
        $newProduct->name=$req->name;
        $newProduct->price=$req->price;
        $newProduct->category=$req->category;
        $newProduct->description=$req->description;
        $newProduct->sale_quantity=0;
        $newProduct->rating=0;
        $newProduct->image=$productname;
        $newProduct->save();

        return redirect()->route('admin.panel')->with('success',' ✅محصول جدید با موفقیت اضافه شد');
    }
}
