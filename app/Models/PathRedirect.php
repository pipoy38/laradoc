<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PathRedirect extends Model
{
    protected $fillable = ['from_path', 'to_path', 'hits'];
}
