<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sample extends Model
{
    use HasFactory;

    public function borehole()
    {
        return $this->belongsTo(Borehole::class);
    }

    public function granulometry()
    {
        return $this->hasOne(Granulometry::class);
    }

    public function waterContent()
    {
        return $this->hasOne(WaterContent::class);
    }

    public function bulkDensity()
    {
        return $this->hasOne(BulkDensity::class);
    }

    public function particleDensity()
    {
        return $this->hasOne(ParticleDensity::class);
    }

    public function plasticity()
    {
        return $this->hasOne(AtterbergLimit::class);
    }

    public function soilType()
    {
        return $this->hasOne(SoilType::class);
    }
}
