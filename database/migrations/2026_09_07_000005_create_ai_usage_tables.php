<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature', 40)->index();
            $table->string('model', 80);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('estimated_cost', 12, 6)->default(0);
            $table->boolean('price_known')->default(true);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('ai_budgets', function (Blueprint $table) {
            $table->id();
            $table->decimal('monthly_budget', 10, 2)->nullable();
            $table->decimal('daily_budget', 10, 2)->nullable();
            $table->string('action', 20)->default('warn');
            $table->decimal('credits_balance', 10, 2)->nullable();
            $table->date('credits_balance_at')->nullable();
            $table->string('alert_emails')->nullable();
            $table->json('alerts_sent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_budgets');
        Schema::dropIfExists('ai_usage_logs');
    }
};
