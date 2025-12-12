<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('job_title');
            $table->string('emp_id')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('iqama_no')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('identity_number')->unique();
            $table->string('nationality');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_assigned_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
