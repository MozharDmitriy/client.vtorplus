<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Waste extends Model
{
	use SoftDeletes;

    protected $table = 'waste';
    protected $primaryKey = 'waste_id';
    protected $dates = ['deleted_at'];

    public function price()
    {
        return $this->hasMany(Price::class);
    }
}
