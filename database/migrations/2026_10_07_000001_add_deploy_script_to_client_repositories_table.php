<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_repositories', function (Blueprint $table) {
            // The site's standard deploy script (e.g. Forge): code reviews only report steps it does not cover.
            $table->text('deploy_script')->nullable()->after('default_branch');
        });
    }

    public function down(): void
    {
        Schema::table('client_repositories', function (Blueprint $table) {
            $table->dropColumn('deploy_script');
        });
    }
};
