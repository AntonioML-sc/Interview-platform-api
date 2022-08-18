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
        return $this->belongsToMany(User::class)->using(TestUser::class);
    }

    public function skillMarks()
    {
        return $this->hasMany(SkillMark::class);
    }
}