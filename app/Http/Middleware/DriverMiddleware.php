<?php

namespace App\Http\Middleware;

use Closure;
use DB;
use Illuminate\Http\Request;

class DriverMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $acceptHeader = $request->header('Authorization');

        if($acceptHeader==null){
            return response()->json([], 400);
        }

        $check_token=DB::table('driver_infos')->where('auth_access_token',$acceptHeader)->first();

        if($check_token==null){
            return response()->json([], 400);
        }

        if($check_token->auth_access_token==$acceptHeader){
            return $next($request);
        }else{
            return response()->json([], 400);
        }
    }
}
