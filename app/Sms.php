<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Session;
use DB;

class Sms extends Model
{
	public static $endpoint = 'http://bulksms.m2mbd.net/smsapi';
	public static $api_key = 'C20028415c1f1afcd61ca0.08017082';
	//public static $senderid = '8801847169884'; // NON Masking
	public static $senderid = 'Airposted'; // Masking


	public static $errors = [
    	"1002"	=> "Sender Id/Masking Not Found",
		"1003"	=> "API Not Found",
		"1004"	=> "SPAM Detected",
		"1005"	=> "Internal Error",
		"1006"	=> "Internal Error",
		"1007"	=> "Balance Insufficient",
		"1008"	=> "Message is empty",
		"1009"	=> "Message Type Not Set (text/unicode)",
		"1010"	=> "Invalid User & Password",
		"1011"	=> "Invalid User Id",
    ];

	public static function send($number, $body){

		if(is_array($number)){
			$number = implode('', $number);
		}
		
		$params = array(
	    "api_key" => self::$api_key,
	    "type" => "text",
	    "contacts" => self::getValidPhoneCode($number),
	    "senderid" => self::$senderid,
	    "msg" => rawurlencode($body),
		);

		$request_url = self::genarate_url($params);
		//dd($request_url);
		$obj = self::curl($request_url, 'obj');

		if(array_key_exists($obj, self::$errors)){
			return [
				'success' => false,
				'response' => $obj
			];
		}

		
		if (strpos($obj, 'SMS SUBMITTED') === false) {
		  return [
				'success' => false,
				'response' => $obj
			];
		}

		return [
			'success' => true,
			'response' => $obj
		];
		//dd($obj);
		//die();
		//return $obj;
		
    }



    public static function send_back($number, $body){

		if(is_array($number)){
			$number = implode('', $number);
		}


		
		if(Session::get('getCountryCode')=='BD'){


			$params = array(
		    "api_key" => self::$api_key,
		    "type" => "text",
		    "contacts" => self::getValidPhoneCode($number),
		    "senderid" => self::$senderid,
		    "msg" => rawurlencode($body),
			);

			$request_url = self::genarate_url($params);
			//dd($request_url);
			$obj = self::curl($request_url, 'obj');

			if(array_key_exists($obj, self::$errors)){
				return [
					'success' => false,
					'response' => $obj
				];
			}

			
			if (strpos($obj, 'SMS SUBMITTED') === false) {
			  return [
					'success' => false,
					'response' => $obj
				];
			}

			return [
				'success' => true,
				'response' => $obj
			];
			//dd($obj);
			//die();
			//return $obj;

		}else{

			$url = "https://api.twilio.com/2010-04-01/Accounts/AC949b394abbc2bd6008a02adf3ef8863f/SMS/Messages.json";
			$from = "+12053509695";
			$to =  self::getValidPhoneCode($number); //twilio trial verified number
			$id = "AC949b394abbc2bd6008a02adf3ef8863f";
			$token = "b8cf872030ae57923d2c754310adf6a0";
			$data = array (
			        'From' => $from,
			        'To' => $to,
			        'Body' => $body,
			    );

			$post = http_build_query($data);

			$x = curl_init($url );
			curl_setopt($x, CURLOPT_POST, true);
			curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($x, CURLOPT_USERPWD, "$id:$token");
			curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($x, CURLOPT_POSTFIELDS, $post);
			//var_dump($post);
			$obj = curl_exec($x);
			//var_dump($obj);
			curl_close($x);

			return [
				'success' => true,
			 	'response' => $obj
			];

		}
		
    }



    public static function getValidPhoneCode($number){

    	$code = Session::get('getCountryCode');
    	$countries   = DB::table('countries')->where('code',$code)->first();
    	if($countries){
    		if($countries->phone_code){

    			$phone_code = substr($countries->phone_code,1);

    			if (strpos($number, $countries->phone_code) !== false) {
				    return $number;
				}elseif (strpos($number, $phone_code) !== false) {
					return '+'.$number;
				
				}elseif (strpos($number, '+') !== false) {
					return $number;
				}else{
					if($number[0]==0)
					$number = substr($number,1);
					return $countries->phone_code.$number;
				}
    		}else{
    			return $number;
    		}
    	}else{
    			return $number;
    	}
    }

    


    public static function genarate_url($params){
		// Sort the parameters by key
		ksort($params);

		$pairs = array();

		foreach ($params as $key => $value) {
		    // array_push($pairs, rawurlencode($key)."=".rawurlencode($value));
		    array_push($pairs, $key."=". $value);
		}

		// Generate the canonical query
		$canonical_query_string = join("&", $pairs);

		// Generate the signed URL
		$request_url = self::$endpoint. '?' .$canonical_query_string;
		return $request_url;
    }

    public static function curl($url, $output_type='obj'){
    	$handle = curl_init();
    	// Set the url
		curl_setopt($handle, CURLOPT_URL, $url);
		// Set the result output to be a string.+
		curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		 
		$output = curl_exec($handle);
		$err = curl_error($handle);  //if you need
		if($err){
			die($err);
		}
		curl_close($handle);

		// dd($output);
		// $xml = simplexml_load_string($output);
		// if($output_type == 'json'){
		// 	return json_encode($xml);
		// } elseif($output_type == 'obj'){
		// 	$json = json_encode($xml);
		// 	return json_decode($json);
		// }
		
		return $output;
    }
}
