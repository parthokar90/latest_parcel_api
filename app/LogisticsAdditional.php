<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogisticsAdditional extends Model
{
    protected $table = "logistics_addional_infos";

    protected $fillable = ['id', 'NID', 'drive_lisence', 'address','gender', 'date_of_birth', 'user_id','verified'];

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
