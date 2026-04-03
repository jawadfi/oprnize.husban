<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('passport_expiry')->nullable()->after('nationality');
            $table->date('visa_expiry')->nullable()->after('passport_expiry');
            $table->integer('vacation_balance')->default(21)->after('visa_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['passport_expiry', 'visa_expiry', 'vacation_balance']);
        });
    }
};
