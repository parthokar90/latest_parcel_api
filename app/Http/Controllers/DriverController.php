<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\PreOrder;
use App\Personal;
use App\Order;
use DB;
use Log;
use Validator;
use Auth;
use DateTime;
use Firebase\FirebaseLib;
use App\CompanyService;
use App\DeliveryChargeModel;
use App\LogisticsAdditional;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;
use Illuminate\Support\Str;
use config;

class DriverController extends Controller
{

    //Auth User ID
    public function loginUser(Request $request){
        $acceptHeader = $request->header('Authorization');
        $user=DB::table('driver_infos')->where('auth_access_token',$acceptHeader)->first();
        return $user->id;
    }

    // map range check
    public function mapRangeCheck($polygon,$point){

        if($polygon[0] != $polygon[count($polygon)-1]){
        $polygon[count($polygon)] = $polygon[0];
        $j = 0;
        $oddNodes = false;
        $x = $point[1];
        $y = $point[0];
        $n = count($polygon);
        for ($i = 0; $i < $n; $i++)
        {
            $j++;
            if ($j == $n)
            {
                $j = 0;
            }
            if ((($polygon[$i][0] < $y) && ($polygon[$j][0] >= $y)) || (($polygon[$j][0] < $y) && ($polygon[$i][0] >=
                $y)))
            {
                if ($polygon[$i][1] + ($y - $polygon[$i][0]) / ($polygon[$j][0] - $polygon[$i][0]) * ($polygon[$j][1] -
                    $polygon[$i][1]) < $x)
                {
                    $oddNodes = !$oddNodes;
                }
            }
        }
        return $oddNodes;
        }

    }

    // prefered area liss add
    public function preferedAreaListAdd(Request $request){

        $login_id=$this->loginUser($request);

        $prefered_area_range = DB::table('prefered_areas')->where('driver_id',$login_id)->where('area_id',$request->area_id)->first();

        if ($prefered_area_range != null)
        {
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'added already'
            ];
        }

        try {

            $prefered_area_range = DB::table('prefered_areas')->insert(
                                ['area_id' => $request->area_id, 'driver_id' => $login_id]
                            );
            return [
                'status' => 200,
                'success' => true,
                'msg' => 'Area added successfully'
            ];

        } catch (Exception $e) {
            return [
                'status' => 200,
                'success' => true,
                'msg' => $e->getMessage()
            ];
        }
    }

}
