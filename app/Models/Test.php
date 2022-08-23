<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Uuids;

class Test extends Model
{
    use HasFactory, Uuids;

    protected $primaryKey = 'id';

    // relationships

    public function users()
    {
        return $this->belongsToMany(User::class)->using(TestUser::class)->withTimestamps();
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'skill_marks', 'test_id', 'skill_id')->using(SkillMark::class)->withPivot('id', 'mark')->as('marks')->withTimestamps();
    }
}
