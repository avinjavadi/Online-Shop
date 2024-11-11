<?php

namespace App\Http\Controllers;

use App\Models\cart;
use App\Models\category;
use App\Models\order;
use App\Models\post;
use App\Models\product;
use App\Models\rate;
use App\Models\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class productController extends Controller
{
    //________________________________________ Save average og rating of products
    function product_rating_avg(){
        $products=product::all();
        foreach($products as $prduct){
            $rate=rate::where('product_id' , $prduct->id)->avg('rating');
            if(!$rate)
                $rate=0;
            product::where('id' , $prduct->id)->update(['rating'=>$rate]);
        }
    }

    //________________________________________ Main page view
    function index(){
        // products by category
        $category=category::all();

        // products by rating 
       $this->product_rating_avg();
       $rate=product::all()->sortBy('rating')->reverse();
       $sell=product::all()->sortBy('sale_quantity')->reverse();
       $time=product::all()->sortBy('created_at')->reverse();

        return view('index',['categories'=>$category ,'productByRate'=>$rate ,'productBySell'=>$sell ,'productByTime'=>$time]);
    }

    //________________________________________ Product page by category
    function products($category){
        $products=product::where('category',$category)->get();
        if(!$products->all())
            abort(404);

        return view('products',['products'=>$products , 'category'=>$category]);
    }

    //________________________________________ Product sort by Column
    function group_by($column){
        $product=product::all()->sortBy($column)->reverse();
        $list=array_slice($product->all(),0,20);
        return view('product_group' , ['products'=>$list]);
    }

    //________________________________________ product details view
    function details($id){
        try{
            $detail=product::findOrFail($id);
        }
        catch(\Exception $exception){
            abort(404);
        }

        //////////////////////// Rating
        $allow_rate=false;
        $rate='';
        if(Session::has('user')){
            $user_id=Session::get('user')['id'];
            //Check if user han ordered this product or no
            $product=order::where(['user_id'=>$user_id ,'product_id'=>$id])->first();
            if($product){
                //Check if user rate  this product or no
                $allow_rate=true;

                $user_rate=rate::where(['user_id'=>$user_id , 'product_id'=>$id])->first();
                if($user_rate)
                    $rate = $user_rate->rating;

            }
        }
        return view('details',['details'=>$detail , 'allow_rate'=>$allow_rate , 'rate'=>$rate]);
    }

    //________________________________________ Add product to Cart
    function add_cart($id){
        $user_id=Session::get('user')['id'];
        $cart=new cart;
        $cart->user_id=$user_id;
        $cart->product_id=$id;
        $cart->save();

        return redirect()->route('product.details',['id'=>$id])->with('sucessMessage','محصول مورد نظر با موفقیت به سبد خرید شما اضافه شد✅');

    }

    //________________________________________ Number of products exist in cart
    static function cart_count(){
        $user_id=Session::get('user')['id'];
        return cart::where('user_id',$user_id)->count();
    }

    //________________________________________ products detail exist in cart
    function cart_list(){
        $user_id=Session::get('user')['id'];
        $products=DB::table('carts')
        ->join('products','carts.product_id','=','products.id')
        ->where('carts.user_id',$user_id)
        ->select('products.*', "carts.id as cart_id")
        ->get();

        return view('cart_list',['products'=>$products]);
    }

    //________________________________________ Remove product from cart
    function cart_remove($id){
        try{
            cart::destroy($id);
        }
        catch(\Exception $exception){
            abort(404);
        }
        return redirect()->route('cartList');
    }

    //________________________________________ order page & total price
    function order_place(){
        $user_id=Session::get('user')['id'];
        $total=DB::table('carts')
        ->join('products' , 'carts.product_id' , '=' ,'products.id')
        ->where('carts.user_id',$user_id)
        ->sum('products.price');
        $posts=post::all();
        return view('orders',['total_price'=>$total , 'posts'=>$posts]);
    }

    //________________________________________ order page & total price
    function order_post_cost(Request $req){
        $cost=post::where('post',$req->post)->select('cost')->first();
        return $cost;
    }

    //________________________________________ create order code
    function order_code(){
        $order_code= rand(1000000,9999999);
        $codeCheck=order::where('orderCode',$order_code)->first();
        if($codeCheck)
            $this->order_code();
        else
            return $order_code;
    }

    //________________________________________ Finish order process & empty the CartList & move products in order table
    function order(Request $req){
        //check address
        $req->validate([
            'address'=> 'required | regex:/(^([\- 0-9 آ-ی]+)(\d+)?$)/u'
        ]);

        //make Order Code
        $date=date('Ymd');
        $order_code=$this->order_code();

        $user_id=Session::get('user')['id'];
        $allcart=cart::where('user_id',$user_id)->get();

        //move informations into order table
        foreach($allcart as $cart){
            $order=new order;
            $order->user_id=$cart['user_id'];
            $order->product_id=$cart['product_id'];
            $order->payment_method=$req->payment;
            $order->delivery_type=$req->delivery;
            $order->address=$req->address;
            $order->orderCode=$date.$order_code;
            $order->date=$date;
            $order->save();

            product::where('id',$cart->product_id)->increment('sale_quantity');
        }
        //empty the cartlist
        cart::where('user_id',$user_id)->delete();
        return redirect()->route('home')->with('message','خرید شما با موفقیت انجام شد ✅');
    }

    //________________________________________ User Panel
    function user_panel(){
        $user_id=Session::get('user')['id'];
        //oreders history
        $orders= DB::table('orders')
        ->where('user_id' , $user_id)
        ->select('orderCode','created_at',DB::raw('count("orderCode") as occurences'))
        ->groupBy('orderCode','created_at')
        ->having('occurences', '>', 0)
        ->get()->sortByDesc('created_at');
        //user info
        $user_info=User::where('id' , $user_id)->first();
        return view('user-panel' , ['orders'=>$orders , 'info'=>$user_info]);
    }

      //________________________________________ Orders-group History
      static function order_group_list($order){
        $user_id=Session::get('user')['id'];
        $joined_order=DB::table('orders')
        ->join('products' , 'orders.product_id' , '=' , 'products.id')
        ->where(['orders.user_id'=>$user_id , 'orders.orderCode'=>$order])
        ->select('products.*','orders.*')
        ->get();
        return $joined_order;
    }

    //________________________________________ Orders-list by orderCode
    function order_list($orderCode){
        $user_id=Session::get('user')['id'];

        $products_info=DB::table('orders')
            ->join('products' , 'orders.product_id' , '=' , 'products.id')
            ->where(['orders.user_id'=>$user_id , 'orders.orderCode'=>$orderCode])
            ->select('orders.*', 'products.*' , 'products.id as pid')
            ->get();

        $order_info=DB::table('orders')
            ->where('orderCode' , $orderCode)
            ->select('orderCode','created_at','payment_method' , 'address' , 'delivery_type')
            ->groupBy('orderCode','created_at','payment_method' , 'address', 'delivery_type')
            ->first();

        return view('order_list',['orders'=>$products_info , 'info'=>$order_info]);
    }

    //________________________________________ Product Rating
    function rate_products(Request $req){
        $user_id=Session::get('user')['id'];
        $rate=rate::where(['user_id'=>$user_id , 'product_id'=>$req->pid])->first();
        if(!$rate){
            $rating=new rate;
            $rating->user_id=$user_id;
            $rating->product_id=$req->pid;
            $rating->rating=$req->rating;
            $rating->save();
        }
        else
            rate::where(['user_id'=>$user_id , 'product_id'=>$req->pid])->update(['rating'=>$req->rating]);

        return $req->rating;
    }

    //________________________________________ Live search
    function search(Request $req){
        $search= product::where('name','like','%'.$req->search.'%')->get();
        return view('search',['products'=>$search , 'search'=>$req->search]);
    }

    //________________________________________ Live search
    function live_search(Request $req){
        $output="";
        $query_result=product::where('name','like','%'.$req->search.'%')->get();
        if($req->search){
            foreach($query_result as $product){
                $img="../images/products/$product->category/$product->image";
                $output.='<a href="/details/'.$product->id.'" class="search-link text-white"><div class="mb-1 border-bottom d-flex align-items-center p-1">
                <img src="'.$img.'" class="img-fluid rounded search-img ml-1"><p class="text-center" style="width:100%;">'
                    .$product->name.
                    '</p></div></a>';
            }
            return response($output);
        }
    }

   /*  function test(Request $req){
        return $req->getHost();
    } */


}
