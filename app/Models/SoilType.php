<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoilType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'method', 'description', 'sample_id'];

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }
}
