<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();
            $table->foreignId('client_company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();
            // pending | approved | declined
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['provider_company_id', 'client_company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_connections');
    }
};
