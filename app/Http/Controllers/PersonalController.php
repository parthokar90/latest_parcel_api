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

    //firebase database and password
    public $firebase_database = 'https://bitoronbd-driver-default-rtdb.asia-southeast1.firebasedatabase.app'; // Masking
    public $fireabase_pass = 'LV5hhbxc4rzpGvjmej1wHHKlbnADWfRRdm3nESp8'; // Masking

    //Auth User ID
    public function loginUser(Request $request){
        $acceptHeader = $request->header('Authorization');
        $user=DB::table('personal_infos')->where('auth_access_token',$acceptHeader)->first();
        return $user->id;
    }

    // map range check
    public function mapRangeCheck($polygon,$point){

        if($polygon[0] != $polygon[count($polygon)-1]){
        $polygon[count($polygon)] = $polygon[0];
        $j = 0;
        $oddNodes = false;
        $x = $point[1];
        $y = $point[0];
        $n = count($polygon);
        for ($i = 0; $i < $n; $i++)
        {
            $j++;
            if ($j == $n)
            {
                $j = 0;
            }
            if ((($polygon[$i][0] < $y) && ($polygon[$j][0] >= $y)) || (($polygon[$j][0] < $y) && ($polygon[$i][0] >=
                $y)))
            {
                if ($polygon[$i][1] + ($y - $polygon[$i][0]) / ($polygon[$j][0] - $polygon[$i][0]) * ($polygon[$j][1] -
                    $polygon[$i][1]) < $x)
                {
                    $oddNodes = !$oddNodes;
                }
            }
        }
        return $oddNodes;
        }

    }

    //push notification send
    function pushNotificationSend($topic,$category,$title,$description,$url){
        $serverKey = 'AAAAUs5vGPM:APA91bGsORUPC1pwcQuAfo0jGYRgXirjlWrA3HiORdiTVsUTQDV1ROHR-b3SzdCcFE-6r4IerPxKI259L3BAJ2HsGc_3NQu-VWbkQbqMe6XDvDeZRnlW-2rcu36G9sRuy1PhGF3vonZE';

        $data = ['category'=>$category];

        $notification = ['click_action'=>'.MainActivity','title' =>$title ,'body' => $description,'icon'=>'https://api.parcelmagic.com/nversion/images/home/nlogo.png'];

        $arrayToSend = array('to' => $topic,'priority'=>'high','notification'=>$notification,'data'=>$data);

        $json = json_encode($arrayToSend);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: key='. $serverKey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_exec($ch);
        curl_close($ch);
    }

    // merge API
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
        $coupons = DB::table('coupons')->where('personal_user_id' , null)->get();
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
        $personal_check = DB::table('personal_infos')->where('id',$login_id)->update(['fcm_token_personal' => $request->fcm_token]);
        // dd($personal_check);
        // if($personal_check){
        //     DB::table('personal_infos')->where('id', $login_id)->update(['fcm_token_driver' => $request->fcm_token]);
        // }else{
        //     DB::table('personal_infos')->where('id', $login_id)->update(['fcm_token_driver' => $request->fcm_token]);
        // }
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

    // get diver location
    public function getDriverArea(Request $request){
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
                    $information1['latitude'] = $item->latitude;
                    $information1['longitude'] = $item->longitude;
                    $information1['vehicle_type'] = $item->vehicle_type;

                    $information[] = $information1;
                }

            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data Found',
                'data' => $information
            ];
            }else{
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'No Data Found',
                    'data' => []
            ];
            }


        }else{

            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'No Data Found',
                    'data' => []
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

    //setParcel
    public function setParcel(Request $request){

        $login_id=$this->loginUser($request);

        // dd($login_id);

        $sender_latitude   =  $request->sender_latitude;
        $sender_longitude   =  $request->sender_longitude;
        $receiver_latitude   =  $request->receiver_latitude;
        $receiver_longitude   =  $request->receiver_longitude;


        $point1 = [$sender_longitude,$sender_latitude];
        $point2 = [$receiver_longitude,$receiver_latitude];

        $prefered_area_range = DB::table('prefered_area_ranges')->get();

        foreach($prefered_area_range as $prefered_area_ranges){
            $to_polygon_array = $prefered_area_ranges->range;

            $to_polygon = [];
            for($i=0;$i < count(explode(' ',$to_polygon_array))-1;$i++){
                $to_polygon[] = explode(',',explode(' ',$to_polygon_array)[$i]);
            }
            $sender_data = $this->mapRangeCheck($to_polygon,$point1);
            $receiver_data = $this->mapRangeCheck($to_polygon,$point2);

            if($receiver_data == true){
                $receiver_area_id = $prefered_area_ranges->id;
            }
            if($sender_data == true){
                $sender_area_id = $prefered_area_ranges->id;
            }
        }

        $user_id = $login_id;
        $recp_name = strip_tags($request->recp_name);
        $invoice_no = $request->invoice_no;
        $recp_phone  = strip_tags($request->recp_phone);
        $recp_city = $request->recp_city;
        $recp_zone = $request->recp_zone;
        $recp_area	 = $request->recp_area;
        $recp_address = $request->recp_address;
        $sender_phone = strip_tags($request->sender_phone);
        $sender_name = strip_tags($request->sender_name);
        $sender_city = $request->sender_city;
        $sender_zone = $request->sender_zone;
        $sender_area	 = $request->sender_area_id;
        $sender_address = $request->sender_address;
        $item_type = $request->item_type;
    	$item_qty = $request->item_qty;
        $item_weight = 1;
        $item_description = $request->item_description;
        $special_instruction = $request->special_instruction;
        $item_price = $request->item_price;


        $payment_status = $request->payment_status;
        $delivery_date = $request->delivery_date;
        $personal_order_type = $request->personal_order_type;

        $Date = date("Y-m-d h:i:s");
        $Datebd = date("Y-m-d H:i:s", strtotime('+6 hours', strtotime($Date)));

        if($personal_order_type == 2){
            $delivery_date = date('Y-m-d H:i:s', strtotime($Datebd. ' + 4 hours'));
        }else{
            $delivery_date = date('Y-m-d H:i:s', strtotime($Datebd. ' + 8 hours'));
        }
        $who_will_pay = $request->who_will_pay;
        $delivery_charge = $request->delivery_charge;
        $logistics_charge = ($request->delivery_charge * 25)/100;
        $dimention = 1;

        if($who_will_pay == 0){
            $cod = 1;
            $coc = 0;
            $current_status = 2;
        }else{
            if(isset($request->ssl_transaction_id) && $request->ssl_transaction_id != null){
                $current_status = 1;
                $coc = 0;
            }else{
                $current_status = 2;
                $coc = 1;
            }
            $cod = 0;
        }

        $payment_type = 2;





        // $preOrder = new PreOrder();
        // $preOrder->recp_name =   $recp_name;
        // $preOrder->recp_phone =   $recp_phone;
        // $preOrder->recp_city =   $recp_city;
        // $preOrder->recp_zone =   $recp_zone;
        // $preOrder->recp_area =   $receiver_area_id;
        // $preOrder->recp_address =   $recp_address;
        // $preOrder->pic_name =   $pick_name;
        // $preOrder->pic_phone =   $pick_phone;
        // $preOrder->pick_city =   $pick_city;
        // $preOrder->pick_area_id =   $sender_area_id;
        // $preOrder->pick_zone =   $pick_zone;
        // $preOrder->pick_address =   $pick_address;
        // $preOrder->item_type =   $item_type;
        // $preOrder->item_weight =   $item_weight;
        // $preOrder->item_des =   $item_des;
        // $preOrder->item_qty =   $item_qty;
        // $preOrder->item_price =   $item_price;
        // $preOrder->special_instruction =  $special_instruction;
        // $preOrder->invoice_no = $invoice_no;
        // $preOrder->Save();
        // if($preOrder->Save()){

            $order = new Order();

            $Date = date("Y-m-d h:i:s");
            $Datebd = date("Y-m-d H:i:s", strtotime('+6 hours', strtotime($Date)));
            // $personal_order_type = 1 then regular , 8 hours
            if($personal_order_type == 2){
                $order->delivery_date = date('Y-m-d H:i:s', strtotime($Datebd. ' + 4 hours'));
            }else{
                $order->delivery_date = date('Y-m-d H:i:s', strtotime($Datebd. ' + 8 hours'));
            }
            $order->invoice_no = $invoice_no;
            $order->personal_user_id = $user_id;
            $order->order_type = 2;
            $order->current_status = 2;
            $order->order_date = $Datebd;
            $order->dimention = $dimention;
            $order->logistics_charge = $logistics_charge;
            $order->who_will_pay = $who_will_pay;
            $order->coc = $coc;
            $order->cod = $cod;
            $order->delivery_charge = $delivery_charge;
            $order->personal_order_type = $personal_order_type;
            $order->billing_status = 0;
            $order->active = 1;
            $order->order_additional_details =  $request->order_additional_details;
            $order->coupon_amount  = $request->coupon_amount;
            $order->sender_latitude = $request->sender_latitude;
            $order->sender_longitude = $request->sender_longitude;
            $order->receiver_latitude = $request->receiver_latitude;
            $order->receiver_longitude = $request->receiver_longitude;
            $order->distance = $request->distance;

            $order->recp_name =   $recp_name;
            $order->recp_phone =   $recp_phone;
            $order->recp_city =   $recp_city;
            $order->recp_zone =   $recp_zone;
            $order->recp_area =   $receiver_area_id;
            $order->recp_address =   $recp_address;
            $order->sender_name =   $sender_name;
            $order->sender_phone =   $sender_phone;
            $order->sender_city =   $sender_city;
            $order->sender_area_id =   $sender_area_id;
            $order->sender_zone =   $sender_zone;
            $order->sender_address =   $sender_address;
            $order->item_type =   $item_type;
            $order->item_weight =   $item_weight;
            $order->item_description =   $item_description;
            $order->item_qty =   $item_qty;
            $order->item_price =   $item_price;
            $order->special_instruction =  $special_instruction;

            $order->save();
            $order_lastinsert_id = $order->id;




            // DB::table('order_distance')->insert(
            //      array(
            //             'sender_latitude'   =>  $request->sender_latitude,
            //             'sender_longitude'   =>  $request->sender_longitude,
            //             'receiver_latitude'   =>  $request->receiver_latitude,
            //             'receiver_longitude'   =>  $request->receiver_longitude,
            //             'distance'   =>  $request->distance,
            //             'invoice_no'   =>  $invoice_no
            //      )
            // );




            // DB::table('journal')->insert(
            //      array(
            //             'invoice_no' => $invoice_no,
            //             'amount' => $delivery_charge,
            //             'db' => 'debit',
            //             'account_title_id' => 1,
            //             'input_user_id' => 1
            //      )
            // );


            // DB::table('journal')->insert(
            //      array(
            //             'invoice_no' => $invoice_no,
            //             'amount' => $delivery_charge,
            //             'db' => 'credit',
            //             'account_title_id' => $user_id,
            //             'input_user_id' => 1
            //      )
            // );


            DB::table('system_status_logs')->insert(
                ['invoice_id' => $invoice_no, 'status' => $current_status]
            );







    		$url = "https://fcm.googleapis.com/fcm/send";
            $category = 'place_order';
            $order_id = $order_lastinsert_id;
            $title = "Order placed succesfully. Invoice no:".$invoice_no;
            $description = "click here for see details";
            $id = 1;
            $order_distances = DB::table('prefered_area_ranges')->get();
            foreach($order_distances as $order_distance){
                      $to_polygon_array = $order_distance->range;

                      $to_polygon = [];
                      for($i=0;$i < count(explode(' ',$to_polygon_array))-1;$i++){
                        $to_polygon[] = explode(',',explode(' ',$to_polygon_array)[$i]);
                      }




                      $point1 = [$request->sender_longitude,$request->sender_latitude];
                      $point2 = [$request->receiver_longitude,$request->receiver_latitude];

                      $sender_data = $this->mapRangeCheck($to_polygon,$point1);

                      $receiver_data = $this->mapRangeCheck($to_polygon,$point2);

                      if($sender_data == true){

                        $area_name = "/topics/".$order_distance->area_name;
                        //dd($area_name);
                        $this->pushNotificationSend($area_name,$category,$title,$description,$url);
                      }elseif($receiver_data == true){
                        $area_name = "/topics/".$order_distance->area_name;
                        //dd($area_name);
                        $this->pushNotificationSend($area_name,$category,$title,$description,$url);
                      }

                }



            /*
            $url = 'https://portal.adnsms.com/api/v1/secure/send-sms';
            $params = array(
                "api_key" => 'KEY-ca5821sy646rc3u4ni8im98kz8lfw9xu',
                "api_secret" => 'R6SjLaKhxwy25GJr',
                "request_type" => 'SINGLE_SMS',
                "message_type"  => 'TEXT',
                "mobile" => $pick_phone,
                "message_body" => 'Dear sir,Your parcel placed successfully. Invoice no:'.$invoice_no
            );

            $query_string = http_build_query($params);

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            curl_close($ch);
            */

            $firebase = new FirebaseLib($this->firebase_database,$this->fireabase_pass);


            $test = [
                'invoice_no' => $invoice_no,
                'sender_address'   => $sender_address,
                'receiver_address'   => $recp_address,
                'earning'   =>  (float)$logistics_charge,
                'sender_area'   => $sender_area_id,
                'sender_lat'   =>  (float)$request->sender_latitude,
                'sender_long'   =>  (float)$request->sender_longitude,
                'receiver_lat'   => (float)$request->receiver_latitude,
                'receiver_long'   => (float)$request->receiver_longitude,
                'receiver_area'   =>  $receiver_area_id,
                'order_date' => date("Y-m-d h:i:s"),
                'driver_type' => 1,
                'delivery_date' => $delivery_date,
                'distance' => (float)$request->distance,
                'qty' => (int)$item_qty,
                'type' => (int)$item_type
            ];
            $dateTime = new DateTime();
            $firebase->set("Parcelmagic" . '/' . $invoice_no,$test);

            $data = array(
                    "invoice_no"=>$invoice_no,
                    "receiver_address"=>$recp_address,
                    "sender_address"=>$sender_address,
                    "distance"=>$request->distance,
                    "qty"=>$item_qty,
                    "type"=>$item_type,
                    "earning"=>$logistics_charge,
                    'area_id' => $sender_area_id
                );



           $api_request_url = 'http://103.112.53.91:3000/set-parcel';

           $ch_curl=curl_init($api_request_url);
           curl_setopt($ch_curl, CURLOPT_POST, true);
           curl_setopt($ch_curl, CURLOPT_POSTFIELDS,$data);
           curl_setopt($ch_curl, CURLOPT_FRESH_CONNECT, true);


           curl_exec($ch_curl);
           curl_close($ch_curl);

            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'order placed successfully',
                    // 'data' => []
            ];


        // }else{
        //     return [
        //             'status' => 200,
        //             'success' => true,
        //             'msg' => 'Error',
        //             // 'data' => []
        //     ];
        // }

    }

    // all ongoing order
    public function allOngoingOrder(Request $request){

        $login_id=$this->loginUser($request);
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("orders")
    	            ->where('personal_user_id',$login_id)
    	            ->whereIn('current_status',[1,2,3,4])
    	            ->orderBy('created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();

    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("driver_trackings")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_trackings.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = null;
            }

            if($tracking_info != null && count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
        	                                 ->select('id','username','phone','image','email')
            	                             ->where('id','=',$tracking->driver_id)
                	                         ->first();
            }else{
                $driver_user_info = null;
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['driver_trackings'] = $orders->current_status != 2 ? $tracking_info : null;
    	    $orderList['driver_user_info'] = $orders->current_status != 2 ? $driver_user_info : null;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['sender_address'] = $orders->sender_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";
    	    $msg[] = $orderList;
    	}

        //dd($orderList);

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'no data found',
                'data' => []
            ];
    	}



    }

    // current order list quick
    public function currentorderlistQuick(Request $request){

        $login_id=$this->loginUser($request);
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("orders")
    	            ->where('personal_user_id',$login_id)
    	            ->where('personal_order_type',1)
    	            ->whereIn('current_status',[1,2,3,4])
    	            ->orderBy('created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();


    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("driver_trackings")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_trackings.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
                                            ->select('id','username','phone','image','email')
            	                            ->where('id','=',$tracking->driver_id)
                	                        ->first();
            }else{
                $driver_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['driver_trackings'] = $orders->current_status != 2 ? $tracking_info : null;
    	    $orderList['logistics_user_info'] = $orders->current_status != 2 ? $driver_user_info : null;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['sender_address'] = $orders->sender_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";
    	    $msg[] = $orderList;
    	}

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'no data found',
                'data' => []
            ];
    	}



    }

    // currennt order list express
    public function currentorderlistExpress(Request $request){
        $login_id=$this->loginUser($request);
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("orders")
                    ->where('personal_user_id',$login_id)
    	            ->where('personal_order_type',2)
    	            ->whereIn('current_status',[1,2,3,4])
    	            ->orderBy('created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("driver_trackings")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_trackings.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
                                            ->select('id','username','phone','image','email')
            	                            ->where('id','=',$tracking->driver_id)
                	                        ->first();
            }else{
                $driver_user_info = [];
            }
    	   // $orderList['id'] = $orders->id;
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['driver_trackings'] = $orders->current_status != 2 ? $tracking_info : null;
    	    $orderList['driver_user_info'] = $orders->current_status != 2 ? $driver_user_info : null;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['sender_address'] = $orders->sender_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";
    	    $msg[] = $orderList;
    	}

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No data found',
                'data' => []
            ];
    	}



    }

    // all orderlist
    public function allOrderList(Request $request){
        $login_id=$this->loginUser($request);
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("orders")
                    ->where('personal_user_id',$login_id)
    	            ->whereIn('current_status',[5,13])
    	            ->orderBy('created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();
                    // dd($order);



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){
            // dd($orders);
    	    $tracking_info = [];
    	    $tracking = DB::table("driver_trackings")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_trackings.invoice_no','=',$orders->invoice_no)
            	            ->first();
                            // dd($tracking);

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
                                            ->select('id','username','phone','image','email')
            	                            ->where('id','=',$tracking->driver_id)
                	                        ->first();
            }else{
                $driver_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['delivery_date'] = $orders->delivery_date;
    	    $orderList['driver_trackings'] = $orders->current_status == 13?null:$tracking_info;
    	    $orderList['driver_user_info'] = $orders->current_status == 13?null:$driver_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['sender_address'] = $orders->sender_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $orderList['is_rated'] = $orders->isRated;
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";

    	    $msg[] = $orderList;
    	}

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No data found',
                'data' => []
            ];
    	}


    }

    // order list regular
    public function orderlistRegular(Request $request){

        $login_id=$this->loginUser($request);
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;
        $order  = DB::table("orders")
                    ->where('personal_user_id',$login_id)
    	            ->where('personal_order_type',1)
    	            ->whereIn('current_status',[5,13])
    	            ->orderBy('created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("driver_trackings")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_trackings.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
                                            ->select('id','username','phone','image','email')
            	                            ->where('id','=',$tracking->driver_id)
                	                        ->first();
            }else{
                $driver_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['delivery_date'] = $orders->delivery_date;
    	    $orderList['driver_trackings'] = $orders->current_status == 13?null:$tracking_info;
    	    $orderList['logistics_user_info'] = $orders->current_status == 13?null:$driver_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['sender_address'] = $orders->sender_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";
    	    $orderList['is_rated'] = $orders->isRated;
    	    $msg[] = $orderList;
    	}

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No data found',
                'data' => []
            ];
    	}


    }

    // order list express
    public function orderListExpress(Request $request){

        $login_id=$this->loginUser($request);
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("orders")
                    ->where('personal_user_id',$login_id)
    	            ->where('personal_order_type',2)
    	            ->whereIn('current_status',[5,13])
    	            ->orderBy('created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("driver_trackings")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_trackings.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
                                            ->select('id','username','phone','image','email')
            	                            ->where('id','=',$tracking->driver_id)
                	                        ->first();
            }else{
                $driver_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['delivery_date'] = $orders->delivery_date;
    	    $orderList['driver_trackings'] = $orders->current_status == 13?null:$tracking_info;
    	    $orderList['logistics_user_info'] = $orders->current_status == 13?null:$driver_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['sender_address'] = $orders->sender_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $orderList['is_rated'] = $orders->isRated;
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";

    	    $msg[] = $orderList;
    	}

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No data found',
                'data' => []
            ];
    	}


    }

    // set rating
    public function setRating(Request $request){

        $login_id=$this->loginUser($request);
        $driverId = $request->driver_id;
        $rating = $request->rating_point;
        $invoice = $request->invoiceId;

        $ratingPoint = DB::table('parcel_ratings')->insert([
            "rating_point" => $rating,
            "driver_id" => $driverId,
            "invoiceId" => $invoice,
            "personal_id" => $login_id
            ]);

        $ratedInvoice = DB::table('orders')->where('invoice_no', $invoice)->update(['isRated' => 1]);

        return [
                'status' => 200,
                'success' => true,
                'msg' => 'Rating added successfully'
        ];

    }

    // personal user update
    public function userupdate(Request $request){
        $login_id=$this->loginUser($request);

        $validator = Validator::make($request->all(), [
            'username' => 'string',
            'image' => 'mimes:jpg,png,jpeg'
        ]);


        if ($validator->fails())
        {
            return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'error',
                        'data' => $validator->errors()->first()
                ];
        }

        if(isset($request->username) && isset($request->image)){

            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $photo_link = "https://api.parcelmagic.com/img/users/".$photo;

            $public_url = str_replace('/api/', '/', public_path());
            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }

            $username = strip_tags($request->username);
            $users = DB::table('personal_infos')->where('id',$login_id)->update(array('username'=>$username, 'image'=>$photo_link));

        }elseif(isset($request->username)){
            $username = strip_tags($request->username);
            $users = DB::table('personal_infos')->where('id',$login_id)->update(array('username'=>$username));
        }elseif(isset($request->image)){
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $photo_link = "https://api.parcelmagic.com/img/users/".$photo;
            $users = DB::table('personal_infos')->where('id',$login_id)->update(array('image'=>$photo_link));
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $public_url = str_replace('/api/', '/', public_path());

            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }

        }
        $users = DB::table('personal_infos')->select('id','username','image','phone')->where('id',$login_id)->first();
    	if(count((array) $users) > 0){
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Updated successfully',
                    'user' => $users

            ];
    	}else{
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Update error'
            ];
    	}

    }

    // user based coupon
    public function userBasedCoupon(Request $request){

        $login_id=$this->loginUser($request);

        $coupons = DB::table('coupons')
                    ->select('id','coupon_text','discount_amount')
                    ->where('personal_user_id' ,'=', $login_id)
                    ->where('published' , 1)
                    ->where('expired_on','>',date("Y/m/d"))
                    ->get();


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
                    'success' => true,
                    'msg' => 'No active Coupons found',

            ];
        }

    }

    // delete fcm token
    public function deleteFcmToken(Request $request){

        $login_id=$this->loginUser($request);

        $personal_check = DB::table('personal_infos')->where('id',$login_id)->update(['fcm_token_personal' => null]);

        return [
            'status' => 200,
            'success' => true,
            'msg' => 'Sucessfully logout',
        ];

    }

    // coupon image
    public function couponImage(){

        $image = DB::table('coupon_images')->where('status',1)->get();

        if(count($image) != 0){
           return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data found',
                'data' => $image
            ];
        }else{
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'No data data',
                'data' => []
            ];
        }

    }

    // order list
    public function orderlistPersonal(Request $request){

        $login_id=$this->loginUser($request);

        $validator = Validator::make($request->all(), [
            'from' => 'required|string',
            'to' => 'required|string',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 200);
        }
    	$order = DB::table("orders")
    	            ->where('personal_user_id',$login_id)
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

    // order details
    public function orderDetails($id){

    	$order = DB::table("orders")
    	            ->where('invoice_no',$id)
    	            ->get();

    	if(count($order) > 0){
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'data found',
                    'data' => $order[0]
            ];
    	}else{
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'data not found'
            ];
    	}
    }

    // pending order list
    public function pendingOrderList(Request $request){
        $area_order_list = DB::table('orders')
                                    ->join('pre_orders','pre_orders.invoice_no','=','orders.invoice_no')
                                    ->join('order_distance','order_distance.invoice_no','=','orders.invoice_no')
                                    ->select('orders.invoice_no as invoice_no','orders.delivery_date as delivery_date','orders.logistics_charge as earning','orders.created_at as order_date','pre_orders.recp_address as receiver_address','pre_orders.pick_address as sender_address','pre_orders.pick_area_id as sender_area','order_distance.distance as distance','pre_orders.item_qty as qty','pre_orders.item_type as type')
                                    ->whereIn('orders.current_status',[1,2])
                                    ->where('pre_orders.pick_area_id',$request->area_id)
                                    ->orderBy('orders.id','desc')
                                    ->get();

        return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Data Found',
                    'data' => $area_order_list
            ];
    }

    // collected order list quick
    public function collectedorderlistQuick(Request $request){

        $login_id=$this->loginUser($request);

    	$order  = DB::table("orders")
                    ->where('personal_user_id',$login_id)
    	            ->where('current_status',4)
    	            ->where('personal_order_type',1)
    	            ->orderBy('created_at', 'desc')
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("driver_infos")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_infos.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
                                            ->select('id','username','phone','image','email')
            	                            ->where('id','=',$tracking->driver_id)
                	                        ->first();
            }else{
                $driver_user_info = [];
            }
    	    $orderList['id'] = $orders->id;
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status_msg'] = $this->OrderAppStatus($orders->current_status);
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['logistics_charge'] = number_format((float)$orders->logistics_charge, 2, '.', '');
    	    $orderList['driver_infos'] = $tracking_info;
    	    $orderList['logistics_user_info'] = $driver_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['who_will_pay'] = $orders->who_will_pay == 0? "Receiver":"Sender";
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['pick_address'] = $orders->pick_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $msg[] = $orderList;
    	}

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => []
            ];
    	}


    }

    // collected order list express
    public function collectedorderlistExpress(Request $request){

        $login_id=$this->loginUser($request);
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;


    	$order  = DB::table("orders")
                    ->where('personal_user_id',$login_id)
    	            ->where('current_status',4)
    	            ->where('personal_order_type',2)
    	            ->orderBy('created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("driver_infos")
            	            ->select('invoice_no','created_at','reschedule_date','driver_id')
            	            ->where('driver_infos.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $driver_user_info = DB::table("driver_infos")
                                            ->select('id','username','phone','image','email')
            	                            ->where('id','=',$tracking->driver_id)
                	                        ->first();
            }else{
                $driver_user_info = [];
            }
    	    $orderList['id'] = $orders->id;
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status_msg'] = $this->OrderAppStatus($orders->current_status);
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['logistics_charge'] = number_format((float)$orders->logistics_charge, 2, '.', '');
    	    $orderList['driver_infos'] = $tracking_info;
    	    $orderList['logistics_user_info'] = $driver_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
    	    $orderList['who_will_pay'] = $orders->who_will_pay == 0? "Receiver":"Sender";
    	    $orderList['personal_order_type'] = $orders->personal_order_type == 1? "Regular":"Express";
    	    $orderList['item_type'] = $orders->item_type;
    	    $orderList['item_qty'] = $orders->item_qty;
    	    $orderList['distance'] = number_format((float)$orders->distance, 2, '.', '');
    	    $orderList['recp_name'] = $orders->recp_name;
    	    $orderList['recp_phone'] = $orders->recp_phone;
    	    $orderList['recp_address'] = $orders->recp_address;
    	    $orderList['pick_address'] = $orders->pick_address;
    	    $orderList['sender_latitude'] = $orders->sender_latitude;
    	    $orderList['sender_longitude'] = $orders->sender_longitude;
    	    $orderList['receiver_latitude'] = $orders->receiver_latitude;
    	    $orderList['receiver_longitude'] = $orders->receiver_longitude;
    	    $msg[] = $orderList;
    	}

    	if(count($orderList) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => $msg
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'data found',
                'data' => []
            ];
    	}


    }

    // user info
    public function userInfo(Request $request){

        $login_id=$this->loginUser($request);

        $users = DB::table('personal_infos')->where('id',$login_id)->get();
    	if(count((array) $users) > 0){

            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Data found',
                    'data' => $users
            ];
    	}else{

            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'No data found',
                    'data' => []
            ];
    	}
    }

    // personal profile
    public function personalprofile(Request $request){
        $login_id=$this->loginUser($request);

    	$response = DB::table('personal_infos')->where('id',$login_id)->get();


    	if(count($response) == 0){
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'No user found',
                'data' => []
            ];
        }else{
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'User Found',
                'data' => $response,
            ];
        }

    }

}
