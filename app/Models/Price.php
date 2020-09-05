<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Price extends Model
{
	use SoftDeletes;

    public $timestamps = false;

    protected $table = 'price';
    protected $primaryKey = 'price_id';
    protected $fillable = ['city_id', 'waste_id', 'price'];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function waste()
    {
        return $this->belongsTo(Waste::class, 'waste_id');
    }
}
