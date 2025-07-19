<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkDensity extends Model
{
    use HasFactory;
    protected $fillable = ['sample_id', 'bulk_density'];
}
