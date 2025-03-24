<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'morning_in',
        'lunch_out',
        'afternoon_in',
        'afternoon_out',
        'evening_in',
        'evening_out',
        'day_type',
        'status',
        'work_hours'
    ];

    protected $casts = [
        'morning_in' => 'datetime:H:i',
        'lunch_out' => 'datetime:H:i',
        'afternoon_in' => 'datetime:H:i',
        'afternoon_out' => 'datetime:H:i',
        'evening_in' => 'datetime:H:i',
        'evening_out' => 'datetime:H:i',
        'work_hours' => 'float'
    ];

    public function getIsCompleteAttribute()
    {
        return $this->morning_in 
            && $this->lunch_out 
            && $this->afternoon_in 
            && $this->afternoon_out 
            && $this->evening_in 
            && $this->evening_out;
    }

    public function setMorningInAttribute($value)
    {
        $this->attributes['morning_in'] = $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function setLunchOutAttribute($value)
    {
        $this->attributes['lunch_out'] = $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function setAfternoonInAttribute($value)
    {
        $this->attributes['afternoon_in'] = $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function setAfternoonOutAttribute($value)
    {
        $this->attributes['afternoon_out'] = $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function setEveningInAttribute($value)
    {
        $this->attributes['evening_in'] = $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function setEveningOutAttribute($value)
    {
        $this->attributes['evening_out'] = $value ? Carbon::parse($value)->format('H:i') : null;
    }
    

    public function employee(){
        return $this->belongsTo(Employee::class, 'employee_id');
    }

}
