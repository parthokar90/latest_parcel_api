<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Geo;
use Session;

class Buyer extends Model
{

    protected $table = "buyers";
    
    protected $fillable = ['id', 'name', 'amazon_url', 'other_url','qty', 'price','unit_price','advance_price', 'image_url', 'country_id', 'city_id', 'user_id', 'category_id', 'p_weight', 'p_length', 'p_height', 'p_width','airposted_pickup_type','parcel_to_name','airposted_pickup_type','parcel_to_phone','parcel_to_address','message_to_traveler','deliver_date','remarks','traveler_note','product_id','is_deal','status', 'created_at', 'updated_at'];
    
    protected $casts = [
    ];

    public function offer(){
        return $this->hasMany('\App\Offer', 'buyers_id');
    }


    public function offer_s(){
        return $this->hasOne('\App\Offer', 'buyers_id');
    }


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


    
    public function country()
    {
        
        return $this->belongsTo('\App\Country');
        
    }
            
    public function city()
    {
        
        return $this->belongsTo('\App\City');
        
    }
            
    public function user()
    {
        
        return $this->belongsTo('\App\User');
        
    }


    public function buyer()
    {
        
        return $this->belongsTo('\App\AwsProductPayment','id','buyer_id');
        
    }


    
            
    public function category()
    {
        
        return $this->belongsTo('\App\Category');
        
    }


    public static function getbuyers($invoice)
    {
        
         return \App\AwsProductPayment::where('user_id', auth()->user()->id)
             ->where('invoice_no',$invoice)
             //->where('status',1)
             ->orderBy('id', 'desc')
             ->lists('buyer_id');
    }


    public static function getTotalProductPrice($buyers)
    {
        $price = Buyer::whereIn('id', $buyers)
             ->sum('price'); 
        return $price;
    }

    public static function getTotalDueProductPrice($total, $paid, $status)
    {
        if($status){
           return $total-$paid;
        }else{
            return $total;
        }
    }


    
    public function scopeToday($query)
    {
        
        return $query->whereBetween('created_at', [ date('Y-m-d').' 00:00:00' , date('Y-m-d').' 23:59:59' ]);
        
    }
    
    
    public function scopeThisWeek($query)
    {
        
        return $query->whereBetween('created_at', [ \Carbon::now()->addDays(-7)->format('Y-m-d').' 00:00:00' , date('Y-m-d').' 23:59:59' ]);
        
    }
    
    
    public function scopeLastMonth($query)
    {
        
        return $query->whereBetween('created_at', [ \Carbon::now()->addMonths(-1)->format('Y-m-d').' 00:00:00' , date('Y-m-d').' 23:59:59' ]);
        
    }

    
    public function get_from_country($id)
    {
       // echo $id;
        $countries = array();

       if($id){
            $countries = DB::table('countries')->where('id', $id)->first();
            return $countries->name;
            if($countries){
               return $countries->name;
            }
        }else{
            return;
        } 
    }


    public function createdAt($date)
    {
        
       return date_format($date,"F j, Y");
        
    }
      


     public function getDeliverDate($date)
    {
        $date= date_create($date);
        if($date=='0000-00-00'){
            $default_date = date('Y-m-d', strtotime("+15 days"));
            return date_format($default_date,"F j, Y");

        }else{

          return date_format($date,"F j, Y");  
        }
        
    }


    public function getDeliverTime($time)
    {   
        $time = date_create($time);
        return date_format($time,"g  i A"); 
        
    }
              


    public function getPickupFrom($airposted_pickup_typ, $app)
    {   
        if($airposted_pickup_typ==1):
            return $app->store_address;
        elseif($airposted_pickup_typ==2):
            return 'Airposted Parcel Delivery';
        elseif($airposted_pickup_typ==3):        
            return 'Traveler';
        else: 
            return $app->store_address;   
        endif;                   
    }

     /*************Price issues****************/
   
   

    public function getEstimatedTotal($price){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        
        $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
        $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');
    
        //$code = 'US';
        $price = (float)substr($price, 0,strlen($price)-2);
        //$display_price = $price;

        if($code=='US'):
            $currency_code='USD';
            $price = $price/$con_rate_us_bd;
        elseif($code=='BD'):
            $currency_code='BDT';
            //$price = $price;
        elseif($code=='IN'):
            $currency_code='INR';
            $price = $price/$con_rate_in_bd;
        else:
            $currency_code='USD';
            $price = $price/$con_rate_us_bd;   
        endif; 

        return $currency_code.' '.number_format((float)$price,2);   
    }



    public static function get_price_via_geo_loc($price){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        
        $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
        $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');
    
        //$code = 'US';
        //$price = (float)substr($price, 0,strlen($price)-2);
        //$display_price = $price;

        if($code=='US'):
            $currency_code='USD';
            $price = $price/$con_rate_us_bd;
        elseif($code=='BD'):
            $currency_code='BDT';
            //$price = $price;
        elseif($code=='IN'):
            $currency_code='INR';
            $price = $price/$con_rate_in_bd;
        else:
            $currency_code='USD';
            $price = $price/$con_rate_us_bd;   
        endif; 

        return $currency_code.' '.number_format((float)$price,2);   
    }





    public function getProductPrice($price=0, $qty=1){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        
        $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
        $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');
    
        //$code = 'US';
        //$price = (float)substr($price, 0,strlen($price)-2);
        //$display_price = $price;

        $price = (float)$price;
        
        if($code=='US'):
            $currency_code='USD';
            $price = ($price/$con_rate_us_bd)*$qty;
        elseif($code=='BD'):
            $currency_code='BDT';
            $price = $price*$qty;
        elseif($code=='IN'):
            $currency_code='INR';
            $price = ($price/$con_rate_in_bd)*$qty;
        else:
            $currency_code='USD';
            $price = ($price/$con_rate_us_bd)*$qty;   
        endif; 

        return $currency_code.' '.number_format((float)$price,2);   
    } 


    public static function requestProductPrice($price=0, $qty=1){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        
        $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
        $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');
    
        //$code = 'US';
        //$price = (float)substr($price, 0,strlen($price)-2);
        //$display_price = $price;

        if($code=='US'):
            $currency_code='USD';
            $price = $price*$qty;
        elseif($code=='BD'):
            $currency_code='BDT';
            //$price = $price*$qty;
            $price = ($price*$con_rate_us_bd)*$qty;
        elseif($code=='IN'):
            $currency_code='INR';
            $price = ($price*$con_rate_in_bd)*$qty;
        else:
            $currency_code='USD';
            $price = $price*$qty;   
        endif; 

        return $currency_code.' '.number_format((float)$price,2);   
    } 



    public function getInternationalDeliveryFee($price=0, $unit_price=1, $qty=1){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
        $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');
        //$code = 'US';
        $price = (float)substr($price, 0,strlen($price)-2);
        $unit_price = (float)substr($unit_price, 0,strlen($unit_price)-2);
        $price = $price-($unit_price*$qty);

        //$price = self::getTravelerEarn($price);

        if($code=='US'):
            $currency_code='USD';
            $price = ($price/$con_rate_us_bd);
        elseif($code=='BD'):
            $currency_code='BDT';
            $price = $price;
        elseif($code=='IN'):
            $currency_code='INR';
            $price = ($price/$con_rate_in_bd);
        else:
            $currency_code='USD';
            $price = ($price*$con_rate_us_bd);   
        endif; 

        return $currency_code.' '.number_format((float)$price,2);   
    } 



    public function getInternationalEarnFee($price=0, $unit_price=1, $qty=1){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
        $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');

        $traveler_bd_rate = getenv('CONVERSION_RATE_TRAVELER_BD');

        //$code = 'US';
        $price = (float)substr($price, 0,strlen($price)-2);
        $unit_price = (float)substr($unit_price, 0,strlen($unit_price)-2);
        $price = $price-($unit_price*$qty);

        $price = self::getTravelerEarn($price);

        if($code=='US'):
            $currency_code='USD';
            $price = ($price/$con_rate_us_bd); 
        elseif($code=='BD'):
            $currency_code='BDT';
            $price = ($price/$con_rate_us_bd)*$traveler_bd_rate;
        elseif($code=='IN'):
            $currency_code='INR';
            $price = ($price/$con_rate_in_bd);
        else:
            $currency_code='USD';
            $price = ($price*$con_rate_us_bd);   
        endif; 

        return $currency_code.' '.number_format((float)$price,2);   
    } 


     public static function getTravelerEarn($price)
    {   
        $traveler_earn = getenv('AVG_TRAVELER_COMMISSION_EARN');
        $commission = 0;

        if($traveler_earn): 
           return $commission = ($traveler_earn/100)*$price;
        else:
            return $price;
        endif;       
    }


    public static function getProductitemRequestStatus($status){ 

        if($status==0):
            return 'Pending';
        elseif($status==1):
            return 'Accepted';
        else:
            return 'Rejected';
        endif;
    }  

    public static function getProductitemRequestEditedby($id){ 

        $users = DB::table('users')->where('id', '=', $id)->first();
        if($users)
            return $users->name;
        return '';
    } 

     public function isCustomLink($custom_id){
       return DB::table('buyer_request_items')->where('id', '=', $custom_id)->count();

    }  

     public static function getParcelDeliveryFee($unit_price=0, $qty=1){
       
        $code = self::getCountryCode()?self::getCountryCode():getenv('DEFAULT_COUNTRY_CODE');
        $con_rate_us_bd = (float)getenv('CONVERSION_RATE_US_BD');
        $con_rate_in_bd = (float)getenv('CONVERSION_RATE_IN_BD');
        //$code = 'US';
        $price = $unit_price*$qty;
        //$price = self::getTravelerEarn($price);
        if($code=='US'):
            $currency_code='USD';
            $price = ($price/$con_rate_us_bd);
        elseif($code=='BD'):
            $currency_code='BDT';
            $price = $price;
        elseif($code=='IN'):
            $currency_code='INR';
            $price = ($price/$con_rate_in_bd);
        else:
            $currency_code='USD';
            $price = ($price*$con_rate_us_bd);   
        endif; 

        return $currency_code.' '.number_format((float)$price,2);   
    } 
    

}

        