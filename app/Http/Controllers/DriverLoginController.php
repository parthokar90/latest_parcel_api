<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Driver;
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

class DriverLoginController extends Controller
{

    // get logs
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

    //  driver phone number check
    public function driverphoneNumberCheck(Request $request){

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
        $users_app_check = Driver::
                            where('phone',$request->phone)
                            ->first();

        if($users_app_check != null){

        	$credentials = [
                'phone' => $request->phone,
                'password' => '123456789'
            ];

    	    try {
               if (auth()->guard('driver')->attempt($credentials)) {
                    $token = auth()->guard('driver')->user()->createToken('TrutsForWeb')->accessToken;
                    $this->getLogs($token);

    	            $users = Driver::select('id','phone','username','client_id','email','image')
                	                ->where('phone',$request->phone)
                	                ->first();

                                    Driver::where('phone',$request->phone)->update([
                                        'auth_access_token'=> 'Bearer'.' '. $token
                                      ]);

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

    // driver registration
    public function registerdelivery(Request $request){

        //return $request->all();
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100',
            'phone' => 'required|string|min:10|max:15:unique:users',
            'gender' => 'required',
            'date_of_birth' => 'required',
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
        $passport = $request->passport_number;
        $gender = $request->gender;
        $date_of_birth = $request->date_of_birth;
        $address = $request->address;
        $driver_type = $request->driver_type;
        $email = $request->email;
        $drive_lisence = $request->drive_lisence;
        $vehicle_registration_no = $request->vehicle_registration_no;

        $checkuserphone = Driver::select('*')->where('phone',$request->phone)->first();

        if ($checkuserphone != null) {
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'Already Register',
                    'data' => []
                ];
        } else {
            $user=new Driver;
            $user->username=$username;
            $user->password= bcrypt('123456789');
            $user->phone=$phone;
            $user->email=$email;
            $user->image=$uphoto;
            $user->role= 6;
            $user->active=1;
            $user->NID=$nid;
            $user->passport_number=$passport;
            $user->gender=$gender;
            $user->drive_lisence=$drive_lisence;
            $user->date_of_birth=$date_of_birth;
            $user->address=$address;
            $user->driver_type=$driver_type;
            $user->vehicle_registration_no=$vehicle_registration_no;
            $user->verified=0;
            $user->save();

        }
        $credentials = [
            'phone' => $request->phone,
            'password' => $password
        ];

        try {
           if (auth()->guard('driver')->attempt($credentials)) {
                $token = auth()->guard('driver')->user()->createToken('TrutsForWeb')->accessToken;
                $this->getLogs($token);
                $info = DB::table("driver_infos")
                            ->select('id','username','phone','client_id','email','image')
                            ->where('phone',$request->phone)
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
                    'msg' => 'User Unauthorized',
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


}
