<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_optional')->default(true);
            $table->boolean('is_recommended')->default(false);
            $table->integer('sort_order')->default(0);
            $table->text('business_value_statement')->nullable();
            $table->text('effort_description')->nullable();
            $table->text('deliverable_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_items');
    }
};
