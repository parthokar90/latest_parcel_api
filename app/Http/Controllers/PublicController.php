<?php

namespace App\Http\Controllers;

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
