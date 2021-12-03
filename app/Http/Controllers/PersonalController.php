<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\PreOrder;
use App\Personal;
use App\Order;
use DB;
use Log;
use Validator;
use Auth;
use DateTime;
use Firebase\FirebaseLib;
use App\CompanyService;
use App\DeliveryChargeModel;
use App\LogisticsAdditional;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;
use Illuminate\Support\Str;
use config;

class PersonalController extends Controller
{

   public function loginUser(Request $request){
      $acceptHeader = $request->header('Authorization');
      $user=DB::table('personal_infos')->where('auth_access_token',$acceptHeader)->first();
      return $user->id; 
   }

    public function MergeApi(Request $request){
        $login_id=$this->loginUser($request);
        //App Settings Start
        $delivery_configurations = DB::table('delivery_configurations')->get();
        $settings = DB::table('settings')->first();
        foreach($delivery_configurations as $delivery_configuration){

            $envelope = "Envelope";
            $small_parcel = "Small";
            $large_parcel = "Large";
            // $envelope ='';
            // return $delivery_configurations->package_name;
            if(strpos($delivery_configuration->package_name, $envelope) !== false)
            {
                $package_name =  "Envelope";
            }elseif(strpos($delivery_configuration->package_name, $small_parcel) !== false){
                $package_name =  "Small Box";
            }elseif(strpos($delivery_configuration->package_name, $large_parcel) !== false){
                $package_name =  "Large Box";
            }

            // return $package_name;
            $information1['id'] = $delivery_configuration->id;
            $information1['package_name'] = $package_name;
            $information1['height'] = number_format((float)$delivery_configuration->height, 2, '.', '');
            $information1['width'] = number_format((float)$delivery_configuration->width, 2, '.', '');
            $information1['Length'] = number_format((float)$delivery_configuration->Length, 2, '.', '');
            $information1['weight'] = number_format((float)$delivery_configuration->weight, 2, '.', '');
            $information1['base_price_quick'] = $delivery_configuration->base_price_quick;
            $information1['base_price_express'] = $delivery_configuration->base_price_express;
            $information1['per_km_price_quick'] = $delivery_configuration->per_km_price_quick;
            $information1['per_km_price_express'] = $delivery_configuration->per_km_price_express;
            $information1['merchant_inside_dhaka'] = $delivery_configuration->merchant_inside_dhaka;
            $information1['merchant_outside_dhaka'] = $delivery_configuration->merchant_outside_dhaka;
            $information1['cancel_fee_express'] = $delivery_configuration->cancel_fee_express;
            $information1['cancel_fee_quick'] = $delivery_configuration->cancel_fee_quick;
            $information1['cancel_fee_for_logistices'] = $delivery_configuration->cancel_fee_for_logistices;
            $information1['flat_refund_fee'] = $delivery_configuration->flat_refund_fee;
            $information1['logistics_fee_per_km'] = $delivery_configuration->logistics_fee_per_km;
            $information1['logistics_fee_base'] = $delivery_configuration->logistics_fee_base;
            $information1['size'] = $delivery_configuration->size;
            $information1['icon'] = $delivery_configuration->icon;

            $information[] = $information1;
        }

        //App settings End

        //Start Coupon
        $user_id = $login_id;
        $coupons = DB::table('coupons')->where('user_id' , null)->get();
        //End coupon

        //Get Driver location Start
        $longitude =  $request->longitude;
        $latitude =  $request->latitude;

        $point1 = [$longitude,$latitude];
        $prefered_area_range = DB::table('prefered_area_ranges')->get();
          foreach($prefered_area_range as $prefered_area_ranges){
              $to_polygon_array = $prefered_area_ranges->range;

              $to_polygon = [];
              for($i=0;$i < count(explode(' ',$to_polygon_array))-1;$i++){
                  $to_polygon[] = explode(',',explode(' ',$to_polygon_array)[$i]);
              }
              $receiver_data = $this->mapRangeCheck($to_polygon,$point1);
              if($receiver_data == true){
                  $id = $prefered_area_ranges->id;
                  $area_name = $prefered_area_ranges->area_name;
              }
          }
        //   return $id;
          if(@$area_name != null){
            $driver_area = DB::table('driver_update_areas')->join('driver_infos', 'driver_update_areas.driver_id', '=', 'driver_infos.id')
                                                        ->where('area_id', $id)->get();
            if(!$driver_area->isEmpty()){
                foreach($driver_area as $item){
                    $information2['latitude'] = $item->latitude;
                    $information2['longitude'] = $item->longitude;
                    $information2['vehicle_type'] = $item->vehicle_type;

                    $driver_information[] = $information2;
                }
            }else{
                $driver_information = [];
            }
        }else{
            $driver_information = [];
        }
        //Get Driver Location End
        //Start Token
        $personal_check = DB::table('logistics_addional_infos')->where('user_id',auth()->user()['id'])->first();
        if($personal_check == null){
            DB::table('users')->where('id', auth()->user()['id'])->update(['fcm_token_personal' => $request->fcm_token]);
        }else{
            DB::table('users')->where('id', auth()->user()['id'])->update(['fcm_token_driver' => $request->fcm_token]);
        }
        //End Token
        $image = DB::table('coupon_images')->where('status',1)->get();
	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Setting Information',
                'package_info'=> $information,
                'coupon_image'=> $image,
                'company_info' => $settings,
                'Drivers'   => $driver_information,
        ];
    }

    public function orderlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|string',
            'to' => 'required|string',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 200);
        }
    	$order = DB::table("orders")
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->get();
    	if(count($order) > 0){
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Order found',
                    'data'=> $order
            ];
    	}else{
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Order not found',
                    'data'=> []

            ];
    	}
    }


    //check coupon
    public function checkCoupons(Request $request){  
        $coupons = DB::table('coupons')->select('coupon_text','discount_amount','id')->where('coupon_text',$request->coupon_text)->where('published' , 1)->where('expired_on','>',date("Y/m/d"))->first();
        if($coupons){
            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Coupon found',
                    'data' => $coupons
            ];
        }else{
            return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'No coupon found'
                    
            ];
        }
    }





 






}
