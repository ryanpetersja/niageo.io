<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->decimal('uptime_score', 5, 2)->nullable()->after('server_count');
            $table->json('service_snapshot')->nullable()->after('uptime_score');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['uptime_score', 'service_snapshot']);
        });
    }
};
