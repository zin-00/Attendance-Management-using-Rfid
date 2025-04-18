<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(){
        $user = Auth::id();
        $schedules = Schedule::all();

        return Inertia::render('Schedule/Index', [
            'user' => $user,
            'schedules' => $schedules,
        ]);
        
    }

    public function store(Request $request){

        $validated = $request->validate([
            'name'                      => ['required', 'string', 'min:3'],
            'morning_in'                => ['required', 'string'],
            'morning_out'               => ['required', 'string'],
            'afternoon_in'              => ['required', 'string'],
            'afternoon_out'             => ['required', 'string'],
            'scan_allowance_minutes'    => ['required', 'string'],
            'late_minutes'              => ['required', 'string'],
            'isSet'                     => ['boolean'],
        ]);

        Schedule::create($validated);
        $schedules = Schedule::all();

        return response()->json([
            'data' => $validated,
            'schedules' => $schedules,
            'message' => 'Created successfully',
        ]);
    }
    public function update(Request $request, $id){
        $update = Schedule::findOrFail($id);
        $validated = $request->validate([
            'name'                      => ['required', 'string'],
            'morning_in'                => ['required', 'string'],
            'morning_out'               => ['required', 'string'],
            'afternoon_in'              => ['required', 'string'],
            'afternoon_out'             => ['required', 'string'],
            'scan_allowance_minutes'    => ['required', 'string'],
            'late_minutes'              => ['required', 'string'],
            'isSet'                     => ['boolean'],

        ]);
        if($validated){
            $update->update($validated);
            $schedules = Schedule::all();
            return response()->json([
                'schedules' => $schedules,
                'success' => 'Updated successfully.',
            ]);
        }

        return response()->json([
            'error' => 'Failed to update.'
        ]);
    }
    public function isSet($id){
        Schedule::query()->update(['isSet' => 0]);
        
        $update = Schedule::findOrFail($id);
        $update->isSet = 1;
        $update->save();

        return response()->json([
            'message' => 'Schedule activated successfully',
            'schedules' => Schedule::all()
        ]);
        
    }

    public function destroy($id){
        $destroy = Schedule::findOrFail($id);
        $execute = $destroy->delete();

        if($execute){
            $schedules = Schedule::all();
            return response()->json([
                'success'   => 'Deleted successfully',
                'schedules' => $schedules,
            ]);
        }
        return response()->json(['error' => 'Error']);
        
    }
}
