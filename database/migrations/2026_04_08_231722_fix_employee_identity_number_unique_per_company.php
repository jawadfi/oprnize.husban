<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: identity_number was globally unique across ALL companies.
 *
 * Problem: Two separate provider companies cannot employ the same person
 * (same iqama / identity number) because a single global UNIQUE index
 * prevents a second row with the same identity_number even when the
 * company_id differs. The second import would fail silently with a
 * constraint violation, leaving that company with no employees of their own.
 *
 * Fix: Replace the global unique index with a compound unique index on
 * (identity_number, company_id) so that the uniqueness check is scoped
 * per company. Two different provider companies CAN now both employ the
 * same person independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop the old global unique constraint
            $table->dropUnique(['identity_number']);

            // Add a per-company unique constraint
            $table->unique(['identity_number', 'company_id'], 'employees_identity_number_company_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_identity_number_company_id_unique');
            $table->unique('identity_number');
        });
    }
};
