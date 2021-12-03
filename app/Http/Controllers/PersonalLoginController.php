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

class PersonalLoginController extends Controller
{

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
               if (auth()->guard('personal')->attempt($credentials)) {         
                 $token = auth()->guard('personal')->user()->createToken('TrutsForWeb')->accessToken;
                    $this->getLogs($token);
    	                $users = Personal::
    	                                select('phone','id','username','client_id','email','image')
                        	            ->where('phone',$request->phone)
                        	            ->first();


                                        DB::table('personal_infos')->where('phone',$request->phone)->update([
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





    //registration
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


            if (auth()->guard('personal')->attempt($credentials)) { 
                $token = auth()->guard('personal')->user()->createToken('TrutsForWeb')->accessToken;
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




}
