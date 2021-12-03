<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log; 
use DB;
use STDclass;
use Session;



class Notification extends Model
{

    protected $table = "notifications";
    
    protected $fillable = ['id', 'type', 'notification_from', 'notification_to', 'name', 'link', 'is_delivered', 'created_at', 'updated_at'];

    static protected $type = [
        // Payment
        '1' => 'payment_confirmed',
        '2' => 'payment_cencelled',
        // Request track
        '11' => 'manual_request_on_review',
        '12' => 'manual_request_approved',
        '13' => 'manual_request_cancelled',
        // Traveler
        '21' => 'traveler_accepted',
        // Track
        '31' => 'track_processed',
        '32' => 'track_shipped',
        '33' => 'track_arrived',
        '34' => 'track_delivered',
        '35' => 'track_cancelled',
        '36' => 'track_published',
    ];

    static protected $from = [
        '1' => 'Airposted Admin',
        '2' => 'Airposted Notification'
    ];


    public static function getCountryCode()
    {
        error_reporting(0);

         $key_access = array("2972953c871aeecd376bf6a6ea5e6954","9cb9756ae5b9250bc85fc98df8e146fd","e5657c0b3d1ca89b576b9a046d4b6a38","e7de8fa6a166c06bb5e18e3148489bba","ae7f52cedc03837439c2e277f726402a","684ca484a828b2529dc98f1ae64035e0","e03d8f501121679cefaeb8ec76e5ee68","8b868c135f48434b3347363014e3b62e","96e1410197759663bdab607935a1164e","a30726752c1c816b3af1118726acf941");


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



    public static function get_from_name_by_key($key){
        if(array_key_exists($key, self::$from)){
            return self::$from[$key];
        }
        return '';
    }



    public static function add_to_profile_notification($type_name, $link_id, $notification, $from_user_id=null, $to_user_id=null){
        $type_key = array_search($type_name, self::$type);
        //dd($type_key);
        // dd($to_user_id ? $to_user_id : auth()->user()->id);

        if(!auth()->guest()){

            self::create([
                'notification_from' => $from_user_id ? $from_user_id : 1,
                'notification_to' => $to_user_id ? $to_user_id : auth()->user()->id,
                'name' => $notification,
                'type' => $type_key,
                'link' => $link_id
            ]);
            return true;

        }

        
    }


    // payment confirmed
    public static function parcels_notification($invoice_no){
        $notification = "Your parcel tracking #{$invoice_no} has been placed successfully.";
        self::add_to_profile_notification($type='post_confirmed', $invoice_no, $notification);
    }


    // payment confirmed
    public static function payment_confirmed_profile_notification($invoice_no){
        $notification = "Your order #{$invoice_no} has been placed successfully.";
        self::add_to_profile_notification($type='payment_confirmed', $invoice_no, $notification);
    }

    // payment cancelled
    public static function payment_cencelled_profile_notification($invoice_no){
        $notification = "Your order #{$invoice_no} has been cancelled.";
        self::add_to_profile_notification($type='payment_cencelled', $invoice_no, $notification);
    }

    // Manual Request On Review
    public static function manual_request_on_review_profile_notification($request_item){
        $notification = "Your request is under review, you will be notified within the next 24 hours. Your requested url {$request_item->customer_url}";
        self::add_to_profile_notification($type='manual_request_on_review', $request_item->id, $notification);
    }

    public static function manual_request_approved_or_cancelled_profile_notification($request_item, $user){
        if($request_item->status==2){
            // cancelled
            self::manual_request_cancelled_profile_notification($request_item, $user);
        } elseif($request_item->status==1){
            // approved
            self::manual_request_approved_profile_notification($request_item, $user);
        }
    }

    // Manual Request Approved
    public static function manual_request_approved_profile_notification($request_item, $user){
        $notification = "Your requested product {$request_item->product_title} is available at your Airposted account.";
        self::add_to_profile_notification($type='manual_request_approved', $request_item->id, $notification, null, $user->id);
    }

    // Manual Request Cancelled
    public static function manual_request_cancelled_profile_notification($request_item, $user){
        $notification = "Sorry! We unable to deliver your requested product.";
        self::add_to_profile_notification($type='manual_request_cancelled', $request_item->id, $notification, null, $user->id);
    }

    // Traveler Accepted
    public static function traveler_accepted_profile_notification($offer_id){
        $notification = "You successfully accepted the order request.";
        self::add_to_profile_notification($type='traveler_accepted', $offer_id, $notification);
    }




    // track_processed
    public static function track_processed_profile_notification($user_id, $invoice_no, $payment_id){
        $notification = "Your order #{$invoice_no} has been processed.";
        self::add_to_profile_notification($type='track_processed', $payment_id, $notification, null, $user_id);
    }

    // track_shipped
    public static function track_shipped_profile_notification($user_id, $invoice_no, $payment_id){
        $notification = "Your order #{$invoice_no} has been shipped.";
        self::add_to_profile_notification($type='track_shipped', $payment_id, $notification, null, $user_id);
    }

    // track_arrived
    public static function track_arrived_profile_notification($user_id, $invoice_no, $payment_id){
        $notification = "Your order #{$invoice_no} has been arrived.";
        self::add_to_profile_notification($type='track_arrived', $payment_id, $notification, null, $user_id);
    }

    // track_delivered
    public static function track_delivered_profile_notification($user_id, $invoice_no, $payment_id){
        $notification = "Your order #{$invoice_no} has been delivered.";
        self::add_to_profile_notification($type='track_delivered', $payment_id, $notification, null, $user_id);
    }

    // track_delivered
    public static function track_cancelled_profile_notification($user_id, $invoice_no, $payment_id){
        $notification = "Your order #{$invoice_no} has been cancelled by admin.";
        self::add_to_profile_notification($type='track_cancelled', $payment_id, $notification, null, $user_id);
    }

    // track_delivered
    public static function track_published_profile_notification($user_id, $invoice_no, $payment_id){
        $notification = "Your order #{$invoice_no} has been published by admin.";
        self::add_to_profile_notification($type='track_published', $payment_id, $notification, null, $user_id);
    }



    protected $casts = [
    ];

    public static function user_unseen_notify_count(){
        return self::unSeen()->count();
    }
    
    
    public function from()
    {
        
        return $this->belongsTo('\App\User', 'notification_from');
        
    }
    
    
    public function to()
    {
        
        return $this->belongsTo('\App\User', 'notification_to');
        
    }
    
            
    public function scopeDelivered($query)
    {
        
        return $query->where('is_delivered', 1);
        
    }
    
    
    
    public function scopeUnDelivered($query)
    {
        
        return $query->where('is_delivered', 0)->where('notification_from', auth()->user()->id);
        
    }

    public function scopeUnSeen($query)
    {
        return $query->where('is_delivered', 0)->where('notification_to', auth()->user()->id);
        
    }
    
    
    
    public function scopeSent($query)
    {
        
        return $query->where('notification_from', auth()->user()->id);
        
    }
    
    
    
    public function scopeReceived($query)
    {
        
        return $query->where('notification_to', auth()->user()->id);
        
    }
    
    
            
    public static function boot()
    {
        
        parent::boot();
        
        static::created(function($model)
        {
            
            ( new \App\Http\Controllers\Mails )->notification( $model->id );
            
        });
        
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


    public static function createdAt($date)
    {
         $date = date_create($date);
      

       return date_format($date,"F j, Y");
        
    }
            
    
    

}

        