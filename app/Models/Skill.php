<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Uuids;

class Skill extends Model
{
    use HasFactory, Uuids;

    protected $primaryKey = 'id';

    // relationships

    public function users()
    {
        return $this->belongsToMany(User::class)->using(SkillUser::class);
    }

    public function positions()
    {
        return $this->belongsToMany(Position::class)->using(PositionSkill::class);
    }
}
