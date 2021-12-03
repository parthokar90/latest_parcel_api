<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use App\Sms;
use App\Notification;
use Mail;
use Log; 
use DB;
use STDclass;
use Session;

class Mails extends Controller
{
    public function test(){
        // return view('mails.pages.invoice');
        // return view('mails.pages.after-order-success');
        // return view('mails.pages.after-sign-up');
        Mail::send('mails.pages.test', [], function ($message){
            $message->to('roddev.radiantiptv@gmail.com');
            $message->from( env('MAIL_INFO'), 'Airposted Customer Service');
            $message->subject('Welcome to Airposted');
        });

    }

    // order_processed_email_to_shopper 
    // order_shipped_email_to_shopper    
    // order_delivered_email_to_shopper 


    public function forgotPassword($id, $new_password)
    {
        
        if($user = User::find($id)){
            
            
            // Mail::send('mails.forgotPassword', ['user' => $user, 'new_password'=>$new_password], function ($m) use ($user) {
            //     $m->to($user->email, $user->firstname." ".$user->lastname)
            //       ->subject('Password Recovery')
            //       ->from( env('MAIL_INFO') , 'Airposted recovery system')
            //       ;
            // });

            Mail::send('mails.pages.forgot-password-email-to-user', ['user' => $user, 'new_password'=>$new_password], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Password Recovery')
                  ->from( env('MAIL_INFO') , 'Airposted recovery system');
            });
            
        }
        
    }


    public function order_processed_email_to_shopper($user_id, $invoice_no)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.order-processed-email-to-shopper', ['user'=>$user, 'invoice_no' => $invoice_no], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your order has been processed');
                });
            }

    }


    public function parcel_processed_email_to_shopper($user_id, $invoice_no)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.parcel-proceed-shipped-email-to-shopper', ['user'=>$user, 'invoice_no' => $invoice_no], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your parcel has been processed');
                });
            }

    }


    



    public function manual_product_request_on_review_email_to_shopper($buyer_request_data)
    {
        if($buyer_request_data->customer_email)
            {
                Mail::send('mails.pages.manual-product-request-on-review', ['buyer_request_data'=>$buyer_request_data], function ($message) use ($buyer_request_data) {
                $message->to($buyer_request_data->customer_email, $buyer_request_data->customer_name);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your requested product is on review');
                });
            }

    }


    public function order_shipped_email_to_shopper($user_id, $invoice_no)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.order-shipped-email-to-shopper', ['user'=>$user, 'invoice_no' => $invoice_no], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your order has been shipped');
                });
            }
    }

    public function order_arrived_email_to_shopper($user_id, $invoice_no)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.order-arrived-email-to-shopper', ['user'=>$user, 'invoice_no' => $invoice_no], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your order has been arrived');
                });
            }
    }

    public function order_delivered_email_to_shopper($user_id, $invoice_no)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.order-delivered-email-to-shopper', ['user'=>$user, 'invoice_no' => $invoice_no], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your order has been delivered');
                });
            }
    }


    public function order_cancelled_sms_to_shopper($user_id, $invoice_no)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.order-delivered-email-to-shopper', ['user'=>$user, 'invoice_no' => $invoice_no], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your order has been cancelled');
                });
            }
    }


    public function order_published_sms_to_shopper($user_id, $invoice_no)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.order-delivered-email-to-shopper', ['user'=>$user, 'invoice_no' => $invoice_no], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Your order has been published');
                });
            }
    }


    public function after_traveler_accept_order_and_send_email_to_traveler($user, $buyers, $offers)
    {
        //dd('comes');
        //$payment = \App\AwsProductPayment::where('buyer_id', $buyer_id)->first();
        // dd($payment);
        //$buyer_id = $payment->buyer->id;
        //$offer_id = \App\Offer::where('buyers_id', $buyer_id)->latest()->first();
        //$user = User::find($user_id);
        if($user && $buyers && $offers)
            {
                //dd('comes');
                Mail::send('mails.pages.after-traveler-accept-order-email-to-traveler', ['buyers'=>$buyers, 'offers' => $offers, 'user'=>$user], function ($message) use ($user) {
                $message->to($user->email, 'Airposted Notification');
                // $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Thank you for accepting the product request');
                });
            }

    }

    public function after_traveler_accept_notify_to_sales_team($buyer_id)
    {   
        $payment = DB::table('payment_pre_orders')->where('buyer_id', $buyer_id)->first();
        // dd($payment);
        $buyer_id = $payment->buyer->id;
        // $offer_id = \App\Offer::where('buyers_id', $buyer_id)->latest()->first();
        $offer_id =  DB::table('offers')->where('buyers_id', $buyer_id)->latest()->first();
        // pr($payment);
        // pr($buyer_id);
        // dd($offer_id);

        if($payment && $offer_id){
            Mail::send('mails.pages.after-traveler-accept-notify-to-sales-team', ['payment'=>$payment, 'offer_id' => $offer_id], function ($message) use ($payment, $offer_id) {
                $message->to('sales@airposted.com', 'Airposted Notification');
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('A traveler accepted a order');
            });
        }
        
    }

    

    


    public function after_add_trip_traveler($user_id){
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.after-add-trip-traveler', ['user'=>$user], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Carry a product, make delivery and start earning');
                });
            }
    }


    public function after_mobile_verified_send($user_id)
    {
        if($user = User::find($user_id))
            {
                Mail::send('mails.pages.after-mobile-verified', ['user'=>$user], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Verified Airposted member!');
                });
            }

    }

    public function signup($id)
    {
        if($user = User::find($id))
            {
                Mail::send('mails.pages.after-sign-up', ['user'=>$user], function ($message) use ($user) {
                $message->to($user->email, $user->firstname.$user->lastname);
                $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
                $message->subject('Buckle up for a fresh new start!');
                
                });
            }

    }
    
    // public function signup($id)
    // {//return $id;
        
    //     if($user = User::find($id))
    //     {//return $user;
    //         switch($user->role)
    //         { 
                
    //             case 1:
    //                     Mail::send('mails.clientSignup', ['user'=>$user], function ($message) use ($user) {
    //                         $message->to($user->email, $user->firstname.$user->lastname);
    //                 	    $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
    //                 	    $message->subject('Welcome to Airposted');
                    	    
    //                 	});
                    	
                    	
    //                 	if($user->referrer_id)
    //                 	{
                    	    
    //                 	    if(User::where('id',$user->referrer_id)->first())
    //                 	    {
    //                 	        $referrer = User::where('id',$user->referrer_id)->first();
                    	        
    //                 	        Mail::send('mails.clientSignupInfoToReferrer', ['user'=>$user, 'referrer'=>$referrer], function ($message) use ($user,$referrer) {
    //                                 $message->to($referrer->email, $referrer->firstname.$referrer->lastname);
    //                         	    $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
    //                         	    $message->subject('You have a new client at Airposted (Bangladesh)');
                            	    
                            	    
    //                         	});
                            	
                    	        
    //                 	    }
                    	    
    //                 	}
                    	
    //                     break;
    //             case 2:
    //                 break;
    //             case 3:
    //                 Mail::send('mails.clientSignup', ['user'=>$user], function ($message) use ($user) {
    //                     $message->to($user->email, $user->firstname.$user->lastname);
    //             	    $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
    //             	    $message->subject('Welcome to Airposted');
                	    
                	    
    //             	});
                    
    //                 Mail::send('mails.clientSignupToAdmin', ['user'=>$user], function ($message) use ($user) {
    //                     $message->to( env('MAIL_INFO') , 'Admin of Airposted' );
    //             	    $message->from( env('MAIL_INFO') , 'Airposted Notification System');
    //             	    $message->subject('New Client has signed up at Airposted');
                	    
                	    
    //             	});
    //                 break;
    //             case 4:
    //                     Mail::send('mails.clientSignup', ['user'=>$user], function ($message) use ($user) {
    //                         $message->to($user->email, $user->firstname.$user->lastname);
    //                 	    $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
    //                 	    $message->subject('Welcome to Airposted');
                    	    
                    	    
    //                 	});
                    	
                    	
    //                 	if($user->referrer_id)
    //                 	{
                    	    
    //                 	    if(User::where('id',$user->referrer_id)->first())
    //                 	    {
    //                 	        $referrer = User::where('id',$user->referrer_id)->first();
                    	        
    //                 	        Mail::send('mails.clientSignupInfoToReferrer', ['user'=>$user, 'referrer'=>$referrer], function ($message) use ($user,$referrer) {
    //                                 $message->to($referrer->email, $referrer->firstname.$referrer->lastname);
    //                         	    $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
    //                         	    $message->subject('You have a new client at Airposted (Bangladesh)');
                            	    
                            	    
    //                         	});
                            	
                    	        
    //                 	    }
                    	    
    //                 	}
                    	
    //                 break;
    //             case 6:
                        
                    	
                    	
    //                 break;
    //             case 5:
                    
    //                 break;
    //             default:
    //                 break;
                
    //         }
            
            
    //     }
        
    // }
    // -------------RODRO COUPON --------------//
    public function userHaveCouponSend($schedule)
    {
        //dd($schedule->message_title);
        if($schedule->user){
            //dd('hav user');
            Mail::send('mails.coupon.user-have-coupon', ['schedule' => $schedule], function ($m) use ($schedule) {
                $m->to($schedule->user->email, $schedule->user->firstname." ". $schedule->user->lastname)
                  ->subject($schedule->coupon->coupon_title)
                  ->from( 'info@airposted.com' , 'Airposted Coupon');
            });
            
        }
        
    }


    public function userEmailVerifyTokenSend($user_id, $email_token)
    {
        //dd($schedule->message_title);
        $user = \App\User::find($user_id);
        if($user){
            //dd('hav user');
            Mail::send('mails.pages.email-verify-mail', ['user' => $user, 'email_token' => $email_token], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ". $user->lastname)
                  ->subject('Welcome to Airposted! Please confirm your email address')
                  ->from( 'info@airposted.com' , 'Airposted Email Verify');
            });
            
        }
        
    }


    // public function userEmailVerifyTokenSend($user_id, $email_token)
    // {
    //     //dd($schedule->message_title);
    //     $user = \App\User::find($user_id);
    //     if($user){
    //         //dd('hav user');
    //         Mail::send('mails.verification.user-email-verification-token', ['user' => $user, 'email_token' => $email_token], function ($m) use ($user) {
    //             $m->to($user->email, $user->firstname." ". $user->lastname)
    //               ->subject('Verify your email address at Airposted (BD)')
    //               ->from( 'info@airposted.com' , 'Airposted Email Verify');
    //         });
            
    //     }
        
    // }


    
    
    public function accountActivation($id)
    {
        
        $user = User::where('id',$id)->first();
        
        if($user)
        {
            
            Mail::send('mails.clientAccountActivationConfirmation', ['user'=>$user], function ($message) use ($user) {
                $message->to($user->email, $user->firstname." ".$user->lastname);
        	    $message->from( env('MAIL_INFO') , 'Airposted Customer Service');
        	    $message->subject('Your account has been activated at Airposted (BD)');
        	    $message->bcc('ashique19@gmail.com', 'A3');
        	    
        	});
            
        }
        
    }
    
    
    
    
    public function buyWishPosted()
    {
        
        $user   = User::find(auth()->user()->id);
        
        if($user){
            
            Mail::send('mails.buy-wish-posted', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Your buy wish has been posted at Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function travelWishPosted()
    {
        
        $user   = User::find(auth()->user()->id);
        
        if($user){
            
            Mail::send('mails.travel-wish-posted', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Your travel detail has been posted at Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function buyerPaidForProductAcknowledgement()
    {
        
        $user   = User::find(auth()->user()->id);
        
        if($user){
            
            Mail::send('mails.buyer-paid-for-product-acknowledgement', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Your payment has been successfully recorded at Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function buyerPaidNotifyTraveler($traveler_id)
    {
        
        $user   = User::find($traveler_id);
        
        if($user){
            
            Mail::send('mails.buyer-paid-for-product-acknowledgement', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted received payment for your offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function buyerPaidNotifyAdmin($payment)
    {
        
        
        if(auth()->user()){
            
            Mail::send('mails.buyer-paid-notify-admin', ['payment' => $payment], function ($m) use ($payment) {
                $m->to('codebar2007@gmail.com', "Mithun Molla" )
                  ->subject('Airposted received a new payment')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ->bcc( 'ashique19@gmail.com' , 'Mail Delivery System');
            });
            
        }
        
    }
    
    
    public function buyerReceivedProduct($buyer_id)
    {
        
        $user   = User::find($buyer_id);
        
        if($user){
            
            Mail::send('mails.buyer-received-product', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Congratulations on a successful shipping with Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function buyerReceivedProductNotifyTraveler($traveler_id)
    {
        
        $user   = User::find($traveler_id);
        
        if($user){
            
            Mail::send('mails.buyer-received-product-notify-traveler', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted buyer received the product')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function buyerReceivedProductNotifyAdmin($traveler_id, $buyer_id, $payment)
    {
        
        $user   = User::find($traveler_id)->toArray();
        
        $buyer  = User::find($buyer_id)->toArray();
        
        $payment= collect($payment);
        
        $payment= (array) $payment->toArray();
        
        if($user){
            
            Mail::send('mails.buyer-received-product-notify-traveler', ['traveler' => $user, 'buyer' => $buyer, 'payment'=> $payment], function ($m) use ($user) {
                $m->to( env('MAIL_ADMIN') , 'Admin of Airposted' )
                  ->subject('Congratulations on a successful transaction with Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function travelerDeliveredProduct($traveler_id)
    {
        
        $user   = User::find($traveler_id);
        
        if($user){
            
            Mail::send('mails.traveler-delivered-product', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Congratulations on a successful transaction with Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function travelerDeliveredProductNotifyBuyer($buyer_id)
    {
        
        $user   = User::find($buyer_id);
        
        if($user){
            
            Mail::send('mails.traveler-delivered-product-notify-buyer', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Your Airposted traveler delivered your product')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function travelerDeliveredProductNotifyAdmin($traveler_id, $buyer_id, $payment)
    {
        
        $user   = User::find($traveler_id)->toArray();
        
        $buyer  = User::find($buyer_id)->toArray();
        
        $payment= collect($payment);
        
        $payment= (array) $payment->toArray();
        
        if($user){
            
            Mail::send('mails.traveler-delivered-product-notify-admin', ['traveler' => $user, 'buyer' => $buyer, 'payment'=> $payment], function ($m) use ($user) {
                $m->to( env('MAIL_ADMIN') , 'Admin of Airposted' )
                  ->subject('Traveler sais, Product delivered to Buyer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function offerMadeByTraveller($receiver_id, $sender_id)
    {
        
        $user   = User::find($receiver_id);
        
        $sender = User::find($sender_id);
        
        if($user){
            
            Mail::send('mails.offer-made-by-traveller', ['user' => $user, 'sender'=> $sender], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('You received a delivery offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function offerMadeByBuyer($receiver_id, $sender_id, $offer_id, $buypost_id)
    {
        
        $users = new User;
        
        //$offer = \App\Offer::find( $offer_id );

        $offer = DB::table('offers')->where('id',$offer_id)->get();
        
        //$buypost = \App\Buyer::find( $buypost_id );

        $buypost = DB::table('buyers')->where('id',$buypost_id)->get();
        
        /**
         * 
         * Acknowledge Traveler that Buyer sent an offer
         * 
        */
        $user   = $users->find($receiver_id);
        
        $sender = $users->find($sender_id);
        
        if($user){
            
            
            Mail::send('mails.offer-made-by-buyer', ['user' => $user, 'sender'=> $sender, 'offer'=> $offer, 'buypost'=> $buypost], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Your request has been sent to the traveler')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
        
        /**
         * 
         * Acknowledge Buyer that his/her offer has been sent to traveler
         * 
        */
        $re_user    = $sender;
        
        $re_sender  = $user;
        
        if($re_user){
            
            
            Mail::send('mails.offer-made-by-buyer-acknowledged', ['user' => $re_user, 'sender'=> $re_sender], function ($m) use ($re_user) {
                $m->to($re_user->email, $re_user->firstname." ".$re_user->lastname)
                  ->subject('Your offer has been sent to the Traveler')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function offerReplyByBuyer($receiver_id, $sender_id)
    {
        
        $users = new User;
        
        /**
         * 
         * Acknowledge Traveler that Buyer sent an offer
         * 
        */
        $user   = $users->find($receiver_id);
        
        $sender = $users->find($sender_id);
        
        if($user){
            
            
            Mail::send('mails.offer-reply-by-buyer', ['user' => $user, 'sender'=> $sender], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted buyer made you an offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }

        
    }
    
    
    public function offerReplyByTraveller($receiver_id, $sender_id)
    {
        
        $users = new User;
        
        /**
         * 
         * Acknowledge Traveler that Buyer sent an offer
         * 
        */
        $user   = $users->find($receiver_id);
        
        $sender = $users->find($sender_id);
        
        if($user){
            
            
            Mail::send('mails.offer-reply-by-traveler', ['user' => $user, 'sender'=> $sender], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted buyer made you an offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }

        
    }
    
    
    public function offerRejectedByBuyer($receiver_id, $sender_id)
    {
        
        $users = new User;
        
        /**
         * 
         * Acknowledge Traveler that Buyer sent an offer
         * 
        */
        $user   = $users->find($receiver_id);
        
        $sender = $users->find($sender_id);
        
        if($user){
            
            
            Mail::send('mails.offer-rejected-by-buyer', ['user' => $user, 'sender'=> $sender], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted buyer rejected your offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }

        
    }
    
    
    public function offerRejectedByTraveler($receiver_id, $sender_id)
    {
        
        $users = new User;
        
        /**
         * 
         * Acknowledge Traveler that Buyer sent an offer
         * 
        */
        $user   = $users->find($receiver_id);
        
        $sender = $users->find($sender_id);
        
        if($user){
            
            
            Mail::send('mails.offer-rejected-by-traveler', ['user' => $user, 'sender'=> $sender], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted traveler rejected your offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }

        
    }
    
    
    public function offerAcceptedByTraveller($receiver_id, $sender_id)
    {
        
        $user   = User::find($sender_id);
        
        $sender = User::find($receiver_id);
        
        if($user){
            
            
            Mail::send('mails.offer-accepted-by-traveller', ['user' => $user, 'sender'=> $sender], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Thank you for accepting the offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
        $re_user   = $sender;
        
        $re_sender = $user;
        
        if($re_user){
            
            
            Mail::send('mails.offer-accepted-by-traveller-notify-buyer', ['user' => $re_user, 'sender'=> $re_sender], function ($m) use ($re_user) {
                $m->to($re_user->email, $re_user->firstname." ".$re_user->lastname)
                  ->subject('Airposted Traveler accepted your offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function offerAcceptedByBuyer($receiver_id, $sender_id)
    {
        
        $users  = new User;
        
        $user   = $users->find($sender_id);
        
        $sender = $users->find($receiver_id);
        
        if($user){
            
            
            Mail::send('mails.offer-accepted-by-buyer', ['user' => $user, 'sender'=> $sender], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted Offer accepted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
        $re_user   = $sender;
        
        $re_sender = $user;
        
        if($re_user){
            
            
            Mail::send('mails.offer-accepted-by-buyer-notify-traveler', ['user' => $re_user, 'sender'=> $re_sender], function ($m) use ($re_user) {
                $m->to($re_user->email, $re_user->firstname." ".$re_user->lastname)
                  ->subject('Airposted Buyer accepted your offer')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function messageReply($receiver_id, $sender_id, $message)
    {
        
        $user   = User::find($receiver_id);
        
        $sender = User::find($sender_id);
        
        if($user){
            
            Mail::send('mails.message-reply', ['user' => $user, 'sender'=> $sender, 'details'=>$message], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('New mesage at Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function messageToBuyerTraveller($receiver_id, $sender_id, $subject, $message)
    {
        
        $user   = User::find($receiver_id);
        
        $sender = User::find($sender_id);
        
        if($user){
            
            
            Mail::send('mails.message-to-traveller-buyer', ['user' => $user, 'sender'=> $sender, 'subject'=> $subject, 'details'=>$message], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('New mesage at Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function adminVerifiedPaymentNotifyBuyer($user_id)
    {
        
        $user = User::find($user_id);

        
        if($user){
            try {
                Mail::send('mails.pages.after-order-success', ['user' => $user], function ($m) use ($user) {
                    $m->to($user->email, $user->firstname." ".$user->lastname)
                    ->subject('Airposted Payment Verified')
                    ->from( env('MAIL_INFO') , 'Airposted notification system');
                });      
            } catch (Exception $e) {
                
            }   
        }
        
    }


    public function adminVerifiedPaymentNotifyBuyerFailed($user_id)
    {
        
        $user = User::find($user_id);
        // dd($user);
        
        if($user){       
            try {
                Mail::send('mails.pages.after-order-failed', ['user' => $user], function ($m) use ($user) {
                    $m->to($user->email, $user->firstname." ".$user->lastname)
                    ->subject('Order Cancelled')
                    ->from( env('MAIL_INFO') , 'Airposted notification system');
                });          
            } catch (Exception $e) {
                
            }         
        }      
    }

    public function sendOrderDetailsToAllAdmin($user_id, $invoice_id)
    {
        //dd($buyer_id);
       //$order_products = \App\AwsProductPayment::where('invoice_no', $invoice_id)->get();
       $order_products = DB::table('payment_pre_orders')->leftJoin('buyers', 'buyers.id', '=', 'payment_pre_orders.buyer_id')->where('invoice_no', $invoice_id)->get();
       
        //dd($order_products->first());


        $user = User::find($user_id);    
        //dd($user); 
        //dd($order_products);
        //die();

        //$user = User::find($buyer_id);
        
        if($user){
            try {

                // Test
                // Mail::send('mails.order.success-order-details-send-to-all-admin', ['order_products' => $order_products, 'user' => $user, 'invoice_id' => $invoice_id], function ($m) use ($user) {
                //     $m->to('roddev.radiantiptv@gmail.com', $user->firstname." ".$user->lastname)
                //     ->subject('New Order')
                //     ->from( env('MAIL_INFO') , 'Airposted notification system');
                // });



                Mail::send('mails.order.success-order-details-send-to-all-admin', ['order_products' => $order_products, 'user' => $user, 'invoice_id' => $invoice_id], function ($m) use ($user) {
                    $m->to($user->email, $user->firstname." ".$user->lastname)
                    ->subject('New Order')
                    ->from( env('MAIL_INFO') , 'Airposted notification system');
                });

                Mail::send('mails.order.success-order-details-send-to-all-admin', ['order_products' => $order_products, 'user' => $user, 'invoice_id' => $invoice_id], function ($m) use ($user) {
                    $m->to('sales@airposted.com')
                    ->subject('New Order')
                    ->from( env('MAIL_INFO') , 'Airposted notification system');
                }); 

               /* Mail::send('mails.order.success-order-details-send-to-all-admin', ['order_products' => $order_products, 'user' => $user, 'invoice_id' => $invoice_id], function ($m) use ($user) {
                    $m->to('support@airposted.com')
                    ->subject('New Order')
                    ->from( env('MAIL_INFO') , 'Airposted notification system');
                }); */
                

               

            } catch (Exception $e) {
                
            }   
        }
        
    }

    


    // public function adminVerifiedPaymentNotifyBuyerFailed($user_id)
    // {
        
    //     $user = User::find($user_id);
    //     // dd($user);
        
    //     if($user){       
    //         try {
    //             Mail::send('mails.admin-verified-payment-notify-buyer-failed', ['user' => $user], function ($m) use ($user) {
    //                 $m->to($user->email, $user->firstname." ".$user->lastname)
    //                 ->subject('Airposted Payment Verified')
    //                 ->from( env('MAIL_INFO') , 'Airposted notification system');
    //             });          
    //         } catch (Exception $e) {
                
    //         }         
    //     }      
    // }
    
    
    public function adminVerifiedPaymentNotifyTraveler($traveler_id)
    {
        
        $user = User::find($traveler_id);
        
        if($user){
            
            
            Mail::send('mails.admin-verified-payment-notify-traveler', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted Payment Verified')
                  ->from( env('MAIL_INFO') , 'Airposted notification system')
                  ;
            });
            
        }
        
    }
    
    
    public function contactToAdmin($request)
    {
        
        Mail::send('mails.contact-to-admin', ['request'=>$request], function ($message) use ($request) {
            $message->to( 'help@airposted.com' , 'Airposted support contact')
                    ->from( env('MAIL_SUPPORT') , 'Notification System')
    	            ->subject('New Contact Request has arrived at Airposted');
    	    
    	});
        
    }

    
    public function contactToRequester($request)
    {
        
        Mail::send('mails.contact-to-requester', ['request'=>$request], function ($message) use ($request) {
                            $message->to( $request['email'], 'To whom it may concern')
                                    ->from( env('MAIL_SUPPORT') , 'Airposted Notification System')
                    	            ->subject('Message received by Airposted')
                    	            ;
                    	    
                    	});
        
    }
    

   


    
    public function labelPurchaseFailedInsufficientBalanceAdmin($error, $user, $payment)
    {
        
        if($user){

            Mail::send('mails.label-purchase-failed-insufiicient-balance-admin', ['user' => $user, 'error'=> $error, 'payment'=> $payment], function ($m) use ($user) {
                $m->to( env('MAIL_INFO'), "Airposted Admin")
                  ->subject('Warning! Insufficient balance in Pitneybowes. Buyer charged but label not Purchased.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });

        }
        
    }
    
   
    public function labelPurchaseFailedInsufficientBalanceUser($error, $user, $payment)
    {
        
        if($user){
            
            Mail::send('mails.label-purchase-failed-insufiicient-balance-user', ['user' => $user, 'error'=> $error, 'payment'=> $payment], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Shipping Label purchased failed. We are taking care of your Payment.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function labelPurchaseFailedUnknownIssueAdmin($error, $user, $payment)
    {
        
        if($user){
            
            Mail::send('mails.label-purchase-failed-unknown-issue-admin', ['user' => $user, 'error'=> $error, 'payment'=> $payment], function ($m) use ($user) {
                $m->to( env('MAIL_INFO'), "Airposted Admin")
                  ->subject('Warning! Pitneybowes label purchased failed - Unknown Issue. Buyer was charged.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function labelPurchaseFailedUnknownIssueUser($error, $user, $payment)
    {
        
        if($user){
            
            Mail::send('mails.label-purchase-failed-unknown-issue-user', ['user' => $user, 'error'=> $error, 'payment'=> $payment], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Shipping Label purchased Failed. We are taking care of your payment.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function labelPurchaseSuccessNotifyBuyer($user, $label)
    {
        
        $label = \App\Label::find($label->id);
        
        if($user){
            
            Mail::send('mails.label-purchase-success-user', ['user' => $user, 'label'=> $label], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted Shipping Label purchased Success.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function labelPurchaseSuccessNotifyAdmin($user, $label, $payment)
    {
        
        if($user){
            
            Mail::send('mails.label-purchase-success-admin', ['user' => $user, 'label'=> $label, 'payment'=> $payment], function ($m) use ($user) {
                $m->to( env('MAIL_INFO'), "Airposted Admin")
                  ->subject('Shipping Label has been purchased.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }


    public function manual_request_accept_or_decline_email_to_shopper($request)
    {
        // pr($request,1);
        //$url = url('/products/custom/add-to-cart/'.urlencode(base64_encode($items->id)));
        //pr($url,1);
        //dd($request);
        if($request->status==2):
            try {

                Mail::send('mails.pages.manual-product-request-cencelled', ['items' => $request], function ($m) use ($request) {
                    $m->to($request->customer_email, $request->customer_name)
                      ->subject('Airposted - Order placed request has been declined.')
                      ->from( env('MAIL_INFO') , 'Airposted notification system');
                });
                
            } catch (Exception $e) {
                
            }     
        elseif($request->status==1):
            try {

                Mail::send('mails.pages.manual-product-request-approved', ['items' => $request], function ($m) use ($request) {
                    $m->to($request->customer_email, $request->customer_name)
                      ->subject('Airposted - Order has been placed successfully.')
                      ->from( env('MAIL_INFO') , 'Airposted notification system');
                });
                
            } catch (Exception $e) {
                
            }
        endif;   
    }



     public function sendInvitation($request)
    {
        $request = (object) $request; 
        $body =  url('/invitation/'.base64_encode(auth()->user()->id));

        try {
            Mail::send('mails.pages.invite-friend-email-to-user', ['items' => $body], function ($m) use ($request) {
                $m->to($request->email)
                  ->subject('Airposted - Friend Invitation')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        } catch (Exception $e) {
            
        }    
    }
    
   
    public function orderPlacedUser($user)
    {
          
        if($user){
            
            Mail::send('mails.order-placed-success-user', ['user' => $user], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted - Order has been placed successfully.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function orderPlacedAdmin($user, $order)
    {
        
        if($user){
            
            Mail::send('mails.order-place-success-admin', ['user' => $user, 'order'=> $order], function ($m) use ($user) {
                $m->to( env('MAIL_INFO'), "Airposted Admin")
                  ->subject('New purchase order has been placed.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function OrderStatusUpdateToUser($order, $user)
    {
        
        
        
        if($user){
            
            Mail::send('mails.order-status-update-user', ['user' => $user, 'order'=> $order], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted - Order status has been updated.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
    
    public function paymentReceivedOrderProductUser($order, $payment, $user)
    {
        
        
        
        if($user){
            
            Mail::send('mails.payment-received-order-product-user', ['user' => $user, 'order'=> $order, 'payment'=> $payment], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted - Your payment has been received successfully.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function paymentReceivedOrderProductAdmin($order, $payment, $user)
    {
        
        if($user){
            
            Mail::send('mails.payment-received-order-product-admin', ['user' => $user, 'order'=> $order, 'payment'=> $payment], function ($m) use ($user) {
                $m->to( env('MAIL_INFO'), "Airposted Admin")
                  ->subject('New payment has arrived for Ordered Products.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
   
    
    public function paymentReceivedOrderShippingUser($order, $payment, $user)
    {
        
        
        
        if($user){
            
            Mail::send('mails.payment-received-order-shipping-user', ['user' => $user, 'order'=> $order, 'payment'=> $payment], function ($m) use ($user) {
                $m->to($user->email, $user->firstname." ".$user->lastname)
                  ->subject('Airposted - Your payment has been received successfully.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
   
    public function paymentReceivedOrderShippingAdmin($order, $payment, $user)
    {
        
        if($user){
            
            Mail::send('mails.payment-received-order-shipping-admin', ['user' => $user, 'order'=> $order, 'payment'=> $payment], function ($m) use ($user) {
                $m->to( env('MAIL_INFO'), "Airposted Admin")
                  ->subject('Payment has been received for Order shipping.')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
    
    
    public function notification($notification_id)
    {
        
        $notification = \App\Notification::find( $notification_id );
        
        $user = $notification ? \App\User::find( $notification->notification_to ) : null;
        
        if($notification && $user){
            
            Mail::send('mails.notification', [ 'user' => $user, 'notification'=> $notification ], function ($m) use ($user) {
                $m->to( env('MAIL_INFO'), "Airposted Admin")
                  ->subject('You received a new notification at Airposted')
                  ->from( env('MAIL_INFO') , 'Airposted notification system');
            });
            
        }
        
    }
   
    
}
