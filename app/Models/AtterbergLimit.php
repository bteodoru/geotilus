<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtterbergLimit extends Model
{
    use HasFactory;
    protected $fillable = ['sample_id', 'liquid_limit', 'plastic_limit'];
}
