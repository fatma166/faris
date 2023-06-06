<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Model\DeliveryZipCode;

class CityController extends Controller
{
    public function getCities($id=null)
    {

        try {
            if($id!= null){
                $cities = DeliveryZipCode::find($id);

            }else{
                $cities = DeliveryZipCode::all();
            }

        } catch (\Exception $e) {
        }

        return response()->json($cities,200);
    }
}
