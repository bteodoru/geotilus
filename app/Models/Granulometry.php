<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Granulometry extends Model
{
    use HasFactory;
    protected $fillable = ['sample_id', 'clay', 'silt', 'sand', 'gravel', 'cobble', 'boulder'];


    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }
}
