<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->default('android');
            $table->string('version', 40); // e.g. 0.2.0
            $table->unsignedInteger('build_number'); // increases with every build (the CI run number)
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('sha256', 64);
            $table->string('git_sha', 40)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at');
            $table->timestamps();

            $table->index(['platform', 'build_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
