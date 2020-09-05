<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Point extends Model
{
	use SoftDeletes;

    protected $table = 'point';
    protected $primaryKey = 'point_id';
    protected $dates = ['deleted_at'];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
