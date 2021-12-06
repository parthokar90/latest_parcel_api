<?php

namespace App\Http\Middleware;

use Closure;
use DB;
use Illuminate\Http\Request;

class PersonalMiddleware
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

        // if token empty
        $acceptHeader = $request->header('Authorization');
        if($acceptHeader==null){
            return response()->json([], 400);
        }

        // if token found but not
       $check_token=DB::table('personal_infos')->where('auth_access_token',$acceptHeader)->first();
       if($check_token==null){
        return response()->json([], 400);
       }

      //token exact match
      if($check_token->auth_access_token==$acceptHeader){
        return $next($request);
      }else{
        return response()->json([], 400);
      }







    }
}
