<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    protected $table = 'positions';
    protected $fillable = ['name', 'description', 'salary'];

    protected $casts =['salary' => 'float'];

    public function employees(){
        return $this->hasMany(Employee::class);
    }
    public function employee(){
        return $this->hasMany(Employee::class);
    }

}
