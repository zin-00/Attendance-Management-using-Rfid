<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date')->default(DB::raw('CURRENT_DATE'));
            $table->string('day_type')->default('Regular');
            $table->string('status')->default('Absent');
            
            $table->time('morning_in')->nullable();
            $table->time('lunch_out')->nullable();
            $table->time('afternoon_in')->nullable();
            $table->time('afternoon_out')->nullable();
            
            $table->time('evening_in')->nullable();
            $table->time('evening_out')->nullable();
            
            $table->decimal('work_hours', 5, 2)->default(0);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};

