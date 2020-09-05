<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
	use SoftDeletes;

    public $timestamps = false;
    protected $table = 'city';
    protected $primaryKey = 'city_id';
    protected $dates = ['deleted_at'];

    public function point()
    {
        return $this->hasMany(Point::class);
    }

    public function price()
    {
        return $this->hasMany(Price::class);
    }
}
