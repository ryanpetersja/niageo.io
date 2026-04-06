<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('owner');
            $table->string('repo_name');
            $table->string('default_branch')->default('main');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['client_id', 'owner', 'repo_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_repositories');
    }
};
