<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;

class User extends BaseModel
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['password'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}