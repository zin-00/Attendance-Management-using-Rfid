<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->onDelete('cascade');
            $table->date('date');

            $table->enum('morning_status', ['✓', 'x'])->default('x');
            $table->enum('afternoon_status', ['✓', 'x'])->default('x');
            $table->enum('evening_status', ['✓', 'x'])->default('x');

            $table->string('final_status')->nullable();
            $table->string('remarks')->nullable();

            $table->decimal('total_work_hours', 5, 2)->default(0.00);
            $table->boolean('is_manual_edit')->default(false);

            $table->unique(['employee_id', 'date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};
