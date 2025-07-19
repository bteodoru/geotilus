<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticleDensity extends Model
{
    use HasFactory;
    protected $fillable = ['sample_id', 'particle_density'];
}
