<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Model\DeliveryZipCode;
use App\Http\Resources\CityResource;

class CityController extends Controller
{
    public function getCities($id=null)
    {

        try {
            if($id!= null){

                $cities = DeliveryZipCode::find($id);
                $cities= new CityResource( $cities);

            }else{
                $cities = DeliveryZipCode::get();
                $cities=CityResource::collection( $cities);

            }

        } catch (\Exception $e) {
        }

        return response()->json($cities,200);
    }
}
