<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_bills', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('category')->default('other'); // hosting, email, devops, workspace, other
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, annual
            $table->date('next_due_date');
            $table->string('status')->default('upcoming'); // upcoming, due_soon, overdue, paid
            $table->datetime('last_paid_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_renew')->default(true);
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_bills');
    }
};
