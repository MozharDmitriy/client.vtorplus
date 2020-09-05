<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryWaste extends Model
{
    protected $table = 'delivery_waste';
    protected $primaryKey = 'delivery_waste_id';
    protected $guarded = ['delivery_waste_id'];
}
