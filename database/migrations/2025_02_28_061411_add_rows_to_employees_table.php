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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('street_address')->after('emergency_contact_number')->nullable();
            $table->string('city')->after('street_address')->nullable();
            $table->string('state')->after('city')->nullable();
            $table->string('zip_code')->after('state')->nullable();
            $table->string('country')->after('zip_code')->nullable();
            $table->string('hire_date')->after('country')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
};
