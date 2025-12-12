<?php

use App\Enums\CompanyTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('commercial_registration_number');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('email_verified_at')->nullable();
            $table->enum('type', CompanyTypes::getValues());
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
