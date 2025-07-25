<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function switchProject($project)
    {
        if (is_null($project)) {
            $this->forceFill([
                'current_project_id' => null,
            ])->save();
            return true;
        }

        if (! $this->hasAccessToProject($project)) {
            return false;
        }

        $this->forceFill([
            'current_project_id' => $project->id,
        ])->save();

        return true;
    }

    public function hasAccessToProject($project)
    {
        return $this->id === $project->user_id;
    }

    public function currentProject()
    {
        return $this->belongsTo(Project::class, 'current_project_id');
    }
}
