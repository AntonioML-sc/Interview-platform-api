<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

use App\Traits\Uuids;

class SkillMark extends Pivot
{
    use HasFactory, Uuids;

    protected $table = 'skill_marks';
    protected $primaryKey = 'id';
}
