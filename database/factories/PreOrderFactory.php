<?php

namespace Database\Factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use App\User;
use App\PreOrder;
use App\Order;
use DB;

class PreOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PreOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */


    public function definition()
    {
     
       //order table insert
       for($i=0;$i<5000;$i++){
          $pre = new PreOrder;
          $pre->recp_name=$this->faker->name();
          $pre->recp_phone=+8801307384145;
          $pre->recp_area=rand(1,72);
          $pre->invoice_no='AIR'.rand();
          $pre->save();

          $order=new Order;
          $order->invoice_no=$pre->invoice_no;
          $order->user_id=User::pluck('id')->random();
          $order->delivery_charge=500;
          $order->order_type=rand(1,2);
          $order->current_status=1;
          $order->order_date=date('Y-m-d');
          $order->cod=0;
          $order->coc=1;
          $order->who_will_pay=1;
          $order->save();

          DB::table('order_distance')->insert([
            'sender_latitude'   =>  90.4035.rand(10,100),
            'sender_longitude'   => 23.8046.rand(10,100),
            'receiver_latitude'   =>  90.4157.rand(10,100),
            'receiver_longitude'   =>  3.5000.rand(10,100),
            'distance'   =>  3.500000,
            'invoice_no'   =>  $pre->invoice_no
          ]);




       }
      
    }
}
