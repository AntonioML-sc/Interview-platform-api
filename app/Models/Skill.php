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
        return $this->belongsToMany(User::class)
            ->using(SkillUser::class)
            ->withPivot('creator')
            ->withTimestamps();
    }

    public function positions()
    {
        return $this->belongsToMany(Position::class)->using(PositionSkill::class)->withTimestamps();
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class, 'skill_marks', 'skill_id', 'test_id')
            ->using(SkillMark::class)
            ->withPivot('id', 'mark')
            ->as('marks')
            ->withTimestamps();
    }
}
