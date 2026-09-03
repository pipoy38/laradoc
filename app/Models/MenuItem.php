<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'url', 'new_tab', 'sort_order'];

    protected $casts = ['new_tab' => 'bool'];
}
