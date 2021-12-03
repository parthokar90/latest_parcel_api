<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use DB;
use Geo;
use Session;

class AwsProductPayment extends Model
{
 
protected $table = "payment_pre_orders";

protected $fillable = ['id','invoice_no','discount', 'product_name', 'coupon_code', 'payment_type', 'buyer_id', 'from_country', 'to_country', 'price','transaction_fees','payment','total_price','total_transaction_fees','total_payment','gateway_id','gateway_payment_id','gateway_payer_id','gateway_logs','user_id','updated_by', 'shipping_status','processing_date','arrived_newyork_date','arrived_chicago_date','shipped_date','arrived_date','delivered_date','cancelled_date','published_date', 'manual_accept_type', 'manual_accept_date', 'manual_accept_by', 'manual_accept_note', 'comments','status','created_at', 'updated_at'];

protected $casts = [];

 public static function getCountryCode()
    {
        error_reporting(0);

        $key_access =  getenv('IPAPI_KEY');
        $key_access = explode(',', $key_access);


        if (!Session::has('getCountryCode')){

            $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36';
            $ip =\Request::ip()=='::1'?'103.110.96.154':\Request::ip();
            try {
                foreach ($key_access as $key) {
                    $url ='http://api.ipapi.com/'.$ip.'?access_key='.$key;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
                    //curl_setopt($ch, CURLOPT_NOBODY, true);
                    $response = curl_exec($ch);
                    curl_close ($ch);
                    $result = json_decode($response);

                    if($result && isset($result->country_code) && !is_null($result->country_code) && !empty($result->country_code)){
                        Session::put("getCountryCode", $result->country_code);
                        return $result->country_code;
                    }
                }         
            } catch (Exception $e) {
                
            }             
        }else{

            return Session::get("getCountryCode");
        }
    }

public static function getCurrencyCode(){
    
    $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');     
    //return $code = 'USD';

    if($code=='US'):
        return 'USD';
    elseif($code=='BD'):
        return 'BDT';
    elseif($code=='IN'):
        return 'INR';
    else:
        return 'USD';    
    endif; 
} 

public function manual_accept_user(){
    return $this->belongsTo('\App\User', 'manual_accept_by');
}

public static function tracking_status($current_status){

    $shipping_status = [
        '1' => [
            'name' => 'Processed'
        ],
       /* '7' => [
            'name' => 'Arrived at New York Off.'
        ],
        '8' => [
            'name' => 'Arrived at Chicago Off.'
        ],*/
        '2' => [
            'name' => 'Shipped'
        ],
        '3' => [
            'name' => 'Arrived'
        ],
        '4' => [
            'name' => 'Delivered'
        
        ]
        /*,
        '5' => [
            'name' => 'Cancelled'
        ],
        '6' => [
            'name' => 'Published'
        ],*/
    ];


  // pr($shipping_status);

   

   if($current_status==7 || $current_status==8 || $current_status==5 || $current_status==6){
    $current_status =1;
   }

   //echo $current_status=7;

    $out = '';
    foreach ($shipping_status as $status => $key) {
        $class = $status <= $current_status ? 'completed' : '';

            $step = $key['name'];
            $out .= '<li class="'.$class.'">';
                $out .= '<span class="bubble"></span>';
                $out .= $step;
            $out .= '</li>';
        
    }
    return $out;

    // <li class="completed">
    //     <span class="bubble"></span>
    //     Step 1.
    // </li>
    // <li class="completed">
    //     <span class="bubble"></span>
    //     Step 2.
    // </li>
    // <li class="completed">
    //     <span class="bubble"></span>
    //     Step 3.
    // </li>
    // <li class="">
    //     <span class="bubble"></span>
    //     Step 4.
    // </li>
    // <li>
    //     <span class="bubble"></span>
    //     Step 5.
    // </li>
}

public static $manual_accept_type_list =
    [
        '' => 'N/A',
        '1' => 'Cash Deposit',
        '2' => 'Bank Deposit BD',
        '7' => 'Bank Deposit US',
        '3' => 'Bkash',
        '4' => 'Rocket',
        '5' => 'Bd Smart',
        '6' => 'Other Internet Banking',
        '8' => 'Cash On Delivery (COD)',
    ];

public static function get_manual_accept_type_value_by_key($key){
    $manual_accept_list_array = self::$manual_accept_type_list;
    if(array_key_exists($key, $manual_accept_list_array)){
        return $manual_accept_list_array[$key];
    }
    return '';
}


public static function setAmount($price, $currency_code){
       
   $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
   $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');
  

    if($currency_code=='USD'):
        return $price/$con_rate_us_bd;
    elseif($currency_code=='BDT'):
        return $price;
    elseif($currency_code=='INR'):
        return $price/$con_rate_in_bd;
    else:
        return $price/$con_rate_us_bd;    
    endif; 
} 

public static function getCommissionType(){
	$type_commission = getenv('PRE_ORDER_TYPE_TRAVELER_COMMISSION');
	$avg_commission = getenv('PRE_ORDER_AVG_TRAVELER_COMMISSION');
	return $avg_commission .'% ';
}


public static function getPayableProductPrice($total, $currency='')
{
	
	//PERCENTAGE//FIXED
     $type_commission = getenv('PRE_ORDER_TYPE_TRAVELER_COMMISSION');
     $avg_commission = getenv('PRE_ORDER_AVG_TRAVELER_COMMISSION');
     $commission =0;
     $commission =($avg_commission/100)*$total;

    if($currency)
    $commission = self::setAmount($commission,$currency);

    return $commission;

}	

    

    public static function getAmount($price){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        $con_rate_percent = getenv('CONVERSION_RATE_PERCENT');
        $con_rate_bd = getenv('CONVERSION_RATE_BD');
        $con_rate_us = getenv('CONVERSION_RATE_US');
        $con_rate_in = getenv('CONVERSION_RATE_IN');

        //$code = 'US';

        $price = (float)substr($price, 0,strlen($price)-2);
        $display_price = $price+($con_rate_percent/100)*$price;

        if($code=='US'):
            return $display_price*$con_rate_us;
        elseif($code=='BD'):
            return $display_price*$con_rate_bd;
        elseif($code=='IN'):
            return $display_price*$con_rate_in;
        else:
            return $display_price*$con_rate_us;    
        endif; 
    }  


    public static function getFormattedPrice($CurrencyCode, $price){

        return $CurrencyCode.' '.number_format((float)$price,2);
    
    }


    public static function getSubTotalProductPrice($data){

       // pr($data,1);

        $total=0;
        foreach ($data as $key => $value) {
            $total+=$value->unit_price*$value->qty;
        }

        return $total;
    }

    public static function getTotalProductPrice($data){

    	$total=0;
        foreach ($data as $key => $value) {
        	$total+=$value->price;
        }

        return $total;
    }

    public static function getTotalProcessingCharge($total, $process_charge=0){

    	if($process_charge){
    		$charge =($process_charge/100)*$total;
   			return $charge;
    	}else{
    		return 0;
    	}
    }

    public static function getTotaliscount($invoice)
    {
        
         return \App\AwsProductPayment::where('invoice_no',$invoice)
             //->where('status',1)
             ->orderBy('id', 'desc')
             ->sum('discount');
    }

    public function user()
    {
        
        return $this->belongsTo('\App\User', 'user_id');
        
    }

    public function updatedby()
    {
        
        return $this->belongsTo('\App\User', 'updated_by');
        
    }
    
    public function buyer()
    {
        
        return $this->belongsTo('\App\Buyer', 'buyer_id');    
    }

            
    public function country_from()
    {
        
        return $this->belongsTo('\App\Country', 'from_country');   
    }
 
            
    public function country_to()
    {
        
        return $this->belongsTo('\App\Country', 'to_country');
        
    }
    
    
    public function gateway()
    {
        
        return $this->belongsTo('\App\Gateway');
        
    }

    public function scopeUnpaid($query)
    {
        
        return $query->where('status', 0);
        
    }


    public function scopeBkash($query){
        return $query->where('gateway_payer_id', 'bkash');
    }
    
    public function scopeVerified($query)
    {
        
        return $query->where('status', 1)->where('payment_type', 2);
        
    }


    public function scopeToday($query)
    {
        
        return $query->whereBetween('created_at', [ date('Y-m-d').' 00:00:00' , date('Y-m-d').' 23:59:59' ]);
        
    }
    
    
    public function scopeThisWeek($query)
    {
        
        return $query->whereBetween('created_at', [ \Carbon::now()->addDays(-7)->format('Y-m-d').' 00:00:00' , date('Y-m-d').' 23:59:59' ]);
        
    }

    
}