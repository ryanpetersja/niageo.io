<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->json('raw_server_activity')->nullable()->after('raw_commits');
            $table->json('server_summary')->nullable()->after('ai_summary');
            $table->unsignedInteger('server_count')->default(0)->after('repo_count');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['raw_server_activity', 'server_summary', 'server_count']);
        });
    }
};
