<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $connection = 'system';
    protected $guarded = [];
    protected $casts = ['configs' => 'json'];
    protected $dates = ['deleted_at'];
}
