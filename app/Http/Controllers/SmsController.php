<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log; 
use DB;
use App\User;
use App\Sms;
use App\Notification;
use STDclass;
use Session;

class SmsController extends Controller
{

    public $endings = ' For any query contact +8809639467678';

    public function checksms()
    {   
        $number = '01678200315';
        $body = 'Hellow From Aiposted 01678200315';
        $send = \App\Sms::send($number, $body);
        return $send;
    }

    public function sendOrderDetailsToUser($user_id, $invoice_id){
        // $order_products = \App\AwsProductPayment::where('invoice_no', $invoice_id)->get();
        $user = \App\User::find($user_id);    
        if($user && $user->contact){
            //dd($user);
            $body = 'Your order has been placed successfully. Your order/invoice id is ' . $invoice_id . '.' . $this->endings;
            $send = \App\Sms::send($user->contact, $body);
        }
    }

    public function sendOrderFailedToUser($user_id){
        // $order_products = \App\AwsProductPayment::where('invoice_no', $invoice_id)->get();
        $user = \App\User::find($user_id);    
        if($user && $user->contact){
            //dd($user);
            $body = 'Your order has been cancelled.' . $this->endings;
            $send = \App\Sms::send($user->contact, $body);
        }
    }

    

    public function sendAppLink(){
        $contact_number = request('contact_number');
        if($contact_number){
            $body = 'Airposted app link is https://play.google.com/store/apps/details?id=com.airposted.' . $this->endings;
            $send = \App\Sms::send($contact_number, $body);
        }
        return back()->with('app-message','Success');
    }

    // tracking
    public function order_processed_sms_to_shopper($user_id, $invoice_no){
        $user = \App\User::find($user_id);
        if($user && $user->contact){
            //dd($user);
            $body = "Your order #{$invoice_no} has been processed." . $this->endings;
            $send = \App\Sms::send($user->contact, $body);
        }
    }
    public function order_shipped_sms_to_shopper($user_id, $invoice_no){
        $user = \App\User::find($user_id);
        if($user && $user->contact){
            //dd($user);
            $body = "Your order #{$invoice_no} has been shipped." . $this->endings;
            $send = \App\Sms::send($user->contact, $body);
        }
    }
    public function order_arrived_sms_to_shopper($user_id, $invoice_no){
        $user = \App\User::find($user_id);
        if($user && $user->contact){
            //dd($user);
            $body = "Your order #{$invoice_no} has been arrived." . $this->endings;
            $send = \App\Sms::send($user->contact, $body);
        }
    }
    public function order_delivered_sms_to_shopper($user_id, $invoice_no){
        $user = \App\User::find($user_id);
        if($user && $user->contact){
            //dd($user);
            $body = "Your order #{$invoice_no} has been delivered." . $this->endings;
            $send = \App\Sms::send($user->contact, $body);
        }
    }


     public function parcel_proceed_sms_to_shopper($user_id, $invoice_no){
        $user = \App\User::find($user_id);
        if($user && $user->contact){
            //dd($user);
            $body = "Your parcel tracking #{$invoice_no} has been confirmed." . $this->endings;;
            $send = \App\Sms::send($user->contact, $body);
        }
    }



    public function order_published_sms_to_shopper($user_id, $invoice_no){
        $user = \App\User::find($user_id);
        if($user && $user->contact){
            //dd($user);
            $body = "Your order #{$invoice_no} has been confirmed." . $this->endings;;
            $send = \App\Sms::send($user->contact, $body);
        }
    }

    public function traveler_accepted_order_sms_to_traveler($contact){
        if($contact){
            //dd($user);
            $body = "You successfully accepted the order request." . $this->endings;
            $send = \App\Sms::send($contact, $body);
        }
    }


    public function order_cancelled_sms_to_shopper($user_id, $invoice_no){
        $user = \App\User::find($user_id);
        if($user && $user->contact){
            //dd($user);
            $body = "Your order #{$invoice_no} has been cancelled." . $this->endings;
            $send = \App\Sms::send($user->contact, $body);
        }
    }

    public function manual_product_request_on_review_sms_to_shopper($buyer_request_data){
        if($buyer_request_data && $buyer_request_data->customer_phone){
            //dd($user);
            if($buyer_request_data->customer_phone){
                $body = "Your request is under review, you will be notified within the next 24 hours. Your requested url \"{$buyer_request_data->customer_url}\"." . $this->endings;
                $send = \App\Sms::send($buyer_request_data->customer_phone, $body);
            }
        }
    }


    public function manual_request_accept_or_decline_sms_to_shopper($request_item)
    {   
        if($request_item->customer_phone){
            if($request_item->status==2){
                // cancelled
                $body = "Sorry! We unable to deliver your requested product \"{$request_item->customer_url}\"." . $this->endings;

                $send = \App\Sms::send($request_item->customer_phone, $body);
            } elseif($request_item->status==1){
                // approved
                $body = "Your requested product \"{$request_item->product_title}\" is available at your Airposted account. Check your email and follow the link for add to cart/notifications." . $this->endings;
                $send = \App\Sms::send($request_item->customer_phone, $body);
            }
        }
    }


    public function sendRefLink($data){

        $contact_number = $data['contact'];
        if($contact_number){
          $body = "Airposted Invite Friend link is ". url('/invitation/'.base64_encode(auth()->user()->id)). $this->endings;  
          $send = \App\Sms::send($contact_number, $body);
        }
    }
}
