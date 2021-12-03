<?php

namespace App;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Personal extends Authenticatable
{
    use Notifiable,HasApiTokens;
    protected $table ="personal_infos";
    protected $guard = 'api-personal';
}
