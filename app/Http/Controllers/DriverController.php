<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Driver;
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

class DriverController extends Controller
{

    //Auth User ID
    public function loginUser(Request $request){
        $acceptHeader = $request->header('Authorization');
        $user=DB::table('driver_infos')->where('auth_access_token',$acceptHeader)->first();
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

    // caldistance
    private function calDistance($lat1, $lon1, $lat2, $lon2, $unit){

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

    // send sms
    public function SendSms($status,$phone,$invoice_no,$body){

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

    // driver config merge
    public function configUpdate(Request $request){

        $login_id=$this->loginUser($request);

        $personal_check = Driver::where('id',$login_id)->update(['fcm_token_driver' => $request->fcm_token]);

        $longitude =  $request->longitude;
        $latitude =  $request->latitude;

        $point1 = [$longitude,$latitude];
        $prefered_area_range = DB::table('prefered_area_ranges')->get();
        $company = Driver::where('id', $login_id)->first();
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
            $this->driverLog($login_id, $latitude, $longitude);
            $driver_area =  DB::table('driver_update_areas')->where('driver_id', $login_id)->first();
            if($driver_area){
                $driver_area_update = DB::table('driver_update_areas')->where('driver_id', $login_id)->update([
                    'area_id'    => $id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,

                ]);
              }else{
                    $driver_area_insert = DB::table('driver_update_areas')->insert([
                      'driver_id' => $login_id,
                      'area_id'    => $id,
                      'latitude' => $latitude,
                      'longitude' => $longitude,

                  ]);
              }
            $areas = DB::table('prefered_area_ranges')->orderBy('area_name')->get();
            $area_order_count = DB::table('orders')
                                    ->whereIn('current_status',[1,2])
                                    ->where('sender_area_id',$id)
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

    //
    public function allArea(Request $request){
        $areas = DB::table('prefered_area_ranges')->orderBy('area_name')->get();
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

    // prefered area list add
    public function preferedAreaListAdd(Request $request){

        $login_id=$this->loginUser($request);

        $prefered_area_range = DB::table('prefered_areas')->where('driver_id',$login_id)->where('area_id',$request->area_id)->first();

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
                                ['area_id' => $request->area_id, 'driver_id' => $login_id]
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

    //
    public function preferedAreaListView(Request $request){

        $login_id=$this->loginUser($request);

        $prefered_area_range = DB::table('prefered_area_ranges')
                                            ->join('prefered_areas','prefered_area_ranges.id','=','prefered_areas.area_id')
                                            ->where('prefered_areas.driver_id',$login_id)
    	                                    ->get();

        $prefered_area_rangeList = [];
        $data = [];
        $matched_order = [];
        // return $prefered_area_range;
    	foreach($prefered_area_range as $prefered_area_ranges){

                    $orders = DB::table('orders')
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
                        // dd($ordersdata);
                        if($ordersdata != null){
                            $counts += 1;
                        }
                    }

	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['id'] = $prefered_area_ranges->id;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['area_name'] = $prefered_area_ranges->area_name;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['range'] = $prefered_area_ranges->range;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['area_id'] = $prefered_area_ranges->area_id;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['driver_id'] = $prefered_area_ranges->driver_id;
	       $prefered_area_rangeList[$prefered_area_ranges->area_name]['count'] = $counts;
	       $data[] = $prefered_area_rangeList[$prefered_area_ranges->area_name];
        }



    	// dd($prefered_area_rangeList);
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

    // prefered area list delete
    public function preferedAreaListDelete(Request $request){

        $login_id=$this->loginUser($request);

        try {

            DB::table('prefered_areas')
                        ->where('area_id', $request->prefered_area_id)
                        ->where('driver_id', $login_id)
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

    // prefered area order list
    public function preferedAreaOrderList(Request $request){

        $login_id=$this->loginUser($request);

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
            $response = Driver::select('*')
                                ->where('id',$login_id)
                                ->first();
            if($response){
                if($response->driver_type == 1){
                    $prefered_areas = $prefered_areas = DB::table('prefered_areas')->where('driver_id',$login_id)->get();
                    // return $prefered_areas;
                    $a = [];
                    if(!$prefered_areas->isEmpty()){
                            for($i =0 ; $i < count($prefered_areas) ; $i++){
                            $prefered_area[] = $prefered_areas[$i]->area_id;
                        }

                    $prefered_pick_orders = DB::table('orders')
                                            ->whereIn('current_status',[1,2])
                                            ->whereIn('recp_area', $prefered_area)
                                            ->where('sender_area_id', $pick_area_id)
                                            ->whereNull('company_id')
                                            ->orderBy('personal_order_type', 'desc')
                                            ->get();

                    if(!$prefered_pick_orders->isEmpty()){
                        foreach($prefered_pick_orders as $item){
                            $prefered_pick_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $prefered_pick_invoice = [];
                    }

                    $prefered_others_orders = DB::table('orders')
                                            ->whereIn('current_status',[1,2])
                                            ->whereIn('recp_area', $prefered_area)
                                            ->whereNull('company_id')
                                            ->orderBy('personal_order_type', 'desc')
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
                $pick_orders = DB::table('orders')
                                        ->whereIn('current_status',[1,2])
                                        ->where('sender_area_id', $pick_area_id)
                                        ->whereNull('company_id')
                                        ->orderBy('personal_order_type', 'desc')
                                        ->get();

                if(!$pick_orders->isEmpty()){
                    foreach($pick_orders as $item){
                        $pick_invoice[] = $item->invoice_no;
                    }
                }else{
                    $pick_invoice = [];
                }


                $invoice2 = $pick_invoice;

                $others_orders = DB::table('orders')
                                        ->whereIn('current_status',[1,2])
                                        ->whereNull('company_id')
                                        // ->whereNotIn('orders.invoice_no', $invoice1)
                                        // ->whereNotIn('orders.invoice_no', $invoice2)
                                        ->orderBy('personal_order_type', 'desc')
                                        ->get();

                foreach($others_orders as $item){
                        $others_invoice[] = $item->invoice_no;
                }

                $invoice3 = $others_invoice;
            }

            elseif($response->driver_type == 2){
                $prefered_areas = $prefered_areas = DB::table('prefered_areas')->where('driver_id ',$login_id)->get();
                    // return $prefered_areas;
                $a = [];
                if(!$prefered_areas->isEmpty()){
                    for($i =0 ; $i < count($prefered_areas) ; $i++){
                        $prefered_area[] = $prefered_areas[$i]->area_id;
                    }

                    $prefered_pick_orders = DB::table('orders')
                                                ->whereIn('current_status',[1,2])
                                                ->whereIn('recp_area', $prefered_area)
                                                ->where('pick_area_id', $pick_area_id)
                                                ->whereNull('company_id')
                                                ->whereRaw('created_at < now() - interval 5 minute')
                                                ->orderBy('personal_order_type', 'desc')
                                                ->get();

                    if(!$prefered_pick_orders->isEmpty()){
                        foreach($prefered_pick_orders as $item){
                            $prefered_pick_invoice[] = $item->invoice_no;
                        }
                    }else{
                        $prefered_pick_invoice = [];
                    }

                    $prefered_others_orders = DB::table('orders')
                                                ->whereIn('current_status',[1,2])
                                                ->whereIn('recp_area', $prefered_area)
                                                ->whereNull('company_id')
                                                ->whereRaw('created_at < now() - interval 5 minute')
                                                ->orderBy('personal_order_type', 'desc')
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

                $pick_orders = DB::table('orders')
                                    ->whereIn('current_status',[1,2])
                                    ->where('pick_area_id', $pick_area_id)
                                    ->whereNull('company_id')
                                    ->whereRaw('created_at < now() - interval 5 minute')
                                    ->orderBy('personal_order_type', 'desc')
                                    ->get();

                if(!$pick_orders->isEmpty()){
                    foreach($pick_orders as $item){
                        $pick_invoice[] = $item->invoice_no;
                    }
                }else{
                    $pick_invoice = [];
                }

                $invoice2 = $pick_invoice;

                $others_orders = DB::table('orders')
                                    ->whereIn('current_status',[1,2])
                                    ->whereNull('company_id')
                                    // ->whereNotIn('orders.invoice_no', $invoice1)
                                    // ->whereNotIn('orders.invoice_no', $invoice2)
                                    ->whereRaw('created_at < now() - interval 5 minute')
                                    ->orderBy('personal_order_type', 'desc')
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

        foreach($search_order as $single_order){
            $final_order1 = DB::table('orders')
                                ->join('item_types', 'item_types.id', '=', 'orders.item_type')
                                ->whereIn('current_status',[1,2])
                                ->where('invoice_no',$single_order)
                                ->where('active','!=',0)
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
            $information1['sender_address'] = $all_order->sender_address;
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

    // prefered area search order list
    public function preferedAreaSearchOrderList(Request $request){

        $login_id=$this->loginUser($request);

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
        if(!$order_check->isEmpty()){
            $response = Driver::select('*')
                            ->where('id',$login_id)
                            ->first();

        $prefered_area_ranges = DB::table('prefered_area_rangeS')->where('id',$recp_area_id)->first();

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

                for($i = 0; $i < count($ranges);$i++)
                {
                    $pick_to_recp_orders[] = DB::table('orders')
                                                ->whereIn('current_status',[1,2])
                                                ->where('recp_area', $ranges[$i])
                                                ->where('sender_area_id', $pick_area_id)
                                                ->whereNull('company_id')
                                                ->orderBy('personal_order_type', 'desc')
                                                ->orderBy('created_at', 'desc')
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
            elseif($response->driver_type == 2){
                $pick_to_recp_orders = [];
                $pick_to_recp_invoice = [];

                for($i = 0; $i < count($ranges);$i++)
                {

                    $pick_to_recp_orders[] = DB::table('orders')
                                                ->whereIn('current_status',[1,2])
                                                ->where('recp_area', $ranges[$i])
                                                ->where('sender_area_id', $pick_area_id)
                                                ->whereNull('company_id')
                                                ->whereRaw('created_at < now() - interval 5 minute')
                                                ->orderBy('personal_order_type', 'desc')
                                                ->orderBy('created_at', 'desc')
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
            $final_order1 = DB::table('orders')
                                ->join('item_types', 'item_types.id', '=', 'orders.item_type')
                                ->whereIn('current_status',[1,2])
                                ->where('invoice_no',$single_order)
                                ->where('active','!=',0)
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
                $information1['sender_address'] = $all_order->sender_address;
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

    // prefered area single search order list
    public function preferedAreaSingleSearchOrderList(Request $request){

        $login_id=$this->loginUser($request);

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
            $response = Driver::select('*')
                                ->where('id',$login_id)
                                ->first();

            $prefered_area_ranges = DB::table('prefered_area_ranges')->where('id',$recp_area_id)->first();

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
                            $pick_to_recp_orders[] = DB::table('orders')
                                                        ->whereIn('current_status',[1,2])
                                                        ->where('recp_area', $ranges[$i])
                                                        ->where('sender_area_id', $pick_area_id)
                                                        // ->whereNull('orders.company_id')
                                                        ->orderBy('personal_order_type', 'desc')
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
                            $others_to_recp_orders[] = DB::table('orders')
                                                        ->whereIn('current_status',[1,2])
                                                        ->where('recp_area', $ranges[$i])
                                                        ->whereNull('company_id')
                                                        ->orderBy('personal_order_type', 'desc')
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
                            $pick_to_recp_orders[] = DB::table('orders')
                                                        ->whereIn('current_status',[1,2])
                                                        ->where('recp_area', $ranges[$i])
                                                        ->where('sender_area_id', $pick_area_id)
                                                        ->whereNull('company_id')
                                                        ->whereRaw('created_at < now() - interval 5 minute')
                                                        ->orderBy('personal_order_type', 'desc')
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
                            $others_to_recp_orders[] = DB::table('orders')
                                                            ->whereIn('current_status',[1,2])
                                                            ->where('recp_area', $ranges[$i])
                                                            ->whereNull('company_id')
                                                            ->whereRaw('created_at < now() - interval 5 minute')
                                                            ->orderBy('personal_order_type', 'desc')
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
                $final_order1 = DB::table('orders')
                                    ->join('item_types', 'item_types.id', '=', 'orders.item_type')
                                    ->whereIn('current_status',[1,2])
                                    ->where('invoice_no',$single_order)
                                    ->where('active','!=',0)
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
                    $information1['sender_address'] = $all_order->sender_address;
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

    // delivery current order list
    public function deliverycurrentorderlist(Request $request){

        $login_id=$this->loginUser($request);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $results = DB::table('driver_trackings')
                            ->where('driver_trackings.driver_id',$login_id)
                            ->get();

        $invoice_no = [];
        foreach($results as $result){
            $invoice_no[] = $result->invoice_no;
        }
        $invoice_no = array_unique($invoice_no);

        //For current status 3
        $order = DB::table('orders')
                            ->join('item_types', 'item_types.id', '=', 'orders.item_type')
                            ->whereIn('invoice_no', $invoice_no)
                            ->whereIn('current_status',[3,4])
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
                                    ->join('item_types', 'item_types.id', '=', 'orders.item_type')
                                    ->where('invoice_no', $single_invoice)
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
    	      $information1['sender_name'] = $single_order->sender_name;
    	      $information1['sender_phone'] = $single_order->sender_phone;
    	      $information1['sender_address'] = $single_order->sender_address;
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

    // company wise order
    public function CompanyWiseOrder(Request $request){

        $login_id=$this->loginUser($request);
        $company = Driver::where('id',$login_id)->first();
        //dd($company->company_id);
        if($company->company_id != null){
            $company_id = $company->company_id;

            $company_array = json_decode($company_id);

            $final_order = DB::table('orders')
                                ->join('item_types', 'item_types.id', '=', 'orders.item_type')
                                ->whereIn('current_status',[1,2])
                                ->where('company_id', $company_array)
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
                $information1['sender_address'] = $all_order->sender_address;
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

    // order status change
    public function orderstatuschange(Request $request){

        $login_id=$this->loginUser($request);

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
    	$pre_data = DB::table('orders')->where('invoice_no',$invoice_no)->first();
        //dd($request->all());

    	$users = Driver::where('id',$data->personal_user_id)->first();
    	$company = $data->company_id;
    	$delivery_users = Driver::where('id',$login_id)->first();
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
                DB::table('driver_trackings')->insert(
                     array(
                            'driver_id' => $login_id,
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




                $this->SendSms($status,$pre_data->sender_phone,$invoice_no,$body);

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
                    $this->SendSms($status,$pre_data->sender_phone,$invoice_no,$body);

                    $body = "Your Parcel from ($users->username) will be delivered shortly. Invoice no #$invoice_no. Please share this code $otp with the delivery agent while receiving.";
                    $this->SendSms($status,$pre_data->recp_phone,$invoice_no,$body);


                }else{
                    $response = DB::table('orders')->where('invoice_no',$invoice_no)->update(array('current_status'=>$status,'collection_date'=>$Datebd));

                    //Sender sms
                    $body = "Your Parcel is on its way to the receiver.";
                    $this->SendSms($status,$pre_data->sender_phone,$invoice_no,$body);

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
                $this->SendSms($status,$pre_data->sender_phone,$invoice_no,$body);

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
                    $this->SendSms($status,$pre_data->sender_phone,$invoice_no,$body);

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

    // order history
    public function orderHistory(Request $request){

        $login_id=$this->loginUser($request);

        $results = DB::table('driver_trackings')
                            ->where('driver_trackings.driver_id',$login_id)
                            ->get();

        $invoice_no = [];
        foreach($results as $result){
            $invoice_no[] = $result->invoice_no;
        }
        $invoice_no = array_unique($invoice_no);

        $orders = DB::table('orders')
                            ->join('item_types', 'item_types.id', '=', 'orders.item_type')
                            ->whereIn('invoice_no', $invoice_no)
                            ->where('current_status', 5)
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
    	      $information1['sender_name'] = $single_order->sender_name;
    	      $information1['sender_phone'] = $single_order->sender_phone;
    	      $information1['sender_address'] = $single_order->sender_address;
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

    // driver user update
    public function deliveryUserUpdate(Request $request){

        $login_id=$this->loginUser($request);
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
            $users = Driver::where('id',$login_id)->update(array('username'=>$username ,'image'=>$photo_link));

        }elseif(isset($request->username)){

            $username = strip_tags($request->username);
            $users = Driver::where('id',$login_id)->update(array('username'=>$username));
        }elseif(isset($request->image)){
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();

            $photo_link = "https://api.parcelmagic.com/img/users/".$photo;

            $users = Driver::where('id',$login_id)->update(array('image'=>$photo_link));
            $photo  = date('Ymdhis').'.'.$request->file('image')->getClientOriginalExtension();
            $public_url = str_replace('/api/', '/', public_path());

            if($request->file('image')->move($public_url.'/img/users/', $photo))
            {
                $request['image'] = '/img/users/'.$photo;
                $user_photo = '/img/users/'.$photo;
            }

        }
        $users = Driver::select('id','username','image','phone')->where('id',$login_id)->first();
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

    // driver profile
    public function deliveryprofile(Request $request)
    {
        $login_id=$this->loginUser($request);

    	$response = Driver::where('id',$login_id)
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

    // delete Fcm token
    public function deleteFcmToken(Request $request){

        $login_id=$this->loginUser($request);

        $driver_check = DB::table('driver_infos')->where('id',$login_id)->update(['fcm_token_driver' => null]);

        return [
            'status' => 200,
            'success' => true,
            'msg' => 'Sucessfully logout',
        ];

    }


}
