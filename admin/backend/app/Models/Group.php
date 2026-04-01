<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'course_groups';

    protected $fillable = [
        'organization_id',
        'name',
        'category',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
