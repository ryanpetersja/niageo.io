<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->enum('billing_cycle', ['monthly', 'quarterly'])->default('monthly');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('next_period_start')->nullable();
            $table->unsignedSmallInteger('issue_days_before')->default(0);
            $table->unsignedSmallInteger('due_days')->default(30);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('title_template')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->enum('status', ['active', 'paused', 'ended'])->default('active');
            $table->timestamps();

            $table->index(['status', 'next_period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
