<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;
    protected $table = 'employees';

    protected $fillable = [
        'rfid_tag',
        'first_name',
        'last_name',
        'birthdate',
        'street_address',
        'country',
        'city',
        'state',
        'zip_code',
        'hire_date',
        'contact_number',
        'emergency_contact',
        'emergency_contact_number',
        'email',
        'password',
        'gender',
        'status',
        'position_id',
        'profile_image'
    ];
    public function position(){
        return $this->belongsTo(Position::class);
    }
    public function attendaces(){
        return $this->hasMany(Attendance::class);
    }
    }
