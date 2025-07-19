<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DerivedSoilPhaseIndex extends Model
{
    use HasFactory;
    protected $fillable = [
        'sample_id',
        'dry_density',
        'degree_of_saturation',
        'voids_ratio',
        'porosity',
        'moisture_content_at_saturation',
        'saturated_density',
        'submerged_density'
    ];
}
