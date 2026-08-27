<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;

class Uom extends BaseModel
{
    protected $table = 'uoms';

    protected $fillable = [
        'code',
        'name',
        'created_by',
        'updated_by',
    ];
}