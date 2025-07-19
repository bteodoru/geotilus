<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borehole extends Model
{
    use HasFactory;

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function samples()
    {
        return $this->hasMany(Sample::class);
    }

    public function strata()
    {
        return $this->hasMany(Stratum::class);
    }
}
