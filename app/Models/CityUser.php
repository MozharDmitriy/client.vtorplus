<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityUser extends Model
{
    protected $table = 'city_user';
    protected $primaryKey = 'city_user_id';
    public $timestamps = false;
}
