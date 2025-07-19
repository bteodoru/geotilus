<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stratum extends Model
{
    use HasFactory;

    protected $fillable = ['borehole_id', 'soil_type', 'depth_from', 'depth_to', 'note'];


    public function borehole()
    {
        return $this->belongsTo(Borehole::class);
    }
}
