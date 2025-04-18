<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'morning_in',
        'morning_out',
        'afternoon_in',
        'afternoon_out',
        'scan_allowance_minutes',
        'late_minutes',
        'isSet',
    ];
}
