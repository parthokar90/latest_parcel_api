<?php
namespace App\Traits;
use Illuminate\Http\Request;
trait PersonalAuth{

    //this function for current user
    public function currentUser($request){

        return $request;
       
        // return $currentUser->id;
    }
}