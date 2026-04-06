<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('label'); // e.g. "Production", "Staging"
            $table->string('host'); // IP or hostname
            $table->unsignedInteger('port')->default(22);
            $table->string('username')->default('root');
            $table->enum('auth_type', ['key', 'password'])->default('key');
            $table->string('private_key_path')->nullable(); // path to SSH key on this machine
            $table->text('encrypted_password')->nullable(); // Laravel Crypt encrypted
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['client_id', 'host', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_servers');
    }
};
