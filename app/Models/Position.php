<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Uuids;

class Position extends Model
{
    use HasFactory, Uuids;

    protected $primaryKey = 'id';

    // relationships

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class)->using(PositionSkill::class)->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'applications', 'position_id', 'user_id')
            ->using(Application::class)
            ->withPivot('status')
            ->as('application')
            ->withTimestamps();
    }
}
