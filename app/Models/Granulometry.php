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

    public function getCuAttribute()
    {
        if (isset($this->d10, $this->d60) && $this->d10 > 0) {
            return $this->d60 / $this->d10;
        }
        return null;
    }

    public function getCcAttribute()
    {
        if (isset($this->d10, $this->d30, $this->d60) && $this->d10 > 0 && $this->d60 > 0) {
            return ($this->d30 ** 2) / ($this->d10 * $this->d60);
        }
        return null;
    }
    public function getFineFraction()
    {
        return $this->clay + $this->silt;
    }

    public function getCoarseFraction()
    {
        return $this->sand + $this->gravel + $this->cobble + $this->boulder;
    }
}
