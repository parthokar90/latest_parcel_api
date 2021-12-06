<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class PublicController extends Controller
{
    //send OTP message
    public function SendOTPMessage(Request $request){

        $number = $request->phone_number;
        $otp = substr(rand(0,9999999999999),0,6);
        $body = "Your parcel magic verification code is ".$otp;
        $this->SendSms("",$number,"",$body);

        // $checkNumber = DB::table('otp_verification_logs')->where('phone',$number)->first();

        // $attempts = DB::table('otp_verification_logs')->where('attempts', 1); // get attempts, default: 0
        // dd($attempts);
        // $attemptsCount = DB::table('otp_verification_logs')->where('attempts', $attempts + 1); // increase attempts
        // dd($attempts);

        // if ($checkNumber==null) {
        //     $sendOTP = DB::table('otp_verification_logs')->insert(['otp_code' => $otp,'phone' => $number]);
        // } else {
        //     $sendOTP = DB::table('otp_verification_logs')->update(['otp_code' => $otp]);
        // }

	    return [
            'status' => 200,
            'success' => true,
            'msg' => 'Status Updated',
            'data' => ['token'=>$otp,'sender_number'=>$number]
        ];

    }
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


}
