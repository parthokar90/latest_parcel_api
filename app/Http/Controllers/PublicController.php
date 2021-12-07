<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class PublicController extends Controller
{
    //send OTP message
    public function SendOTPMessage(Request $request){

        $number = $request->phone_number;
        $otp = substr(rand(0,9999999999999),0,6);
        $body = "Your parcel magic verification code is ".$otp;

        $checkNumber = DB::table('otp_verification_logs')->where('phone',$number)->first();

        if ($checkNumber==null) {
            $sendOTP = DB::table('otp_verification_logs')->insert(['otp_code' => $otp,'phone' => $number]);
            $this->SendSms($number,$body);
        } else {
            if($checkNumber->attempts < 3) {
                $sendOTP = DB::table('otp_verification_logs')->where('phone',$number)->update(['otp_code' => $otp,'attempts' => $checkNumber->attempts + 1,'created_at' => Carbon::now()]);
                $this->SendSms($number,$body);
                return [
                    'status' => 200,
                    'success' => true,
                    'msg' => 'OTP code sent',
                ];
            } else {
                $checkNumber = DB::table('otp_verification_logs')->first();
                // dd($checkNumber->created_at);
                $currentTime = date('Y-m-d H:i:s');
                 //dd($currentTime);
                $create_time=Carbon::parse($checkNumber->created_at);
                $current_time=Carbon::parse($currentTime);

                $time_different=$create_time->diffInRealHours($current_time);
                $time = 2;

                if ($time_different >= $time) {
                    $sendOTP = DB::table('otp_verification_logs')->where('phone',$number)->update(['otp_code' => $otp,'attempts' => 1,'created_at' => Carbon::now()]);
                    $this->SendSms($number,$body);
                    return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'OTP code sent',
                    ];
                } else {
                    return [
                        'status' => 200,
                        'success' => false,
                        'msg' => 'Attempts Limit Cross.....Please try again after sometime',
                    ];
                }

            }
        }
    }

    // otp verification
    public function otpVerification(Request $request){

        $number = $request->phone;
        $otp = $request->otp_code;
        $checkNumber = DB::table('otp_verification_logs')->where('phone',$number)->first();
        if ($checkNumber==null) {
            return [
                'status' => 200,
                'success' => false,
                'msg' => 'Phone number not valid',
            ];
        } else {
            $checkOTP = DB::table('otp_verification_logs')->where('otp_code',$otp)->first();
            if ($checkOTP != null) {
                // dd($checkNumber->created_at);
                $currentTime = date('Y-m-d H:i:s');
                // dd($currentTime);
                $create_time=Carbon::parse($checkNumber->created_at);
                $current_time=Carbon::parse($currentTime);

                $time_different=$create_time->diffInMinutes($current_time);
                $time = 3;

                if ($time_different >= $time) {
                    return [
                        'status' => 200,
                        'success' => false,
                        'msg' => 'Expired Time...Please Try Again',
                    ];
                } else {
                    DB::table('otp_verification_logs')->where('phone', $number)->delete();

                    return [
                        'status' => 200,
                        'success' => true,
                        'msg' => 'OTP Verified',
                    ];
                }
            } else {
                return [
                    'status' => 200,
                    'success' => false,
                    'msg' => 'OTP not found',
                ];
            }
        }



    }

    // send sms
    public function SendSms($phone,$body){

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


}
