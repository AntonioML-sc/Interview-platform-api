<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Uuids;

class SkillMark extends Model
{
    use HasFactory, Uuids;

    protected $primaryKey = 'id';

    // relationships

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
