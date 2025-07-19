<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterContent extends Model
{
    use HasFactory;
    protected $fillable = ['sample_id', 'water_content'];
}
