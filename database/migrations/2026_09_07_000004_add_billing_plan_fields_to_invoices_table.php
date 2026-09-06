<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('billing_plan_id')->nullable()->after('pricing_preset_id')->constrained()->nullOnDelete();
            $table->date('period_start')->nullable()->after('billing_plan_id');
            $table->date('period_end')->nullable()->after('period_start');

            // One invoice per plan and billing period.
            $table->unique(['billing_plan_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['billing_plan_id', 'period_start']);
            $table->dropConstrainedForeignId('billing_plan_id');
            $table->dropColumn(['period_start', 'period_end']);
        });
    }
};
