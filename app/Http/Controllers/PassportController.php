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

class PassportController extends Controller
{


    public function __construct()
    {
            $this->middleware('guest')->except('logout');
    }


    public static $endpoint = 'http://bulksms.m2mbd.net/smsapi';
    public static $api_key = 'C20028415c1f1afcd61ca0.08017082';
    //public static $senderid = '8801847169884'; // NON Masking
    public static $senderid = 'Airposted'; // Masking
    public $firebase_database = 'https://bitoronbd-driver-default-rtdb.asia-southeast1.firebasedatabase.app'; // Masking
    public $fireabase_pass = 'LV5hhbxc4rzpGvjmej1wHHKlbnADWfRRdm3nESp8'; // Masking

    public static $errors = [
        "1002"  => "Sender Id/Masking Not Found",
        "1003"  => "API Not Found",
        "1004"  => "SPAM Detected",
        "1005"  => "Internal Error",
        "1006"  => "Internal Error",
        "1007"  => "Balance Insufficient",
        "1008"  => "Message is empty",
        "1009"  => "Message Type Not Set (text/unicode)",
        "1010"  => "Invalid User & Password",
        "1011"  => "Invalid User Id",
    ];




    function getLogs($message, $type=7){

        switch ($type) {
            case 1:
               Log::error($message);
            break;
            case 2:
               Log::emergency($message);
            break;
            case 3:
               Log::critical($message);
            break;
            case 4:
               Log::warning($message);
            break;
            case 5:
               Log::notice($message);
            break;
            case 6:
               Log::info($message);
            break;
            case 7:
               Log::debug($message);
            break;

            default:
                Log::debug($message);
                break;
        }
    }
    private function calDistance($lat1, $lon1, $lat2, $lon2, $unit)
    {

      //return 5.69;

      if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
      } else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
          return (($miles * 1.609344));
        } else if ($unit == "N") {
          return ($miles * 0.8684);
        } else {
          return $miles;
        }
      }
    }




    function testFireabse(){

        $firebase = new FirebaseLib($this->firebase_database,$this->fireabase_pass);

        $rand = rand(1,10000000000000);
        $invoice_no = "PM".$rand;
        // Storing an array
        $test = [
            'invoice_no' => $invoice_no,
            'sender_address'   => "Road# 14, Sector#4, Uttara Dhaka",
            'receiver_address'   => "Road# 1, Sector#1, Uttara Dhaka",
            'earning'   =>  140.00,
            'sender_area'   =>  1,
            'sender_lat'   =>  23.872271352739656,
            'sender_long'   =>   90.38884403921301,
            'receiver_lat'   =>   23.872899940243958,
            'receiver_long'   =>  90.40300934958533,
            'receiver_area'   =>  14,
            'order_date' => date("Y-m-d h:i:s"),
            'driver_type' => 1,
            'distance' => 3.4,
            'qty' => 3,
            'type' => 1
        ];
        $dateTime = new DateTime();
        $firebase->set("Parcelmagic" . '/' . $invoice_no,$test);


    }

    public function lastFiveminutesOrder(){

        $firebase = new FirebaseLib($this->firebase_database,$this->fireabase_pass);
        $parcel_magics = json_decode($firebase->get('Parcelmagic'));


        foreach($parcel_magics as $parcel_magic){

                $date = new DateTime;
                $date->modify('-5 minutes');
                $formatted_date = $date->format('Y-m-d H:i:s');

                $result = DB::table('orders')->where('created_at','>=',$formatted_date)->where('invoice_no',$parcel_magic->invoice_no)->first();
                if($result != null){

                    $data = [
                        'driver_type' => 2,
                    ];

                    $firebase->update("Parcelmagic" . '/' . $parcel_magic->invoice_no, $data); // updates data in Firebase
                }

        }

    }
    
    
    public function appSettings(){
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

            $information1['package_name'] = $package_name;
            $information1['height'] = $delivery_configuration->height;
            $information1['width'] = $delivery_configuration->width;
            $information1['Length'] = $delivery_configuration->Length;
            $information1['weight'] = $delivery_configuration->weight;
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

            $information[] = $information1;
        }
	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Setting Information',
                'package_info'=> $information,
                'company_info' => $settings

        ];
    }



    //driver lat and long log history with json file
    public function driverLog($userid,$lat,$long){
        $dataResults = file_get_contents('driver/driver_log.json');
        $parsedJson = json_decode($dataResults, true);
        $flag        = false;
        if($parsedJson!=null){
           foreach ($parsedJson as $key => $value) {
               if ($value['user_id'] == $userid && $value['date'] == date('Y-m-d')) {
                   $flag = true;
               }
           }
        }
        if($flag){
           foreach ($parsedJson as $key => $value) {
               if ($value['user_id'] == $userid && $value['date'] == date('Y-m-d')) {
                  $newLat=$value['latitude'].','.$lat;
                  $newLong=$value['longitude'].','.$long;
                  $parsedJson[$key]['latitude'] = $newLat;
                  $parsedJson[$key]['longitude'] = $newLong;
               }
               file_put_contents('driver/driver_log.json', json_encode($parsedJson));
           }
        }else{
           $additionalArray = array(
               'user_id' => $userid,
               'date' => date('Y-m-d'),
               'latitude' => $lat,
               'longitude' => $long
           );
           //open or read json data and insert new array
           $data_results = file_get_contents('driver/driver_log.json');
           $tempArray = json_decode($dataResults);

           $tempArray[] = $additionalArray ;
           $jsonData = json_encode($tempArray);
           file_put_contents('driver/driver_log.json', $jsonData);
        }

    }


    public function preferedAreaList(){
        $prefered_area_range = DB::table('prefered_area_range')->first();

        if(count($prefered_area_range) == 0){
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Data Found',

            ];
        }else{
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data Found',
                'data' => $prefered_area_range,
            ];
        }

    }

    public function deleteFcmToken(){

        $personal_check = DB::table('logistics_addional_infos')->where('user_id',auth()->user()['id'])->first();
        if($personal_check == null){
            DB::table('users')->where('id', auth()->user()['id'])->update(['fcm_token_personal' => null]);
        }else{
            DB::table('users')->where('id', auth()->user()['id'])->update(['fcm_token_driver' => null]);
        }

        return [
            'status' => 200,
            'success' => true,
            'msg' => 'Sucessfully logout',
        ];

    }

    public function configUpdate(Request $request){

        $personal_check = DB::table('logistics_addional_infos')->where('user_id',auth()->user()['id'])->first();
        if($personal_check == null){
            DB::table('users')->where('id', auth()->user()['id'])->update(['fcm_token_personal' => $request->fcm_token]);
        }else{
            DB::table('users')->where('id', auth()->user()['id'])->update(['fcm_token_driver' => $request->fcm_token]);
        }
        
        
      $longitude =  $request->longitude;
      $latitude =  $request->latitude;

      $point1 = [$longitude,$latitude];
      $prefered_area_range = DB::table('prefered_area_range')->get();
      $company = LogisticsAdditional::where('user_id', auth()->user()['id'])->first();
      $company_id = $company->company_id;
      $active = $company->verified;

        $check = [];
        foreach($prefered_area_range as $prefered_area_ranges){

            $to_polygon_array = $prefered_area_ranges->range;

            $to_polygon = [];
            for($i=0;$i < count(explode(' ',$to_polygon_array))-1;$i++){
                $to_polygon[] = explode(',',explode(' ',$to_polygon_array)[$i]);
            }
            $receiver_data = $this->mapRangeCheck($to_polygon,$point1);
            $check[] =  $receiver_data;
            if($receiver_data == true){
                $id = $prefered_area_ranges->id;
                $area_name = $prefered_area_ranges->area_name;
            }
        }

        //dd($check);
        if(isset($area_name) && $area_name != null){
            $this->driverLog(auth()->user()['id'], $latitude, $longitude);
            $driver_area =  DB::table('driver_update_area')->where('user_id', auth()->user()['id'])->first();
            if($driver_area){
            $driver_area_update = DB::table('driver_update_area')->where('user_id', auth()->user()['id'])->update([
                'area_id'    => $id,
                'latitude' => $latitude,
                'longitude' => $longitude,

            ]);
              }else{
                    $driver_area_insert = DB::table('driver_update_area')->insert([
                      'user_id' => auth()->user()['id'],
                      'area_id'    => $id,
                      'latitude' => $latitude,
                      'longitude' => $longitude,

                  ]);
              }
            $areas = DB::table('prefered_area_range')->orderBy('area_name')->get();
            $area_order_count = DB::table('orders')
                                    ->join('pre_orders','pre_orders.invoice_no','=','orders.invoice_no')
                                    ->whereIn('orders.current_status',[1,2])
                                    ->where('pre_orders.pick_area_id',$id)
                                    ->count();
            
            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Data Found',
                    'data' => ['id'=>$id,'area_name'=>$area_name,'active' => $active == 1 ? true : false ,'company' => $company_id != null ? true : false,'area_order_count'=>$area_order_count,'area'=>$areas ]
            ];
        }else{

            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'You are not in our coverage area'
            ];
        }

    }
    
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

    public function preferedAreaListAdd(Request $request){

        $prefered_area_range = DB::table('prefered_areas')->where('logistics_id',auth()->user()['id'])->where('area_id',$request->area_id)->first();

        if ($prefered_area_range != null)
        {
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'added already'
            ];
        }

        try {

            $prefered_area_range = DB::table('prefered_areas')->insert(
                                ['area_id' => $request->area_id, 'logistics_id' => auth()->user()['id']]
                            );
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Area added successfully'
            ];

        } catch (Exception $e) {
            return [
                'status' => 200,
                'success' => true,
                'msg' => $e->getMessage()
            ];
        }
    }


    public function preferedAreaListView(Request $request){

        $prefered_area_range = DB::table('prefered_area_range')
                                            ->join('prefered_areas','prefered_area_range.id','=','prefered_areas.area_id')
                                            ->where('prefered_areas.logistics_id',auth()->user()['id'])
    	                                    ->get();

        $prefered_area_rangeList = [];
        $data = [];
        $matched_order = [];
        // return $prefered_area_range;
    	foreach($prefered_area_range as $prefered_area_ranges){


                    $orders = DB::table('pre_orders')
                            //->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                            ->leftjoin('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                            ->where('recp_area',$prefered_area_ranges->area_id)
                            ->get();
                    // return $orders;
                    $counts = 0;
                    foreach($orders as $order){

                        $ordersdata = DB::table('orders')
                                        ->whereIn('current_status',[1,2])
                                        ->whereNull('company_id')
                                        ->where('invoice_no',$order->invoice_no)
                                        ->first();
                        //dd($data);
                        if($ordersdata != null){
                            $counts += 1;
                        }
                    }

	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['id'] = $prefered_area_ranges->id;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['area_name'] = $prefered_area_ranges->area_name;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['range'] = $prefered_area_ranges->range;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['area_id'] = $prefered_area_ranges->area_id;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['logistics_id'] = $prefered_area_ranges->logistics_id;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['count'] = $counts;
	       $data[] = $prefered_area_rangeList[$prefered_area_ranges->area_name];
        }



    	//dd($prefered_area_rangeList);
        if(count((array) $prefered_area_range) > 0){
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Data found',
                    'data'=> $data

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


    public function preferedAreaListDelete(Request $request){

        try {

            DB::table('prefered_areas')
                        ->where('area_id', $request->prefered_area_id)
                        ->where('logistics_id', auth()->user()['id'])
                        ->delete();

            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Area deleted successfully'
            ];

        } catch (Exception $e) {
            return [
                'status' => 200,
                'success' => true,
                'msg' => $e->getMessage()
            ];
        }
    }




    public function wallet(Request $request){
        //dd(auth()->user()['id']);

        try {

            $order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                    ->join('logistics_tracking', 'logistics_tracking.invoice_no', '=', 'orders.invoice_no')
    	            ->where('logistics_tracking.logistics_id',auth()->user()['id'])
    	            ->where('orders.current_status',5)
    	            ->orderBy('orders.created_at', 'desc')
    	            ->get();


            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Order found',
                'wallet_info' => ['total_amount'=>50000,'monthly_income'=>2000,'due_amount'=>3000],
                'data' => $order
            ];

        } catch (Exception $e) {
            return [
                'status' => 200,
                'success' => true,
                'msg' => $e->getMessage()
            ];
        }
    }

    public function authLogin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
            'phone' => 'required|string|min:10|max:13'
        ]);


        if ($validator->fails())
        {
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Error',
                'data' => $validator->errors()->all()
            ];
        }

        $database_check = DB::table('users')->where('password',$request->password)->where('phone',$request->phone)->where('role',1)->first();


        if ($database_check == null)
        {
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Error',
                'data' => 'Please register as merchant'
            ];
        }

        $credentials = [
            'phone' => $request->phone,
            'password' => $request->password
        ];


        try {
           if (auth()->attempt($credentials)) {
                $token = auth()->user()->createToken('TrutsForWeb')->accessToken;
                $this->getLogs($token);
                $users = DB::table("users")->select('phone','image')->where('phone',$request->phone)->first();
                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'User login successfully',
                        'user'=> $users,
                        'data' => ['token_type' => 'Bearer',
                        'token' => $token]

                ];
            } else {

                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Login not successfull',
                        'data'=> $this->getLogs('Unauthorized'),
                        'token' => []
                ];
            }

        } catch (Exception $e) {

                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Login not successfull',
                        'data'=> $e,
                        'token' => []
                ];
        }
    }


    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
            'phone' => 'required|string|min:10|max:13'
        ]);


        if ($validator->fails())
        {
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Error',
                'data' => $validator->errors()->all()
            ];
        }
        //dd("ok");
        $credentials = [
            'phone' => $request->phone,
            'password' => $request->password
            //'role'=>1
        ];
        // return response()->json(['error' => $credentials, 'success' => 0], 401);


        try {
           if (auth()->attempt($credentials)) {
                $token = auth()->user()->createToken('TrutsForWeb')->accessToken;
                $this->getLogs($token);
                $users = DB::table("users")->select('id','phone','active','image')->where('phone',$request->phone)->first();
                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'User login successfully',
                        'user'=> $users,
                        'data' => ['token_type' => 'Bearer',
                        'token' => $token]

                ];
            } else {

                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Login not successfull',
                        'data'=> $this->getLogs('Unauthorized'),
                        'token' => []
                ];
            }

        } catch (Exception $e) {

                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Login not successfull',
                        'data'=> $e,
                        'token' => []
                ];
        }
    }


    public function deliverylogin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
            'phone' => 'required|string|min:10|max:13'
        ]);


        if ($validator->fails())
        {
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Error',
                'data' => $validator->errors()->all()
            ];
        }
        //dd("ok");
        $credentials = [
            'phone' => $request->phone,
            'password' => $request->password
            //'role'=>1
        ];
        // return response()->json(['error' => $credentials, 'success' => 0], 401);


        $personal_check = DB::table('logistics_addional_infos')->where('users.id',auth()->user()['id'])->first();

        if($personal_check != null){

            if($personal_check->verified == 0 ||$personal_check->verified == 2 ||$personal_check->verified == 3){

                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Please complete your verification process'
                ];
            }
            try {
               if (auth()->attempt($credentials)) {
                    $token = auth()->user()->createToken('TrutsForWeb')->accessToken;
                    $this->getLogs($token);

                    //dd(auth()->user()['id']);
                    $info = DB::table('users')->select('users.id','users.username','users.phone','users.image')
                                                ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                                                ->where('users.id',auth()->user()['id'])
        	                                    ->first();

                    return [
                            'status' => 200,
                            'success' => true,
                            'msg' => 'User login successfully',
                            'user'=> $info,
                            'data' => ['token_type' => 'Bearer',
                            'token' => $token]

                    ];

                } else {

                    return [
                            'status' => 200,
                            'success' => true,
                            'msg' => 'Login not successfull',
                            'data'=> $this->getLogs('Unauthorized'),
                            'token' => []
                    ];
                }

            } catch (Exception $e) {

                    return [
                            'status' => 200,
                            'success' => true,
                            'msg' => 'Login not successfull',
                            'data'=> $e,
                            'token' => []
                    ];
            }

        }else{

            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Please register from rider app first'
            ];
        }




    }

    public function userupdate(Request $request)
    {

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
            $users = DB::table('users')->where('id',auth()->user()['id'])->update(array('username'=>$username, 'image'=>$photo_link));

        }elseif(isset($request->username)){
            $username = strip_tags($request->username);
            $users = DB::table('users')->where('id',auth()->user()['id'])->update(array('username'=>$username));
        }elseif(isset($request->image)){
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $photo_link = "https://api.parcelmagic.com/img/users/".$photo;
            $users = DB::table('users')->where('id',auth()->user()['id'])->update(array('image'=>$photo_link));
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $public_url = str_replace('/api/', '/', public_path());

            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }

        }
        $users = DB::table('users')->select('id','username','image','phone')->where('id',auth()->user()['id'])->first();
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




    public function deliveryUserUpdate(Request $request)
    {
        // dd(env('APP_URL')."/img/users/");
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

        //dd($request->username);
        if(isset($request->username) && isset($request->image) && $request->image != null && $request->username != ""){

            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $photo_link = "https://api.parcelmagic.com/img/users/".$photo;

            $public_url = str_replace('/api/', '/', public_path());
            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = 'https://api.parcelmagic.com/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }
            $username = strip_tags($request->username);
            $users = DB::table('users')->where('id',auth()->user()['id'])->update(array('username'=>$username ,'image'=>$photo_link));

        }elseif(isset($request->username)){

            $username = strip_tags($request->username);
            $users = DB::table('users')->where('id',auth()->user()['id'])->update(array('username'=>$username));
        }elseif(isset($request->image)){
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();

            $photo_link = "https://api.parcelmagic.com/img/users/".$photo;

            $users = DB::table('users')->where('id',auth()->user()['id'])->update(array('image'=>$photo_link));
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $public_url = str_replace('/api/', '/', public_path());

            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }

        }
        $users = DB::table('users')->select('id','username','image','phone')->where('id',auth()->user()['id'])->first();
    	if(count((array) $users) > 0){
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Update successfully',
                    'data'=>$users

            ];
    	}else{
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Update error',
                    'data' => []
            ];
    	}

    }

    public function userInfo(){
        $users = DB::table('users')->where('id',auth()->user()['id'])->get();
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

    public function rating(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'points' => 'integer|max:6|required',
            'user_id' => 'integer|required',
            'logistics_id' => 'integer|required',
            'order_id' => 'integer|required'
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

        $rating = DB::table('ratings')->insert(
            ['points' => $request->points, 'user_id' => $request->user_id,'logistics_id' => $request->logistics_id,'order_id' => $request->order_id]
        );

    	if(count((array) $rating) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data Found',
                'data' => $rating,
            ];
    	}else{
    	    return [
    	        'status' => 200,
                'success' => true,
                'msg' => 'No Data Found',
                'data' => []
            ];
    	}

    }

public function personalphoneNumberCheck(Request $request)
    {

         $validator = Validator::make($request->all(), [
            'phone' => 'required|min:9'
         ]);

        if ($validator->fails())
        {
            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => $validator->errors()->first()
            ];
        }

        $users_app_check = Personal::
                            where('phone',$request->phone)
                            ->first();


        if($users_app_check != null){

        	$credentials = [
                'phone' => $request->phone,
                'password' => '123456789'
            ];
    
    	    try {
               if (Auth::guard('api-personal')->attempt($credentials)) {
                   return auth()->user()->id;
                    // $token=Str::random(60);
                    // $token = auth()->user()->createToken('TrutsForWeb')->accessToken;
                    // $this->getLogs($token);
    	            //     $users = Personal::
    	            //                     select('phone','id','username','client_id','email','image')
                    //     	            ->where('phone',$request->phone)
                    //     	            ->first();
                    //     return [
                    //         'status' => 200,
                    //         'success' => true,
                    //         'msg' => 'User registered',
                    //         'user' => $users,
                    //         'data' => ['token_type' => 'Bearer','token' => $token]
                    //     ];



                }else{
                    return [
                            'status' => 200,
                            'success' => false,
                            'msg' => 'Credential does not matched',
                    ];
                }

            } catch (Exception $e) {
                return [
                            'status' => 200,
                            'success' => false,
                            'msg' => 'error',
                            'data' => $e->getMessage()
                    ];
            }
    	}else{
    	    return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'User not registered'
            ];
    	}

    }


 public function driverphoneNumberCheck(Request $request)
    {

         $validator = Validator::make($request->all(), [
            'phone' => 'required|min:9'
         ]);


        if ($validator->fails())
        {
            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => $validator->errors()->first()
            ];
        }
        $users_merchant_check = DB::table("users")
                                    ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                                    ->where('users.phone',$request->phone)
                                    ->first();
      
        if($users_merchant_check != null){

        	$credentials = [
                'phone' => $request->phone,
                'password' => '123456789'
            ];
            
    	    try {
               if (auth()->attempt($credentials)) {
                    $token = auth()->user()->createToken('TrutsForWeb')->accessToken;
                    $this->getLogs($token);


    	            $users = DB::table("users")
                	            ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                                ->select('users.phone','users.id','users.phone','users.username','logistics_addional_infos.client_id','logistics_addional_infos.email','logistics_addional_infos.image')
                	            ->where('users.phone',$request->phone)
                	            ->first();

                                return [
                                    'status' => 200,
                                    'success' => true,
                                    'msg' => 'User registered',
                                    'user' => $users,
                                    'data' => ['token_type' => 'Bearer','token' => $token]
                                ];

                    }else{
                        return [
                                'status' => 200,
                                'success' => true,
                                'msg' => 'Credential does not matched',
                        ];
                    }

            } catch (Exception $e) {

                return [
                        'status' => 200,
                        'success' => false,
                        'msg' => 'error',
                        'data' => $e->getMessage()
                    ];
            }
    	}else{

    	    return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'User not registered'
            ];
    	}

    }

   








    public function orderDetails($id)
    {

    	$order = DB::table("orders")
    	            ->join('pre_orders','orders.invoice_no','=','pre_orders.invoice_no')
    	            ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.invoice_no',$id)
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




    public function UserIdDetails($user_id){
        $users = DB::table("users")
                    ->select('id','username','phone','image','role')
    	            ->where('id',$user_id)
    	            ->first();
        return $users;
    }

    public function OrderAppStatus($status){
        if($status == 1 || $status == 2){
            $AppStatus = "Parcel Request Placed Successfully";
        }elseif($status == 2){
            $AppStatus = "Parcel Request Accepted";
        }elseif($status == 3){
            $AppStatus = "Parcel Picked-up";
        }elseif($status == 5 || $status == 9){
            $AppStatus = "Parcel Delivered Successfully";
        }else{
            $AppStatus = "Status Error";
        }
        return $AppStatus;
    }

    public function currentorderlistQuick(Request $request)
    {

        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->where('personal_order_type',1)
    	            ->whereIn('orders.current_status',[1,2,3,4])
    	            ->orderBy('orders.created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->select('id','username','phone','image')
            	                             ->where('id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['logistics_tracking'] = $orders->current_status != 2 ? $tracking_info : null;
    	    $orderList['logistics_user_info'] = $orders->current_status != 2 ? $logistics_user_info : null;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
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

    public function allOngoingOrder(Request $request)
    {

        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->whereIn('orders.current_status',[1,2,3,4])
    	            ->orderBy('orders.created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();

    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = null;
            }
            
            if($tracking_info != null && count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
        	                                 ->select('users.id','users.username','users.phone','logistics_addional_infos.image')
            	                             ->where('id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = null;
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['logistics_tracking'] = $orders->current_status != 2 ? $tracking_info : null;
    	    $orderList['logistics_user_info'] = $orders->current_status != 2 ? $logistics_user_info : null;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
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




    public function currentorderlistExpress(Request $request)
    {
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->where('personal_order_type',2)
    	            ->whereIn('orders.current_status',[1,2,3,4])
    	            ->orderBy('orders.created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->select('id','username','phone','image')
            	                             ->where('id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = [];
            }
    	   // $orderList['id'] = $orders->id;
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['logistics_tracking'] = $orders->current_status != 2 ? $tracking_info : null;
    	    $orderList['logistics_user_info'] = $orders->current_status != 2 ? $logistics_user_info : null;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
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


    public function orderListExpress(Request $request)
    {

        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->where('personal_order_type',2)
    	            ->whereIn('orders.current_status',[5,13])
    	            ->orderBy('orders.created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->select('id','username','phone','image')
            	                             ->where('id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['delivery_date'] = $orders->delivery_date;
    	    $orderList['logistics_tracking'] = $orders->current_status == 13?null:$tracking_info;
    	    $orderList['logistics_user_info'] = $orders->current_status == 13?null:$logistics_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
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
    public function allOrderList(Request $request)
    {

        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->whereIn('orders.current_status',[5,13])
    	            ->orderBy('orders.created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
        	                                 ->select('users.id','users.username','users.phone','logistics_addional_infos.image')
            	                             ->where('users.id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['delivery_date'] = $orders->delivery_date;
    	    $orderList['logistics_tracking'] = $orders->current_status == 13?null:$tracking_info;
    	    $orderList['logistics_user_info'] = $orders->current_status == 13?null:$logistics_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
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





    public function orderlistRegular(Request $request)
    {

        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;
        $order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->where('personal_order_type',1)
    	            ->whereIn('orders.current_status',[5,13])
    	            ->orderBy('orders.created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->select('id','username','phone','image')
            	                             ->where('id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = [];
            }
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['delivery_date'] = $orders->delivery_date;
    	    $orderList['logistics_tracking'] = $orders->current_status == 13?null:$tracking_info;
    	    $orderList['logistics_user_info'] = $orders->current_status == 13?null:$logistics_user_info;
    	    $orderList['cod'] = $orders->cod == 0? "No":"Yes";
    	    $orderList['coc'] = $orders->coc == 0? "No":"Yes";
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



    public function collectedorderlistQuick(Request $request)
    {

    	$order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->where('orders.current_status',4)
    	            ->where('personal_order_type',1)
    	            ->orderBy('orders.created_at', 'desc')
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->select('id','username','phone','image')
            	                             ->where('id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = [];
            }
    	    $orderList['id'] = $orders->id;
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status_msg'] = $this->OrderAppStatus($orders->current_status);
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['logistics_charge'] = number_format((float)$orders->logistics_charge, 2, '.', '');
    	    $orderList['logistics_tracking'] = $tracking_info;
    	    $orderList['logistics_user_info'] = $logistics_user_info;
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


    public function collectedorderlistExpress(Request $request)
    {
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;


    	$order  = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->where('orders.current_status',4)
    	            ->where('personal_order_type',2)
    	            ->orderBy('orders.created_at', 'desc')
    	            ->skip($skip)
    	            ->limit($limit)
    	            ->get();



    	$orderList = [];
    	$msg = [];
    	foreach($order as $orders){

    	    $tracking_info = [];
    	    $tracking = DB::table("logistics_tracking")
            	            ->select('invoice_no','created_at','reschedule_date','logistics_id')
            	            ->where('logistics_tracking.invoice_no','=',$orders->invoice_no)
            	            ->first();

            if($tracking){
    	        $tracking_info['logistices_assign_date'] = $tracking->created_at;
    	        $tracking_info['logistices_reschedule_date'] = $tracking->reschedule_date;
            }else{
                $tracking_info = [];
            }

            if(count($tracking_info) > 0){
        	    $logistics_user_info = DB::table("users")
        	                                 ->select('id','username','phone','image')
            	                             ->where('id','=',$tracking->logistics_id)
                	                         ->first();
            }else{
                $logistics_user_info = [];
            }
    	    $orderList['id'] = $orders->id;
    	    $orderList['invoice_no'] = $orders->invoice_no;
    	    $orderList['delivery_charge'] = number_format((float)$orders->delivery_charge, 2, '.', '');
    	    $orderList['current_status_msg'] = $this->OrderAppStatus($orders->current_status);
    	    $orderList['current_status'] = $orders->current_status;
    	    $orderList['order_date'] = $orders->order_date;
    	    $orderList['logistics_charge'] = number_format((float)$orders->logistics_charge, 2, '.', '');
    	    $orderList['logistics_tracking'] = $tracking_info;
    	    $orderList['logistics_user_info'] = $logistics_user_info;
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





    public function livedelivery(Request $request)
    {
    	$order = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->where('current_status',19)
    	            ->get();


    	if(count($order) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Data Found',
                'data' => $order
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Data Found',
                'data' => []
            ];
    	}



    }

public function registerPersonal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'string|max:100',
            'phone' => 'string|min:10|max:14'
        ]);

        if ($validator->fails())
        {
            return [
                        'status' => 200,
                        'success' => true,
                        'msg' => $validator->errors()->first()

                ];
        }
        if($request->file('image') != null){
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();

            $public_url = str_replace('/api/', '/', public_path());

            // return response()->json([ 'data' =>$photo, 'success' => 1], 200);

            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }
        }else{

            $photo = $request->file('image');
        }


        if($photo != null){
            $photo_path = "https://api.parcelmagic.com/img/users/".$photo;
        }else{
            $photo_path = null;
        }

        $username = strip_tags($request->username);
        $phone = strip_tags($request->phone);
        $email = $request->email;


        $checkuserphone = DB::table('personal_infos')->select('*')->where('phone',$request->phone)->first();

        // if ($checkuserphone != null) {
        //     $client_id = $checkuserphone->id."".date('Y')."". "01" ."_". mt_rand(10000, 99999);
        //     $checkuser = DB::table('personal_infos')->where('id', $checkuserphone->id)->first();
        //     if ($checkuser != null) {
        //         return [
        //             'status' => 200,
        //             'success' => true,
        //             'msg' => 'Already Register',
        //             'data' => []
        //         ];
        //     } else {
        //         DB::table('personal_infos')->insert(
        //             ['email' => $email, 'image' => $photo_path, 'client_id'=>$client_id,]
        //         );
        //     }
        // }
        
        // else {
        //     $users = DB::table('personal_infos')->insertGetId(
        //         ['username' => $username, 'password' => bcrypt('123456789'), 'phone' => $phone, 'status' => 1, 'role'=>7]
        //     );
        //     $client_id = $users."".date('Y')."". "01" ."_". mt_rand(10000, 99999);
        //     // DB::table('personal_infos')->insert(
        //     //     ['email' => $email, 'image' => $photo_path, 'client_id'=>$client_id,]
        //     // );
        // }

        if($checkuserphone != null){
            return [
                            'status' => 200,
                            'success' => true,
                            'msg' => 'Already Register',
                            'data' => []
                        ];
        }else{
            $user=new Personal;
            $user->username= $username;
            $user->phone=   $phone;
            $user->email=  $email;
            $user->password= bcrypt('123456789');
            $user->image=$photo_path;
            $user->role= 5;
            $user->status=1;
            $user->save();

            $client_id = $user->id."".date('Y')."". "01" ."_". mt_rand(10000, 99999);
            DB::table('personal_infos')->where('id',$user->id)->update([
               'client_id'=> $client_id
            ]);
        }

        $credentials = [
            'phone' => $request->phone,
            'password' => '123456789'
        ];

        try {

            if (Auth::guard('api-personal')->attempt($credentials)) {
                //$token = auth()->user()->createToken('TrutsForWeb')->accessToken;
                $token=Str::random(60);
                $this->getLogs($token);
                $users = DB::table("personal_infos")
                                    ->select('id','username','phone','client_id','email','image')
                                    ->where('phone',$request->phone)
                                    ->first();
                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'User registered successfully',
                        'user'=> $users,
                        'data' => ['token_type' => 'Bearer',
                        'token' => $token]

                ];
            } else {
                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'User registration failed',
                        'data' => []
                ];
            }

        } catch (Exception $e) {
            return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'error',
                        'data' => $e->getMessage()

                ];
        }



    }



public function registerdelivery(Request $request)
    {

        //return $request->all();
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100',
            'phone' => 'required|string|min:10|max:15',
            'gender' => 'required',
            'dob' => 'required',
        ]);

        if ($validator->fails())
        {
            return [
                'status' => 200,
                'success' => true,
                'msg' => $validator->errors()->first()
            ];
        }

        /*

        if($request->file('image') != null){

            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();

            $public_url = str_replace('/api/', '/', public_path());

            // return response()->json([ 'data' =>$photo, 'success' => 1], 200);

            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }

            $uphoto = env('APP_URL')."/img/users/".$photo;
        }else{

            $uphoto = null;
        }

        if($request->file('nid') != null){
            //$nid  = $request->nid;

            $nidPic  = date('Ymdhis').'.'.$request->file('nid')->getClientOriginalExtension();

            $public_url = str_replace('/api/', '/', public_path());

            // return response()->json([ 'data' =>$photo, 'success' => 1], 200);

            if($request->file('nid')->move($public_url.'/img/users/', $nidPic))
            {
                $request['nid'] = '/img/users/'.$nidPic;
                $user_nid = '/img/users/'.$nidPic;
            }

            $nid = env('APP_URL')."/img/users/".$nidPic;


        }else{
            $nid = null;
        }

        if($request->file('drive_lisence') != null){
            //$drive_lisence  = $request->drive_lisence;

            $driverPic  = date('Ymdhis').'.'.$request->file('drive_lisence')->getClientOriginalExtension();

            $public_url = str_replace('/api/', '/', public_path());

            // return response()->json([ 'data' =>$photo, 'success' => 1], 200);

            if($request->file('drive_lisence')->move($public_url.'/img/users/', $driverPic))
            {
                $request['drive_lisence'] = '/img/users/'.$driverPic;
                $driver_li = '/img/users/'.$driverPic;
            }

            $drive_lisence  = env('APP_URL')."/img/users/".$driverPic;




        }else{
            $drive_lisence = null;
        }

        */

        if($request->file('image') != null){

            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();

            $public_url = str_replace('/api/', '/', public_path());

            // return response()->json([ 'data' =>$photo, 'success' => 1], 200);

            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }
            $uphoto = "https://api.parcelmagic.com/img/users/".$photo;
        }else{

            $uphoto = null;
        }
        $password = "123456789";

        $username = strip_tags($request->username);
        $phone = strip_tags($request->phone);
        $nid = $request->nid;
        $passport = $request->passport;
        $gender = $request->gender;
        $email = $request->email;
        $drive_lisence = $request->drive_lisence;
        $vehicle_registration_no = $request->vehicle_registration_no;

        $checkuserphone = DB::table('users')->select('*')->where('phone',$request->phone)->first();

        if ($checkuserphone != null) {
            $client_id = $checkuserphone->id."".date('Y')."". "01" ."_". mt_rand(10000, 99999);
            $checkuser = DB::table('logistics_addional_infos')->where('user_id', $checkuserphone->id)->first();
            if ($checkuser != null) {
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Already Register',
                    'data' => []
                ];
            } else {

                DB::table('logistics_addional_infos')->insert(
                ['NID' => $nid, 'passport' => $passport, 'gender' => $gender, 'email' => $email,'image' => $uphoto, 'drive_lisence' => $drive_lisence,'user_id' => $checkuserphone->id, 'client_id'=>$client_id,'verified'=>0,'date_of_birth'=>$request->dob,'address'=>$request->address,'driver_type'=>$request->driver_type,'vehicle_registration_no'=>$request->vehicle_registration_no]
            );
            }
        } else {

            $users = DB::table('users')->insertGetId(
                ['username' => $username, 'password' =>  bcrypt($password), 'phone' => $phone,'active' => 1, 'role'=>6]
            );
            $client_id = $users."".date('Y')."". "01" ."_". mt_rand(10000, 99999);
            DB::table('logistics_addional_infos')->insert(
                ['NID' => $nid, 'passport' => $passport, 'gender' => $gender, 'email' => $email,'image' => $uphoto, 'drive_lisence' => $drive_lisence,'user_id' => $users, 'client_id'=>$client_id,'verified'=>0,'date_of_birth'=>$request->dob,'address'=>$request->address,'driver_type'=>$request->driver_type,'vehicle_registration_no'=>$request->vehicle_registration_no]
            );
        }
        $credentials = [
            'phone' => $request->phone,
            'password' => $password
        ];
        // return response()->json(['error' => $credentials, 'success' => 0], 401);

        try {
           if (auth()->attempt($credentials)) {
                $token = auth()->user()->createToken('TrutsForWeb')->accessToken;
                $this->getLogs($token);
                $info = DB::table('users')->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                                            ->select('users.id','users.username','users.phone','logistics_addional_infos.client_id','logistics_addional_infos.email','logistics_addional_infos.image')
                                            ->where('users.id',auth()->user()['id'])
    	                                    ->first();

                    return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'User registered successfully',
                        'user'=> $info,
                        'data' => ['token_type' => 'Bearer',
                        'token' => $token,]

                    ];


            } else {
                $this->getLogs('Unauthorized');
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'User not registered',
                    'data' => []
                ];
            }

        } catch (Exception $e) {
            $this->getLogs('Unauthorized');

            return [
                'status' => 200,
                'success' => true,
                'msg' => 'User not registered',
                'data' => []
            ];

        }
    }




    public function area(Request $request)
    {
        $areas = DB::table('prefered_area_range')->orderBy('area_name')->get();
    	if(count((array) $areas) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data Found',
                'data' => $areas
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Data Found',
                'data' => []
            ];
    	}

    }


    public function rangeorderlist(Request $request)
    {
    	$order = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
    	            ->where('pre_orders.pick_area_id',$request->from)
    	            ->where('pre_orders.recp_area',$request->to)
    	            ->orderby('id','desc')
    	            ->get();


    	if(count($order) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data Found',
                'data' => $order
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Data Found',
                'data' => []
            ];
    	}

    }

    public function preferedorderlist(Request $request)
    {

        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

        $area = DB::table("prefered_areas")->where('logistics_id',auth()->user()['id'])->get();
    	//dd($area);
    	if(count($area) > 0){
    	    $area_list = [];
    	    foreach($area as $areas){
    	        $area_list[] = $areas->area_id;
    	    }
        	$order = DB::table("pre_orders")
        	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
        	            ->whereIn('pre_orders.pick_area_id',$area_list)
        	            ->OrwhereIn('pre_orders.recp_area',$area_list)
        	            ->skip($skip)->limit($limit)->get();

    	    //dd($order);
        	if(count($order) > 0){

        	        return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Data Found',
                        'data' => $order
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
                    'msg' => 'No Prefered Data Found',
                    'data' => []
            ];
    	}

    }



    public function deliveryorderDetails($id)
    {
    	$order =  DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
    	            ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.id',$id)
    	            ->get();


    	if(count($order) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data Found',
                'data' => $order[0]
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Data Found'
            ];
    	}



    }

    public function SendSms($status,$phone,$invoice_no,$body){
        
        /*
        $postdata = http_build_query(
            array(
                "api_key" => 'KEY-xe7m5lxxd83bp4jsc6ghetgf0g70t9h5',
                "api_secret" => 'eObDf8Qynu6h!tSI',
                "request_type" => 'OTP',
                "message_type"  => 'TEXT',
                "mobile" => $phone,
                "message_body" => "ok"
            )
        );
        
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        
        $context  = stream_context_create($opts);

        $result = file_get_contents('https://portal.adnsms.com/api/v1/secure/send-sms', false, $context);
        */
        $url = 'https://portal.adnsms.com/api/v1/secure/send-sms';

        $params = array(
            "api_key" => 'KEY-twr5ut0kc07pb4lqy1k9kjm81o67xby8',
            "api_secret" => 'YYM@mqJbaWdpi50h',
            "request_type" => 'OTP',
            "message_type"  => 'TEXT',
            "mobile" => $phone,
            "message_body" => $body
        );

        $query_string = http_build_query($params);

        $ch = curl_init($url);
       
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      
        $result = curl_exec($ch);
        
        curl_close($ch);
        
    }

    public function SendOTPMessage(Request $request){

        $number = $request->phone_number;
        $otp = substr(rand(0,9999999999999),0,6);
        $body = "Your parcel magic verification code is ".$otp;
        $this->SendSms("",$number,"",$body);
        
	    return [
            'status' => 200,
            'success' => true,
            'msg' => 'Status Updated',
            'data' => ['token'=>$otp,'sender_number'=>$number]
        ];

    }

    public function orderstatuschange(Request $request)
    {

        $invoice_no = $request->invoice_no;
        $status = $request->current_status;
        $Date = date("Y-m-d h:i:s");
        $Datebd = date("Y-m-d H:i:s", strtotime('+6 hours', strtotime($Date)));

    	$url = "https://fcm.googleapis.com/fcm/send";
        $category = 'status_change';
        $description = "click here for see details";
    	$data = DB::table('orders')->where('invoice_no',$invoice_no)->first();
    	if(!isset($data)){
            return [
                'status' => 200,
                'success' => false,
                'msg' => 'This order not found'
                ];
    	}
    	$pre_data = DB::table('pre_orders')->where('invoice_no',$invoice_no)->first();
        //dd($request->all());

    	$users = DB::table('users')->where('id',$data->user_id)->first();
    	$company = $data->company_id;
    	$delivery_users = DB::table('users')->where('id',auth()->user()['id'])->first();
        if($status == 3){
            $data = DB::table('orders')->where('invoice_no',$invoice_no)->first();
            
            if($data->current_status == 2){
                
                if($company == null){

                    $otp = substr(rand(0,9999999999999),0,6);
                    $body = "Your parcel request has been accepted successfully. Invoice no #$invoice_no. Delivery agent ($delivery_users->username) is arriving at your location for pickup. Please share this code $otp with the delivery agent while dispatching.";
    
                    $response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('active'=>1,'current_status'=>$status,'otp'=>$otp,'accepted_date'=>$Datebd));
    
                    $title = "Your order is accepted. Invoice no:".$invoice_no;
                    $description = "Your parcel request has been accepted. Delivery agent ($delivery_users->username) is arriving at your location for pickup. Please share this code $otp with the delivery agent while dispatching.";
                }else{
    
                    $body = "Your parcel request has been accepted successfully. Invoice no #$invoice_no. Delivery agent ($delivery_users->username) is arriving at your location for pickup.";
                    $response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('current_status'=>$status,'accepted_date'=>$Datebd));
    
                    $title = "Your order is accepted. Invoice no:".$invoice_no;
                    $description = "Your parcel request has been accepted. Delivery agent ($delivery_users->username) is arriving at your location for pickup.";
    
                }
                DB::table('logistics_tracking')->insert(
                     array(
                            'logistics_id' => auth()->user()['id'],
                            'invoice_no' => $invoice_no,
                            'priority' => 1,
                            'reschedule_date' => date('y:m:d h:m:s'),
                            'status' => 1
                     )
                );
                
                
               $api_request_url = 'http://103.112.53.91:3000/accept';
               $invoice_no_array = ['invoice_no'=> $invoice_no];
               $ch_curl=curl_init($api_request_url);
               curl_setopt($ch_curl, CURLOPT_POST, true);
               curl_setopt($ch_curl, CURLOPT_POSTFIELDS,$invoice_no_array);
               curl_setopt($ch_curl, CURLOPT_FRESH_CONNECT, true);
            
               
               curl_exec($ch_curl);
               curl_close($ch_curl);


            

                $this->SendSms($status,$pre_data->pic_phone,$invoice_no,$body);

            }else{
                return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'This order already accepted'
                ];
            }
           

        }

        if($status == 4){

            if($data->current_status == 4){
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'This order already collected'
                ];

            }

            if($company == null){

        			$otp = $request->otp;
            	    $otp_check = DB::table('orders')->where('otp',$otp)->where('invoice_no',$invoice_no)->first();

            	    if($otp_check == null){
                	    return [
                            'status' => 200,
                            'success' => false,
                            'msg' => 'OTP not matched'
                        ];
            	    }

                    $otp = substr(rand(0,9999999999999),0,6);
                    $response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('current_status'=>$status,'otp'=>$otp,'collection_date'=>$Datebd));

                    $body = "Your Parcel is on its way to the receiver. Invoice no #$invoice_no";
                    $this->SendSms($status,$pre_data->pic_phone,$invoice_no,$body);

                    $body = "Your Parcel from ($users->username) will be delivered shortly. Invoice no #$invoice_no. Please share this code $otp with the delivery agent while receiving.";
                    $this->SendSms($status,$pre_data->recp_phone,$invoice_no,$body);


                }else{
                    $response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('current_status'=>$status,'collection_date'=>$Datebd));

                    //Sender sms
                    $body = "Your Parcel is on its way to the receiver.";
                    $this->SendSms($status,$pre_data->pic_phone,$invoice_no,$body);

                    //Receiver sms
                    $body = "Your Parcel from ($users->username) will be delivered shortly.";
                    $this->SendSms($status,$pre_data->recp_phone,$invoice_no,$body);
                }


                    $title = "Your order is collected  Invoice no:".$invoice_no;
                    $description = "Delivery person has picked up your parcel and on the way to recipient.";
                    // $this->pushNotificationSend($users->fcm_token_personal,$category,$title,$description,$url);

                    DB::table('system_status_logs')->insert([
                    "invoice_id" => $invoice_no,
                    "status" => 4

                    ]);
        }


        if($status == 5){

            if($company == null){

    			$otp = $request->otp;
        	    $otp_check = DB::table('orders')->where('otp',$otp)->where('invoice_no',$invoice_no)->first();
        	    if($otp_check == null){
            	    return [
                        'status' => 200,
                        'success' => false,
                        'msg' => 'OTP not matched'
                    ];
        	    }

            }

                DB::table('orders')->where('invoice_no',$invoice_no)->update(['billing_status' => 1]);
                $response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('current_status'=>$status,'otp'=>null,'delivery_date'=>$Datebd,'delivered_date'=>$Datebd));

                // Sender sms
                $body = "Your parcel has been delivered successfully. Invoice no #$invoice_no.";
                $this->SendSms($status,$pre_data->pic_phone,$invoice_no,$body);

                $title = "Your order is delivered. Invoice no:".$invoice_no;
                $description = "Your parcel has been delivered successfully";
                // $this->pushNotificationSend($users->fcm_token_personal,$category,$title,$description,$url);

                DB::table('system_status_logs')->insert([
                    "invoice_id" => $invoice_no,
                    "status" => 5

                ]);
        }
        if($status == 13){
                
                if($data->current_status == 2){
                    
                    $response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('current_status'=>$status,'otp'=>null,'cancelled_date'=>$Datebd));

                    // Sender sms
                    $body = "Your parcel has been cancelled successfully. Invoice no #$invoice_no.";
                    $this->SendSms($status,$pre_data->pic_phone,$invoice_no,$body);
    
                    $title = "Your order is cancelled. Invoice no:".$invoice_no;
                    $description = "Your parcel has been cancelled successfully";
                    // $this->pushNotificationSend($users->fcm_token_personal,$category,$title,$description,$url);
    
                    DB::table('system_status_logs')->insert([
                        "invoice_id" => $invoice_no,
                        "status" => 13
    
                    ]);
                        
        
                }else{
                    return [
                            'status' => 200,
                            'success' => true,
                            'msg' => 'You can not cancel this order, because its already under process'
                        ];
                }
                
                
        }


    // 	$response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('current_status'=>$status));



	    return [
            'status' => 200,
            'success' => true,
            'msg' => 'Status Updated'
        ];


    }






    public function deliveryconfirmorderlist(Request $request)
    {

        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;

    	$order = DB::table("pre_orders")
    	            ->join('orders','orders.invoice_no','=','pre_orders.invoice_no')
    	            ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
    	            ->where('orders.user_id',auth()->user()['id'])
    	            ->whereIn('current_status',[16,17,23])
    	            ->orderby('orders.created_at','desc')
    	            ->skip($skip)->limit($limit)
    	            ->get();


    	if(count($order) > 0){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Data Found',
                'data' => $order
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Data Found',
                'data' => []
            ];
    	}

    }


    public function deliverycurrentorderlist(Request $request)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $results = DB::table('logistics_tracking')
                            ->where('logistics_tracking.logistics_id',auth()->user()['id'])
                            ->get();

        $invoice_no = [];
        foreach($results as $result){
            $invoice_no[] = $result->invoice_no;
        }
        $invoice_no = array_unique($invoice_no);

        //For current status 3
        $order = DB::table('orders')
                            ->join('pre_orders', 'orders.invoice_no', '=', 'pre_orders.invoice_no')
                            //->join('users','users.id','=','orders.user_id')
                            ->join('item_types', 'item_types.id', '=', 'pre_orders.item_type')
                            ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                            ->whereIn('orders.invoice_no', $invoice_no)
                            ->whereIn('orders.current_status',[3,4])
                            ->orderBy('orders.id','desc')
                            ->get();

        if(!$order->isEmpty()){

            $order_latitude = [];
            $order_longitude = [];
            foreach($order as $item)
            {
                if($item->current_status == 3){
                    $invoice[] = $item->invoice_no;
                    $order_latitude[] = $item->sender_latitude;
                    $order_longitude[] = $item->sender_longitude;
                }elseif($item->current_status == 4){
                    $invoice[] = $item->invoice_no;
                    $order_latitude[] = $item->receiver_latitude;
                    $order_longitude[] = $item->receiver_longitude;
                }

            }

            for($i = 0; $i < count($invoice); $i++)
            {

                $distance[$invoice[$i]][] = $this->calDistance($latitude, $longitude, $order_latitude[$i], $order_longitude[$i], "K");
            }

            asort($distance);
            $invoice1 = array_keys($distance);
        }else{
            $invoice1 = [];
        }

        if(isset($invoice1)){
        foreach($invoice1 as $single_invoice)
            {
                $final_order[] = DB::table('orders')
                                    ->join('pre_orders', 'orders.invoice_no', '=', 'pre_orders.invoice_no')
                                    //->join('users','users.id','=','orders.user_id')
                                    ->join('item_types', 'item_types.id', '=', 'pre_orders.item_type')
                                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                                    ->where('orders.invoice_no', $single_invoice)
                                    ->orderBy('orders.id','desc')
                                    ->first();
            }
        }
        // return @$final_order;
        $information1 = [];
    	if(isset($final_order)){

    	  foreach($final_order as $single_order){

    	      $information1['invoice_no'] = $single_order->invoice_no;

    	      $information1['delivery_charge'] = number_format((float)$single_order->delivery_charge, 2, '.', '');
    	      $information1['order_type'] = $single_order->order_type;
    	      $information1['current_status'] = $single_order->current_status;
    	      $information1['delivery_date'] = $single_order->delivery_date;
    	      $information1['order_date'] = $single_order->order_date;
    	      $information1['logistics_charge'] = number_format((float)$single_order->logistics_charge, 2, '.', '');
    	      $information1['cod'] = $single_order->cod;
    	      $information1['coc'] = $single_order->coc;
    	      $information1['who_will_pay'] = $single_order->who_will_pay;
    	      $information1['personal_order_type'] = $single_order->personal_order_type;
    	      $information1['order_additional_details'] = $single_order->order_additional_details;
    	      $information1['recp_name'] = $single_order->recp_name;
    	      $information1['recp_phone'] = $single_order->recp_phone;
    	      $information1['recp_address'] = $single_order->recp_address;
    	      $information1['pic_name'] = $single_order->pic_name;
    	      $information1['pic_phone'] = $single_order->pic_phone;
    	      $information1['pick_address'] = $single_order->pick_address;
    	      $information1['item_type'] = $single_order->item_type;
    	      $information1['item_qty'] = $single_order->item_qty;
    	      $information1['order_item_name'] = $single_order->order_item_name;
    	      $information1['sender_latitude'] = $single_order->sender_latitude;
    	      $information1['sender_longitude'] = $single_order->sender_longitude;
    	      $information1['receiver_latitude'] = $single_order->receiver_latitude;
    	      $information1['receiver_longitude'] = $single_order->receiver_longitude;
    	      $information1['distance'] = number_format((float)$single_order->distance, 2, '.', '');
    	      $information1['company'] = $single_order->company_id ? true : false;


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


    }





    public function orderShipmentUpdate(Request $request)
    {
        $order_id = $request->order_id;

    	$response = DB::table('orders')->where('id',$order_id)->update(array('logistics_id'=>$logistics_id));

    	if($response){
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Updated',
                'data' => []
            ];
    	}else{
    	    return [
                'status' => 200,
                'success' => true,
                'msg' => 'Not updated',
                'data' => []
            ];
    	}

    }





    public function personalprofile(Request $request)
    {

    	$response = DB::table('users')->where('id',auth()->user()['id'])->get();


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

    public function deliveryprofile(Request $request)
    {

    	$response = DB::table('users')->select('*')
                                            ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                                            ->where('users.id',auth()->user()['id'])
    	                                    ->first();


    	if(count((array) $response) == 0){
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

    public function preferedAreaOrderList(Request $request){

            $validator = Validator::make($request->all(), [
            'from' => 'required|numeric|digits_between: 1,5'
        ]);

        if ($validator->fails())
        {
            return [
                'status' => 200,
                'success' => false,
                'msg' => 'Error',
                'data' => $validator->errors()->all()
            ];
        }

        $order_check = Order::whereIn('current_status',[1,2])->get();
        // return $pre_order;
        if(!$order_check->isEmpty())
        {

        $pick_area_id = $request->from;
        $response = DB::table('users')->select('*')
                        ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                        ->where('users.id',auth()->user()['id'])
                        ->first();
        if($response){
            if($response->driver_type == 1){

                $prefered_areas = $prefered_areas = DB::table('prefered_areas')->where('logistics_id',auth()->user()['id'])->get();
                    // return $prefered_areas;
                $a = [];
                if(!$prefered_areas->isEmpty()){
                        for($i =0 ; $i < count($prefered_areas) ; $i++){
                            $prefered_area[] = $prefered_areas[$i]->area_id;
                        }

                    $prefered_pick_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                        ->whereIn('orders.current_status',[1,2])
                                        ->whereIn('recp_area', $prefered_area)
                                        ->where('pick_area_id', $pick_area_id)
                                        ->whereNull('orders.company_id')
                                        ->orderBy('orders.personal_order_type', 'desc')
                                        ->get();
                    if(!$prefered_pick_orders->isEmpty()){
                        foreach($prefered_pick_orders as $item){
                        $prefered_pick_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $prefered_pick_invoice = [];
                    }

                    $prefered_others_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                        ->whereIn('orders.current_status',[1,2])
                                        ->whereIn('recp_area', $prefered_area)
                                        ->whereNull('orders.company_id')
                                        ->orderBy('orders.personal_order_type', 'desc')
                                        ->get();
                    if(!$prefered_others_orders->isEmpty()){

                        foreach($prefered_others_orders as $item){
                        $prefered_others_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $prefered_others_invoice = [];
                    }



                    $invoice1 = array_unique(array_merge($prefered_pick_invoice, $prefered_others_invoice), SORT_REGULAR);
                        // return gettype($recp_area_prefered);

                }else{
                    $invoice1 = [];
                }
                    $pick_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                        ->whereIn('orders.current_status',[1,2])
                        ->where('pick_area_id', $pick_area_id)
                        ->whereNull('orders.company_id')
                        ->orderBy('orders.personal_order_type', 'desc')
                        ->get();

                    if(!$pick_orders->isEmpty()){
                        foreach($pick_orders as $item){
                            $pick_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $pick_invoice = [];
                    }


                    $invoice2 = $pick_invoice;

                $others_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                ->whereIn('orders.current_status',[1,2])
                                                ->whereNull('orders.company_id')
                                                // ->whereNotIn('orders.invoice_no', $invoice1)
                                                // ->whereNotIn('orders.invoice_no', $invoice2)
                                                ->orderBy('orders.personal_order_type', 'desc')
                                                ->get();

                foreach($others_orders as $item){
                        $others_invoice[] = $item->invoice_no;
                }

                $invoice3 = $others_invoice;
            }
            elseif($response->driver_type == 2){
                $prefered_areas = $prefered_areas = DB::table('prefered_areas')->where('logistics_id',auth()->user()['id'])->get();
                    // return $prefered_areas;
                $a = [];
                if(!$prefered_areas->isEmpty()){
                        for($i =0 ; $i < count($prefered_areas) ; $i++){
                            $prefered_area[] = $prefered_areas[$i]->area_id;
                        }

                    $prefered_pick_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                        ->whereIn('orders.current_status',[1,2])
                                        ->whereIn('recp_area', $prefered_area)
                                        ->where('pick_area_id', $pick_area_id)
                                        ->whereNull('orders.company_id')
                                        ->whereRaw('orders.created_at < now() - interval 5 minute')
                                        ->orderBy('orders.personal_order_type', 'desc')
                                        ->get();
                    if(!$prefered_pick_orders->isEmpty()){
                        foreach($prefered_pick_orders as $item){
                        $prefered_pick_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $prefered_pick_invoice = [];
                    }

                    $prefered_others_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                        ->whereIn('orders.current_status',[1,2])
                                        ->whereIn('recp_area', $prefered_area)
                                        ->whereNull('orders.company_id')
                                        ->whereRaw('orders.created_at < now() - interval 5 minute')
                                        ->orderBy('orders.personal_order_type', 'desc')
                                        ->get();
                    if(!$prefered_others_orders->isEmpty()){

                        foreach($prefered_others_orders as $item){
                        $prefered_others_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $prefered_others_invoice = [];
                    }



                    $invoice1 = array_unique(array_merge($prefered_pick_invoice, $prefered_others_invoice), SORT_REGULAR);
                        // return gettype($recp_area_prefered);

                }else{
                    $invoice1 = [];
                }
                    $pick_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                        ->whereIn('orders.current_status',[1,2])
                        ->where('pick_area_id', $pick_area_id)
                        ->whereNull('orders.company_id')
                        ->whereRaw('orders.created_at < now() - interval 5 minute')
                        ->orderBy('orders.personal_order_type', 'desc')
                        ->get();

                    if(!$pick_orders->isEmpty()){
                        foreach($pick_orders as $item){
                            $pick_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $pick_invoice = [];
                    }


                    $invoice2 = $pick_invoice;

                $others_orders = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                ->whereIn('orders.current_status',[1,2])
                                                ->whereNull('orders.company_id')
                                                // ->whereNotIn('orders.invoice_no', $invoice1)
                                                // ->whereNotIn('orders.invoice_no', $invoice2)
                                                ->whereRaw('orders.created_at < now() - interval 5 minute')
                                                ->orderBy('orders.personal_order_type', 'desc')
                                                ->get();

                foreach($others_orders as $item){
                        $others_invoice[] = $item->invoice_no;
                }

                $invoice3 = $others_invoice;
            }else{
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Driver type not assign',
                    'data'=> []
                ];
            }
        }else{
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Driver type not assign',
                'data'=> []
            ];
        }
        // return $invoice3;
        $search_order = array_unique(array_merge($invoice1, $invoice2, $invoice3), SORT_REGULAR);

        foreach($search_order as $single_order)
        {
            $final_order1 = DB::table('pre_orders')
                                ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                                ->join('item_types', 'item_types.id', '=', 'pre_orders.item_type')
                                ->join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                ->whereIn('orders.current_status',[1,2])
                                ->where('orders.invoice_no',$single_order)
                                ->where('orders.active','!=',0)
                                ->first();
            if($final_order1 != null){
                $final_order[] = $final_order1;
            }
        }
        
        foreach($final_order as $all_order){

            $order_details = json_decode($all_order->order_additional_details, true);
            if($order_details){
                foreach($order_details as $key =>$item)
                {
                    $order_detail[] = $item;
                    $param_name[] = $key;
                }
            }

            $information1['recp_address'] = $all_order->recp_address;
            $information1['pick_address'] = $all_order->pick_address;
            $information1['item_type'] = $all_order->item_type;
            $information1['item_qty'] = $all_order->item_qty;
            $information1['delivery_date'] = $all_order->delivery_date;
            $information1['invoice_no'] = $all_order->invoice_no;
            $information1['distance'] = number_format((float)$all_order->distance, 2, '.', '');
            $information1['order_item_name'] = $all_order->order_item_name;
            $information1['delivery_charge'] = number_format((float)$all_order->delivery_charge, 2, '.', '');
            $information1['personal_order_type'] = $all_order->personal_order_type;
            $information1['additional_info'] = $all_order->order_additional_details;

            // $information1['recp order'] = $all_order->recp_area;

            if(in_array($all_order->invoice_no, $invoice1, true) == true){
                $information1['prefered_area'] = 1;
            }elseif(in_array($all_order->invoice_no, $invoice2, true) == true){
                $information1['prefered_area'] = 2;
            }else{
                $information1['prefered_area'] = 3;
            };


            $information[] = $information1;

        }
        // return $information1;
        return [
            'status' => 200,
            'success' => true,
            'msg' => 'Order Found',
            'data'=> $information
        ];

    }else{
        return [
            'status' => 200,
            'success' => false,
            'msg' => 'No data found',
            'data'=> []
        ];
    }


    }

    public function CompanyWiseOrder()
    {
        $company = LogisticsAdditional::where('user_id', auth()->user()['id'])->first();
        //dd($company->company_id);
        if($company->company_id != null){
            $company_id = $company->company_id;
    
            $company_array = json_decode($company_id);
         
            $final_order = DB::table('pre_orders')
                        ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                        ->join('item_types', 'item_types.id', '=', 'pre_orders.item_type')
                        ->join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                        ->whereIn('orders.current_status',[1,2])
                        ->whereIn('orders.company_id', $company_array)
                        ->get();
            if(!$final_order->isEmpty()){
    
            foreach($final_order as $all_order){
    
                $order_details = json_decode($all_order->order_additional_details, true);
                if($order_details){
                    foreach($order_details as $key =>$item)
                    {
                        $order_detail[] = $item;
                        $param_name[] = $key;
                    }
                }
    
                $information1['recp_address'] = $all_order->recp_address;
                $information1['pick_address'] = $all_order->pick_address;
                $information1['item_type'] = $all_order->item_type;
                $information1['item_qty'] = $all_order->item_qty;
                $information1['delivery_date'] = $all_order->delivery_date;
                $information1['invoice_no'] = $all_order->invoice_no;
                $information1['distance'] = number_format((float)$all_order->distance, 2, '.', '');
                $information1['order_item_name'] = $all_order->order_item_name;
                $information1['delivery_charge'] = number_format((float)$all_order->delivery_charge, 2, '.', '');
                $information1['personal_order_type'] = $all_order->personal_order_type;
                $information1['additional_info'] = $all_order->order_additional_details;
                $information1['prefered_area'] = 0;
    
                $information[] = $information1;
    
            }
            // return $information1;
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Order Found',
                'data'=> $information
            ];
    
            }else{
                return [
                'status' => 200,
                'success' => false,
                'msg' => 'No data found',
                'data'=> []
            ];
            }
        }else{
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Company not assigned',
                'data'=> []
            ];
        }
    }



    public function getDriverArea(Request $request){
        $longitude =  $request->longitude;
        $latitude =  $request->latitude;

        $point1 = [$longitude,$latitude];
        $prefered_area_range = DB::table('prefered_area_range')->get();


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
            $driver_area = DB::table('driver_update_area')->join('logistics_addional_infos', 'driver_update_area.user_id', '=', 'logistics_addional_infos.user_id')
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


    public function preferedAreaSearchOrderList(Request $request){

        $validator = Validator::make($request->all(), [
            'to' => 'required|numeric|digits_between: 1,5',
            'from' => 'required|numeric|digits_between: 1,5'
        ]);

        if ($validator->fails())
        {
            return [
                'status' => 200,
                'success' => false,
                'msg' => 'Error',
                'data' => $validator->errors()->all()
            ];
        }
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;


        $pick_area_id =  $request->from;
        $recp_area_id =  $request->to;

            $order_check = Order::whereIn('current_status',[1,2])->get();
            // return $pre_order;
            if(!$order_check->isEmpty())
            {
            $response = DB::table('users')->select('*')
                            ->join('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                            ->where('users.id',auth()->user()['id'])
                            ->first();

            $prefered_area_ranges = DB::table('prefered_area_range')->where('id',$recp_area_id)->first();

            $ranges = [];
            if(@$prefered_area_ranges->related_area != null){

                foreach(json_decode($prefered_area_ranges->related_area) as $related_area){
                    $ranges[] = $related_area;
                }
                //$ranges[] = in_array($request->to,json_decode($prefered_area_range->related_area));
            }

            array_push($ranges,$request->to);
            $ranges = array_unique($ranges);


            if($response){
                if($response->driver_type == 1){
                        $pick_to_recp_orders = [];
                        $pick_to_recp_invoice = [];

                        for($i = 0; $i < count($ranges);$i++){

                            $pick_to_recp_orders[] = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                            ->whereIn('orders.current_status',[1,2])
                                                            ->where('recp_area', $ranges[$i])
                                                            ->where('pick_area_id', $pick_area_id)
                                                            ->whereNull('orders.company_id')
                                                            ->orderBy('orders.personal_order_type', 'desc')
                                                            ->orderBy('orders.created_at', 'desc')
                                                            ->skip($skip)
                                                            ->limit($limit)
                                                            ->get();

                            if($pick_to_recp_orders != null){

                                foreach($pick_to_recp_orders as $item){
                                    if(count($item) != 0){
                                        for($x = 0; $x < count($item); $x++){
                                            $pick_to_recp_invoice[] = $item[$x]->invoice_no;
                                        }
                                    }
                                }
                            }
                        }

                        if(isset($pick_to_recp_invoice)){
                            $invoice1 = $pick_to_recp_invoice;
                        }else{
                           $invoice1 = [];
                        }


                        $invoice1 = array_unique($invoice1);

                }elseif($response->driver_type == 2){
                        $pick_to_recp_orders = [];
                        $pick_to_recp_invoice = [];

                        for($i = 0; $i < count($ranges);$i++){

                            $pick_to_recp_orders[] = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                            ->whereIn('orders.current_status',[1,2])
                                                            ->where('recp_area', $ranges[$i])
                                                            ->where('pick_area_id', $pick_area_id)
                                                            ->whereNull('orders.company_id')
                                                            ->whereRaw('orders.created_at < now() - interval 5 minute')
                                                            ->orderBy('orders.personal_order_type', 'desc')
                                                            ->orderBy('orders.created_at', 'desc')
                                                            ->skip($skip)
                                                            ->limit($limit)
                                                            ->get();

                            if($pick_to_recp_orders != null){

                                foreach($pick_to_recp_orders as $item){
                                    if(count($item) != 0){
                                        for($x = 0; $x < count($item); $x++){
                                            $pick_to_recp_invoice[] = $item[$x]->invoice_no;
                                        }
                                    }
                                }
                            }
                        }

                        if(isset($pick_to_recp_invoice)){
                            $invoice1 = $pick_to_recp_invoice;
                        }else{
                           $invoice1 = [];
                        }

                        $invoice1 = array_unique($invoice1);
                }

                else{
                    return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Driver type not assign',
                        'data'=> []
                    ];
                }
            }else{
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Driver type not assign',
                    'data'=> []
                ];
            }
            // return $invoice3;

            $search_order = $invoice1;
            if(empty($search_order)){
                return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'No data found',
                    'data'=> []
                ];
            }
            foreach($search_order as $single_order)
            {
                $final_order1 = DB::table('pre_orders')
                                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                                    ->join('item_types', 'item_types.id', '=', 'pre_orders.item_type')
                                    ->join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                    ->whereIn('orders.current_status',[1,2])
                                    ->where('orders.invoice_no',$single_order)
                                    ->where('orders.active','!=',0)
                                    ->first();
                if($final_order1 != null){
                    $final_order[] = $final_order1;
                }
            }

            
            if(isset($final_order)){
                foreach($final_order as $all_order){
    
                    $order_details = json_decode($all_order->order_additional_details, true);
                    if($order_details){
                        foreach($order_details as $key =>$item)
                        {
                            $order_detail[] = $item;
                            $param_name[] = $key;
                        }
                    }
    
                    $information1['recp_address'] = $all_order->recp_address;
                    $information1['pick_address'] = $all_order->pick_address;
                    $information1['item_type'] = $all_order->item_type;
                    $information1['item_qty'] = $all_order->item_qty;
                    $information1['delivery_date'] = $all_order->delivery_date;
                    $information1['invoice_no'] = $all_order->invoice_no;
                    $information1['distance'] = number_format((float)$all_order->distance, 2, '.', '');
                    $information1['order_item_name'] = $all_order->order_item_name;
                    $information1['delivery_charge'] = number_format((float)$all_order->delivery_charge, 2, '.', '');
                    $information1['personal_order_type'] = $all_order->personal_order_type;
                    $information1['additional_info'] = $all_order->order_additional_details;
                    // $information1['sender order'] = $all_order->pick_area_id;
                    // $information1['recp order'] = $all_order->recp_area;
    
                    if(in_array($all_order->invoice_no, $pick_to_recp_invoice, true) == true){
                        $information1['prefered_area'] = 1;
                    }else{
                        $information1['prefered_area'] = null;
                    }
    
                    $information[] = $information1;
    
                }
    
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Order Found',
                    'data'=> $information
                ];
            }else{
                return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'No data found',
                    'data'=> []
                ];
            }

        }else{
            return [
                'status' => 200,
                'success' => false,
                'msg' => 'No data found',
                'data'=> []
            ];
        }

    }


        public function preferedAreaSingleSearchOrderList(Request $request){

        $validator = Validator::make($request->all(), [
            'to' => 'required|numeric|digits_between: 1,5',
            'from' => 'required|numeric|digits_between: 1,5'
        ]);

        if ($validator->fails())
        {
            return [
                'status' => 200,
                'success' => false,
                'msg' => 'Error',
                'data' => $validator->errors()->all()
            ];
        }
        $limit = $request->limit ? $request->limit : 20;
        $page = $request->page && $request->page > 0 ? $request->page : 1;
        $skip = ($page - 1) * $limit;


        $pick_area_id =  $request->from;
        $recp_area_id =  $request->to;

            $order_check = Order::whereIn('current_status',[1,2])->get();
            // return $pre_order;
            if(!$order_check->isEmpty())
            {
            $response = DB::table('users')->select('*')
                            ->leftjoin('logistics_addional_infos','users.id','=','logistics_addional_infos.user_id')
                            ->where('users.id',auth()->user()['id'])
                            ->first();
            $prefered_area_ranges = DB::table('prefered_area_range')->where('id',$recp_area_id)->first();

            $ranges = [];
            if(@$prefered_area_ranges->related_area != null){

                foreach(json_decode($prefered_area_ranges->related_area) as $related_area){
                    $ranges[] = $related_area;
                }
                //$ranges[] = in_array($request->to,json_decode($prefered_area_range->related_area));
            }

            array_push($ranges,$request->to);
            $ranges = array_unique($ranges);

            if($response){
                if($response->driver_type == 1){
                        $pick_to_recp_orders = [];
                        $pick_to_recp_invoice = [];

                        for($i = 0; $i < count($ranges);$i++){
                            $pick_to_recp_orders[] = PreOrder::leftjoin('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                        ->whereIn('orders.current_status',[1,2])
                                                        ->where('recp_area', $ranges[$i])
                                                        ->where('pick_area_id', $pick_area_id)
                                                        // ->whereNull('orders.company_id')
                                                        ->orderBy('orders.personal_order_type', 'desc')
                                                        ->skip($skip)
                                                        ->limit($limit)
                                                        ->get();

                            if($pick_to_recp_orders != null){
                                foreach($pick_to_recp_orders as $item){
                                    for($x = 0; $x < count($item); $x++){
                                            $pick_to_recp_invoice[] = $item[$x]->invoice_no;
                                        }
                                }
                            }
                        }
                        if(isset($pick_to_recp_invoice)){
                            $pick_to_recp_invoice = $pick_to_recp_invoice;
                        }else{
                           $pick_to_recp_invoice = [];
                        }

                        $others_to_recp_orders = [];
                        $others_to_recp_invoice = [];

                        for($i = 0; $i < count($ranges);$i++){
                            $others_to_recp_orders[] = PreOrder::leftjoin('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                    ->whereIn('orders.current_status',[1,2])
                                                    ->where('recp_area', $ranges[$i])
                                                    ->whereNull('orders.company_id')
                                                    ->orderBy('orders.personal_order_type', 'desc')
                                                    ->skip($skip)
                                                    ->limit($limit)
                                                    ->get();
                            // return $others_to_recp_orders;
                            if($others_to_recp_orders != null){

                                foreach($others_to_recp_orders as $item){
                                    if(count($item) != 0){
                                        for($x = 0; $x < count($item); $x++){
                                            $others_to_recp_invoice[] = $item[$x]->invoice_no;
                                        }
                                    }
                                }
                            }

                        }

                        if(isset($others_to_recp_invoice)){
                            $others_to_recp_invoice = $others_to_recp_invoice;
                        }else{
                           $others_to_recp_invoice = [];
                        }


                    $invoice1 = array_unique(array_merge($pick_to_recp_invoice, $others_to_recp_invoice), SORT_REGULAR);

                }elseif($response->driver_type == 2){
                        $pick_to_recp_orders = [];
                        $pick_to_recp_invoice = [];

                        for($i = 0; $i < count($ranges);$i++){
                            $pick_to_recp_orders[] = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                        ->whereIn('orders.current_status',[1,2])
                                                        ->where('recp_area', $ranges[$i])
                                                        ->where('pick_area_id', $pick_area_id)
                                                        ->whereNull('orders.company_id')
                                                        ->whereRaw('orders.created_at < now() - interval 5 minute')
                                                        ->orderBy('orders.personal_order_type', 'desc')
                                                        ->skip($skip)
                                                        ->limit($limit)
                                                        ->get();

                            if($pick_to_recp_orders != null){
                                foreach($pick_to_recp_orders as $item){
                                    for($x = 0; $x < count($item); $x++){
                                            $pick_to_recp_invoice[] = $item[$x]->invoice_no;
                                        }
                                }
                            }
                        }
                        if(isset($pick_to_recp_invoice)){
                            $pick_to_recp_invoice = $pick_to_recp_invoice;
                        }else{
                           $pick_to_recp_invoice = [];
                        }

                        $others_to_recp_orders = [];
                        $others_to_recp_invoice = [];

                        for($i = 0; $i < count($ranges);$i++){
                            $others_to_recp_orders[] = PreOrder::join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                                    ->whereIn('orders.current_status',[1,2])
                                                    ->where('recp_area', $ranges[$i])
                                                    ->whereNull('orders.company_id')
                                                    ->whereRaw('orders.created_at < now() - interval 5 minute')
                                                    ->orderBy('orders.personal_order_type', 'desc')
                                                    ->skip($skip)
                                                    ->limit($limit)
                                                    ->get();

                            if($others_to_recp_orders != null){

                                foreach($others_to_recp_orders as $item){
                                    if(count($item) != 0){
                                        for($x = 0; $x < count($item); $x++){
                                            $others_to_recp_invoice[] = $item[$x]->invoice_no;
                                        }
                                    }
                                }
                            }

                        }

                        if(isset($others_to_recp_invoice)){
                            $others_to_recp_invoice = $others_to_recp_invoice;
                        }else{
                           $others_to_recp_invoice = [];
                        }



                    $invoice1 = array_unique(array_merge($pick_to_recp_invoice, $others_to_recp_invoice), SORT_REGULAR);
                }


                else{
                    return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Driver type not assign',
                        'data'=> []
                    ];
                }
            }else{
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Driver type not assign',
                    'data'=> []
                ];
            }
            // return $invoice3;
            $search_order = $invoice1;
            if(empty($search_order)){
                return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'No data found',
                    'data'=> []
                ];
            }
            foreach($search_order as $single_order)
            {
                $final_order1 = DB::table('pre_orders')
                                    ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                                    ->join('item_types', 'item_types.id', '=', 'pre_orders.item_type')
                                    ->join('orders', 'pre_orders.invoice_no', '=', 'orders.invoice_no')
                                    ->whereIn('orders.current_status',[1,2])
                                    ->where('orders.invoice_no',$single_order)
                                    ->where('orders.active','!=',0)
                                    ->first();
                if($final_order1 != null){
                    $final_order[] = $final_order1;
                }
            }


            if(isset($final_order)){

                foreach($final_order as $all_order){
    
                    $order_details = json_decode($all_order->order_additional_details, true);
                    if($order_details){
                        foreach($order_details as $key =>$item)
                        {
                            $order_detail[] = $item;
                            $param_name[] = $key;
                        }
                    }
    
                    $information1['recp_address'] = $all_order->recp_address;
                    $information1['pick_address'] = $all_order->pick_address;
                    $information1['item_type'] = $all_order->item_type;
                    $information1['item_qty'] = $all_order->item_qty;
                    $information1['delivery_date'] = $all_order->delivery_date;
                    $information1['invoice_no'] = $all_order->invoice_no;
                    $information1['distance'] = number_format((float)$all_order->distance, 2, '.', '');
                    $information1['order_item_name'] = $all_order->order_item_name;
                    $information1['delivery_charge'] = number_format((float)$all_order->delivery_charge, 2, '.', '');
                    $information1['personal_order_type'] = $all_order->personal_order_type;
                    $information1['additional_info'] = $all_order->order_additional_details;
                    // $information1['sender order'] = $all_order->pick_area_id;
                    // $information1['recp order'] = $all_order->recp_area;
    
                    if(in_array($all_order->invoice_no, $pick_to_recp_invoice, true) == true){
                        $information1['prefered_area'] = 1;
                    }elseif(in_array($all_order->invoice_no, $others_to_recp_invoice, true) == true){
                        $information1['prefered_area'] = 2;
                    }else{
                        $information1['prefered_area'] = null;
                    }
    
                    $information[] = $information1;
    
                }
    
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Order Found',
                    'data'=> $information
                ];
            }else{
                return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'No data found',
                    'data'=> []
                ];
            }

        }else{
            return [
                'status' => 200,
                'success' => false,
                'msg' => 'No data found',
                'data'=> []
            ];
        }


    }

    public function orderHistory(){

        $results = DB::table('logistics_tracking')
                            ->where('logistics_tracking.logistics_id',auth()->user()['id'])
                            ->get();

        $invoice_no = [];
        foreach($results as $result){
            $invoice_no[] = $result->invoice_no;
        }
        $invoice_no = array_unique($invoice_no);

        $orders = DB::table('orders')
                            ->join('pre_orders', 'orders.invoice_no', '=', 'pre_orders.invoice_no')
                            ->join('users','users.id','=','orders.user_id')
                            ->join('item_types', 'item_types.id', '=', 'pre_orders.item_type')
                            ->join('order_distance', 'pre_orders.invoice_no', '=', 'order_distance.invoice_no')
                            ->whereIn('orders.invoice_no', $invoice_no)
                            ->where('orders.current_status', 5)
                            ->orderby('orders.id','desc')
                            ->get();

        if(count($orders) > 0){
              foreach($orders as $single_order){

    	      $information1['delivery_charge'] = number_format((float)$single_order->delivery_charge, 2, '.', '');
    	      $information1['order_type'] = $single_order->order_type;
    	      $information1['current_status'] = $single_order->current_status;
    	      $information1['delivery_date'] = $single_order->delivery_date;
    	      $information1['order_date'] = $single_order->order_date;
    	      $information1['logistics_charge'] = number_format((float)$single_order->logistics_charge, 2, '.', '');
    	      $information1['personal_order_type'] = $single_order->personal_order_type;
    	      $information1['order_additional_details'] = $single_order->order_additional_details;
    	      $information1['recp_name'] = $single_order->recp_name;
    	      $information1['recp_phone'] = $single_order->recp_phone;
    	      $information1['recp_address'] = $single_order->recp_address;
    	      $information1['pic_name'] = $single_order->pic_name;
    	      $information1['pic_phone'] = $single_order->pic_phone;
    	      $information1['pick_address'] = $single_order->pick_address;
    	      $information1['item_type'] = $single_order->item_type;
    	      $information1['item_qty'] = $single_order->item_qty;
    	      $information1['order_item_name'] = $single_order->order_item_name;
    	      $information1['sender_latitude'] = $single_order->sender_latitude;
    	      $information1['sender_longitude'] = $single_order->sender_longitude;
    	      $information1['receiver_latitude'] = $single_order->receiver_latitude;
    	      $information1['receiver_longitude'] = $single_order->receiver_longitude;
    	      $information1['distance'] = number_format((float)$single_order->distance, 2, '.', '');


    	      $information[] = $information1;
    	      //number_format($single_order->distance, 2, '.', '');


    	  }
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Order Found',
                'data' => $information
            ];
        }else{
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'No Order Found',
                'data' => [],
            ];
        }
    }

    public function createParcel(Request $request){

        for($i = 0; $i < count($request->data);$i++){
            $recp_name = $request->data[$i]['recp_name'];
            $recp_phone = $request->data[$i]['recp_phone'];
            $recp_address = $request->data[$i]['recp_address'];
            $pic_name = $request->data[$i]['pick_name'];
            $pic_phone = $request->data[$i]['pick_phone'];
            $pic_address = $request->data[$i]['pick_address'];
            $item_type = $request->data[$i]['item_type'];
            $item_qty = $request->data[$i]['item_qty'];
            $item_weight = $request->data[$i]['item_weight'];
            $item_des = $request->data[$i]['item_des'];
            $item_price = $request->data[$i]['item_price'];
            $invoice_no = '#AIR'.rand(0,99999).rand(0,99999);

            //DB::table('orders')->insert(
              //  ['recp_name' => $recp_name,'recp_phone' => $recp_phone,'recp_address' => $recp_address,'pic_name' => $pic_name,'$pic_phone' => $pick_phone,'pic_address' => $pic_address,'item_type' => $item_type,'item_qty' => $item_qty,'item_weight' => $item_weight,'item_des' => $item_des,'item_price' => $item_price]
            //);
        }


    }




    public function checkParcel(){

        $url = 'https://api.parcelmagic.com/api/v1/create_parcel';

        $data = [
                    'endpoint' => 'v1/create_parcel',
                    'method' => 'POST',
                    'data' => [
                        [
                            'recp_name'=>'korim',
                            'recp_phone'=>'0193899393',
                            'recp_address'=>'road:4, sec:4, uttara, dhaka',
                            'pick_name'=>'jon',
                            'pick_phone'=>'',
                            'pick_address'=>'road:4, sec:4, mogbazar, dhaka',
                            'item_type'=> 1, // 1=Fragile. 2=liquid, 3=solid, 4 = document
                            'item_qty'=> 2,
                            'item_weight'=> 1,
                            'item_price'=> 200,
                        ],
                        [
                            'recp_name'=>'Rohim',
                            'recp_phone'=>'0193899392',
                            'recp_address'=>'road:4, sec:4, uttara, dhaka',
                            'pick_name'=>'jon',
                            'pick_phone'=>'017838838938',
                            'pick_address'=>'road:4, sec:4, mogbazar, dhaka',
                            'item_type'=> 1, // 1=Fragile. 2=liquid, 3=solid, 4 = document
                            'item_qty'=> 2,
                            'item_weight'=> 1,
                            'item_price'=> 200,
                        ]
                    ]
                ];

        $query_string = http_build_query($data);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        print_r($result);

    }


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


    public function setParcel(Request $request){


        $sender_latitude   =  $request->sender_latitude;
        $sender_longitude   =  $request->sender_longitude;
        $receiver_latitude   =  $request->receiver_latitude;
        $receiver_longitude   =  $request->receiver_longitude;


        $point1 = [$sender_longitude,$sender_latitude];
        $point2 = [$receiver_longitude,$receiver_latitude];

        $prefered_area_range = DB::table('prefered_area_range')->get();

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
        
        $user_id = Auth::id();
        $recp_name = strip_tags($request->recp_name);
        $invoice_no = $request->invoice_no;
        $recp_phone  = strip_tags($request->recp_phone);
        $recp_city = $request->recp_city;
        $recp_zone = $request->recp_zone;
        $recp_area	 = $request->recp_area;
        $recp_address = $request->recp_address;
        $pick_phone = strip_tags($request->pic_phone);
        $pick_name = strip_tags($request->pic_name);
        $pick_city = $request->pick_city;
        $pick_zone = $request->pick_zone;
        $pick_area	 = $request->pick_area_id;
        $pick_address = $request->pick_address;
        $item_type = $request->item_type;
    	$item_qty = $request->item_qty;
        $item_weight = 1;
        $item_des	 = $request->item_des;
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





        $preOrder = new PreOrder();
        $preOrder->recp_name =   $recp_name;
        $preOrder->recp_phone =   $recp_phone;
        $preOrder->recp_city =   $recp_city;
        $preOrder->recp_zone =   $recp_zone;
        $preOrder->recp_area =   $receiver_area_id;
        $preOrder->recp_address =   $recp_address;
        $preOrder->pic_name =   $pick_name;
        $preOrder->pic_phone =   $pick_phone;
        $preOrder->pick_city =   $pick_city;
        $preOrder->pick_area_id =   $sender_area_id;
        $preOrder->pick_zone =   $pick_zone;
        $preOrder->pick_address =   $pick_address;
        $preOrder->item_type =   $item_type;
        $preOrder->item_weight =   $item_weight;
        $preOrder->item_des =   $item_des;
        $preOrder->item_qty =   $item_qty;
        $preOrder->item_price =   $item_price;
        $preOrder->special_instruction =  $special_instruction;
        $preOrder->invoice_no = $invoice_no;
        $preOrder->Save();
        if($preOrder->Save()){

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
            $order->user_id = $user_id;
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
            $order->save();
            $order_lastinsert_id = $order->id;




            DB::table('order_distance')->insert(
                 array(
                        'sender_latitude'   =>  $request->sender_latitude,
                        'sender_longitude'   =>  $request->sender_longitude,
                        'receiver_latitude'   =>  $request->receiver_latitude,
                        'receiver_longitude'   =>  $request->receiver_longitude,
                        'distance'   =>  $request->distance,
                        'invoice_no'   =>  $invoice_no
                 )
            );




            DB::table('journal')->insert(
                 array(
                        'invoice_no' => $invoice_no,
                        'amount' => $delivery_charge,
                        'db' => 'debit',
                        'account_title_id' => 1,
                        'input_user_id' => 1
                 )
            );


            DB::table('journal')->insert(
                 array(
                        'invoice_no' => $invoice_no,
                        'amount' => $delivery_charge,
                        'db' => 'credit',
                        'account_title_id' => $user_id,
                        'input_user_id' => 1
                 )
            );


            DB::table('system_status_logs')->insert(
                ['invoice_id' => $invoice_no, 'status' => $current_status]
            );







    		$url = "https://fcm.googleapis.com/fcm/send";
            $category = 'place_order';
            $order_id = $order_lastinsert_id;
            $title = "Order placed succesfully. Invoice no:".$invoice_no;
            $description = "click here for see details";
            $id = 1;
            $order_distances = DB::table('prefered_area_range')->get();
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
                'sender_address'   => $pick_address,
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
                    "sender_address"=>$pick_address,
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


        }else{
            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Error',
                    // 'data' => []
            ];
        }

    }

        public function setParcelService(Request $request){

            $sender_latitude   =  $request->sender_latitude;
            $sender_longitude   =  $request->sender_longitude;
            $receiver_latitude   =  $request->receiver_latitude;
            $receiver_longitude   =  $request->receiver_longitude;

            if($receiver_latitude == 22.360483 || $receiver_longitude == 91.791954 || $receiver_latitude == 24.894954 || $receiver_longitude == 91.868721){
                $distance = 350;
                $receiver_area_id = 432;
                $sender_area_id = 43;

            }else{


            $distance = $this->calDistance($sender_latitude, $sender_longitude, $receiver_latitude, $receiver_longitude, "K");
            // $sender_area = $this->getCurrentArea($sender_latitude, $sender_longitude);

            // $recp_area = $this->getCurrentArea($receiver_latitude, $receiver_longitude);

            //FInding Area from lat long


                        $point1 = [$sender_longitude,$sender_latitude];
                        $point2 = [$receiver_longitude,$receiver_latitude];

                        $prefered_area_range = DB::table('prefered_area_range')->get();

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
            }
                // return $receiver_area_id;
            //End finding area

            $company_service = CompanyService::where('client_id', $request->client_id)
                                                ->where('client_secret', $request->client_secret)
                                                ->first();
            $user_id = $company_service->company_id;
            $company_id = $company_service->company_id;
            $recp_name = strip_tags($request->recp_name);
            $rand1 = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
            $rand2 = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
            $invoice_no = $rand1.'-'.$rand2;
            $recp_phone  = strip_tags($request->recp_phone);
            $recp_area	 = $receiver_area_id;
            $recp_address = $request->recp_address;
            $pick_phone = strip_tags($request->pic_phone);
            $pick_name = strip_tags($request->pic_name);
            $pick_area	 = $sender_area_id;
            $pick_address = $request->pick_address;
            $item_type = 3;
        	$item_qty = $request->item_qty;
            $order_type = 1;
            $personal_order_type = 1;
            $delivery_date = $request->delivery_date;
            $order_date = date("Y-m-d H:i:s");
            $order_additional_details = $request->order_additional_details;
            $current_status = 2;
            $who_will_pay = 0;
            $dimension = 2;
            $delivery_type = 1;
            $coc = 0;

            $total = DeliveryChargeModel::find($dimension);
            $basePrice = 0;
            if ($delivery_type == 1) {
              if ($distance == 0) {
                $basePrice =  $total->base_price_express;
              } else {
                $basePrice =  $total->per_km_price_express * $distance;
              }
            } else if ($delivery_type == 2) {
              if ($distance == 0) {
                $basePrice =  $total->base_price_quick;
              } else {
                $basePrice =  $total->per_km_price_quick * $distance;
              }
            }


            $delivery_charge = $basePrice;
            $logistics_charge = ($request->delivery_charge * 25)/100;

            $preOrder = new PreOrder();
            $preOrder->recp_name =   $recp_name;
            $preOrder->recp_phone =   $recp_phone;
            $preOrder->recp_area =   $recp_area;
            $preOrder->recp_address =   $recp_address;
            $preOrder->pic_name =   $pick_name;
            $preOrder->pic_phone =   $pick_phone;
            $preOrder->pick_area_id =   $pick_area;
            $preOrder->pick_address =   $pick_address;
            $preOrder->item_type =   $item_type;
            $preOrder->item_qty =   $item_qty;
            $preOrder->invoice_no = $invoice_no;
            $preOrder->Save();
            if($preOrder->Save()){

                $order = new Order();

                $order->invoice_no = $invoice_no;
                $order->user_id = $user_id;
                $order->order_type = 2;
                $order->current_status = 2;
                $order->order_date = $order_date;
                $order->delivery_date = $delivery_date;
                $order->dimention = $dimension;
                $order->logistics_charge = $logistics_charge;
                $order->who_will_pay = $who_will_pay;
                $order->delivery_charge = $delivery_charge;
                $order->personal_order_type = $personal_order_type;
                $order->order_type = $order_type;
                $order->billing_status = 0;
                $order->cod = 1;
                $order->coc = $coc;
                $order->order_additional_details = $order_additional_details;
                $order->company_id = $company_id;

                $order->save();
                $order_lastinsert_id = $order->id;

                DB::table('order_distance')->insert(
                     array(
                            'sender_latitude'   =>  $request->sender_latitude,
                            'sender_longitude'   =>  $request->sender_longitude,
                            'receiver_latitude'   =>  $request->receiver_latitude,
                            'receiver_longitude'   =>  $request->receiver_longitude,
                            'distance'   =>  round($distance,2),
                            'invoice_no'   =>  $invoice_no
                     )
                );


                DB::table('system_status_logs')->insert(
                    ['invoice_id' => $invoice_no, 'status' => $current_status]
                );







        		$url = "https://fcm.googleapis.com/fcm/send";
                $category = 'place_order';
                $order_id = $order_lastinsert_id;
                $title = "Order placed succesfully. Invoice no:".$invoice_no;
                $description = "click here for see details";
                $id = 1;
                $order_distances = DB::table('prefered_area_range')->get();
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




                $url = 'https://portal.adnsms.com/api/v1/secure/send-sms';
                $params = array(
                    "api_key" => 'KEY-xe7m5lxxd83bp4jsc6ghetgf0g70t9h5',
                    "api_secret" => 'eObDf8Qynu6h!tSI',
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


                $firebase = new FirebaseLib($this->firebase_database,$this->fireabase_pass);
                $test = [
                    'invoice_no' => $invoice_no,
                    'sender_address'   => $pick_address,
                    'receiver_address'   => $recp_address,
                    'earning'   =>  0,
                    'sender_area'   => $sender_area_id,
                    'sender_lat'   =>  (float)$request->sender_latitude,
                    'sender_long'   =>  (float)$request->sender_longitude,
                    'receiver_lat'   => (float)$request->receiver_latitude,
                    'receiver_long'   => (float)$request->receiver_longitude,
                    'receiver_area'   =>  $receiver_area_id,
                    'order_date' => date("Y-m-d h:i:s"),
                    'driver_type' => 4,
                    'delivery_date' => $delivery_date,
                    'distance' => 0,
                    'qty' => (int)$item_qty,
                    'type' => (int)$item_type
                ];
                $dateTime = new DateTime();
                $firebase->set("Parcelmagic" . '/' . $invoice_no,$test);


                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'order placed successfully',
                        // 'data' => []
                ];


            }else{
                return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'Error',
                        // 'data' => []
                ];
            }

    }

        public function getCurrentArea($longitude, $latitude){

        // $longitude =  $request->longitude;
        // $latitude =  $request->latitude;
        return 1;
        $point1 = [$longitude,$latitude];
        $prefered_area_range = DB::table('prefered_area_range')->get();

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
          if($area_name != null){

              return $area_name;
          }else{

              return [
                      'status' => 200,
                      'success' => true,
                      'msg' => 'No Data Found'
              ];
          }

    }


    //Arman's API Starts here

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


    public function userBasedCoupon(Request $request){

        $user_id = auth()->user()['id'];


        $coupons = DB::table('coupons')->select('coupon_text','discount_amount','id')->where('user_id' , null)->where('published' , 1)->where('expired_on','>',date("Y/m/d"))->get();

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

    public function setRating(Request $request)
    {

        $user_id = auth()->user()['id'];
        $logisticId = $request->logisticId;
        $rating = $request->rating;
        $invoice = $request->invoice;

        $ratingPoint = DB::table('parcel_rating')->insert([
            "user_id" => $user_id,
            "rating_point" => $rating,
            "logisticId" => $logisticId,
            "invoiceId" => $invoice
            ]);

        $ratedInvoice = DB::table('orders')->where('invoice_no', $invoice)->update(['isRated' => 1]);

        return [
                'status' => 200,
                'success' => true,
                'msg' => 'Rating added successfully'
        ];

    }


    public function getAvgRating(Request $request){

        $user_id = auth()->user()['id'];

        $ratings = DB::table('parcel_rating')->where("logisticId" , $user_id)->select(DB::raw('AVG(rating_point) as ratings_average'))->get();

        return [
                'status' => 200,
                'success' => true,
                'msg' => 'Avg rating data',
                'data' => $ratings
        ];
    }

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

    
    

    public function driverProfile(){
        
        $user_id = auth()->user()['id'];

        $users = DB::table('users')->where("id" , $user_id)->first();
        $user_address = DB::table('logistics_addional_infos')->select('address')->where("user_id" , $users->id)->first()->address;
        
        $invoice_no = DB::table('logistics_tracking')->select('invoice_no')->where("logistics_id" ,$user_id)->get();
        
        $invoice_no_arr = [];
        foreach($invoice_no as $invoice){
            $invoice_no_arr[] = $invoice->invoice_no;
        }
        
        if(isset($users->id)){
            
            $ratings = DB::table('ratings')->where('user_id',$user_id)->avg('points'); 
            $pending_delivery = DB::table('orders')->whereIn('current_status',[2, 3, 4])->whereIn('invoice_no',$invoice_no_arr)->count(); 
            $completed_delivery = DB::table('orders')->where('current_status',5)->whereIn('invoice_no',$invoice_no_arr)->count(); 
            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Data found',
                    'data' => ['id'=>$users->id,'name'=>$users->username,'address'=>$user_address,'phone'=>$users->phone,'rating'=>$ratings,'pending_delivery'=>$pending_delivery,'completed_delivery'=>$completed_delivery]
            ];
            
        }else{
            return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'User not found',
                    'data' => []
            ];
        }
        
        
        
    }


}
