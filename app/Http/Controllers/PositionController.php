<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(){
        return Inertia::render('Employees/ManageEmployee',[
            'positions' => Position::all(),
        ]);
    }
}
