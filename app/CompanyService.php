<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyService extends Model
{
    protected $table = "company_service";

    protected $fillable = ['id', 'company_name', 'company_id', 'client_id','client_secret','status','expired_at','created_at', 'updated_at'];
}
