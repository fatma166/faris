<?php

namespace App\Http\Controllers\Admin;

use App\CPU\BackEndHelper;
use App\Http\Controllers\Controller;
use App\Model\DeliveryZipCode;
use App\Model\ShippingMethod;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\BusinessSetting;
use App\Model\Category;
use App\Model\CategoryShippingCost;

class ShippingMethodController extends Controller
{
    public function index_admin()
    {
        $shipping_methods = ShippingMethod::where(['creator_type' => 'admin'])->get();

        return view('admin-views.shipping-method.add-new', compact('shipping_methods'));
    }

    public function store(Request $request)
    {
       // print_r($request->all()); exit;
        if(isset($request['method_'])&& $request['method_']=="city_wise"){
            $request->validate([
                'title' => 'required|max:200',
                'duration' => 'required',
                'cost' => 'numeric',
                'city_id'=>'required|unique:shipping_methods'
            ]);
        }else {
            $request->validate([
                'title' => 'required|max:200',
                'duration' => 'required',
                'cost' => 'numeric',
            ]);
        }

        DB::table('shipping_methods')->insert([
            'creator_id'   => auth('admin')->id(),
            'creator_type' => 'admin',
            'title'        => $request['title'],
            'duration'     => $request['duration'],
            'cost'         => BackEndHelper::currency_to_usd($request['cost']),
            'status'       => 1,
            'city_id'      =>$request['city_id'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        Toastr::success('Successfully added.');
        return back();
    }

    public function status_update(Request $request)
    {
        ShippingMethod::where(['id' => $request['id']])->update([
            'status' => $request['status'],
        ]);
        return response()->json([
            'success' => 1,
        ], 200);
    }

    public function edit($id)
    {
        if ($id != 1) {
            $method = ShippingMethod::where(['id' => $id])->first();
            $cities=DeliveryZipCode::get()->toArray();
            return view('admin-views.shipping-method.edit', compact('method','cities'));
        }
        return back();
    }

    public function update(Request $request, $id)
    {

        if(isset($request['method_'])&& $request['method_']=="city_wise"){

            $request->validate([
                'title' => 'required|max:200',
                'duration' => 'required',
                'cost' => 'numeric',

                'city_id'=>'required|unique:shipping_methods',
            ]);
        }else {
            $request->validate([
                'title' => 'required|max:200',
                'duration' => 'required',
                'cost' => 'numeric',
            ]);
        }

        DB::table('shipping_methods')->where(['id' => $id])->update([
            'creator_id'   => auth('admin')->id(),
            'creator_type' => 'admin',
            'title'        => $request['title'],
            'duration'     => $request['duration'],
            'cost'         => BackEndHelper::currency_to_usd($request['cost']),
            'status'       => 1,
            'city_id'      =>$request['city_id'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        Toastr::success('Successfully updated.');
        return redirect()->back();
    }

    public function setting()
    {
        $shipping_methods = ShippingMethod::where(['creator_type' => 'admin'])/*->whereHas('shippingCity')->or*/->whereNULL('city_id')->get();
        $shipping_methods_city_wize = ShippingMethod::where(['creator_type' => 'admin'])->whereHas('shippingCity')->with('shippingCity')/*->orwhereNULL('city_id')*/->get();
        $all_category_ids = Category::where(['position' => 0])->pluck('id')->toArray();
        $category_shipping_cost_ids = CategoryShippingCost::where('seller_id',0)->pluck('category_id')->toArray();
        $cities=DeliveryZipCode::get()->toArray();
//dd($shipping_methods_city_wize); exit;
        foreach($all_category_ids as $id)
        {
            if(!in_array($id,$category_shipping_cost_ids))
            {
                $new_category_shipping_cost = new CategoryShippingCost;
                $new_category_shipping_cost->seller_id = 0;
                $new_category_shipping_cost->category_id = $id;
                $new_category_shipping_cost->cost = 0;
                $new_category_shipping_cost->save();
            }
        }
        $all_category_shipping_cost = CategoryShippingCost::where('seller_id',0)->get();
       // print_r($shipping_methods); exit;
        return view('admin-views.shipping-method.setting',compact('all_category_shipping_cost','shipping_methods','cities','shipping_methods_city_wize'));
    }
    public function shippingStore(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['type' => 'shipping_method'], [
            'value' => $request['shippingMethod']
        ]);
        //Toastr::success('Shipping Method Added Successfully!');
        //return back();
        return response()->json();
    }
    public function delete(Request $request)
    {

        $shipping = ShippingMethod::find($request->id);

        $shipping->delete();
        return response()->json();
    }

}
