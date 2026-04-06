<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "TruBlu EHR Production"
            $table->string('url'); // full URL to check
            $table->unsignedInteger('check_interval_minutes')->default(5);
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->unsignedInteger('degraded_threshold_ms')->default(2000);
            $table->enum('current_status', ['up', 'degraded', 'down'])->default('down');
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('last_response_time_ms')->nullable();
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->text('last_error_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_endpoints');
    }
};
