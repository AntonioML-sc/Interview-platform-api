<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

use App\Traits\Uuids;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, Uuids;

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'title',
        'description',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // jwt

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // relationships

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class)
            ->using(SkillUser::class)
            ->withPivot('creator')
            ->withTimestamps();
    }

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'applications', 'user_id', 'position_id')
            ->using(Application::class)
            ->withPivot('status')
            ->as('application')
            ->withTimestamps();
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class)
            ->using(TestUser::class)
            ->withPivot('user_type')
            ->withTimestamps();
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
