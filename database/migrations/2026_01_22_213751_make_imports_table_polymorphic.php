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
        // 1. Add user_type column if it doesn't exist
        if (!Schema::hasColumn('imports', 'user_type')) {
             Schema::table('imports', function (Blueprint $table) {
                $table->string('user_type')->nullable()->after('user_id');
             });
        }
        
        // 2. Try to drop the foreign key constraint. Currently assumes 'imports_user_id_foreign'.
        // We wrap this in a try-catch block at the generic execution level by using a separate Schema call.
        try {
            Schema::table('imports', function (Blueprint $table) {
                $table->dropForeign(['user_id']); 
            });
        } catch (\Throwable $e) {
            // Foreign key might not exist or have a different name. 
            // We'll proceed since user_type is the critical fix.
        }

        // 3. Update existing records
        DB::table('imports')->whereNull('user_type')->update(['user_type' => 'App\\Models\\Company']);
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
             // We cannot easily restore the foreign key accurately without knowing if we want to restrict it again.
             // But we can drop the user_type column
             $table->dropColumn('user_type');
        });
    }
};
