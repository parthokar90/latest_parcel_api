<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\PreOrder;

class OrderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PreOrder::factory()->count(50)->create();
    }
}
